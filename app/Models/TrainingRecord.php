<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class TrainingRecord extends Model
{
    protected $table = 'training_records';
    protected $fillable = ['machine_id', 'operator_id', 'trained_date', 'expiry_date', 'pdf_path', 'pdf_original_name', 'added_by_user_id', 'notes'];
    protected $casts = ['trained_date' => 'date', 'expiry_date' => 'date'];

    public function machine(): BelongsTo  { return $this->belongsTo(TrainingMachine::class, 'machine_id'); }
    public function operator(): BelongsTo { return $this->belongsTo(TrainingOperator::class, 'operator_id'); }
    public function addedBy(): BelongsTo  { return $this->belongsTo(User::class, 'added_by_user_id'); }

    // Returns: 'valid' | 'expiring' | 'expired' | 'no_expiry'
    public function status(): string
    {
        if (!$this->expiry_date) return 'no_expiry';
        if ($this->expiry_date->isPast()) return 'expired';
        if ($this->expiry_date->diffInDays(now()) <= 60) return 'expiring';
        return 'valid';
    }
}
