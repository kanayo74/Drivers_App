<?php

namespace App\Helpers;

class NotificationHelper
{
    /**
     * Safely get unread notification count.
     * Returns 0 instead of crashing if the notifications table doesn't exist yet.
     */
    public static function unreadCount(): int
    {
        try {
            return auth()->check()
                ? auth()->user()->unreadNotifications()->count()
                : 0;
        } catch (\Exception $e) {
            return 0;
        }
    }
}
