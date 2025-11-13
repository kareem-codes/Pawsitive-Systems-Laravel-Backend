<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            // Owner permissions
            'view owners',
            'create owners',
            'edit owners',
            'delete owners',
            
            // Pet permissions
            'view pets',
            'create pets',
            'edit pets',
            'delete pets',
            
            // Appointment permissions
            'view appointments',
            'create appointments',
            'edit appointments',
            'delete appointments',
            
            // Medical record permissions
            'view medical records',
            'create medical records',
            'edit medical records',
            'delete medical records',
            
            // Vaccination permissions
            'view vaccinations',
            'create vaccinations',
            'edit vaccinations',
            'delete vaccinations',
            
            // Invoice permissions
            'view invoices',
            'create invoices',
            'edit invoices',
            'delete invoices',
            
            // Payment permissions
            'view payments',
            'create payments',
            'edit payments',
            'delete payments',
            
            // Product/Inventory permissions
            'view products',
            'create products',
            'edit products',
            'delete products',
            
            // User management permissions
            'view users',
            'create users',
            'edit users',
            'delete users',
            
            // Role & Permission management
            'view roles',
            'create roles',
            'edit roles',
            'delete roles',
            
            // Reports
            'view reports',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission, 'guard_name' => 'api']);
        }

        // Create roles and assign permissions
        
        // Admin - full access
        $admin = Role::create(['name' => 'admin', 'guard_name' => 'api']);
        $admin->givePermissionTo(Permission::all());

        // Veterinarian - medical and patient management
        $vet = Role::create(['name' => 'veterinarian', 'guard_name' => 'api']);
        $vet->givePermissionTo([
            'view owners', 'view pets', 'edit pets',
            'view appointments', 'edit appointments',
            'view medical records', 'create medical records', 'edit medical records',
            'view vaccinations', 'create vaccinations', 'edit vaccinations',
            'view invoices',
        ]);

        // Receptionist - scheduling and basic management
        $receptionist = Role::create(['name' => 'receptionist', 'guard_name' => 'api']);
        $receptionist->givePermissionTo([
            'view owners', 'create owners', 'edit owners',
            'view pets', 'create pets', 'edit pets',
            'view appointments', 'create appointments', 'edit appointments',
            'view invoices', 'create invoices',
            'view payments', 'create payments',
            'view products',
        ]);

        // Cashier - POS and billing only
        $cashier = Role::create(['name' => 'cashier', 'guard_name' => 'api']);
        $cashier->givePermissionTo([
            'view invoices', 'create invoices',
            'view payments', 'create payments',
            'view products',
        ]);

        // Owner - customer/pet owner role with limited access
        $owner = Role::create(['name' => 'owner', 'guard_name' => 'api']);
        $owner->givePermissionTo([
            'view pets',        // View own pets only
            'view appointments', // View own appointments only
            'create appointments', // Book appointments
            'view invoices',    // View own invoices only
            'view payments',    // View own payments only
            'view products',    // Browse products in online store
        ]);

        // Create default admin user
        $adminUser = User::create([
            'name' => 'Admin User',
            'email' => 'admin@pawsitive.com',
            'password' => bcrypt('password'),
            'user_type' => 'admin',
            'is_active' => true,
        ]);
        $adminUser->assignRole('admin');

        // Create a test owner/customer
        $testOwner = User::create([
            'name' => 'John Doe',
            'email' => 'owner@test.com',
            'password' => bcrypt('password'),
            'phone' => '1234567890',
            'user_type' => 'owner',
            'address' => '123 Main St',
            'city' => 'New York',
            'is_active' => true,
        ]);
        $testOwner->assignRole('owner');

        $this->command->info('Roles and permissions seeded successfully!');
        $this->command->info('Admin: admin@pawsitive.com / password');
        $this->command->info('Owner: owner@test.com / password');
    }
}
