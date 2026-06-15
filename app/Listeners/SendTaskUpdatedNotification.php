<?php

namespace App\Listeners;

use App\Events\NotificationCreated;
use App\Events\TaskUpdated;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

/**
 * Queued listener that notifies the creator and assignee when task content is updated.
 *
 * The user who made the change is excluded from receiving the notification.
 */
class SendTaskUpdatedNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        private readonly NotificationService $notificationService,
    ) {}

    /**
     * Create update notifications for all relevant recipients and broadcast them.
     */
    public function handle(TaskUpdated $event): void
    {
        $recipientIds = collect([
            (int) $event->task->assigned_to,
            (int) $event->task->created_by,
        ])->unique()->reject(fn (int $id) => $id === $event->updatedBy)->values();

        foreach ($recipientIds as $recipientId) {
            $notification = $this->notificationService->createForTaskUpdate(
                $event->task,
                $event->changes,
                $event->updatedBy,
                $recipientId,
            );

            event(new NotificationCreated($notification));
        }
    }
}
