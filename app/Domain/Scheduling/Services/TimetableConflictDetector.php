<?php

declare(strict_types=1);

namespace App\Domain\Scheduling\Services;

use App\Domain\Scheduling\Models\TimetableEntry;
use App\Domain\Schools\Support\TenantContext;

/**
 * Finds double-bookings in the timetable.
 *
 * Overlap is evaluated in PHP from parsed minutes rather than in SQL: the
 * comparison is identical on MySQL and SQLite, and a single school-day of
 * entries is always a small set.
 *
 * Both entry points name the school they are about, so each pins that school
 * around its query — the detector is also driven from tests and, eventually,
 * commands, where no request session can supply the tenant.
 */
final class TimetableConflictDetector
{
    public function __construct(private readonly TenantContext $tenants) {}

    /**
     * Conflicts for a candidate entry (used before it is saved).
     *
     * @param  array{school_id: int, day_of_week: string, start_time: string, end_time: string, teacher_id?: int|null, room_id?: int|null, section_id?: int|null}  $candidate
     * @return list<array{entry_id: int, reasons: list<string>, label: string, time: string}>
     */
    public function detect(array $candidate, ?int $ignoreEntryId = null): array
    {
        $start = $this->minutes((string) $candidate['start_time']);
        $end = $this->minutes((string) $candidate['end_time']);

        if ($start === null || $end === null || $end <= $start) {
            return [];
        }

        $query = TimetableEntry::query()
            ->where('school_id', $candidate['school_id'])
            ->where('day_of_week', $candidate['day_of_week'])
            ->with(['section', 'room', 'offering.subject']);

        if ($ignoreEntryId !== null) {
            $query->whereKeyNot($ignoreEntryId);
        }

        $shared = array_filter([
            'teacher_id' => $candidate['teacher_id'] ?? null,
            'room_id' => $candidate['room_id'] ?? null,
            'section_id' => $candidate['section_id'] ?? null,
        ], static fn (?int $value): bool => $value !== null && $value !== 0);

        if ($shared === []) {
            return [];
        }

        $query->where(function ($nested) use ($shared): void {
            foreach ($shared as $column => $value) {
                $nested->orWhere($column, $value);
            }
        });

        $conflicts = [];

        foreach ($this->tenants->runFor((int) $candidate['school_id'], fn () => $query->get()) as $entry) {
            $otherStart = $this->minutes((string) $entry->start_time);
            $otherEnd = $this->minutes((string) $entry->end_time);

            if ($otherStart === null || $otherEnd === null) {
                continue;
            }

            // Half-open intervals: touching ends (10:00–11:00 and 11:00–12:00) do not conflict.
            if ($otherStart >= $end || $otherEnd <= $start) {
                continue;
            }

            $reasons = [];

            foreach (array_keys($shared) as $column) {
                if ((int) $entry->{$column} === (int) $shared[$column]) {
                    $reasons[] = str_replace('_id', '', $column);
                }
            }

            if ($reasons === []) {
                continue;
            }

            $conflicts[] = [
                'entry_id' => $entry->id,
                'reasons' => $reasons,
                'label' => $this->label($entry),
                'time' => $entry->startsAt().' - '.$entry->endsAt(),
            ];
        }

        return $conflicts;
    }

    /**
     * Every conflicting pair in a school's timetable.
     *
     * @return list<array{entry_id: int, conflicts_with: int, reasons: list<string>, label: string, time: string, day_of_week: string}>
     */
    public function allForSchool(int $schoolId): array
    {
        $entries = $this->tenants->runFor($schoolId, fn () => TimetableEntry::where('school_id', $schoolId)
            ->with(['section', 'room', 'offering.subject'])
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get())
            ->groupBy('day_of_week');

        $conflicts = [];

        foreach ($entries as $day => $dayEntries) {
            $dayEntries = $dayEntries->values();

            for ($i = 0; $i < $dayEntries->count(); $i++) {
                for ($j = $i + 1; $j < $dayEntries->count(); $j++) {
                    $left = $dayEntries[$i];
                    $right = $dayEntries[$j];
                    $reasons = $this->reasons($left, $right);

                    if ($reasons === []) {
                        continue;
                    }

                    $conflicts[] = [
                        'entry_id' => $left->id,
                        'conflicts_with' => $right->id,
                        'reasons' => $reasons,
                        'label' => $this->label($left),
                        'other_label' => $this->label($right),
                        'time' => $left->startsAt().' - '.$left->endsAt(),
                        'other_time' => $right->startsAt().' - '.$right->endsAt(),
                        'day_of_week' => (string) $day,
                    ];
                }
            }
        }

        return $conflicts;
    }

    /**
     * @return list<string>
     */
    private function reasons(TimetableEntry $left, TimetableEntry $right): array
    {
        $leftStart = $this->minutes((string) $left->start_time);
        $leftEnd = $this->minutes((string) $left->end_time);
        $rightStart = $this->minutes((string) $right->start_time);
        $rightEnd = $this->minutes((string) $right->end_time);

        if ($leftStart === null || $leftEnd === null || $rightStart === null || $rightEnd === null) {
            return [];
        }

        if ($leftStart >= $rightEnd || $rightStart >= $leftEnd) {
            return [];
        }

        $reasons = [];

        if ((int) $left->teacher_id === (int) $right->teacher_id) {
            $reasons[] = 'teacher';
        }

        if ($left->room_id !== null && (int) $left->room_id === (int) $right->room_id) {
            $reasons[] = 'room';
        }

        if ((int) $left->section_id === (int) $right->section_id) {
            $reasons[] = 'section';
        }

        return $reasons;
    }

    private function label(TimetableEntry $entry): string
    {
        $subject = $entry->offering?->subject?->name;
        $section = $entry->section?->name;

        return trim(($subject ?? 'Session').' · '.($section ?? '—'), ' ·');
    }

    /**
     * Parse 'HH:MM' or 'HH:MM:SS' into minutes past midnight.
     */
    public function minutes(string $time): ?int
    {
        if (! preg_match('/^(\d{1,2}):(\d{2})/', trim($time), $matches)) {
            return null;
        }

        $hours = (int) $matches[1];
        $minutes = (int) $matches[2];

        if ($hours > 23 || $minutes > 59) {
            return null;
        }

        return ($hours * 60) + $minutes;
    }
}
