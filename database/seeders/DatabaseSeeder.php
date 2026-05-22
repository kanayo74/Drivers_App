<?php

namespace Database\Seeders;

use App\Models\{User, DriverProfile, Vehicle, Trip, DriverRating, FuelLog};
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ── Admin ─────────────────────────────────────────────────────────────
        $admin = User::create([
            'name'        => 'Amara Diallo',
            'email'       => 'admin@nsia.com',
            'phone'       => '08012345678',
            'employee_id' => 'NSIA-001',
            'department'  => 'Administration',
            'role'        => 'admin',
            'password'    => Hash::make('password'),
            'is_active'   => true,
        ]);

        // ── Staff ─────────────────────────────────────────────────────────────
        $staffData = [
            ['Emeka Nwosu',   'emeka@nsia.com',   'staff',    'Operations',  'NSIA-002'],
            ['Zainab Hassan', 'zainab@nsia.com',  'staff',    'HR',          'NSIA-003'],
            ['Ngozi Akor',    'ngozi@nsia.com',   'staff',    'Finance',     'NSIA-004'],
            ['Chidi Eze',     'chidi@nsia.com',   'marketer', 'Marketing',   'NSIA-005'],
            ['Aisha Musa',    'aisha@nsia.com',   'marketer', 'Marketing',   'NSIA-006'],
        ];

        $staffUsers = [];
        foreach ($staffData as [$name, $email, $role, $dept, $empId]) {
            $staffUsers[] = User::create([
                'name'        => $name,
                'email'       => $email,
                'phone'       => '0801' . rand(1000000, 9999999),
                'employee_id' => $empId,
                'department'  => $dept,
                'role'        => $role,
                'password'    => Hash::make('password'),
                'is_active'   => true,
            ]);
        }

        // ── Drivers ───────────────────────────────────────────────────────────
        $driverData = [
            ['Kelechi Obi',   'kelechi@nsia.com',  'NSIA-D01', 'LIC-001-KO', 'B', 8],
            ['Femi Bello',    'femi@nsia.com',      'NSIA-D02', 'LIC-002-FB', 'B', 12],
            ['Musa Audu',     'musa@nsia.com',      'NSIA-D03', 'LIC-003-MA', 'B', 5],
            ['Philip Duru',   'philip@nsia.com',    'NSIA-D04', 'LIC-004-PD', 'B', 7],
            ['Sunday Ojo',    'sunday@nsia.com',    'NSIA-D05', 'LIC-005-SO', 'B', 3],
        ];

        $drivers = [];
        foreach ($driverData as [$name, $email, $empId, $lic, $class, $exp]) {
            $user = User::create([
                'name'        => $name,
                'email'       => $email,
                'phone'       => '0802' . rand(1000000, 9999999),
                'employee_id' => $empId,
                'role'        => 'driver',
                'password'    => Hash::make('password'),
                'is_active'   => true,
            ]);

            DriverProfile::create([
                'user_id'          => $user->id,
                'license_number'   => $lic,
                'license_expiry'   => now()->addYears(3),
                'license_class'    => $class,
                'years_experience' => $exp,
                'per_trip_rate'    => 1200.00,
                'status'           => 'available',
            ]);

            $drivers[] = $user;
        }

        // ── Vehicles ──────────────────────────────────────────────────────────
        $vehicleData = [
            ['Toyota',  'Hiace',  '2020', 'LSR-874-KJ', 'staff_bus',      2.7, 50, 2.2, 9.0,  $drivers[0]->id],
            ['Toyota',  'Hiace',  '2021', 'LSR-902-KJ', 'staff_bus',      2.7, 50, 2.2, 36.0, $drivers[4]->id],
            ['Toyota',  'Camry',  '2022', 'ABJ-118-FC', 'marketing_car',  2.5, 50, 2.0, 30.0, $drivers[1]->id],
            ['Toyota',  'Camry',  '2021', 'ABJ-220-FC', 'marketing_car',  2.5, 50, 2.0, 12.0, $drivers[3]->id],
            ['Toyota',  'Prado',  '2023', 'ABJ-001-FC', 'executive_car',  3.5, 85, 3.5, 72.0, $drivers[2]->id],
        ];

        $vehicles = [];
        foreach ($vehicleData as [$make, $model, $year, $plate, $type, $engine, $tank, $cons, $fuel, $driverId]) {
            $pct = ($fuel / $tank) * 100;
            $status = match(true) {
                $pct >= 90 => 'full', $pct >= 50 => 'half',
                $pct >= 25 => 'quarter', $pct > 5 => 'critical', default => 'empty'
            };
            $vehicles[] = Vehicle::create([
                'make'                       => $make,
                'model'                      => $model,
                'year'                       => $year,
                'plate_number'               => $plate,
                'type'                       => $type,
                'engine_size'                => $engine,
                'tank_capacity'              => $tank,
                'fuel_consumption_per_hour'  => $cons,
                'current_fuel_level'         => $fuel,
                'fuel_status'                => $status,
                'assigned_driver_id'         => $driverId,
                'status'                     => 'active',
                'last_service_date'          => now()->subMonths(rand(1, 3)),
                'next_service_date'          => now()->addMonths(rand(1, 4)),
                'current_mileage'            => rand(20000, 100000),
            ]);
        }

        // ── Sample completed trips with ratings ───────────────────────────────
        $allBookers = array_merge([$admin], $staffUsers);

        for ($i = 1; $i <= 30; $i++) {
            $booker   = $allBookers[array_rand($allBookers)];
            $driver   = $drivers[array_rand($drivers)];
            $vehicle  = $vehicles[array_rand($vehicles)];
            $daysAgo  = rand(1, 30);
            $completed = Carbon::now()->subDays($daysAgo)->addHours(rand(7, 17));

            $trip = Trip::create([
                'trip_code'          => 'TR-' . str_pad($i, 4, '0', STR_PAD_LEFT),
                'booked_by_id'       => $booker->id,
                'passenger_id'       => $booker->id,
                'driver_id'          => $driver->id,
                'vehicle_id'         => $vehicle->id,
                'reason'             => collect([
                    'Client meeting at Victoria Island',
                    'Marketing field visit — Lekki axis',
                    'Airport pickup for executive',
                    'Staff morning commute',
                    'Site inspection — Ikoyi',
                    'Branch visit — Maryland',
                ])->random(),
                'destination'        => collect(['Victoria Island', 'Lekki', 'Airport', 'Ikoyi', 'Maryland', 'Surulere'])->random(),
                'scheduled_at'       => $completed->subHour(),
                'started_at'         => $completed->subMinutes(30),
                'completed_at'       => $completed,
                'status'             => 'completed',
                'payment_processed'  => $daysAgo > 7,
                'approved_by_id'     => $admin->id,
                'approved_at'        => $completed->subHours(2),
            ]);

            // Rate each completed trip
            DriverRating::create([
                'trip_id'     => $trip->id,
                'driver_id'   => $driver->id,
                'rated_by_id' => $booker->id,
                'rating'      => rand(3, 5),
                'comment'     => collect([
                    'Very punctual and professional.',
                    'Good driver, smooth ride.',
                    'Arrived on time. Friendly.',
                    'Excellent service!',
                    null,
                ])->random(),
            ]);
        }

        // ── 5 pending trips ───────────────────────────────────────────────────
        foreach (range(1, 5) as $i) {
            $booker = $allBookers[array_rand($allBookers)];
            $driver = $drivers[array_rand($drivers)];
            Trip::create([
                'trip_code'    => 'TR-' . str_pad(30 + $i, 4, '0', STR_PAD_LEFT),
                'booked_by_id' => $booker->id,
                'passenger_id' => $booker->id,
                'driver_id'    => $driver->id,
                'vehicle_id'   => $vehicles[array_rand($vehicles)]->id,
                'reason'       => 'Pending trip reason ' . $i,
                'destination'  => 'Lagos Island',
                'scheduled_at' => now()->addHours(rand(1, 48)),
                'status'       => 'pending',
            ]);
        }

        $this->command->info('✅ NSIA Fleet seeded successfully!');
        $this->command->table(
            ['Role', 'Email', 'Password'],
            [
                ['Admin',    'admin@nsia.com',   'password'],
                ['Staff',    'emeka@nsia.com',   'password'],
                ['Marketer', 'chidi@nsia.com',   'password'],
                ['Driver',   'kelechi@nsia.com', 'password'],
            ]
        );
    }
}
