<?php

declare(strict_types=1);

namespace App\Domain\Compliance\Enums;

enum AuditAction: string
{
    case Created = 'created';
    case Updated = 'updated';
    case Deleted = 'deleted';
    case Viewed = 'viewed';
    case Exported = 'exported';
    case LoggedIn = 'logged_in';
    case LoggedOut = 'logged_out';
}
