<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\{Trip, User, Vehicle, DriverRating};
use App\Notifications\{TripRequestNotification, NewTripBookingNotification};
use Illuminate\Http\Request;

// ═══════════════════════════════════════════════════════════════════
// StaffTripController
// ═══════════════════════════════════════════════════════════════════
class StaffTripController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

        $query = Trip::where(function ($q) use ($user) {
                $q->where('passenger_id', $user->id)
                  ->orWhere('booked_by_id', $user->id);
            })
            ->with(['driver', 'vehicle', 'rating'])
            ->when($request->search, fn($q) =>
                $q->where(function ($inner) use ($request) {
                    $inner->where('trip_code', 'like', '%'.$request->search.'%')
                          ->orWhere('reason', 'like', '%'.$request->search.'%')
                          ->orWhere('destination', 'like', '%'.$request->search.'%')
                          ->orWhereHas('driver', fn($d) =>
                              $d->where('name', 'like', '%'.$request->search.'%')
                          );
                })
            )
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->latest();

        $trips = $query->paginate(15);

        // Trips that are completed but not rated yet (for the banner)
        $unratedTrips = Trip::where('passenger_id', $user->id)
            ->where('status', 'completed')
            ->whereDoesntHave('rating')
            ->with('driver')
            ->latest()
            ->take(5)
            ->get();

        // Active trip
        $activeTrip = Trip::where('passenger_id', $user->id)
            ->where('status', 'in_progress')
            ->with(['driver', 'vehicle'])
            ->first();

        // Drivers & vehicles for booking modal
        $drivers = User::where('role', 'driver')
            ->where('is_active', true)
            ->with(['driverProfile', 'assignedVehicle'])
            ->get()
            ->map(function ($d) {
                $d->average_rating = round($d->ratings()->avg('rating') ?? 0, 1);
                return $d;
            });

        $vehicles = Vehicle::where('status', 'active')->get();

        // Summary stats
        $stats = [
            'total'           => Trip::where('passenger_id', $user->id)->count(),
            'pending'         => Trip::where('passenger_id', $user->id)->where('status','pending')->count(),
            'completed'       => Trip::where('passenger_id', $user->id)->where('status','completed')->count(),
            'completed_month' => Trip::where('passenger_id', $user->id)
                                     ->where('status','completed')
                                     ->whereMonth('completed_at', now()->month)
                                     ->count(),
            'unrated'         => $unratedTrips->count(),
        ];

        return view('staff.trips.index', compact(
            'trips', 'unratedTrips', 'activeTrip', 'drivers', 'vehicles', 'stats'
        ));
    }

    // ─── Store new booking ──────────────────────────────────────────
    public function store(Request $request)
    {
        $user = auth()->user();

        // Validation rules — marketers need a longer reason
        $minReason = $user->isMarketer() ? 20 : 10;

        $validated = $request->validate([
            'driver_id'    => ['required', 'exists:users,id', function ($attr, $val, $fail) {
                $driver = User::find($val);
                if (!$driver || $driver->role !== 'driver') $fail('Invalid driver selected.');
                if ($driver && !$driver->is_active)         $fail('This driver is not active.');
            }],
            'vehicle_id'   => 'required|exists:vehicles,id',
            'reason'       => "required|string|min:{$minReason}",
            'destination'  => 'nullable|string|max:255',
            'scheduled_at' => 'required|date|after:now',
        ], [
            'reason.min'       => "Please provide at least {$minReason} characters for your trip reason." . ($user->isMarketer() ? ' Marketers must be specific.' : ''),
            'scheduled_at.after' => 'The trip must be scheduled for a future date and time.',
        ]);

        // Prevent double-booking on same time with same driver
        $conflict = Trip::where('driver_id', $validated['driver_id'])
            ->whereIn('status', ['pending','approved','in_progress'])
            ->whereDate('scheduled_at', date('Y-m-d', strtotime($validated['scheduled_at'])))
            ->exists();

        if ($conflict) {
            return back()
                ->withInput()
                ->withErrors(['driver_id' => 'This driver already has a booking on that date. Please choose another driver or time.']);
        }

        $trip = Trip::create([
            ...$validated,
            'booked_by_id' => $user->id,
            'passenger_id' => $user->id,
            'status'       => 'pending',
        ]);

        // Notify the selected driver
        $trip->driver->notify(new TripRequestNotification($trip));

        // Notify all admins
        User::where('role', 'admin')->each(fn($admin) =>
            $admin->notify(new NewTripBookingNotification($trip))
        );

        return redirect()
            ->route('staff.trips.index')
            ->with('success', "Trip #{$trip->trip_code} submitted successfully. Awaiting admin approval — you'll be notified once approved.");
    }

    // ─── Cancel a pending trip ──────────────────────────────────────
    public function cancel(Request $request, Trip $trip)
    {
        // Only the passenger who booked can cancel
        if ($trip->passenger_id !== auth()->id() && $trip->booked_by_id !== auth()->id()) {
            abort(403, 'You cannot cancel this trip.');
        }

        if (!$trip->isPending()) {
            return back()->with('error', 'Only pending trips can be cancelled. Contact admin for approved trips.');
        }

        $trip->update(['status' => 'cancelled']);

        // Notify driver the trip was cancelled
        $trip->driver->notify(new \App\Notifications\TripCancelledNotification($trip));

        return back()->with('success', "Trip #{$trip->trip_code} has been cancelled.");
    }
}

// ═══════════════════════════════════════════════════════════════════
// RatingController — staff rates driver after a completed trip
// ═══════════════════════════════════════════════════════════════════
class RatingController extends Controller
{
    public function store(Request $request, Trip $trip)
    {
        // Only the trip's passenger can rate
        if ($trip->passenger_id !== auth()->id()) {
            abort(403, 'You can only rate trips you were a passenger on.');
        }

        if (!$trip->isCompleted()) {
            return back()->with('error', 'You can only rate completed trips.');
        }

        // One rating per trip per user
        if ($trip->rating()->exists()) {
            return back()->with('error', 'You have already rated this trip.');
        }

        $validated = $request->validate([
            'rating'  => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:500',
        ], [
            'rating.required' => 'Please select a star rating before submitting.',
            'rating.min'      => 'Rating must be at least 1 star.',
            'rating.max'      => 'Rating cannot exceed 5 stars.',
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
            "Thank you! You rated {$trip->driver->name} {$stars} ({$validated['rating']}/5). Your rating contributes to the monthly driver award."
        );
    }
}
