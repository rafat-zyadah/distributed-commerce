<?php

namespace App\Modules\Notifications\Services;

use App\Jobs\CreateNotificationJob;
use Illuminate\Support\Str;

class NotificationService
{
    public function send(int $userId, string $title, string $message): void
    {
        $job = new CreateNotificationJob(
            userId: $userId,
            title: $title,
            message: $message,
            eventKey: (string) Str::uuid()
        );

        // Keep existing tests synchronous.
        if (config('queue.default') === 'sync') {
            dispatch_sync($job);
            return;
        }

        // Dispatch only after the database transaction commits.
        dispatch($job)->afterCommit();
    }
}
