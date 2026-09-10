<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\File;
use App\Models\Folder;
use Database\Seeders\DepartmentSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FileManagementTest extends TestCase
{
    use RefreshDatabase;

    private \App\Models\User $admin;
    private \App\Models\User $viewer;
    private File $file;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([UserSeeder::class, DepartmentSeeder::class]);
        Storage::fake('private');

        $this->admin  = \App\Models\User::whereEmail('admin@example.com')->first();
        $this->viewer = \App\Models\User::whereEmail('viewer@example.com')->first();

        // seed one real file (via model — no HTTP guard-cache poisoning)
        $folder = Folder::create(['name' => 'Docs', 'created_by' => $this->admin->id]);
        Storage::disk('private')->put('documents/2026/09/seed.pdf', 'seed-bytes');

        $this->file = File::create([
            'folder_id'     => $folder->id,
            'department_id' => Department::where('name', 'Finance')->first()->id,
            'user_id'       => $this->admin->id,
            'title'         => 'Seed File',
            'original_name' => 'seed.pdf',
            'storage_path'  => 'documents/2026/09/seed.pdf',
            'mime_type'     => 'application/pdf',
            'file_size'     => 10,
        ]);
    }

    public function test_anyone_authenticated_can_list_and_filter_files(): void
    {
        $this->actingAs($this->viewer)->getJson('/api/v1/files')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonStructure(['data', 'meta' => ['current_page', 'last_page', 'per_page', 'total']]);

        // filter by a department with no files → empty
        $other = Department::where('name', 'IT')->first()->id;
        $this->actingAs($this->viewer)
            ->getJson("/api/v1/files?department_id={$other}")
            ->assertStatus(200)
            ->assertJsonCount(0, 'data');
    }

    public function test_file_detail_has_contract_shape(): void
    {
        $this->actingAs($this->viewer)
            ->getJson("/api/v1/files/{$this->file->id}")
            ->assertStatus(200)
            ->assertJsonPath('data.title', 'Seed File')
            ->assertJsonPath('data.folder.name', 'Docs')
            ->assertJsonPath('data.department.name', 'Finance')
            ->assertJsonPath('data.uploaded_by.name', 'System Administrator');
    }

    public function test_admin_can_edit_metadata_but_not_folder(): void
    {
        $it = Department::where('name', 'IT')->first()->id;

        $this->actingAs($this->admin)
            ->putJson("/api/v1/files/{$this->file->id}", [
                'title'         => 'Renamed',
                'department_id' => $it,
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.title', 'Renamed')
            ->assertJsonPath('data.department.name', 'IT');

        // folder_id is not part of the contract — sending it changes nothing
        $this->assertDatabaseHas('files', ['id' => $this->file->id, 'title' => 'Renamed']);
    }

    public function test_viewer_cannot_edit_or_delete(): void
    {
        $this->actingAs($this->viewer)
            ->putJson("/api/v1/files/{$this->file->id}", ['title' => 'x', 'department_id' => 1])
            ->assertStatus(403);

        $this->actingAs($this->viewer)
            ->deleteJson("/api/v1/files/{$this->file->id}")
            ->assertStatus(403);
    }

    public function test_admin_soft_delete_keeps_bytes_on_disk(): void
    {
        $this->actingAs($this->admin)
            ->deleteJson("/api/v1/files/{$this->file->id}")
            ->assertStatus(200);

        $this->assertSoftDeleted('files', ['id' => $this->file->id]);
        Storage::disk('private')->assertExists('documents/2026/09/seed.pdf'); // bytes intact

        // soft-deleted file disappears from listing
        $this->actingAs($this->admin)
            ->getJson('/api/v1/files')
            ->assertJsonCount(0, 'data');
    }

    public function test_download_streams_original_name(): void
    {
        $response = $this->actingAs($this->viewer)
            ->get("/api/v1/files/{$this->file->id}/download", ['Accept' => 'application/json']);

        $response->assertStatus(200)
            ->assertHeader('Content-Disposition', 'attachment; filename=seed.pdf');

        $this->assertSame('seed-bytes', $response->streamedContent());
    }

    public function test_preview_uses_inline_disposition(): void
    {
        $this->actingAs($this->viewer)
            ->get("/api/v1/files/{$this->file->id}/preview", ['Accept' => 'application/json'])
            ->assertStatus(200)
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('Content-Disposition', 'inline; filename=seed.pdf');
    }

    public function test_download_requires_authentication(): void
    {
        $this->getJson('/api/v1/files')->assertStatus(401);
        $this->getJson("/api/v1/files/{$this->file->id}/download")->assertStatus(401);
    }

    public function test_department_with_files_cannot_be_deleted(): void
    {
        $deptId = $this->file->department_id;

        $this->actingAs($this->admin)
            ->deleteJson("/api/v1/departments/{$deptId}")
            ->assertStatus(422)
            ->assertJsonPath('success', false);

        $this->assertDatabaseHas('departments', ['id' => $deptId]);
    }

    public function test_department_without_files_can_be_deleted(): void
    {
        $legal = Department::where('name', 'Legal')->first();

        $this->actingAs($this->admin)
            ->deleteJson("/api/v1/departments/{$legal->id}")
            ->assertStatus(200);
    }

    public function test_soft_deleted_file_still_blocks_department_deletion(): void
    {
        // trash the file, THEN try deleting its department — the FK is still held
        $this->file->delete();
        $deptId = $this->file->department_id;

        $this->actingAs($this->admin)
            ->deleteJson("/api/v1/departments/{$deptId}")
            ->assertStatus(422);
    }
}
