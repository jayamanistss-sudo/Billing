<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::where('email', 'demo@srimuruganstores.com')->firstOrFail();

        $categories = [
            ['name' => 'Grocery', 'icon_emoji' => '🌾', 'color_hex' => '#F59E0B', 'sort_order' => 1],
            ['name' => 'Dairy', 'icon_emoji' => '🥛', 'color_hex' => '#3B82F6', 'sort_order' => 2],
            ['name' => 'Snacks', 'icon_emoji' => '🍪', 'color_hex' => '#EF4444', 'sort_order' => 3],
            ['name' => 'Drinks', 'icon_emoji' => '🥤', 'color_hex' => '#10B981', 'sort_order' => 4],
            ['name' => 'Household', 'icon_emoji' => '🧺', 'color_hex' => '#8B5CF6', 'sort_order' => 5],
            ['name' => 'Personal Care', 'icon_emoji' => '🧴', 'color_hex' => '#EC4899', 'sort_order' => 6],
        ];

        foreach ($categories as $cat) {
            Category::updateOrCreate(
                ['tenant_id' => $tenant->id, 'name' => $cat['name']],
                array_merge($cat, ['tenant_id' => $tenant->id, 'is_active' => true])
            );
        }
    }
}
