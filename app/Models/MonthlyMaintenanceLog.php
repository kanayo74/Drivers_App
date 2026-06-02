<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class MonthlyMaintenanceLog extends Model
{
    protected $fillable = [
        'vehicle_id','driver_id','log_date','month','year','status','done_at','notes',
    ];
    protected $casts = ['log_date' => 'date', 'done_at' => 'datetime'];

    public function vehicle() { return $this->belongsTo(Vehicle::class); }
    public function driver()  { return $this->belongsTo(User::class, 'driver_id'); }
    public function isDone(): bool { return $this->status === 'done'; }
}