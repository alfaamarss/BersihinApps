<?php

namespace Database\Seeders;

use App\Models\Area;
use App\Models\CollectorAssignment;
use App\Models\Complaint;
use App\Models\GarbageDump;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PocketSize;
use App\Models\Rw;
use App\Models\Schedule;
use App\Models\ServiceAccount;
use App\Models\User;
use App\Models\Waste;
use App\Models\WasteDelivery;
use App\Models\WasteDeliveryItem;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class DemoDashboardSeeder extends Seeder
{
    private const MARKER = '[DEMO-DASHBOARD]';
    private const DEMO_PASSWORD = 'Demo12345!';

    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new \RuntimeException(
                'Demo dashboard data can only be seeded in local or testing environments.'
            );
        }

        DB::transaction(function (): void {
            $now = now();
            $residents = $this->createUsers([
                ['name' => 'Nadia Putri', 'email' => 'demo.warga01@example.test'],
                ['name' => 'Rizky Pratama', 'email' => 'demo.warga02@example.test'],
                ['name' => 'Siti Rahmawati', 'email' => 'demo.warga03@example.test'],
                ['name' => 'Budi Santoso', 'email' => 'demo.warga04@example.test'],
                ['name' => 'Intan Permata', 'email' => 'demo.warga05@example.test'],
                ['name' => 'Andi Wijaya', 'email' => 'demo.warga06@example.test'],
            ], 'warga', $now);

            $collectors = $this->createUsers([
                ['name' => 'Fajar Nugraha', 'email' => 'demo.kolektor01@example.test'],
                ['name' => 'Dewi Anggraini', 'email' => 'demo.kolektor02@example.test'],
            ], 'collector', $now);

            $locations = $this->createLocations();
            $schedules = $this->createSchedules($locations, $collectors, $now);
            $accountsByDistrict = $this->createServiceAccounts(
                $locations,
                $residents,
                $now
            );
            $this->createTps($locations, $collectors, $now);
            $this->createCollectedPickups(
                $locations,
                $accountsByDistrict,
                $schedules,
                $collectors,
                $residents,
                $now
            );
            $this->createPickupRequests(
                $accountsByDistrict,
                $collectors,
                $residents,
                $now
            );
            $this->createComplaints(
                $accountsByDistrict,
                $collectors,
                $residents,
                $now
            );
        });

        $this->command?->info(
            'Demo dashboard data added safely. No existing records were deleted.'
        );
    }

    private function createUsers(array $definitions, string $roleName, $now): array
    {
        $users = [];
        $role = Role::firstOrCreate([
            'name' => $roleName,
            'guard_name' => 'web',
        ]);

        foreach ($definitions as $index => $definition) {
            $createdAt = $now->copy()->subDays(count($definitions) - $index);
            $user = User::firstOrCreate(
                ['email' => $definition['email']],
                [
                    'name' => $definition['name'],
                    'password' => Hash::make(self::DEMO_PASSWORD),
                    'email_verified_at' => $createdAt,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]
            );

            if (! $user->hasRole($roleName, 'web')) {
                $user->assignRole($role);
            }

            $apiRole = Role::firstOrCreate([
                'name' => $roleName,
                'guard_name' => 'api',
            ]);
            if (! $user->hasRole($roleName, 'api')) {
                $user->assignRole($apiRole);
            }

            $user->forceFill([
                'password' => Hash::make(self::DEMO_PASSWORD),
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ])->save();
            $users[] = $user;
        }

        return $users;
    }

    private function createLocations(): array
    {
        $definitions = [
            'Coblong' => ['Dago', 'Lebak Gede'],
            'Sukasari' => ['Sarijadi', 'Sukarasa'],
            'Cidadap' => ['Hegarmanah', 'Ciumbuleuit'],
            'Buahbatu' => ['Cijawura', 'Margasari'],
            'Antapani' => ['Antapani Tengah', 'Antapani Kidul'],
        ];
        $locations = [];

        foreach ($definitions as $districtName => $villages) {
            $district = Area::firstOrCreate(
                ['name' => $districtName, 'level' => 'Kecamatan', 'parent_id' => null]
            );
            $locations[$districtName] = [
                'district' => $district,
                'villages' => [],
            ];

            foreach ($villages as $villageName) {
                $village = Area::firstOrCreate(
                    [
                        'name' => $villageName,
                        'level' => 'Kelurahan',
                        'parent_id' => $district->id,
                    ]
                );
                $rws = [];
                foreach ([1, 2] as $number) {
                    $rws[] = Rw::firstOrCreate([
                        'kelurahan_id' => $village->id,
                        'name' => sprintf('RW %02d', $number),
                    ]);
                }
                $locations[$districtName]['villages'][] = [
                    'area' => $village,
                    'rws' => $rws,
                ];
            }
        }

        return $locations;
    }

    private function createSchedules(array $locations, array $collectors, $now): array
    {
        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        $schedules = [];
        $index = 0;

        foreach ($locations as $district) {
            foreach ($district['villages'] as $village) {
                $schedule = Schedule::firstOrCreate(
                    [
                        'area_id' => $village['area']->id,
                        'day_of_week' => $days[$index % count($days)],
                    ],
                    [
                        'pickup_time' => $index % 2 === 0 ? '08:00:00' : '13:00:00',
                        'is_active' => true,
                        'auto_generate_delivery' => false,
                        'generate_days_ahead' => 7,
                        'created_at' => $now->copy()->subDays(30),
                        'updated_at' => $now,
                    ]
                );

                $collector = $collectors[$index % count($collectors)];
                CollectorAssignment::firstOrCreate(
                    [
                        'user_id' => $collector->id,
                        'schedule_id' => $schedule->id,
                        'start_date' => $now->copy()->subMonths(3)->toDateString(),
                    ],
                    [
                        'rw_id' => $village['rws'][0]->id,
                        'end_date' => null,
                        'created_at' => $now->copy()->subMonths(3),
                        'updated_at' => $now,
                    ]
                );
                $schedules[] = $schedule;
                $index++;
            }
        }

        return $schedules;
    }

    private function createServiceAccounts(
        array $locations,
        array $residents,
        $now
    ): array {
        $accountsByDistrict = [];
        $index = 0;

        foreach ($locations as $districtName => $district) {
            $accountsByDistrict[$districtName] = [];
            foreach ($district['villages'] as $villageIndex => $village) {
                foreach ($village['rws'] as $rwIndex => $rw) {
                    $accountName = sprintf(
                        'Rumah Demo %02d - %s',
                        $index + 1,
                        $village['area']->name
                    );
                    $createdAt = $now->copy()->subDays(20 - $index);
                    $account = ServiceAccount::firstOrCreate(
                        ['name' => $accountName],
                        [
                            'user_id' => $residents[$index % count($residents)]->id,
                            'area_id' => $village['area']->id,
                            'rw_id' => $rw->id,
                            'contact_phone' => sprintf('0812%08d', 10000000 + $index),
                            'address' => sprintf(
                                'Jl. Simulasi %d No. %d, Kelurahan %s, Kecamatan %s',
                                $villageIndex + 1,
                                10 + $index,
                                $village['area']->name,
                                $districtName
                            ),
                            'latitude' =>  -6.88 + ($index * 0.003),
                            'longitude' => 107.60 + ($index * 0.003),
                            'note' => self::MARKER,
                            'status' => 'active',
                            'created_at' => $createdAt,
                            'updated_at' => $createdAt,
                        ]
                    );
                    $account->forceFill([
                        'created_at' => $createdAt,
                        'updated_at' => $createdAt,
                    ])->save();
                    $accountsByDistrict[$districtName][] = $account;
                    $index++;
                }
            }
        }

        return $accountsByDistrict;
    }

    private function createTps(array $locations, array $collectors, $now): void
    {
        $index = 0;
        foreach ($locations as $district) {
            $village = $district['villages'][0];
            $tps = GarbageDump::firstOrCreate(
                ['name' => self::MARKER . ' TPS ' . $district['district']->name],
                [
                    'kecamatan_id' => $district['district']->id,
                    'kelurahan_id' => $village['area']->id,
                    'rw_id' => $village['rws'][0]->id,
                    'address' => 'Jl. Simulasi TPS, Kecamatan ' . $district['district']->name,
                    'latitude' => -6.88 + ($index * 0.01),
                    'longitude' => 107.60 + ($index * 0.01),
                    'capacity_volume' => 12 + ($index * 3),
                    'capacity_weight' => 750 + ($index * 100),
                    'status' => 'active',
                    'created_at' => $now->copy()->subMonths(2),
                    'updated_at' => $now,
                ]
            );

            if ($index < count($collectors)) {
                DB::table('garbage_dump_collector')->updateOrInsert(
                    [
                        'garbage_dump_id' => $tps->id,
                        'collector_id' => $collectors[$index]->id,
                    ],
                    ['created_at' => $now, 'updated_at' => $now]
                );
            }
            $index++;
        }
    }

    private function createCollectedPickups(
        array $locations,
        array $accountsByDistrict,
        array $schedules,
        array $collectors,
        array $residents,
        $now
    ): void {
        $wastes = [
            Waste::firstOrCreate(
                ['code' => 'ORG'],
                ['name' => 'Sampah Organik', 'category' => 'organic']
            ),
            Waste::firstOrCreate(
                ['code' => 'INORG'],
                ['name' => 'Sampah Anorganik', 'category' => 'inorganic']
            ),
        ];
        $pocketSize = PocketSize::firstOrCreate(
            ['name' => 'Sedang'],
            ['description' => 'Ukuran sedang']
        );
        $districtNames = array_keys($locations);
        $sequence = 0;

        foreach ($districtNames as $districtIndex => $districtName) {
            for ($pickupIndex = 0; $pickupIndex < 3; $pickupIndex++) {
                $account = $accountsByDistrict[$districtName][$pickupIndex];
                $bags = 4 + (($districtIndex + $pickupIndex) % 5);
                $dayOffset = min($sequence, max(0, $now->day - 1));
                $collectedAt = $now->copy()->startOfMonth()->addDays($dayOffset);
                $deliveryMarker = self::MARKER . ' scheduled-' . sprintf('%02d', $sequence + 1);
                $delivery = WasteDelivery::firstOrCreate(
                    ['note' => $deliveryMarker],
                    [
                        'service_account_id' => $account->id,
                        'schedule_id' => $schedules[($districtIndex * 2 + $pickupIndex) % count($schedules)]->id,
                        'pickup_date' => $collectedAt->toDateString(),
                        'pickup_type' => 'scheduled',
                        'request_status' => null,
                        'assigned_collector_id' => $collectors[$districtIndex % count($collectors)]->id,
                        'bag_count' => $bags,
                        'base_amount' => $bags * 5000,
                        'extra_fee' => 0,
                        'status' => 'collected',
                        'confirmation_status' => 'confirmed',
                        'scheduled_at' => $collectedAt->copy()->setTime(8, 0),
                        'confirmed_at' => $collectedAt->copy()->setTime(8, 5),
                        'resident_note' => 'Sampah diletakkan di depan rumah.',
                        'total_amount' => $bags * 5000,
                        'created_at' => $collectedAt,
                        'updated_at' => $collectedAt,
                    ]
                );
                $delivery->forceFill([
                    'collected_at' => $collectedAt,
                    'completed_at' => $collectedAt->copy()->addMinutes(25),
                    'created_at' => $collectedAt,
                    'updated_at' => $collectedAt,
                ])->save();

                $unitPrice = 5000;
                WasteDeliveryItem::firstOrCreate(
                    [
                        'waste_delivery_id' => $delivery->id,
                        'waste_id' => $wastes[$sequence % count($wastes)]->id,
                        'pocket_size_id' => $pocketSize->id,
                    ],
                    [
                        'quantity' => $bags,
                        'unit_price' => $unitPrice,
                        'total_price' => $bags * $unitPrice,
                        'created_at' => $collectedAt,
                        'updated_at' => $collectedAt,
                    ]
                );

                $paid = $sequence < 5;
                $invoiceNumber = self::MARKER . '-INV-' . sprintf('%03d', $sequence + 1);
                $invoice = Invoice::firstOrCreate(
                    ['invoice_number' => $invoiceNumber],
                    [
                        'waste_delivery_id' => $delivery->id,
                        'service_account_id' => $account->id,
                        'total_amount' => $bags * $unitPrice,
                        'status' => $paid ? 'paid' : 'not_paid',
                        'issued_at' => $collectedAt->copy()->addMinutes(30),
                        'paid_at' => $paid ? $collectedAt->copy()->addHours(2) : null,
                        'created_at' => $collectedAt->copy()->addMinutes(30),
                        'updated_at' => $collectedAt->copy()->addMinutes(30),
                    ]
                );
                if ($sequence < 7) {
                    $orderId = self::MARKER . '-ORDER-' . sprintf('%03d', $sequence + 1);
                    Payment::firstOrCreate(
                        ['order_id' => $orderId],
                        [
                            'invoice_id' => $invoice->id,
                            'amount' => $bags * $unitPrice,
                            'payment_method' => 'bank_transfer',
                            'payment_type' => 'bank_transfer',
                            'payment_channel' => 'BCA',
                            'status' => $paid ? 'success' : 'pending',
                            'provider_transaction_id' => $paid ? $orderId : null,
                            'created_at' => $collectedAt->copy()->addHours(1),
                            'updated_at' => $collectedAt->copy()->addHours(1),
                        ]
                    );
                }

                $sequence++;
            }
        }
    }

    private function createPickupRequests(
        array $accountsByDistrict,
        array $collectors,
        array $residents,
        $now
    ): void {
        $requests = [
            ['sent', 'pending', 'Menunggu konfirmasi jadwal.'],
            ['processing', 'in_progress', 'Kolektor sedang menuju lokasi.'],
            ['pending', 'pending', 'Sampah sudah siap diambil.'],
            ['paid', 'completed', 'Permintaan sudah selesai.'],
            ['rejected', 'cancelled', 'Jadwal tidak tersedia pada waktu tersebut.'],
        ];
        $districts = array_values($accountsByDistrict);

        foreach ($requests as $index => [$requestStatus, $status, $residentNote]) {
            $account = $districts[$index % count($districts)][$index % 4];
            $requestedAt = $now->copy()->subHours(($index + 1) * 3);
            $marker = self::MARKER . ' request-' . sprintf('%02d', $index + 1);
            WasteDelivery::firstOrCreate(
                ['note' => $marker],
                [
                    'service_account_id' => $account->id,
                    'schedule_id' => null,
                    'pickup_date' => $requestedAt->toDateString(),
                    'pickup_type' => 'request',
                    'request_status' => $requestStatus,
                    'requested_at' => $requestedAt,
                    'requested_pickup_date' => $requestedAt->copy()->addDays(1)->toDateString(),
                    'requested_pickup_time' => '10:00:00',
                    'requested_by' => $residents[$index % count($residents)]->id,
                    'assigned_collector_id' => $collectors[$index % count($collectors)]->id,
                    'bag_count' => 2 + $index,
                    'unit_price_snapshot' => 5000,
                    'base_amount' => (2 + $index) * 5000,
                    'extra_fee' => 0,
                    'status' => $status,
                    'confirmation_status' => 'pending',
                    'resident_note' => $residentNote,
                    'total_amount' => (2 + $index) * 5000,
                    'created_at' => $requestedAt,
                    'updated_at' => $requestedAt,
                ]
            );
        }
    }

    private function createComplaints(
        array $accountsByDistrict,
        array $collectors,
        array $residents,
        $now
    ): void {
        $definitions = [
            ['open', 'Jadwal pengambilan terlewat', 'Sampah belum terangkut sesuai jadwal.'],
            ['open', 'Sampah tertinggal di lokasi', 'Sebagian sampah masih tertinggal setelah pengangkutan.'],
            ['open', 'Permintaan informasi layanan', 'Mohon informasi jadwal pengangkutan berikutnya.'],
            ['in_progress', 'Keterlambatan pengangkutan', 'Pengangkutan terlambat dari jadwal yang diinformasikan.'],
            ['in_progress', 'Kondisi armada', 'Mohon pengecekan kondisi armada pengangkut.'],
            ['in_progress', 'Perubahan jadwal', 'Mohon bantuan untuk menyesuaikan jadwal layanan.'],
            ['resolved', 'Konfirmasi pengangkutan', 'Pengangkutan sudah dilakukan dan laporan dapat ditutup.'],
            ['resolved', 'Kebersihan lokasi TPS', 'Area TPS sudah dibersihkan setelah laporan diterima.'],
            ['resolved', 'Pembaruan data akun', 'Data layanan sudah diperbarui sesuai permintaan.'],
            ['rejected', 'Laporan lokasi ganda', 'Laporan ini merujuk ke lokasi yang sudah ditangani.'],
            ['rejected', 'Permintaan di luar cakupan', 'Lokasi berada di luar area layanan saat ini.'],
            ['rejected', 'Informasi tidak lengkap', 'Laporan tidak memiliki informasi lokasi yang cukup.'],
        ];
        $districts = array_values($accountsByDistrict);

        foreach ($definitions as $index => [$status, $type, $description]) {
            $account = $districts[$index % count($districts)][$index % 4];
            $createdAt = $now->copy()->subDays($index + 1)->subHours($index % 5);
            $description = self::MARKER . ' ' . $description;
            Complaint::firstOrCreate(
                ['description' => $description],
                [
                    'user_id' => $account->user_id,
                    'service_account_id' => $account->id,
                    'assignee_id' => in_array($status, ['in_progress', 'resolved'], true)
                        ? $collectors[$index % count($collectors)]->id
                        : null,
                    'type' => $type,
                    'address' => $account->address,
                    'latitude' => $account->latitude,
                    'longitude' => $account->longitude,
                    'status' => $status,
                    'resolution_description' => $status === 'resolved'
                        ? 'Laporan sudah ditindaklanjuti oleh petugas.'
                        : null,
                    'rejection_reason' => $status === 'rejected'
                        ? 'Laporan ditutup setelah pemeriksaan admin.'
                        : null,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]
            );
        }
    }
}
