<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**AA
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->create([
            'username' => 'admin1234',
            'email' => 'abdul.azis@rajawali.com',
            'password' => Hash::make('admin1234'),
            'name' => 'Abdul Azis',
            'phone' => '1234567890',
            'address' => 'Jalan Tipar Cakung No 22 Sukapura, Cilincing, Jakarta Utara',
            'role' => 'ADMIN',
        ]);
        
        User::factory()->create([
            'username' => 'staff1234',
            'email' => 'staff@rajawali.com',
            'password' => Hash::make('staff1234'),
            'name' => 'Staff',
            'phone' => '1234567890',
            'address' => 'Cilincing',
            'role' => 'STAFF',
        ]);

        $this->call([
            CategorySeeder::class,
            SupplierSeeder::class,
            ProductSeeder::class,
        ]);
    }
}
