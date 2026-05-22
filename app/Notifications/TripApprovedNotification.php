<?php
namespace App\Notifications;
use App\Models\Trip;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class TripApprovedNotification extends Notification
{
    use Queueable;
    public function __construct(public Trip $trip) {}
    public function via($notifiable): array { return ['database','mail']; }
    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Trip Approved — {$this->trip->trip_code}")
            ->greeting("Hello {$notifiable->name},")
            ->line("Trip {$this->trip->trip_code} has been APPROVED.")
            ->line("Passenger: {$this->trip->passenger->name}")
            ->line("Scheduled: {$this->trip->scheduled_at->format('D, d M Y H:i')}")
            ->action('Start Trip', url('/driver/dashboard'));
    }
    public function toDatabase($notifiable): array
    {
        return [
            'type'    => 'trip_approved',
            'title'   => 'Trip Approved ✓',
            'message' => "Trip {$this->trip->trip_code} approved. Ready to go.",
            'trip_id' => $this->trip->id,
            'url'     => '/driver/dashboard',
        ];
    }
}
