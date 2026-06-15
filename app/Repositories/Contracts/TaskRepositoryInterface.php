<?php

namespace App\Repositories\Contracts;

use App\Enums\TaskStatus;
use App\Models\Task;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * Contract for task persistence operations.
 */
interface TaskRepositoryInterface
{
    /**
     * Persist a new task from the given data.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Task;

    /**
     * Find a task by ID or throw a ModelNotFoundException.
     */
    public function findOrFail(int $id): Task;

    /**
     * Update a task's attributes.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Task $task, array $data): Task;

    /**
     * Transition a task to a new status.
     */
    public function updateStatus(Task $task, TaskStatus $status): Task;

    /**
     * Paginate tasks assigned to or created by a given user.
     */
    public function paginateForUser(int $userId, int $perPage = 15): LengthAwarePaginator;

    /**
     * Paginate all tasks with optional filters (search, status, assigned_to).
     *
     * @param  array<string, mixed>  $filters
     */
    public function paginateAll(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Retrieve all tasks without pagination.
     *
     * @return Collection<int, Task>
     */
    public function all(): Collection;
}
