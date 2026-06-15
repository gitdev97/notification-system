<?php

namespace App\Listeners;

use App\Events\NotificationCreated;
use App\Events\TaskStatusChanged;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

/**
 * Queued listener that notifies the task creator when the status changes.
 */
class SendTaskStatusChangedNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        private readonly NotificationService $notificationService,
    ) {}

    /**
     * Create status-change notifications for the creator and assignee, excluding the changer.
     */
    public function handle(TaskStatusChanged $event): void
    {
        $recipientIds = collect([
            (int) $event->task->assigned_to,
            (int) $event->task->created_by,
        ])->unique()->reject(fn (int $id) => $id === $event->changedBy)->values();

        foreach ($recipientIds as $recipientId) {
            $notification = $this->notificationService->createForStatusChange(
                $event->task,
                $event->oldStatus,
                $event->newStatus,
                $event->changedBy,
                $recipientId,
            );

            event(new NotificationCreated($notification));
        }
    }
}
