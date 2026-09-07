<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use Inertia\Response;
use Inertia\Inertia;

class FaqController
{
    public function index(): Response
    {
        $faqs = [
            'admissions' => [
                ['question' => 'public.faq.admissions.howToApply.q', 'answer' => 'public.faq.admissions.howToApply.a'],
                ['question' => 'public.faq.admissions.requirements.q', 'answer' => 'public.faq.admissions.requirements.a'],
                ['question' => 'public.faq.admissions.deadline.q', 'answer' => 'public.faq.admissions.deadline.a'],
            ],
            'academics' => [
                ['question' => 'public.faq.academics.curriculum.q', 'answer' => 'public.faq.academics.curriculum.a'],
                ['question' => 'public.faq.academics.classSizes.q', 'answer' => 'public.faq.academics.classSizes.a'],
                ['question' => 'public.faq.academics.extracurricular.q', 'answer' => 'public.faq.academics.extracurricular.a'],
            ],
            'fees' => [
                ['question' => 'public.faq.fees.tuition.q', 'answer' => 'public.faq.fees.tuition.a'],
                ['question' => 'public.faq.fees.financialAid.q', 'answer' => 'public.faq.fees.financialAid.a'],
                ['question' => 'public.faq.fees.paymentMethods.q', 'answer' => 'public.faq.fees.paymentMethods.a'],
            ],
            'general' => [
                ['question' => 'public.faq.general.hours.q', 'answer' => 'public.faq.general.hours.a'],
                ['question' => 'public.faq.general.transportation.q', 'answer' => 'public.faq.general.transportation.a'],
                ['question' => 'public.faq.general.tour.q', 'answer' => 'public.faq.general.tour.a'],
            ],
        ];

        return Inertia::render('public/faq', [
            'faqs' => $faqs,
        ]);
    }
}