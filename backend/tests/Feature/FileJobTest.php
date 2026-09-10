<?php

namespace Tests\Feature;

use App\Jobs\GenerateFileThumbnailJob;
use App\Jobs\RecordActivityLogJob;
use App\Models\Department;
use App\Models\File;
use App\Models\Folder;
use Database\Seeders\DepartmentSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FileJobTest extends TestCase
{
    use RefreshDatabase;

    private \App\Models\User $admin;
    private \App\Models\User $viewer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([UserSeeder::class, DepartmentSeeder::class]);
        Storage::fake('private');
        Queue::fake();

        $this->admin  = \App\Models\User::whereEmail('admin@example.com')->first();
        $this->viewer = \App\Models\User::whereEmail('viewer@example.com')->first();
    }

    private function uploadViaApi(array $overrides = []): \Illuminate\Testing\TestResponse
    {
        // Prerequisites via MODEL, not HTTP — no guard-cache poisoning
        $folder = Folder::create(['name' => 'Docs', 'created_by' => $this->admin->id]);

        return $this->actingAs($this->admin)
            ->post('/api/v1/files', array_merge([
                'title'         => 'Job Test',
                'folder_id'     => $folder->id,
                'department_id' => Department::where('name', 'Finance')->first()->id,
                'file'          => UploadedFile::fake()->image('photo.png', 600, 400),
            ], $overrides), ['Accept' => 'application/json']);
    }

    public function test_upload_dispatches_thumbnail_and_activity_jobs(): void
    {
        $this->uploadViaApi()->assertStatus(201);

        Queue::assertPushed(GenerateFileThumbnailJob::class);
        Queue::assertPushed(RecordActivityLogJob::class, fn ($job) => $job->action === 'upload_file');
    }

    public function test_download_and_delete_dispatch_activity_jobs(): void
    {
        $this->uploadViaApi();
        $file = File::first();

        Queue::fake(); // reset the recorder — assert only what download/delete push

        $this->actingAs($this->viewer)
            ->get("/api/v1/files/{$file->id}/download", ['Accept' => 'application/json'])
            ->assertStatus(200);
        Queue::assertPushed(RecordActivityLogJob::class, fn ($job) => $job->action === 'download_file');

        $this->actingAs($this->admin)
            ->deleteJson("/api/v1/files/{$file->id}")
            ->assertStatus(200);
        Queue::assertPushed(RecordActivityLogJob::class, fn ($job) => $job->action === 'delete_file');
    }

    public function test_activity_log_job_writes_the_audit_row(): void
    {
        $this->uploadViaApi();
        $file = File::first();

        // run inline — this is exactly what the worker executes
        (new RecordActivityLogJob($file, 'upload_file', ['folder_id' => $file->folder_id]))
            ->handle();

        $this->assertDatabaseHas('activity_logs', [
            'action'      => 'upload_file',
            'entity_type' => 'App\Models\File',
            'entity_id'   => $file->id,
        ]);
        $this->assertDatabaseCount('activity_logs', 1);
    }

    public function test_thumbnail_job_generates_png_for_real_images(): void
    {
        $this->uploadViaApi();
        $file = File::first();

        (new GenerateFileThumbnailJob($file))->handle();

        Storage::disk('private')->assertExists('thumbnails/'.$file->id.'.png');
    }

    public function test_thumbnail_job_skips_non_images(): void
    {
        $this->uploadViaApi(['file' => UploadedFile::fake()->create('doc.pdf', 10)]);
        $file = File::first();

        (new GenerateFileThumbnailJob($file))->handle();

        Storage::disk('private')->assertMissing('thumbnails/'.$file->id.'.png');
    }
}
