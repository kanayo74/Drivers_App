<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class SetupProject extends Command
{
    protected $signature   = 'nsia:setup';
    protected $description = 'Run all migrations, create notifications table, and seed the database';

    public function handle(): void
    {
        $this->info('══════════════════════════════════════');
        $this->info('  NSIA Fleet — Project Setup');
        $this->info('══════════════════════════════════════');

        // 1. Generate app key if missing
        if (empty(config('app.key'))) {
            $this->call('key:generate');
        }

        // 2. Run all migrations
        $this->info('▶ Running migrations…');
        $this->call('migrate', ['--force' => true]);

        // 3. Ensure notifications table exists
        if (!Schema::hasTable('notifications')) {
            $this->info('▶ Creating notifications table…');
            $this->call('notifications:table');
            $this->call('migrate', ['--force' => true]);
        } else {
            $this->info('✓ Notifications table already exists.');
        }

        // 4. Seed
        $this->info('▶ Seeding database…');
        $this->call('db:seed', ['--force' => true]);

        // 5. Copy CSS to public
        $this->info('▶ Publishing CSS…');
        $src  = resource_path('css/app.css');
        $dest = public_path('css/app.css');
        if (file_exists($src)) {
            if (!is_dir(public_path('css'))) {
                mkdir(public_path('css'), 0755, true);
            }
            copy($src, $dest);
            $this->info('✓ CSS copied to public/css/app.css');
        }

        // 6. Clear caches
        $this->call('config:clear');
        $this->call('route:clear');
        $this->call('view:clear');

        $this->newLine();
        $this->info('══════════════════════════════════════');
        $this->info('  ✅  Setup complete!');
        $this->info('══════════════════════════════════════');
        $this->newLine();
        $this->table(
            ['Role', 'Email', 'Password'],
            [
                ['Admin',    'admin@nsia.com',    'password'],
                ['Staff',    'emeka@nsia.com',    'password'],
                ['Marketer', 'chidi@nsia.com',    'password'],
                ['Driver',   'kelechi@nsia.com',  'password'],
            ]
        );
        $this->newLine();
        $this->info('  Run: php artisan serve');
        $this->info('  Open: http://localhost:8000');
    }
}
