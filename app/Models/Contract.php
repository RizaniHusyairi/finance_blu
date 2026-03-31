<?php

namespace App\Models;

class Contract extends KontrakPengadaan
{
    public function supplier()
    {
        return $this->vendor();
    }

    public function budget()
    {
        return $this->dipa();
    }

    public function terms()
    {
        return $this->termin();
    }

    public function spps()
    {
        return $this->hasMany(DokumenSpp::class, 'tagihan_id');
    }

    public function getContractNumberAttribute()
    {
        return $this->nomor_spk;
    }

    public function getDescriptionAttribute()
    {
        return $this->nama_pekerjaan;
    }

    public function getTotalAmountAttribute()
    {
        return $this->nilai_total_kontrak;
    }

    public function getDateAttribute()
    {
        return $this->tanggal_spk;
    }

    public function getStatusAttribute()
    {
        return $this->status_kontrak;
    }
}
