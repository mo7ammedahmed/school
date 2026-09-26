<?php

declare(strict_types=1);

namespace App\Domain\Assessment\Services;

use DOMDocument;
use DOMNode;
use Illuminate\Support\Str;
use RuntimeException;
use ZipArchive;

/**
 * Reads a Microsoft Word (.docx) question paper without any third-party
 * dependency: a .docx is a zip archive whose body text lives in
 * `word/document.xml`, so ZipArchive + DOMDocument are enough.
 *
 * The document follows a small, fixed convention (documented in the
 * downloadable template):
 *
 *   Title: Chapter 4 Quiz            (optional)
 *   Description: Covers fractions.   (optional)
 *
 *   1. What is 2 + 2?
 *   A) 3
 *   B) 4
 *   Answer: B
 *   Points: 2
 *
 *   2. Water boils at 100°C at sea level.
 *   Answer: True
 */
class DocxQuestionParser
{
    private const NS = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main';

    private const QUESTION = '/^\s*(?:Q(?:uestion)?\s*)?(\d+)\s*[.):\-]\s*(.+)$/iu';

    private const QUESTION_LABELLED = '/^\s*Q(?:uestion)?\s*[.:]\s*(.+)$/iu';

    private const OPTION = '/^\s*([A-Za-z])\s*[.)]\s*(.+)$/u';

    private const ANSWER = '/^\s*(?:Answer|Correct)\s*[:\-]\s*(.+)$/iu';

    private const POINTS = '/^\s*Points?\s*[:\-]\s*(\d+(?:[.,]\d+)?)\s*$/iu';

    private const TITLE = '/^\s*Title\s*[:\-]\s*(.+)$/iu';

    private const DESCRIPTION = '/^\s*Description\s*[:\-]\s*(.+)$/iu';

    /**
     * Read the plain text of a .docx, one line per paragraph.
     */
    public function extractText(string $path): string
    {
        $zip = new ZipArchive;
        $opened = $zip->open($path);

        if ($opened !== true) {
            throw new RuntimeException('The file is not a readable .docx document.');
        }

        $xml = $zip->getFromName('word/document.xml');
        $zip->close();

        if ($xml === false || $xml === '') {
            throw new RuntimeException('This .docx does not contain a readable document body. Save it as .docx (not .doc) and try again.');
        }

        return $this->paragraphsToText($xml);
    }

    /**
     * Turn raw document text into a structured question paper.
     *
     * @return array{title: ?string, description: ?string, questions: list<array<string, mixed>>, warnings: list<string>}
     */
    public function parse(string $text, ?string $fallbackTitle = null): array
    {
        $title = null;
        $description = null;
        $questions = [];
        $warnings = [];
        $current = null;

        $flush = function () use (&$current, &$questions, &$warnings): void {
            if ($current === null) {
                return;
            }

            $question = $this->finaliseQuestion($current, count($questions) + 1, $warnings);
            if ($question !== null) {
                $questions[] = $question;
            }
        };

        foreach ($this->lines($text) as $line) {
            // Title/Description only apply before the first question.
            if ($current === null && $title === null && preg_match(self::TITLE, $line, $m) === 1) {
                $title = $this->clean($m[1]);

                continue;
            }

            if ($current === null && $description === null && preg_match(self::DESCRIPTION, $line, $m) === 1) {
                $description = $this->clean($m[1]);

                continue;
            }

            if (preg_match(self::QUESTION, $line, $m) === 1) {
                $flush();
                $current = ['prompt' => $this->clean($m[2]), 'options' => [], 'answer' => null, 'points' => null];

                continue;
            }

            if (preg_match(self::QUESTION_LABELLED, $line, $m) === 1) {
                $flush();
                $current = ['prompt' => $this->clean($m[1]), 'options' => [], 'answer' => null, 'points' => null];

                continue;
            }

            if ($current === null) {
                continue;
            }

            if (preg_match(self::ANSWER, $line, $m) === 1) {
                $current['answer'] = $this->clean($m[1]);

                continue;
            }

            if (preg_match(self::POINTS, $line, $m) === 1) {
                $current['points'] = (float) str_replace(',', '.', $m[1]);

                continue;
            }

            if (preg_match(self::OPTION, $line, $m) === 1) {
                $current['options'][] = ['key' => strtoupper($m[1]), 'text' => $this->clean($m[2])];

                continue;
            }

            // A continuation line belongs to the prompt (wrapped sentences).
            $current['prompt'] = trim($current['prompt'].' '.$this->clean($line));
        }

        $flush();

        if ($questions === []) {
            $warnings[] = 'No questions were found. Number each question (for example "1. What is 2 + 2?") and try again.';
        }

        return [
            'title' => $title ?? $fallbackTitle,
            'description' => $description,
            'questions' => $questions,
            'warnings' => $warnings,
        ];
    }

