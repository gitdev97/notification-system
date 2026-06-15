<?php

namespace App\Repositories;

use App\Models\Notification;
use App\Repositories\Contracts\NotificationRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Eloquent implementation of the NotificationRepositoryInterface.
 */
class NotificationRepository implements NotificationRepositoryInterface
{
    public function __construct(
        private readonly Notification $model,
    ) {}

    /** {@inheritDoc} */
    public function create(array $data): Notification
    {
        return $this->model->newQuery()->create($data);
    }

    /** {@inheritDoc} */
    public function findOrFail(int $id): Notification
    {
        return $this->model->newQuery()->findOrFail($id);
    }

    /** {@inheritDoc} */
    public function paginateForUser(int $userId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->newQuery()
            ->forUser($userId)
            ->latest()
            ->paginate($perPage);
    }

    /** {@inheritDoc} */
    public function unreadCountForUser(int $userId): int
    {
        return $this->model->newQuery()
            ->forUser($userId)
            ->unread()
            ->count();
    }

    /** {@inheritDoc} */
    public function markAsRead(Notification $notification): Notification
    {
        $notification->markAsRead();

        return $notification->refresh();
    }

    /** {@inheritDoc} */
    public function markAllAsReadForUser(int $userId): int
    {
        return $this->model->newQuery()
            ->forUser($userId)
            ->unread()
            ->update(['read_at' => now()]);
    }
}
