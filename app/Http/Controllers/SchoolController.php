<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Identity\Models\UserMembership;
use App\Domain\Schools\Models\Organization;
use App\Domain\Schools\Models\School;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Manage the schools and branches that live under an organization.
 *
 * "Branch" is not a separate table: a branch is simply another school row
 * inside the same organization, which is how tenancy is modelled here.
 */
class SchoolController extends Controller
{
    public function index(): Response
    {
        $schools = $this->scopedSchools()
            ->with('organization:id,name')
            ->withCount(['students', 'teachers'])
            ->orderBy('name_en')
            ->get()
            ->map(fn (School $school) => [
                'id' => $school->id,
                'name' => $school->name,
                'name_ar' => $school->name_ar,
                'name_en' => $school->name_en,
                'slug' => $school->slug,
                'city' => $school->city,
                'country' => $school->country,
                'locale' => $school->locale,
                'currency' => $school->currency,
                'primary_color' => $school->primary_color,
                'organization' => $school->organization?->only(['id', 'name']),
                'students_count' => $school->students_count,
                'teachers_count' => $school->teachers_count,
                'is_active' => (int) session('school_id') === $school->id,
            ]);

        return Inertia::render('schools/index', [
            'schools' => $schools,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('schools/form', [
            'school' => null,
            'organizations' => $this->organizations(),
        ]);
    }

    /**
     * Schools the current user is allowed to see.
     *
     * Super admins see the whole platform; everyone else is limited to the
     * organizations their memberships belong to, so one org can never browse
     * another org's schools.
     */
    /**
     * @return Builder<School>
     */
    private function scopedSchools(): Builder
    {
        $query = School::query();

        if (auth()->user()?->hasRole('super_admin')) {
            return $query;
        }

        $organizationIds = $this->organizationIds();

        return $query->whereIn('organization_id', $organizationIds ?: [-1]);
    }

    /**
     * @return array<int, int>
     */
    private function organizationIds(): array
    {
        return auth()->user()
            ?->memberships()
            ->where('is_active', true)
            ->with('school:id,organization_id')
            ->get()
            ->pluck('school.organization_id')
            ->filter()
            ->unique()
            ->values()
            ->all() ?? [];
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->rules());
        $this->assertOrganizationAllowed((int) $validated['organization_id']);

        $school = School::create([
            ...$validated,
            'slug' => $this->uniqueSlug($validated['slug'] ?? null, $validated['name_en']),
            'country' => $validated['country'] ?? 'SA',
            'timezone' => $validated['timezone'] ?? 'Asia/Riyadh',
            'locale' => $validated['locale'] ?? 'en',
            'currency' => $validated['currency'] ?? 'SAR',
        ]);

        // Give the creator access to the school they just created so it can be
        // entered straight away from the school switcher.
        $user = $request->user();
        if ($user && ! $user->hasRole('super_admin')) {
            UserMembership::firstOrCreate(
                ['user_id' => $user->id, 'school_id' => $school->id],
                ['role' => 'school_admin', 'is_active' => true],
            );
        }

        return redirect()
            ->route('schools.index')
            ->with('success', "{$school->name} was created.");
    }

    public function show(School $school): Response
    {
        $this->assertOrganizationAllowed((int) $school->organization_id);

        return Inertia::render('schools/form', [
            'school' => $this->payload($school),
            'organizations' => $this->organizations(),
        ]);
    }

    public function edit(School $school): Response
    {
        $this->assertOrganizationAllowed((int) $school->organization_id);

        return Inertia::render('schools/form', [
            'school' => $this->payload($school),
            'organizations' => $this->organizations(),
        ]);
    }

    public function update(Request $request, School $school): RedirectResponse
    {
        $this->assertOrganizationAllowed((int) $school->organization_id);

        $validated = $request->validate($this->rules());
        $this->assertOrganizationAllowed((int) $validated['organization_id']);

        $school->update([
            ...$validated,
            'slug' => $this->uniqueSlug($validated['slug'] ?? null, $validated['name_en'], $school->id),
        ]);

        return redirect()
            ->route('schools.index')
            ->with('success', "{$school->name} was updated.");
    }

    public function destroy(School $school): RedirectResponse
    {
        $this->assertOrganizationAllowed((int) $school->organization_id);

        if ((int) session('school_id') === $school->id) {
            return back()->with('error', 'You cannot delete the school you are currently working in.');
        }

        $name = $school->name;
        $school->delete();

        return redirect()
            ->route('schools.index')
            ->with('success', "{$name} was deleted.");
    }

    /** Reject attempts to attach a school to an organization the user cannot see. */
    private function assertOrganizationAllowed(int $organizationId): void
    {
        if (auth()->user()?->hasRole('super_admin')) {
            return;
        }

        abort_unless(in_array($organizationId, $this->organizationIds(), true), 403);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function rules(): array
    {
        return [
            'organization_id' => ['required', 'integer', 'exists:organizations,id'],
            'name_en' => ['required', 'string', 'max:255'],
            'name_ar' => ['nullable', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:500'],
            'city' => ['nullable', 'string', 'max:120'],
            'country' => ['nullable', 'string', 'size:2'],
            'timezone' => ['nullable', 'string', 'max:64'],
            'locale' => ['nullable', 'in:en,ar'],
            'currency' => ['nullable', 'string', 'size:3'],
            'primary_color' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'secondary_color' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'accent_color' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            // `is_branch` used to be validated here. There is no column, no model
            // attribute and no control anywhere that sets it, so it only ever
            // produced a payload key that nothing read: a school's place in the
            // hierarchy is `organization_id`.
        ];
    }

    private function uniqueSlug(?string $slug, string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($slug ?: $name) ?: 'school';
        $candidate = $base;
        $suffix = 2;

        while (
            School::withTrashed()
                ->where('slug', $candidate)
                ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
                ->exists()
        ) {
            $candidate = "{$base}-{$suffix}";
            $suffix++;
        }

        return $candidate;
    }

    /**
     * @return array<int, array{id: int, name: string}>
     */
    private function organizations(): array
    {
        $query = Organization::query()->orderBy('name');

        if (! auth()->user()?->hasRole('super_admin')) {
            $query->whereIn('id', $this->organizationIds() ?: [-1]);
        }

        return $query
            ->get(['id', 'name'])
            ->map(fn (Organization $organization) => [
                'id' => $organization->id,
                'name' => $organization->name,
            ])
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(School $school): array
    {
        return [
            'id' => $school->id,
            'organization_id' => $school->organization_id,
            'name' => $school->name,
            'name_en' => $school->name_en,
            'name_ar' => $school->name_ar,
            'slug' => $school->slug,
            'email' => $school->email,
            'phone' => $school->phone,
            'address' => $school->address,
            'city' => $school->city,
            'country' => $school->country,
            'timezone' => $school->timezone,
            'locale' => $school->locale,
            'currency' => $school->currency,
            'primary_color' => $school->primary_color,
            'secondary_color' => $school->secondary_color,
            'accent_color' => $school->accent_color,
        ];
    }
}
