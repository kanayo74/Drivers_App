<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class DriverRating extends Model
{
    protected $fillable = ['trip_id','driver_id','rated_by_id','rating','comment'];
    public function trip()    { return $this->belongsTo(Trip::class); }
    public function driver()  { return $this->belongsTo(User::class,'driver_id'); }
    public function ratedBy() { return $this->belongsTo(User::class,'rated_by_id'); }
}
