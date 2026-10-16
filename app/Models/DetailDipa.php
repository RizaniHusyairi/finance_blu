<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;

class DetailDipa extends Model
{
    protected $table = 'dipa_revision_items';

    /** Status tagihan yang tidak lagi mengikat pagu. */
    public const STATUS_TAGIHAN_BATAL = ['BATAL', 'DIBATALKAN'];

    protected $fillable = [
        'dipa_revision_id',
        'coa_id',
        'nilai_pagu',
        'volume',
        'satuan',
        'harga_satuan',
        'status_aktif',
        'blokir',
    ];

    protected $casts = [
        'nilai_pagu' => 'decimal:2',
        'volume' => 'decimal:2',
        'harga_satuan' => 'decimal:2',
        'status_aktif' => 'boolean',
        'blokir' => 'boolean',
    ];

    /** @var array{realisasi: float, komitmen: float}|null */
    protected ?array $paguCache = null;

    public function dipaRevision()
    {
        return $this->belongsTo(RiwayatRevisiDipa::class, 'dipa_revision_id');
    }

    public function dipa()
    {
        return $this->hasOneThrough(MasterDipa::class, RiwayatRevisiDipa::class, 'id', 'id', 'dipa_revision_id', 'master_dipa_id');
    }

    public function coa()
    {
        return $this->belongsTo(MasterCoa::class, 'coa_id');
    }

    public function realisasiAnggarans()
    {
        return $this->hasMany(RealisasiAnggaran::class, 'dipa_revision_item_id')->where('status', 'TERCATAT');
    }

    /**
     * Item yang boleh dibebani tagihan: aktif, tidak diblokir, COA ada,
     * berada pada revisi aktif dari DIPA yang aktif.
     */
    public function scopeDapatDipilih(Builder $query): Builder
    {
        return $query
            ->where('status_aktif', true)
            ->where('blokir', false)
            ->whereHas('coa')
            ->whereHas('dipaRevision', function ($revisionQuery) {
                $revisionQuery
                    ->where('is_active', true)
                    ->whereHas('masterDipa', fn ($dipaQuery) => $dipaQuery->where('status_aktif', true));
            });
    }

    /**
     * Realisasi tercatat pada baris item ini saja (tanpa revisi lain).
     */
    public function getRealisasiItemAttribute()
    {
        return $this->relationLoaded('realisasiAnggarans')
            ? $this->realisasiAnggarans->sum('nominal_cair')
            : $this->realisasiAnggarans()->sum('nominal_cair');
    }

    /**
     * Realisasi COA ini pada DIPA yang sama, lintas seluruh revisi — revisi
     * DIPA membuat baris item baru, jadi serapan revisi lama harus ikut.
     */
    public function getTotalRealisasiAttribute()
    {
        return $this->paguTerhitung()['realisasi'];
    }

    /**
     * Nominal tagihan/komponen yang sudah membebani COA ini tetapi belum
     * terealisasi (belum SP2D), lintas revisi DIPA yang sama.
     */
    public function getTotalKomitmenAttribute()
    {
        return $this->paguTerhitung()['komitmen'];
    }

    public function getSisaPaguAttribute()
    {
        $pagu = $this->paguTerhitung();

        return (float) $this->nilai_pagu - $pagu['realisasi'] - $pagu['komitmen'];
    }

    /**
     * Sisa pagu untuk memvalidasi pembebanan, tanpa menghitung komitmen milik
     * tagihan/komponen yang sedang divalidasi itu sendiri.
     */
    public function sisaPaguTersedia(?int $kecualiTagihanId = null, ?int $kecualiKomponenId = null): float
    {
        if ($kecualiTagihanId === null && $kecualiKomponenId === null) {
            return (float) $this->sisa_pagu;
        }

        $pagu = static::agregatPagu([$this], $kecualiTagihanId, $kecualiKomponenId)[$this->id];

        return (float) $this->nilai_pagu - $pagu['realisasi'] - $pagu['komitmen'];
    }

    /**
     * Hitung realisasi & komitmen banyak item sekaligus (beberapa query
     * agregat) agar daftar pilihan COA tidak memicu query per item.
     *
     * @param  iterable<DetailDipa>  $items
     */
    public static function muatPagu(iterable $items): void
    {
        $items = collect($items)->filter(fn ($item) => $item instanceof self && $item->exists);
        if ($items->isEmpty()) {
            return;
        }

        $hasil = static::agregatPagu($items->all());
        foreach ($items as $item) {
            $item->paguCache = $hasil[$item->id];
        }
    }

    /** @return array{realisasi: float, komitmen: float} */
    protected function paguTerhitung(): array
    {
        if ($this->paguCache === null) {
            if (! $this->exists) {
                return ['realisasi' => 0.0, 'komitmen' => 0.0];
            }
            static::muatPagu([$this]);
        }

        return $this->paguCache;
    }

