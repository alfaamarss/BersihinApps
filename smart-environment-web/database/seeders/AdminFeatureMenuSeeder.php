<?php

namespace Database\Seeders;

use App\Models\Menu;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AdminFeatureMenuSeeder extends Seeder
{
    public function run(): void
    {
        $adminRole = Role::firstOrCreate([
            'name' => 'admin',
            'guard_name' => 'web'
        ]);

        // Ensure we use the main "Menu Manajemen" as the single sidebar root for admin items
        $root = Menu::firstOrCreate([
            'nama_menu' => 'Menu Manajemen',
            'url' => '#',
            'parent_id' => '0',
        ], [
            'icon' => '',
            'urutan' => 1,
            'tipe_menu' => 'be',
        ]);

        // Clean up old menus under root to rebuild fresh structure
        $this->cleanupOldMenus($root);

        // 1. Dashboard
        $dashboard = $this->child($root, 'Dashboard', 'home', 'fas fa-home', 1);

        // 2. Master Data
        $master = $this->child($root, 'Master Data', '#', 'fas fa-database', 2);
            $this->child($master, 'Pengaturan', 'admin/settings', '', 1);
            $this->child($master, 'Akun Layanan', 'admin/service-accounts', '', 2);
            $this->child($master, 'Area', 'admin/areas', '', 3);
            $this->child($master, 'Sampah', 'admin/wastes', '', 4);

        // 3. Jadwal Pengangkutan
        $jadwal = $this->child($root, 'Jadwal Pengangkutan', '#', 'fas fa-calendar', 3);
            $this->child($jadwal, 'Daftar Jadwal', 'admin/schedules', '', 1);
            $this->child($jadwal, 'Kalender Jadwal', 'admin/schedules/calendar', '', 2);

        // 4. Penugasan Collector
        $tugas = $this->child($root, 'Penugasan Collector', 'admin/collector-assignments', 'fas fa-user-check', 4);

        // 5. Operasional
        $operasional = $this->child($root, 'Operasional', '#', 'fas fa-truck', 5);
            $this->child($operasional, 'Monitor Pengambilan', 'admin/pickups/monitor', '', 1);
            $this->child($operasional, 'Permintaan Luar Jadwal', 'admin/off-schedule-pickups', '', 2);
            $this->child($operasional, 'Daftar TPS/TPA', 'admin/garbage-dumps', '', 3);
            $this->child($operasional, 'Pengangkutan ke TPS', 'admin/tps-deposits', '', 4);
            $this->child($operasional, 'Pengangkutan ke TPA', 'admin/tpa-transports', '', 5);

        // 6. Pembayaran
        $billing = $this->child($root, 'Pembayaran', '#', 'fas fa-file-invoice', 6);
            $this->child($billing, 'Invoices', 'admin/invoices', '', 1);
            $this->child($billing, 'Payments', 'admin/payments', '', 2);

        // 7. Komplain
        $komplain = $this->child($root, 'Komplain', 'admin/complaints', 'fas fa-exclamation-triangle', 7);

        // 8. Artikel
        $artikel = $this->child($root, 'Artikel', 'admin/articles', 'fas fa-newspaper', 8);

        // 9. Manajemen Pengguna
        $pengguna = $this->child($root, 'Manajemen Pengguna', '#', 'fas fa-users-cog', 9);
            $this->child($pengguna, 'Kelola Pengguna', 'manage-user', '', 1);
            $this->child($pengguna, 'Kelola Role', 'manage-role', '', 2);
            // $this->child($pengguna, 'Kelola Menu', 'manage-menu', '', 3);

        // Attach minimal permissions by domain for visibility
        foreach ([
            'schedule', 'collector_assignment', 'complaint', 'waste_delivery', 'waste_collection', 
            'waste_carry', 'garbage_dump', 'invoice', 'payment', 'area', 'service_account', 
            'waste', 'article', 'tpa_transport', 'tps_deposit', 'user', 'role', 'menu', 'setting'
        ] as $entity) {
            foreach (['create', 'read', 'update', 'delete'] as $action) {
                $perm = Permission::firstOrCreate([
                    'name' => $action . '_' . $entity,
                    'guard_name' => 'web'
                ]);
                
                if (!$adminRole->hasPermissionTo($perm)) {
                    $adminRole->givePermissionTo($perm);
                }
            }
        }

        // Attach all menus to admin role
        $sections = collect([
            $dashboard->id, $master->id, $jadwal->id, $tugas->id, 
            $operasional->id, $billing->id, $komplain->id, $artikel->id, $pengguna->id
        ]);
        $childIds = Menu::whereIn('parent_id', $sections)->pluck('id');
        $attachIds = $sections->merge($childIds)->unique();
        
        foreach ($attachIds as $mid) {
            DB::table('role_has_menus')->updateOrInsert([
                'menu_id' => $mid,
                'role_id' => $adminRole->id,
            ], []);
        }
    }

    private function cleanupOldMenus(Menu $root): void
    {
        // Get all child menus under root
        $childMenus = Menu::where('parent_id', $root->id)->get();
        
        foreach ($childMenus as $menu) {
            // Get grandchildren
            $grandchildren = Menu::where('parent_id', $menu->id)->pluck('id');
            
            // Delete from role_has_menus
            DB::table('role_has_menus')->whereIn('menu_id', $grandchildren)->delete();
            DB::table('role_has_menus')->where('menu_id', $menu->id)->delete();
            
            // Delete grandchildren menus
            Menu::whereIn('id', $grandchildren)->delete();
            
            // Delete child menu
            $menu->delete();
        }
    }

    private function child(Menu $parent, string $name, string $url, string $icon, int $order): Menu
    {
        return Menu::firstOrCreate([
            'nama_menu' => $name,
            'url' => $url,
            'parent_id' => (string) $parent->id,
        ], [
            'icon' => $icon,
            'urutan' => $order,
            'tipe_menu' => 'be',
        ]);
    }
}
