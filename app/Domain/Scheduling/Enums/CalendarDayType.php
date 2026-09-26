<?php

declare(strict_types=1);

namespace App\Domain\Scheduling\Enums;

enum CalendarDayType: string
{
    case Holiday = 'holiday';
    case Closure = 'closure';
    case TermStart = 'term_start';
    case TermEnd = 'term_end';
    case ExamPeriod = 'exam_period';
    case Event = 'event';
    case StaffWorkday = 'staff_workday';

    /**
     * Whether teaching happens on this day.
     *
     * Authoring an explicit flag wins; this is the sensible default when the
     * author does not set one.
     */
    public function isInstructionalByDefault(): bool
    {
        return match ($this) {
            self::StaffWorkday, self::TermStart => true,
            default => false,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Holiday => 'Holiday',
            self::Closure => 'Closure',
            self::TermStart => 'Term start',
            self::TermEnd => 'Term end',
            self::ExamPeriod => 'Exam period',
            self::Event => 'Event',
            self::StaffWorkday => 'Staff workday',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }
}
