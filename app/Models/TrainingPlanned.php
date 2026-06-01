<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrainingPlanned extends Model
{
    protected $table = 'training_planned';
    protected $fillable = ['machine_id', 'operator_id', 'planned_date', 'notes', 'completed', 'added_by_user_id'];
    protected $casts = ['planned_date' => 'date', 'completed' => 'boolean'];

    public function machine(): BelongsTo  { return $this->belongsTo(TrainingMachine::class, 'machine_id'); }
    public function operator(): BelongsTo { return $this->belongsTo(TrainingOperator::class, 'operator_id'); }
    public function addedBy(): BelongsTo  { return $this->belongsTo(User::class, 'added_by_user_id'); }
}
