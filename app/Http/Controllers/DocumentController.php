<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Document;
use App\Validation\AllowedAttachment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Response;

class DocumentController extends Controller
{
    public function index(): Response
    {
        $documents = Document::where('school_id', session('school_id'))
            ->with('uploadedBy')
            ->latest()
            ->paginate(15);

        return inertia('documents/index', ['documents' => $documents]);
    }

    public function create(): Response
    {
        return inertia('documents/create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'classification' => 'required|in:transcript,certificate,report,policy,form,other',
            'file' => ['required', AllowedAttachment::rule()],
            'description' => 'nullable|string',
        ]);

        if ($request->hasFile('file')) {
            // Private disk: a document is a school record, not a web asset. On
            // the public disk its URL sits inside the document root and the web
            // server will serve — and execute — whatever extension it kept.
            $path = $request->file('file')->store('documents');
            $validated['file_path'] = $path;
            $validated['file_size'] = $request->file('file')->getSize();
            $validated['file_type'] = $request->file('file')->extension();
        }

        $validated['school_id'] = session('school_id');
        $validated['uploaded_by'] = auth()->id();

        Document::create($validated);

        return redirect()->route('documents.index')->with('success', 'Document uploaded successfully.');
    }

    public function show(Document $document): Response
    {
        $document->load('uploadedBy');

        return inertia('documents/show', ['document' => $document]);
    }

    public function edit(Document $document): Response
    {
        $document->load('uploadedBy');

        return inertia('documents/edit', ['document' => $document]);
    }

    public function update(Request $request, Document $document): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'classification' => 'required|in:transcript,certificate,report,policy,form,other',
            'description' => 'nullable|string',
        ]);

        $document->update($validated);

        return redirect()->route('documents.show', $document)->with('success', 'Document updated successfully.');
    }

    public function destroy(Document $document): RedirectResponse
    {
        // Both disks: documents uploaded before this stopped writing to the
        // public disk are still there, and the copy nobody deletes is the copy
        // that stays reachable inside the document root.
        foreach (['local', 'public'] as $disk) {
            if ($document->file_path && Storage::disk($disk)->exists($document->file_path)) {
                Storage::disk($disk)->delete($document->file_path);
            }
        }

        $document->delete();

        return redirect()->route('documents.index')->with('success', 'Document deleted successfully.');
    }
}
