<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Trip extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'trip_code', 'booked_by_id', 'passenger_id', 'driver_id',
        'vehicle_id', 'reason', 'destination', 'pickup_location',
        'scheduled_at', 'started_at', 'completed_at', 'status',
        'rejection_reason', 'approved_by_id', 'approved_at', 'payment_processed',
    ];

    protected $casts = [
        'scheduled_at'      => 'datetime',
        'started_at'        => 'datetime',
        'completed_at'      => 'datetime',
        'approved_at'       => 'datetime',
        'payment_processed' => 'boolean',
    ];

    // ─── Boot ──────────────────────────────────────────────────────────

    protected static function booted(): void
    {
        static::creating(function (Trip $trip) {
            $trip->trip_code = static::generateCode();
        });
    }

    public static function generateCode(): string
    {
        $last = static::orderByDesc('id')->value('id') ?? 0;
        return 'TR-' . str_pad($last + 1, 4, '0', STR_PAD_LEFT);
    }

    // ─── Relationships ─────────────────────────────────────────────────

    public function bookedBy()
    {
        return $this->belongsTo(User::class, 'booked_by_id');
    }

    public function passenger()
    {
        return $this->belongsTo(User::class, 'passenger_id');
    }

    public function driver()
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by_id');
    }

    public function locations()
    {
        return $this->hasMany(TripLocation::class)->orderBy('sequence');
    }

    public function rating()
    {
        return $this->hasOne(DriverRating::class);
    }

    // ─── Scopes ────────────────────────────────────────────────────────

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeToday($query)
    {
        return $query->whereDate('scheduled_at', today());
    }

    public function scopeUnpaid($query)
    {
        return $query->where('status', 'completed')->where('payment_processed', false);
    }

    // ─── Helpers ───────────────────────────────────────────────────────

    public function isPending(): bool    { return $this->status === 'pending'; }
    public function isApproved(): bool   { return $this->status === 'approved'; }
    public function isCompleted(): bool  { return $this->status === 'completed'; }

    public function canBeRated(): bool
    {
        return $this->isCompleted() && !$this->rating;
    }
}
