<?php

declare(strict_types=1);

namespace App\Domain\Finance\Enums;

enum FeeTypeCategory: string
{
    case Tuition = 'tuition';
    case Transport = 'transport';
    case Meals = 'meals';
    case Activities = 'activities';
    case Other = 'other';
}
