<?php

namespace App\Notifications;

use App\Models\Trip;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class TripCancelledNotification extends Notification
{
    use Queueable;

    public function __construct(public Trip $trip) {}

    public function via($notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Trip Cancelled — {$this->trip->trip_code}")
            ->greeting("Hello {$notifiable->name},")
            ->line("Trip {$this->trip->trip_code} has been cancelled by the passenger.")
            ->line("Passenger: {$this->trip->passenger->name}")
            ->line("Originally scheduled: {$this->trip->scheduled_at->format('D, d M Y H:i')}")
            ->line("No action is required from you.");
    }

    public function toDatabase($notifiable): array
    {
        return [
            'type'    => 'trip_cancelled',
            'title'   => 'Trip Cancelled',
            'message' => "Trip {$this->trip->trip_code} was cancelled by {$this->trip->passenger->name}.",
            'trip_id' => $this->trip->id,
            'url'     => '/driver/dashboard',
        ];
    }
}
