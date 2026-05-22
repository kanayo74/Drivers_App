<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class FuelLog extends Model
{
    protected $fillable = [
        'vehicle_id','driver_id','fuel_request_id','fill_type',
        'litres_added','level_before','level_after',
        'estimated_hours_remaining','estimated_empty_at','cost',
    ];
    protected $casts = ['estimated_empty_at' => 'datetime'];
    public function vehicle()     { return $this->belongsTo(Vehicle::class); }
    public function driver()      { return $this->belongsTo(User::class,'driver_id'); }
    public function fuelRequest() { return $this->belongsTo(FuelRequest::class); }
}
