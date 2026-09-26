<?php

declare(strict_types=1);

namespace App\Domain\Scheduling\Services;

use App\Domain\Academics\Models\AcademicYear;
use App\Domain\Academics\Models\Semester;
use App\Domain\Admissions\Models\AdmissionPeriod;
use App\Domain\Assessment\Models\Assessment;
use App\Domain\Assessment\Models\Exam;
use App\Domain\Communication\Models\Announcement;
use App\Domain\Content\Models\Event;
use App\Domain\Learning\Models\Assignment;
use App\Domain\Scheduling\DTO\CalendarItem;
use App\Domain\Scheduling\Models\CalendarDay;
use Carbon\CarbonInterface;
use DateTimeInterface;
use Illuminate\Support\Carbon;

/**
 * Read model that normalises every dated thing a school already records into a
 * single calendar feed.
 *
 * Nothing here is stored twice: authored `calendar_days` rows cover what no
 * other table knows (holidays, closures), and everything else is derived on
 * read. All comparisons are done with datetime bounds rather than SQL date
 * functions, so the same queries work on MySQL and SQLite (Decision 4).
 */
final readonly class AcademicCalendar
{
    /** Types that can be requested from `between()`. */
    public const array TYPES = [
        'holiday',
        'closure',
        'term_start',
        'term_end',
        'exam_period',
        'event',
        'staff_workday',
        'term',
        'exam',
        'assessment',
        'assignment',
        'admissions',
        'announcement',
    ];

    public function __construct(private int $schoolId) {}

    /**
     * @param  list<string>  $only  Limit to these item types.
     * @return list<CalendarItem>
     */
    public function between(CarbonInterface $start, CarbonInterface $end, array $only = []): array
    {
        $items = [
            ...$this->authored($start, $end),
            ...$this->terms($start, $end),
            ...$this->events($start, $end),
            ...$this->exams($start, $end),
            ...$this->assessments($start, $end),
            ...$this->assignments($start, $end),
            ...$this->admissionPeriods($start, $end),
            ...$this->announcements($start, $end),
        ];

        if ($only !== []) {
            $items = array_values(array_filter(
                $items,
                static fn (CalendarItem $item): bool => in_array($item->type, $only, true),
            ));
        }

        usort($items, static fn(CalendarItem $a, CalendarItem $b): int => [$a->date, $a->title] <=> [$b->date, $b->title]);

        return $items;
    }

    /**
     * @param  list<string>  $only
     * @return list<CalendarItem>
     */
    public function forMonth(int $year, int $month, array $only = []): array
    {
        $start = Carbon::create($year, $month, 1, 0, 0, 0)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        return $this->between($start, $end, $only);
    }

    /**
     * Every item keyed by the ISO date it starts on.
     *
     * @param  list<string>  $only
     * @return array<string, list<array<string, mixed>>>
     */
    public function groupedByDay(CarbonInterface $start, CarbonInterface $end, array $only = []): array
    {
        $grouped = [];

        foreach ($this->between($start, $end, $only) as $item) {
            $grouped[$item->date][] = $item->toArray();
        }

        return $grouped;
    }

    /**
     * @return list<CalendarItem>
     */
    private function authored(CarbonInterface $start, CarbonInterface $end): array
    {
        return CalendarDay::where('school_id', $this->schoolId)
            ->whereDate('date', '<=', $end->toDateString())
            ->where(function ($query) use ($start): void {
                $query->whereNull('end_date')
                    ->orWhereDate('end_date', '>=', $start->toDateString());
            })
            ->orderBy('date')
            ->get()
            ->map(fn (CalendarDay $day): CalendarItem => new CalendarItem(
                date: (string) $this->day($day->date),
                endDate: $this->day($day->end_date),
                type: $day->type->value,
                title: $day->title,
                source: 'calendar_day',
                id: $day->id,
            ))
            ->all();
    }

    /**
     * @return list<CalendarItem>
     */
    private function terms(CarbonInterface $start, CarbonInterface $end): array
    {
        $items = [];

        $years = AcademicYear::where('school_id', $this->schoolId)
            ->whereDate('start_date', '<=', $end->toDateString())
            ->whereDate('end_date', '>=', $start->toDateString())
            ->get();

        foreach ($years as $year) {
            $items[] = new CalendarItem(
                date: (string) $this->day($year->start_date),
                endDate: $this->day($year->end_date),
                type: 'term',
                title: "{$year->name} begins",
                source: 'academic_year',
                id: $year->id,
            );
            $items[] = new CalendarItem(
                date: (string) $this->day($year->end_date),
                endDate: null,
                type: 'term',
                title: "{$year->name} ends",
                source: 'academic_year',
                id: $year->id,
            );
        }

        $semesters = Semester::where('school_id', $this->schoolId)
            ->whereDate('start_date', '<=', $end->toDateString())
            ->whereDate('end_date', '>=', $start->toDateString())
            ->get();

        foreach ($semesters as $semester) {
            $items[] = new CalendarItem(
                date: (string) $this->day($semester->start_date),
                endDate: $this->day($semester->end_date),
                type: 'term',
                title: $semester->name,
                source: 'semester',
                id: $semester->id,
            );
        }

        return $items;
    }

    /**
     * @return list<CalendarItem>
     */
    private function events(CarbonInterface $start, CarbonInterface $end): array
    {
        return Event::where('school_id', $this->schoolId)
            ->where('start_date', '<=', $end->copy()->endOfDay())
            ->where(function ($query) use ($start): void {
                $query->whereNull('end_date')
                    ->orWhere('end_date', '>=', $start->copy()->startOfDay());
            })
            ->get()
            ->map(fn (Event $event): CalendarItem => new CalendarItem(
                date: (string) $this->day($event->start_date),
                endDate: $this->day($event->end_date),
                type: 'event',
                title: $event->title,
                source: 'event',
                id: $event->id,
                href: "/events/{$event->id}",
            ))
            ->all();
    }

    /**
     * @return list<CalendarItem>
     */
    private function exams(CarbonInterface $start, CarbonInterface $end): array
    {
        return Exam::where('school_id', $this->schoolId)
            ->whereDate('exam_date', '>=', $start->toDateString())
            ->whereDate('exam_date', '<=', $end->toDateString())
            ->get()
            ->map(fn (Exam $exam): CalendarItem => new CalendarItem(
                date: (string) $this->day($exam->exam_date),
                endDate: null,
                type: 'exam',
                title: $exam->name,
                source: 'exam',
                id: $exam->id,
                href: "/exams/{$exam->id}",
            ))
            ->all();
    }

    /**
     * @return list<CalendarItem>
     */
    private function assessments(CarbonInterface $start, CarbonInterface $end): array
    {
        return Assessment::where('school_id', $this->schoolId)
            ->whereNotNull('due_date')
            ->whereDate('due_date', '>=', $start->toDateString())
            ->whereDate('due_date', '<=', $end->toDateString())
            ->get()
            ->map(fn (Assessment $assessment): CalendarItem => new CalendarItem(
                date: (string) $this->day($assessment->due_date),
                endDate: null,
                type: 'assessment',
                title: $assessment->name,
                source: 'assessment',
                id: $assessment->id,
                href: "/assessments/{$assessment->id}",
            ))
            ->all();
    }

    /**
     * @return list<CalendarItem>
     */
    private function assignments(CarbonInterface $start, CarbonInterface $end): array
    {
        return Assignment::where('school_id', $this->schoolId)
            ->whereNotNull('due_date')
            ->whereDate('due_date', '>=', $start->toDateString())
            ->whereDate('due_date', '<=', $end->toDateString())
            ->get()
            ->map(fn (Assignment $assignment): CalendarItem => new CalendarItem(
                date: (string) $this->day($assignment->due_date),
                endDate: null,
                type: 'assignment',
                title: $assignment->title,
                source: 'assignment',
                id: $assignment->id,
                href: "/assignments/{$assignment->id}",
            ))
            ->all();
    }

    /**
     * @return list<CalendarItem>
     */
    private function admissionPeriods(CarbonInterface $start, CarbonInterface $end): array
    {
        return AdmissionPeriod::where('school_id', $this->schoolId)
            ->whereDate('start_date', '<=', $end->toDateString())
            ->whereDate('end_date', '>=', $start->toDateString())
            ->get()
            ->map(fn (AdmissionPeriod $period): CalendarItem => new CalendarItem(
                date: (string) $this->day($period->start_date),
                endDate: $this->day($period->end_date),
                type: 'admissions',
                title: $period->name,
                source: 'admission_period',
                id: $period->id,
                href: '/admissions/periods',
            ))
            ->all();
    }

    /**
     * @return list<CalendarItem>
     */
    private function announcements(CarbonInterface $start, CarbonInterface $end): array
    {
        return Announcement::where('school_id', $this->schoolId)
            ->whereNotNull('start_date')
            ->whereDate('start_date', '<=', $end->toDateString())
            ->where(function ($query) use ($start): void {
                $query->whereNull('end_date')
                    ->orWhereDate('end_date', '>=', $start->toDateString());
            })
            ->get()
            ->map(fn (Announcement $announcement): CalendarItem => new CalendarItem(
                date: (string) $this->day($announcement->start_date),
                endDate: $this->day($announcement->end_date),
                type: 'announcement',
                title: $announcement->title,
                source: 'announcement',
                id: $announcement->id,
                href: '/announcements',
            ))
            ->all();
    }

    /**
     * Accepts either a Carbon instance (from a model cast) or a raw string.
     */
    private function day(string|DateTimeInterface|null $value): ?string
    {
        return $value === null ? null : Carbon::parse($value)->toDateString();
    }
}
