<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Room;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response;

class RoomController extends Controller
{
    public function index(): Response
    {
        $schoolId = session('school_id');
        $rooms = Room::where('school_id', $schoolId)->latest()->paginate(15);

        return inertia('rooms/index', ['rooms' => $rooms]);
    }

    public function create(): Response
    {
        return inertia('rooms/create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name_ar' => 'required|string|max:255',
            'name_en' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:rooms,code,NULL,id,school_id,'.session('school_id'),
            'room_type' => 'required|in:classroom,laboratory,library,gymnasium,auditorium,office,other',
            'capacity' => 'required|integer|min:1',
            'description' => 'nullable|string',
        ]);

        $validated['school_id'] = session('school_id');

        $room = Room::create($validated);

        return redirect()->route('rooms.show', $room)->with('success', 'Room created successfully.');
    }

    public function show(Room $room): Response
    {
        if ($room->school_id !== session('school_id')) {
            abort(403);
        }

        return inertia('rooms/show', ['room' => $room]);
    }

    public function edit(Room $room): Response
    {
        if ($room->school_id !== session('school_id')) {
            abort(403);
        }

        return inertia('rooms/edit', ['room' => $room]);
    }

    public function update(Request $request, Room $room): RedirectResponse
    {
        if ($room->school_id !== session('school_id')) {
            abort(403);
        }

        $validated = $request->validate([
            'name_ar' => 'required|string|max:255',
            'name_en' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:rooms,code,'.$room->id.',school_id,'.session('school_id'),
            'room_type' => 'required|in:classroom,laboratory,library,gymnasium,auditorium,office,other',
            'capacity' => 'required|integer|min:1',
            'description' => 'nullable|string',
        ]);

        $room->update($validated);

        return redirect()->route('rooms.show', $room)->with('success', 'Room updated successfully.');
    }

    public function destroy(Room $room): RedirectResponse
    {
        if ($room->school_id !== session('school_id')) {
            abort(403);
        }

        $room->delete();

        return redirect()->route('rooms.index')->with('success', 'Room deleted successfully.');
    }
}
