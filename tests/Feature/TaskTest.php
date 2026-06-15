<?php

namespace Tests\Feature;

use App\Enums\TaskStatus;
use App\Events\TaskAssigned;
use App\Events\TaskCompleted;
use App\Events\TaskStatusChanged;
use App\Listeners\SendTaskAssignedNotification;
use App\Models\Task;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class TaskTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_user_cannot_create_task(): void
    {
        $response = $this->postJson('/api/tasks', [
            'title' => 'Test Task',
            'description' => 'A test',
            'assigned_to' => 1,
        ]);

        $response->assertStatus(401);
    }

    public function test_authenticated_user_can_create_task(): void
    {
        Event::fake([TaskAssigned::class]);

        $user = User::factory()->create();
        $assignee = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/tasks', [
            'title' => 'Build API',
            'description' => 'Create REST endpoints',
            'assigned_to' => $assignee->id,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.title', 'Build API')
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.assignee.id', $assignee->id)
            ->assertJsonPath('data.creator.id', $user->id);

        $this->assertDatabaseHas('tasks', [
            'title' => 'Build API',
            'assigned_to' => $assignee->id,
            'created_by' => $user->id,
            'status' => TaskStatus::Pending->value,
        ]);
    }

    public function test_task_creation_dispatches_task_assigned_event(): void
    {
        Event::fake([TaskAssigned::class]);

        $user = User::factory()->create();
        $assignee = User::factory()->create();

        $this->actingAs($user)->postJson('/api/tasks', [
            'title' => 'Build API',
            'description' => 'Create REST endpoints',
            'assigned_to' => $assignee->id,
        ]);

        Event::assertDispatched(TaskAssigned::class, function ($event) use ($assignee) {
            return $event->task->assigned_to === $assignee->id;
        });
    }

    public function test_task_assigned_listener_implements_should_queue(): void
    {
        $this->assertTrue(
            is_subclass_of(
                SendTaskAssignedNotification::class,
                ShouldQueue::class,
            ),
            'SendTaskAssignedNotification must implement ShouldQueue',
        );
    }

    public function test_task_creation_validates_required_fields(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/tasks', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title', 'assigned_to']);
    }

    public function test_task_creation_validates_assigned_user_exists(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/tasks', [
            'title' => 'Build API',
            'assigned_to' => 9999,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['assigned_to']);
    }

    public function test_task_status_can_be_updated(): void
    {
        Event::fake([TaskAssigned::class, TaskCompleted::class, TaskStatusChanged::class]);

        $user = User::factory()->create();
        $task = Task::factory()->pending()->create([
            'created_by' => $user->id,
            'assigned_to' => $user->id,
        ]);

        $response = $this->actingAs($user)->patchJson("/api/tasks/{$task->id}/status", [
            'status' => 'in_progress',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'in_progress');

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'status' => 'in_progress',
        ]);
    }

    public function test_completing_task_dispatches_task_completed_event(): void
    {
        Event::fake([TaskAssigned::class, TaskCompleted::class, TaskStatusChanged::class]);

        $user = User::factory()->create();
        $task = Task::factory()->inProgress()->create([
            'created_by' => $user->id,
            'assigned_to' => $user->id,
        ]);

        $this->actingAs($user)->patchJson("/api/tasks/{$task->id}/status", [
            'status' => 'completed',
        ]);

        Event::assertDispatched(TaskCompleted::class, function ($event) use ($task) {
            return $event->task->id === $task->id;
        });
    }

    public function test_invalid_status_value_is_rejected(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->create([
            'created_by' => $user->id,
            'assigned_to' => $user->id,
        ]);

        $response = $this->actingAs($user)->patchJson("/api/tasks/{$task->id}/status", [
            'status' => 'invalid_status',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }

    public function test_dashboard_page_loads_with_tasks(): void
    {
        $user = User::factory()->create();
        Task::factory()->count(3)->create([
            'created_by' => $user->id,
            'assigned_to' => $user->id,
        ]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200)
            ->assertInertia(fn ($page) => $page
                ->component('Tasks/Index')
                ->has('tasks.data', 3)
                ->has('users')
                ->has('statuses')
            );
    }
}
