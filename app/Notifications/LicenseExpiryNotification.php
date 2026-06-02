<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use App\Models\Driver;
use Carbon\Carbon;

class LicenseExpiryNotification extends Notification
{
    use Queueable;

    public $driver;

    public function __construct(Driver $driver)
    {
        $this->driver = $driver;
    }

    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable)
    {
        $when = $this->driver->license_end_date ? Carbon::parse($this->driver->license_end_date)->diffForHumans() : 'unknown';
        return (new MailMessage)
            ->subject("Driver license expiry: {$this->driver->name}")
            ->line("Driver {$this->driver->name} license will expire {$when}.")
            ->action('View driver', url(route('admin.drivers.show', $this->driver->id)))
            ->line('Please take action if renewal is required.');
    }

    public function toArray($notifiable)
    {
        return [
            'driver_id' => $this->driver->id,
            'driver_name' => $this->driver->name,
            'license_end_date' => optional($this->driver->license_end_date)->toDateString(),
        ];
    }
}