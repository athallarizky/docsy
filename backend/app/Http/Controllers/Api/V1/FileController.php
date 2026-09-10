<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\File\StoreFileRequest;
use App\Http\Resources\V1\FileResource;
use App\Models\File;
use App\Services\File\UploadFileService;
use Illuminate\Http\JsonResponse;

class FileController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(File::class, 'file');
    }

    /**
     * POST /api/v1/files — multipart upload, admin only.
     */
    public function store(StoreFileRequest $request, UploadFileService $uploader): JsonResponse
    {
        $file = $uploader->execute($request);
        $file->load(['folder', 'department', 'uploader']);

        return response()->json([
            'success' => true,
            'message' => 'File uploaded successfully',
            'data'    => new FileResource($file),
        ], 201);
    }
}
