<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pejabat extends Model
{
    protected $primaryKey = 'pejabat_id';
    
    protected $fillable = [
        'perjaldin_id',
        'employee_id',
        'nama_pejabat',
        'nip',
        'no_spt',
        'no_sppd',
        'tujuan',
        'tanggal_berangkat',
        'lama_perjalanan_dinas',
        'tiket',
        'transport',
        'uang_harian',
        'penginapan',
        'uang_representasi',
        'rekening',
        'status',
        'alasan_penolakan',
    ];

    public function perjaldin()
    {
        return $this->belongsTo(Perjaldin::class, 'perjaldin_id', 'perjaldin_id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }
}
