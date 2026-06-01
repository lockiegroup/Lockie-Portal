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

    // Computed expiry from machine retrain_months + trained_date
    public function expiryDate(): ?\Carbon\Carbon
    {
        $months = $this->machine?->retrain_months;
        return $months ? $this->trained_date->copy()->addMonths($months) : null;
    }

    // Returns: 'valid' | 'expiring' | 'expired' | 'no_expiry'
    public function status(): string
    {
        $expiry = $this->expiryDate();
        if (!$expiry) return 'no_expiry';
        if ($expiry->isPast()) return 'expired';
        if (now()->diffInDays($expiry) <= 60) return 'expiring';
        return 'valid';
    }
}
