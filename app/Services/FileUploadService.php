<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileUploadService
{
    /**
     * Upload image
     */
    public function uploadImage(UploadedFile $file, string $directory): string
    {
        $fileName = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $filePath = $directory . '/' . $fileName;
        
        $file->storeAs('', $filePath, 'public');

        return $filePath;
    }

    /**
     * Upload file
     */
    public function uploadFile(UploadedFile $file, string $directory): string
    {
        $fileName = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $filePath = $directory . '/' . $fileName;
        
        $file->storeAs('', $filePath, 'public');

        return $filePath;
    }

    /**
     * Delete file
     */
    public function deleteFile(string $filePath): bool
    {
        if (Storage::disk('public')->exists($filePath)) {
            return Storage::disk('public')->delete($filePath);
        }

        return false;
    }

    /**
     * Get file size in human-readable format
     */
    public function formatFileSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Validate file type
     */
    public function isAllowedFileType(string $mimeType, array $allowedTypes): bool
    {
        return in_array($mimeType, $allowedTypes);
    }

    /**
     * Get file extension from mime type
     */
    public function getExtensionFromMimeType(string $mimeType): ?string
    {
        $mimeMap = [
            'application/pdf' => 'pdf',
            'application/msword' => 'doc',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
        ];

        return $mimeMap[$mimeType] ?? null;
    }
}

