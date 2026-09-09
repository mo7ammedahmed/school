<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Document;
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
            'file' => 'required|file|max:10240',
            'description' => 'nullable|string',
        ]);

        if ($request->hasFile('file')) {
            $path = $request->file('file')->store('documents', 'public');
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
        abort_unless((int) $document->school_id === (int) session('school_id'), 404);

        $document->load('uploadedBy');

        return inertia('documents/show', ['document' => $document]);
    }

    public function edit(Document $document): Response
    {
        abort_unless((int) $document->school_id === (int) session('school_id'), 404);

        $document->load('uploadedBy');

        return inertia('documents/edit', ['document' => $document]);
    }

    public function update(Request $request, Document $document): RedirectResponse
    {
        abort_unless((int) $document->school_id === (int) session('school_id'), 404);

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
        abort_unless((int) $document->school_id === (int) session('school_id'), 404);

        if ($document->file_path && Storage::disk('public')->exists($document->file_path)) {
            Storage::disk('public')->delete($document->file_path);
        }

        $document->delete();

        return redirect()->route('documents.index')->with('success', 'Document deleted successfully.');
    }
}
