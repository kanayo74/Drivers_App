<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\{Trip, DriverRating};
use Illuminate\Http\Request;

class RatingController extends Controller
{
    public function store(Request $request, Trip $trip)
    {
        if ($trip->passenger_id !== auth()->id()) {
            abort(403, 'You can only rate trips you were a passenger on.');
        }

        if (!$trip->isCompleted()) {
            return back()->with('error', 'You can only rate completed trips.');
        }

        if ($trip->rating()->exists()) {
            return back()->with('error', 'You have already rated this trip.');
        }

        $validated = $request->validate([
            'rating'  => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:500',
        ], [
            'rating.required' => 'Please select a star rating before submitting.',
        ]);

        DriverRating::create([
            'trip_id'     => $trip->id,
            'driver_id'   => $trip->driver_id,
            'rated_by_id' => auth()->id(),
            'rating'      => $validated['rating'],
            'comment'     => $validated['comment'] ?? null,
        ]);

        $stars = str_repeat('★', $validated['rating']) . str_repeat('☆', 5 - $validated['rating']);

        return back()->with('success',
            "Thank you! You rated {$trip->driver->name} {$stars} ({$validated['rating']}/5)."
        );
    }
}
