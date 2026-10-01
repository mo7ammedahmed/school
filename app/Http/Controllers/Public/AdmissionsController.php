<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Domain\Academics\Models\GradeLevel;
use App\Domain\Admissions\Models\AdmissionApplication;
use App\Domain\Admissions\Models\AdmissionPeriod;
use App\Validation\AllowedAttachment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The guided public application journey.
 *
 * Every step writes into one session key, and the final step turns that payload
 * into an `AdmissionApplication`. Until now there was no final step: the review
 * screen rendered a hard-coded applicant, and its "Submit Application" button was
 * a plain link to the thank-you page, so nothing a family typed here ever
 * reached the database.
 *
 * The collected answers are namespaced per step (`guardian`, `student`,
 * `previous_school`). They used to be merged flat into one array, which meant the
 * student step silently overwrote the guardian's `first_name`, `last_name` and
 * `address` — the two steps ask for the same keys.
 */
class AdmissionsController extends PublicController
{
    public function apply(): Response
    {
        return Inertia::render('apply');
    }

    public function start(): Response
    {
        return Inertia::render('apply/start');
    }

    public function storeStart(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'academic_year_id' => ['required', 'integer'],
        ]);

        return $this->saveStep($request, 'start', $data, 'apply.guardian');
    }

    public function guardian(): Response
    {
        return Inertia::render('apply/guardian');
    }

    public function storeGuardian(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'relationship' => ['required', 'string', 'max:50'],
            'occupation' => ['nullable', 'string', 'max:150'],
            'address' => ['required', 'string', 'max:500'],
        ]);

        return $this->saveStep($request, 'guardian', $data, 'apply.student');
    }

    public function student(): Response
    {
        return Inertia::render('apply/student');
    }

    public function storeStudent(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'date_of_birth' => ['required', 'date'],
            'gender' => ['required', 'string', 'max:30'],
            'nationality' => ['required', 'string', 'max:100'],
            'address' => ['required', 'string', 'max:500'],
            'previous_school' => ['nullable', 'string', 'max:255'],
        ]);

        return $this->saveStep($request, 'student', $data, 'apply.previous-school');
    }

    public function previousSchool(): Response
    {
        return Inertia::render('apply/previous-school');
    }

    public function storePreviousSchool(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'school_name' => ['required', 'string', 'max:255'],
            'school_address' => ['required', 'string', 'max:500'],
            'last_grade_completed' => ['required', 'string', 'max:50'],
            'reason_for_leaving' => ['required', 'string', 'max:500'],
        ]);

        return $this->saveStep($request, 'previous_school', $data, 'apply.documents');
    }

    public function documents(): Response
    {
        return Inertia::render('apply/documents');
    }

    public function storeDocuments(Request $request): RedirectResponse
    {
        $request->validate([
            'birth_certificate' => ['required', AllowedAttachment::rule()],
            'previous_school_records' => ['required', AllowedAttachment::rule()],
            'passport_photos' => ['required', AllowedAttachment::rule()],
            'medical_records' => ['required', AllowedAttachment::rule()],
        ]);

        // The uploads used to be read for their filename and thrown away — the
        // step recorded a name that pointed at no file. They are stored now, on
        // the private disk, under the school that will review them.
        $schoolId = $this->schoolId();
        $documents = [];

        foreach ($request->file() as $type => $file) {
            $documents[] = [
                'type' => $type,
                'name' => $file->getClientOriginalName(),
                'path' => $file->store("admission-documents/{$schoolId}", 'local'),
            ];
        }

        return $this->saveStep($request, 'documents', ['files' => $documents], 'apply.review');
    }

    public function review(Request $request): Response
    {
        // The visitor sees their own answers, not a sample applicant.
        return Inertia::render('apply/review', [
            'collected' => $this->collected($request),
        ]);
    }

    public function submit(Request $request): RedirectResponse
    {
        $session = $request->session()->get(self::SESSION_KEY, []);
        $guardian = is_array($session['guardian'] ?? null) ? $session['guardian'] : [];
        $student = is_array($session['student'] ?? null) ? $session['student'] : [];
        $previous = is_array($session['previous_school'] ?? null) ? $session['previous_school'] : [];

        if ($guardian === [] || $student === []) {
            return redirect()->route('apply.start')
                ->with('error', 'Please complete the earlier steps before submitting your application.');
        }

        $schoolId = $this->schoolId();

        $application = DB::transaction(function () use ($guardian, $student, $previous, $session, $schoolId): AdmissionApplication {
            $application = AdmissionApplication::create([
                'school_id' => $schoolId,
                // A family starting from the school's admissions page belongs to
                // the intake that page is advertising; the wizard never asks.
                'admission_period_id' => $this->openPeriodId($schoolId),
                'reference' => $this->reference(),
                'status' => 'submitted',
                'guardian_first_name' => $guardian['first_name'] ?? '',
                'guardian_last_name' => $guardian['last_name'] ?? '',
                'guardian_email' => $guardian['email'] ?? '',
                'guardian_phone' => $guardian['phone'] ?? null,
                'guardian_relationship' => $guardian['relationship'] ?? null,
                'guardian_occupation' => $guardian['occupation'] ?? null,
                'guardian_address' => $guardian['address'] ?? null,
                'student_first_name' => $student['first_name'] ?? '',
                'student_last_name' => $student['last_name'] ?? '',
                'student_date_of_birth' => $student['date_of_birth'] ?? null,
                'student_gender' => $student['gender'] ?? null,
                'student_nationality' => $student['nationality'] ?? null,
                'student_address' => $student['address'] ?? null,
                'previous_school_name' => $previous['school_name'] ?? null,
                'previous_school_address' => $previous['school_address'] ?? null,
                'previous_school_last_grade' => $previous['last_grade_completed'] ?? null,
                'reason_for_leaving' => $previous['reason_for_leaving'] ?? null,
                'documents' => $session['documents']['files'] ?? [],
                'submitted_at' => now(),
            ]);

            $application->events()->create([
                'event_type' => 'submitted',
                'notes' => 'Application submitted from the public website',
            ]);

            return $application;
        });

        $request->session()->forget(self::SESSION_KEY);

        return redirect()->route('apply.submitted')
            ->with('application_reference', $application->reference);
    }

    public function submitted(Request $request): Response
    {
        return Inertia::render('apply/submitted', [
            'reference' => $request->session()->get('application_reference'),
        ]);
    }

    public function index(): Response
    {
        // `name` is an appended accessor on both models below, not a column: a
        // select list asking for it filled every row with the literal "name".
        // A period covers no particular grades — there is no column for it, and
        // no admin field to set one — so the page no longer claims otherwise.
        $schoolId = $this->schoolId();

        $periods = AdmissionPeriod::forSchool($schoolId)
            ->where('is_active', true)
            ->orderBy('start_date')
            ->get(['id', 'name', 'description', 'start_date', 'end_date']);

        $gradeLevels = GradeLevel::forSchool($schoolId)
            ->orderBy('level')
            ->get(['id', 'name_en', 'name_ar', 'level']);

        return Inertia::render('public/admissions', [
            'periods' => $periods,
            'gradeLevels' => $gradeLevels,
        ]);
    }

    /**
     * One step's answers, stored under the step's own name so that two steps
     * asking for `address` cannot overwrite each other.
     *
     * @param  array<string, mixed>  $data
     */
    private function saveStep(Request $request, string $step, array $data, string $nextRoute): RedirectResponse
    {
        $session = $request->session()->get(self::SESSION_KEY, []);
        $session = is_array($session) ? $session : [];
        $session[$step] = $data;

        $request->session()->put(self::SESSION_KEY, $session);

        return redirect()->route($nextRoute);
    }

    /**
     * @return array<string, mixed>
     */
    private function collected(Request $request): array
    {
        $session = $request->session()->get(self::SESSION_KEY, []);

        return is_array($session) ? $session : [];
    }

    private function openPeriodId(int $schoolId): ?int
    {
        $periodId = AdmissionPeriod::forSchool($schoolId)
            ->where('is_active', true)
            ->orderBy('start_date')
            ->value('id');

        return is_numeric($periodId) ? (int) $periodId : null;
    }

    /**
     * A reference the family can quote. Random rather than sequential so two
     * schools cannot infer each other's application volumes from the numbers.
     */
    private function reference(): string
    {
        return 'APP-'.now()->format('Ymd').'-'.strtoupper(Str::random(4));
    }

    private const SESSION_KEY = 'public_application';
}
