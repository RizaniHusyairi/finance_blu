<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WorkflowNotification extends Notification
{
    use Queueable;

    private $data;

    /**
     * Create a new notification instance.
     * $data = ['title', 'message', 'url', 'icon', 'color']
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->data['title'] ?? 'Pemberitahuan Baru',
            'message' => $this->data['message'] ?? '',
            'url' => $this->data['url'] ?? '#',
            'icon' => $this->data['icon'] ?? 'notifications',
            'color' => $this->data['color'] ?? 'primary',
        ];
    }
}
