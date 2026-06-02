<?php
namespace App\Services;

use Illuminate\Support\Carbon;
use App\Models\Trip;
use Illuminate\Support\Collection;

class WeekendPaymentService
{
    /**
     * Calculate payment for a single trip, adding a weekend premium when applicable.
     *
     * @param  Trip  $trip
     * @param  int   $baseRate  Base per-trip rate (optional)
     * @param  int   $weekendPremium  Extra amount to add when trip is on weekend (default 500)
     * @return int
     */
    public function calculateForTrip(Trip $trip, int $baseRate = 0, int $weekendPremium = 500): int
    {
        $scheduled = $trip->scheduled_at ? Carbon::parse($trip->scheduled_at) : Carbon::now();
        $isWeekend = $scheduled->isWeekend(); // Saturday/Sunday
        return $baseRate + ($isWeekend ? $weekendPremium : 0);
    }

    /**
     * Calculate total payments for a collection of trips.
     *
     * @param  Collection|array  $trips
     * @param  int  $baseRate
     * @param  int  $weekendPremium
     * @return int
     */
    public function calculateTotal($trips, int $baseRate = 0, int $weekendPremium = 500): int
    {
        $collection = $trips instanceof Collection ? $trips : collect($trips);
        return (int) $collection->sum(fn(Trip $trip) => $this->calculateForTrip($trip, $baseRate, $weekendPremium));
    }
       // ...existing code...
    /**
     * Determine and optionally persist whether a trip is eligible for weekend payment.
     *
     * Returns true if the trip is on a weekend, false otherwise.
     * If the Trip model contains a 'payment_eligible' attribute, it will be updated.
     *
     * @param Trip $trip
     * @return bool
     */
   public function markTripPaymentEligibility(Trip $trip): bool
   {
       $scheduled = $trip->scheduled_at ? Carbon::parse($trip->scheduled_at) : Carbon::now();
       $isWeekend = $scheduled->isWeekend();

       // If the trip model has a payment_eligible attribute, persist the value.
       $attributes = $trip->getAttributes();
       if (array_key_exists('payment_eligible', $attributes) || in_array('payment_eligible', $trip->getFillable())) {
           $trip->payment_eligible = $isWeekend;
           $trip->save();
       }

       return $isWeekend;
   }
    
}