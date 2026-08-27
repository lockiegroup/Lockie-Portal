<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RackingItem extends Model
{
    protected $fillable = [
        'bay', 'division', 'description', 'pallet_ref',
        'quantity', 'date_stored', 'is_unusable',
        'for_outside_storage', 'sort_order', 'notes',
    ];

    protected $casts = [
        'date_stored'          => 'date',
        'is_unusable'          => 'boolean',
        'for_outside_storage'  => 'boolean',
    ];

    public static function bays(): array
    {
        $bays = [];
        foreach (range('A', 'H') as $letter) {
            foreach (range(1, 3) as $level) {
                $bays[] = $letter . $level;
            }
        }
        return $bays;
    }
}
