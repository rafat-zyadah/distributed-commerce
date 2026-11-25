<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class CreateNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 30;

    public function __construct(
        public int $userId,
        public string $title,
        public string $message,
        public string $eventKey
    ) {}

    public function handle(): void
    {
        // The unique event key prevents duplicate notifications
        // if the same job is retried.
        DB::table('app_notifications')->insertOrIgnore([
            'user_id' => $this->userId,
            'title' => $this->title,
            'message' => $this->message,
            'event_key' => $this->eventKey,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
