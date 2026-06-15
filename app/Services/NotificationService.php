<?php

namespace App\Services;

use App\Enums\NotificationType;
use App\Enums\TaskStatus;
use App\Models\Notification;
use App\Models\Task;
use App\Models\User;
use App\Repositories\Contracts\NotificationRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Handles notification creation for various domain events and read-state management.
 */
class NotificationService
{
    public function __construct(
        private readonly NotificationRepositoryInterface $notificationRepository,
    ) {}

    /**
     * Create a notification for the assignee when a task is assigned to them.
     */
    public function createForTaskAssignment(Task $task): Notification
    {
        $creatorName = $task->creator->name;

        return $this->notificationRepository->create([
            'user_id' => $task->assigned_to,
            'type' => NotificationType::TaskAssigned,
            'message' => "{$creatorName} assigned you task: {$task->title}",
            'data' => [
                'task_id' => $task->id,
                'task_title' => $task->title,
                'assigned_by' => $task->created_by,
            ],
        ]);
    }

    /**
     * Create a notification for the creator when their task is completed.
     */
    public function createForTaskCompletion(Task $task): Notification
    {
        $assigneeName = $task->assignee->name;

        return $this->notificationRepository->create([
            'user_id' => $task->created_by,
            'type' => NotificationType::TaskCompleted,
            'message' => "{$assigneeName} completed task: {$task->title}",
            'data' => [
                'task_id' => $task->id,
                'task_title' => $task->title,
                'completed_by' => $task->assigned_to,
            ],
        ]);
    }

    /**
     * Create a status-change notification for a specific recipient.
     */
    public function createForStatusChange(Task $task, TaskStatus $oldStatus, TaskStatus $newStatus, int $changedById, int $recipientId): Notification
    {
        $changedBy = User::find($changedById);
        $changedByName = $changedBy?->name ?? 'Someone';

        return $this->notificationRepository->create([
            'user_id' => $recipientId,
            'type' => NotificationType::TaskStatusChanged,
            'message' => "{$changedByName} changed \"{$task->title}\" from {$oldStatus->label()} to {$newStatus->label()}",
            'data' => [
                'task_id' => $task->id,
                'task_title' => $task->title,
                'old_status' => $oldStatus->value,
                'new_status' => $newStatus->value,
                'changed_by' => $changedById,
            ],
        ]);
    }

    /**
     * Create a notification for a recipient when task content is updated.
     *
     * @param  array<int, array{type: string, description: string, data?: array}>  $changes
     */
    public function createForTaskUpdate(Task $task, array $changes, int $updatedById, int $recipientId): Notification
    {
        $updatedBy = User::find($updatedById);
        $updatedByName = $updatedBy?->name ?? 'Someone';

        $summary = collect($changes)->pluck('description')->join(', ');

        return $this->notificationRepository->create([
            'user_id' => $recipientId,
            'type' => NotificationType::TaskUpdated,
            'message' => "{$updatedByName} updated \"{$task->title}\": {$summary}",
            'data' => [
                'task_id' => $task->id,
                'task_title' => $task->title,
                'updated_by' => $updatedById,
                'changes' => $changes,
            ],
        ]);
    }

    /**
     * Paginate notifications for a given user, most recent first.
     */
    public function getNotificationsForUser(int $userId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->notificationRepository->paginateForUser($userId, $perPage);
    }

    /**
     * Get the count of unread notifications for a given user.
     */
    public function getUnreadCount(int $userId): int
    {
        return $this->notificationRepository->unreadCountForUser($userId);
    }

    /**
     * Mark a single notification as read by its ID.
     */
    public function markAsRead(int $notificationId): Notification
    {
        $notification = $this->notificationRepository->findOrFail($notificationId);

        return $this->notificationRepository->markAsRead($notification);
    }

    /**
     * Mark all unread notifications as read for a given user. Returns the count affected.
     */
    public function markAllAsRead(int $userId): int
    {
        return $this->notificationRepository->markAllAsReadForUser($userId);
    }
}
