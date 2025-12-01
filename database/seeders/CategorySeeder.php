<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['category_name' => 'Sembako', 'category_code' => 'SMB'],
            ['category_name' => 'Minuman', 'category_code' => 'MNM'],
            ['category_name' => 'Kebutuhan Rumah Tangga', 'category_code' => 'HOM'],
            ['category_name' => 'Perawatan Pribadi', 'category_code' => 'SKN'],
            ['category_name' => 'Obat-obatan', 'category_code' => 'MED'],
        ];

        foreach ($categories as $category) {
            Category::firstOrCreate(
                ['category_code' => $category['category_code']],
                $category
            );
        }
    }
}
