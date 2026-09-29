<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Announcement;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response;

class AnnouncementController extends Controller
{
    public function index(): Response
    {
        $announcements = $this->forSchool()->latest()->paginate(15);

        return inertia('announcements/index', ['announcements' => $announcements]);
    }

    public function create(): Response
    {
        return inertia('announcements/create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validated($request);

        // The row belongs to the school the operator is working in. Without this
        // the insert failed outright: `announcements.school_id` is not nullable.
        $validated['school_id'] = $this->schoolId();

        $announcement = Announcement::create($validated);

        return redirect()->route('announcements.show', $announcement)->with('success', 'Announcement created successfully.');
    }

    public function show(Announcement $announcement): Response
    {
        $this->ensureOwned($announcement, 404);

        return inertia('announcements/show', ['announcement' => $announcement]);
    }

    public function edit(Announcement $announcement): Response
    {
        $this->ensureOwned($announcement, 404);

        return inertia('announcements/edit', ['announcement' => $announcement]);
    }

    public function update(Request $request, Announcement $announcement): RedirectResponse
    {
        $this->ensureOwned($announcement, 404);

        $announcement->update($this->validated($request));

        return redirect()->route('announcements.show', $announcement)->with('success', 'Announcement updated successfully.');
    }

    public function destroy(Announcement $announcement): RedirectResponse
    {
        $this->ensureOwned($announcement, 404);

        $announcement->delete();

        return redirect()->route('announcements.index')->with('success', 'Announcement deleted successfully.');
    }

    /**
     * The names here previously described a schema that does not exist
     * (`content`, `publish_date`, `expiry_date`, `is_active`), so the model's
     * fillable list dropped the body, the two dates and the published flag: an
     * announcement could be created with a title and nothing else, and the
     * screens that read those fields showed blanks.
     *
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $validated = $request->validate([
            // Either language satisfies the pair; the empty side is translated.
            'title' => ['nullable', 'string', 'max:255', 'required_without:title_ar'],
            'title_ar' => ['nullable', 'string', 'max:255', 'required_without:title'],
            'body' => ['nullable', 'string', 'required_without:body_ar'],
            'body_ar' => ['nullable', 'string', 'required_without:body'],
            'target_audience' => 'required|in:all,students,teachers,parents,staff',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'is_published' => 'required|boolean',
        ]);

        $validated['is_published'] = (bool) $validated['is_published'];

        return $validated;
    }

    /**
     * @return Builder<Announcement>
     */
    private function forSchool(): Builder
    {
        return Announcement::where('school_id', $this->schoolId());
    }
}
