<?php

namespace App\Services;

use App\Models\Tagihan;
use App\Models\User;
use App\Notifications\WorkflowNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;
use Throwable;

class TagihanReadyForSppNotificationService
{
    public function notifyIfNewlyReady(Tagihan $tagihan, ?string $previousStatus = null): void
    {
        $readyStatus = $this->readyStatusFor($tagihan);

        if (! $readyStatus || $tagihan->status !== $readyStatus || $previousStatus === $readyStatus) {
            return;
        }

        try {
            $operators = User::role('Operator BLU')->get();

            if ($operators->isEmpty()) {
                return;
            }

            Notification::send($operators, new WorkflowNotification($this->payloadFor($tagihan)));
        } catch (Throwable) {
            // Notifikasi tidak boleh menggagalkan proses approval utama.
        }
    }

    private function readyStatusFor(Tagihan $tagihan): ?string
    {
        return match ($tagihan->tipe_tagihan) {
            'KONTRAK' => 'READY_FOR_SPP',
            'PERJALDIN' => 'DISETUJUI_PERJALDIN',
            'HONORARIUM' => 'DISETUJUI',
            default => null,
        };
    }

    private function payloadFor(Tagihan $tagihan): array
    {
        $label = $this->labelFor($tagihan);
        $identifier = $tagihan->nomor_tagihan ?: $tagihan->deskripsi ?: 'ID ' . $tagihan->id;

        return [
            'title' => "{$label} Siap Dibuatkan SPP",
            'message' => "{$label} {$identifier} sudah selesai diverifikasi dan siap dibuatkan SPP.",
            'url' => $this->urlFor($tagihan),
            'icon' => 'receipt_long',
            'color' => 'success',
        ];
    }

    private function labelFor(Tagihan $tagihan): string
    {
        return match ($tagihan->tipe_tagihan) {
            'KONTRAK' => 'Tagihan Kontrak',
            'PERJALDIN' => 'Tagihan Perjaldin',
            'HONORARIUM' => 'Tagihan Honorarium',
            default => 'Tagihan',
        };
    }

    private function urlFor(Tagihan $tagihan): string
    {
        $route = match ($tagihan->tipe_tagihan) {
            'KONTRAK' => 'spps.kontrak.detail',
            'PERJALDIN' => 'spps.perjaldin.detail',
            'HONORARIUM' => 'spps.honor.detail',
            default => null,
        };

        if ($route && Route::has($route)) {
            return route($route, $tagihan->id);
        }

        $fallbackRoute = match ($tagihan->tipe_tagihan) {
            'KONTRAK' => 'spps.kontrak.index',
            'PERJALDIN' => 'spps.perjaldin.index',
            'HONORARIUM' => 'spps.honor.index',
            default => null,
        };

        return $fallbackRoute && Route::has($fallbackRoute) ? route($fallbackRoute) : '#';
    }
}
