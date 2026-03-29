<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use App\Models\Perjaldin;

class PerjaldinRevisiNotification extends Notification
{
    use Queueable;

    protected $perjaldin;
    protected $revisioleh;
    protected $catatan;

    public function __construct(Perjaldin $perjaldin, string $revisiOleh, string $catatan)
    {
        $this->perjaldin  = $perjaldin;
        $this->revisioleh = $revisiOleh;
        $this->catatan    = $catatan;
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'perjaldin_id' => $this->perjaldin->perjaldin_id,
            'uraian'       => $this->perjaldin->uraian,
            'revisi_oleh'  => $this->revisioleh,
            'catatan'      => $this->catatan,
            'message'      => "Perjaldin '{$this->perjaldin->uraian}' dikembalikan untuk revisi oleh {$this->revisioleh}.",
            'url'          => route('perjaldins.index'),
        ];
    }
}
