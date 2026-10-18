<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AdminUploadController extends Controller
{
    /**
     * Secure image upload for thumbnails/covers. Validates real MIME,
     * randomizes the filename, stores on the public disk.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'max:5120', 'mimes:jpg,jpeg,png,webp,svg', 'mimetypes:image/jpeg,image/png,image/webp,image/svg+xml'],
            'folder' => ['nullable', 'string', 'regex:/^[a-z0-9\-\/]+$/', 'max:60'],
        ]);

        $file = $request->file('file');
        $ext = match ($file->getMimeType()) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/svg+xml' => 'svg',
            default => abort(422, 'نوع الملف غير مدعوم.'),
        };

        $folder = $request->string('folder')->toString() ?: 'uploads';
        $path = $file->storeAs($folder, Str::uuid().'.'.$ext, 'public');

        return response()->json([
            'path' => $path,
            'url' => Storage::disk('public')->url($path),
        ], 201);
    }
}
