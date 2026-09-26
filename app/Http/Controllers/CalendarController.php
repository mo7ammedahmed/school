<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Scheduling\DTO\CalendarItem;
use App\Domain\Scheduling\Enums\CalendarDayType;
use App\Domain\Scheduling\Services\AcademicCalendar;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Inertia\Response as InertiaResponse;
use Throwable;

class CalendarController extends Controller
{
    /** The school week runs Sunday–Thursday; the grid always starts on Sunday. */
    private const WEEK_START = Carbon::SUNDAY;

    public function index(Request $request): InertiaResponse
    {
        $view = in_array($request->string('view')->toString(), ['month', 'week', 'day'], true)
            ? $request->string('view')->toString()
            : 'month';

        $anchor = $this->anchor($request->string('date')->toString());
        [$start, $end] = $this->range($view, $anchor);

        $calendar = new AcademicCalendar((int) session('school_id'));
        $items = $calendar->between($start, $end);
        $grouped = $calendar->groupedByDay($start, $end);

        $gridStart = $view === 'month' ? $start->copy()->startOfWeek(self::WEEK_START) : $start->copy();
        $gridEnd = $view === 'month' ? $end->copy()->endOfWeek(Carbon::SATURDAY) : $end->copy();

        return inertia('calendar/index', [
            'view' => $view,
            'anchor' => $anchor->toDateString(),
            'range' => [
                'start' => $start->toDateString(),
                'end' => $end->toDateString(),
            ],
            'cells' => $this->cells($gridStart, $gridEnd, $start, $end, $grouped),
            'items' => array_map(static fn (CalendarItem $item): array => $item->toArray(), $items),
            'dayTypeOptions' => CalendarDayType::options(),
            'filterOptions' => AcademicCalendar::TYPES,
        ]);
    }

    public function feed(Request $request): JsonResponse
    {
        $start = $this->anchor($request->string('start')->toString())->startOfDay();
        $end = $this->anchor($request->string('end')->toString())->endOfDay();

        $only = array_values(array_filter(
            (array) $request->input('types', []),
            static fn ($type): bool => is_string($type) && in_array($type, AcademicCalendar::TYPES, true),
        ));

        $items = (new AcademicCalendar((int) session('school_id')))->between($start, $end, $only);

        return response()->json([
            'start' => $start->toDateString(),
            'end' => $end->toDateString(),
            'items' => array_map(static fn (CalendarItem $item): array => $item->toArray(), $items),
        ]);
    }

    public function download(Request $request): Response
    {
        $start = $this->anchor($request->string('start')->toString())->startOfDay();
        $end = $this->anchor($request->string('end')->toString())->endOfDay();

        $items = (new AcademicCalendar((int) session('school_id')))->between($start, $end);

        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//Aether School OS//Academic Calendar//EN',
            'CALSCALE:GREGORIAN',
        ];

        foreach ($items as $index => $item) {
            $endDate = $item->endDate ?? $item->date;

            $lines = [
                ...$lines,
                'BEGIN:VEVENT',
                'UID:'.$item->source.'-'.($item->id ?? 'x').'-'.$item->date.'-'.$index.'@aether',
                'DTSTAMP:'.now()->utc()->format('Ymd\THis\Z'),
                'DTSTART;VALUE=DATE:'.str_replace('-', '', $item->date),
                'DTEND;VALUE=DATE:'.Carbon::parse($endDate)->addDay()->format('Ymd'),
                'SUMMARY:'.$this->escape($item->title),
                'CATEGORIES:'.$item->type,
                'END:VEVENT',
            ];
        }

        $lines[] = 'END:VCALENDAR';

        return response(implode("\r\n", $lines), 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="academic-calendar.ics"',
        ]);
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function range(string $view, Carbon $anchor): array
    {
        return match ($view) {
            'day' => [$anchor->copy()->startOfDay(), $anchor->copy()->endOfDay()],
            'week' => [
                $anchor->copy()->startOfWeek(self::WEEK_START),
                $anchor->copy()->endOfWeek(Carbon::SATURDAY),
            ],
            default => [$anchor->copy()->startOfMonth(), $anchor->copy()->endOfMonth()],
        };
    }

    /**
     * @param  array<string, list<array<string, mixed>>>  $grouped
     * @return list<array<string, mixed>>
     */
    private function cells(Carbon $gridStart, Carbon $gridEnd, Carbon $rangeStart, Carbon $rangeEnd, array $grouped): array
    {
        $cells = [];
        $cursor = $gridStart->copy();

        while ($cursor->lessThanOrEqualTo($gridEnd)) {
            $key = $cursor->toDateString();

            $cells[] = [
                'date' => $key,
                'in_range' => $cursor->greaterThanOrEqualTo($rangeStart) && $cursor->lessThanOrEqualTo($rangeEnd),
                'is_today' => $cursor->isToday(),
                'weekday' => (int) $cursor->dayOfWeek,
                'items' => $grouped[$key] ?? [],
            ];

            $cursor->addDay();
        }

        return $cells;
    }

    private function anchor(string $date): Carbon
    {
        if ($date === '') {
            return Carbon::now()->startOfDay();
        }

        try {
            return Carbon::parse($date)->startOfDay();
        } catch (Throwable) {
            return Carbon::now()->startOfDay();
        }
    }

    private function escape(string $value): string
    {
        return str_replace([',', ';', "\n"], ['\\,', '\\;', '\\n'], $value);
    }
}
