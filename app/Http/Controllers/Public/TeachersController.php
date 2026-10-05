<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Models\Teacher;
use Inertia\Inertia;
use Inertia\Response;

class TeachersController extends PublicController
{
    /**
     * Everything a visitor may see about a member of staff, in one place.
     *
     * Named once and used by both the list and the profile page, because the two
     * drifting apart is the whole hazard: the list had an allowlist and the
     * profile page returned the row, so a visitor opening one teacher saw
     * `employee_id`, `hire_date`, `metadata` and `school_id` — an HR record and a
     * join key to a login account — that the list had deliberately withheld.
     *
     * `metadata` is excluded for a second reason: nothing constrains what goes in
     * it, so a column that is safe today is not a column that is safe by design.
     */
    private const PUBLIC_COLUMNS = [
        'id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'specialization',
        'bio',
        'qualification',
    ];

    // There is no `status` on a teacher: the row is live until it is soft
    // deleted, and asking for an `active` one matched nothing, which emptied the
    // whole page. `position` and `education` were never columns either — the
    // profile calls them `specialization` and `qualification`.
    public function index(): Response
    {
        if ($managed = $this->managedPage('faculty')) {
            return $managed;
        }
        $teachers = Teacher::forSchool($this->schoolId())
            ->with('subjects')
            ->get(self::PUBLIC_COLUMNS);

        return Inertia::render('public/teachers', [
            'teachers' => $teachers,
        ]);
    }

    public function show(int $id): Response
    {
        $teacher = Teacher::forSchool($this->schoolId())
            ->with('subjects')
            ->find($id, self::PUBLIC_COLUMNS);

        abort_if($teacher === null, 404);

        return Inertia::render('public/teachers/show', [
            'teacher' => $teacher,
        ]);
    }
}
