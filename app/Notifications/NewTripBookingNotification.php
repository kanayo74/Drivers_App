<?php
namespace App\Notifications;
use App\Models\Trip;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class NewTripBookingNotification extends Notification
{
    use Queueable;
    public function __construct(public Trip $trip) {}
    public function via($notifiable): array { return ['database','mail']; }
    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("New Trip Booking — {$this->trip->trip_code}")
            ->line("{$this->trip->bookedBy->name} booked a trip for {$this->trip->passenger->name}.")
            ->line("Reason: {$this->trip->reason}")
            ->line("Scheduled: {$this->trip->scheduled_at->format('D, d M Y H:i')}")
            ->action('Review & Approve', url('/admin/trips'));
    }
    public function toDatabase($notifiable): array
    {
        return [
            'type'    => 'new_booking',
            'title'   => 'New Trip Booking',
            'message' => "Trip {$this->trip->trip_code} by {$this->trip->bookedBy->name}. Needs approval.",
            'trip_id' => $this->trip->id,
            'url'     => '/admin/trips',
        ];
    }
}
