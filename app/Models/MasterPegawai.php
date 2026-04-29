<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MasterPegawai extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'master_pegawai';
    protected $guarded = ['id'];

    protected $casts = [
        'status_aktif' => 'boolean',
    ];

    public function user()
    {
        return $this->morphOne(User::class, 'profilable');
    }

    public function detailPerjaldin()
    {
        return $this->hasMany(DetailPerjaldin::class, 'pegawai_id');
    }
}
