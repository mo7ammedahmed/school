<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Guardian;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response;

class GuardianController extends Controller
{
    public function index(): Response
    {
        $guardians = Guardian::where('school_id', $this->schoolId())
            ->latest()
            ->paginate(15);

        return inertia('guardians/index', ['guardians' => $guardians]);
    }

    public function create(): Response
    {
        return inertia('guardians/create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',

            'email' => 'required|email|unique:guardians,email',
            'phone' => 'required|string|max:20',
            'relationship' => 'required|in:father,mother,guardian,other',
            'occupation' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'emergency_contact' => 'nullable|string|max:20',
        ]);

        $validated['school_id'] = $this->schoolId();

        $guardian = Guardian::create($validated);

        return redirect()->route('guardians.show', $guardian)->with('success', 'Guardian added successfully.');
    }

    public function show(Guardian $guardian): Response
    {
        return inertia('guardians/show', ['guardian' => $guardian]);
    }

    public function edit(Guardian $guardian): Response
    {
        return inertia('guardians/edit', ['guardian' => $guardian]);
    }

    public function update(Request $request, Guardian $guardian): RedirectResponse
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:guardians,email,'.$guardian->id,
            'phone' => 'required|string|max:20',
            'relationship' => 'required|in:father,mother,guardian,other',
            'occupation' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'emergency_contact' => 'nullable|string|max:20',
        ]);

        $guardian->update($validated);

        return redirect()->route('guardians.show', $guardian)->with('success', 'Guardian updated successfully.');
    }

    public function destroy(Guardian $guardian): RedirectResponse
    {
        $guardian->delete();

        return redirect()->route('guardians.index')->with('success', 'Guardian deleted successfully.');
    }
}
