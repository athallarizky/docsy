<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('files', function (Blueprint $table) {
            $table->id();
            // FK RESTRICT x3: folder/department/user TIDAK BISA dihapus selama punya file.
            // File adalah data paling berharga — putus hubungan diam-diam itu berbahaya.
            $table->foreignId('folder_id')->constrained('folders')->restrictOnDelete();
            $table->foreignId('department_id')->constrained('departments')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();

            $table->string('title');                  // display title (bisa diedit user)
            $table->string('original_name');          // nama saat upload (mis. report.pdf)
            $table->string('storage_path', 500);      // path di storage privat — JANGAN pernah bocor ke response
            $table->string('mime_type', 150);
            $table->unsignedBigInteger('file_size');  // bytes mentah, integer murni
            $table->softDeletes();
            $table->timestamps();

            // Single-column FK indexes (PG tidak otomatis bikin index untuk FK)
            $table->index('folder_id');
            $table->index('department_id');
            $table->index('user_id');
        });

        // Composite PARTIAL indexes — masing-masing dirancang menghadapi satu query:
        // buka folder  : WHERE folder_id=? AND deleted_at IS NULL ORDER BY created_at DESC
        // filter dept  : WHERE department_id=? AND deleted_at IS NULL ORDER BY created_at DESC
        DB::statement(
            'CREATE INDEX idx_files_folder_pagination
             ON files (folder_id, created_at DESC)
             WHERE deleted_at IS NULL'
        );
        DB::statement(
            'CREATE INDEX idx_files_department_listing
             ON files (department_id, created_at DESC)
             WHERE deleted_at IS NULL'
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('files');
    }
};
