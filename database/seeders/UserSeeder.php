<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            ['name' => 'Admin', 'email' => 'admin@pos.com', 'role' => 'admin'],
            ['name' => 'Runner', 'email' => 'runner@pos.com', 'role' => 'runner'],
            ['name' => 'Kasir', 'email' => 'kasir@pos.com', 'role' => 'kasir'],
            ['name' => 'Gudang', 'email' => 'gudang@pos.com', 'role' => 'gudang'],
            ['name' => 'Akuntan', 'email' => 'akuntan@pos.com', 'role' => 'akuntan'],
            ['name' => 'Dapur', 'email' => 'dapur@pos.com', 'role' => 'dapur'],
        ];

        foreach ($users as $attributes) {
            if (User::where('email', $attributes['email'])->exists()) {
                continue;
            }

            $user = new User([
                ...$attributes,
                'is_active' => true,
                'password' => Hash::make('password'),
            ]);

            $user->save();
        }
    }
}
