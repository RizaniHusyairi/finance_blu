<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Mengambil struktur JSON notifikasi saat ini
     */
    public function fetch()
    {
        if(!auth()->check()) return response()->json(['count' => 0, 'notifications' => []]);

        $user = auth()->user();
        $notifications = $user->unreadNotifications()->take(10)->get()->map(function($notif) {
            return [
                'id' => $notif->id,
                'title' => $notif->data['title'] ?? 'Pesan',
                'message' => $notif->data['message'] ?? '',
                'url' => $notif->data['url'] ?? '#',
                'icon' => $notif->data['icon'] ?? 'notifications',
                'color' => $notif->data['color'] ?? 'primary',
                'time_ago' => $notif->created_at->diffForHumans()
            ];
        });

        return response()->json([
            'count' => $user->unreadNotifications()->count(),
            'notifications' => $notifications
        ]);
    }

    /**
     * Reset badge alert
     */
    public function markAsRead()
    {
        if(auth()->check()){
            auth()->user()->unreadNotifications->markAsRead();
        }
        return response()->json(['status' => 'success']);
    }
}
