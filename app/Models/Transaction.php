<?php

namespace App\Models;

class Transaction extends Tagihan
{
    public function budget()
    {
        return $this->belongsTo(MasterDipa::class, 'master_dipa_id');
    }

    public function honorariumItems()
    {
        return $this->detailHonorarium();
    }

    public function spps()
    {
        return $this->hasMany(Spp::class, 'tagihan_id');
    }

    public function getTypeAttribute()
    {
        return $this->tipe_tagihan;
    }

    public function getDescriptionAttribute()
    {
        return $this->deskripsi;
    }

    public function getGrossAmountAttribute()
    {
        return $this->total_bruto;
    }

    public function getTransactionNumberAttribute()
    {
        return $this->nomor_tagihan;
    }

    public function getBastNumberAttribute()
    {
        return null;
    }

    public function getBastDateAttribute()
    {
        return null;
    }
}
