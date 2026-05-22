<?php
namespace App\Notifications;
use App\Models\Trip;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class TripRequestNotification extends Notification
{
    use Queueable;
    public function __construct(public Trip $trip) {}
    public function via($notifiable): array { return ['database','mail']; }
    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("New Trip Request — {$this->trip->trip_code}")
            ->greeting("Hello {$notifiable->name},")
            ->line("You have a new trip request from {$this->trip->passenger->name}.")
            ->line("Reason: {$this->trip->reason}")
            ->line("Scheduled: {$this->trip->scheduled_at->format('D, d M Y H:i')}")
            ->line("Awaiting admin approval.")
            ->action('View Dashboard', url('/driver/dashboard'));
    }
    public function toDatabase($notifiable): array
    {
        return [
            'type'    => 'trip_request',
            'title'   => 'New Trip Request',
            'message' => "Trip {$this->trip->trip_code} from {$this->trip->passenger->name} is pending approval.",
            'trip_id' => $this->trip->id,
            'url'     => '/driver/dashboard',
        ];
    }
}
