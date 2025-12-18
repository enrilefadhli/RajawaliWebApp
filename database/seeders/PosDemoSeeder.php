<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use App\Models\Product;
use App\Models\BatchOfStock;
use Illuminate\Support\Str;

class PosDemoSeeder extends Seeder
{
    public function run(): void
    {
        $category = Category::firstOrCreate(
            ['category_code' => 'POS'],
            ['category_name' => 'Point of Sale Demo']
        );

        $products = [
            [
                'product_code' => 'POS-COF',
                'product_name' => 'Kopi Arabika 250g',
                'variant' => 'Medium Roast',
                'selling_price' => 55000,
                'purchase_price' => 35000,
                'minimum_stock' => 5,
                'status' => 'ACTIVE',
            ],
            [
                'product_code' => 'POS-TEA',
                'product_name' => 'Teh Hijau 50ct',
                'variant' => null,
                'selling_price' => 45000,
                'purchase_price' => 25000,
                'minimum_stock' => 5,
                'status' => 'ACTIVE',
            ],
            [
                'product_code' => 'POS-SNK',
                'product_name' => 'Keripik Singkong 200g',
                'variant' => 'Balado',
                'selling_price' => 18000,
                'purchase_price' => 9000,
                'minimum_stock' => 10,
                'status' => 'ACTIVE',
            ],
        ];

        foreach ($products as $data) {
            $product = Product::firstOrCreate(
                ['product_code' => $data['product_code']],
                array_merge($data, [
                    'category_id' => $category->id,
                    'sku' => Str::slug($data['product_code']),
                ])
            );

            // Seed a fresh, unexpired batch with available stock.
            BatchOfStock::firstOrCreate(
                [
                    'product_id' => $product->id,
                    'batch_no' => "{$data['product_code']}-B01",
                ],
                [
                    'expiry_date' => now()->addMonths(6)->toDateString(),
                    'quantity' => 50,
                ]
            );
        }
    }
}
