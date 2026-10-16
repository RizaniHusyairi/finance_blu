<?php

namespace Tests\Feature;

use App\Http\Controllers\MitraJasaPenjualanController;
use App\Models\MitraJasa;
use App\Models\MitraJasaPenjualan;
use App\Models\SipandaiReconciliation;
use App\Models\User;
use App\Services\SipandaiReconciliationService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ViewErrorBag;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;

class SipandaiReconciliationTest extends TestCase
{
    private SipandaiReconciliationService $service;

    private MitraJasaPenjualan $report;

    protected function setUp(): void
    {
        parent::setUp();
        // Isolated schema: never migrate or modify the developer's MySQL data.
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        Schema::create('mitra_jasa_penjualan', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('mitra_jasa_id');
            $table->unsignedBigInteger('layanan_jasa_id');
            $table->unsignedBigInteger('kontrak_mitra_jasa_id')->nullable();
            $table->unsignedBigInteger('tagihan_jasa_id')->nullable();
            $table->string('status');
            $table->date('periode_mulai');
            $table->date('periode_selesai');
            $table->json('penerbangan_details');
            $table->decimal('total_omzet');
            $table->decimal('nilai_tagihan');
            $table->timestamps();
            $table->softDeletes();
        });
        (require database_path('migrations/2026_10_07_000001_create_sipandai_reconciliations_table.php'))->up();
        config(['sipandai.enabled' => true, 'sipandai.url' => 'http://127.0.0.1:3001/api',
            'sipandai.token' => str_repeat('x', 40), 'sipandai.airlines' => [7 => 'GA']]);
        Http::preventStrayRequests();
        $this->service = app(SipandaiReconciliationService::class);
        $this->report = MitraJasaPenjualan::create([
            'mitra_jasa_id' => 7, 'layanan_jasa_id' => 2, 'status' => 'diajukan',
            'periode_mulai' => '2026-10-07', 'periode_selesai' => '2026-10-07',
            'penerbangan_details' => [['nomor_penerbangan' => 'GA 123', 'pax_dewasa' => 90, 'pax_anak' => 5, 'pax_bayi' => 2, 'pax_transit' => 3]],
            'total_omzet' => 97, 'nilai_tagihan' => 97000,
        ]);
    }

    private function payload(array $overrides = []): array
    {
        return array_replace([
            'schemaVersion' => 1, 'timezone' => 'Asia/Makassar', 'basis' => 'valid_scans_not_confirmed_boarding',
            'from' => '2026-10-07', 'to' => '2026-10-07', 'airlineCode' => 'GA', 'generatedAt' => '2026-10-07T10:00:00Z',
            'flights' => [['id' => 'f1', 'flightNumber' => 'GA123', 'airlineCode' => 'GA', 'std' => '2026-10-07T01:00:00Z', 'status' => 'CLOSED', 'passengers' => 98]],
        ], $overrides);
    }

    private function fake(array $overrides = []): void
    {
        Http::fake(['127.0.0.1:3001/*' => Http::response($this->payload($overrides))]);
    }

    public function test_fetch_compares_all_categories_without_changing_invoice_basis(): void
    {
        $this->fake();
        $snapshot = $this->service->fetch($this->report, 1);
        $this->assertSame(100, $snapshot->comparison[0]['reported']);
        $this->assertSame(-2, $snapshot->comparison[0]['difference']);
        $this->assertSame('97.00', $this->report->fresh()->total_omzet);
        $this->assertSame('97000.00', $this->report->fresh()->nilai_tagihan);
        Http::assertSent(fn ($request) => $request['airline'] === 'GA' && $request['from'] === '2026-10-07' && $request->hasHeader('Authorization', 'Bearer '.str_repeat('x', 40)));
    }

    public function test_acceptance_required_before_verification_and_records_actor(): void
    {
        $this->fake();
        $snapshot = $this->service->fetch($this->report, 1);
        try {
            $this->service->assertVerifiedBasis($this->report);
            $this->fail('Must block pending reconciliation');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('rekonsiliasi', $e->getMessage());
        }
        $this->service->accept($this->report, $snapshot->id, 2, 'Selisih dua penumpang sudah diperiksa petugas.');
        $this->service->assertVerifiedBasis($this->report);
        $this->assertSame(2, $snapshot->fresh()->accepted_by);
    }

    public function test_report_changes_invalidate_acceptance(): void
    {
        $this->fake();
        $snapshot = $this->service->fetch($this->report, 1);
        $this->service->accept($this->report, $snapshot->id, 2, 'Sudah diperiksa lengkap oleh petugas.');
        $this->report->update(['nilai_tagihan' => 98000]);
        $this->expectException(ValidationException::class);
        $this->service->assertVerifiedBasis($this->report);
    }

    public function test_old_snapshot_cannot_be_accepted_after_refresh_and_no_report_is_duplicated(): void
    {
        $this->fake();
        $old = $this->service->fetch($this->report, 1);
        $this->service->fetch($this->report, 1);
        $this->assertSame(1, MitraJasaPenjualan::count());
        $this->expectException(ValidationException::class);
        $this->service->accept($this->report, $old->id, 2, 'Data lama tidak boleh digunakan.');
    }

    public function test_verified_report_cannot_be_refetched(): void
    {
        $this->report->update(['status' => 'diverifikasi']);
        try {
            $this->service->fetch($this->report, 1);
            $this->fail('Must reject locked report');
        } catch (ValidationException $e) {
            Http::assertNothingSent();
        }
    }

    public function test_wrong_airline_response_is_rejected_without_saving_snapshot(): void
    {
        $this->fake(['airlineCode' => 'JT']);
        try {
            $this->service->fetch($this->report, 1);
            $this->fail('Must reject wrong airline');
        } catch (ValidationException $e) {
            $this->assertSame(0, SipandaiReconciliation::count());
        }
    }

    public function test_failed_fetch_keeps_previous_accepted_snapshot(): void
    {
        Http::fake(['127.0.0.1:3001/*' => Http::sequence()->push($this->payload())->push([], 503)]);
        $old = $this->service->fetch($this->report, 1);
        $this->service->accept($this->report, $old->id, 2, 'Sudah diperiksa oleh petugas bandara.');
        try {
            $this->service->fetch($this->report, 1);
            $this->fail('Must reject upstream error');
        } catch (ValidationException $e) {
            $this->assertSame($old->id, $this->service->latest($this->report)->id);
        }
        $this->service->assertVerifiedBasis($this->report);
    }

    public function test_missing_flights_are_not_treated_as_matching_zero(): void
    {
        $this->fake(['flights' => []]);
        $snapshot = $this->service->fetch($this->report, 1);
        $this->assertNull($snapshot->comparison[0]['scanned']);
        $this->assertNull($snapshot->comparison[0]['difference']);
    }

    public function test_unmapped_partner_cannot_fetch_other_airlines(): void
    {
        config(['sipandai.airlines' => []]);
        try {
            $this->service->fetch($this->report, 1);
            $this->fail('Mapping required');
        } catch (ValidationException $e) {
            Http::assertNothingSent();
        }
    }

    public function test_source_dates_outside_report_are_rejected(): void
    {
        $data = $this->payload();
        $data['flights'][0]['std'] = '2026-10-06T15:59:59Z';
        $this->fake($data);
        $this->expectException(ValidationException::class);
        $this->service->fetch($this->report, 1);
    }

    public function test_mapping_can_be_saved_and_cannot_be_reused_for_another_partner(): void
    {
        config(['sipandai.airlines' => []]);
        $this->service->saveMapping($this->report, 'ga', 1);
        $this->assertSame('GA', $this->service->airline($this->report));
        $other = $this->report->replicate();
        $other->mitra_jasa_id = 8;
        $other->save();
        $this->expectException(ValidationException::class);
        $this->service->saveMapping($other, 'GA', 1);
    }

    public function test_controller_rejects_wrong_partner_before_fetching(): void
    {
        $mitra = new MitraJasa(['id' => 999]);
        $mitra->id = 999;
        $this->expectException(NotFoundHttpException::class);
        app(MitraJasaPenjualanController::class)->fetchSipandai($mitra, $this->report, $this->service);
    }

    public function test_controller_rejects_unprivileged_user(): void
    {
        $user = \Mockery::mock(User::class)->makePartial();
        $user->shouldReceive('hasAnyRole')->andReturn(false);
        $user->shouldReceive('hasRole')->andReturn(false);
        auth()->setUser($user);
        $mitra = new MitraJasa;
        $mitra->id = 7;
        $this->expectException(HttpException::class);
        app(MitraJasaPenjualanController::class)->fetchSipandai($mitra, $this->report, $this->service);
    }

    public function test_reconciliation_view_renders_actions_and_escapes_notes(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
        });
        DB::table('users')->insert(['id' => 1, 'name' => 'Petugas Uji']);
        $user = \Mockery::mock(User::class)->makePartial();
        $user->shouldReceive('hasAnyRole')->andReturn(true);
        auth()->setUser($user);
        $this->fake();
        $snapshot = $this->service->fetch($this->report, 1);
        $mitra = new MitraJasa(['nama_mitra' => 'Maskapai Uji']);
        $mitra->id = 7;
        $render = fn () => view('super_admin_jasa.mitra.sipandai-reconciliation', [
            'mitra' => $mitra, 'penjualan' => $this->report, 'errors' => new ViewErrorBag,
        ])->render();
        $html = $render();
        $this->assertStringContainsString('Ambil Data SIPANDAI', $html);
        $this->assertStringContainsString('Simpan Rekonsiliasi', $html);
        $this->assertStringContainsString('GA123', $html);
        $this->service->accept($this->report, $snapshot->id, 1, '<script>alert(1)</script> diperiksa');
        $html = $render();
        $this->assertStringContainsString('&lt;script&gt;', $html);
        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
        $this->assertStringNotContainsString('Simpan Rekonsiliasi</button>', $html);
    }
}
