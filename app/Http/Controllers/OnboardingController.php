<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Domain\Schools\Models\Organization;
use Illuminate\Http\Request;
use Inertia\Inertia;

class OnboardingController extends Controller
{
    public function index()
    {
        return Inertia::render('onboarding/index');
    }

    public function schoolInformation(Request $request)
    {
        return Inertia::render('onboarding/school-information');
    }

    public function createSchool(Request $request)
    {
        $organizations = Organization::all();

        return Inertia::render('onboarding/create-school', [
            'organizations' => $organizations,
        ]);
    }

    public function academicYear(Request $request)
    {
        return Inertia::render('onboarding/academic-year');
    }

    public function grades(Request $request)
    {
        return Inertia::render('onboarding/grades');
    }

    public function subjects(Request $request)
    {
        return Inertia::render('onboarding/subjects');
    }

    public function teachers(Request $request)
    {
        return Inertia::render('onboarding/teachers');
    }

    public function students(Request $request)
    {
        return Inertia::render('onboarding/students');
    }

    public function feeStructure(Request $request)
    {
        return Inertia::render('onboarding/fee-structure');
    }

    public function paymentGateway(Request $request)
    {
        return Inertia::render('onboarding/payment-gateway');
    }

    public function finish(Request $request)
    {
        return Inertia::render('onboarding/finish');
    }
}
