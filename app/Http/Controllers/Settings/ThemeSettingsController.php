<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

/**
 * The light/dark palettes used to live on their own screen; they now sit inside
 * Settings → Appearance together with the logo and the website colours, so one
 * form owns the whole look of the school. This controller keeps the old URLs
 * working (bookmarks, the sidebar before the merge) and owns the per-user
 * light/dark preference, which is not an admin setting and therefore lives
 * outside the settings-management route group.
 */
class ThemeSettingsController extends Controller
{
    public function index(): RedirectResponse
    {
        return Redirect::route('settings.appearance');
    }

    /**
     * POSTs to the old endpoint are forwarded to the merged screen instead of
     * silently losing the operator's edits.
     */
    public function store(Request $request): RedirectResponse
    {
        return Redirect::route('settings.appearance')->withInput($request->all());
    }

    /**
     * Persist the signed-in user's light/dark/system preference.
     */
    public function storeMode(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'mode' => 'required|in:light,dark,system',
        ]);

        $request->user()->update(['theme' => $validated['mode']]);

        return back();
    }
}
