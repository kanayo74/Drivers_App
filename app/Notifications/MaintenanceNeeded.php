<?php

namespace App\Notifications;

use App\Models\Vehicle;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class MaintenanceNeeded extends Notification
{
    use Queueable;

    protected $vehicle;
    protected $issue;
    protected $type;

    public function __construct(Vehicle $vehicle, $issue, $type = null)
    {
        $this->vehicle = $vehicle;
        $this->issue = $issue;
        $this->type = $type;
    }

    public function via($notifiable)
    {
        return ['database', 'mail'];
    }

    public function toArray($notifiable)
    {
        return [
            'vehicle_id' => $this->vehicle->id,
            'plate_number' => $this->vehicle->plate_number,
            'issue' => $this->issue,
            'type' => $this->type,
            'message' => "Maintenance issue reported for vehicle {$this->vehicle->plate_number}: {$this->issue}",
        ];
    }
}