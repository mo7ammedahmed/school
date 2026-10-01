<?php

declare(strict_types=1);

namespace Tests\Feature\Assessment;

use App\Domain\Assessment\Services\DocxQuestionParser;
use RuntimeException;
use Tests\TestCase;
use ZipArchive;

/**
 * The import accepts a .docx, which is a zip archive, and reads one member of it
 * into memory with `getFromName()`. Laravel's `max:8192` bounds the *compressed*
 * upload, and those are not the same quantity.
 *
 * A zip entry can declare a decompressed size far larger than the file that
 * carries it, and repetitive text compresses by three orders of magnitude — so a
 * .docx of a few kilobytes, comfortably inside the upload limit, can ask the
 * process to allocate hundreds of megabytes. `loadXML()` then holds that string
 * and a DOM tree built from it at the same time.
 *
 * That is a denial of service available to anyone who can reach the import
 * screen, and it costs one crafted file.
 *
 * The bound is checked against the size the zip's own directory declares, before
 * the member is read — reading it in order to measure it is the thing that hurts.
 */
class DocxImportIsBoundedTest extends TestCase
{
    private DocxQuestionParser $parser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->parser = new DocxQuestionParser;
    }

    public function test_a_document_that_expands_far_beyond_its_upload_size_is_refused(): void
    {
        $path = $this->docxWithBodyOfSize(DocxQuestionParser::MAX_DOCUMENT_XML_BYTES + 1024);

        $this->assertLessThan(
            8192,
            filesize($path),
            'The test document is not small on disk, so it does not demonstrate the point: the upload '
            .'size limit would have refused it before the parser ever ran.',
        );

        $this->expectException(RuntimeException::class);

        $this->parser->extractText($path);
    }

    public function test_the_refusal_explains_itself(): void
    {
        $path = $this->docxWithBodyOfSize(DocxQuestionParser::MAX_DOCUMENT_XML_BYTES + 1024);

        try {
            $this->parser->extractText($path);
        } catch (RuntimeException $exception) {
            $this->assertStringContainsStringIgnoringCase(
                'too large',
                $exception->getMessage(),
                'The message is shown to the teacher on the import screen, so it has to say what went '
                .'wrong rather than only that something did.',
            );

            return;
        }

        $this->fail('The oversized document was accepted.');
    }

    public function test_an_ordinary_question_paper_is_still_read(): void
    {
        $path = $this->docxWithBody([
            'Title: Chapter 4 Quiz',
            '1. What is 2 + 2?',
            'A) 3',
            'B) 4',
            'Answer: B',
        ]);

        $text = $this->parser->extractText($path);

        $this->assertStringContainsString('Chapter 4 Quiz', $text);
        $this->assertStringContainsString('What is 2 + 2?', $text);
    }

    public function test_a_document_without_a_body_is_still_refused_clearly(): void
    {
        $path = $this->zipContaining(['not-a-docx/readme.txt' => 'hello']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/does not contain a readable document body/');

        $this->parser->extractText($path);
    }

    public function test_a_file_that_is_not_a_zip_at_all_is_refused(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'notzip').'.php';
        file_put_contents($path, "<?php echo 'hi';");

        try {
            $this->expectException(RuntimeException::class);
            $this->parser->extractText($path);
        } finally {
            @unlink($path);
        }
    }

    // ------------------------------------------------------------------

    /**
     * A .docx whose `word/document.xml` decompresses to well over the bound while
     * the archive on disk stays tiny.
     */
    private function docxWithBodyOfSize(int $bytes): string
    {
        // A single long run of one character, so the archive on disk is tiny
        // while the member inside it is large — which is the whole point.
        $filler = str_repeat('a', $bytes);
        $body = '<w:p><w:r><w:t>'.$filler.'</w:t></w:r></w:p>';

        return $this->zipContaining([
            '[Content_Types].xml' => '<?xml version="1.0"?><Types/>',
            'word/document.xml' => '<?xml version="1.0" encoding="UTF-8"?>'
                .'<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">'
                .$body
                .'</w:document>',
        ]);
    }

    /**
     * @param  list<string>  $lines
     */
    private function docxWithBody(array $lines): string
    {
        $paragraphs = '';

        foreach ($lines as $line) {
            $paragraphs .= '<w:p><w:r><w:t xml:space="preserve">'
                .htmlspecialchars($line, ENT_QUOTES | ENT_XML1, 'UTF-8')
                .'</w:t></w:r></w:p>';
        }

        return $this->zipContaining([
            '[Content_Types].xml' => '<?xml version="1.0"?><Types/>',
            'word/document.xml' => '<?xml version="1.0" encoding="UTF-8"?>'
                .'<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">'
                .'<w:body>'.$paragraphs.'</w:body>'
                .'</w:document>',
        ]);
    }

    /**
     * @param  array<string, string>  $members
     */
    private function zipContaining(array $members): string
    {
        $path = tempnam(sys_get_temp_dir(), 'docxtest');

        $zip = new ZipArchive;
        $zip->open($path, ZipArchive::OVERWRITE | ZipArchive::CREATE);

        foreach ($members as $name => $contents) {
            $zip->addFromString($name, $contents);
        }

        $zip->close();

        return $path;
    }
}
