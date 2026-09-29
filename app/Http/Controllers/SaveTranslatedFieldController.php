<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Localization\Support\BilingualTargets;
use App\Domain\Schools\Models\School;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SaveTranslatedFieldController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $schoolId = (int) session('school_id');
        abort_if($schoolId === 0, 403, 'No school context is available for this request.');

        $targets = collect(BilingualTargets::all())
            ->filter(static fn (array $target): bool => isset($target['scope'])
                && ! isset($target['scope_relation'])
                && ! in_array($target['scope'], ['global'], true))
            ->keyBy(static fn (array $target): string => (new $target['model'])->getTable());

        $validated = $request->validate([
            'table' => ['required', 'string', Rule::in($targets->keys()->all())],
            'id' => ['required', 'integer', 'min:1'],
            'source_column' => ['required', 'string', 'max:100'],
            'source_value' => ['required', 'string', 'max:5000'],
            'column' => ['required', 'string', 'max:100'],
            'value' => ['required', 'string', 'max:5000'],
        ]);

        $target = $targets->get($validated['table']);
        $matchingPair = collect($target['pairs'])->first(static fn (array $pair): bool => ($pair['en'] === $validated['source_column'] && $pair['ar'] === $validated['column'])
            || ($pair['ar'] === $validated['source_column'] && $pair['en'] === $validated['column'])
        );

        if ($matchingPair === null) {
            throw ValidationException::withMessages([
                'column' => 'The selected column is not a translatable field.',
            ]);
        }

        $scopeColumn = (string) $target['scope'];
        $scopeValue = match ($target['scope_value'] ?? 'school') {
            'organization' => School::query()->whereKey($schoolId)->value('organization_id'),
            default => $scopeColumn === 'id' ? $schoolId : $schoolId,
        };

        abort_if($scopeValue === null, 404);

        $model = $target['model'];
        $record = $model::query()
            ->whereKey($validated['id'])
            ->where($scopeColumn, $scopeValue)
            ->firstOrFail();

        if ($validated['table'] === 'schools') {
            $permissions = $request->user()->getAllPermissions()->pluck('name');
            abort_unless($permissions->contains('manage-settings') || $permissions->contains('manage-schools'), 403);
        }

        if ($validated['table'] !== 'schools' && Gate::getPolicyFor($record) !== null) {
            Gate::authorize('update', $record);
        }

        $record->setAttribute($validated['source_column'], $validated['source_value']);
        $record->setAttribute($validated['column'], $validated['value']);
        $record->save();

        return response()->json(['saved' => true, 'value' => $record->getAttribute($validated['column'])]);
    }
}
