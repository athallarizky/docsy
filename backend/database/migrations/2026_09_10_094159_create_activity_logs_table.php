<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            // User dihapus → log TETAP hidup, user_id jadi NULL (sejarah tidak boleh ikut lenyap)
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 50);            // upload_file | download_file | delete_file | ...
            $table->string('entity_type', 100);      // App\Models\File, App\Models\Folder, ...
            $table->unsignedBigInteger('entity_id');
            $table->jsonb('metadata')->nullable();   // konteks ekstra (diff, nama file, dst.)
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            // Query dominan: "aktivitas entitas X" dan "riwayat user Y"
            $table->index(['entity_type', 'entity_id']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
