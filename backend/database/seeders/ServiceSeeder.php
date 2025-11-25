<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ServiceSeeder extends Seeder
{
    public function run(): void
    {
        $services = [
            [
                'name' => 'Home 10 Mbps',
                'code' => 'HOME-10',
                'speed_up' => 10,
                'speed_down' => 10,
                'price' => 150000,
                'billing_cycle' => 'monthly',
                'description' => 'Paket internet rumah 10 Mbps unlimited',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Home 20 Mbps',
                'code' => 'HOME-20',
                'speed_up' => 20,
                'speed_down' => 20,
                'price' => 250000,
                'billing_cycle' => 'monthly',
                'description' => 'Paket internet rumah 20 Mbps unlimited',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Home 30 Mbps',
                'code' => 'HOME-30',
                'speed_up' => 30,
                'speed_down' => 30,
                'price' => 350000,
                'billing_cycle' => 'monthly',
                'description' => 'Paket internet rumah 30 Mbps unlimited',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Business 50 Mbps',
                'code' => 'BIZ-50',
                'speed_up' => 50,
                'speed_down' => 50,
                'price' => 750000,
                'billing_cycle' => 'monthly',
                'description' => 'Paket internet bisnis 50 Mbps dengan SLA',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Business 100 Mbps',
                'code' => 'BIZ-100',
                'speed_up' => 100,
                'speed_down' => 100,
                'price' => 1500000,
                'billing_cycle' => 'monthly',
                'description' => 'Paket internet bisnis 100 Mbps dengan SLA',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('services')->insert($services);
    }
}
