<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Models\Teacher;
use Inertia\Response;
use Inertia\Inertia;

class TeachersController
{
    public function index(): Response
    {
        $teachers = Teacher::with('subjects')
            ->where('school_id', session('school_id'))
            ->where('status', 'active')
            ->get(['id', 'first_name', 'last_name', 'email', 'phone', 'position', 'bio', 'education']);

        return Inertia::render('public/teachers', [
            'teachers' => $teachers,
        ]);
    }

    public function show(int $id): Response
    {
        $teacher = Teacher::with('subjects')
            ->where('school_id', session('school_id'))
            ->where('status', 'active')
            ->findOrFail($id);

        return Inertia::render('public/teachers/show', [
            'teacher' => $teacher,
        ]);
    }
}