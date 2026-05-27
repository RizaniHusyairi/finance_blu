<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

class TagihanJasa extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'tagihan_jasas';
    protected $guarded = ['id'];
    protected $casts = [
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

        return max(0, $this->tanggal_jatuh_tempo->diffInDays(now(), false));
    }

    public function getTarifDendaKeterlambatanAttribute(): float
    {
        return 0.02;
    }

    public function getNominalDendaKeterlambatanAttribute(): float
    {
        return round((float) $this->total_tagihan * $this->tarif_denda_keterlambatan * $this->hari_terlambat, 2);
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
