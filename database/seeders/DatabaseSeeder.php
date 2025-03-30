<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        $roleMahasiswa = Role::create(['name' => 'mahasiswa']);
        $roleAdmin = Role::create(['name' => 'admin']);
        $adminPermission = Permission::create(['name' => 'edit articles']);
        $mahasiswaPermission = Permission::create(['name' => 'view articles']);
        $roleAdmin->givePermissionTo($adminPermission);
        $roleMahasiswa->givePermissionTo($mahasiswaPermission);

        User::factory()->create([
            'name' => 'Test User',
            'nim' => '2310512056',
            'email' => 'user@example.com',
            'password' => bcrypt('string'),
            'image_url' => '/storage/avatars/defaultAvatar.jpg',
        ])->assignRole('admin');

        User::factory()->create([
            'name' => 'Test User Mahasiswa',
            'nim' => '2310512057',
            'email' => 'mahasiswa@example.com',
            'password' => bcrypt('string'),
            'image_url' => '/storage/avatars/defaultAvatar.jpg',
        ])->assignRole('mahasiswa');
    }
}
