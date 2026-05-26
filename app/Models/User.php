<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Traits\HasRoles; // Added this line

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles; // Added HasRoles here

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'email',
        'email_verified_at',
        'password',
        'is_active',
        'active_from',
        'active_until',
        'disabled_at',
        'profilable_type',
        'profilable_id',
    ];

    /**
     * Field virtual yang dihasilkan dari accessor (bukan kolom DB).
     */
    protected $appends = ['name'];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'active_from' => 'date',
            'active_until' => 'date',
            'disabled_at' => 'datetime',
        ];
    }

    public function scopeActive($query)
    {
        if (! self::hasActiveAccountColumns()) {
            return $query;
        }

        $today = now()->toDateString();

        return $query
            ->where('is_active', true)
            ->where(function ($q) use ($today) {
                $q->whereNull('active_from')
                    ->orWhereDate('active_from', '<=', $today);
            })
            ->where(function ($q) use ($today) {
                $q->whereNull('active_until')
                    ->orWhereDate('active_until', '>=', $today);
            });
    }

    public function isAccountActive(): bool
    {
        if (! array_key_exists('is_active', $this->attributes)) {
            return true;
        }

        if (! $this->is_active) {
            return false;
        }

        if ($this->active_from && $this->active_from->isFuture()) {
            return false;
        }

        return ! $this->hasExpiredActivePeriod();
    }

    public function hasExpiredActivePeriod(): bool
    {
        return $this->active_until !== null
            && $this->active_until->lt(now()->startOfDay());
    }

    public function disableIfExpired(): bool
    {
        if (! self::hasActiveAccountColumns()) {
            return false;
        }

        if (! $this->is_active || ! $this->hasExpiredActivePeriod()) {
            return false;
        }

        $this->forceFill([
            'is_active' => false,
            'disabled_at' => now(),
        ])->save();

        return true;
    }

    public function accountInactiveMessage(): string
    {
        if ($this->hasExpiredActivePeriod()) {
            return 'Masa aktif akun ini sudah berakhir pada ' . $this->active_until->translatedFormat('d F Y') . '.';
        }

        if (! $this->is_active) {
            return 'Akun ini sedang nonaktif. Hubungi Super Admin untuk mengaktifkan kembali.';
        }

        if ($this->active_from && $this->active_from->isFuture()) {
            return 'Akun ini baru aktif mulai ' . $this->active_from->translatedFormat('d F Y') . '.';
        }

        return 'Akun ini tidak aktif.';
    }

    public static function hasActiveAccountColumns(): bool
    {
        return Schema::hasTable('users')
            && Schema::hasColumn('users', 'is_active')
            && Schema::hasColumn('users', 'active_from')
            && Schema::hasColumn('users', 'active_until')
            && Schema::hasColumn('users', 'disabled_at');
    }

    /**
     * Relasi polymorphic ke profil utama user.
     * Tepat satu pegawai, mitra/vendor lama, atau Mitra Jasa.
     */
    public function profilable()
    {
        return $this->morphTo();
    }

    public function layananJasaDikelola()
    {
        return $this->belongsToMany(LayananJasa::class, 'admin_jasa_layanan', 'user_id', 'layanan_jasa_id')
            ->withPivot(['status_aktif', 'tanggal_mulai', 'tanggal_selesai', 'keterangan', 'created_by'])
            ->withTimestamps();
    }

    public function layananJasaDikelolaAktif()
    {
        return $this->layananJasaDikelola()
            ->wherePivot('status_aktif', true)
            ->where(function ($query) {
                $query->whereNull('admin_jasa_layanan.tanggal_mulai')
                    ->orWhereDate('admin_jasa_layanan.tanggal_mulai', '<=', now()->toDateString());
            })
            ->where(function ($query) {
                $query->whereNull('admin_jasa_layanan.tanggal_selesai')
                    ->orWhereDate('admin_jasa_layanan.tanggal_selesai', '>=', now()->toDateString());
            });
    }

    /**
     * Accessor: $user->name
     * Diambil dari profilable (nama_lengkap pegawai / nama_pihak mitra),
     * fallback ke email kalau profilable belum ter-load atau kosong.
     */
    public function getNameAttribute(): ?string
    {
        $profile = $this->relationLoaded('profilable')
            ? $this->getRelation('profilable')
            : $this->profilable;

        if ($profile instanceof MasterPegawai) {
            return $profile->nama_lengkap;
        }

        if ($profile instanceof MasterPihak) {
            return $profile->nama_pihak;
        }

        if ($profile instanceof MitraJasa) {
            return $profile->nama_mitra;
        }

        return $this->attributes['email'] ?? null;
    }

    /**
     * Scope untuk mengurutkan user berdasarkan nama dari tabel profilable.
     * Gunakan sebagai pengganti orderBy('name') yang sebelumnya pakai kolom users.name.
     */
    public function scopeOrderByDisplayName($query, string $direction = 'asc')
    {
        $direction = strtolower($direction) === 'desc' ? 'desc' : 'asc';

        return $query
            ->leftJoin('master_pegawai', function ($join) {
                $join->on('master_pegawai.id', '=', 'users.profilable_id')
                    ->where('users.profilable_type', '=', MasterPegawai::class);
            })
            ->leftJoin('master_pihak', function ($join) {
                $join->on('master_pihak.id', '=', 'users.profilable_id')
                    ->whereIn('users.profilable_type', [MasterPihak::class, MasterMitraVendor::class]);
            })
            ->leftJoin('mitra_jasa', function ($join) {
                $join->on('mitra_jasa.id', '=', 'users.profilable_id')
                    ->where('users.profilable_type', '=', MitraJasa::class);
            })
            ->orderByRaw('COALESCE(master_pegawai.nama_lengkap, master_pihak.nama_pihak, mitra_jasa.nama_mitra, users.email) ' . $direction)
            ->select('users.*');
    }

    /**
     * Ekspresi SQL untuk mengambil nama tampilan user via JOIN.
     * Pakai di select() bersama leftJoin master_pegawai/master_pihak.
     */
    public static function displayNameSqlExpression(string $alias = 'name'): \Illuminate\Database\Query\Expression
    {
        return \Illuminate\Support\Facades\DB::raw('COALESCE(master_pegawai.nama_lengkap, master_pihak.nama_pihak, mitra_jasa.nama_mitra, users.email) AS ' . $alias);
    }

    /**
     * Accessor backward-compat: $user->pegawai
     * Return MasterPegawai jika profilable-nya pegawai, selain itu null.
     */
    public function getPegawaiAttribute()
    {
        $profile = $this->relationLoaded('profilable') ? $this->getRelation('profilable') : $this->profilable;

        return $profile instanceof MasterPegawai ? $profile : null;
    }

    /**
     * Accessor: $user->mitra -> instance mitra/vendor lama atau MitraJasa jika profilnya mitra.
     */
    public function getMitraAttribute()
    {
        $profile = $this->relationLoaded('profilable') ? $this->getRelation('profilable') : $this->profilable;

        return $profile instanceof MasterPihak || $profile instanceof MitraJasa ? $profile : null;
    }

    protected static function booted(): void
    {
        // Guard hanya saat CREATE — setelah user terbentuk, integritas dijaga oleh
        // UNIQUE(profilable_type, profilable_id) di level DB.
        // Saat update (mis. remember_token / password / email_verified_at) guard
        // tidak perlu jalan karena kolom profilable_* tidak diubah.
        static::creating(function (self $user) {
            if (app()->runningInConsole() && env('SKIP_USER_PROFILABLE_GUARD', false)) {
                return;
            }

            $hasType = ! empty($user->profilable_type);
            $hasId = ! empty($user->profilable_id);

            if ($hasType !== $hasId) {
                throw new \DomainException('Profilable user tidak konsisten: type dan id harus terisi bersama-sama.');
            }

            if (! $hasType) {
                throw new \DomainException('User wajib terhubung ke 1 pegawai atau 1 mitra (profilable_type/profilable_id kosong).');
            }

            $allowed = [MasterPegawai::class, MasterPihak::class, MasterMitraVendor::class, MasterPersonelEksternal::class, MitraJasa::class];
            if (! in_array($user->profilable_type, $allowed, true)) {
                throw new \DomainException('Tipe profilable tidak diizinkan: ' . $user->profilable_type);
            }
        });

        // Saat update, tetap cegah user mengosongkan profilable yang sudah ada.
        static::updating(function (self $user) {
            if (! $user->isDirty(['profilable_type', 'profilable_id'])) {
                return; // Bukan update profilable — lewati.
            }

            $hasType = ! empty($user->profilable_type);
            $hasId = ! empty($user->profilable_id);

            if ($hasType !== $hasId || ! $hasType) {
                throw new \DomainException('User tidak boleh dilepas dari pegawai/mitra. profilable_type & profilable_id wajib terisi keduanya.');
            }

            $allowed = [MasterPegawai::class, MasterPihak::class, MasterMitraVendor::class, MasterPersonelEksternal::class, MitraJasa::class];
            if (! in_array($user->profilable_type, $allowed, true)) {
                throw new \DomainException('Tipe profilable tidak diizinkan: ' . $user->profilable_type);
            }
        });
    }
}
