<?php

namespace App\Repositories\Contracts;

use App\Models\Notification;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Contract for notification persistence operations.
 */
interface NotificationRepositoryInterface
{
    /**
     * Persist a new notification.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Notification;

    /**
     * Find a notification by ID or throw a ModelNotFoundException.
     */
    public function findOrFail(int $id): Notification;

    /**
     * Paginate notifications for a given user, most recent first.
     */
    public function paginateForUser(int $userId, int $perPage = 15): LengthAwarePaginator;

    /**
     * Count unread notifications for a given user.
     */
    public function unreadCountForUser(int $userId): int;

    /**
     * Mark a single notification as read and return the refreshed instance.
     */
    public function markAsRead(Notification $notification): Notification;

    /**
     * Mark all unread notifications as read for a given user. Returns the number of rows affected.
     */
    public function markAllAsReadForUser(int $userId): int;
}
