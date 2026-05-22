<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{TrainingAssignment, User};
use App\Notifications\TrainingAssignedNotification;
use Illuminate\Http\Request;

class TrainingController extends Controller
{
    public function index()
    {
        $assignments = TrainingAssignment::with(['driver', 'assignedBy'])
            ->orderByDesc('training_date')
            ->paginate(20);

        $drivers = User::where('role', 'driver')->where('is_active', true)
            ->with(['driverProfile', 'assignedVehicle'])->get();

        return view('admin.training.index', compact('assignments', 'drivers'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'driver_id'     => 'required|exists:users,id',
            'training_type' => 'required|string',
            'provider'      => 'nullable|string',
            'training_date' => 'required|date',
            'duration_days' => 'required|integer|min:1',
            'notes'         => 'nullable|string',
        ]);

        $assignment = TrainingAssignment::create([
            ...$validated,
            'assigned_by_id' => auth()->id(),
            'status'         => 'upcoming',
        ]);

        optional($assignment->driver->driverProfile)->update(['status' => 'training']);
        $assignment->driver->notify(new TrainingAssignedNotification($assignment));

        return back()->with('success', 'Training assigned. Driver notified.');
    }

    public function updateStatus(Request $request, TrainingAssignment $assignment)
    {
        $request->validate(['status' => 'required|in:upcoming,in_progress,completed,cancelled']);
        $assignment->update(['status' => $request->status]);

        if ($request->status === 'completed') {
            optional($assignment->driver->driverProfile)->update(['status' => 'available']);
        }

        return back()->with('success', 'Training status updated.');
    }
}
