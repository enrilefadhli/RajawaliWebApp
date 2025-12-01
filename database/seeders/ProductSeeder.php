<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Services\InventoryService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use SplFileObject;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $categoryMap = [
            'SMB' => Category::firstOrCreate(['category_code' => 'SMB'], ['category_name' => 'Sembako'])->id,
            'SKN' => Category::firstOrCreate(['category_code' => 'SKN'], ['category_name' => 'Perawatan Pribadi'])->id,
        ];

        $products = [
            ['sku' => '8999999012625', 'name' => 'BANGO 220 ML', 'price' => 10000, 'variant' => 'PCS', 'cat' => 'SMB'],
            ['sku' => '8999999204488', 'name' => 'BANGO 220 ML', 'price' => 215000, 'variant' => 'KARTON', 'cat' => 'SMB'],
            ['sku' => '8999999533496', 'name' => 'BANGO MANIS SAK 1000', 'price' => 10000, 'variant' => 'LUSINAN', 'cat' => 'SMB'],
            ['sku' => '8999999204464', 'name' => 'BANGO MANIS SAK 1000', 'price' => 115000, 'variant' => 'KARTON', 'cat' => 'SMB'],
            ['sku' => '8999999514006', 'name' => 'BANGO MANIS 77 GR', 'price' => 2500, 'variant' => null, 'cat' => 'SMB'],
            ['sku' => '8999999204471', 'name' => 'BANGO MANIS 77 GR', 'price' => 115000, 'variant' => 'KARTON', 'cat' => 'SMB'],
            ['sku' => '8999999100506', 'name' => 'BANGO MANIS RL 700 GR', 'price' => 22500, 'variant' => null, 'cat' => 'SMB'],
            ['sku' => '8999999210748', 'name' => 'BANGO MANIS RL 700 GR', 'price' => 255000, 'variant' => 'KARTON', 'cat' => 'SMB'],
            ['sku' => '8999999003777', 'name' => 'CITRA H&B FRESH RADIANCE 250 ML', 'price' => 0, 'variant' => null, 'cat' => 'SKN'],
            ['sku' => null, 'name' => 'CITRA H&B FRESH RADIANCE 250 ML', 'price' => 0, 'variant' => null, 'cat' => 'SKN'],
            ['sku' => '8999999003517', 'name' => 'CITRA H&B FRESH RADIANCE 60 ML', 'price' => 0, 'variant' => null, 'cat' => 'SKN'],
            ['sku' => null, 'name' => 'CITRA H&B FRESH RADIANCE 60 ML', 'price' => 0, 'variant' => null, 'cat' => 'SKN'],
            ['sku' => '8999999003661', 'name' => 'CITRA H&B LASTING GLOW 120 ML', 'price' => 0, 'variant' => null, 'cat' => 'SKN'],
            ['sku' => null, 'name' => 'CITRA H&B LASTING GLOW 120 ML', 'price' => 0, 'variant' => null, 'cat' => 'SKN'],
            ['sku' => '8999999003524', 'name' => 'CITRA H&B LASTING GLOW 60 ML', 'price' => 0, 'variant' => null, 'cat' => 'SKN'],
            ['sku' => null, 'name' => 'CITRA H&B LASTING GLOW 60 ML', 'price' => 0, 'variant' => null, 'cat' => 'SKN'],
            ['sku' => '8999999718077', 'name' => 'CITRA H&B LASTING GLOW 9 ML/480', 'price' => 0, 'variant' => null, 'cat' => 'SKN'],
            ['sku' => null, 'name' => 'CITRA H&B LASTING GLOW 9 ML/480', 'price' => 0, 'variant' => null, 'cat' => 'SKN'],
            ['sku' => '8999999003920', 'name' => 'CITRA H&B LASTING GLOW SCHT 9 ML', 'price' => 0, 'variant' => null, 'cat' => 'SKN'],
            ['sku' => null, 'name' => 'CITRA H&B LASTING GLOW SCHT 9 ML', 'price' => 0, 'variant' => null, 'cat' => 'SKN'],
        ];

        $counters = [];

        $nextCode = function (string $prefix) use (&$counters) {
            $counters[$prefix] = ($counters[$prefix] ?? 0) + 1;
            return sprintf('%s%05d', $prefix, $counters[$prefix]);
        };

        $file = storage_path('app/moka_inventory.csv');
        if (file_exists($file)) {
            $rows = $this->readCsv($file, 100);
            if ($rows) {
                $products = $rows;
            }
        }

        $inventory = app(InventoryService::class);

        foreach ($products as $item) {
            $purchasePrice = (int) $item['price'];
            $sellingPrice = $purchasePrice > 0 ? (int) ceil($purchasePrice * 1.15) : 0;
            $catId = $categoryMap[$item['cat']] ?? null;

            if (! $catId) {
                continue;
            }

            $product = Product::firstOrNew(
                $item['sku'] ? ['sku' => $item['sku']] : ['product_name' => $item['name'], 'variant' => $item['variant']]
            );

            if (! $product->exists) {
                $product->product_code = $nextCode($item['cat']);
            }

            $product->fill([
                'category_id' => $catId,
                'sku' => $item['sku'],
                'product_name' => $item['name'],
                'variant' => $item['variant'],
                'purchase_price' => $purchasePrice,
                'selling_price' => $sellingPrice,
                'discount_percent' => null,
                'discount_amount' => null,
                'minimum_stock' => 10,
            ]);

            $product->save();

            $inventory->setInitialStock($product, 0, Auth::user());
        }
    }

    protected function readCsv(string $path, int $limit = 100): array
    {
        $file = new SplFileObject($path);
        $file->setFlags(SplFileObject::READ_CSV);
        $file->setCsvControl(',');

        $headers = [];
        $data = [];

        foreach ($file as $index => $row) {
            if ($row === [null] || $row === false) {
                continue;
            }

            if (empty($headers)) {
                $headers = array_map(fn ($h) => strtolower(trim((string) $h)), $row);
                continue;
            }

            $rowAssoc = [];
            foreach ($headers as $i => $key) {
                $rowAssoc[$key] = $row[$i] ?? null;
            }

            $sku = trim((string) ($rowAssoc['sku'] ?? ''));
            $name = trim((string) ($rowAssoc['items name (do not edit)'] ?? ''));
            $variant = trim((string) ($rowAssoc['variant name'] ?? '')) ?: null;
            $priceRaw = $rowAssoc['basic - price'] ?? 0;
            $price = (int) preg_replace('/\D/', '', (string) $priceRaw);

            if ($name === '') {
                continue;
            }

            $cat = Str::contains(Str::lower($name), ['citra', 'handbody', 'shampoo', 'sabun']) ? 'SKN' : 'SMB';

            $data[] = [
                'sku' => $sku !== '' ? $sku : null,
                'name' => $name,
                'price' => $price,
                'variant' => $variant,
                'cat' => $cat,
            ];

            if (count($data) >= $limit) {
                break;
            }
        }

        return $data;
    }
}
