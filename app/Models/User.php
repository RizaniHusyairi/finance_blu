<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
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
        ];
    }

    /**
     * Relasi polymorphic ke profil utama user.
     * Tepat satu pegawai (MasterPegawai) atau mitra (MasterPihak / MasterMitraVendor).
     */
    public function profilable()
    {
        return $this->morphTo();
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
            ->orderByRaw('COALESCE(master_pegawai.nama_lengkap, master_pihak.nama_pihak, users.email) ' . $direction)
            ->select('users.*');
    }

    /**
     * Ekspresi SQL untuk mengambil nama tampilan user via JOIN.
     * Pakai di select() bersama leftJoin master_pegawai/master_pihak.
     */
    public static function displayNameSqlExpression(string $alias = 'name'): \Illuminate\Database\Query\Expression
    {
        return \Illuminate\Support\Facades\DB::raw('COALESCE(master_pegawai.nama_lengkap, master_pihak.nama_pihak, users.email) AS ' . $alias);
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
     * Accessor: $user->mitra → instance MasterPihak/MasterMitraVendor jika profilnya mitra.
     */
    public function getMitraAttribute()
    {
        $profile = $this->relationLoaded('profilable') ? $this->getRelation('profilable') : $this->profilable;

        return $profile instanceof MasterPihak ? $profile : null;
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

            $allowed = [MasterPegawai::class, MasterPihak::class, MasterMitraVendor::class, MasterPersonelEksternal::class];
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

            $allowed = [MasterPegawai::class, MasterPihak::class, MasterMitraVendor::class, MasterPersonelEksternal::class];
            if (! in_array($user->profilable_type, $allowed, true)) {
                throw new \DomainException('Tipe profilable tidak diizinkan: ' . $user->profilable_type);
            }
        });
    }
}
