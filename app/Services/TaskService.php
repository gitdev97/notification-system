<?php

namespace App\Services;

use App\Enums\TaskStatus;
use App\Events\TaskAssigned;
use App\Events\TaskCompleted;
use App\Events\TaskStatusChanged;
use App\Events\TaskUpdated;
use App\Models\Task;
use App\Models\TaskActivity;
use App\Models\User;
use App\Repositories\Contracts\TaskRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * Orchestrates task CRUD operations, activity logging, and domain event dispatching.
 */
class TaskService
{
    public function __construct(
        private readonly TaskRepositoryInterface $taskRepository,
    ) {}

    /**
     * Create a new task, log the initial activities, and notify the assignee.
     *
     * @param  array<string, mixed>  $data  Validated task attributes (title, description, assigned_to).
     */
    public function createTask(array $data, int $creatorId): Task
    {
        $task = $this->taskRepository->create([
            ...$data,
            'created_by' => $creatorId,
            'status' => TaskStatus::Pending,
        ]);

        $task->load(['assignee', 'creator']);

        $this->recordActivity($task, $creatorId, 'created', 'created this task');

        $this->recordActivity($task, $creatorId, 'assigned', "assigned this task to {$task->assignee->name}");

        event(new TaskAssigned($task));

        return $task;
    }

    /**
     * Update a task's content (title, description, assignee), log changes, and dispatch events.
     *
     * @param  array<string, mixed>  $data  Validated update attributes.
     */
    public function updateTask(int $taskId, array $data): Task
    {
        $task = $this->taskRepository->findOrFail($taskId);
        $previousAssignee = $task->assigned_to;
        $changes = $this->detectChanges($task, $data);

        $task = $this->taskRepository->update($task, $data);

        if ($changes) {
            $userId = auth()->id() ?? $task->created_by;

            foreach ($changes as $change) {
                $this->recordActivity($task, $userId, $change['type'], $change['description'], $change['data'] ?? null);
            }

            if (isset($data['assigned_to']) && (int) $data['assigned_to'] !== $previousAssignee) {
                event(new TaskAssigned($task));
            }

            event(new TaskUpdated($task, $changes, $userId));
        }

        return $task;
    }

    /**
     * Transition a task to a new status, log the change, and notify the creator.
     *
     * Returns the task unchanged if the status is already the requested value.
     */
    public function updateStatus(int $taskId, TaskStatus $status, int $changedById): Task
    {
        $task = $this->taskRepository->findOrFail($taskId);
        $previousStatus = $task->status;

        if ($previousStatus === $status) {
            return $task;
        }

        $task = $this->taskRepository->updateStatus($task, $status);

        $this->recordActivity(
            $task,
            $changedById,
            'status_changed',
            "changed status from {$previousStatus->label()} to {$status->label()}",
            ['from' => $previousStatus->value, 'to' => $status->value],
        );

        event(new TaskStatusChanged($task, $previousStatus, $status, $changedById));

        if ($status === TaskStatus::Completed && $previousStatus !== TaskStatus::Completed) {
            event(new TaskCompleted($task));
        }

        return $task;
    }

    /**
     * Paginate tasks relevant to a specific user (assigned or created).
     */
    public function getTasksForUser(int $userId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->taskRepository->paginateForUser($userId, $perPage);
    }

    /**
     * Paginate all tasks with optional server-side filters.
     *
     * @param  array<string, mixed>  $filters  Supported keys: search, status, assigned_to.
     */
    public function getAllTasks(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->taskRepository->paginateAll($filters, $perPage);
    }

    /**
     * Retrieve a single task by its ID.
     */
    public function getTask(int $taskId): Task
    {
        return $this->taskRepository->findOrFail($taskId);
    }

    /**
     * Retrieve all tasks without pagination.
     *
     * @return Collection<int, Task>
     */
    public function getAllTasksUnpaginated(): Collection
    {
        return $this->taskRepository->all();
    }

    /**
     * Persist a task activity entry for the audit timeline.
     *
     * @param  array<string, mixed>|null  $changes
     */
    private function recordActivity(Task $task, int $userId, string $type, string $description, ?array $changes = null): TaskActivity
    {
        return TaskActivity::create([
            'task_id' => $task->id,
            'user_id' => $userId,
            'type' => $type,
            'description' => $description,
            'changes' => $changes,
        ]);
    }

    /**
     * Compare current task state with incoming data and return a list of detected changes.
     *
     * @param  array<string, mixed>  $data
     * @return array<int, array{type: string, description: string, data?: array}>
     */
    private function detectChanges(Task $task, array $data): array
    {
        $changes = [];

        if (isset($data['title']) && $data['title'] !== $task->title) {
            $changes[] = [
                'type' => 'updated',
                'description' => "changed the title from \"{$task->title}\" to \"{$data['title']}\"",
                'data' => ['field' => 'title', 'from' => $task->title, 'to' => $data['title']],
            ];
        }

        if (array_key_exists('description', $data) && $data['description'] !== $task->description) {
            $changes[] = [
                'type' => 'updated',
                'description' => 'updated the description',
                'data' => ['field' => 'description'],
            ];
        }

        if (isset($data['assigned_to']) && (int) $data['assigned_to'] !== $task->assigned_to) {
            $oldAssignee = $task->assignee->name;
            $newAssignee = User::find($data['assigned_to'])?->name ?? 'Unknown';
            $changes[] = [
                'type' => 'reassigned',
                'description' => "reassigned from {$oldAssignee} to {$newAssignee}",
                'data' => ['from' => $task->assigned_to, 'to' => (int) $data['assigned_to']],
            ];
        }

        return $changes;
    }
}
