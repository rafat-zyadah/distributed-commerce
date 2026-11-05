<?php

namespace App\Modules\Notifications\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    // Not "notifications": that name is reserved by Laravel's own notifications table.
    protected $table = 'app_notifications';

  //  protected $fillable = ['user_id', 'title', 'message'];
        protected $fillable = ['user_id', 'title', 'message', 'read_at'];

    protected function casts(): array
    {
        return ['read_at' => 'datetime'];
    }

    public function markAsRead(): void
    {
        if ($this->read_at === null) {
            $this->update(['read_at' => now()]);
        }
    }
}
