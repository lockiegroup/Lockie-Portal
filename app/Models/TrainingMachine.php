<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TrainingMachine extends Model
{
    protected $table = 'training_machines';
    protected $fillable = ['name', 'category', 'description', 'retrain_months', 'active'];
    protected $casts = ['active' => 'boolean'];

    public function records(): HasMany { return $this->hasMany(TrainingRecord::class, 'machine_id'); }
    public function planned(): HasMany  { return $this->hasMany(TrainingPlanned::class, 'machine_id'); }
}
