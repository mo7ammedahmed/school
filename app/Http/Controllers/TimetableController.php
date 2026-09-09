<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Inertia\Response;
use App\Domain\Scheduling\Models\TimetableEntry;
use App\Domain\Scheduling\Models\Room;
use App\Domain\Academics\Models\Offering;
use App\Domain\Academics\Models\Section;
use App\Domain\Academics\Models\Semester;
use App\Domain\Academics\Models\Subject;
use App\Domain\People\Models\TeacherProfile;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;

class TimetableController extends Controller
{
    public function index(): Response
    {
        $schoolId = session('school_id');
        $timetables = TimetableEntry::where('school_id', $schoolId)
            ->with(['offering.subject', 'section', 'teacher', 'room'])
            ->latest()
            ->paginate(15);

        return inertia('timetable/index', ['timetables' => $timetables]);
    }

    public function create(): Response
    {
        $schoolId = session('school_id');
        $sections = Section::where('school_id', $schoolId)->orderBy('name_en')->get();
        $subjects = Subject::where('school_id', $schoolId)->orderBy('name_en')->get();
        $teachers = TeacherProfile::where('school_id', $schoolId)->orderBy('first_name')->get();

        return inertia('timetable/create', ['sections' => $sections, 'subjects' => $subjects, 'teachers' => $teachers]);
    }

    public function store(Request $request): RedirectResponse
    {
        $schoolId = session('school_id');
        $validated = $request->validate([
            'subject_id' => 'required|exists:subjects,id',
            'section_id' => 'required|exists:sections,id',
            'teacher_id' => 'required|exists:teacher_profiles,id',
            'day_of_week' => 'required|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'start_time' => 'required',
            'end_time' => 'required|after:start_time',
            'room' => 'nullable|string|max:50',
            'semester_id' => 'nullable|exists:semesters,id',
        ]);

        $offering = $this->resolveOffering((int) $validated['subject_id'], (int) $validated['section_id'], (int) $validated['teacher_id']);

        $entry = TimetableEntry::create([
            'school_id' => $schoolId,
            'academic_year_id' => $offering->academic_year_id,
            'semester_id' => $validated['semester_id'] ?? $this->currentSemesterId($offering->academic_year_id),
            'offering_id' => $offering->id,
            'room_id' => $this->resolveRoomId($validated['room'] ?? null),
            'teacher_id' => $validated['teacher_id'],
            'section_id' => $validated['section_id'],
            'day_of_week' => $validated['day_of_week'],
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'is_published' => true,
        ]);

        return redirect()->route('timetable.show', $entry)->with('success', 'Schedule created successfully');
    }

    public function show(TimetableEntry $timetable): Response
    {
        $this->authorizeSchool($timetable);
        $timetable->load(['offering.subject', 'section', 'teacher', 'room']);

        return inertia('timetable/show', ['schedule' => $timetable]);
    }

    public function edit(TimetableEntry $timetable): Response
    {
        $this->authorizeSchool($timetable);
        $schoolId = session('school_id');
        $sections = Section::where('school_id', $schoolId)->orderBy('name_en')->get();
        $subjects = Subject::where('school_id', $schoolId)->orderBy('name_en')->get();
        $teachers = TeacherProfile::where('school_id', $schoolId)->orderBy('first_name')->get();

        return inertia('timetable/edit', [
            'schedule' => $timetable->load(['offering.subject', 'section', 'teacher', 'room']),
            'sections' => $sections,
            'subjects' => $subjects,
            'teachers' => $teachers,
        ]);
    }

    public function update(Request $request, TimetableEntry $timetable): RedirectResponse
    {
        $this->authorizeSchool($timetable);
        $validated = $request->validate([
            'subject_id' => 'required|exists:subjects,id',
            'section_id' => 'required|exists:sections,id',
            'teacher_id' => 'required|exists:teacher_profiles,id',
            'day_of_week' => 'required|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'start_time' => 'required',
            'end_time' => 'required|after:start_time',
            'room' => 'nullable|string|max:50',
            'semester_id' => 'nullable|exists:semesters,id',
        ]);

        $offering = $this->resolveOffering((int) $validated['subject_id'], (int) $validated['section_id'], (int) $validated['teacher_id']);

        $timetable->update([
            'academic_year_id' => $offering->academic_year_id,
            'semester_id' => $validated['semester_id'] ?? $this->currentSemesterId($offering->academic_year_id),
            'offering_id' => $offering->id,
            'room_id' => $this->resolveRoomId($validated['room'] ?? null),
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

    public function teacher(int $teacher): Response
    {
        return $this->filteredIndex(['teacher_id' => $teacher]);
    }

    public function section(int $section): Response
    {
        return $this->filteredIndex(['section_id' => $section]);
    }

    public function mySchedule(): Response
    {
        $profile = TeacherProfile::where('school_id', session('school_id'))
            ->where('user_id', auth()->id())
            ->first();

        return $profile
            ? $this->filteredIndex(['teacher_id' => $profile->id])
            : $this->index();
    }

    private function filteredIndex(array $filters): Response
    {
        $query = TimetableEntry::where('school_id', session('school_id'))
            ->with(['offering.subject', 'section', 'teacher', 'room']);

        foreach ($filters as $column => $value) {
            $query->where($column, $value);
        }

        return inertia('timetable/index', ['timetables' => $query->latest()->paginate(15)]);
    }

    private function authorizeSchool(TimetableEntry $entry): void
    {
        if ((int) $entry->school_id !== (int) session('school_id')) {
            abort(403);
        }
    }

    private function resolveOffering(int $subjectId, int $sectionId, int $teacherId): Offering
    {
        $offering = Offering::where('school_id', session('school_id'))
            ->where('subject_id', $subjectId)
            ->where('section_id', $sectionId)
            ->where('teacher_id', $teacherId)
            ->first()
            ?? Offering::where('school_id', session('school_id'))
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

        $room = Room::where('school_id', session('school_id'))->where('name', $roomName)->first();

        if (! $room) {
            $room = Room::create([
                'school_id' => session('school_id'),
                'name' => $roomName,
                'code' => strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $roomName), 0, 6)) . '-' . random_int(100, 999),
                'room_type' => 'classroom',
                'capacity' => 30,
            ]);
        }

        return $room->id;
    }

    private function currentSemesterId(int $academicYearId): int
    {
        return (int) Semester::where('school_id', session('school_id'))
            ->where('academic_year_id', $academicYearId)
            ->orderBy('id')
            ->value('id');
    }
}
