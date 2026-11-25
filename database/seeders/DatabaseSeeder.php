<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $data = json_decode(File::get(database_path('data/spbu-initial-data.json')), true, 512, JSON_THROW_ON_ERROR);

        $this->replace($data);
    }

    /** @param array<string, mixed> $data */
    public function replace(array $data): void
    {
        foreach (['sessions', 'activity_logs', 'citizen_reports', 'queue_statuses', 'stock_histories', 'fuel_stocks', 'fuel_types', 'stations', 'users'] as $table) {
            DB::table($table)->delete();
        }

        $this->insertRows('users', $data['users'], [
            'id', 'name', 'email', 'phone', 'role', 'station_id', 'is_active', 'created_at', 'updated_at',
        ], fn (array $row): array => [
            'password' => Hash::make('password'),
        ]);

        $this->insertRows('stations', $data['stations'], [
            'id', 'code', 'name', 'address', 'region', 'latitude', 'longitude', 'phone',
            'opening_hours', 'operational_status', 'facility', 'deleted_at', 'created_at', 'updated_at',
        ]);

        $this->insertRows('fuel_types', $data['fuel_types'], [
            'id', 'name', 'ron', 'description', 'price_per_liter', 'is_active', 'color', 'created_at', 'updated_at',
        ]);

        $this->insertRows('fuel_stocks', $data['fuel_stocks'], [
            'id', 'station_id', 'fuel_type_id', 'stock_liters', 'capacity_liters',
            'low_stock_threshold', 'updated_by', 'created_at', 'updated_at',
        ]);

        $this->insertRows('stock_histories', $data['stock_histories'], [
            'id', 'fuel_stock_id', 'station_id', 'fuel_name', 'previous_stock', 'new_stock',
            'change_amount', 'note', 'updated_by', 'created_at', 'updated_at',
        ]);

        $this->insertRows('queue_statuses', $data['queue_statuses'], [
            'id', 'station_id', 'vehicle_category', 'estimated_wait_minutes', 'queue_level',
            'source', 'updated_by', 'created_at', 'updated_at',
        ]);

        $this->insertRows('citizen_reports', $data['citizen_reports'], [
            'id', 'user_id', 'station_id', 'category', 'description', 'photo_url',
            'status', 'admin_note', 'handled_by', 'created_at', 'updated_at',
        ]);

        $this->insertRows('activity_logs', $data['activity_logs'], [
            'id', 'user_name', 'user_role', 'action', 'entity_type', 'entity_id', 'description', 'created_at', 'updated_at',
        ]);
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @param  list<string>  $columns
     * @param  callable(array<string, mixed>): array<string, mixed>  $extra
     */
    private function insertRows(string $table, array $rows, array $columns, ?callable $extra = null): void
    {
        DB::table($table)->insert(array_map(
            fn (array $row): array => array_merge(
                Arr::only($row, $columns),
                [
                    'created_at' => $row['created_at'] ?? now(),
                    'updated_at' => $row['updated_at'] ?? $row['created_at'] ?? now(),
                ],
                $extra ? $extra($row) : [],
            ),
            $rows,
        ));
    }
}
