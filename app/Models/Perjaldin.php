<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;

class Perjaldin extends Tagihan
{
    protected static function booted(): void
    {
        parent::booted();
        static::addGlobalScope('tipe_tagihan', function (Builder $builder) {
            $builder->where('tipe_tagihan', 'PERJALDIN');
        });
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->tipe_tagihan = 'PERJALDIN';
        });
    }
    public function pejabatats()
    {
        return $this->detailPerjaldin();
    }

    public function pejabats()
    {
        return $this->detailPerjaldin();
    }

    public function spps()
    {
        return $this->hasMany(Spp::class, 'tagihan_id');
    }

    public function getPerjaldinIdAttribute()
    {
        return $this->id;
    }

    public function getUraianAttribute()
    {
        return $this->deskripsi;
    }

    public function getNoBastAttribute()
    {
        return null;
    }
}
