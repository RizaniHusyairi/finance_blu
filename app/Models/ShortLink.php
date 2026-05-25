<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Short link generic.
 *
 * Pakai `ShortLink::forTarget('tagihan_jasa', $id)` untuk mendapatkan slug
 * (akan generate sekali jika belum ada, lalu dipakai ulang setiap kali
 * link dibutuhkan). URL publik dirender dengan helper `publicUrl()`.
 */
class ShortLink extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'expires_at' => 'datetime',
        'last_clicked_at' => 'datetime',
    ];

    /**
     * Ambil atau buat short link untuk pasangan target_type+target_id.
     */
    public static function forTarget(string $type, int $id, ?int $createdBy = null): self
    {
        $existing = static::where('target_type', $type)
            ->where('target_id', $id)
            ->first();

        if ($existing) {
            return $existing;
        }

        return static::create([
            'slug' => static::generateUniqueSlug(),
            'target_type' => $type,
            'target_id' => $id,
            'created_by' => $createdBy,
        ]);
    }

    /**
     * URL publik singkat: {APP_URL}/i/{slug}
     */
    public function publicUrl(): string
    {
        return url('/i/' . $this->slug);
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function recordClick(): void
    {
        $this->increment('clicked_count');
        $this->forceFill(['last_clicked_at' => Carbon::now()])->save();
    }

    /**
     * Generate slug random 8 karakter (a-zA-Z0-9), retry kalau bentrok.
     */
    protected static function generateUniqueSlug(int $length = 8): string
    {
        $alphabet = 'abcdefghijkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        do {
            $slug = '';
            for ($i = 0; $i < $length; $i++) {
                $slug .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            }
        } while (static::where('slug', $slug)->exists());

        return $slug;
    }
}
