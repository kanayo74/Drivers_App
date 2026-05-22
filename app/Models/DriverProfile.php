<?php
// ═══════════════════════════════════════════════════════
// App\Models\DriverProfile
// ═══════════════════════════════════════════════════════
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DriverProfile extends Model
{
    protected $fillable = [
        'user_id', 'license_number', 'license_expiry', 'license_class',
        'years_experience', 'guarantor_name', 'guarantor_phone',
        'per_trip_rate', 'status',
    ];

    protected $casts = [
        'license_expiry' => 'date',
        'per_trip_rate'  => 'decimal:2',
    ];

    public function driver()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function isAvailable(): bool
    {
        return $this->status === 'available';
    }
}
