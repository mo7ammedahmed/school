<?php

declare(strict_types=1);

namespace Tests\Feature\Content;

use App\Domain\Schools\Models\School;
use App\Mail\ContactMessageMail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use RuntimeException;
use Tests\TestCase;

/**
 * The contact form used to validate four fields, drop all four on the floor and
 * tell the visitor their message was sent.
 */
class ContactFormTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_message_from_the_website_reaches_the_schools_inbox(): void
    {
        Mail::fake();

        $school = School::factory()->create(['slug' => 'home-school', 'email' => 'office@home.test']);

        $this->post('/contact', $this->validMessage([
            'email' => 'parent@example.test',
        ]))->assertRedirect();

        Mail::assertSent(ContactMessageMail::class, function (ContactMessageMail $mail) use ($school): bool {
            return $mail->hasTo($school->email)
                && $mail->hasReplyTo('parent@example.test')
                && $mail->subjectLine === 'A question about admissions';
        });
    }

    public function test_a_message_is_rejected_when_a_field_is_missing(): void
    {
        Mail::fake();

        School::factory()->create(['slug' => 'home-school', 'email' => 'office@home.test']);

        $this->post('/contact', ['name' => 'A parent'])->assertSessionHasErrors([
            'email',
            'subject',
            'message',
        ]);

        Mail::assertNothingSent();
    }

    public function test_the_visitor_is_not_told_it_was_sent_when_the_mail_server_fails(): void
    {
        School::factory()->create(['slug' => 'home-school', 'email' => 'office@home.test']);

        Mail::shouldReceive('to')->once()->andThrow(new RuntimeException('smtp is down'));

        $response = $this->post('/contact', $this->validMessage());

        $response->assertSessionHas('error')->assertSessionMissing('success');
    }

    /**
     * @param  array<string, string>  $overrides
     * @return array<string, string>
     */
    private function validMessage(array $overrides = []): array
    {
        return array_merge([
            'name' => 'A parent',
            'email' => 'parent@example.test',
            'subject' => 'A question about admissions',
            'message' => 'When do applications close?',
        ], $overrides);
    }
}
