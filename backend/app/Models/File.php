<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class File extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'folder_id',
        'department_id',
        'user_id',
        'title',
        'original_name',
        'storage_path',
        'mime_type',
        'file_size',
    ];

    public function folder(): BelongsTo
    {
        return $this->belongsTo(Folder::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Kolom DB-nya `user_id`; relasi dinamai `uploader`
     * supaya API kontrak bisa ekspos "uploaded_by".
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Kolom virtual: bytes → "2.34 MB". Computed property-nya model.
     */
    protected function fileSizeFormatted(): Attribute
    {
        return Attribute::get(function () {
            $bytes = $this->file_size;
            $units = ['B', 'KB', 'MB', 'GB', 'TB'];
            $i = 0;

            while ($bytes >= 1024 && $i < count($units) - 1) {
                $bytes /= 1024;
                $i++;
            }

            return round($bytes, 2) . ' ' . $units[$i];
        });
    }
}
