<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index()
    {
        $notifications = Notification::latest()->limit(50)->get();

        return response()->json($notifications->map(fn (Notification $n) => $this->toView($n)));
    }

    public function unread()
    {
        $notifications = Notification::where('is_read', false)->latest()->get();

        return response()->json([
            'count' => $notifications->count(),
            'notifications' => $notifications->map(fn (Notification $n) => $this->toView($n)),
        ]);
    }

    public function markRead(Notification $notification)
    {
        $notification->update(['is_read' => true]);

        return response()->json(['message' => 'Marked as read.', 'notification' => $this->toView($notification)]);
    }

    public function markAllRead()
    {
        Notification::where('is_read', false)->update(['is_read' => true]);

        return response()->json(['message' => 'All notifications marked as read.']);
    }

    public function destroy(Notification $notification)
    {
        $notification->delete();

        return response()->json(['message' => 'Notification deleted.']);
    }

    private function toView(Notification $n): array
    {
        return [
            'id' => $n->id,
            'type' => $n->type,
            'title' => $n->title,
            'body' => $n->body,
            'data' => $n->data,
            'isRead' => $n->is_read,
            'createdAt' => $n->created_at?->valueOf(),
        ];
    }
}