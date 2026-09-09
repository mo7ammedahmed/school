<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Inertia\Response;
use App\Domain\Learning\Models\Material;
use App\Domain\Academics\Models\Offering;
use App\Domain\Academics\Models\Section;
use App\Domain\Academics\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;

class MaterialController extends Controller
{
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
            'file' => 'required|file|max:10240',
            'description' => 'nullable|string',
        ]);

        $offeringId = $validated['offering_id'] ?? $this->resolveOfferingId((int) $validated['subject_id'], (int) $validated['section_id']);

        $path = $request->file('file')->store('materials', 'public');
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

    public function show(Material $material): Response
    {
        $this->authorizeSchool($material);
        $material->load(['offering.subject', 'offering.section']);

        return inertia('materials/show', ['material' => $material]);
    }

    public function edit(Material $material): Response
    {
        $this->authorizeSchool($material);
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
        $this->authorizeSchool($material);
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'subject_id' => 'required_without:offering_id|exists:subjects,id',
            'section_id' => 'required_without:offering_id|exists:sections,id',
            'offering_id' => 'nullable|exists:offerings,id',
            'file' => 'nullable|file|max:10240',
            'description' => 'nullable|string',
        ]);

        $offeringId = $validated['offering_id'] ?? $this->resolveOfferingId((int) $validated['subject_id'], (int) $validated['section_id']);

        $data = [
            'offering_id' => $offeringId,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
        ];

        if ($request->hasFile('file')) {
            if ($material->file_path && Storage::disk('public')->exists($material->file_path)) {
                Storage::disk('public')->delete($material->file_path);
            }
            $path = $request->file('file')->store('materials', 'public');
            $data['file_path'] = $path;
            $data['file_type'] = $request->file('file')->extension() ?: $request->file('file')->getClientOriginalExtension();
            $data['file_size'] = $request->file('file')->getSize();
        }

        $material->update($data);

        return redirect()->route('materials.show', $material)->with('success', 'Material updated successfully.');
    }

    public function destroy(Material $material): RedirectResponse
    {
        $this->authorizeSchool($material);
        if ($material->file_path && Storage::disk('public')->exists($material->file_path)) {
            Storage::disk('public')->delete($material->file_path);
        }

        $material->delete();

        return redirect()->route('materials.index')->with('success', 'Material deleted successfully.');
    }

    private function authorizeSchool(Material $material): void
    {
        if ((int) $material->school_id !== (int) session('school_id')) {
            abort(403);
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
