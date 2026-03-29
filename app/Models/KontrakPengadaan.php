<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KontrakPengadaan extends Model
{
    protected $table = 'kontrak_pengadaan';
    protected $guarded = ['id'];

    public function vendor()
    {
        return $this->belongsTo(MasterMitraVendor::class, 'vendor_id');
    }

    public function dipa()
    {
        return $this->belongsTo(MasterDipa::class, 'master_dipa_id');
    }

    public function addendums()
    {
        return $this->hasMany(KontrakAddendum::class, 'kontrak_pengadaan_id');
    }

    public function termin()
    {
        return $this->hasMany(KontrakTermin::class, 'kontrak_pengadaan_id');
    }
}
