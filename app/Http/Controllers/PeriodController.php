<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Scheduling\Models\Period;
use App\Http\Controllers\Concerns\HandlesBilingualInput;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response;

class PeriodController extends Controller
{
    use HandlesBilingualInput;

    public function index(): Response
    {
        $periods = Period::where('school_id', $this->schoolId())
            ->orderBy('sort_order')
            ->orderBy('start_time')
            ->get()
            ->map(fn (Period $period) => $this->toRow($period))
            ->all();

        return inertia('periods/index', ['periods' => $periods]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Period::class);

        $validated = $this->translateBilingual($request->validate($this->rules()));

        Period::create([
            ...$validated,
            'school_id' => $this->schoolId(),
        ]);

        return back()->with('success', 'Period created.');
    }

    public function update(Request $request, Period $period): RedirectResponse
    {
        $this->authorize('update', $period);

        $period->update($this->translateBilingual($request->validate($this->rules())));

        return back()->with('success', 'Period updated.');
    }

    public function destroy(Period $period): RedirectResponse
    {
        $this->authorize('delete', $period);

        $period->delete();

        return back()->with('success', 'Period deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function toRow(Period $period): array
    {
        return [
            'id' => $period->id,
            'name' => $period->name,
            'name_en' => $period->name_en,
            'name_ar' => $period->name_ar,
            'code' => $period->code,
            'start_time' => $period->startsAt(),
            'end_time' => $period->endsAt(),
            'sort_order' => $period->sort_order,
            'is_break' => $period->is_break,
        ];
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function rules(): array
    {
        return [
            'name_en' => ['nullable', 'string', 'max:100', 'required_without:name_ar'],
            'name_ar' => ['nullable', 'string', 'max:100', 'required_without:name_en'],
            'code' => ['nullable', 'string', 'max:20'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_break' => ['boolean'],
        ];
    }

    private function schoolId(): int
    {
        $schoolId = (int) session('school_id');

        if ($schoolId === 0) {
            abort(403, 'No active school context.');
        }

        return $schoolId;
    }
}
