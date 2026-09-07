<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Inertia\Response;
use App\Models\ReportCard;
use App\Models\Student;
use App\Models\AcademicYear;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Http\RedirectResponse;

class ReportCardController extends Controller
{
    public function index(): Response
    {
        $reportCards = ReportCard::with(['student', 'academicYear'])->latest()->paginate(15);
        return inertia('report-cards/index', ['reportCards' => $reportCards]);
    }

    public function create(): Response
    {
        $students = Student::orderBy('first_name')->get();
        $academicYears = AcademicYear::orderBy('name', 'desc')->get();
        return inertia('report-cards/create', ['students' => $students, 'academicYears' => $academicYears]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'grade' => 'required|string|max:10',
            'gpa' => 'required|numeric|min:0|max:4',
            'status' => 'required|in:draft,published,archived',
            'remarks' => 'nullable|string',
        ]);

        $reportCard = ReportCard::create($validated);

        return redirect()->route('report-cards.show', $reportCard)->with('success', 'Report card created successfully.');
    }

    public function show(ReportCard $reportCard): Response
    {
        $reportCard->load('student', 'academicYear');
        return inertia('report-cards/show', ['reportCard' => $reportCard]);
    }

    public function edit(ReportCard $reportCard): Response
    {
        $students = Student::orderBy('first_name')->get();
        $academicYears = AcademicYear::orderBy('name', 'desc')->get();
        return inertia('report-cards/edit', ['reportCard' => $reportCard, 'students' => $students, 'academicYears' => $academicYears]);
    }

    public function update(Request $request, ReportCard $reportCard): RedirectResponse
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'grade' => 'required|string|max:10',
            'gpa' => 'required|numeric|min:0|max:4',
            'status' => 'required|in:draft,published,archived',
            'remarks' => 'nullable|string',
        ]);

        $reportCard->update($validated);

        return redirect()->route('report-cards.show', $reportCard)->with('success', 'Report card updated successfully.');
    }

    public function destroy(ReportCard $reportCard): RedirectResponse
    {
        $reportCard->delete();

        return redirect()->route('report-cards.index')->with('success', 'Report card deleted successfully.');
    }
}
