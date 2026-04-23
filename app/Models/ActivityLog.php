<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    public $timestamps = false;

    protected $fillable = ['user_id', 'action', 'description', 'ip_address', 'created_at'];

    protected $casts = ['created_at' => 'datetime'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function record(string $action, string $description, ?int $userId = null): void
    {
        static::create([
            'user_id'    => $userId ?? auth()->id(),
            'action'     => $action,
            'description'=> $description,
            'ip_address' => request()->ip(),
            'created_at' => now(),
        ]);
    }
}
