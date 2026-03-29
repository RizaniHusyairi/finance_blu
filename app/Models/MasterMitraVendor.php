<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MasterMitraVendor extends Model
{
    protected $table = 'master_mitra_vendor';

    protected $fillable = [
        'kategori',
        'tipe_supplier',
        'user_id',
        'npwp',
        'nama_perusahaan',
        'nama_direktur',
        'alamat',
        'no_telepon',
    ];

    public function rekening()
    {
        return $this->morphMany(RekeningBank::class, 'pemilik');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
