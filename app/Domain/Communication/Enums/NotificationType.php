<?php

declare(strict_types=1);

namespace App\Domain\Communication\Enums;

enum NotificationType: string
{
    case Info = 'info';
    case Warning = 'warning';
    case Success = 'success';
    case Error = 'error';
}
