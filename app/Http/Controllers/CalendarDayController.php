<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Scheduling\Enums\CalendarDayType;
use App\Domain\Scheduling\Models\CalendarDay;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class CalendarDayController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', CalendarDay::class);

        $validated = $request->validate($this->rules());

        CalendarDay::create([
            ...$validated,
            'school_id' => (int) session('school_id'),
            'created_by' => $request->user()?->id,
        ]);

        return back()->with('success', 'Calendar entry added.');
    }

    public function update(Request $request, CalendarDay $calendarDay): RedirectResponse
    {
        $this->authorize('update', $calendarDay);

        $calendarDay->update($request->validate($this->rules()));

        return back()->with('success', 'Calendar entry updated.');
    }

    public function destroy(CalendarDay $calendarDay): RedirectResponse
    {
        $this->authorize('delete', $calendarDay);

        $calendarDay->delete();

        return back()->with('success', 'Calendar entry removed.');
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function rules(): array
    {
        $schoolId = (int) session('school_id');

        return [
            'title' => ['required', 'string', 'max:150'],
            'type' => ['required', new Enum(CalendarDayType::class)],
            'date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:date'],
            'description' => ['nullable', 'string'],
            'is_instructional' => ['boolean'],
            'academic_year_id' => ['nullable', Rule::exists('academic_years', 'id')->where('school_id', $schoolId)],
            'semester_id' => ['nullable', Rule::exists('semesters', 'id')->where('school_id', $schoolId)],
        ];
    }
}
