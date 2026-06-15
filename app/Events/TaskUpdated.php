<?php

namespace App\Events;

use App\Models\Task;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired when a task's content (title, description, assignee) is updated.
 */
class TaskUpdated
{
    use Dispatchable, SerializesModels;

    /**
     * @param  array<int, array{type: string, description: string, data?: array}>  $changes
     */
    public function __construct(
        public readonly Task $task,
        public readonly array $changes,
        public readonly int $updatedBy,
    ) {}
}
