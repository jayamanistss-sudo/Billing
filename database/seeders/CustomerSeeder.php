<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::where('email', 'demo@srimuruganstores.com')->firstOrFail();

        $customers = [
            ['Rajan Kumar', '9876501001', 'rajan@example.com', 2000, 450],
            ['Meena Devi', '9876501002', null, 1000, 0],
            ['Suresh Babu', '9876501003', 'suresh@example.com', 3000, 1200],
            ['Kavitha Rajesh', '9876501004', null, 0, 0],
            ['Murugesan P', '9876501005', null, 5000, 2300],
            ['Lakshmi Priya', '9876501006', 'lakshmi@example.com', 1500, 0],
            ['Selvam T', '9876501007', null, 0, 0],
            ['Geetha Krishnan', '9876501008', null, 2000, 800],
            ['Anand Raj', '9876501009', 'anand@example.com', 0, 0],
            ['Vijayalakshmi S', '9876501010', null, 1000, 350],
        ];

        foreach ($customers as $c) {
            Customer::updateOrCreate(
                ['tenant_id' => $tenant->id, 'phone' => $c[1]],
                [
                    'tenant_id' => $tenant->id,
                    'name' => $c[0],
                    'phone' => $c[1],
                    'email' => $c[2],
                    'credit_limit' => $c[3],
                    'credit_balance' => $c[4],
                ]
            );
        }
    }
}
