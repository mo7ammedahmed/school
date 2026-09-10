<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Announcement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response;

class AnnouncementController extends Controller
{
    public function index(): Response
    {
        $announcements = Announcement::latest()->paginate(15);

        return inertia('announcements/index', ['announcements' => $announcements]);
    }

    public function create(): Response
    {
        return inertia('announcements/create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'target_audience' => 'required|in:all,students,teachers,parents,staff',
            'publish_date' => 'required|date',
            'expiry_date' => 'required|date|after_or_equal:publish_date',
            'is_active' => 'required|boolean',
        ]);

        $validated['is_active'] = $request->has('is_active');

        $announcement = Announcement::create($validated);

        return redirect()->route('announcements.show', $announcement)->with('success', 'Announcement created successfully.');
    }

    public function show(Announcement $announcement): Response
    {
        return inertia('announcements/show', ['announcement' => $announcement]);
    }

    public function edit(Announcement $announcement): Response
    {
        return inertia('announcements/edit', ['announcement' => $announcement]);
    }

    public function update(Request $request, Announcement $announcement): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'target_audience' => 'required|in:all,students,teachers,parents,staff',
            'publish_date' => 'required|date',
            'expiry_date' => 'required|date|after_or_equal:publish_date',
            'is_active' => 'required|boolean',
        ]);

        $validated['is_active'] = $request->has('is_active');

        $announcement->update($validated);

        return redirect()->route('announcements.show', $announcement)->with('success', 'Announcement updated successfully.');
    }

    public function destroy(Announcement $announcement): RedirectResponse
    {
        $announcement->delete();

        return redirect()->route('announcements.index')->with('success', 'Announcement deleted successfully.');
    }
}
