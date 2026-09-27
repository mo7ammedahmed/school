<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Assessment;

use App\Domain\Assessment\Services\DocxQuestionParser;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class DocxQuestionParserTest extends TestCase
{
    private DocxQuestionParser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new DocxQuestionParser;
    }

    public function test_it_reads_the_title_and_description(): void
    {
        $paper = $this->parser->parse("Title: Chapter 4 Quiz\nDescription: Fractions and decimals.\n\n1. What is 2 + 2?\nA) 3\nB) 4\nAnswer: B");

        $this->assertSame('Chapter 4 Quiz', $paper['title']);
        $this->assertSame('Fractions and decimals.', $paper['description']);
        $this->assertCount(1, $paper['questions']);
    }

    public function test_it_parses_a_multiple_choice_question(): void
    {
        $paper = $this->parser->parse("1. What is 2 + 2?\nA) 3\nB) 4\nC) 5\nAnswer: B\nPoints: 2");

        $question = $paper['questions'][0];

        $this->assertSame('multiple_choice', $question['type']);
        $this->assertSame('What is 2 + 2?', $question['prompt']);
        $this->assertSame(['A', 'B', 'C'], array_column($question['options'], 'key'));
        $this->assertSame('B', $question['answer']);
        $this->assertSame(2.0, $question['points']);
        $this->assertSame([], $paper['warnings']);
    }

    public function test_it_accepts_the_answer_as_option_text(): void
    {
        $paper = $this->parser->parse("1. Capital of Saudi Arabia?\nA) Jeddah\nB) Riyadh\nAnswer: Riyadh");

        $this->assertSame('B', $paper['questions'][0]['answer']);
    }

    public function test_true_false_is_detected_from_the_answer(): void
    {
        $paper = $this->parser->parse("1. Water boils at 100°C at sea level.\nAnswer: True");

        $question = $paper['questions'][0];

        $this->assertSame('true_false', $question['type']);
        $this->assertSame(['True', 'False'], array_column($question['options'], 'text'));
        $this->assertSame('A', $question['answer']);
    }

    public function test_free_text_answers_become_short_answer_questions(): void
    {
        $paper = $this->parser->parse("1. Name the gas plants absorb.\nAnswer: Carbon dioxide");

        $question = $paper['questions'][0];

        $this->assertSame('short_answer', $question['type']);
        $this->assertSame('Carbon dioxide', $question['answer']);
        $this->assertSame([], $question['options']);
    }

    public function test_wrapped_lines_are_joined_into_the_prompt(): void
    {
        $paper = $this->parser->parse("1. Explain why the sky\nappears blue during the day.\nAnswer: Scattering");

        $this->assertSame('Explain why the sky appears blue during the day.', $paper['questions'][0]['prompt']);
    }

    public function test_a_question_without_an_answer_is_flagged(): void
    {
        $paper = $this->parser->parse("1. What is 2 + 2?\nA) 3\nB) 4");

        $this->assertSame('multiple_choice', $paper['questions'][0]['type']);
        $this->assertNull($paper['questions'][0]['answer']);
        $this->assertStringContainsString('no clear "Answer:" line', $paper['warnings'][0]);
    }

    public function test_a_document_without_numbered_questions_reports_a_warning(): void
    {
        $paper = $this->parser->parse('Just some prose with no questions in it.');

        $this->assertSame([], $paper['questions']);
        $this->assertStringContainsString('No questions were found', $paper['warnings'][0]);
    }

    public function test_the_generated_template_round_trips_through_the_parser(): void
    {
        $path = $this->parser->buildTemplate();

        try {
            $this->assertFileExists($path);

            $paper = $this->parser->parse(
                $this->parser->extractText($path),
                'Fallback title',
            );
        } finally {
            @unlink($path);
        }

        // "Question paper template" is the first line, then the real title.
        $this->assertSame('Example Chapter Quiz', $paper['title']);
        $this->assertCount(3, $paper['questions']);

        $this->assertSame('multiple_choice', $paper['questions'][0]['type']);
        $this->assertSame('B', $paper['questions'][0]['answer']);
        $this->assertSame(2.0, $paper['questions'][0]['points']);

        $this->assertSame('true_false', $paper['questions'][1]['type']);
        $this->assertSame('short_answer', $paper['questions'][2]['type']);
        $this->assertSame('Photosynthesis', $paper['questions'][2]['answer']);
    }

    public function test_it_rejects_a_file_that_is_not_a_docx_archive(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'not-a-docx');
        file_put_contents($path, 'plain text, not a zip archive');

        try {
            $this->expectException(RuntimeException::class);
            $this->parser->extractText($path);
        } finally {
            @unlink($path);
        }
    }
}