    /**
     * Build a ready-to-fill .docx template and return its path.
     *
     * The directory is caller-supplied because the web server's temp directory
     * is not always writable; the default keeps this usable from a plain unit
     * test without booting Laravel.
     */
    public function buildTemplate(?string $directory = null): string
    {
        $document = $this->documentXml([
            'Question paper template',
            'Title: Example Chapter Quiz',
            'Description: Replace this line with a short description (optional).',
            '',
            '1. What is 2 + 2?',
            'A) 3',
            'B) 4',
            'C) 5',
            'Answer: B',
            'Points: 2',
            '',
            '2. Water boils at 100°C at sea level.',
            'Answer: True',
            'Points: 1',
            '',
            '3. Name the process plants use to make food.',
            'Answer: Photosynthesis',
            'Points: 3',
        ]);

        // An explicit path rather than tempnam(): PHP 8.6 raises a notice for
        // tempnam() and Laravel's error handler turns that into a 500.
        $directory = $directory ?: sys_get_temp_dir();

        if (! is_dir($directory)) {
            mkdir($directory, 0o775, true);
        }

        if (! is_writable($directory)) {
            throw new RuntimeException("The template directory is not writable: {$directory}");
        }

        $path = rtrim($directory, '/\\').DIRECTORY_SEPARATOR.'exam-template-'.Str::uuid().'.docx';
        $zip = new ZipArchive;

        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Unable to build the template document.');
        }

        $zip->addFromString('[Content_Types].xml', <<<'XML'
        <?xml version="1.0" encoding="UTF-8" standalone="yes"?>
        <Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/></Types>
        XML);

        $zip->addFromString('_rels/.rels', <<<'XML'
        <?xml version="1.0" encoding="UTF-8" standalone="yes"?>
        <Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/></Relationships>
        XML);

        $zip->addFromString('word/document.xml', $document);
        $zip->close();

        return $path;
    }

    /**
     * @param  list<string>  $paragraphs
     */
    private function documentXml(array $paragraphs): string
    {
        $body = '';

        foreach ($paragraphs as $paragraph) {
            $escaped = htmlspecialchars($paragraph, ENT_QUOTES | ENT_XML1, 'UTF-8');
            $body .= "<w:p><w:r><w:t xml:space=\"preserve\">{$escaped}</w:t></w:r></w:p>";
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<w:document xmlns:w="'.self::NS.'"><w:body>'.$body.'</w:body></w:document>';
    }

    private function paragraphsToText(string $xml): string
    {
        $dom = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        $dom->loadXML($xml);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $lines = [];

        foreach ($dom->getElementsByTagNameNS(self::NS, 'p') as $paragraph) {
            $lines[] = $this->paragraphText($paragraph);
        }

        return implode("\n", $lines);
    }

    private function paragraphText(DOMNode $paragraph): string
    {
        $text = '';

        foreach ($paragraph->getElementsByTagNameNS(self::NS, 't') as $node) {
            $text .= $node->textContent;
        }

        // Tabs inside a paragraph behave like sentence separators.
        foreach ($paragraph->getElementsByTagNameNS(self::NS, 'tab') as $node) {
            $text .= ' ';
        }

        return $text;
    }

    /**
     * @return list<string>
     */
    private function lines(string $text): array
    {
        $text = str_replace(["\u{00A0}", "\r\n", "\r"], [' ', "\n", "\n"], $text);

        return array_values(array_filter(
            array_map(fn (string $line) => trim($line), explode("\n", $text)),
            fn (string $line) => $line !== '',
        ));
    }

    private function clean(string $value): string
    {
        return trim(preg_replace('/\s+/u', ' ', $value) ?? $value);
    }

    /**
     * @param  array{prompt: string, options: list<array{key: string, text: string}>, answer: ?string, points: ?float}  $current
     * @param  list<string>  $warnings
     * @return array<string, mixed>|null
     */
    private function finaliseQuestion(array $current, int $number, array &$warnings): ?array
    {
        $prompt = $this->clean($current['prompt']);

        if ($prompt === '') {
            $warnings[] = "Question {$number} has no text and was skipped.";

            return null;
        }

        $points = $current['points'] ?? 1.0;
        $answer = $current['answer'] !== null ? $this->clean($current['answer']) : null;

        if ($current['options'] !== []) {
            $resolved = $this->resolveChoiceAnswer($answer, $current['options']);

            if ($resolved === null) {
                $warnings[] = "Question {$number} has options but no clear \"Answer:\" line.";
            }

            return [
                'number' => $number,
                'type' => 'multiple_choice',
                'prompt' => $prompt,
                'options' => $current['options'],
                'answer' => $resolved,
                'points' => $points,
            ];
        }

        if ($answer !== null && in_array(mb_strtolower($answer), ['true', 'false', 't', 'f', 'yes', 'no'], true)) {
            return [
                'number' => $number,
                'type' => 'true_false',
                'prompt' => $prompt,
                'options' => [
                    ['key' => 'A', 'text' => 'True'],
                    ['key' => 'B', 'text' => 'False'],
                ],
                'answer' => in_array(mb_strtolower($answer), ['true', 't', 'yes'], true) ? 'A' : 'B',
                'points' => $points,
            ];
        }

        if ($answer === null) {
            $warnings[] = "Question {$number} has no \"Answer:\" line.";
        }

        return [
            'number' => $number,
            'type' => 'short_answer',
            'prompt' => $prompt,
            'options' => [],
            'answer' => $answer,
            'points' => $points,
        ];
    }

    /**
     * Accept either the option letter ("B") or its exact text ("4").
     *
     * @param  list<array{key: string, text: string}>  $options
     */
    private function resolveChoiceAnswer(?string $answer, array $options): ?string
    {
        if ($answer === null || $answer === '') {
            return null;
        }

        foreach ($options as $option) {
            if (mb_strtoupper($answer) === $option['key']) {
                return $option['key'];
            }
        }

        foreach ($options as $option) {
            if (mb_strtolower($answer) === mb_strtolower($option['text'])) {
                return $option['key'];
            }
        }

        return null;
    }
}
