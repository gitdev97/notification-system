<?php

namespace App\Repositories;

use App\Enums\TaskStatus;
use App\Models\Task;
use App\Repositories\Contracts\TaskRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * Eloquent implementation of the TaskRepositoryInterface.
 */
class TaskRepository implements TaskRepositoryInterface
{
    public function __construct(
        private readonly Task $model,
    ) {}

    /** {@inheritDoc} */
    public function create(array $data): Task
    {
        return $this->model->newQuery()->create($data);
    }

    /** {@inheritDoc} */
    public function findOrFail(int $id): Task
    {
        return $this->model->newQuery()
            ->with(['assignee', 'creator'])
            ->findOrFail($id);
    }

    /** {@inheritDoc} */
    public function update(Task $task, array $data): Task
    {
        $task->update($data);

        return $task->fresh(['assignee', 'creator']);
    }

    /** {@inheritDoc} */
    public function updateStatus(Task $task, TaskStatus $status): Task
    {
        $task->update(['status' => $status]);

        return $task->fresh(['assignee', 'creator']);
    }

    /** {@inheritDoc} */
    public function paginateForUser(int $userId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->newQuery()
            ->with(['assignee', 'creator'])
            ->where('assigned_to', $userId)
            ->orWhere('created_by', $userId)
            ->latest()
            ->paginate($perPage);
    }

    /** {@inheritDoc} */
    public function paginateAll(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->newQuery()->with(['assignee', 'creator']);

        if (! empty($filters['search'])) {
            $query->where('title', 'like', '%'.trim($filters['search']).'%');
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['assigned_to'])) {
            $query->where('assigned_to', $filters['assigned_to']);
        }

        return $query->latest()->paginate($perPage)->withQueryString();
    }

    /** {@inheritDoc} */
    public function all(): Collection
    {
        return $this->model->newQuery()
            ->with(['assignee', 'creator'])
            ->latest()
            ->get();
    }
}
