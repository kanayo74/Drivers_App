<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class TripLocation extends Model
{
    protected $fillable = [
        'trip_id','location_name','latitude','longitude',
        'sequence','direction','arrived_at',
    ];
    protected $casts = ['arrived_at' => 'datetime'];
    public function trip() { return $this->belongsTo(Trip::class); }
}
