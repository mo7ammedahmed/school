<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Domain\Academics\Models\GradeLevel;
use App\Domain\Admissions\Models\AdmissionPeriod;
use App\Domain\Schools\Services\SchoolResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdmissionsController
{
    public function __construct(private readonly SchoolResolver $schools) {}

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

        return $this->saveStep($request, $data, 'apply.guardian');
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

        return $this->saveStep($request, $data, 'apply.student');
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

        return $this->saveStep($request, $data, 'apply.previous-school');
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

        return $this->saveStep($request, $data, 'apply.documents');
    }

    public function documents(): Response
    {
        return Inertia::render('apply/documents');
    }

    public function storeDocuments(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'birth_certificate' => ['required', 'file', 'max:10240'],
            'previous_school_records' => ['required', 'file', 'max:10240'],
            'passport_photos' => ['required', 'file', 'max:10240'],
            'medical_records' => ['required', 'file', 'max:10240'],
        ]);

        $data = collect($data)->mapWithKeys(
            fn ($file, $key) => [$key => $file->getClientOriginalName()]
        )->all();

        return $this->saveStep($request, $data, 'apply.review');
    }

    public function review(): Response
    {
        return Inertia::render('apply/review');
    }

    public function submitted(): Response
    {
        return Inertia::render('apply/submitted');
    }

    private function saveStep(Request $request, array $data, string $nextRoute): RedirectResponse
    {
        $application = $request->session()->get('public_application', []);
        $request->session()->put('public_application', array_merge($application, $data));

        return redirect()->route($nextRoute);
    }

    public function index(): Response
    {
        // `name` is an appended accessor on both models below, not a column: a
        // select list asking for it filled every row with the literal "name".
        // A period covers no particular grades — there is no column for it, and
        // no admin field to set one — so the page no longer claims otherwise.
        // A guest has no school in the session, so the site's own school comes
        // from the resolver; `session('school_id')` was null and matched nothing.
        $schoolId = $this->schools->current()?->id;

        $periods = AdmissionPeriod::where('is_active', true)
            ->where('school_id', $schoolId)
            ->orderBy('start_date')
            ->get(['id', 'name', 'description', 'start_date', 'end_date']);

        $gradeLevels = GradeLevel::where('school_id', $schoolId)
            ->orderBy('level')
            ->get(['id', 'name_en', 'name_ar', 'level']);

        return Inertia::render('public/admissions', [
            'periods' => $periods,
            'gradeLevels' => $gradeLevels,
        ]);
    }
}
