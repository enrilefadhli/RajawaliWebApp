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
                'supplier_code' => 'UNVR',
                'supplier_name' => 'UNILEVER INDONESIA',
                'supplier_phone' => '02180470000',
                'supplier_address' => 'contact@unilever.co.id',
            ],
            [
                'supplier_code' => 'UNSP',
                'supplier_name' => 'UNSPECIFIED SUPPLIER',
                'supplier_phone' => null,
                'supplier_address' => null,
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
