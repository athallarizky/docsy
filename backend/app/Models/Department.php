<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
  protected $fillable = [
    'name',
    'description'
  ];

  public function files(): HasMany
  {
    return $this->hasMany(File::class);
  }
}
