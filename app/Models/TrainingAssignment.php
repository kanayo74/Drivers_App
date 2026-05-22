<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class TrainingAssignment extends Model
{
    protected $fillable = [
        'driver_id','assigned_by_id','training_type','provider',
        'training_date','duration_days','notes','status',
    ];
    protected $casts = ['training_date' => 'date'];
    public function driver()     { return $this->belongsTo(User::class,'driver_id'); }
    public function assignedBy() { return $this->belongsTo(User::class,'assigned_by_id'); }
}
