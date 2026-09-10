<?php

namespace Tests\Feature;

use App\Models\Folder;
use App\Models\User;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FolderHierarchyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(UserSeeder::class);
    }

    private function admin(): User
    {
        return User::whereEmail('admin@example.com')->first();
    }

    private function viewer(): User
    {
        return User::whereEmail('viewer@example.com')->first();
    }

    /** Build a root→lvl5 chain via API: Company/Finance/2026/Q1/January */
    private function buildFiveLevelChain(): array
    {
        $names  = ['Company', 'Finance', '2026', 'Q1', 'January'];
        $ids    = [];
        $parentId = null;

        foreach ($names as $name) {
            $response = $this->actingAs($this->admin())
                ->postJson('/api/v1/folders', [
                    'name'      => $name,
                    'parent_id' => $parentId,
                ]);

            $response->assertStatus(201);
            $parentId = $response->json('data.id');
            $ids[]    = $parentId;
        }

        return $ids; // [root, lvl2, lvl3, lvl4, lvl5]
    }

    public function test_can_nest_folders_five_levels_deep(): void
    {
        $ids = $this->buildFiveLevelChain();

        $this->assertCount(5, $ids);

        // Parents are wired correctly: root has none, the rest are chained
        $this->assertDatabaseHas('folders', ['id' => $ids[0], 'parent_id' => null]);
        $this->assertDatabaseHas('folders', ['id' => $ids[4], 'parent_id' => $ids[3]]);
    }

    public function test_breadcrumbs_return_root_to_child_in_one_chain(): void
    {
        $ids = $this->buildFiveLevelChain();
        $deepest = end($ids);

        $response = $this->actingAs($this->viewer())
            ->getJson("/api/v1/folders/{$deepest}/breadcrumbs");

        $response->assertStatus(200)
            ->assertJsonCount(5, 'data')
            // Root→child order: first element is root, last is the requested folder
            ->assertJsonPath('data.0.name', 'Company')
            ->assertJsonPath('data.0.parent_id', null)
            ->assertJsonPath('data.4.name', 'January')
            ->assertJsonPath('data.4.id', $deepest);
    }

    public function test_breadcrumbs_of_root_folder_is_single_item(): void
    {
        $ids = $this->buildFiveLevelChain();

        $this->actingAs($this->viewer())
            ->getJson("/api/v1/folders/{$ids[0]}/breadcrumbs")
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Company');
    }

    public function test_moving_folder_into_its_own_descendant_is_rejected(): void
    {
        $ids = $this->buildFiveLevelChain();
        [$root, $finance, $y2026, $q1, $january] = $ids;

        // Move "Finance" (lvl2) INTO "January" (lvl5, its descendant) → 422
        $this->actingAs($this->admin())
            ->putJson("/api/v1/folders/{$finance}", [
                'name'      => 'Finance',
                'parent_id' => $january,
            ])
            ->assertStatus(422)
            ->assertJsonPath('success', false);

        // Finance must NOT move — data intact
        $this->assertDatabaseHas('folders', ['id' => $finance, 'parent_id' => $root]);
    }

    public function test_moving_folder_into_its_own_descendant_via_self_is_rejected(): void
    {
        $ids = $this->buildFiveLevelChain();
        [$root, $finance] = $ids;

        // Move the folder INTO itself → 422
        $this->actingAs($this->admin())
            ->putJson("/api/v1/folders/{$finance}", [
                'name'      => 'Finance',
                'parent_id' => $finance,
            ])
            ->assertStatus(422);
    }

    public function test_valid_move_to_sibling_position_succeeds(): void
    {
        $ids = $this->buildFiveLevelChain();
        [$root, $finance, $y2026] = $ids;

        // "2026" moves directly under root — not its own descendant → valid
        $this->actingAs($this->admin())
            ->putJson("/api/v1/folders/{$y2026}", [
                'name'      => '2026',
                'parent_id' => $root,
            ])
            ->assertStatus(200);

        $this->assertDatabaseHas('folders', ['id' => $y2026, 'parent_id' => $root]);
        // Its children follow (still attached to 2026)
        $this->assertDatabaseHas('folders', ['name' => 'Q1', 'parent_id' => $y2026]);
    }

    public function test_deleting_folder_soft_deletes_entire_subtree(): void
    {
        $ids = $this->buildFiveLevelChain();
        [$root, $finance] = $ids;

        $this->actingAs($this->admin())
            ->deleteJson("/api/v1/folders/{$finance}")
            ->assertStatus(200);

        // The subtree (Finance..January) is soft-deleted; root stays alive
        foreach (['Finance', '2026', 'Q1', 'January'] as $name) {
            $this->assertSoftDeleted('folders', ['name' => $name]);
        }
        $this->assertDatabaseHas('folders', ['id' => $root, 'deleted_at' => null]);

        // Soft delete = rows STILL exist in the DB, just flagged
        $this->assertDatabaseCount('folders', 5);
    }

    public function test_soft_deleted_folders_disappear_from_listing_and_cannot_be_parent(): void
    {
        $ids = $this->buildFiveLevelChain();
        [$root, $finance] = $ids;

        Folder::find($finance)->delete();

        // Gone from the listing
        $this->actingAs($this->viewer())
            ->getJson('/api/v1/folders?parent_id=' . $root)
            ->assertStatus(200)
            ->assertJsonCount(0, 'data');

        // Cannot become a parent (exists + whereNull rejects soft-deleted rows)
        $this->actingAs($this->admin())
            ->postJson('/api/v1/folders', [
                'name'      => 'Under Ghost',
                'parent_id' => $finance,
            ])
            ->assertStatus(422);
    }

    public function test_viewer_cannot_create_but_can_read_folders(): void
    {
        $this->actingAs($this->admin())
            ->postJson('/api/v1/folders', ['name' => 'Company'])
            ->assertStatus(201);

        $this->actingAs($this->viewer())
            ->getJson('/api/v1/folders')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data');

        $this->actingAs($this->viewer())
            ->postJson('/api/v1/folders', ['name' => 'Nope'])
            ->assertStatus(403);
    }

    public function test_duplicate_sibling_name_is_rejected_but_same_name_elsewhere_ok(): void
    {
        $ids = $this->buildFiveLevelChain();
        [$root, $finance] = $ids;

        // Duplicate SIBLING name (both root children) → rejected by validation (422)
        $this->actingAs($this->admin())
            ->postJson('/api/v1/folders', ['name' => 'Company', 'parent_id' => null])
            ->assertStatus(422)
            ->assertJsonValidationErrors('name');

        // Same name in a DIFFERENT branch (child of Finance) → valid
        $this->actingAs($this->admin())
            ->postJson('/api/v1/folders', ['name' => 'Company', 'parent_id' => $finance])
            ->assertStatus(201);
    }

    /**
     * Regression test: routes were once registered OUTSIDE the auth:sanctum
     * group — anonymous requests returned 200 instead of 401. actingAs() in
     * tests masked the hole (default guard), so this must be asserted cold.
     */
    public function test_folder_and_department_endpoints_require_authentication(): void
    {
        $this->getJson('/api/v1/folders')->assertStatus(401);
        $this->getJson('/api/v1/folders/1/breadcrumbs')->assertStatus(401);
        $this->getJson('/api/v1/departments')->assertStatus(401);
        $this->postJson('/api/v1/folders', ['name' => 'X'])->assertStatus(401);
    }
}
