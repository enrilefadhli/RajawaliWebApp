<?php

namespace Database\Seeders;

use App\Models\BatchOfStock;
use App\Models\Category;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderDetail;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestApproval;
use App\Models\PurchaseRequestDetail;
use App\Models\Role;
use App\Models\Sale;
use App\Models\SaleDetail;
use App\Models\StockAdjustment;
use App\Models\StockAdjustmentItem;
use App\Models\StockOpname;
use App\Models\StockOpnameItem;
use App\Models\Supplier;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\PurchaseService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class RajawaliFlowSeeder extends Seeder
{
    public function run(): void
    {
        // Prevent accidental spam during seeding.
        $settings = SystemSetting::firstOrNew([]);
        $settings->fill([
            'low_stock_alerts_enabled' => false,
            'expiry_alerts_enabled' => false,
        ]);
        $settings->save();

        // Don't duplicate the "real-life flow" seed if it already exists.
        if (PurchaseRequest::where('request_note', 'Demo PR seeded from Rajawali test products.')->exists()) {
            return;
        }

        $adminRole = Role::firstOrCreate(['name' => 'ADMIN']);
        $purchasingRole = Role::firstOrCreate(['name' => 'PURCHASING']);
        $warehouseRole = Role::firstOrCreate(['name' => 'WAREHOUSE']);
        $cashierRole = Role::firstOrCreate(['name' => 'CASHIER']);

        $admin = User::firstOrCreate(
            ['username' => 'owner'],
            [
                'email' => 'owner@rajawali.local',
                'password' => Hash::make('password'),
                'name' => 'Owner (Admin)',
                'phone' => '-',
                'address' => '-',
                'role' => 'ADMIN',
            ]
        );
        $admin->roles()->syncWithoutDetaching([$adminRole->id]);

        $purchasing = User::firstOrCreate(
            ['username' => 'purchasing'],
            [
                'email' => 'purchasing@rajawali.local',
                'password' => Hash::make('password'),
                'name' => 'Purchasing',
                'phone' => '-',
                'address' => '-',
                'role' => 'PURCHASING',
            ]
        );
        $purchasing->roles()->syncWithoutDetaching([$purchasingRole->id]);

        $warehouse = User::firstOrCreate(
            ['username' => 'warehouse'],
            [
                'email' => 'warehouse@rajawali.local',
                'password' => Hash::make('password'),
                'name' => 'Warehouse',
                'phone' => '-',
                'address' => '-',
                'role' => 'WAREHOUSE',
            ]
        );
        $warehouse->roles()->syncWithoutDetaching([$warehouseRole->id]);

        $cashier = User::firstOrCreate(
            ['username' => 'cashier'],
            [
                'email' => 'cashier@rajawali.local',
                'password' => Hash::make('password'),
                'name' => 'Cashier',
                'phone' => '-',
                'address' => '-',
                'role' => 'CASHIER',
            ]
        );
        $cashier->roles()->syncWithoutDetaching([$cashierRole->id]);

        $supplier = Supplier::firstOrCreate(
            ['supplier_name' => 'UNILEVER INDONESIA'],
            [
                'supplier_code' => 'UNVR',
                'supplier_phone' => '021-804-70000',
                'supplier_address' => 'contact@unilever.co.id',
            ]
        );

        $productsByCategory = $this->ensureTestProducts();

        DB::transaction(function () use ($admin, $purchasing, $warehouse, $cashier, $supplier, $productsByCategory) {
            $requestedAt = now()->subDays(3)->setTime(10, 15);

            $pr = PurchaseRequest::create([
                'requested_by_id' => $purchasing->getKey(),
                'approved_by_id' => null,
                'supplier_id' => $supplier->id,
                'total_expected_amount' => 0,
                'request_note' => 'Demo PR seeded from Rajawali test products.',
                'status' => 'PENDING',
                'requested_at' => $requestedAt,
                'handled_at' => null,
            ]);

            // Use all test products in the PR.
            $allProducts = collect($productsByCategory)->flatten(1)->values();
            foreach ($allProducts as $product) {
                PurchaseRequestDetail::create([
                    'purchase_request_id' => $pr->id,
                    'product_id' => $product->id,
                    'quantity' => random_int(5, 20),
                    'expected_unit_price' => (int) ($product->purchase_price ?? 0),
                ]);
            }

            // Approve PR and auto-create PO + details (similar to approval resource).
            PurchaseRequestApproval::create([
                'purchase_request_id' => $pr->id,
                'approved_by' => $admin->getKey(),
                'status' => 'APPROVED',
                'note' => 'Approved (seeded).',
                'approved_at' => now()->subDays(2),
            ]);

            $pr->update([
                'status' => 'APPROVED',
                'handled_at' => now()->subDays(2),
                'approved_by_id' => $admin->getKey(),
            ]);

            $po = PurchaseOrder::create([
                'purchase_request_id' => $pr->id,
                'status' => 'ONPROGRESS',
            ]);

            $pr->loadMissing('details');
            foreach ($pr->details as $detail) {
                $expiryDate = now()->addDays(random_int(45, 240))->toDateString();

                PurchaseOrderDetail::create([
                    'purchase_order_id' => $po->id,
                    'product_id' => $detail->product_id,
                    'quantity' => (int) $detail->quantity,
                    'expiry_date' => $expiryDate,
                    'expected_unit_price' => $detail->expected_unit_price ?? 0,
                    'unit_price' => $detail->expected_unit_price ?? 0,
                ]);
            }

            // Complete PO and create Purchase + BatchOfStocks.
            $po->update(['status' => 'COMPLETED']);
            app(PurchaseService::class)->createFromPurchaseOrder($po, (int) $purchasing->getKey());

            // Add one additional batch for FEFO testing (nearer expiry) for one product.
            $fefoProduct = $allProducts->first();
            if ($fefoProduct) {
                BatchOfStock::create([
                    'product_id' => $fefoProduct->id,
                    'quantity' => 8,
                    'expiry_date' => now()->addDays(20)->toDateString(),
                    'batch_no' => null,
                ]);
            }

            // Simulate a sale (deduct stock via SaleDetail model hook -> FEFO).
            $sale = Sale::create([
                'user_id' => $cashier->getKey(),
                'total_amount' => 0,
                'sale_date' => now()->subDay(),
                'notes' => 'Seeded sale (POS simulation).',
            ]);

            $saleProducts = $allProducts->take(3);
            foreach ($saleProducts as $product) {
                SaleDetail::create([
                    'sale_id' => $sale->id,
                    'product_id' => $product->id,
                    'quantity' => 2,
                    'price' => (int) ($product->selling_price ?? 0),
                ]);
            }

            // Simulate stock opname and adjustments on one batch.
            $batch = BatchOfStock::query()
                ->where('quantity', '>', 0)
                ->orderBy('expiry_date')
                ->first();

            if ($batch) {
                $opname = StockOpname::create([
                    'opname_date' => now()->toDateString(),
                    'created_by' => $warehouse->getKey(),
                    'notes' => 'Seeded stock opname.',
                ]);

                $systemQty = (int) $batch->quantity;
                $actualQty = max(0, $systemQty + random_int(-2, 5));

                $item = StockOpnameItem::create([
                    'stock_opname_id' => $opname->id,
                    'batch_of_stock_id' => $batch->id,
                    'product_id' => $batch->product_id,
                    'system_qty' => $systemQty,
                    'actual_qty' => $actualQty,
                    'difference_qty' => $actualQty - $systemQty,
                    'notes' => 'Counted in seeded opname.',
                ]);

                if ((int) $item->difference_qty !== 0) {
                    $adjustment = StockAdjustment::create([
                        'adjustment_date' => $opname->opname_date,
                        'reason' => 'OPNAME',
                        'created_by' => $opname->created_by,
                        'notes' => $opname->notes,
                    ]);

                    StockAdjustmentItem::create([
                        'stock_adjustment_id' => $adjustment->id,
                        'batch_of_stock_id' => $item->batch_of_stock_id,
                        'product_id' => $item->product_id,
                        'qty_change' => (int) $item->difference_qty,
                        'notes' => $item->notes,
                    ]);
                }
            }
        });
    }

    /**
     * Ensure 10 curated "tested" products exist:
     * - 3 from Sembako (SMB)
     * - 1 product for each other category in CategorySeeder
     *
     * @return array<string, array<int, \App\Models\Product>>
     */
    protected function ensureTestProducts(): array
    {
        $categories = Category::query()
            ->whereIn('category_code', ['SMB', 'HOM', 'SKN', 'MED', 'ROK', 'BEV', 'MSC', 'BBY'])
            ->get()
            ->keyBy('category_code');

        // In case CategorySeeder wasn't run.
        foreach ([
            ['category_name' => 'Sembako', 'category_code' => 'SMB'],
            ['category_name' => 'Kebutuhan Rumah Tangga', 'category_code' => 'HOM'],
            ['category_name' => 'Perawatan Pribadi', 'category_code' => 'SKN'],
            ['category_name' => 'Obat-obatan', 'category_code' => 'MED'],
            ['category_name' => 'Rokok', 'category_code' => 'ROK'],
            ['category_name' => 'Beverages', 'category_code' => 'BEV'],
            ['category_name' => 'Miscellaneous', 'category_code' => 'MSC'],
            ['category_name' => 'Baby Care', 'category_code' => 'BBY'],
        ] as $cat) {
            $categories[$cat['category_code']] = $categories[$cat['category_code']] ?? Category::firstOrCreate(
                ['category_code' => $cat['category_code']],
                $cat
            );
        }

        $desired = [
            'SMB' => 3,
            'HOM' => 1,
            'SKN' => 1,
            'MED' => 1,
            'ROK' => 1,
            'BEV' => 1,
            'MSC' => 1,
            'BBY' => 1,
        ];

        $picked = [];
        foreach ($desired as $code => $count) {
            $picked[$code] = [];
        }

        $csvPath = storage_path('app/moka_inventory.csv');
        if (is_file($csvPath)) {
            $picked = $this->pickFromCsv($csvPath, $desired, $picked);
        }

        // Fallback if CSV doesn't contain enough in some categories.
        foreach ($desired as $code => $count) {
            while (count($picked[$code]) < $count) {
                $picked[$code][] = [
                    'sku' => null,
                    'name' => "TEST {$code} " . (count($picked[$code]) + 1),
                    'variant' => null,
                    'price' => 10000,
                ];
            }
        }

        $created = [];
        foreach ($desired as $code => $count) {
            $created[$code] = [];
            $category = $categories[$code];

            foreach ($picked[$code] as $row) {
                $purchasePrice = (int) ($row['price'] ?? 0);
                $purchasePrice = $purchasePrice > 0 ? $purchasePrice : 10000;
                $sellingPrice = (int) ceil($purchasePrice * 1.15);

                $product = \App\Models\Product::firstOrNew(
                    $row['sku'] ? ['sku' => $row['sku']] : ['product_name' => $row['name'], 'variant' => $row['variant']]
                );

                if (! $product->exists) {
                    $product->product_code = \App\Filament\Admin\Resources\ProductResource::generateProductCodeByCategory($category->id);
                }

                $product->fill([
                    'category_id' => $category->id,
                    'sku' => $row['sku'],
                    'product_name' => $row['name'],
                    'variant' => $row['variant'],
                    'purchase_price' => $purchasePrice,
                    'selling_price' => $sellingPrice,
                    'discount_percent' => null,
                    'discount_amount' => null,
                    'minimum_stock' => $code === 'SMB' ? 20 : 10,
                    'status' => 'ACTIVE',
                ]);

                $product->save();
                $created[$code][] = $product;
            }
        }

        return $created;
    }

    protected function pickFromCsv(string $csvPath, array $desired, array $picked): array
    {
        $file = new \SplFileObject($csvPath);
        $file->setFlags(\SplFileObject::READ_CSV);
        $file->setCsvControl(',');

        $headers = [];
        $seenNamePerCat = [];
        foreach (array_keys($desired) as $code) {
            $seenNamePerCat[$code] = [];
        }

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
            $variant = trim((string) ($rowAssoc['variant name'] ?? ''));
            $priceRaw = $rowAssoc['basic - price'] ?? 0;
            $price = (int) preg_replace('/\\D/', '', (string) $priceRaw);

            if ($sku === '' || $name === '') {
                continue;
            }

            // Prefer unit/pcs variants for demo.
            $variantNorm = $variant !== '' ? strtoupper($variant) : null;
            if ($variantNorm && $variantNorm !== 'PCS' && $variantNorm !== 'PC' && $variantNorm !== 'PICS') {
                continue;
            }

            if ($price <= 0) {
                continue;
            }

            $cat = (new \Database\Seeders\ProductSeeder())->guessCategory($name);
            if (! array_key_exists($cat, $desired)) {
                continue;
            }

            if (count($picked[$cat]) >= $desired[$cat]) {
                continue;
            }

            $normalizedName = strtoupper($name);
            if (in_array($normalizedName, $seenNamePerCat[$cat], true)) {
                continue;
            }

            $seenNamePerCat[$cat][] = $normalizedName;

            $picked[$cat][] = [
                'sku' => $sku,
                'name' => $name,
                'variant' => null,
                'price' => $price,
            ];

            $done = true;
            foreach ($desired as $code => $count) {
                if (count($picked[$code]) < $count) {
                    $done = false;
                    break;
                }
            }
            if ($done) {
                break;
            }
        }

        return $picked;
    }
}
