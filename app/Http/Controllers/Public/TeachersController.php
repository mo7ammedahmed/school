<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Domain\Schools\Services\SchoolResolver;
use App\Models\Teacher;
use Inertia\Inertia;
use Inertia\Response;

class TeachersController
{
    // There is no `status` on a teacher: the row is live until it is soft
    // deleted, and asking for an `active` one matched nothing, which emptied the
    // whole page. `position` and `education` were never columns either — the
    // profile calls them `specialization` and `qualification`.
    //
    // Nobody browsing the public site has a school in their session, so the
    // resolver supplies the site's own school; `session('school_id')` here was
    // null and matched no rows at all.
    public function __construct(private readonly SchoolResolver $schools) {}

    public function index(): Response
    {
        $teachers = Teacher::with('subjects')
            ->where('school_id', $this->schools->current()?->id)
            ->get(['id', 'first_name', 'last_name', 'email', 'phone', 'specialization', 'bio', 'qualification']);

        return Inertia::render('public/teachers', [
            'teachers' => $teachers,
        ]);
    }

    public function show(int $id): Response
    {
        $teacher = Teacher::with('subjects')
            ->where('school_id', $this->schools->current()?->id)
            ->findOrFail($id);

        return Inertia::render('public/teachers/show', [
            'teacher' => $teacher,
        ]);
    }
}
