<?php

declare(strict_types=1);

namespace App\Domain\Communication\Enums;

enum AnnouncementAudience: string
{
    case All = 'all';
    case Students = 'students';
    case Teachers = 'teachers';
    case Parents = 'parents';
    case Staff = 'staff';
}
