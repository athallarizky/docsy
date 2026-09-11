<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Generated STORED column: tokenized ONCE at write time.
        // coalesce guards NULL columns (NULL || x = NULL in SQL);
        // translate turns filename separators into spaces — PG's default
        // parser treats 'financial_notes.txt' as ONE file-ish token, so the
        // word 'financial' would never stand alone and would not match.
        DB::statement(
            "ALTER TABLE files
             ADD COLUMN search_vector tsvector
             GENERATED ALWAYS AS (
                 to_tsvector('english',
                     coalesce(title, '') || ' ' ||
                     translate(coalesce(original_name, ''), '_.-', '   '))
             ) STORED"
        );

        // Inverted index over the vector — word -> rows
        DB::statement(
            'CREATE INDEX idx_files_search_vector
             ON files USING GIN (search_vector)'
        );
    }

    public function down(): void
    {
        // the GIN index dies with its column — no separate drop needed
        DB::statement('ALTER TABLE files DROP COLUMN search_vector');
    }
};
