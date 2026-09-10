<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use Inertia\Inertia;
use Inertia\Response;

class FacilitiesController
{
    public function index(): Response
    {
        $facilities = [
            [
                'title' => 'public.facilities.classrooms',
                'description' => 'public.facilities.classroomsDesc',
                'icon' => 'Presentation',
            ],
            [
                'title' => 'public.facilities.scienceLabs',
                'description' => 'public.facilities.scienceLabsDesc',
                'icon' => 'FlaskConical',
            ],
            [
                'title' => 'public.facilities.library',
                'description' => 'public.facilities.libraryDesc',
                'icon' => 'Library',
            ],
            [
                'title' => 'public.facilities.sportsFacilities',
                'description' => 'public.facilities.sportsFacilitiesDesc',
                'icon' => 'Dumbbell',
            ],
            [
                'title' => 'public.facilities.auditorium',
                'description' => 'public.facilities.auditoriumDesc',
                'icon' => 'Drama',
            ],
            [
                'title' => 'public.facilities.outdoorAreas',
                'description' => 'public.facilities.outdoorAreasDesc',
                'icon' => 'Trees',
            ],
        ];

        return Inertia::render('public/facilities', [
            'facilities' => $facilities,
        ]);
    }
}
