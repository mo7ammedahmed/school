<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ServesStoredAttachment;
use App\Models\Document;
use App\Validation\AllowedAttachment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentController extends Controller
{
    use ServesStoredAttachment;

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
            // The disk is named, not inherited. `store()` with one argument falls back
            // to `config('filesystems.default')`, so the guarantee that a document is
            // not written inside the document root would otherwise be a value in
            // `.env` rather than anything this code says. The name itself comes from
            // `filesystems.private`, the one place a deployment can move it.
            $path = $request->file('file')->store('documents', $this->privateDisk());
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

    /**
     * Hand back the file itself.
     *
     * `view` is the ability the record's own page already asks for, so this grants
     * no one who could not already read the document — see `ServesStoredAttachment`
     * for why the path is checked before it is opened.
     */
    public function download(Document $document): StreamedResponse
    {
        $this->authorize('view', $document);

        return $this->downloadAttachment($document->file_path, $document->title);
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
        foreach (array_unique([$this->privateDisk(), 'public']) as $disk) {
            if ($document->file_path && Storage::disk($disk)->exists($document->file_path)) {
                Storage::disk($disk)->delete($document->file_path);
            }
        }

        $document->delete();

        return redirect()->route('documents.index')->with('success', 'Document deleted successfully.');
    }
}
