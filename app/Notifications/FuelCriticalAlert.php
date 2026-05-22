<?php
namespace App\Notifications;
use App\Models\Vehicle;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class FuelCriticalAlert extends Notification
{
    use Queueable;
    public function __construct(public Vehicle $vehicle) {}
    public function via($notifiable): array { return ['database','mail']; }
    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("⚠️ Critical Fuel — {$this->vehicle->plate_number}")
            ->line("{$this->vehicle->display_name} is critically low on fuel.")
            ->line("Current level: {$this->vehicle->fuel_percent}%")
            ->action('View Fleet', url('/admin/fuel'));
    }
    public function toDatabase($notifiable): array
    {
        return [
            'type'       => 'fuel_critical',
            'title'      => '⚠️ Critical Fuel Level',
            'message'    => "{$this->vehicle->plate_number} is at {$this->vehicle->fuel_percent}% fuel.",
            'vehicle_id' => $this->vehicle->id,
            'url'        => '/admin/fuel',
        ];
    }
}
