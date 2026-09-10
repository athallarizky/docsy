<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('folders', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            // Self-referencing FK: NULL = folder level root
            $table->foreignId('parent_id')
                ->nullable()
                ->constrained('folders')
                ->cascadeOnDelete();
            // Who created it — audit trail; RESTRICT: user cannot be deleted while owning folders
            $table->foreignId('created_by')
                ->constrained('users')
                ->restrictOnDelete();
            $table->softDeletes(); // deleted_at TIMESTAMP NULL column
            $table->timestamps();

            // Index for child lookups: WHERE parent_id = ? is instant
            $table->index('parent_id');
        });

        // Partial unique index: names are unique among live siblings.
        // COALESCE: NULL != NULL in PG unique indexes — anchor NULL to 0 so
        // root folders (NULL parent) are uniqueness-checked too.
        DB::statement(
            'CREATE UNIQUE INDEX folders_parent_name_unique
             ON folders (COALESCE(parent_id, 0), name)
             WHERE deleted_at IS NULL'
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('folders');
    }
};
