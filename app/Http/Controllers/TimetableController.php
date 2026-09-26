<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Academics\Models\Offering;
use App\Domain\Academics\Models\Section;
use App\Domain\Academics\Models\Semester;
use App\Domain\Academics\Models\Subject;
use App\Domain\People\Models\TeacherProfile;
use App\Domain\Scheduling\Models\CalendarDay;
use App\Domain\Scheduling\Models\Period;
use App\Domain\Scheduling\Models\Room;
use App\Domain\Scheduling\Models\TimetableEntry;
use App\Domain\Scheduling\Services\TimetableConflictDetector;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class TimetableController extends Controller
{
    /** School week: Sunday through Thursday. */
    private const DAYS = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday'];

    public function __construct(private readonly TimetableConflictDetector $conflicts) {}

    /**
     * The timetable lands on the calendar; the weekly grid and the flat list
     * stay one click away through the shared view switcher.
     */
    public function index(Request $request): InertiaResponse
    {
        return $this->calendarView($request, '/timetable');
    }

    /**
     * The same entries as a filterable table.
     */
    public function list(Request $request): InertiaResponse
    {
        $filters = array_filter([
            'section_id' => $request->integer('section_id') ?: null,
            'teacher_id' => $request->integer('teacher_id') ?: null,
            'semester_id' => $request->integer('semester_id') ?: null,
        ], static fn (?int $value): bool => $value !== null);

        $query = TimetableEntry::where('school_id', $this->schoolId())
            ->with(['offering.subject', 'section', 'teacher', 'room']);

        foreach ($filters as $column => $value) {
            $query->where($column, $value);
        }

        return inertia('timetable/index', [
            'timetables' => $query->latest()->paginate(15)->withQueryString(),
            'filters' => $filters,
        ]);
    }

    /**
     * Days × periods grid for one section or teacher.
     */
    public function grid(Request $request): InertiaResponse
    {
        $schoolId = $this->schoolId();
        $sectionId = $request->integer('section_id') ?: null;
        $teacherId = $request->integer('teacher_id') ?: null;
        $semesterId = $request->integer('semester_id') ?: null;

        $query = TimetableEntry::where('school_id', $schoolId)
            ->with(['offering.subject', 'section', 'room', 'teacher']);

        if ($sectionId !== null) {
            $query->where('section_id', $sectionId);
        }

        if ($teacherId !== null) {
            $query->where('teacher_id', $teacherId);
        }

        if ($semesterId !== null) {
            $query->where('semester_id', $semesterId);
        }

        $entries = $query->orderBy('start_time')->get();

        $periods = Period::where('school_id', $schoolId)
            ->orderBy('sort_order')
            ->orderBy('start_time')
            ->get();

        $rows = $periods->isNotEmpty()
            ? $periods->map(fn (Period $period) => [
                'id' => $period->id,
                'label' => $period->name,
                'start' => $period->startsAt(),
                'end' => $period->endsAt(),
                'is_break' => $period->is_break,
            ])->all()
            : $this->derivedRows($entries);

        $matrix = $this->matrix($entries, $rows);

        return inertia('timetable/grid', [
            'basePath' => '/timetable/grid',
            'rows' => $rows,
            'days' => self::DAYS,
            'matrix' => $matrix,
            'filters' => [
                'section_id' => $sectionId,
                'teacher_id' => $teacherId,
                'semester_id' => $semesterId,
            ],
            'sections' => Section::where('school_id', $schoolId)->orderBy('name_en')->get(['id', 'name_en'])
                ->map(fn (Section $section) => ['id' => $section->id, 'name' => $section->name])->all(),
            'teachers' => TeacherProfile::where('school_id', $schoolId)->orderBy('first_name')
                ->get(['id', 'first_name', 'last_name'])
                ->map(fn (TeacherProfile $teacher) => [
                    'id' => $teacher->id,
                    'name' => trim($teacher->first_name.' '.$teacher->last_name),
                ])->all(),
            'semesters' => Semester::where('school_id', $schoolId)->orderBy('id')->get(['id', 'name_en'])
                ->map(fn (Semester $semester) => ['id' => $semester->id, 'name' => $semester->name])->all(),
            'hasPeriods' => $periods->isNotEmpty(),
        ]);
    }

    /**
     * The weekly timetable projected onto a real calendar month.
     *
     * Each date renders the lessons that fall on its weekday, except on
     * non-instructional calendar days (holidays, closures) where lessons are
     * suppressed and the day is flagged instead.
     */
    public function calendar(Request $request): InertiaResponse
    {
        return $this->calendarView($request, '/timetable/calendar');
    }

    /**
     * @param  string  $basePath  the URL this render belongs to, so in-page
     *                            navigation (month arrows, filters) stays there.
     */
    private function calendarView(Request $request, string $basePath): InertiaResponse
    {
        $schoolId = $this->schoolId();
        $sectionId = $request->integer('section_id') ?: null;
        $teacherId = $request->integer('teacher_id') ?: null;
        $semesterId = $request->integer('semester_id') ?: null;

        $anchor = $this->anchorMonth($request->string('date')->toString());

        $query = TimetableEntry::where('school_id', $schoolId)
            ->whereIn('day_of_week', self::DAYS)
            ->with(['offering.subject', 'section', 'room', 'teacher']);

        if ($sectionId !== null) {
            $query->where('section_id', $sectionId);
        }

        if ($teacherId !== null) {
            $query->where('teacher_id', $teacherId);
        }

        if ($semesterId !== null) {
            $query->where('semester_id', $semesterId);
        }

        $byDay = $query->orderBy('start_time')->get()->groupBy('day_of_week');

        // Sunday-to-Saturday weeks, padded so the month fills whole rows.
        $rangeStart = $anchor->copy()->startOfMonth()->startOfWeek(Carbon::SUNDAY);
        $rangeEnd = $anchor->copy()->endOfMonth()->endOfWeek(Carbon::SATURDAY);

        $closures = $this->nonInstructionalDays($schoolId, $rangeStart, $rangeEnd);

        $cells = [];
        $today = Carbon::today()->toDateString();

        for ($cursor = $rangeStart->copy(); $cursor->lessThanOrEqualTo($rangeEnd); $cursor->addDay()) {
            $date = $cursor->toDateString();
            $weekday = strtolower($cursor->englishDayOfWeek);
            $closed = $closures[$date] ?? null;

            $lessons = $this->isSchoolDay($weekday)
                ? ($byDay->get($weekday) ?? collect())
                    ->map(fn (TimetableEntry $entry) => [
                        'id' => $entry->id,
                        'subject' => $entry->offering?->subject?->name ?? $entry->section?->name,
                        'section' => $entry->section?->name,
                        'teacher' => trim(($entry->teacher?->first_name ?? '').' '.($entry->teacher?->last_name ?? '')),
                        'room' => $entry->room?->name,
                        'start' => $entry->startsAt(),
                        'end' => $entry->endsAt(),
                        'is_published' => (bool) $entry->is_published,
                    ])
                    ->all()
                : [];

            $cells[] = [
                'date' => $date,
                'weekday' => $weekday,
                'day_label' => $cursor->day,
                'in_month' => $cursor->month === $anchor->month,
                'is_today' => $date === $today,
                'is_school_day' => $this->isSchoolDay($weekday),
                'closure' => $closed,
                'lessons' => $closed !== null ? [] : $lessons,
            ];
        }

        return inertia('timetable/calendar', [
            'basePath' => $basePath,
            'month' => $anchor->format('Y-m'),
            'month_label' => $anchor->format('F Y'),
            'range' => ['start' => $rangeStart->toDateString(), 'end' => $rangeEnd->toDateString()],
            'days' => ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'],
            'cells' => $cells,
            'lesson_count' => collect($cells)->sum(fn (array $cell) => count($cell['lessons'])),
            'filters' => [
                'section_id' => $sectionId,
                'teacher_id' => $teacherId,
                'semester_id' => $semesterId,
            ],
            'sections' => Section::where('school_id', $schoolId)->orderBy('name_en')->get(['id', 'name_en'])
                ->map(fn (Section $section) => ['id' => $section->id, 'name' => $section->name])->all(),
            'teachers' => TeacherProfile::where('school_id', $schoolId)->orderBy('first_name')
                ->get(['id', 'first_name', 'last_name'])
                ->map(fn (TeacherProfile $teacher) => [
                    'id' => $teacher->id,
                    'name' => trim($teacher->first_name.' '.$teacher->last_name),
                ])->all(),
            'semesters' => Semester::where('school_id', $schoolId)->orderBy('id')->get(['id', 'name_en'])
                ->map(fn (Semester $semester) => ['id' => $semester->id, 'name' => $semester->name])->all(),
        ]);
    }

    public function create(): InertiaResponse
    {
        return inertia('timetable/create', $this->formOptions());
    }

    public function store(Request $request): RedirectResponse
    {
        $schoolId = $this->schoolId();
        $validated = $request->validate($this->rules($schoolId));

        $offering = $this->resolveOffering(
            (int) $validated['subject_id'],
            (int) $validated['section_id'],
            (int) $validated['teacher_id'],
        );

        $candidate = $this->candidate($schoolId, $validated, $this->resolveRoomId($validated['room'] ?? null));

        $this->assertNoConflicts($candidate);

        $entry = TimetableEntry::create([
            'school_id' => $schoolId,
            'academic_year_id' => $offering->academic_year_id,
            'semester_id' => $validated['semester_id'] ?? $this->currentSemesterId($offering->academic_year_id),
            'period_id' => $validated['period_id'] ?? null,
            'offering_id' => $offering->id,
            'room_id' => $candidate['room_id'],
            'teacher_id' => $validated['teacher_id'],
            'section_id' => $validated['section_id'],
            'day_of_week' => $validated['day_of_week'],
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            // Entries start unpublished; staff publish them explicitly once the
            // grid is settled so students never see a half-built timetable.
            'is_published' => false,
        ]);

        return redirect()->route('timetable.show', $entry)->with('success', 'Schedule created successfully');
    }

    public function show(TimetableEntry $timetable): InertiaResponse
    {
        $this->authorizeSchool($timetable);

        $timetable->load(['offering.subject', 'section', 'teacher', 'room', 'period']);

        return inertia('timetable/show', ['schedule' => $timetable]);
    }

    public function edit(TimetableEntry $timetable): InertiaResponse
    {
        $this->authorizeSchool($timetable);

        return inertia('timetable/edit', [
            'schedule' => $timetable->load(['offering.subject', 'section', 'teacher', 'room']),
            ...$this->formOptions(),
        ]);
    }

    public function update(Request $request, TimetableEntry $timetable): RedirectResponse
    {
        $this->authorizeSchool($timetable);

        $schoolId = $this->schoolId();
        $validated = $request->validate($this->rules($schoolId));

        $offering = $this->resolveOffering(
            (int) $validated['subject_id'],
            (int) $validated['section_id'],
            (int) $validated['teacher_id'],
        );

        $candidate = $this->candidate($schoolId, $validated, $this->resolveRoomId($validated['room'] ?? null));

        $this->assertNoConflicts($candidate, $timetable->id);

        $timetable->update([
            'academic_year_id' => $offering->academic_year_id,
            'semester_id' => $validated['semester_id'] ?? $this->currentSemesterId($offering->academic_year_id),
            'period_id' => $validated['period_id'] ?? null,
            'offering_id' => $offering->id,
            'room_id' => $candidate['room_id'],
            'teacher_id' => $validated['teacher_id'],
            'section_id' => $validated['section_id'],
            'day_of_week' => $validated['day_of_week'],
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
        ]);

        return redirect()->route('timetable.show', $timetable)->with('success', 'Schedule updated successfully');
    }

    public function destroy(TimetableEntry $timetable): RedirectResponse
    {
        $this->authorizeSchool($timetable);

        $timetable->delete();

        return redirect()->route('timetable.index')->with('success', 'Schedule deleted successfully');
    }

    public function publish(TimetableEntry $timetable): RedirectResponse
    {
        $this->authorizeSchool($timetable);

        $timetable->update(['is_published' => true]);

        return back()->with('success', 'Timetable entry published.');
    }

    public function unpublish(TimetableEntry $timetable): RedirectResponse
    {
        $this->authorizeSchool($timetable);

        $timetable->update(['is_published' => false]);

        return back()->with('success', 'Timetable entry unpublished.');
    }

    public function conflicts(): InertiaResponse
    {
        return inertia('timetable/conflicts', [
            'conflicts' => $this->conflicts->allForSchool($this->schoolId()),
        ]);
    }

    public function teacher(int $teacher): InertiaResponse
    {
        return $this->filteredIndex(['teacher_id' => $teacher]);
    }

    public function section(int $section): InertiaResponse
    {
        return $this->filteredIndex(['section_id' => $section]);
    }

    public function mySchedule(): InertiaResponse
    {
        $profile = TeacherProfile::where('school_id', $this->schoolId())
            ->where('user_id', auth()->id())
            ->first();

        return $profile
            ? $this->filteredIndex(['teacher_id' => $profile->id])
            : $this->index();
    }

    /**
     * Printable timetable for the current filter.
     */
    public function exportPdf(Request $request): SymfonyResponse
    {
        $schoolId = $this->schoolId();

        $query = TimetableEntry::where('school_id', $schoolId)->with(['offering.subject', 'section', 'teacher', 'room']);

        if ($request->filled('section_id')) {
            $query->where('section_id', $request->integer('section_id'));
        }

        if ($request->filled('teacher_id')) {
            $query->where('teacher_id', $request->integer('teacher_id'));
        }

        $entries = $query->orderBy('day_of_week')->orderBy('start_time')->get();

        $pdf = Pdf::loadView('timetable.pdf', [
            'entries' => $entries,
            'days' => self::DAYS,
            'schoolName' => session('school_name') ?? config('app.name'),
        ]);

        return $pdf->download('timetable.pdf');
    }

    /**
     * Calendar feed of the timetable for the current filter.
     */
    public function exportIcs(Request $request): Response
    {
        $schoolId = $this->schoolId();

        $query = TimetableEntry::where('school_id', $schoolId)->with(['offering.subject', 'section']);

        if ($request->filled('section_id')) {
            $query->where('section_id', $request->integer('section_id'));
        }

        if ($request->filled('teacher_id')) {
            $query->where('teacher_id', $request->integer('teacher_id'));
        }

        $entries = $query->get();

        // Recurring weekly events anchored on the current week's matching weekday.
        $weekStart = Carbon::now()->startOfWeek(Carbon::SUNDAY);

        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//Aether School OS//Timetable//EN',
            'CALSCALE:GREGORIAN',
        ];

        foreach ($entries as $entry) {
            $offset = array_search($entry->day_of_week, self::DAYS, true);
            $day = $weekStart->copy()->addDays($offset === false ? 0 : $offset);
            $start = Carbon::parse($day->toDateString().' '.$entry->startsAt());
            $end = Carbon::parse($day->toDateString().' '.$entry->endsAt());

            $summary = trim(($entry->offering?->subject?->name ?? 'Session').' · '.($entry->section?->name ?? ''));

            $lines = [
                ...$lines,
                'BEGIN:VEVENT',
                'UID:timetable-'.$entry->id.'@aether',
                'DTSTAMP:'.now()->utc()->format('Ymd\THis\Z'),
                'DTSTART:'.$start->format('Ymd\THis'),
                'DTEND:'.$end->format('Ymd\THis'),
                'RRULE:FREQ=WEEKLY',
                'SUMMARY:'.$this->escape($summary),
                'END:VEVENT',
            ];
        }

        $lines[] = 'END:VCALENDAR';

        return response(implode("\r\n", $lines), 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="timetable.ics"',
        ]);
    }

    private function filteredIndex(array $filters): InertiaResponse
    {
        $query = TimetableEntry::where('school_id', $this->schoolId())
            ->with(['offering.subject', 'section', 'teacher', 'room']);

        foreach ($filters as $column => $value) {
            $query->where($column, $value);
        }

        return inertia('timetable/index', [
            'timetables' => $query->latest()->paginate(15),
            'filters' => $filters,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function formOptions(): array
    {
        $schoolId = $this->schoolId();

        return [
            'sections' => Section::where('school_id', $schoolId)->orderBy('name_en')->get(['id', 'name_en'])
                ->map(fn (Section $section) => ['id' => $section->id, 'name' => $section->name])->all(),
            'subjects' => Subject::where('school_id', $schoolId)->orderBy('name_en')->get(['id', 'name_en'])
                ->map(fn (Subject $subject) => ['id' => $subject->id, 'name' => $subject->name])->all(),
            'teachers' => TeacherProfile::where('school_id', $schoolId)->orderBy('first_name')
                ->get(['id', 'first_name', 'last_name'])
                ->map(fn (TeacherProfile $teacher) => [
                    'id' => $teacher->id,
                    'name' => trim($teacher->first_name.' '.$teacher->last_name),
                ])->all(),
            'rooms' => Room::where('school_id', $schoolId)->orderBy('name_en')->get(['id', 'name_en'])
                ->map(fn (Room $room) => ['id' => $room->id, 'name' => $room->name])->all(),
            'periods' => Period::where('school_id', $schoolId)->orderBy('sort_order')->get()
                ->map(fn (Period $period) => [
                    'id' => $period->id,
                    'name' => $period->name,
                    'start_time' => $period->startsAt(),
                    'end_time' => $period->endsAt(),
                ])->all(),
            'semesters' => Semester::where('school_id', $schoolId)->orderBy('id')->get(['id', 'name_en'])
                ->map(fn (Semester $semester) => ['id' => $semester->id, 'name' => $semester->name])->all(),
            'days' => self::DAYS,
        ];
    }

    /**
     * @param  Collection<int, TimetableEntry>  $entries
     * @return list<array<string, mixed>>
     */
    private function derivedRows(Collection $entries): array
    {
        return $entries
            ->map(fn (TimetableEntry $entry) => [
                'id' => null,
                'label' => $entry->startsAt().' - '.$entry->endsAt(),
                'start' => $entry->startsAt(),
                'end' => $entry->endsAt(),
                'is_break' => false,
            ])
            ->unique('label')
            ->sortBy('start')
            ->values()
            ->all();
    }

    /**
     * Place each entry into the (day, row) cell whose time window contains it.
     *
     * @param  Collection<int, TimetableEntry>  $entries
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<string, array<int, list<array<string, mixed>>>>
     */
    private function matrix(Collection $entries, array $rows): array
    {
        $matrix = [];

        foreach (self::DAYS as $day) {
            $matrix[$day] = [];

            foreach ($rows as $index => $row) {
                $matrix[$day][$index] = [];
            }
        }

        $rowMinutes = array_map(
            fn (array $row) => [
                'start' => $this->conflicts->minutes((string) $row['start']) ?? 0,
                'end' => $this->conflicts->minutes((string) $row['end']) ?? 0,
            ],
            $rows,
        );

        foreach ($entries as $entry) {
            if (! isset($matrix[$entry->day_of_week])) {
                continue;
            }

            $entryStart = $this->conflicts->minutes((string) $entry->start_time) ?? 0;
            $rowIndex = $this->rowFor($entryStart, $rowMinutes, $rows);

            if ($rowIndex === null) {
                continue;
            }

            $matrix[$entry->day_of_week][$rowIndex][] = [
                'id' => $entry->id,
                'subject' => $entry->offering?->subject?->name,
                'section' => $entry->section?->name,
                'teacher' => trim(($entry->teacher?->first_name ?? '').' '.($entry->teacher?->last_name ?? '')),
                'room' => $entry->room?->name,
                'start' => $entry->startsAt(),
                'end' => $entry->endsAt(),
                'is_published' => (bool) $entry->is_published,
            ];
        }

        return $matrix;
    }

    /**
     * @param  array<int, array{start: int, end: int}>  $rowMinutes
     * @param  array<int, array<string, mixed>>  $rows
     */
    private function rowFor(int $entryStart, array $rowMinutes, array $rows): ?int
    {
        foreach ($rowMinutes as $index => $window) {
            if ($entryStart >= $window['start'] && $entryStart < $window['end']) {
                return $index;
            }
        }

        // Fall back to the closest preceding row so entries outside the bell
        // schedule still appear rather than silently vanishing from the grid.
        $fallback = null;

        foreach ($rowMinutes as $index => $window) {
            if ($window['start'] <= $entryStart) {
                $fallback = $index;
            }
        }

        return $fallback ?? ($rows === [] ? null : 0);
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array{school_id: int, day_of_week: string, start_time: string, end_time: string, teacher_id: int, room_id: int|null, section_id: int}
     */
    private function candidate(int $schoolId, array $validated, ?int $roomId): array
    {
        return [
            'school_id' => $schoolId,
            'day_of_week' => $validated['day_of_week'],
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'teacher_id' => (int) $validated['teacher_id'],
            'room_id' => $roomId,
            'section_id' => (int) $validated['section_id'],
        ];
    }

    /**
     * @param  array{school_id: int, day_of_week: string, start_time: string, end_time: string, teacher_id: int, room_id: int|null, section_id: int}  $candidate
     */
    private function assertNoConflicts(array $candidate, ?int $ignoreEntryId = null): void
    {
        $conflicts = $this->conflicts->detect($candidate, $ignoreEntryId);

        if ($conflicts === []) {
            return;
        }

        $first = $conflicts[0];
        $who = implode(', ', $first['reasons']);

        throw ValidationException::withMessages([
            'start_time' => "This clashes with an existing entry for the same {$who} ({$first['label']}, {$first['time']}).",
        ]);
    }

    private function authorizeSchool(TimetableEntry $entry): void
    {
        if ((int) $entry->school_id !== $this->schoolId()) {
            abort(403);
        }
    }

    private function schoolId(): int
    {
        $schoolId = (int) session('school_id');

        if ($schoolId === 0) {
            abort(403, 'No active school context.');
        }

        return $schoolId;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function rules(int $schoolId): array
    {
        return [
            'subject_id' => ['required', Rule::exists('subjects', 'id')->where('school_id', $schoolId)],
            'section_id' => ['required', Rule::exists('sections', 'id')->where('school_id', $schoolId)],
            'teacher_id' => ['required', Rule::exists('teacher_profiles', 'id')->where('school_id', $schoolId)],
            'day_of_week' => ['required', Rule::in([...self::DAYS, 'friday', 'saturday'])],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'room' => ['nullable', 'string', 'max:50'],
            'period_id' => ['nullable', Rule::exists('periods', 'id')->where('school_id', $schoolId)],
            'semester_id' => ['nullable', Rule::exists('semesters', 'id')->where('school_id', $schoolId)],
        ];
    }

    private function resolveOffering(int $subjectId, int $sectionId, int $teacherId): Offering
    {
        $offering = Offering::where('school_id', $this->schoolId())
            ->where('subject_id', $subjectId)
            ->where('section_id', $sectionId)
            ->where('teacher_id', $teacherId)
            ->first()
            ?? Offering::where('school_id', $this->schoolId())
                ->where('subject_id', $subjectId)
                ->where('section_id', $sectionId)
                ->first();

        if (! $offering) {
            throw ValidationException::withMessages([
                'subject_id' => 'No active offering exists for this subject, section and teacher.',
            ]);
        }

        return $offering;
    }

    private function resolveRoomId(?string $roomName): ?int
    {
        if (! $roomName) {
            return null;
        }

        // `rooms.name` was replaced by the bilingual name_en/name_ar pair, so
        // match on either spelling before creating a new room.
        $room = Room::where('school_id', $this->schoolId())
            ->where(function ($query) use ($roomName): void {
                $query->where('name_en', $roomName)->orWhere('name_ar', $roomName);
            })
            ->first();

        if (! $room) {
            $room = Room::create([
                'school_id' => $this->schoolId(),
                'name_en' => $roomName,
                'name_ar' => $roomName,
                'code' => strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $roomName) ?? '', 0, 6)).'-'.random_int(100, 999),
                'room_type' => 'classroom',
                'capacity' => 30,
            ]);
        }

        return $room->id;
    }

    /**
     * Semester for an academic year, or null when the school has not defined
     * one yet. Returning 0 here would violate the semester_id foreign key.
     */
    private function currentSemesterId(int $academicYearId): ?int
    {
        $semesterId = Semester::where('school_id', $this->schoolId())
            ->where('academic_year_id', $academicYearId)
            ->orderBy('id')
            ->value('id');

        return $semesterId === null ? null : (int) $semesterId;
    }

    /** First day of the month containing the requested date (defaults to today). */
    private function anchorMonth(string $date): Carbon
    {
        try {
            return $date !== '' ? Carbon::parse($date)->startOfMonth() : Carbon::today()->startOfMonth();
        } catch (\Throwable) {
            return Carbon::today()->startOfMonth();
        }
    }

    /** The school teaches Sunday–Thursday. */
    private function isSchoolDay(string $weekday): bool
    {
        return in_array($weekday, self::DAYS, true);
    }

    /**
     * Expand every non-instructional calendar entry in the window into a
     * date => title map so multi-day holidays suppress lessons too.
     *
     * @return array<string, string>
     */
    private function nonInstructionalDays(int $schoolId, Carbon $start, Carbon $end): array
    {
        $days = CalendarDay::where('school_id', $schoolId)
            ->where('is_instructional', false)
            ->whereDate('date', '<=', $end)
            ->where(function ($query) use ($start): void {
                $query->whereDate('end_date', '>=', $start)->orWhereNull('end_date');
            })
            ->get(['date', 'end_date', 'title']);

        $map = [];

        foreach ($days as $day) {
            $cursor = $day->date->copy();
            $last = $day->end_date?->copy() ?? $day->date->copy();

            // Guard against mis-authored ranges that never terminate.
            $guard = 0;
            while ($cursor->lessThanOrEqualTo($last) && $guard < 366) {
                $map[$cursor->toDateString()] = (string) $day->title;
                $cursor->addDay();
                $guard++;
            }
        }

        return $map;
    }

    private function escape(string $value): string
    {
        return str_replace([',', ';', "\n"], ['\\,', '\\;', '\\n'], $value);
    }
}
