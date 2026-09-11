<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\File\StoreFileRequest;
use App\Jobs\RecordActivityLogJob;
use App\Http\Requests\File\UpdateFileRequest;
use App\Http\Resources\V1\FileResource;
use App\Models\File;
use App\Services\File\UploadFileService;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

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

    /**
     * GET /api/v1/files?search=&folder_id=&department_id=&page=&per_page=
     * search → GIN full-text (rank-ordered); otherwise recency (partial index).
     * Soft deletes are excluded automatically by the model.
     */
    public function index(Request $request): JsonResponse
    {
        $term = $request->string('search')->toString();

        $files = File::query()
            ->with(['folder', 'department', 'uploader'])
            ->when($request->filled('folder_id'), fn(Builder $q) => $q->where('folder_id', $request->integer('folder_id')))
            ->when($request->filled('department_id'), fn(Builder $q) => $q->where('department_id', $request->integer('department_id')))
            ->when(
                $term !== '',
                // websearch_to_tsquery: safe for raw user input
                // ('laporan q3' → laporan & q3; quoted phrases supported)
                fn(Builder $q) => $q
                    ->whereRaw("search_vector @@ websearch_to_tsquery('english', ?)", [$term])
                    ->orderByRaw("ts_rank(search_vector, websearch_to_tsquery('english', ?)) DESC", [$term])
            )
            // created_at has SECOND precision — ties happen within one second,
            // so the always-increasing id is the deterministic tiebreaker
            ->when($term === '', fn(Builder $q) => $q->orderByDesc('created_at')->orderByDesc('id'))
            ->paginate($request->integer('per_page', 15));

        return response()->json([
            'success' => true,
            'message' => 'Files retrieved',
            'data'    => FileResource::collection($files->items()),
            'meta'    => [
                'current_page' => $files->currentPage(),
                'last_page'    => $files->lastPage(),
                'per_page'     => $files->perPage(),
                'total'        => $files->total(),
            ],
        ]);
    }

    /**
     * GET /api/v1/files/{id} — contract 3.4 detail.
     */
    public function show(File $file): JsonResponse
    {
        $file->load(['folder', 'department', 'uploader']);

        return response()->json([
            'success' => true,
            'message' => 'File detail',
            'data'    => new FileResource($file),
        ]);
    }

    /**
     * PUT /api/v1/files/{id} — metadata only (title, department).
     */
    public function update(UpdateFileRequest $request, File $file): JsonResponse
    {
        $file->update($request->safe()->only(['title', 'department_id']));

        return response()->json([
            'success' => true,
            'message' => 'File updated successfully',
            'data'    => new FileResource($file->fresh(['folder', 'department', 'uploader'])),
        ]);
    }

    /**
     * DELETE /api/v1/files/{id} — soft delete metadata; bytes stay on disk
     * (restore is possible; a janitor job may purge much later).
     */
    public function destroy(File $file): JsonResponse
    {
        $file->delete();

        // Audit asynchronously — the request never waits for the insert
        RecordActivityLogJob::dispatch($file, 'delete_file', [], request()->ip());

        return response()->json([
            'success' => true,
            'message' => 'File deleted successfully',
        ]);
    }

    /**
     * GET /api/v1/files/{id}/download — force download with the ORIGINAL name.
     * Storage::download() returns a streamed response: memory stays flat.
     */
    public function download(Request $request, File $file): StreamedResponse
    {
        RecordActivityLogJob::dispatch($file, 'download_file', [], $request->ip());

        return Storage::disk('private')->download(
            $file->storage_path,
            $file->original_name
        );
    }

    /**
     * GET /api/v1/files/{id}/preview — inline disposition so PDFs/images
     * render in the browser tab instead of downloading.
     */
    public function preview(File $file): StreamedResponse
    {
        // streamDownload() overwrites a manual Content-Disposition header via
        // makeDisposition() with default 'attachment' — the disposition must
        // be passed as the 4th parameter instead.
        return response()->streamDownload(function () use ($file) {
            $stream = Storage::disk('private')->readStream($file->storage_path);
            fpassthru($stream);          // echo chunks as they are read
            fclose($stream);
        }, $file->original_name, [
            'Content-Type' => $file->mime_type,
        ], 'inline');
    }
}
