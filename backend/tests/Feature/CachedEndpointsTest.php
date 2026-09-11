<?php

namespace Tests\Feature;

use App\Models\File;
use App\Models\Folder;
use Database\Seeders\DepartmentSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class CachedEndpointsTest extends TestCase
{
    use RefreshDatabase;

    private \App\Models\User $admin;
    private \App\Models\User $viewer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([UserSeeder::class, DepartmentSeeder::class]);
        $this->admin  = \App\Models\User::whereEmail('admin@example.com')->first();
        $this->viewer = \App\Models\User::whereEmail('viewer@example.com')->first();
    }

    public function test_dashboard_stats_shape_and_cache_fill(): void
    {
        $response = $this->actingAs($this->admin)
            ->getJson('/api/v1/dashboard/stats')
            ->assertStatus(200)
            ->assertJsonStructure(['data' => [
                'total_folders',
                'total_files',
                'total_departments',
                'recent_files',
            ]]);

        // the miss populated the cache — phase-3 will prove invalidation
        $this->assertTrue(Cache::has('docsy:dash:stats'));
        $this->assertSame(4, $response->json('data.total_departments'));
    }

    public function test_dashboard_requires_admin(): void
    {
        // anonymous check FIRST — after an actingAs request the cached guard
        // would leak the previous user and yield 403 instead of 401
        $this->getJson('/api/v1/dashboard/stats')->assertStatus(401);

        $this->actingAs($this->viewer)
            ->getJson('/api/v1/dashboard/stats')
            ->assertStatus(403);
    }

    public function test_folder_tree_is_nested(): void
    {
        $root  = Folder::create(['name' => 'Root', 'created_by' => $this->admin->id]);
        $child = Folder::create(['name' => 'Child', 'parent_id' => $root->id, 'created_by' => $this->admin->id]);
        Folder::create(['name' => 'Grandchild', 'parent_id' => $child->id, 'created_by' => $this->admin->id]);

        $this->actingAs($this->viewer)
            ->getJson('/api/v1/folders/tree')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')              // satu root
            ->assertJsonPath('data.0.name', 'Root')
            ->assertJsonCount(1, 'data.0.children')
            ->assertJsonPath('data.0.children.0.children.0.name', 'Grandchild');

        $this->assertTrue(Cache::has('docsy:folders:tree'));
    }

    public function test_activity_logs_requires_admin_and_is_fresh(): void
    {
        $folder = Folder::create(['name' => 'A', 'created_by' => $this->admin->id]);
        $file = File::create([
            'folder_id' => $folder->id,
            // never hardcode ids — PG sequences are not transactional
            'department_id' => \App\Models\Department::where('name', 'Finance')->first()->id,
            'user_id' => $this->admin->id,
            'title' => 'T',
            'original_name' => 't.pdf',
            'storage_path' => 'd/t.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 1,
        ]);
        \App\Jobs\RecordActivityLogJob::dispatchSync($file, 'upload_file');

        $this->actingAs($this->viewer)
            ->getJson('/api/v1/activity-logs')
            ->assertStatus(403);

        $this->actingAs($this->admin)
            ->getJson('/api/v1/activity-logs')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.action', 'upload_file')
            ->assertJsonPath('data.0.description', 'T')
            ->assertJsonStructure(['meta' => ['current_page', 'total']]);
    }

    public function test_departments_list_is_served_from_cache(): void
    {
        $this->actingAs($this->viewer)
            ->getJson('/api/v1/departments')
            ->assertStatus(200)
            ->assertJsonCount(4, 'data');

        $this->assertTrue(Cache::has('docsy:depts:list'));
    }
}
