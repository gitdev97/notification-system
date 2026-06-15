<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Represents an auditable activity entry for a task (creation, updates, status changes, reassignments).
 *
 * @property int $id
 * @property int $task_id
 * @property int $user_id
 * @property string $type
 * @property string $description
 * @property array<string, mixed>|null $changes
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Task $task
 * @property-read User $user
 */
class TaskActivity extends Model
{
    protected $fillable = [
        'task_id',
        'user_id',
        'type',
        'description',
        'changes',
    ];

    protected function casts(): array
    {
        return [
            'changes' => 'array',
        ];
    }

    /** @return BelongsTo<Task, $this> */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
