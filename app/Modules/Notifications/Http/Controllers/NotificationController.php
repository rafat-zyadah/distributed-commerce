<?php

namespace App\Modules\Notifications\Http\Controllers;

use App\Modules\Notifications\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class NotificationController extends Controller
{
    /** Only the logged-in user's notifications. */
    public function index(): JsonResponse
    {
        $notifications = Notification::query()
            ->where('user_id', auth('api')->user()->id)
            ->latest('id')
            ->paginate(10);

        return response()->json($notifications);
    }

    public function markRead(Notification $notification): JsonResponse
    {
        abort_unless(
            $notification->user_id === auth('api')->user()->id,
            403,
            'This action is unauthorized.'
        );

        $notification->markAsRead();

        return response()->json(['data' => $notification->refresh()]);
    }
}
