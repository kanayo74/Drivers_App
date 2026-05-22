<?php
namespace App\Notifications;
use App\Models\Trip;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class TripRejectedNotification extends Notification
{
    use Queueable;
    public function __construct(public Trip $trip) {}
    public function via($notifiable): array { return ['database','mail']; }
    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Trip Rejected — {$this->trip->trip_code}")
            ->greeting("Hello {$notifiable->name},")
            ->line("Trip {$this->trip->trip_code} was rejected.")
            ->line("Reason: {$this->trip->rejection_reason}")
            ->action('View Trips', url('/driver/trips'));
    }
    public function toDatabase($notifiable): array
    {
        return [
            'type'    => 'trip_rejected',
            'title'   => 'Trip Rejected',
            'message' => "Trip {$this->trip->trip_code} was rejected: {$this->trip->rejection_reason}",
            'trip_id' => $this->trip->id,
            'url'     => '/driver/dashboard',
        ];
    }
}
