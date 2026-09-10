<?php

namespace Tests\Feature;

use App\Models\File;
use Database\Seeders\DepartmentSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FileUploadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([UserSeeder::class, DepartmentSeeder::class]);
        Storage::fake('private');
    }

    private function admin()
    {
        return \App\Models\User::whereEmail('admin@example.com')->first();
    }

    private function viewer()
    {
        return \App\Models\User::whereEmail('viewer@example.com')->first();
    }

    private function folderId(): int
    {
        $admin = \App\Models\User::whereEmail('admin@example.com')->first();

        return \App\Models\Folder::create([
            'name'       => 'Docs',
            'created_by' => $admin->id,
        ])->id;
    }

    /**
     * NEVER hardcode department ids in tests: PG sequences are not
     * transactional — rolled-back inserts from earlier tests still
     * consume ids, so the seeder's first run in this class may start at 5.
     */
    private function departmentId(): int
    {
        return \App\Models\Department::where('name', 'Finance')->first()->id;
    }

    public function test_admin_can_upload_file(): void
    {
        $response = $this->actingAs($this->admin())
            ->post('/api/v1/files', [
                'title'         => 'Q3 Financial Statement',
                'folder_id'     => $this->folderId(),
                'department_id' => $this->departmentId(),
                'file'          => UploadedFile::fake()->create('q3_statement.pdf', 100),
            ], ['Accept' => 'application/json']);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.title', 'Q3 Financial Statement')
            ->assertJsonPath('data.original_name', 'q3_statement.pdf')
            ->assertJsonPath('data.file_size_formatted', '100 KB') // fake()->create size is in KB
            ->assertJsonPath('data.folder.name', 'Docs')
            ->assertJsonPath('data.uploaded_by.name', 'System Administrator');

        // file really landed on the (fake) private disk under a uuid name
        $file = File::first();
        Storage::disk('private')->assertExists($file->storage_path);
        $this->assertStringContainsString('documents/', $file->storage_path);

        // the internal path must NEVER appear in the response
        $response->assertJsonMissing(['storage_path' => $file->storage_path]);
    }

    public function test_viewer_cannot_upload(): void
    {
        $this->actingAs($this->viewer())
            ->post('/api/v1/files', [
                'title'         => 'x',
                'folder_id'     => $this->folderId(),
                'department_id' => $this->departmentId(),
                'file'          => UploadedFile::fake()->create('x.pdf', 10),
            ], ['Accept' => 'application/json'])
            ->assertStatus(403);

        $this->assertDatabaseCount('files', 0);
    }

    public function test_upload_requires_authentication(): void
    {
        $this->post('/api/v1/files', [], ['Accept' => 'application/json'])
            ->assertStatus(401);
    }

    public function test_rejected_mime_type(): void
    {
        $this->actingAs($this->admin())
            ->post('/api/v1/files', [
                'title'         => 'evil',
                'folder_id'     => $this->folderId(),
                'department_id' => $this->departmentId(),
                'file'          => UploadedFile::fake()->create('payload.exe', 10),
            ], ['Accept' => 'application/json'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('file');
    }

    public function test_rejected_file_over_25mb(): void
    {
        $this->actingAs($this->admin())
            ->post('/api/v1/files', [
                'title'         => 'big',
                'folder_id'     => $this->folderId(),
                'department_id' => $this->departmentId(),
                'file'          => UploadedFile::fake()->create('big.pdf', 26000), // KB
            ], ['Accept' => 'application/json'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('file');
    }

    public function test_soft_deleted_folder_cannot_accept_files(): void
    {
        $folderId = $this->folderId();
        \App\Models\Folder::find($folderId)->delete();

        $this->actingAs($this->admin())
            ->post('/api/v1/files', [
                'title'         => 'ghost parent',
                'folder_id'     => $folderId,
                'department_id' => $this->departmentId(),
                'file'          => UploadedFile::fake()->create('g.pdf', 10),
            ], ['Accept' => 'application/json'])
            ->assertStatus(422);
    }
}
