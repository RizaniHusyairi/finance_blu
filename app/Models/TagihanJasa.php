<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

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
