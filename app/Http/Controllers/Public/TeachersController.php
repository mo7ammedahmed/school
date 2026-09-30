<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Models\Teacher;
use Inertia\Inertia;
use Inertia\Response;

class TeachersController extends PublicController
{
    // There is no `status` on a teacher: the row is live until it is soft
    // deleted, and asking for an `active` one matched nothing, which emptied the
    // whole page. `position` and `education` were never columns either — the
    // profile calls them `specialization` and `qualification`.
    public function index(): Response
    {
        $teachers = Teacher::forSchool($this->schoolId())
            ->with('subjects')
            ->get(['id', 'first_name', 'last_name', 'email', 'phone', 'specialization', 'bio', 'qualification']);

        return Inertia::render('public/teachers', [
            'teachers' => $teachers,
        ]);
    }

    public function show(int $id): Response
    {
        $teacher = Teacher::forSchool($this->schoolId())
            ->with('subjects')
            ->findOrFail($id);

        return Inertia::render('public/teachers/show', [
            'teacher' => $teacher,
        ]);
    }
}
