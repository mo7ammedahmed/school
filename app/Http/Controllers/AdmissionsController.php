<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use RuntimeException;
use App\Domain\Academics\Models\AcademicYear;
use App\Domain\People\Models\GuardianRelationship;
use App\Domain\Academics\Models\GradeLevel;
use App\Domain\Academics\Models\Section;
use App\Domain\Admissions\Models\AdmissionApplication;
use App\Domain\Admissions\Models\AdmissionPeriod;
use App\Domain\People\Models\Guardian;
use App\Domain\People\Models\Student;
use App\Domain\Academics\Models\Enrollment;
use App\Domain\Compliance\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;

class AdmissionsController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:manage-admissions')->except([]);
    }

    public function periods(Request $request)
    {
        $periods = AdmissionPeriod::where('school_id', $request->session()->get('school_id'))
            ->withCount('applications')
            ->latest()
            ->get();

        return Inertia::render('admissions/periods/index', [
            'periods' => $periods,
        ]);
    }

    public function applications(Request $request)
    {
        $schoolId = $request->session()->get('school_id');

        $applications = AdmissionApplication::where('school_id', $schoolId)
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = $request->string('search');
                $q->where(function ($qq) use ($term) {
                    $qq->where('reference', 'like', "%{$term}%")
                        ->orWhere('student_first_name', 'like', "%{$term}%")
                        ->orWhere('student_last_name', 'like', "%{$term}%");
                });
            })
            ->latest('submitted_at')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('admissions/applications/index', [
            'applications' => $applications,
            'filters' => $request->only(['status', 'search']),
        ]);
    }

    public function applicationShow(Request $request, int $id)
    {
        $application = AdmissionApplication::where('school_id', $request->session()->get('school_id'))
            ->with('events')
            ->findOrFail($id);

        return Inertia::render('admissions/applications/show', [
            'application' => $application,
        ]);
    }

    /**
     * Review decision endpoint: approve or reject an application.
     */
    public function applicationDecide(Request $request, int $id)
    {
        $validated = $request->validate([
            'decision' => ['required', 'in:approved,rejected'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $application = AdmissionApplication::where('school_id', $request->session()->get('school_id'))
            ->where('status', 'submitted')
            ->orWhere(function ($q) use ($request, $id) {
                $q->where('school_id', $request->session()->get('school_id'))
                    ->where('id', $id)
                    ->where('status', 'under_review');
            })
            ->findOrFail($id);

        $application->update([
            'status' => $validated['decision'],
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
            'review_notes' => $validated['notes'] ?? null,
        ]);

        $application->events()->create([
            'user_id' => $request->user()->id,
            'event_type' => $validated['decision'],
            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect()->back()->with('success', 'Application decision recorded.');
    }

    /**
     * Convert an approved application into Guardian + Student + Enrollment.
     * Runs in a transaction, prevents duplicate identity creation.
     */
    public function applicationConvert(Request $request, int $id)
    {
        $application = AdmissionApplication::where('school_id', $request->session()->get('school_id'))
            ->where('status', 'approved')
            ->findOrFail($id);

        $schoolId = $application->school_id;

        $result = DB::transaction(function () use ($application, $schoolId, $request) {
            // Guard against double conversion
            if ($application->converted_student_id !== null) {
                throw new RuntimeException('Application already converted.');
            }

            $currentYear = AcademicYear::where('school_id', $schoolId)
                ->where('is_current', true)
                ->first();

            // Find or create guardian by email (school-scoped, prevents duplicate identity)
            $guardian = Guardian::withTrashed()
                ->where('school_id', $schoolId)
                ->where('email', $application->guardian_email)
                ->first();

            if (!$guardian) {
                $guardian = Guardian::create([
                    'school_id' => $schoolId,
                    'first_name' => $application->guardian_first_name,
                    'last_name' => $application->guardian_last_name,
                    'relationship' => $application->guardian_relationship ?? 'Guardian',
                    'email' => $application->guardian_email,
                    'phone' => $application->guardian_phone,
                ]);
            }

            // Create the student
            $student = Student::create([
                'school_id' => $schoolId,
                'first_name' => $application->student_first_name,
                'last_name' => $application->student_last_name,
                'student_id_number' => 'ANS-' . strtoupper(Str::random(6)),
                'date_of_birth' => $application->student_date_of_birth,
                'gender' => $application->student_gender,
                'nationality' => $application->student_nationality,
                'enrollment_date' => now()->format('Y-m-d'),
                'status' => 'active',
            ]);

            // Link guardian relationship
            GuardianRelationship::create([
                'school_id' => $schoolId,
                'guardian_id' => $guardian->id,
                'student_id' => $student->id,
                'is_primary' => true,
                'is_financial_guardian' => true,
            ]);

            // Enroll into the first section of the applied grade for the current year
            $gradeLevel = GradeLevel::where('school_id', $schoolId)
                ->where('name', $application->grade_applying)
                ->first();

            $section = $gradeLevel
                ? Section::where('school_id', $schoolId)
                    ->where('academic_year_id', $currentYear?->id)
                    ->where('grade_level_id', $gradeLevel->id)
                    ->first()
                : null;

            if ($currentYear && $section) {
                Enrollment::create([
                    'school_id' => $schoolId,
                    'student_id' => $student->id,
                    'academic_year_id' => $currentYear->id,
                    'section_id' => $section->id,
                    'enrollment_date' => now()->format('Y-m-d'),
                    'status' => 'active',
                ]);
            }

            $application->update([
                'status' => 'converted',
                'converted_student_id' => $student->id,
            ]);

            $application->events()->create([
                'user_id' => $request->user()->id,
                'event_type' => 'converted',
                'notes' => "Converted to student {$student->student_id_number}",
            ]);

            AuditLog::create([
                'school_id' => $schoolId,
                'user_id' => $request->user()->id,
                'action' => 'admission_converted',
                'entity_type' => AdmissionApplication::class,
                'entity_id' => $application->id,
                'new_values' => json_encode(['student_id' => $student->id]),
            ]);

            return $student;
        });

        return redirect()
            ->route('students.show', $result->id)
            ->with('success', "Application converted. New student: {$result->first_name} {$result->last_name}");
    }
}
