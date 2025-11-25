<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            [
                'name' => 'superadmin',
                'display_name' => 'Super Administrator',
                'description' => 'Full system access',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'admin',
                'display_name' => 'Administrator',
                'description' => 'General administrative access',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'noc',
                'display_name' => 'NOC Engineer',
                'description' => 'Network operations and monitoring',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'finance',
                'display_name' => 'Finance',
                'description' => 'Billing and accounting access',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'customer',
                'display_name' => 'Customer',
                'description' => 'Customer portal access',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('roles')->insert($roles);
    }
}
