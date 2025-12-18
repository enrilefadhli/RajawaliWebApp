<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Sale;
use App\Models\SaleDetail;
use App\Models\Product;
use App\Models\User;

class PosDemoSalesSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::first();
        if (! $user) {
            $this->command?->warn('No users found; skipping POS demo sale seeding.');
            return;
        }

        $productCodes = ['POS-COF', 'POS-TEA', 'POS-SNK'];
        $products = Product::whereIn('product_code', $productCodes)->get()->keyBy('product_code');

        if ($products->isEmpty()) {
            $this->command?->warn('POS demo products not found; run PosDemoSeeder first.');
            return;
        }

        $sale = Sale::where('notes', 'POS demo sale (seed)')->first();
        if ($sale && $sale->details()->exists()) {
            $this->command?->info('POS demo sale already exists; skipping.');
            return;
        }

        DB::transaction(function () use ($user, $products, $sale) {
            $lines = collect([
                ['code' => 'POS-COF', 'qty' => 2],
                ['code' => 'POS-TEA', 'qty' => 1],
                ['code' => 'POS-SNK', 'qty' => 3],
            ])->filter(fn ($line) => $products->has($line['code']));

            if ($lines->isEmpty()) {
                $this->command?->warn('No matching products for POS demo sale; skipping.');
                return;
            }

            $saleModel = $sale ?? Sale::create([
                'user_id' => $user->id,
                'total_amount' => 0,
                'sale_date' => now(),
                'notes' => 'POS demo sale (seed)',
            ]);

            $total = 0;
            foreach ($lines as $line) {
                $product = $products[$line['code']];
                $price = (float) $product->selling_price;
                $quantity = (int) $line['qty'];

                // This will trigger FEFO deduction via SaleDetail model.
                SaleDetail::firstOrCreate(
                    [
                        'sale_id' => $saleModel->id,
                        'product_id' => $product->id,
                    ],
                    [
                        'quantity' => $quantity,
                        'price' => $price,
                    ]
                );

                $total += $price * $quantity;
            }

            $saleModel->update(['total_amount' => $total]);
        });
    }
}
