<?php

namespace Database\Seeders;

use App\Models\Supplier;
use Illuminate\Database\Seeder;

class SupplierSeeder extends Seeder
{
    public function run(): void
    {
        $suppliers = [
            [
                'supplier_name' => 'UNILEVER INDONESIA',
                'supplier_phone' => '021-804-70000',
                'supplier_address' => 'Grha Unilever, BSD Green Office Park, Tangerang',
            ],
            [
                'supplier_name' => 'CV Tirta Makmur',
                'supplier_phone' => '021-777-2233',
                'supplier_address' => 'Jl. Industri No.18, Bekasi',
            ],
            [
                'supplier_name' => 'PT Cahaya Elektronik',
                'supplier_phone' => '021-9900-1122',
                'supplier_address' => 'Jl. Gatot Subroto No.55, Jakarta Selatan',
            ],
            [
                'supplier_name' => 'UD Sejahtera Grosir',
                'supplier_phone' => '021-8877-3344',
                'supplier_address' => 'Jl. Pasar Rebo No.12, Depok',
            ],
        ];

        foreach ($suppliers as $supplier) {
            Supplier::firstOrCreate(
                ['supplier_name' => $supplier['supplier_name']],
                $supplier
            );
        }
    }
}
