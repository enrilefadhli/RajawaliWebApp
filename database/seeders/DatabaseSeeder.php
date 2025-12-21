<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\User;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**AA
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RoleSeeder::class);

        $adminRole = Role::firstOrCreate(['name' => 'ADMIN']);
        $managerRole = Role::firstOrCreate(['name' => 'MANAGER']);
        $purchasingRole = Role::firstOrCreate(['name' => 'PURCHASING']);

        $admin = User::factory()->create([
            'username' => 'abdulazis',
            'email' => 'abdul.azis@rajawali.com',
            'password' => Hash::make('admin1234'),
            'name' => 'Abdul Azis',
            'phone' => '1234567890',
            'address' => 'Jalan Tipar Cakung No 22 Sukapura, Cilincing, Jakarta Utara',
            'role' => 'ADMIN',
        ]);
        $admin->roles()->sync([$adminRole->id]);

        $staff = User::factory()->create([
            'username' => 'ahmadferdy',
            'email' => 'ahmad.ferdy@rajawali.com',
            'password' => Hash::make('staff1234'),
            'name' => 'Ahmad Ferdy',
            'phone' => '1234567890',
            'address' => 'Cilincing',
            'role' => 'PURCHASING',
        ]);
        $staff->roles()->sync([$purchasingRole->id]);

        $manager = User::factory()->create([
            'username' => 'manager',
            'email' => 'manager@rajawali.com',
            'password' => Hash::make('manager1234'),
            'name' => 'Purchasing Manager',
            'phone' => '1234567890',
            'address' => 'Cilincing',
            'role' => 'MANAGER',
        ]);
        $manager->roles()->sync([$managerRole->id]);

        $enrile = User::updateOrCreate(
            ['username' => 'enrilefadhli'],
            [
                'email' => 'enrilefadhli@gmail.com',
                'password' => Hash::make('password'),
                'name' => 'Enrile Fadhli Fahrezi',
                'phone' => '081388932334',
                'address' => '-',
                'role' => 'ADMIN',
            ]
        );
        $enrile->roles()->syncWithoutDetaching([$adminRole->id]);

        $this->call([
            CategorySeeder::class,
            SupplierSeeder::class,
            ProductSeeder::class,
            RajawaliFlowSeeder::class,
        ]);
    }
}
