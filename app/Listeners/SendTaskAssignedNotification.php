<?php

namespace App\Listeners;

use App\Events\NotificationCreated;
use App\Events\TaskAssigned;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

/**
 * Queued listener that creates a notification for the assignee when a task is assigned.
 */
class SendTaskAssignedNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        private readonly NotificationService $notificationService,
    ) {}

    /**
     * Create the assignment notification and broadcast it in real time.
     */
    public function handle(TaskAssigned $event): void
    {
        $notification = $this->notificationService->createForTaskAssignment($event->task);

        event(new NotificationCreated($notification));
    }
}
