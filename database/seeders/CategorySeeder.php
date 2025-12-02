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
            ['category_name' => 'Kebutuhan Rumah Tangga', 'category_code' => 'HOM'],
            ['category_name' => 'Perawatan Pribadi', 'category_code' => 'SKN'],
            ['category_name' => 'Obat-obatan', 'category_code' => 'MED'],
            ['category_name' => 'Rokok', 'category_code' => 'ROK'],
            ['category_name' => 'Beverages', 'category_code' => 'BEV'],
            ['category_name' => 'Miscellaneous', 'category_code' => 'MSC'],
            ['category_name' => 'Baby Care', 'category_code' => 'BBY'],
        ];

        foreach ($categories as $category) {
            Category::firstOrCreate(
                ['category_code' => $category['category_code']],
                $category
            );
        }
    }
}
