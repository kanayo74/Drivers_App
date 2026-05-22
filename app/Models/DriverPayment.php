<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class DriverPayment extends Model
{
    protected $fillable = [
        'driver_id','processed_by_id','trips_count','amount_per_trip',
        'total_amount','period_start','period_end','status',
        'paid_at','payment_reference','notes',
    ];
    protected $casts = [
        'period_start'    => 'date',
        'period_end'      => 'date',
        'paid_at'         => 'datetime',
        'total_amount'    => 'decimal:2',
        'amount_per_trip' => 'decimal:2',
    ];
    public function driver()      { return $this->belongsTo(User::class,'driver_id'); }
    public function processedBy() { return $this->belongsTo(User::class,'processed_by_id'); }
    public function isPaid(): bool { return $this->status === 'paid'; }
}
