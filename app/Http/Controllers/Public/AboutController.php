<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use Inertia\Inertia;
use Inertia\Response;

class AboutController extends PublicController
{
    public function index(): Response
    {
        if ($managed = $this->managedPage('about')) {
            return $managed;
        }

        return Inertia::render('public/about', [
            'stats' => [
                ['number' => '500+', 'label' => 'public.studentsEnrolled'],
                ['number' => '50+', 'label' => 'public.qualifiedTeachers'],
                ['number' => '25+', 'label' => 'public.yearsOfExcellence'],
                ['number' => '15+', 'label' => 'public.academicPrograms'],
            ],
        ]);
    }
}
