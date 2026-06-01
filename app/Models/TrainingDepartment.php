<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class TrainingDepartment extends Model
{
    protected $table    = 'training_departments';
    protected $fillable = ['name'];
}
