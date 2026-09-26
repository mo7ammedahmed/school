<?php

declare(strict_types=1);

namespace Tests\Feature\Finance;

use App\Domain\Finance\Models\Invoice;
use App\Domain\Finance\Services\GatewaySettings;
use App\Domain\Identity\Models\UserMembership;
use App\Domain\People\Models\Guardian;
use App\Domain\People\Models\GuardianRelationship;
use App\Domain\People\Models\Student;
use App\Domain\Schools\Models\School;
use App\Mail\InvoiceMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class InvoiceDeliveryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    public function test_issuing_an_invoice_emails_the_guardian_and_marks_it_sent(): void
    {
        $school = School::factory()->create();
        $this->actingAsSchoolUser($school);

        [$invoice, $guardian] = $this->invoiceWithFinancialGuardian($school);

        $this->post("/finance/invoices/{$invoice->id}/issue")->assertRedirect();

        $invoice->refresh();

        $this->assertSame('issued', $invoice->status);
        $this->assertNotNull($invoice->sent_at, 'the delivery timestamp should be stamped');
        $this->assertTrue((bool) $invoice->delivery_channels['email']);
        $this->assertTrue((bool) $invoice->delivery_channels['inapp']);

        Mail::assertSent(InvoiceMail::class, fn(InvoiceMail $mail) => $mail->invoice->id === $invoice->id && $mail->kind === InvoiceMail::KIND_ISSUED);

        Mail::assertSent(InvoiceMail::class, fn (InvoiceMail $mail) => $mail->hasTo($guardian->email));
    }

    public function test_issuing_writes_an_in_app_notification_for_the_guardian_account(): void
    {
        $school = School::factory()->create();
        $this->actingAsSchoolUser($school);

        [$invoice, $guardian] = $this->invoiceWithFinancialGuardian($school);

        $this->post("/finance/invoices/{$invoice->id}/issue")->assertRedirect();

        $this->assertDatabaseHas('notifications', [
            'school_id' => $school->id,
            'user_id' => $guardian->user_id,
            'type' => 'invoice_issued',
        ]);
    }

    public function test_delivery_is_skipped_when_automatic_sending_is_disabled(): void
    {
        $school = School::factory()->create();
        $this->actingAsSchoolUser($school);

        GatewaySettings::for($school->id)->save(['auto_send' => false]);

        [$invoice] = $this->invoiceWithFinancialGuardian($school);

        $this->post("/finance/invoices/{$invoice->id}/issue")->assertRedirect();

        $invoice->refresh();

        $this->assertSame('issued', $invoice->status);
        $this->assertNull($invoice->sent_at);
        Mail::assertNothingSent();
    }

    public function test_an_invoice_with_no_guardian_contact_is_not_marked_as_sent(): void
    {
        $school = School::factory()->create();
        $this->actingAsSchoolUser($school);

        $student = Student::factory()->create(['school_id' => $school->id, 'email' => null]);
        $invoice = $this->invoiceFor($school, $student);

        $this->post("/finance/invoices/{$invoice->id}/issue")->assertRedirect();

        $invoice->refresh();

        $this->assertNull($invoice->sent_at, 'nothing was delivered, so it is not "sent"');
        $this->assertFalse((bool) $invoice->delivery_channels['email']);
        Mail::assertNothingSent();
    }

    public function test_resending_a_delivered_invoice_sends_a_reminder_instead(): void
    {
        $school = School::factory()->create();
        $this->actingAsSchoolUser($school);

        [$invoice] = $this->invoiceWithFinancialGuardian($school);

        $this->post("/finance/invoices/{$invoice->id}/issue");
        $this->post("/finance/invoices/{$invoice->id}/send")->assertRedirect();

        $invoice->refresh();

        $this->assertNotNull($invoice->reminder_sent_at);
        Mail::assertSent(InvoiceMail::class, fn (InvoiceMail $mail) => $mail->kind === InvoiceMail::KIND_REMINDER);
    }

    public function test_a_guardian_without_contact_details_falls_back_to_the_student(): void
    {
        $school = School::factory()->create();
        $this->actingAsSchoolUser($school);

        $student = Student::factory()->create([
            'school_id' => $school->id,
            'email' => 'student@example.test',
        ]);

        Guardian::create([
            'school_id' => $school->id,
            'first_name' => 'No',
            'last_name' => 'Contact',
        ]);

        $invoice = $this->invoiceFor($school, $student);

        $this->post("/finance/invoices/{$invoice->id}/issue")->assertRedirect();

        Mail::assertSent(InvoiceMail::class, fn (InvoiceMail $mail) => $mail->hasTo('student@example.test'));
    }

    public function test_the_financial_guardian_is_preferred_over_other_guardians(): void
    {
        $school = School::factory()->create();
        $this->actingAsSchoolUser($school);

        $student = Student::factory()->create(['school_id' => $school->id, 'email' => null]);

        $billing = Guardian::create([
            'school_id' => $school->id,
            'first_name' => 'Billing',
            'last_name' => 'Parent',
            'email' => 'billing@example.test',
        ]);

        $other = Guardian::create([
            'school_id' => $school->id,
            'first_name' => 'Other',
            'last_name' => 'Parent',
            'email' => 'other@example.test',
        ]);

        GuardianRelationship::create([
            'school_id' => $school->id,
            'guardian_id' => $other->id,
            'student_id' => $student->id,
            'is_primary' => true,
            'is_financial_guardian' => false,
        ]);

        GuardianRelationship::create([
            'school_id' => $school->id,
            'guardian_id' => $billing->id,
            'student_id' => $student->id,
            'is_primary' => false,
            'is_financial_guardian' => true,
        ]);

        $invoice = $this->invoiceFor($school, $student);

        $this->post("/finance/invoices/{$invoice->id}/issue")->assertRedirect();

        Mail::assertSent(InvoiceMail::class, fn (InvoiceMail $mail) => $mail->hasTo('billing@example.test'));
        Mail::assertNotSent(InvoiceMail::class, fn (InvoiceMail $mail) => $mail->hasTo('other@example.test'));
    }

    /**
     * @return array{0: Invoice, 1: Guardian}
     */
    private function invoiceWithFinancialGuardian(School $school): array
    {
        $student = Student::factory()->create(['school_id' => $school->id, 'email' => null]);

        $guardianUser = User::factory()->create();
        $guardian = Guardian::create([
            'school_id' => $school->id,
            'user_id' => $guardianUser->id,
            'first_name' => 'Amina',
            'last_name' => 'Hassan',
            'relationship' => 'mother',
            'email' => 'amina@example.test',
        ]);

        GuardianRelationship::create([
            'school_id' => $school->id,
            'guardian_id' => $guardian->id,
            'student_id' => $student->id,
            'is_primary' => true,
            'is_financial_guardian' => true,
        ]);

        return [$this->invoiceFor($school, $student), $guardian];
    }

    private function invoiceFor(School $school, Student $student): Invoice
    {
        return Invoice::factory()->create([
            'school_id' => $school->id,
            'student_id' => $student->id,
            'status' => 'draft',
            'subtotal' => 1000,
            'total_amount' => 1150,
            'balance_due' => 1150,
            'amount_paid' => 0,
        ]);
    }

    private function actingAsSchoolUser(School $school): User
    {
        $user = User::factory()->create();

        UserMembership::factory()->create([
            'user_id' => $user->id,
            'school_id' => $school->id,
            'is_active' => true,
        ]);

        $this->actingAs($user);
        $this->app['session']->put('school_id', $school->id);

        return $user;
    }
}
