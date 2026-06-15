<?php

namespace App\Listeners;

use App\Events\NotificationCreated;
use App\Events\TaskCompleted;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

/**
 * Queued listener that notifies the task creator when their task is completed.
 */
class SendTaskCompletedNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        private readonly NotificationService $notificationService,
    ) {}

    /**
     * Create the completion notification and broadcast it in real time.
     */
    public function handle(TaskCompleted $event): void
    {
        $notification = $this->notificationService->createForTaskCompletion($event->task);

        event(new NotificationCreated($notification));
    }
}
