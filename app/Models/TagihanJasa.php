<?php

namespace App\Models;

use App\Models\Concerns\Blameable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

class TagihanJasa extends Model
{
    use Blameable, HasFactory, SoftDeletes;

    protected $table = 'tagihan_jasas';
    protected $guarded = ['id'];
    protected $casts = [
        'btn_va_data' => 'array',
        'tanggal_tagihan' => 'date',
        'tanggal_publish' => 'date',
        'tanggal_jatuh_tempo' => 'date',
        'tanggal_akhir_toleransi' => 'date',
        'tanggal_lunas' => 'date',
        'uploaded_surat_pengantar_at' => 'datetime',
        'va_expired_at' => 'datetime',
        'paid_at' => 'datetime',
        'last_payment_sync_at' => 'datetime',
        'total_tagihan' => 'decimal:2',
        'jumlah_dibayar' => 'decimal:2',
        'sisa_tagihan' => 'decimal:2',
        'masa_denda_hari' => 'integer',
    ];

    public function mitra()
    {
        return $this->belongsTo(MitraJasa::class, 'mitra_jasa_id');
    }

    public function mitraLegacy()
    {
        return $this->belongsTo(MasterPihak::class, 'mitra_id');
    }

    public function kontrakMitraJasa()
    {
        return $this->belongsTo(KontrakMitraJasa::class, 'kontrak_mitra_jasa_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function suratPengantarSigner()
    {
        return $this->belongsTo(User::class, 'uploaded_surat_pengantar_by');
    }

    public function details()
    {
        return $this->hasMany(TagihanJasaDetail::class, 'tagihan_jasa_id');
    }

    public function workflowInstances()
    {
        return $this->morphMany(WorkflowInstance::class, 'workflowable');
    }

    public function workflowInstance()
    {
        return $this->morphOne(WorkflowInstance::class, 'workflowable')->latestOfMany();
    }

    public function logs()
    {
        return $this->morphMany(LogStatusDokumen::class, 'dokumen');
    }

    public function arsipDokumen()
    {
        return $this->morphMany(ArsipDokumen::class, 'documentable');
    }

    public function transaksiPenerimaan()
    {
        return $this->hasOne(TransaksiPenerimaan::class, 'nomor_invoice', 'nomor_tagihan');
    }

    public function paymentProofs()
    {
        return $this->hasMany(TagihanJasaPaymentProof::class, 'tagihan_jasa_id')->latest();
    }

    public function latestPaymentProof()
    {
        return $this->hasOne(TagihanJasaPaymentProof::class, 'tagihan_jasa_id')->latestOfMany();
    }

    /* ── Scope Helpers ── */

    public function scopeFungsi($query)
    {
        return $query->where('tipe_pnbp', 'FUNGSI');
    }

    public function scopeNonFungsi($query)
    {
        return $query->where('tipe_pnbp', 'NON_FUNGSI');
    }

    /* ── Accessor ── */

    public function getLabelTipePnbpAttribute(): string
    {
        return match ($this->tipe_pnbp) {
            'KONSESI' => 'Konsesi',
            default => 'Tagihan Jasa',
        };
    }

    public function getUmurPiutangHariAttribute(): int
    {
        if ($this->status_pembayaran === 'lunas' || $this->status === 'LUNAS') {
            return 0;
        }

        return $this->tanggal_tagihan ? max(0, $this->tanggal_tagihan->diffInDays(now(), false)) : 0;
    }

    public function getHariTerlambatAttribute(): int
    {
        if ($this->status_pembayaran === 'lunas' || $this->status === 'LUNAS' || ! $this->tanggal_jatuh_tempo) {
            return 0;
        }

        $today = now()->startOfDay();
        $due = $this->tanggal_jatuh_tempo->copy()->startOfDay();

        return max(0, (int) $due->diffInDays($today, false));
    }

    /**
     * Panjang satu periode denda (hari). Denda PJP2U dihitung per 30 hari.
     * Disnapshot dari layanan saat publish; default 30 bila belum dikonfigurasi.
     */
    public function getPeriodeDendaHariAttribute(): int
    {
        return (int) $this->masa_denda_hari ?: 30;
    }

    /**
     * Jumlah periode denda yang berjalan = pembulatan ke atas dari hari keterlambatan
     * dibagi panjang periode. Denda terus bertambah tiap periode (tidak dibekukan).
     */
    public function getJumlahPeriodeDendaAttribute(): int
    {
        if ($this->hari_terlambat <= 0) {
            return 0;
        }

        return (int) ceil($this->hari_terlambat / $this->periode_denda_hari);
    }

    /** Tarif denda per periode (2% per 30 hari). */
    public function getTarifDendaKeterlambatanAttribute(): float
    {
        return 0.02;
    }

    public function getNominalDendaKeterlambatanAttribute(): float
    {
        return round((float) $this->total_tagihan * $this->tarif_denda_keterlambatan * $this->jumlah_periode_denda, 2);
    }

    /**
     * Kualitas piutang berdasarkan umur tunggakan sejak jatuh tempo, mengikuti
     * tangga baku piutang pemerintah (lihat PiutangAgingService):
     *   Lancar 0 · Kurang Lancar 1–90 · Diragukan 91–180 · Macet >180 hari.
     */
    public function getKualitasPiutangAttribute(): string
    {
        if ($this->status_pembayaran === 'lunas' || $this->status === 'LUNAS') {
            return 'LANCAR';
        }

        return match (true) {
            $this->hari_terlambat <= 0 => 'LANCAR',
            $this->hari_terlambat <= 90 => 'KURANG_LANCAR',
            $this->hari_terlambat <= 180 => 'DIRAGUKAN',
            default => 'MACET',
        };
    }

    /** Piutang macet (umur tunggakan > 180 hari). Denda tetap berjalan. */
    public function getIsMacetAttribute(): bool
    {
        return $this->kualitas_piutang === 'MACET';
    }

    public function getTotalDenganDendaAttribute(): float
    {
        return round((float) $this->total_tagihan + $this->nominal_denda_keterlambatan, 2);
    }

    public function getSisaTagihanBerjalanAttribute(): float
    {
        if ($this->status_pembayaran === 'lunas' || $this->status === 'LUNAS') {
            return 0;
        }

        return max(0, round($this->total_dengan_denda - (float) $this->jumlah_dibayar, 2));
    }

    public function getKodeVerifikasiDigitalAttribute(): string
    {
        $date = $this->tanggal_surat_pengantar
            ?: $this->tanggal_tagihan
            ?: $this->created_at
            ?: Carbon::now();

        return 'VERIF-' . Carbon::parse($date)->format('Ymd') . '-' . str_pad((string) $this->id, 5, '0', STR_PAD_LEFT);
    }

    public function digitalSealPayload(): array
    {
        $this->loadMissing(['mitra', 'mitraLegacy', 'details']);
        $mitra = $this->mitra ?? $this->mitraLegacy;

        return [
            'id' => (int) $this->id,
            'nomor_surat_pengantar' => (string) ($this->nomor_surat_pengantar ?: ''),
            'tanggal_surat_pengantar' => $this->tanggal_surat_pengantar || $this->tanggal_tagihan
                ? Carbon::parse($this->tanggal_surat_pengantar ?: $this->tanggal_tagihan)->format('Y-m-d')
                : null,
            'nomor_tagihan' => (string) ($this->nomor_tagihan ?: ''),
            'tanggal_tagihan' => $this->tanggal_tagihan ? Carbon::parse($this->tanggal_tagihan)->format('Y-m-d') : null,
            'mitra_jasa_id' => (int) ($this->mitra_jasa_id ?: 0),
            'nama_mitra' => (string) ($mitra->nama_mitra ?? $mitra->nama_pihak ?? ''),
            'npwp' => (string) ($mitra->npwp ?? ''),
            'total_tagihan' => number_format((float) $this->total_tagihan, 2, '.', ''),
            'details' => $this->details
                ->sortBy('id')
                ->map(fn ($detail) => [
                    'layanan_jasa_id' => (int) $detail->layanan_jasa_id,
                    'kode_akun' => (string) ($detail->kode_akun ?: ''),
                    'qty' => number_format((float) $detail->qty, 4, '.', ''),
                    'harga_satuan' => number_format((float) $detail->harga_satuan, 2, '.', ''),
                    'subtotal' => number_format((float) $detail->subtotal, 2, '.', ''),
                    'keterangan' => (string) ($detail->keterangan ?: ''),
                ])
                ->values()
                ->all(),
        ];
    }

    public function digitalSealHash(): string
    {
        $payload = json_encode($this->digitalSealPayload(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return hash_hmac('sha256', $payload ?: '', (string) config('app.key'));
    }

    public function getStatusJatuhTempoAttribute(): string
    {
        if ($this->status_pembayaran === 'lunas' || $this->status === 'LUNAS') {
            return 'LUNAS';
        }

        if (! $this->tanggal_jatuh_tempo) {
            return 'BELUM_DISET';
        }

        if ($this->is_macet) {
            return 'MACET';
        }

        $today = now()->startOfDay();
        $due = $this->tanggal_jatuh_tempo->copy()->startOfDay();
        $days = $today->diffInDays($due, false);

        return match (true) {
            $days < 0 => 'LEWAT_JATUH_TEMPO',
            $days === 0 => 'JATUH_TEMPO_HARI_INI',
            $days <= 7 => 'MENDEKATI_JATUH_TEMPO',
            default => 'NORMAL',
        };
    }
}
