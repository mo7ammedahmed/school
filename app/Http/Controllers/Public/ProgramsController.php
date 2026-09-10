<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Models\GradeLevel;
use App\Models\Subject;
use Inertia\Inertia;
use Inertia\Response;

class ProgramsController
{
    public function index(): Response
    {
        $programs = Subject::with('gradeLevel')
            ->orderBy('name')
            ->get(['id', 'name', 'description', 'grade_level_id']);

        $gradeLevels = GradeLevel::orderBy('level')->get(['id', 'name', 'level']);

        return Inertia::render('public/programs', [
            'programs' => $programs,
            'gradeLevels' => $gradeLevels,
        ]);
    }

    public function show(int $id): Response
    {
        $program = Subject::with('gradeLevel')->findOrFail($id);

        return Inertia::render('public/programs/show', [
            'program' => $program,
        ]);
    }
}
