<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Domain\Schools\Services\SchoolResolver;
use App\Models\GradeLevel;
use App\Models\Subject;
use Inertia\Inertia;
use Inertia\Response;

class ProgramsController
{
    public function __construct(private readonly SchoolResolver $schools) {}

    public function index(): Response
    {
        $schoolId = $this->schools->current()?->id;

        // `subjects` and `grade_levels` store their names in two columns and
        // expose `name` as an appended accessor. Asking the database for `name`
        // returned the literal word "name" in every row, so the public programs
        // page printed it as each subject's title.
        $programs = Subject::where('school_id', $schoolId)
            ->with('gradeLevel')
            ->orderBy('name_en')
            ->get(['id', 'name_en', 'name_ar', 'description', 'grade_level_id']);

        $gradeLevels = GradeLevel::where('school_id', $schoolId)
            ->orderBy('level')
            ->get(['id', 'name_en', 'name_ar', 'level']);

        return Inertia::render('public/programs', [
            'programs' => $programs,
            'gradeLevels' => $gradeLevels,
        ]);
    }

    public function show(int $id): Response
    {
        $program = Subject::where('school_id', $this->schools->current()?->id)
            ->with('gradeLevel')
            ->findOrFail($id);

        return Inertia::render('public/programs/show', [
            'program' => $program,
        ]);
    }
}
