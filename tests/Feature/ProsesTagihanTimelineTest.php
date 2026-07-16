<?php

namespace Tests\Feature;

use App\Models\DokumenSpp;
use App\Models\PotonganTagihan;
use App\Models\Spp;
use App\Models\Tagihan;
use App\Support\TimelineAksi;
use Tests\TestCase;

/**
 * Kamus label timeline Proses Tagihan (TimelineAksi): entri eksplisit,
 * fallback berpola untuk aksi dinamis, dan chip konteks dokumen anak.
 * Perilaku union query diverifikasi end-to-end di ProsesTagihanSampaiBkuTest.
 */
class ProsesTagihanTimelineTest extends TestCase
{
    public function test_label_indonesia_untuk_aksi_terdaftar(): void
    {
        $this->assertSame('KPA menyetujui tagihan', TimelineAksi::meta('KPA_SETUJU')['label']);
        $this->assertSame('SPP diajukan untuk verifikasi', TimelineAksi::meta('SUBMIT_SPP')['label']);
        $this->assertSame('SP2D terbit — dana dicairkan', TimelineAksi::meta('EXECUTE_PAYMENT')['label']);
        $this->assertSame('NTPN & bukti setor pajak diinput', TimelineAksi::meta('INPUT_NTPN')['label']);
        $this->assertSame('COA dibebankan', TimelineAksi::meta('SET_COA')['label']);
        $this->assertSame('Vendor menandatangani dokumen (TTE)', TimelineAksi::meta('TTD_VENDOR')['label']);
    }

    public function test_fallback_berpola_untuk_aksi_dinamis(): void
    {
        // Aksi dinamis lama (APPROVE_<ROLE>, REVISI_<ROLE>, UPLOAD_<JENIS>)
        // tetap terbaca manusiawi tanpa entri eksplisit di katalog.
        $this->assertSame('Disetujui BENPEN', TimelineAksi::meta('APPROVE_BENPEN')['label']);
        $this->assertSame('success', TimelineAksi::meta('APPROVE_BENPEN')['color']);

        $this->assertSame('Revisi diminta KASUBBAG', TimelineAksi::meta('REVISI_KASUBBAG')['label']);
        $this->assertSame('warning', TimelineAksi::meta('REVISI_KASUBBAG')['color']);

        $this->assertSame('Unggah SPD', TimelineAksi::meta('UPLOAD_SPD')['label']);

        // Default humanize untuk aksi yang benar-benar tak dikenal.
        $this->assertSame('Aksi tidak dikenal', TimelineAksi::meta('AKSI_TIDAK_DIKENAL')['label']);
        $this->assertSame('secondary', TimelineAksi::meta('AKSI_TIDAK_DIKENAL')['color']);
    }

    public function test_chip_konteks_dokumen_anak(): void
    {
        $this->assertSame('SPP', TimelineAksi::dokumenContext(DokumenSpp::class));
        // Varian legacy: log lama SPP tersimpan dengan FQCN subclass App\Models\Spp.
        $this->assertSame('SPP', TimelineAksi::dokumenContext(Spp::class));
        $this->assertSame('Pajak', TimelineAksi::dokumenContext(PotonganTagihan::class));
        // Log bertipe Tagihan tidak perlu chip.
        $this->assertNull(TimelineAksi::dokumenContext(Tagihan::class));
        $this->assertNull(TimelineAksi::dokumenContext(null));
    }
}
