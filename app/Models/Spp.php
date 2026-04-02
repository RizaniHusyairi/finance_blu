<?php

namespace App\Models;

class Spp extends DokumenSpp
{
    public function getSppIdAttribute()
    {
        return $this->id;
    }

    public function setStatusSppAttribute($value)
    {
        $this->attributes['status'] = $value;
    }

    public function sppable()
    {
        return $this->tagihan();
    }

    public function getNomorNpiAttribute()
    {
        return optional(optional($this->spm)->npi)->nomor_npi;
    }

    public function getTanggalNpiAttribute()
    {
        return optional(optional($this->spm)->npi)->tanggal_npi;
    }

    public function getNomorSp2dAttribute()
    {
        return optional(optional(optional($this->spm)->npi)->sp2d)->nomor_sp2d;
    }

    public function getTanggalSp2dAttribute()
    {
        return optional(optional(optional($this->spm)->npi)->sp2d)->tanggal_sp2d;
    }

    public function getCatatanBkuAttribute()
    {
        return optional(optional(optional($this->spm)->npi)->sp2d)->catatan_bku;
    }

    public function getNomorSpmAttribute()
    {
        return optional($this->spm)->nomor_spm;
    }

    public function getTanggalSpmAttribute()
    {
        return optional($this->spm)->tanggal_spm;
    }

    public function getPenandatanganSpmNamaAttribute()
    {
        return optional(optional($this->spm)->ppspm)->name;
    }

    public function getPenandatanganSpmNipAttribute()
    {
        return '-';
    }

    public function getCatatanRevisiAttribute()
    {
        if ($this->spm?->npi) {
            if ($this->spm->npi->sp2d) {
                return $this->spm->npi->sp2d->catatan_bku;
            }

            return $this->spm->npi->catatan_revisi;
        }

        return optional($this->spm)->catatan_revisi;
    }

    public function getStatusSppAttribute()
    {
        if ($this->spm?->npi?->sp2d) {
            return $this->spm->npi->sp2d->status_spp;
        }

        if ($this->spm?->npi) {
            return $this->spm->npi->status_spp;
        }

        if ($this->spm) {
            return $this->spm->status_spp;
        }

        return $this->status;
    }
}
