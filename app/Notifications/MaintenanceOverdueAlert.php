<?php
namespace App\Notifications;
use App\Models\Vehicle;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class MaintenanceOverdueAlert extends Notification
{
    use Queueable;
    public function __construct(public Vehicle $vehicle) {}
    public function via($notifiable): array { return ['database','mail']; }
    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("🔧 Maintenance Overdue — {$this->vehicle->plate_number}")
            ->line("{$this->vehicle->plate_number} has overdue maintenance.")
            ->line("Next service was due: {$this->vehicle->next_service_date?->format('d M Y')}")
            ->action('View Maintenance', url('/admin/maintenance'));
    }
    public function toDatabase($notifiable): array
    {
        return [
            'type'       => 'maintenance_overdue',
            'title'      => '🔧 Maintenance Overdue',
            'message'    => "{$this->vehicle->plate_number} maintenance is overdue.",
            'vehicle_id' => $this->vehicle->id,
            'url'        => '/admin/maintenance',
        ];
    }
}
