<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name', 'email', 'phone', 'employee_id', 'department',
        'role', 'avatar', 'password', 'fcm_token', 'is_active',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password'          => 'hashed',
        'is_active'         => 'boolean',
    ];

    // ── Role helpers ───────────────────────────────────────────────

    public function isAdmin(): bool    { return $this->role === 'admin'; }
    public function isDriver(): bool   { return $this->role === 'driver'; }
    public function isStaff(): bool    { return $this->role === 'staff'; }
    public function isMarketer(): bool { return $this->role === 'marketer'; }

    public function canBook(): bool
    {
        return in_array($this->role, ['admin', 'staff', 'marketer']);
    }

    // ── Relationships ──────────────────────────────────────────────

    public function driverProfile()
    {
        return $this->hasOne(DriverProfile::class);
    }

    public function assignedVehicle()
    {
        return $this->hasOne(Vehicle::class, 'assigned_driver_id');
    }

    public function bookedTrips()
    {
        return $this->hasMany(Trip::class, 'booked_by_id');
    }

    public function passengerTrips()
    {
        return $this->hasMany(Trip::class, 'passenger_id');
    }

    public function drivenTrips()
    {
        return $this->hasMany(Trip::class, 'driver_id');
    }

    public function fuelRequests()
    {
        return $this->hasMany(FuelRequest::class, 'driver_id');
    }

    public function ratings()
    {
        return $this->hasMany(DriverRating::class, 'driver_id');
    }

    public function ratingsGiven()
    {
        return $this->hasMany(DriverRating::class, 'rated_by_id');
    }

    public function trainingAssignments()
    {
        return $this->hasMany(TrainingAssignment::class, 'driver_id');
    }

    public function payments()
    {
        return $this->hasMany(DriverPayment::class, 'driver_id');
    }

    public function awards()
    {
        return $this->hasMany(DriverAward::class, 'driver_id');
    }

    // ── Computed attributes ────────────────────────────────────────

    public function getAverageRatingAttribute(): float
    {
        return round($this->ratings()->avg('rating') ?? 0, 1);
    }

    public function getOutstandingPaymentAttribute(): float
    {
        return (float) $this->payments()
            ->where('status', 'pending')
            ->sum('total_amount');
    }

    public function getTotalTripsAttribute(): int
    {
        return $this->drivenTrips()
            ->where('status', 'completed')
            ->count();
    }
}
