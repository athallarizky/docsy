<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\File;
use App\Models\Folder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FileSearchTest extends TestCase
{
    use RefreshDatabase;

    private \App\Models\User $viewer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(UserSeeder::class);
        $this->viewer = \App\Models\User::whereEmail('viewer@example.com')->first();

        // NEVER hardcode ids: PG sequences are not transactional (sprint-2
        // lesson) — the seeded admin is only id 1 in the very first test.
        $admin = \App\Models\User::whereEmail('admin@example.com')->first();

        $folder = Folder::create(['name' => 'Docs', 'created_by' => $admin->id]);
        $dept   = Department::create(['name' => 'Finance']);

        File::create([
            'folder_id' => $folder->id, 'department_id' => $dept->id, 'user_id' => $admin->id,
            'title'         => 'Quarterly Financial Report',
            'original_name' => 'q3_financial_report.pdf',
            'storage_path'  => 'documents/x.pdf', 'mime_type' => 'application/pdf', 'file_size' => 1,
        ]);
        File::create([
            'folder_id' => $folder->id, 'department_id' => $dept->id, 'user_id' => $admin->id,
            'title'         => 'Onboarding Guide',
            'original_name' => 'onboarding.pdf',
            'storage_path'  => 'documents/y.pdf', 'mime_type' => 'application/pdf', 'file_size' => 1,
        ]);
        File::create([
            'folder_id' => $folder->id, 'department_id' => $dept->id, 'user_id' => $admin->id,
            'title'         => 'Tax Summary',
            'original_name' => 'financial_notes.txt', // match via FILE NAME, not title
            'storage_path'  => 'documents/z.txt', 'mime_type' => 'text/plain', 'file_size' => 1,
        ]);
    }

    public function test_search_matches_title_and_original_name(): void
    {
        $this->actingAs($this->viewer)
            ->getJson('/api/v1/files?search=financial')
            ->assertStatus(200)
            ->assertJsonCount(2, 'data'); // title match + original_name match
    }

    public function test_search_is_stemmed(): void
    {
        // verified via psql: 'financial' & 'financially' both stem to 'financi'
        // (but 'financing' stems to 'financ' — different!)
        $this->actingAs($this->viewer)
            ->getJson('/api/v1/files?search=financially')
            ->assertJsonCount(2, 'data');
    }

    public function test_multiword_search_is_anded(): void
    {
        // 'financial report' → financial & report → only file 1
        $this->actingAs($this->viewer)
            ->getJson('/api/v1/files?search=financial report')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Quarterly Financial Report');
    }

    public function test_search_without_match_returns_empty(): void
    {
        $this->actingAs($this->viewer)
            ->getJson('/api/v1/files?search=zebraunicorn')
            ->assertStatus(200)
            ->assertJsonCount(0, 'data');
    }

    public function test_listing_without_search_still_recency_ordered(): void
    {
        $this->actingAs($this->viewer)
            ->getJson('/api/v1/files')
            ->assertStatus(200)
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('data.0.title', 'Tax Summary'); // newest first
    }
}
