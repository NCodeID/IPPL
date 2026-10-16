<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'manage_users' => 'Manage users',
            'manage_roles' => 'Manage roles',
            'manage_permissions' => 'Manage permissions',
            'manage_categories' => 'Manage product categories',
            'manage_products' => 'Manage products',
            'manage_units' => 'Manage measurement units',
            'manage_ingredients' => 'Manage ingredients',
            'manage_tables' => 'Manage restaurant tables',
            'manage_orders' => 'Manage orders',
            'manage_purchase_requests' => 'Manage purchase requests',
            'manage_payments' => 'Manage payments',
            'manage_payment_methods' => 'Manage payment methods',
            'manage_cash_transactions' => 'Manage cash transactions',
        ];

        foreach ($permissions as $name => $description) {
            Permission::firstOrCreate(['name' => $name], ['description' => $description]);
        }

        $rolePermissions = [
            'admin' => array_keys($permissions),
            'akuntan' => [
                'manage_cash_transactions',
                'manage_payment_methods',
                'manage_purchase_requests',
                'manage_payments',
                'manage_orders',
            ],
            'kasir' => [
                'manage_orders',
                'manage_payments',
                'manage_cash_transactions',
            ],
            'dapur' => [
                'manage_orders',
                'manage_ingredients',
                'manage_products',
            ],
            'runner' => [
                'manage_orders',
            ],
            'gudang' => [
                'manage_ingredients',
                'manage_purchase_requests',
            ],
        ];

        foreach ($rolePermissions as $roleName => $perms) {
            $role = Role::where('name', $roleName)->first();

            if (! $role) {
                continue;
            }

            $permissionIds = Permission::whereIn('name', $perms)->pluck('id')->toArray();

            $role->permissions()->syncWithoutDetaching($permissionIds);
        }
    }
}
