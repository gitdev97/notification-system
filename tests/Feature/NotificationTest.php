<?php

namespace Tests\Feature;

use App\Enums\NotificationType;
use App\Events\NotificationCreated;
use App\Events\TaskAssigned;
use App\Listeners\SendTaskAssignedNotification;
use App\Models\Notification;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_notification_is_created_when_task_assigned_listener_fires(): void
    {
        $creator = User::factory()->create(['name' => 'John']);
        $assignee = User::factory()->create();

        $task = Task::factory()->create([
            'title' => 'Build API',
            'created_by' => $creator->id,
            'assigned_to' => $assignee->id,
        ]);

        Event::fake([NotificationCreated::class]);

        $listener = app(SendTaskAssignedNotification::class);
        $listener->handle(new TaskAssigned($task));

        $this->assertDatabaseHas('notifications', [
            'user_id' => $assignee->id,
            'type' => NotificationType::TaskAssigned->value,
        ]);

        $notification = Notification::where('user_id', $assignee->id)->first();
        $this->assertStringContains('John assigned you task: Build API', $notification->message);
    }

    public function test_authenticated_user_can_list_notifications(): void
    {
        $user = User::factory()->create();
        Notification::factory()->count(5)->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->getJson('/api/notifications');

        $response->assertStatus(200)
            ->assertJsonCount(5, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'type', 'type_label', 'message', 'is_read', 'created_at'],
                ],
            ]);
    }

    public function test_user_only_sees_own_notifications(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        Notification::factory()->count(3)->create(['user_id' => $user1->id]);
        Notification::factory()->count(2)->create(['user_id' => $user2->id]);

        $response = $this->actingAs($user1)->getJson('/api/notifications');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_notification_can_be_marked_as_read(): void
    {
        $user = User::factory()->create();
        $notification = Notification::factory()->create([
            'user_id' => $user->id,
            'read_at' => null,
        ]);

        $this->assertNull($notification->read_at);

        $response = $this->actingAs($user)
            ->postJson("/api/notifications/{$notification->id}/read");

        $response->assertStatus(200)
            ->assertJson(['is_read' => true])
            ->assertJsonFragment(['read_at' => $response->json('read_at')]);

        $notification->refresh();
        $this->assertNotNull($notification->read_at);
    }

    public function test_all_notifications_can_be_marked_as_read(): void
    {
        $user = User::factory()->create();
        Notification::factory()->count(5)->create([
            'user_id' => $user->id,
            'read_at' => null,
        ]);

        $response = $this->actingAs($user)->postJson('/api/notifications/mark-all-read');

        $response->assertStatus(200)
            ->assertJsonPath('count', 5);

        $this->assertEquals(0, Notification::where('user_id', $user->id)->whereNull('read_at')->count());
    }

    public function test_unread_count_endpoint_returns_correct_count(): void
    {
        $user = User::factory()->create();
        Notification::factory()->count(3)->create(['user_id' => $user->id, 'read_at' => null]);
        Notification::factory()->count(2)->read()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->getJson('/api/notifications/unread-count');

        $response->assertStatus(200)
            ->assertJsonPath('unread_count', 3);
    }

    public function test_unauthenticated_user_cannot_access_notifications(): void
    {
        $this->getJson('/api/notifications')->assertUnauthorized();
        $this->postJson('/api/notifications/1/read')->assertUnauthorized();
        $this->getJson('/api/notifications/unread-count')->assertUnauthorized();
    }

    private function assertStringContains(string $needle, string $haystack): void
    {
        $this->assertTrue(
            str_contains($haystack, $needle),
            "Failed asserting that '{$haystack}' contains '{$needle}'."
        );
    }
}
