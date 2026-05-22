<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class FuelRequest extends Model
{
    protected $fillable = [
        'vehicle_id','driver_id','current_level_litres',
        'current_level_percent','notes','status',
        'acknowledged_by_id','acknowledged_at',
    ];
    protected $casts = ['acknowledged_at' => 'datetime'];
    public function vehicle()        { return $this->belongsTo(Vehicle::class); }
    public function driver()         { return $this->belongsTo(User::class,'driver_id'); }
    public function acknowledgedBy() { return $this->belongsTo(User::class,'acknowledged_by_id'); }
    public function fuelLog()        { return $this->hasOne(FuelLog::class); }
    public function isPending(): bool { return $this->status === 'pending'; }
}