    /**
     * @param  array<int, DetailDipa>  $items
     * @return array<int, array{realisasi: float, komitmen: float}>
     */
    protected static function agregatPagu(array $items, ?int $kecualiTagihanId = null, ?int $kecualiKomponenId = null): array
    {
        $items = collect($items);
        $masterDipaPerRevisi = DB::table('dipa_revisions')
            ->whereIn('id', $items->pluck('dipa_revision_id')->unique())
            ->pluck('master_dipa_id', 'id');

        // Seluruh baris item (semua revisi) untuk pasangan DIPA+COA yang diminta.
        $saudara = DB::table('dipa_revision_items as i')
            ->join('dipa_revisions as r', 'r.id', '=', 'i.dipa_revision_id')
            ->whereIn('r.master_dipa_id', $masterDipaPerRevisi->unique()->values())
            ->whereIn('i.coa_id', $items->pluck('coa_id')->unique())
            ->get(['i.id', 'i.coa_id', 'r.master_dipa_id']);

        $kunciPerItem = $saudara->mapWithKeys(fn ($row) => [$row->id => $row->master_dipa_id . ':' . $row->coa_id]);
        $semuaId = $kunciPerItem->keys()->all();

        $realisasi = RealisasiAnggaran::query()
            ->whereIn('dipa_revision_item_id', $semuaId)
            ->where('status', 'TERCATAT')
            ->groupBy('dipa_revision_item_id')
            ->selectRaw('dipa_revision_item_id, SUM(nominal_cair) as total')
            ->pluck('total', 'dipa_revision_item_id');

        $komitmenTagihan = static::tagihanBerjalan($semuaId)
            // Perjaldin berkomponen dibebankan per komponen (lihat di bawah).
            ->where(function ($query) {
                $query->where('tipe_tagihan', '!=', 'PERJALDIN')
                    ->orWhereDoesntHave('komponenPerjaldin', fn ($komponen) => $komponen->where('total_nominal', '>', 0));
            })
            ->when($kecualiTagihanId, fn ($query) => $query->whereKeyNot($kecualiTagihanId))
            ->groupBy('dipa_revision_item_id')
            ->selectRaw('dipa_revision_item_id, SUM(total_netto) as total')
            ->pluck('total', 'dipa_revision_item_id');

        $komitmenKomponen = static::komponenBerjalan($semuaId)
            ->where('total_nominal', '>', 0)
            ->when($kecualiTagihanId, fn ($query) => $query->where('tagihan_id', '!=', $kecualiTagihanId))
            ->when($kecualiKomponenId, fn ($query) => $query->whereKeyNot($kecualiKomponenId))
            ->groupBy('dipa_revision_item_id')
            ->selectRaw('dipa_revision_item_id, SUM(total_nominal) as total')
            ->pluck('total', 'dipa_revision_item_id');

        $perKunci = [];
        foreach ($kunciPerItem as $itemId => $kunci) {
            $perKunci[$kunci] ??= ['realisasi' => 0.0, 'komitmen' => 0.0];
            $perKunci[$kunci]['realisasi'] += (float) ($realisasi[$itemId] ?? 0);
            $perKunci[$kunci]['komitmen'] += (float) ($komitmenTagihan[$itemId] ?? 0) + (float) ($komitmenKomponen[$itemId] ?? 0);
        }

        $hasil = [];
        foreach ($items as $item) {
            $kunci = ($masterDipaPerRevisi[$item->dipa_revision_id] ?? '') . ':' . $item->coa_id;
            $hasil[$item->id] = $perKunci[$kunci] ?? ['realisasi' => 0.0, 'komitmen' => 0.0];
        }

        return $hasil;
    }

    /**
     * Tagihan yang membebani item-item ini dan belum terealisasi (belum SP2D)
     * serta tidak dibatalkan.
     *
     * @param  array<int, int>  $itemIds
     */
    public static function tagihanBerjalan(array $itemIds): Builder
    {
        return Tagihan::query()
            ->whereIn('dipa_revision_item_id', $itemIds)
            ->whereNotIn('status', self::STATUS_TAGIHAN_BATAL)
            ->whereNotExists(fn (QueryBuilder $query) => static::realisasiTercatatUntuk($query, Tagihan::class, 'tagihan.id', true));
    }

    /**
     * Komponen perjaldin yang membebani item-item ini dan belum terealisasi.
     *
     * @param  array<int, int>  $itemIds
     */
    public static function komponenBerjalan(array $itemIds): Builder
    {
        return TagihanPerjaldinKomponen::query()
            ->whereIn('dipa_revision_item_id', $itemIds)
            ->whereHas('tagihan', fn ($tagihan) => $tagihan->whereNotIn('status', self::STATUS_TAGIHAN_BATAL))
            ->whereNotExists(fn (QueryBuilder $query) => static::realisasiTercatatUntuk(
                $query,
                TagihanPerjaldinKomponen::class,
                'tagihan_perjaldin_komponen.id'
            ));
    }

    private static function realisasiTercatatUntuk(QueryBuilder $query, string $sourceType, string $kolomId, bool $cekTagihanId = false): void
    {
        $query->selectRaw('1')
            ->from('realisasi_anggaran as ra')
            ->whereNull('ra.deleted_at')
            ->where('ra.status', 'TERCATAT')
            ->where(function ($match) use ($sourceType, $kolomId, $cekTagihanId) {
                $match->where(fn ($source) => $source
                    ->where('ra.sourceable_type', $sourceType)
                    ->whereColumn('ra.sourceable_id', $kolomId));

                if ($cekTagihanId) {
                    $match->orWhereColumn('ra.tagihan_id', $kolomId);
                }
            });
    }
}
