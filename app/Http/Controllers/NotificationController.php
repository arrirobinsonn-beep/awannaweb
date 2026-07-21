<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class NotificationController extends Controller
{
    // ─── Daftar Notifikasi ─────────────────────────────────────

    public function index(): View
    {
        $user = Auth::user();

        $notifications = Notification::forUser($user->id)
            ->latest()
            ->paginate(20);

        return view('notifications.index', compact('notifications'));
    }

    // ─── Tandai Sudah Dibaca ───────────────────────────────────

    public function markRead(Notification $notification): RedirectResponse
    {
        abort_unless($notification->user_id === Auth::id(), 403);

        $notification->update(['is_read' => true]);

        // Redirect ke URL tujuan jika ada
        $url = $notification->data['url'] ?? null;
        if ($url) {
            return redirect()->to($url);
        }

        return redirect()->route('notifications.index');
    }

    // ─── Tandai Semua Dibaca ───────────────────────────────────

    public function markAllRead(): RedirectResponse
    {
        Notification::forUser(Auth::id())
            ->unread()
            ->update(['is_read' => true]);

        return redirect()->route('notifications.index')
            ->with('success', 'Semua notifikasi telah ditandai sudah dibaca.');
    }

    // ─── Hitung notifikasi belum dibaca (JSON) ─────────────────

    public function unreadCount(): JsonResponse
    {
        $count = Notification::forUser(Auth::id())
            ->unread()
            ->count();

        return response()->json(['count' => $count]);
    }

    // ─── Ambil notifikasi terbaru (JSON) ───────────────────────

    public function latest(): JsonResponse
    {
        $notifications = Notification::forUser(Auth::id())
            ->with('fromUser')
            ->latest()
            ->limit(10)
            ->get()
            ->map(function ($n) {
                return [
                    'id' => $n->id,
                    'type' => $n->type,
                    'title' => $n->title,
                    'message' => $n->message,
                    'is_read' => $n->is_read,
                    'created_at' => $n->created_at->diffForHumans(),
                    'url' => $n->data['url'] ?? null,
                ];
            });

        $unreadCount = Notification::forUser(Auth::id())->unread()->count();

        return response()->json([
            'notifications' => $notifications,
            'unread_count' => $unreadCount,
        ]);
    }
}
