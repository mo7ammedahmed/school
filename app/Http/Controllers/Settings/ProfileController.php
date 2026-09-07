<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use Inertia\Response;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Http\RedirectResponse;

class ProfileController extends Controller
{
    public function edit(): Response
    {
        return inertia('settings/profile/edit', [
            'user' => auth()->user(),
        ]);
    }

    public function index(): Response
    {
        return $this->edit();
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . auth()->id(),
            'phone' => 'nullable|string|max:20',
        ]);

        auth()->user()->update($validated);

        return redirect()->route('settings.profile')->with('success', 'Profile updated successfully.');
    }

    public function store(Request $request): RedirectResponse
    {
        return $this->update($request);
    }
}
