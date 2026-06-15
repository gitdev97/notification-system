<?php

namespace App\Http\Controllers;

use App\Enums\TaskStatus;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Http\Requests\UpdateTaskStatusRequest;
use App\Http\Resources\TaskActivityResource;
use App\Http\Resources\TaskResource;
use App\Models\User;
use App\Services\TaskService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use OpenApi\Attributes as OA;

class TaskController extends Controller
{
    public function __construct(
        private readonly TaskService $taskService,
    ) {}

    /**
     * Display the paginated, filterable task board.
     */
    public function index(Request $request): Response
    {
        $filters = $request->only(['search', 'status', 'assigned_to']);

        $tasks = $this->taskService->getAllTasks(filters: $filters, perPage: 20);

        return Inertia::render('Tasks/Index', [
            'tasks' => TaskResource::collection($tasks),
            'filters' => $filters,
            'users' => User::select('id', 'name', 'email')->get(),
            'statuses' => collect(TaskStatus::cases())->map(fn (TaskStatus $s) => [
                'value' => $s->value,
                'label' => $s->label(),
            ]),
        ]);
    }

    /**
     * Display a single task with its activity timeline.
     */
    public function show(int $taskId): Response
    {
        $task = $this->taskService->getTask($taskId);
        $task->load(['activities.user']);

        return Inertia::render('Tasks/Show', [
            'task' => new TaskResource($task),
            'activities' => TaskActivityResource::collection($task->activities),
            'users' => User::select('id', 'name', 'email')->get(),
            'statuses' => collect(TaskStatus::cases())->map(fn (TaskStatus $s) => [
                'value' => $s->value,
                'label' => $s->label(),
            ]),
        ]);
    }

    /**
     * Create a new task and dispatch assignment notifications.
     */
    #[OA\Post(
        path: '/tasks',
        summary: 'Create a new task',
        description: 'Creates a task, records activity, and notifies the assignee in real time.',
        tags: ['Tasks'],
        security: [['sessionAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['title', 'assigned_to'],
                properties: [
                    new OA\Property(property: 'title', type: 'string', maxLength: 255, example: 'Build REST API'),
                    new OA\Property(property: 'description', type: 'string', nullable: true, example: 'Create REST endpoints for the project.'),
                    new OA\Property(property: 'assigned_to', type: 'integer', example: 2),
                ],
            ),
        ),
        responses: [
            new OA\Response(response: 201, description: 'Task created', content: new OA\JsonContent(ref: '#/components/schemas/TaskResource')),
            new OA\Response(response: 422, description: 'Validation error'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ],
    )]
    public function store(StoreTaskRequest $request): JsonResponse
    {
        $task = $this->taskService->createTask(
            $request->validated(),
            $request->user()->id,
        );

        return (new TaskResource($task))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Update a task's content (title, description, assignee).
     */
    #[OA\Put(
        path: '/tasks/{taskId}',
        summary: 'Update a task',
        description: 'Updates task content and notifies relevant users of changes.',
        tags: ['Tasks'],
        security: [['sessionAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'taskId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'title', type: 'string', maxLength: 255),
                    new OA\Property(property: 'description', type: 'string', nullable: true),
                    new OA\Property(property: 'assigned_to', type: 'integer'),
                ],
            ),
        ),
        responses: [
            new OA\Response(response: 200, description: 'Task updated', content: new OA\JsonContent(ref: '#/components/schemas/TaskResource')),
            new OA\Response(response: 404, description: 'Task not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ],
    )]
    public function update(UpdateTaskRequest $request, int $taskId): JsonResponse
    {
        $task = $this->taskService->updateTask(
            $taskId,
            $request->validated(),
        );

        return (new TaskResource($task))
            ->response()
            ->setStatusCode(200);
    }

    /**
     * Transition a task to a new status and notify the creator.
     */
    #[OA\Patch(
        path: '/tasks/{taskId}/status',
        summary: 'Update task status',
        description: 'Transitions a task to a new status and notifies the creator.',
        tags: ['Tasks'],
        security: [['sessionAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'taskId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['status'],
                properties: [
                    new OA\Property(property: 'status', type: 'string', enum: ['pending', 'in_progress', 'completed']),
                ],
            ),
        ),
        responses: [
            new OA\Response(response: 200, description: 'Status updated', content: new OA\JsonContent(ref: '#/components/schemas/TaskResource')),
            new OA\Response(response: 404, description: 'Task not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ],
    )]
    public function updateStatus(UpdateTaskStatusRequest $request, int $taskId): JsonResponse
    {
        $task = $this->taskService->updateStatus(
            $taskId,
            TaskStatus::from($request->validated('status')),
            $request->user()->id,
        );

        return (new TaskResource($task))
            ->response()
            ->setStatusCode(200);
    }
}
