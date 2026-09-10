<?php

namespace App\Jobs;

use App\Models\File;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class GenerateFileThumbnailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private const THUMB_WIDTH = 300;

    public function __construct(public readonly File $file)
    {
    }

    public function handle(): void
    {
        // Raster images only — PDFs preview directly, office files have no preview
        if (! str_starts_with($this->file->mime_type, 'image/')) {
            return;
        }

        $disk = Storage::disk('private');

        // Guard: the file may have been deleted between dispatch and run
        if (! $disk->exists($this->file->storage_path)) {
            return;
        }

        // GD pipeline: bytes -> image resource -> scale -> PNG bytes -> store
        $source = imagecreatefromstring($disk->get($this->file->storage_path));
        if ($source === false) {
            return; // corrupt image — not worth failing the whole job
        }

        $thumb = imagescale($source, self::THUMB_WIDTH);
        imagedestroy($source);

        ob_start();
        imagepng($thumb);
        $pngBytes = ob_get_clean();
        imagedestroy($thumb);

        $disk->put('thumbnails/'.$this->file->id.'.png', $pngBytes);
    }
}
