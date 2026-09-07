<?php

declare(strict_types=1);

namespace App\Domain\Admissions\Enums;

enum ApplicationStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case UnderReview = 'under_review';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Converted = 'converted';
    case Withdrawn = 'withdrawn';
}
