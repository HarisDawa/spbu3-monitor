<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\CitizenReport;
use App\Models\FuelStock;
use App\Models\FuelType;
use App\Models\QueueStatus;
use App\Models\Station;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class MonitoringController extends Controller
{
    private const REPORT_STATUSES = ['Menunggu', 'Diverifikasi', 'Ditindaklanjuti', 'Selesai', 'Ditolak'];

    private const QUEUE_LEVELS = ['Lancar', 'Sedang', 'Padat', 'Tutup'];

    public function storeUser(Request $request): JsonResponse
    {
        $this->ensureRole($request, ['admin']);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:50'],
            'role' => ['required', Rule::in(['admin', 'petugas', 'warga'])],
            'station_id' => ['nullable', 'exists:stations,id'],
        ]);

        $user = User::create([
            ...$data,
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);

        $this->audit($request, 'CREATE', 'Pengguna', (string) $user->id, "Menambahkan pengguna baru: {$user->name} ({$user->role})");

        return response()->json(['ok' => true, 'id' => $user->id]);
    }

    public function updateUser(Request $request, User $user): JsonResponse
    {
        if ($request->user()->id !== $user->id) {
            $this->ensureRole($request, ['admin']);
        }

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['sometimes', 'nullable', 'string', 'max:50'],
            'role' => ['sometimes', Rule::in(['admin', 'petugas', 'warga'])],
            'station_id' => ['sometimes', 'nullable', 'exists:stations,id'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        if ($request->user()->role === 'warga') {
            $data = array_intersect_key($data, array_flip(['name', 'email', 'phone']));
        }

        $user->update($data);
        $this->audit($request, 'UPDATE', 'Pengguna', (string) $user->id, "Memperbarui data pengguna: {$user->name}");

        return response()->json(['ok' => true]);
    }

    public function destroyUser(Request $request, User $user): JsonResponse
    {
        $this->ensureRole($request, ['admin']);
        abort_if($user->id === $request->user()->id, 422, 'Tidak dapat menghapus akun yang sedang aktif.');

        $name = $user->name;
        $user->delete();
        $this->audit($request, 'DELETE', 'Pengguna', (string) $user->id, "Menghapus pengguna: {$name}");

        return response()->json(['ok' => true]);
    }

    public function storeStation(Request $request): JsonResponse
    {
        $this->ensureRole($request, ['admin']);

        $data = $this->validateStation($request);
        $data['id'] = 'SPBU-'.$data['code'];

        abort_if(Station::withTrashed()->where('id', $data['id'])->exists(), 422, 'Kode SPBU sudah terdaftar.');

        DB::transaction(function () use ($data, $request): void {
            Station::create($data);

            foreach (FuelType::where('is_active', true)->orderBy('id')->limit(4)->get() as $fuelType) {
                FuelStock::create([
                    'station_id' => $data['id'],
                    'fuel_type_id' => $fuelType->id,
                    'stock_liters' => 15000,
                    'capacity_liters' => 25000,
                    'low_stock_threshold' => 3000,
                    'updated_by' => $request->user()->name,
                ]);
            }

            QueueStatus::create([
                'station_id' => $data['id'],
                'vehicle_category' => 'Semua Kendaraan',
                'estimated_wait_minutes' => 5,
                'queue_level' => 'Lancar',
                'source' => 'Petugas',
                'updated_by' => $request->user()->name,
            ]);
        });

        $this->audit($request, 'CREATE', 'SPBU', $data['id'], "Menambahkan SPBU baru: {$data['name']} ({$data['code']})");

        return response()->json(['ok' => true, 'id' => $data['id']]);
    }

    public function updateStation(Request $request, Station $station): JsonResponse
    {
        $this->ensureRole($request, ['admin']);

        $request->merge(['code' => $station->code]);

        $station->update($this->validateStation($request, $station));
        $this->audit($request, 'UPDATE', 'SPBU', $station->id, "Memperbarui data SPBU: {$station->name}");

        return response()->json(['ok' => true]);
    }

    public function destroyStation(Request $request, Station $station): JsonResponse
    {
        $this->ensureRole($request, ['admin']);

        $station->delete();
        $this->audit($request, 'DELETE', 'SPBU', $station->id, "Menonaktifkan / Soft delete SPBU: {$station->name}");

        return response()->json(['ok' => true]);
    }

    public function toggleStation(Request $request, Station $station): JsonResponse
    {
        $this->ensureRole($request, ['admin']);

        $status = match ($station->operational_status) {
            'Buka' => 'Tutup',
            'Tutup' => 'Perbaikan',
            default => 'Buka',
        };

        $station->update(['operational_status' => $status]);
        $this->audit($request, 'UPDATE_STATUS', 'SPBU', $station->id, "Mengubah status operasional {$station->name} ke: {$status}");

        return response()->json(['ok' => true, 'status' => $status]);
    }

    public function storeFuelType(Request $request): JsonResponse
    {
        $this->ensureRole($request, ['admin']);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:fuel_types,name'],
            'ron' => ['nullable', 'string', 'max:50'],
            'price_per_liter' => ['required', 'integer', 'min:0'],
            'description' => ['nullable', 'string', 'max:1000'],
            'color' => ['nullable', 'string', 'max:20'],
        ]);

        $fuelType = FuelType::create([
            ...$data,
            'ron' => $data['ron'] ?: 'Oktan/Cetane',
            'description' => $data['description'] ?? '',
            'color' => $data['color'] ?: '#0284c7',
            'is_active' => true,
        ]);

        $this->audit($request, 'CREATE', 'Jenis BBM', (string) $fuelType->id, "Menambahkan jenis BBM: {$fuelType->name}");

        return response()->json(['ok' => true, 'id' => $fuelType->id]);
    }

    public function updateFuelType(Request $request, FuelType $fuelType): JsonResponse
    {
        $this->ensureRole($request, ['admin']);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('fuel_types', 'name')->ignore($fuelType->id)],
            'ron' => ['nullable', 'string', 'max:50'],
            'price_per_liter' => ['required', 'integer', 'min:0'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $oldPrice = $fuelType->price_per_liter;
        $fuelType->update($data);
        $description = $oldPrice !== $fuelType->price_per_liter
            ? "Penyesuaian harga {$fuelType->name}: Rp {$oldPrice} -> Rp {$fuelType->price_per_liter}"
            : "Memperbarui info jenis BBM {$fuelType->name}";

        $this->audit($request, 'UPDATE', 'Jenis BBM', (string) $fuelType->id, $description);

        return response()->json(['ok' => true]);
    }

    public function toggleFuelType(Request $request, FuelType $fuelType): JsonResponse
    {
        $this->ensureRole($request, ['admin']);

        $fuelType->update(['is_active' => ! $fuelType->is_active]);
        $status = $fuelType->is_active ? 'diaktifkan' : 'dinonaktifkan';
        $this->audit($request, 'STATUS', 'Jenis BBM', (string) $fuelType->id, "BBM {$fuelType->name} {$status}");

        return response()->json(['ok' => true, 'is_active' => $fuelType->is_active]);
    }

    public function updateStock(Request $request, FuelStock $fuelStock): JsonResponse
    {
        $this->ensureRole($request, ['admin', 'petugas']);

        $data = $request->validate([
            'stock_liters' => ['required', 'integer', 'min:0', 'max:'.$fuelStock->capacity_liters],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $previous = $fuelStock->stock_liters;
        $change = $data['stock_liters'] - $previous;

        DB::transaction(function () use ($fuelStock, $data, $previous, $change, $request): void {
            $fuelStock->update([
                'stock_liters' => $data['stock_liters'],
                'updated_by' => $request->user()->name,
            ]);

            $fuelStock->histories()->create([
                'station_id' => $fuelStock->station_id,
                'fuel_name' => $fuelStock->fuelType->name,
                'previous_stock' => $previous,
                'new_stock' => $data['stock_liters'],
                'change_amount' => $change,
                'note' => $data['note'] ?: 'Pembaruan manual petugas',
                'updated_by' => $request->user()->name,
            ]);
        });

        $this->audit($request, 'UPDATE_STOCK', 'Stok BBM', (string) $fuelStock->id, "Update stok {$fuelStock->fuelType->name} di {$fuelStock->station_id}: {$previous}L -> {$data['stock_liters']}L ({$change}L)");

        return response()->json(['ok' => true]);
    }

    public function updateQueue(Request $request, Station $station): JsonResponse
    {
        $this->ensureRole($request, ['admin', 'petugas']);

        $data = $request->validate([
            'vehicle_category' => ['nullable', 'string', 'max:100'],
            'estimated_wait_minutes' => ['required', 'integer', 'min:0', 'max:300'],
            'queue_level' => ['required', Rule::in(self::QUEUE_LEVELS)],
            'source' => ['nullable', 'string', 'max:100'],
        ]);

        $queue = QueueStatus::firstOrNew(['station_id' => $station->id]);
        $queue->fill([
            'vehicle_category' => $data['vehicle_category'] ?: 'Semua Kendaraan',
            'estimated_wait_minutes' => $data['estimated_wait_minutes'],
            'queue_level' => $data['queue_level'],
            'source' => $data['source'] ?: ($request->user()->role === 'petugas' ? 'Petugas' : 'Simulasi'),
            'updated_by' => $request->user()->name,
        ])->save();

        $this->audit($request, 'UPDATE_QUEUE', 'Antrean', $station->id, "Update antrean SPBU: {$queue->queue_level} (Est. {$queue->estimated_wait_minutes} menit)");

        return response()->json(['ok' => true, 'id' => $queue->id]);
    }

    public function simulateTick(Request $request): JsonResponse
    {
        $this->ensureRole($request, ['admin', 'petugas']);

        $station = Station::where('operational_status', 'Buka')->inRandomOrder()->first();

        if ($station === null) {
            return response()->json(['ok' => false, 'message' => 'Tidak ada SPBU beroperasi.'], 422);
        }

        $level = fake()->randomElement(['Lancar', 'Sedang', 'Padat']);
        $wait = match ($level) {
            'Lancar' => fake()->numberBetween(3, 7),
            'Sedang' => fake()->numberBetween(10, 19),
            default => fake()->numberBetween(20, 34),
        };

        $queue = QueueStatus::firstOrNew(['station_id' => $station->id]);
        $queue->fill([
            'vehicle_category' => 'Semua Kendaraan',
            'estimated_wait_minutes' => $wait,
            'queue_level' => $level,
            'source' => 'Sensor ATG',
            'updated_by' => $request->user()->name,
        ])->save();

        $this->audit($request, 'UPDATE_QUEUE', 'Antrean', $station->id, "Simulasi sensor: {$station->name} menjadi {$level} ({$wait} menit)");

        return response()->json(['ok' => true, 'station_id' => $station->id, 'queue_level' => $level, 'estimated_wait_minutes' => $wait]);
    }

    public function storeReport(Request $request): JsonResponse
    {
        $data = $request->validate([
            'station_id' => ['required', 'exists:stations,id'],
            'category' => ['required', 'string', 'max:100'],
            'description' => ['required', 'string', 'max:5000'],
            'photo_url' => ['nullable', 'string', 'max:5000000'],
        ]);

        $code = 'LAP-'.now()->format('Ymd').'-'.str_pad((string) (CitizenReport::count() + 1), 3, '0', STR_PAD_LEFT);

        $report = CitizenReport::create([
            ...$data,
            'id' => $code,
            'user_id' => $request->user()->id,
            'photo_url' => $data['photo_url'] ?? null,
            'status' => 'Menunggu',
        ]);

        $this->audit($request, 'CREATE_REPORT', 'Laporan Warga', $code, "Laporan warga baru #{$code} diajukan untuk {$report->station->name}");

        return response()->json(['ok' => true, 'id' => $report->id]);
    }

    public function updateReport(Request $request, CitizenReport $citizenReport): JsonResponse
    {
        $this->ensureRole($request, ['admin', 'petugas']);

        $data = $request->validate([
            'status' => ['required', Rule::in(self::REPORT_STATUSES)],
            'admin_note' => ['nullable', 'string', 'max:5000'],
        ]);

        $citizenReport->fill([
            'status' => $data['status'],
            'admin_note' => $data['admin_note'] ?: $citizenReport->admin_note,
            'handled_by' => $request->user()->name,
        ])->save();

        $this->audit($request, 'UPDATE_REPORT', 'Laporan Warga', $citizenReport->id, "Status laporan #{$citizenReport->id} diubah ke: {$citizenReport->status}");

        return response()->json(['ok' => true]);
    }

    public function destroyReport(Request $request, CitizenReport $citizenReport): JsonResponse
    {
        $this->ensureRole($request, ['admin', 'petugas']);

        $id = $citizenReport->id;
        $citizenReport->delete();
        $this->audit($request, 'DELETE_REPORT', 'Laporan Warga', $id, "Menghapus laporan #{$id}");

        return response()->json(['ok' => true]);
    }

    public function reset(Request $request): JsonResponse
    {
        $this->ensureRole($request, ['admin']);

        (new DatabaseSeeder)->run();
        $this->audit($request, 'RESET', 'Sistem', '-', 'Reset database ke kondisi awal demo');

        return response()->json(['ok' => true]);
    }

    public function import(Request $request): JsonResponse
    {
        $this->ensureRole($request, ['admin']);

        $data = $request->validate([
            'stations' => ['required', 'array'],
            'fuel_stocks' => ['required', 'array'],
            'users' => ['required', 'array'],
        ]);

        (new DatabaseSeeder)->replace($request->all());
        $this->audit($request, 'IMPORT', 'Sistem', '-', 'Impor snapshot JSON ke database');

        return response()->json(['ok' => true]);
    }

    /** @param list<string> $roles */
    private function ensureRole(Request $request, array $roles): void
    {
        abort_unless(in_array($request->user()?->role, $roles, true), 403, 'Aksi tidak diizinkan untuk peran ini.');
    }

    /** @return array<string, mixed> */
    private function validateStation(Request $request, ?Station $station = null): array
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:30', Rule::unique('stations', 'code')->ignore($station?->id, 'id')],
            'name' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:500'],
            'region' => ['required', 'string', 'max:150'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'phone' => ['nullable', 'string', 'max:50'],
            'opening_hours' => ['nullable', 'string', 'max:100'],
            'operational_status' => ['nullable', Rule::in(['Buka', 'Tutup', 'Perbaikan'])],
            'facility' => ['nullable', 'string', 'max:500'],
        ]);

        $data['phone'] ??= '-';
        $data['opening_hours'] ??= '24 Jam';
        $data['operational_status'] ??= 'Buka';
        $data['facility'] ??= 'Toilet, Musholla';

        return $data;
    }

    private function audit(Request $request, string $action, string $entityType, string $entityId, string $description): void
    {
        ActivityLog::create([
            'user_name' => $request->user()->name,
            'user_role' => $request->user()->role,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'description' => $description,
        ]);
    }
}
