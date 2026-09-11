<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\File;
use App\Models\Folder;
use Database\Seeders\DepartmentSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class CacheInvalidationTest extends TestCase
{
    use RefreshDatabase;

    private \App\Models\User $admin;
    private int $deptId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([UserSeeder::class, DepartmentSeeder::class]);
        $this->admin = \App\Models\User::whereEmail('admin@example.com')->first();
        $this->deptId = Department::where('name', 'Finance')->first()->id;
    }

    public function test_file_mutation_flushes_dashboard_stats(): void
    {
        // warm the cache the way the real endpoint does
        $this->actingAs($this->admin)->getJson('/api/v1/dashboard/stats')->assertStatus(200);
        $this->assertTrue(Cache::has('docsy:dash:stats'));

        $folder = Folder::create(['name' => 'X', 'created_by' => $this->admin->id]);
        File::create([
            'folder_id' => $folder->id,
            'department_id' => $this->deptId,
            'user_id' => $this->admin->id,
            'title' => 'T',
            'original_name' => 't.pdf',
            'storage_path' => 'd/t.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 1,
        ]);

        $this->assertFalse(Cache::has('docsy:dash:stats'));
    }

    public function test_folder_mutation_flushes_tree_and_stats(): void
    {
        $this->actingAs($this->admin)->getJson('/api/v1/folders/tree')->assertStatus(200);
        $this->assertTrue(Cache::has('docsy:folders:tree'));

        Folder::create(['name' => 'New', 'created_by' => $this->admin->id]);

        $this->assertFalse(Cache::has('docsy:folders:tree'));
    }

    public function test_soft_deleted_file_still_invalidates(): void
    {
        $folder = Folder::create(['name' => 'Y', 'created_by' => $this->admin->id]);
        $file = File::create([
            'folder_id' => $folder->id,
            'department_id' => $this->deptId,
            'user_id' => $this->admin->id,
            'title' => 'T',
            'original_name' => 't.pdf',
            'storage_path' => 'd/t.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 1,
        ]);
        $this->actingAs($this->admin)->getJson('/api/v1/dashboard/stats')->assertStatus(200);
        $this->assertTrue(Cache::has('docsy:dash:stats'));

        $file->delete(); // soft delete still fires the deleted event

        $this->assertFalse(Cache::has('docsy:dash:stats'));
    }

    public function test_department_mutation_flushes_list(): void
    {
        $this->actingAs($this->admin)->getJson('/api/v1/departments')->assertStatus(200);
        $this->assertTrue(Cache::has('docsy:depts:list'));

        Department::create(['name' => 'Marketing']);

        $this->assertFalse(Cache::has('docsy:depts:list'));
    }

    /**
     * Benchmark (task 4.5): cache hit must run ZERO database queries.
     */
    public function test_cached_stats_runs_no_queries_on_second_call(): void
    {
        $first = $this->actingAs($this->admin)->getJson('/api/v1/dashboard/stats')->assertStatus(200);

        \DB::enableQueryLog();
        $second = $this->actingAs($this->admin)->getJson('/api/v1/dashboard/stats')->assertStatus(200);
        $queries = \DB::getQueryLog();
        \DB::disableQueryLog();

        $this->assertSame(
            json_decode($first->getContent(), true)['data'],
            json_decode($second->getContent(), true)['data'],
            'cached response must be identical',
        );
        $this->assertEmpty($queries, 'cache hit must issue zero SQL queries');
    }
}
