<?php

namespace Database\Seeders;

use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\TaskActivity;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
        ]);

        $john = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => bcrypt('password'),
        ]);

        $jane = User::factory()->create([
            'name' => 'Jane Smith',
            'email' => 'jane@example.com',
            'password' => bcrypt('password'),
        ]);

        $mike = User::factory()->create([
            'name' => 'Mike Johnson',
            'email' => 'mike@example.com',
            'password' => bcrypt('password'),
        ]);

        $users = collect([$admin, $john, $jane, $mike]);

        $taskDefinitions = [
            ['title' => 'Build REST API', 'description' => 'Create REST endpoints for the project management module.', 'status' => TaskStatus::InProgress],
            ['title' => 'Design Database Schema', 'description' => 'Normalize tables and add proper indexes for the notification system.', 'status' => TaskStatus::Completed],
            ['title' => 'Set Up CI/CD Pipeline', 'description' => 'Configure Bitbucket pipelines for automated testing and deployment to AWS.', 'status' => TaskStatus::Pending],
            ['title' => 'Implement User Authentication', 'description' => 'Add Sanctum-based token authentication with proper middleware guards.', 'status' => TaskStatus::Completed],
            ['title' => 'Write Feature Tests', 'description' => 'Cover task creation, notifications, and event dispatching with PHPUnit tests.', 'status' => TaskStatus::Pending],
            ['title' => 'Integrate Pusher Broadcasting', 'description' => 'Set up real-time notifications via Pusher and Laravel Echo.', 'status' => TaskStatus::InProgress],
            ['title' => 'Optimize Eloquent Queries', 'description' => 'Profile and optimize N+1 queries on the task listing page.', 'status' => TaskStatus::Pending],
            ['title' => 'Code Review: Notification Module', 'description' => 'Review PR #42 — event/listener architecture and queue processing.', 'status' => TaskStatus::Pending],
        ];

        $tasks = [];
        foreach ($taskDefinitions as $i => $def) {
            $creator = $users[$i % $users->count()];
            $assignee = $users->except($creator->id)->random();

            $tasks[] = Task::create([
                'title' => $def['title'],
                'description' => $def['description'],
                'status' => $def['status'],
                'created_by' => $creator->id,
                'assigned_to' => $assignee->id,
            ]);
        }

        foreach ($tasks as $task) {
            $creator = User::find($task->created_by);

            TaskActivity::create([
                'task_id' => $task->id,
                'user_id' => $creator->id,
                'type' => 'created',
                'description' => 'created this task',
                'created_at' => $task->created_at,
                'updated_at' => $task->created_at,
            ]);
        }
    }
}
