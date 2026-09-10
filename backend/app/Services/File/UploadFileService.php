<?php

namespace App\Services\File;

use App\Http\Requests\File\StoreFileRequest;
use App\Jobs\GenerateFileThumbnailJob;
use App\Jobs\RecordActivityLogJob;
use App\Models\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UploadFileService
{
    public function __construct(private readonly Storage $storage) {}

    /**
     * Store the binary on the private disk (uuid name), persist metadata.
     * Storage first, DB second — a failed insert leaves an orphan file,
     * a reversed order could reference a file that was never written.
     */
    public function execute(StoreFileRequest $request): File
    {
        $uploaded = $request->file('file');

        // documents/2026/09/{uuid}.{ext} — date partition keeps dirs small
        $path = $uploaded->storeAs(
            'documents/' . now()->format('Y/m'),
            Str::uuid()->toString() . '.' . $uploaded->getClientOriginalExtension(),
            'private'
        );

        $file = File::create([
            ...$request->safe()->only(['title', 'folder_id', 'department_id']),
            'user_id'       => $request->user()->id,
            'original_name' => $uploaded->getClientOriginalName(),
            'storage_path'  => $path,
            'mime_type'     => $uploaded->getMimeType(), // sniffed server-side
            'file_size'     => $uploaded->getSize(),
        ]);

        // Fire-and-forget: the response never waits for these to finish
        GenerateFileThumbnailJob::dispatch($file);
        RecordActivityLogJob::dispatch(
            $file,
            'upload_file',
            ['folder_id' => $file->folder_id, 'department_id' => $file->department_id],
            request()->ip(),
        );

        return $file;
    }
}
