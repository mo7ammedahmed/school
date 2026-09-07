<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use Inertia\Response;
use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\ContactFormMail;

class ContactController
{
    public function index(): Response
    {
        return Inertia::render('public/contact');
    }

    public function store(Request $request): \Illuminate\Http\RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
        ]);

        // TODO: Send email to school
        // Mail::to(config('mail.school_email'))->send(new ContactFormMail($validated));

        return back()->with('success', 'Message sent successfully. We will get back to you soon.');
    }
}