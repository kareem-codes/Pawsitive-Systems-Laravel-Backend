<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadDocumentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'file' => 'required|file|mimes:pdf,doc,docx,jpg,jpeg,png,gif|max:10240', // 10MB
            'documentable_type' => 'required|string|in:Pet,MedicalRecord,Vaccination',
            'documentable_id' => 'required|integer|exists:' . $this->getTableName() . ',id',
            'document_type' => 'required|in:medical_report,lab_result,xray,prescription,vaccination_card,other',
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
        ];
    }

    /**
     * Get table name based on documentable type
     */
    protected function getTableName(): string
    {
        $typeMap = [
            'Pet' => 'pets',
            'MedicalRecord' => 'medical_records',
            'Vaccination' => 'vaccinations',
        ];

        return $typeMap[$this->documentable_type] ?? 'pets';
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'file.required' => 'Please select a file to upload.',
            'file.mimes' => 'File must be a PDF, DOC, DOCX, JPG, JPEG, PNG, or GIF.',
            'file.max' => 'File size must not exceed 10MB.',
            'documentable_type.required' => 'Document type is required.',
            'documentable_id.required' => 'Document ID is required.',
            'documentable_id.exists' => 'The selected record does not exist.',
        ];
    }
}

