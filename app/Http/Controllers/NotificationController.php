<?php
namespace App\Http\Controllers;
use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $query = Notification::query();
        if ($request->boolean('unread')) $query->whereNull('read_at');
        $notifications = $query->latest()->paginate(30)->withQueryString();
        return view('notifications.index', ['notifications' => $notifications, 'unread' => Notification::whereNull('read_at')->count()]);
    }

    public function read(Notification $notification)
    {
        if (!$notification->read_at) $notification->update(['read_at' => now()]);
        return $notification->url ? redirect($notification->url) : back();
    }

    public function readAll()
    {
        Notification::whereNull('read_at')->update(['read_at' => now()]);
        return back()->with('status', 'All notifications marked as read.');
    }
}
