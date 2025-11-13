<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\Pet;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class FileUploadController extends Controller
{
    use AuthorizesRequests;

    /**
     * Upload pet photo
     */
    public function uploadPetPhoto(Request $request, Pet $pet): JsonResponse
    {
        $this->authorize('update', $pet);

        $request->validate([
            'photo' => 'required|image|max:5120', // 5MB max
        ]);

        // Delete old photo if exists
        if ($pet->photo) {
            Storage::disk('public')->delete($pet->photo);
        }

        $file = $request->file('photo');
        $fileName = 'pets/' . Str::uuid() . '.' . $file->getClientOriginalExtension();
        $file->storeAs('', $fileName, 'public');

        $pet->update(['photo' => $fileName]);

        return response()->json([
            'message' => 'Pet photo uploaded successfully',
            'photo_url' => url('storage/' . $fileName),
        ]);
    }

    /**
     * Delete pet photo
     */
    public function deletePetPhoto(Pet $pet): JsonResponse
    {
        $this->authorize('update', $pet);

        if ($pet->photo) {
            Storage::disk('public')->delete($pet->photo);
            $pet->update(['photo' => null]);
        }

        return response()->json([
            'message' => 'Pet photo deleted successfully',
        ]);
    }

    /**
     * Upload document
     */
    public function uploadDocument(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:10240', // 10MB max
            'documentable_type' => 'required|string|in:Pet,MedicalRecord,Vaccination',
            'documentable_id' => 'required|integer',
            'document_type' => 'required|in:medical_report,lab_result,xray,prescription,vaccination_card,other',
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);

        // Get the model
        $modelClass = "App\\Models\\" . $request->documentable_type;
        $model = $modelClass::findOrFail($request->documentable_id);

        // Authorization check based on model type
        if ($request->documentable_type === 'Pet') {
            $this->authorize('update', $model);
        } elseif (in_array($request->documentable_type, ['MedicalRecord', 'Vaccination'])) {
            $this->authorize('update', $model);
        }

        $file = $request->file('file');
        $fileName = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $filePath = 'documents/' . $request->documentable_type . '/' . $fileName;
        
        $file->storeAs('', $filePath, 'public');

        $document = Document::create([
            'documentable_type' => $modelClass,
            'documentable_id' => $request->documentable_id,
            'title' => $request->title,
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $filePath,
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'document_type' => $request->document_type,
            'description' => $request->description,
            'uploaded_by' => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'Document uploaded successfully',
            'document' => $document,
        ], 201);
    }

    /**
     * Get documents for a model
     */
    public function getDocuments(Request $request): JsonResponse
    {
        $request->validate([
            'documentable_type' => 'required|string|in:Pet,MedicalRecord,Vaccination',
            'documentable_id' => 'required|integer',
        ]);

        $modelClass = "App\\Models\\" . $request->documentable_type;
        $model = $modelClass::findOrFail($request->documentable_id);

        // Authorization check
        if ($request->documentable_type === 'Pet') {
            $this->authorize('view', $model);
        } elseif (in_array($request->documentable_type, ['MedicalRecord', 'Vaccination'])) {
            $this->authorize('view', $model);
        }

        $documents = $model->documents()->with('uploader')->latest()->get();

        return response()->json($documents);
    }

    /**
     * Download document
     */
    public function downloadDocument(Document $document)
    {
        // Authorization check
        $model = $document->documentable;
        if ($model instanceof Pet) {
            $this->authorize('view', $model);
        } elseif ($model instanceof \App\Models\MedicalRecord || $model instanceof \App\Models\Vaccination) {
            $this->authorize('view', $model);
        }

        $filePath = storage_path('app/public/' . $document->file_path);
        
        if (!file_exists($filePath)) {
            abort(404, 'File not found');
        }

        return response()->download($filePath, $document->file_name);
    }

    /**
     * Delete document
     */
    public function deleteDocument(Document $document): JsonResponse
    {
        // Authorization check
        $model = $document->documentable;
        if ($model instanceof Pet) {
            $this->authorize('update', $model);
        } elseif ($model instanceof \App\Models\MedicalRecord || $model instanceof \App\Models\Vaccination) {
            $this->authorize('update', $model);
        }

        $document->delete();

        return response()->json([
            'message' => 'Document deleted successfully',
        ]);
    }
}
