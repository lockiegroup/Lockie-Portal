<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClockingCategory extends Model
{
    protected $fillable = ['name', 'color'];

    public function employees(): HasMany
    {
        return $this->hasMany(ClockingEmployee::class, 'category_id');
    }

    public function alertRecipients(): HasMany
    {
        return $this->hasMany(ClockingAlertRecipient::class, 'category_id');
    }
}
