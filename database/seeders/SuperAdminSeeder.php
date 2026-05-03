<?php

namespace Database\Seeders;

use App\Models\SuperAdmin;
use Illuminate\Database\Seeder;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        SuperAdmin::updateOrCreate(
            ['email' => 'admin@shopbill.in'],
            [
                'name'     => 'Super Admin',
                'email'    => 'admin@shopbill.in',
                'password' => bcrypt('Admin@123'),
            ]
        );
    }
}
