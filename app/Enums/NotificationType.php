<?php

namespace App\Enums;

/**
 * Categorises notifications by the domain event that triggered them.
 */
enum NotificationType: string
{
    case TaskAssigned = 'task_assigned';
    case TaskCompleted = 'task_completed';
    case TaskStatusChanged = 'task_status_changed';
    case TaskUpdated = 'task_updated';
    case TaskCommented = 'task_commented';

    /**
     * Get the human-readable label for the notification type.
     */
    public function label(): string
    {
        return match ($this) {
            self::TaskAssigned => 'Task Assigned',
            self::TaskCompleted => 'Task Completed',
            self::TaskStatusChanged => 'Status Changed',
            self::TaskUpdated => 'Task Updated',
            self::TaskCommented => 'Task Commented',
        };
    }
}
