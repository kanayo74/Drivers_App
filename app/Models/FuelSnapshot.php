<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class FuelSnapshot extends Model
{
    protected $fillable = [
        'vehicle_id','level_litres','level_percent',
        'estimated_empty_at','recorded_at',
    ];
    protected $casts = [
        'estimated_empty_at' => 'datetime',
        'recorded_at'        => 'datetime',
    ];
    public function vehicle() { return $this->belongsTo(Vehicle::class); }
}
