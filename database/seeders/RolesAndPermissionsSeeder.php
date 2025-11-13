<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            // Pet permissions
            'view pets',
            'create pets',
            'edit pets',
            'delete pets',
            
            // Owner permissions
            'view owners',
            'create owners',
            'edit owners',
            'delete owners',
            
            // Appointment permissions
            'view appointments',
            'create appointments',
            'edit appointments',
            'delete appointments',
            'confirm appointments',
            'cancel appointments',
            
            // Medical Record permissions
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
            
            // Product permissions
            'view products',
            'create products',
            'edit products',
            'delete products',
            'manage stock',
            
            // POS permissions
            'access pos',
            'process sales',
            
            // Report permissions
            'view reports',
            'export reports',
            
            // Settings permissions
            'view settings',
            'edit settings',
            
            // User management permissions
            'view users',
            'create users',
            'edit users',
            'delete users',
            'assign roles',
            
            // Role management permissions
            'view roles',
            'create roles',
            'edit roles',
            'delete roles',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'api']);
        }

        // Create roles and assign permissions
        
        // Admin - has all permissions
        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'api']);
        $adminPermissions = Permission::where('guard_name', 'api')->get();
        $admin->syncPermissions($adminPermissions);

        // Veterinarian - can manage medical records, appointments, pets
        $vet = Role::firstOrCreate(['name' => 'veterinarian', 'guard_name' => 'api']);
        $vetPermissions = Permission::where('guard_name', 'api')->whereIn('name', [
            'view pets', 'create pets', 'edit pets',
            'view owners', 'create owners', 'edit owners',
            'view appointments', 'create appointments', 'edit appointments',
            'confirm appointments', 'cancel appointments',
            'view medical records', 'create medical records', 'edit medical records',
            'view vaccinations', 'create vaccinations', 'edit vaccinations',
            'view invoices', 'create invoices',
            'view products',
            'access pos', 'process sales',
            'view reports',
        ])->get();
        $vet->syncPermissions($vetPermissions);

        // Receptionist - can manage appointments, basic pet/owner info
        $receptionist = Role::firstOrCreate(['name' => 'receptionist', 'guard_name' => 'api']);
        $receptionistPermissions = Permission::where('guard_name', 'api')->whereIn('name', [
            'view pets', 'create pets', 'edit pets',
            'view owners', 'create owners', 'edit owners',
            'view appointments', 'create appointments', 'edit appointments',
            'confirm appointments', 'cancel appointments',
            'view invoices', 'view payments',
            'view products',
            'access pos', 'process sales',
        ])->get();
        $receptionist->syncPermissions($receptionistPermissions);

        // Staff - basic viewing permissions
        $staff = Role::firstOrCreate(['name' => 'staff', 'guard_name' => 'api']);
        $staffPermissions = Permission::where('guard_name', 'api')->whereIn('name', [
            'view pets',
            'view owners',
            'view appointments',
            'view medical records',
            'view vaccinations',
            'view products',
        ])->get();
        $staff->syncPermissions($staffPermissions);

        // Owner (Pet Owner) - can only view their own pets and appointments
        $owner = Role::firstOrCreate(['name' => 'owner', 'guard_name' => 'api']);
        $ownerPermissions = Permission::where('guard_name', 'api')->whereIn('name', [
            'view pets',
            'view appointments',
            'view medical records',
            'view vaccinations',
            'view invoices',
        ])->get();
        $owner->syncPermissions($ownerPermissions);

        $this->command->info('Roles and permissions created successfully!');
    }
}
