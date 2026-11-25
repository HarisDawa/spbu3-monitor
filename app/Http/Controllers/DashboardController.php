<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\CitizenReport;
use App\Models\FuelStock;
use App\Models\FuelType;
use App\Models\QueueStatus;
use App\Models\Station;
use App\Models\StockHistory;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index(): View
    {
        return view('admin', ['bootstrap' => $this->snapshot()]);
    }

    public function warga(): View
    {
        return view('warga', ['bootstrap' => $this->snapshot()]);
    }

    /** @return array<string, mixed> */
    private function snapshot(): array
    {
        /** @var User $user */
        $user = Auth::user();

        return [
            'users' => User::orderBy('id')->get()->map(fn (User $item): array => [
                'id' => $item->id,
                'name' => $item->name,
                'email' => $item->email,
                'role' => $item->role,
                'phone' => $item->phone,
                'station_id' => $item->station_id,
                'is_active' => $item->is_active,
                'created_at' => $this->formatDate($item->created_at),
            ])->all(),
            'fuel_types' => FuelType::orderBy('id')->get()->map(fn (FuelType $item): array => [
                'id' => $item->id,
                'name' => $item->name,
                'ron' => $item->ron,
                'description' => $item->description,
                'price_per_liter' => $item->price_per_liter,
                'is_active' => $item->is_active,
                'color' => $item->color,
            ])->all(),
            'stations' => Station::orderBy('id')->get()->map(fn (Station $item): array => [
                'id' => $item->id,
                'code' => $item->code,
                'name' => $item->name,
                'address' => $item->address,
                'region' => $item->region,
                'latitude' => $item->latitude,
                'longitude' => $item->longitude,
                'phone' => $item->phone,
                'opening_hours' => $item->opening_hours,
                'operational_status' => $item->operational_status,
                'facility' => $item->facility,
                'deleted_at' => null,
                'created_at' => $this->formatDate($item->created_at),
            ])->all(),
            'fuel_stocks' => FuelStock::orderBy('id')->get()->map(fn (FuelStock $item): array => [
                'id' => $item->id,
                'station_id' => $item->station_id,
                'fuel_type_id' => $item->fuel_type_id,
                'stock_liters' => $item->stock_liters,
                'capacity_liters' => $item->capacity_liters,
                'low_stock_threshold' => $item->low_stock_threshold,
                'updated_by' => $item->updated_by,
                'updated_at' => $this->formatDate($item->updated_at),
            ])->all(),
            'stock_histories' => StockHistory::with('station')->latest()->get()->map(fn (StockHistory $item): array => [
                'id' => $item->id,
                'fuel_stock_id' => $item->fuel_stock_id,
                'station_id' => $item->station_id,
                'fuel_name' => $item->fuel_name,
                'previous_stock' => $item->previous_stock,
                'new_stock' => $item->new_stock,
                'change_amount' => $item->change_amount,
                'note' => $item->note,
                'updated_by' => $item->updated_by,
                'created_at' => $this->formatDate($item->created_at),
            ])->all(),
            'queue_statuses' => QueueStatus::orderBy('id')->get()->map(fn (QueueStatus $item): array => [
                'id' => $item->id,
                'station_id' => $item->station_id,
                'vehicle_category' => $item->vehicle_category,
                'estimated_wait_minutes' => $item->estimated_wait_minutes,
                'queue_level' => $item->queue_level,
                'source' => $item->source,
                'updated_by' => $item->updated_by,
                'updated_at' => $this->formatDate($item->updated_at),
            ])->all(),
            'citizen_reports' => CitizenReport::with(['user', 'station'])->latest()->get()->map(fn (CitizenReport $item): array => [
                'id' => $item->id,
                'report_code' => $item->id,
                'user_id' => $item->user_id,
                'user_name' => $item->user?->name ?? 'Warga',
                'station_id' => $item->station_id,
                'station_name' => $item->station?->name ?? 'SPBU Umum',
                'category' => $item->category,
                'description' => $item->description,
                'photo_url' => $item->photo_url,
                'status' => $item->status,
                'admin_note' => $item->admin_note,
                'handled_by' => $item->handled_by,
                'created_at' => $this->formatDate($item->created_at),
                'updated_at' => $this->formatDate($item->updated_at),
            ])->all(),
            'activity_logs' => ActivityLog::latest()->limit(200)->get()->map(fn (ActivityLog $item): array => [
                'id' => $item->id,
                'user_name' => $item->user_name,
                'user_role' => $item->user_role,
                'action' => $item->action,
                'entity_type' => $item->entity_type,
                'entity_id' => $item->entity_id,
                'description' => $item->description,
                'created_at' => $this->formatDate($item->created_at),
            ])->all(),
            'currentUser' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'phone' => $user->phone,
                'station_id' => $user->station_id,
            ],
        ];
    }

    private function formatDate(?Carbon $date): ?string
    {
        return $date?->format('Y-m-d H:i');
    }
}
