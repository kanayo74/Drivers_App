<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class MaintenanceRecord extends Model
{
    protected $fillable = [
        'vehicle_id','service_type','service_date','next_service_date',
        'mileage_at_service','provider','cost','notes','status','logged_by_id',
    ];
    protected $casts = [
        'service_date'      => 'date',
        'next_service_date' => 'date',
        'cost'              => 'decimal:2',
    ];
    public function vehicle()  { return $this->belongsTo(Vehicle::class); }
    public function loggedBy() { return $this->belongsTo(User::class,'logged_by_id'); }
    public function isOverdue(): bool
    {
        return $this->next_service_date
            && $this->next_service_date->isPast()
            && $this->status !== 'completed';
    }
}
