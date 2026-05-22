<?php
namespace App\Notifications;
use App\Models\FuelRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class FuelAcknowledgedNotification extends Notification
{
    use Queueable;
    public function __construct(public FuelRequest $request) {}
    public function via($notifiable): array { return ['database']; }
    public function toDatabase($notifiable): array
    {
        return [
            'type'    => 'fuel_acknowledged',
            'title'   => 'Fuel Request Acknowledged',
            'message' => "Your fuel request for {$this->request->vehicle->plate_number} has been acknowledged. Fuel is on the way.",
            'url'     => '/driver/dashboard',
        ];
    }
}
