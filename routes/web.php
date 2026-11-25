<?php

use App\Http\Controllers\Api\MonitoringController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1')->name('login.store');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/warga', [DashboardController::class, 'warga'])->name('warga');

    Route::post('/api/switch-user', [AuthController::class, 'switchUser'])->name('api.switch-user');

    Route::post('/api/users', [MonitoringController::class, 'storeUser'])->name('api.users.store');
    Route::put('/api/users/{user}', [MonitoringController::class, 'updateUser'])->name('api.users.update');
    Route::delete('/api/users/{user}', [MonitoringController::class, 'destroyUser'])->name('api.users.destroy');

    Route::post('/api/stations', [MonitoringController::class, 'storeStation'])->name('api.stations.store');
    Route::put('/api/stations/{station}', [MonitoringController::class, 'updateStation'])->name('api.stations.update');
    Route::delete('/api/stations/{station}', [MonitoringController::class, 'destroyStation'])->name('api.stations.destroy');
    Route::post('/api/stations/{station}/toggle', [MonitoringController::class, 'toggleStation'])->name('api.stations.toggle');

    Route::post('/api/fuel-types', [MonitoringController::class, 'storeFuelType'])->name('api.fuel-types.store');
    Route::put('/api/fuel-types/{fuel_type}', [MonitoringController::class, 'updateFuelType'])->name('api.fuel-types.update');
    Route::post('/api/fuel-types/{fuel_type}/toggle', [MonitoringController::class, 'toggleFuelType'])->name('api.fuel-types.toggle');

    Route::put('/api/fuel-stocks/{fuel_stock}', [MonitoringController::class, 'updateStock'])->name('api.fuel-stocks.update');

    Route::put('/api/queue-statuses/{station}', [MonitoringController::class, 'updateQueue'])->name('api.queue-statuses.update');
    Route::post('/api/queue-statuses/simulate', [MonitoringController::class, 'simulateTick'])->name('api.queue-statuses.simulate');

    Route::post('/api/deliveries', [MonitoringController::class, 'storeDelivery'])->name('api.deliveries.store');
    Route::put('/api/deliveries/{fuel_delivery}', [MonitoringController::class, 'updateDelivery'])->name('api.deliveries.update');
    Route::delete('/api/deliveries/{fuel_delivery}', [MonitoringController::class, 'destroyDelivery'])->name('api.deliveries.destroy');
    Route::post('/api/deliveries/simulate', [MonitoringController::class, 'simulateDeliveries'])->name('api.deliveries.simulate');

    Route::post('/api/reports', [MonitoringController::class, 'storeReport'])->name('api.reports.store');
    Route::put('/api/reports/{citizen_report}', [MonitoringController::class, 'updateReport'])->name('api.reports.update');
    Route::delete('/api/reports/{citizen_report}', [MonitoringController::class, 'destroyReport'])->name('api.reports.destroy');

    Route::post('/api/system/reset', [MonitoringController::class, 'reset'])->name('api.system.reset');
    Route::post('/api/system/import', [MonitoringController::class, 'import'])->name('api.system.import');
});
