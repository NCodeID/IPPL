<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        /**
         * Untuk Role
         */
        $roles = ['admin', 'akuntan', 'kasir', 'dapur', 'runner', 'gudang'];
        foreach ($roles as $role) {
            Role::create(['name' => $role]);
        }

        User::create([
            'name' => 'Admin',
            'email' => 'admin@pos.com',
            'password' => Hash::make('password'),
            'role_id' => 1,
        ]);
        $kasir = User::create([
            'name' => 'Kasir',
            'email' => 'kasir@pos.com',
            'password' => Hash::make('password'),
            'role_id' => 3,
        ]);
        $runner = User::create([
            'name' => 'Runner',
            'email' => 'runner@pos.com',
            'password' => Hash::make('password'),
            'role_id' => 5,
        ]);
        $dapur = User::create([
            'name' => 'Dapur',
            'email' => 'dapur@pos.com',
            'password' => Hash::make('password'),
            'role_id' => 4,
        ]);
        $gudang = User::create([
            'name' => 'Gudang',
            'email' => 'gudang@pos.com',
            'password' => Hash::make('password'),
            'role_id' => 6,
        ]);
        $akuntan = User::create([
            'name' => 'Akuntan',
            'email' => 'akuntan@pos.com',
            'password' => Hash::make('password'),
            'role_id' => 2,
        ]);
    }
}
