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
            'name_ar' => ['nullable', 'string', 'max:255', 'required_without:name_en'],
            'name_en' => ['nullable', 'string', 'max:255', 'required_without:name_ar'],
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
        $this->authorize('view', $room);

        return inertia('rooms/show', ['room' => $room]);
    }

    public function edit(Room $room): Response
    {
        $this->authorize('update', $room);

        return inertia('rooms/edit', ['room' => $room]);
    }

    public function update(Request $request, Room $room): RedirectResponse
    {
        $this->authorize('update', $room);

        $validated = $request->validate([
            'name_ar' => ['nullable', 'string', 'max:255', 'required_without:name_en'],
            'name_en' => ['nullable', 'string', 'max:255', 'required_without:name_ar'],
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
        $this->authorize('delete', $room);

        $room->delete();

        return redirect()->route('rooms.index')->with('success', 'Room deleted successfully.');
    }
}
