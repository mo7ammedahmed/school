<?php

declare(strict_types=1);

namespace Tests\Feature\Tenant;

use App\Domain\Academics\Models\AcademicYear;
use App\Domain\Academics\Models\GradeLevel;
use App\Domain\Academics\Models\Section;
use App\Domain\Finance\Models\FeeType;
use App\Domain\Localization\Services\TranslationSettings;
use App\Domain\People\Models\Student;
use App\Domain\Schools\Models\School;
use App\Models\Discount;
use App\Models\Enrollment;
use App\Models\FeeStructure;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\ReportCard;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Every tenant table keys off `school_id`, and several stores forgot to set it —
 * which is invisible until somebody opens the page, because the write either
 * throws on a NOT NULL column or files the record under school zero.
 *
 * These tests drive the real routes rather than the models, so a store that
 * drops `school_id` (or that validates a column the table does not have) fails
 * here instead of in production.
 */
class TenantWritesTest extends TestCase
{
    use RefreshDatabase;

    public function test_enrolling_a_student_files_the_enrolment_under_the_active_school(): void
    {
        $school = School::factory()->create();
        $this->actingAsSchoolUser($school, ['manage-enrollments']);

        $student = Student::factory()->create(['school_id' => $school->id]);
        [$year, $gradeLevel] = $this->yearAndGradeLevel($school);
        $section = Section::factory()->create([
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'grade_level_id' => $gradeLevel->id,
        ]);

        $this->post('/enrollments', [
            'student_id' => $student->id,
            'section_id' => $section->id,
            'academic_year_id' => $year->id,
            'enrollment_date' => '2026-09-01',
            'status' => 'active',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertDatabaseHas('enrollments', [
            'student_id' => $student->id,
            'school_id' => $school->id,
        ]);

        $this->assertSame($school->id, Enrollment::firstOrFail()->school_id);
    }

    public function test_a_report_card_is_filed_under_the_active_school(): void
    {
        $school = School::factory()->create();
        $this->actingAsSchoolUser($school, ['manage-report-cards']);

        $student = Student::factory()->create(['school_id' => $school->id]);
        [$year] = $this->yearAndGradeLevel($school);

        // The form used to post `grade`, `status` and `remarks` — three columns
        // the table does not have — so the comment was silently dropped.
        $this->post('/report-cards', [
            'student_id' => $student->id,
            'academic_year_id' => $year->id,
            'gpa' => 3.75,
            'comments' => 'Good term',
            'comments_ar' => 'فصل دراسي جيد',
            'is_published' => 0,
        ])->assertRedirect()->assertSessionHasNoErrors();

        $reportCard = ReportCard::firstOrFail();

        $this->assertSame($school->id, $reportCard->school_id);
        $this->assertSame('Good term', $reportCard->comments);
        $this->assertSame('فصل دراسي جيد', $reportCard->comments_ar);
        $this->assertNull($reportCard->published_at, 'a draft card should not be stamped as published');
    }

    public function test_publishing_a_report_card_stamps_the_publication_time(): void
    {
        $school = School::factory()->create();
        $this->actingAsSchoolUser($school, ['manage-report-cards']);

        $student = Student::factory()->create(['school_id' => $school->id]);
        [$year] = $this->yearAndGradeLevel($school);

        $this->post('/report-cards', [
            'student_id' => $student->id,
            'academic_year_id' => $year->id,
            'gpa' => 3.2,
            'comments' => 'Ready to share',
            'is_published' => 1,
        ])->assertRedirect()->assertSessionHasNoErrors();

        $reportCard = ReportCard::firstOrFail();

        $this->assertNotNull($reportCard->published_at);
        $this->assertNotNull($reportCard->published_by);
    }

    public function test_a_fee_structure_is_filed_under_the_active_school_and_keeps_its_description(): void
    {
        $school = School::factory()->create();
        $this->actingAsSchoolUser($school, ['manage-fee-structures']);

        $feeType = $this->feeType($school);
        [$year, $gradeLevel] = $this->yearAndGradeLevel($school);

        $this->post('/finance/fee-structures', [
            'fee_type_id' => $feeType->id,
            'grade_level_id' => $gradeLevel->id,
            'amount' => 1500,
            'description' => 'Annual tuition',
            'description_ar' => 'الرسوم السنوية',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $structure = FeeStructure::firstOrFail();

        $this->assertSame($school->id, $structure->school_id);
        $this->assertSame('Annual tuition', $structure->description);
        $this->assertSame('الرسوم السنوية', $structure->description_ar);
        $this->assertSame($gradeLevel->id, $structure->grade_level_id);
    }

    public function test_the_fee_structure_list_shows_only_the_active_schools_rows(): void
    {
        $school = School::factory()->create();
        $other = School::factory()->create();

        $mine = FeeStructure::create([
            'school_id' => $school->id,
            'fee_type_id' => $this->feeType($school)->id,
            'amount' => 100,
            'description' => 'Mine',
        ]);

        FeeStructure::create([
            'school_id' => $other->id,
            'fee_type_id' => $this->feeType($other)->id,
            'amount' => 200,
            'description' => 'Theirs',
        ]);

        $this->actingAsSchoolUser($school, ['manage-fee-structures']);

        $response = $this->get('/finance/fee-structures');

        $response->assertOk();

        $rows = $response->viewData('page')['props']['feeStructures']['data'];

        $this->assertSame([$mine->id], array_column($rows, 'id'), 'another school\'s fee structures leaked into the list');
    }

    public function test_opening_another_schools_fee_structure_is_refused(): void
    {
        $school = School::factory()->create();
        $other = School::factory()->create();

        $foreign = FeeStructure::create([
            'school_id' => $other->id,
            'fee_type_id' => $this->feeType($other)->id,
            'amount' => 200,
        ]);

        $this->actingAsSchoolUser($school, ['manage-fee-structures']);

        $this->get("/finance/fee-structures/{$foreign->id}")->assertForbidden();
        $this->get("/finance/fee-structures/{$foreign->id}/edit")->assertForbidden();
    }

    public function test_a_discount_keeps_its_inactive_flag_and_its_school(): void
    {
        $school = School::factory()->create();
        $this->actingAsSchoolUser($school, ['manage-discounts']);

        // `is_active=0` is what the old `$request->has('is_active')` check got
        // wrong: it read the field as present rather than as false, so every
        // discount was stored active however the operator set the switch.
        $this->post('/finance/discounts', [
            'name' => 'Sibling discount',
            'type' => 'percentage',
            'value' => 10,
            'start_date' => '2026-09-01',
            'end_date' => '2027-06-30',
            'is_active' => 0,
        ])->assertRedirect()->assertSessionHasNoErrors();

        $discount = Discount::firstOrFail();

        $this->assertFalse((bool) $discount->is_active);
        $this->assertSame($school->id, $discount->school_id);
    }

    public function test_a_refund_is_filed_under_the_active_school(): void
    {
        $school = School::factory()->create();
        $this->actingAsSchoolUser($school, ['manage-refunds']);

        $student = Student::factory()->create(['school_id' => $school->id]);
        $invoice = Invoice::factory()->create([
            'school_id' => $school->id,
            'student_id' => $student->id,
        ]);

        // `refunds.payment_id` and `refunds.currency` are not nullable, and the
        // form sends neither: they are resolved from the invoice being refunded.
        $payment = Payment::create([
            'school_id' => $school->id,
            'student_id' => $student->id,
            'invoice_id' => $invoice->id,
            'payment_number' => 'PAY-REFUND-1',
            'payment_date' => now()->toDateString(),
            'amount' => 250,
            'currency' => 'SAR',
            'payment_method' => 'cash',
            'status' => 'paid',
        ]);

        $this->post('/finance/refunds', [
            'invoice_id' => $invoice->id,
            'amount' => 250,
            'reason' => 'Overpaid',
            'status' => 'pending',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $refund = Refund::firstOrFail();

        $this->assertSame($school->id, $refund->school_id);
        $this->assertSame($invoice->id, $refund->invoice_id);
        $this->assertSame($payment->id, $refund->payment_id);
        $this->assertSame($invoice->currency, $refund->currency);
    }

    public function test_refunding_an_invoice_with_no_payment_is_a_field_error_not_a_crash(): void
    {
        $school = School::factory()->create();
        $this->actingAsSchoolUser($school, ['manage-refunds']);

        $student = Student::factory()->create(['school_id' => $school->id]);
        $invoice = Invoice::factory()->create([
            'school_id' => $school->id,
            'student_id' => $student->id,
        ]);

        $this->post('/finance/refunds', [
            'invoice_id' => $invoice->id,
            'amount' => 250,
            'reason' => 'Nothing was ever paid',
            'status' => 'pending',
        ])->assertSessionHasErrors('invoice_id');

        $this->assertDatabaseCount('refunds', 0);
    }

    public function test_a_request_without_a_school_context_is_refused_rather_than_writing_under_zero(): void
    {
        // No session school id and no membership: the shared helper must refuse
        // the request instead of quietly filing rows under `school_id = 0`.
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->post('/finance/discounts', [
            'name' => 'Nowhere discount',
            'type' => 'fixed',
            'value' => 5,
            'start_date' => '2026-09-01',
            'end_date' => '2027-06-30',
            'is_active' => 1,
        ])->assertRedirect();

        $this->assertDatabaseMissing('discounts', ['name' => 'Nowhere discount']);
    }

    public function test_a_bilingual_fee_structure_fills_the_missing_language_on_save(): void
    {
        $school = School::factory()->create();
        $this->actingAsSchoolUser($school, ['manage-fee-structures']);
        $this->enableTranslation($school, 'Tuition fee paid yearly');

        $feeType = $this->feeType($school);

        // Arabic only: the English side is the one the table used to insist on.
        $this->post('/finance/fee-structures', [
            'fee_type_id' => $feeType->id,
            'amount' => 300,
            'description_ar' => 'رسوم دراسية',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame('Tuition fee paid yearly', FeeStructure::firstOrFail()->description);
    }

    public function test_the_rewritten_screens_render(): void
    {
        $school = School::factory()->create();
        $this->actingAsSchoolUser($school, ['manage-fee-structures', 'manage-fee-types', 'manage-report-cards', 'manage-refunds', 'manage-announcements']);

        $feeType = $this->feeType($school);
        [$year, $gradeLevel] = $this->yearAndGradeLevel($school);

        $student = Student::factory()->create(['school_id' => $school->id]);
        $invoice = Invoice::factory()->create(['school_id' => $school->id, 'student_id' => $student->id]);

        $structure = FeeStructure::create([
            'school_id' => $school->id,
            'fee_type_id' => $feeType->id,
            'grade_level_id' => null,
            'amount' => 100,
        ]);

        $card = ReportCard::create([
            'school_id' => $school->id,
            'student_id' => $student->id,
            'academic_year_id' => $year->id,
            'gpa' => 3.5,
        ]);

        $refund = Refund::create([
            'school_id' => $school->id,
            'invoice_id' => $invoice->id,
            'payment_id' => Payment::create([
                'school_id' => $school->id,
                'student_id' => $student->id,
                'invoice_id' => $invoice->id,
                'payment_number' => 'PAY-RENDER-1',
                'payment_date' => now()->toDateString(),
                'amount' => 100,
                'currency' => 'SAR',
                'status' => 'paid',
            ])->id,
            'amount' => 10,
            'currency' => 'SAR',
            'reason' => 'Overpaid',
            'status' => 'pending',
        ]);

        // A fee structure with no grade level is the case that used to take the
        // whole list down with a 500.
        foreach ([
            '/finance/fee-structures',
            '/finance/fee-structures/create',
            // The sidebar links here; the route is served by the same controller.
            '/finance/fee-types',
            "/finance/fee-structures/{$structure->id}",
            "/finance/fee-structures/{$structure->id}/edit",
            '/report-cards',
            '/report-cards/create',
            "/report-cards/{$card->id}",
            "/report-cards/{$card->id}/edit",
            '/finance/refunds',
            '/finance/refunds/create',
            "/finance/refunds/{$refund->id}",
            "/finance/refunds/{$refund->id}/edit",
            '/announcements',
            '/announcements/create',
        ] as $url) {
            $this->get($url)->assertOk();
        }

        // And the id is what the grade level was checked against, so make sure
        // the fixture really is the un-scoped one.
        $this->assertNull($structure->fresh()->grade_level_id);
        $this->assertSame($gradeLevel->school_id, $school->id);
    }

    public function test_the_fee_structure_form_rejects_another_schools_fee_type(): void
    {
        $school = School::factory()->create();
        $other = School::factory()->create();
        $this->actingAsSchoolUser($school, ['manage-fee-structures']);

        $this->post('/finance/fee-structures', [
            'fee_type_id' => $this->feeType($other)->id,
            'amount' => 100,
            'description' => 'Someone else\'s fee type',
        ])->assertSessionHasErrors('fee_type_id');

        $this->assertDatabaseCount('fee_structures', 0);
    }

    /**
     * @return array{0: AcademicYear, 1: GradeLevel}
     */
    private function yearAndGradeLevel(School $school): array
    {
        return [
            AcademicYear::factory()->create(['school_id' => $school->id]),
            GradeLevel::factory()->create(['school_id' => $school->id]),
        ];
    }

    private function feeType(School $school): FeeType
    {
        return FeeType::create([
            'school_id' => $school->id,
            'name' => 'Tuition',
            'name_ar' => 'الرسوم',
            'code' => 'TUI',
        ]);
    }

    /**
     * Points the school at a stub provider that echoes a fixed translation, so
     * the fill-on-save path is exercised without touching the network.
     */
    private function enableTranslation(School $school, string $english): void
    {
        config(['services.openai.api_key' => 'sk-test']);

        TranslationSettings::for($school->id)->save(['provider' => 'openai', 'model' => 'gpt-4o-mini']);

        Http::fake([
            'api.openai.com/*' => Http::response(['choices' => [['message' => ['content' => $english]]]]),
        ]);
    }
}
