<?php

namespace App\Events;

use App\Models\Task;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired when a task transitions to the "completed" status.
 */
class TaskCompleted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Task $task,
    ) {}
}
