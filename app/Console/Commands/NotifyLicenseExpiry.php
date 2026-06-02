<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Driver;
use App\Models\User;
use App\Notifications\LicenseExpiryNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Notification;

class NotifyLicenseExpiry extends Command
{
    protected $signature = 'notify:license-expiry {days=30}';
    protected $description = 'Notify drivers and admins when driver license will expire in given days or less';

    public function handle()
    {
        $days = (int) $this->argument('days');
        $cutoff = Carbon::now()->addDays($days);

        $drivers = Driver::whereNotNull('license_end_date')
            ->whereDate('license_end_date', '<=', $cutoff)
            ->get();

        if ($drivers->isEmpty()) {
            $this->info('No expiring licenses.');
            return 0;
        }

        $admins = User::whereHas('roles', function($q){ $q->where('name','admin'); })->get();

        foreach ($drivers as $driver) {
            // notify driver
            if ($driver->user) {
                $driver->user->notify(new LicenseExpiryNotification($driver));
            }
            // notify admins
            Notification::send($admins, new LicenseExpiryNotification($driver));
            $this->info("Notified for driver {$driver->id}");
        }

        return 0;
    }
}