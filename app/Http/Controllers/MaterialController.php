<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Academics\Models\Offering;
use App\Domain\Academics\Models\Section;
use App\Domain\Academics\Models\Subject;
use App\Domain\Learning\Models\Material;
use App\Http\Controllers\Concerns\ServesStoredAttachment;
use App\Validation\AllowedAttachment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MaterialController extends Controller
{
    use ServesStoredAttachment;

    public function index(): Response
    {
        $schoolId = session('school_id');
        $materials = Material::where('school_id', $schoolId)
            ->with(['offering.subject', 'offering.section'])
            ->latest()
            ->paginate(15);

        return inertia('materials/index', ['materials' => $materials]);
    }

    public function create(): Response
    {
        $schoolId = session('school_id');
        $subjects = Subject::where('school_id', $schoolId)->orderBy('name_en')->get();
        $sections = Section::where('school_id', $schoolId)->orderBy('name_en')->get();

        return inertia('materials/create', ['subjects' => $subjects, 'sections' => $sections]);
    }

    public function store(Request $request): RedirectResponse
    {
        $schoolId = session('school_id');
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'subject_id' => 'required_without:offering_id|exists:subjects,id',
            'section_id' => 'required_without:offering_id|exists:sections,id',
            'offering_id' => 'nullable|exists:offerings,id',
            'file' => ['required', AllowedAttachment::rule()],
            'description' => 'nullable|string',
        ]);

        $offeringId = $validated['offering_id'] ?? $this->resolveOfferingId((int) $validated['subject_id'], (int) $validated['section_id']);

        // The private disk, not `public`. A material is a school document, not a
        // web asset: on the public disk its URL is inside the document root and
        // the web server will serve — and execute — whatever extension it kept.
        // The disk is named, not inherited — see DocumentController::store().
        $path = $request->file('file')->store('materials', 'local');
        $file = $request->file('file');

        Material::create([
            'school_id' => $schoolId,
            'offering_id' => $offeringId,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'file_path' => $path,
            'file_type' => $file->extension() ?: $file->getClientOriginalExtension(),
            'file_size' => $file->getSize(),
            'is_published' => true,
        ]);

        return redirect()->route('materials.index')->with('success', 'Material uploaded successfully.');
    }

    /**
     * Hand back the lesson plan itself. `view` is the ability the record's page
     * already asks for, so this grants no one new access.
     */
    public function download(Material $material): StreamedResponse
    {
        $this->authorize('view', $material);

        return $this->downloadAttachment($material->file_path, $material->title);
    }

    public function show(Material $material): Response
    {
        $material->load(['offering.subject', 'offering.section']);

        return inertia('materials/show', ['material' => $material]);
    }

    public function edit(Material $material): Response
    {
        $schoolId = session('school_id');
        $subjects = Subject::where('school_id', $schoolId)->orderBy('name_en')->get();
        $sections = Section::where('school_id', $schoolId)->orderBy('name_en')->get();

        return inertia('materials/edit', [
            'material' => $material->load(['offering.subject', 'offering.section']),
            'subjects' => $subjects,
            'sections' => $sections,
        ]);
    }

    public function update(Request $request, Material $material): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'subject_id' => 'required_without:offering_id|exists:subjects,id',
            'section_id' => 'required_without:offering_id|exists:sections,id',
            'offering_id' => 'nullable|exists:offerings,id',
            'file' => ['nullable', AllowedAttachment::rule()],
            'description' => 'nullable|string',
        ]);

        $offeringId = $validated['offering_id'] ?? $this->resolveOfferingId((int) $validated['subject_id'], (int) $validated['section_id']);

        $data = [
            'offering_id' => $offeringId,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
        ];

        if ($request->hasFile('file')) {
            $this->deleteStoredFile($material->file_path);

            // The disk is named, not inherited — see DocumentController::store().
            $path = $request->file('file')->store('materials', 'local');
            $data['file_path'] = $path;
            $data['file_type'] = $request->file('file')->extension() ?: $request->file('file')->getClientOriginalExtension();
            $data['file_size'] = $request->file('file')->getSize();
        }

        $material->update($data);

        return redirect()->route('materials.show', $material)->with('success', 'Material updated successfully.');
    }

    public function destroy(Material $material): RedirectResponse
    {
        $this->deleteStoredFile($material->file_path);

        $material->delete();

        return redirect()->route('materials.index')->with('success', 'Material deleted successfully.');
    }

    /**
     * Remove a material's stored file, wherever it happens to be.
     *
     * Materials uploaded before this file stopped being written to the public
     * disk are still there, so the public disk is checked as well. Checking only
     * the private one would leave every pre-existing upload sitting in the
     * document root indefinitely — the copy nobody deletes is the copy that
     * stays reachable.
     */
    private function deleteStoredFile(?string $path): void
    {
        if ($path === null || $path === '') {
            return;
        }

        foreach (['local', 'public'] as $disk) {
            if (Storage::disk($disk)->exists($path)) {
                Storage::disk($disk)->delete($path);
            }
        }
    }

    private function resolveOfferingId(int $subjectId, int $sectionId): int
    {
        $offering = Offering::where('school_id', session('school_id'))
            ->where('subject_id', $subjectId)
            ->where('section_id', $sectionId)
            ->first();

        if (! $offering) {
            throw ValidationException::withMessages([
                'subject_id' => 'No active offering exists for this subject and section.',
            ]);
        }

        return (int) $offering->id;
    }
}
