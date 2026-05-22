<?php
namespace App\Notifications;
use App\Models\Trip;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class RateDriverNotification extends Notification
{
    use Queueable;
    public function __construct(public Trip $trip) {}
    public function via($notifiable): array { return ['database']; }
    public function toDatabase($notifiable): array
    {
        return [
            'type'    => 'rate_driver',
            'title'   => 'Rate Your Driver',
            'message' => "Trip {$this->trip->trip_code} is complete. Please rate driver {$this->trip->driver->name}.",
            'trip_id' => $this->trip->id,
            'url'     => '/staff/trips',
        ];
    }
}
