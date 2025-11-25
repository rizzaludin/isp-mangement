<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'role_id' => 1, // superadmin
                'name' => 'Super Admin',
                'email' => 'admin@isp.local',
                'password' => Hash::make('admin123'),
                'phone' => '08123456789',
                'status' => 'active',
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'role_id' => 3, // noc
                'name' => 'NOC Engineer',
                'email' => 'noc@isp.local',
                'password' => Hash::make('noc123'),
                'phone' => '08123456790',
                'status' => 'active',
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'role_id' => 4, // finance
                'name' => 'Finance Manager',
                'email' => 'finance@isp.local',
                'password' => Hash::make('finance123'),
                'phone' => '08123456791',
                'status' => 'active',
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('users')->insert($users);
    }
}
