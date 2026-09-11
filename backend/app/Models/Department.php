<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;  # note: Eloquent\Attributes, not Database\Attributes
use App\Observers\DepartmentObserver;

#[ObservedBy(DepartmentObserver::class)]
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
