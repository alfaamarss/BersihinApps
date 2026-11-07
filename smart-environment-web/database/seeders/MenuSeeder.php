<?php

namespace Database\Seeders;

use App\Models\Menu;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class MenuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $adminRole = Role::firstOrCreate(['name' => 'admin']);

        // Create the root menu only - all submenus are handled by AdminFeatureMenuSeeder
        $root = Menu::firstOrCreate([
            'nama_menu' => 'Menu Manajemen',
            'url' => '#',
            'icon' => '',
            'parent_id' => '0',
        ], [
            'urutan' => 1,
            'tipe_menu' => 'be',
        ]);

        // Attach root menu to admin role
        DB::table('role_has_menus')->updateOrInsert([
            'menu_id' => $root->id,
            'role_id' => $adminRole->id,
        ], []);
    }
}
