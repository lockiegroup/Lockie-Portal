<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrainingOperator extends Model
{
    protected $table = 'training_operators';
    protected $fillable = ['name', 'employee_code', 'department', 'email', 'user_id', 'active'];
    protected $casts = ['active' => 'boolean'];

    public function records(): HasMany  { return $this->hasMany(TrainingRecord::class, 'operator_id'); }
    public function planned(): HasMany  { return $this->hasMany(TrainingPlanned::class, 'operator_id'); }
    public function user(): BelongsTo   { return $this->belongsTo(User::class); }
}
