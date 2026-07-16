<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'user_name',
        'user_role',
        'event',
        'modul',
        'description',
        'method',
        'route',
        'url',
        'ip',
        'user_agent',
        'properties',
        'status_code',
    ];

    protected $casts = [
        'properties' => 'array',
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Label & warna badge per jenis event untuk tampilan. */
    public static function eventMeta(): array
    {
        return [
            'login' => ['label' => 'Login',        'color' => 'success', 'icon' => 'login'],
            'logout' => ['label' => 'Logout',       'color' => 'secondary', 'icon' => 'logout'],
            'login_gagal' => ['label' => 'Login Gagal',  'color' => 'danger',  'icon' => 'gpp_bad'],
            'create' => ['label' => 'Tambah Data',  'color' => 'primary', 'icon' => 'add_circle'],
            'update' => ['label' => 'Ubah Data',    'color' => 'warning', 'icon' => 'edit'],
            'delete' => ['label' => 'Hapus Data',   'color' => 'danger',  'icon' => 'delete'],
            'aksi' => ['label' => 'Aksi Lain',    'color' => 'info',    'icon' => 'bolt'],
        ];
    }

    public function scopeFilter(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['user_id'] ?? null, fn ($q, $v) => $q->where('user_id', $v))
            ->when($filters['event'] ?? null, fn ($q, $v) => $q->where('event', $v))
            ->when($filters['modul'] ?? null, fn ($q, $v) => $q->where('modul', $v))
            ->when($filters['tanggal_dari'] ?? null, fn ($q, $v) => $q->whereDate('created_at', '>=', $v))
            ->when($filters['tanggal_sampai'] ?? null, fn ($q, $v) => $q->whereDate('created_at', '<=', $v))
            ->when($filters['cari'] ?? null, function ($q, $v) {
                $q->where(function ($qq) use ($v) {
                    $qq->where('description', 'like', "%{$v}%")
                        ->orWhere('user_name', 'like', "%{$v}%")
                        ->orWhere('url', 'like', "%{$v}%")
                        ->orWhere('ip', 'like', "%{$v}%");
                });
            });
    }
}
