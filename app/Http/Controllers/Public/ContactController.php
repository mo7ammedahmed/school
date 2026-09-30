<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Mail\ContactMessageMail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class ContactController extends PublicController
{
    public function index(): Response
    {
        return Inertia::render('public/contact');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        $school = $this->schools->current();
        $recipient = $this->recipient($school?->email);

        if ($recipient === null) {
            // Better to say so than to thank the sender for a message that went
            // nowhere; `mail.from.address` is the last-resort inbox.
            return back()->with('error', 'This school has no contact address configured yet.');
        }

        // The form used to validate all four fields, queue nothing and thank the
        // visitor, so every message written here was lost.
        try {
            Mail::to($recipient)->send(new ContactMessageMail(
                senderName: $data['name'],
                senderEmail: $data['email'],
                subjectLine: $data['subject'],
                messageBody: $data['message'],
                schoolName: $school?->name,
            ));
        } catch (Throwable $exception) {
            // A broken mail server must not take the public site down with it.
            report($exception);

            return back()->with('error', 'We could not send your message just now. Please try again shortly.');
        }

        return back()->with('success', 'Message sent successfully. We will get back to you soon.');
    }

    /**
     * Who receives website messages: the school's own inbox, or the address the
     * application sends from when the school has not set one.
     */
    private function recipient(?string $schoolEmail): ?string
    {
        foreach ([$schoolEmail, config('mail.from.address')] as $candidate) {
            if (is_string($candidate) && filter_var($candidate, FILTER_VALIDATE_EMAIL)) {
                return $candidate;
            }
        }

        return null;
    }
}
