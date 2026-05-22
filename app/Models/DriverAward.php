<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class DriverAward extends Model
{
    protected $fillable = [
        'driver_id','award_type','year','month',
        'average_rating','total_trips','score','notes',
    ];
    public function driver() { return $this->belongsTo(User::class,'driver_id'); }
    public function getDisplayTitleAttribute(): string
    {
        if ($this->award_type === 'driver_of_year') {
            return "Driver of the Year {$this->year}";
        }
        $monthName = \Carbon\Carbon::create()->month($this->month)->format('F');
        return "Driver of the Month — {$monthName} {$this->year}";
    }
}
