<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\User;
use Database\Seeders\DepartmentSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class DepartmentTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([UserSeeder::class, DepartmentSeeder::class]);
    }

    private function admin(): User
    {
        return User::whereEmail('admin@example.com')->first();
    }

    private function viewer(): User
    {
        return User::whereEmail('viewer@example.com')->first();
    }

    public function test_any_authenticated_user_can_list_departments(): void
    {
        $this->actingAs($this->viewer())
            ->getJson('/api/v1/departments')
            ->assertStatus(200)
            ->assertJsonCount(4, 'data')
            ->assertJsonStructure(['data' => [['id', 'name', 'description']]]);
    }

    public function test_admin_can_create_department(): void
    {
        $response = $this->actingAs($this->admin())
            ->postJson('/api/v1/departments', [
                'name'        => 'Marketing',
                'description' => 'Brand & campaign assets',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Marketing');

        $this->assertDatabaseHas('departments', ['name' => 'Marketing']);
    }

    public function test_viewer_cannot_create_department(): void
    {
        $this->actingAs($this->viewer())
            ->postJson('/api/v1/departments', [
                'name' => 'Marketing',
            ])
            ->assertStatus(403);

        $this->assertDatabaseCount('departments', 4);
    }

    public function test_admin_can_update_department_without_renaming(): void
    {
        $dept = Department::where('name', 'IT')->first();

        // update description TANPA mengubah name —
        // tanpa ->ignore() di rule unique, test ini gagal 422
        $this->actingAs($this->admin())
            ->putJson("/api/v1/departments/{$dept->id}", [
                'name'        => 'IT',
                'description' => 'Infra, platform & SRE docs',
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.description', 'Infra, platform & SRE docs');
    }

    public function test_viewer_cannot_update_department(): void
    {
        $dept = Department::first();

        $this->actingAs($this->viewer())
            ->putJson("/api/v1/departments/{$dept->id}", [
                'name' => 'Renamed',
            ])
            ->assertStatus(403);
    }

    public function test_admin_can_delete_department(): void
    {
        $dept = Department::where('name', 'Legal')->first();

        $this->actingAs($this->admin())
            ->deleteJson("/api/v1/departments/{$dept->id}")
            ->assertStatus(200);

        $this->assertDatabaseMissing('departments', ['id' => $dept->id]);
    }

    public function test_viewer_cannot_delete_department(): void
    {
        $dept = Department::first();

        $this->actingAs($this->viewer())
            ->deleteJson("/api/v1/departments/{$dept->id}")
            ->assertStatus(403);
    }

    public function test_duplicate_name_is_rejected(): void
    {
        $this->actingAs($this->admin())
            ->postJson('/api/v1/departments', [
                'name' => 'Finance', // sudah ada dari seeder
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('name');
    }

    public function test_missing_department_returns_404(): void
    {
        $this->actingAs($this->admin())
            ->getJson('/api/v1/departments/9999')
            ->assertStatus(404);
    }
}