<?php
namespace App\Notifications;
use App\Models\{Vehicle, User};
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class FuelRequestedNotification extends Notification
{
    use Queueable;
    public function __construct(public Vehicle $vehicle, public User $driver) {}
    public function via($notifiable): array { return ['database','mail']; }
    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Fuel Request — {$this->vehicle->plate_number}")
            ->line("{$this->driver->name} has requested fuel for {$this->vehicle->plate_number}.")
            ->line("Current level: {$this->vehicle->fuel_percent}%")
            ->action('View Fuel Requests', url('/admin/fuel'));
    }
    public function toDatabase($notifiable): array
    {
        return [
            'type'       => 'fuel_request',
            'title'      => 'Fuel Request',
            'message'    => "{$this->driver->name} needs fuel for {$this->vehicle->plate_number} ({$this->vehicle->fuel_percent}% remaining).",
            'vehicle_id' => $this->vehicle->id,
            'url'        => '/admin/fuel',
        ];
    }
}
