<?php

namespace App\Jobs;

use App\Models\ActivityLog;
use App\Models\File;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RecordActivityLogJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Only scalars + models — anything else must be resolved inside handle().
     * SerializesModels turns $file into its id in the queue payload.
     *
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public readonly File $file,
        public readonly string $action, // upload_file | download_file | delete_file
        public readonly array $metadata = [],
        public readonly ?string $ipAddress = null,
    ) {
    }

    public function handle(): void
    {
        ActivityLog::create([
            'user_id'     => $this->file->user_id,
            'action'      => $this->action,
            'entity_type' => $this->file->getMorphClass(),
            'entity_id'   => $this->file->id,
            'metadata'    => array_merge([
                'title'         => $this->file->title,
                'original_name' => $this->file->original_name,
            ], $this->metadata),
            'ip_address'  => $this->ipAddress,
        ]);
    }
}
