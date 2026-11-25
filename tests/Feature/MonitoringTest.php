<?php

namespace Tests\Feature;

use App\Models\CitizenReport;
use App\Models\FuelStock;
use App\Models\Station;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MonitoringTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/')->assertRedirect('/login');
        $this->get('/warga')->assertRedirect('/login');
    }

    public function test_demo_account_can_log_in(): void
    {
        $this->post('/login', [
            'email' => 'admin@spbumonitor.test',
            'password' => 'password',
        ])->assertRedirect('/');

        $this->assertAuthenticatedAs(User::where('email', 'admin@spbumonitor.test')->firstOrFail());
    }

    public function test_dashboard_receives_database_snapshot(): void
    {
        $this->actingAs($this->user('admin@spbumonitor.test'))
            ->get('/')
            ->assertOk()
            ->assertViewHas('bootstrap', fn (array $bootstrap): bool => count($bootstrap['stations']) === 8
                && count($bootstrap['fuel_stocks']) === 30
                && count($bootstrap['citizen_reports']) === 10
                && $bootstrap['currentUser']['role'] === 'admin');
    }

    public function test_pages_expose_csrf_token_and_named_navigation(): void
    {
        $this->actingAs($this->user('admin@spbumonitor.test'))
            ->get('/')
            ->assertOk()
            ->assertSee('<meta name="csrf-token"', false)
            ->assertSee('href="'.route('warga').'"', false);

        $this->actingAs($this->user('warga@spbumonitor.test'))
            ->get('/warga')
            ->assertOk()
            ->assertSee('<meta name="csrf-token"', false)
            ->assertSee('href="'.route('dashboard').'"', false);
    }

    public function test_role_switch_updates_session_user(): void
    {
        $warga = $this->user('warga@spbumonitor.test');
        $admin = $this->actingAs($this->user('admin@spbumonitor.test'));
        $admin->get('/')->assertOk();
        $oldToken = csrf_token();

        $admin->postJson('/api/switch-user', ['id' => $warga->id])
            ->assertOk();

        $this->assertAuthenticatedAs($warga);
        $this->assertNotSame($oldToken, csrf_token());
    }

    public function test_admin_stock_update_records_history(): void
    {
        $this->actingAs($this->user('admin@spbumonitor.test'))
            ->putJson('/api/fuel-stocks/101', [
                'stock_liters' => 25000,
                'note' => 'Dropping TBBM Plumpang',
            ])->assertOk();

        $this->assertDatabaseHas('fuel_stocks', ['id' => 101, 'stock_liters' => 25000]);
        $this->assertDatabaseHas('stock_histories', [
            'fuel_stock_id' => 101,
            'previous_stock' => 24500,
            'new_stock' => 25000,
            'note' => 'Dropping TBBM Plumpang',
        ]);
    }

    public function test_stock_update_rejects_amount_above_capacity(): void
    {
        $this->actingAs($this->user('admin@spbumonitor.test'))
            ->putJson('/api/fuel-stocks/101', ['stock_liters' => 999999])
            ->assertStatus(422);

        $this->assertSame(24500, FuelStock::findOrFail(101)->stock_liters);
    }

    public function test_petugas_cannot_manage_stations(): void
    {
        $this->actingAs($this->user('petugas@spbumonitor.test'))
            ->postJson('/api/stations', $this->stationPayload())
            ->assertForbidden();
    }

    public function test_warga_can_submit_report(): void
    {
        $this->actingAs($this->user('warga@spbumonitor.test'))
            ->postJson('/api/reports', [
                'station_id' => 'SPBU-34.124.01',
                'category' => 'Antrean panjang',
                'description' => 'Antrean motor mencapai jalan utama.',
            ])->assertOk();

        $this->assertDatabaseHas('citizen_reports', [
            'station_id' => 'SPBU-34.124.01',
            'status' => 'Menunggu',
            'description' => 'Antrean motor mencapai jalan utama.',
        ]);
    }

    public function test_warga_cannot_change_report_status(): void
    {
        $this->actingAs($this->user('warga@spbumonitor.test'))
            ->putJson('/api/reports/LAP-20261120-001', ['status' => 'Selesai'])
            ->assertForbidden();

        $this->assertSame('Selesai', CitizenReport::findOrFail('LAP-20261120-001')->status);
    }

    public function test_admin_can_reset_demo_data(): void
    {
        $this->actingAs($this->user('admin@spbumonitor.test'))
            ->postJson('/api/system/reset')
            ->assertOk();

        $this->assertSame(8, Station::count());
        $this->assertSame(10, CitizenReport::count());
    }

    private function user(string $email): User
    {
        return User::where('email', $email)->firstOrFail();
    }

    /** @return array<string, mixed> */
    private function stationPayload(): array
    {
        return [
            'code' => '99.999.99',
            'name' => 'SPBU Uji',
            'address' => 'Jl. Uji No. 1',
            'region' => 'Jakarta Selatan',
            'latitude' => -6.2,
            'longitude' => 106.8,
        ];
    }
}
