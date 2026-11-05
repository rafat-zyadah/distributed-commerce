<?php

namespace App\Modules\Notifications\Services;

use App\Modules\Notifications\Models\Notification;

class NotificationService
{
    public function send(int $userId, string $title, string $message): Notification
    {
        return Notification::create([
            'user_id' => $userId,
            'title' => $title,
            'message' => $message,
        ]);
    }
}
