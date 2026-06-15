<?php

namespace App\Http\Controllers;

use App\Http\Resources\NotificationResource;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use OpenApi\Attributes as OA;

class NotificationController extends Controller
{
    public function __construct(
        private readonly NotificationService $notificationService,
    ) {}

    /**
     * List paginated notifications for the authenticated user.
     */
    #[OA\Get(
        path: '/notifications',
        summary: 'List notifications',
        description: 'Returns paginated notifications for the authenticated user, most recent first.',
        tags: ['Notifications'],
        security: [['sessionAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Paginated notification list',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/NotificationResource')),
                    ],
                ),
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ],
    )]
    public function index(Request $request): AnonymousResourceCollection
    {
        $notifications = $this->notificationService->getNotificationsForUser(
            $request->user()->id,
            perPage: 20,
        );

        return NotificationResource::collection($notifications);
    }

    /**
     * Return the count of unread notifications for the authenticated user.
     */
    #[OA\Get(
        path: '/notifications/unread-count',
        summary: 'Get unread count',
        description: 'Returns the number of unread notifications for the authenticated user.',
        tags: ['Notifications'],
        security: [['sessionAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Unread count',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'unread_count', type: 'integer', example: 3),
                    ],
                ),
            ),
        ],
    )]
    public function unreadCount(Request $request): JsonResponse
    {
        $count = $this->notificationService->getUnreadCount($request->user()->id);

        return response()->json(['unread_count' => $count]);
    }

    /**
     * Mark a single notification as read by its ID.
     */
    #[OA\Post(
        path: '/notifications/{id}/read',
        summary: 'Mark notification as read',
        description: 'Marks a single notification as read and returns the updated resource.',
        tags: ['Notifications'],
        security: [['sessionAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Notification marked as read', content: new OA\JsonContent(ref: '#/components/schemas/NotificationResource')),
            new OA\Response(response: 404, description: 'Notification not found'),
        ],
    )]
    public function markAsRead(int $id): NotificationResource
    {
        $notification = $this->notificationService->markAsRead($id);

        return new NotificationResource($notification);
    }

    /**
     * Mark all unread notifications as read for the authenticated user.
     */
    #[OA\Post(
        path: '/notifications/mark-all-read',
        summary: 'Mark all as read',
        description: 'Marks all unread notifications as read for the authenticated user.',
        tags: ['Notifications'],
        security: [['sessionAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'All notifications marked as read',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'All notifications marked as read.'),
                        new OA\Property(property: 'count', type: 'integer', example: 5),
                    ],
                ),
            ),
        ],
    )]
    public function markAllAsRead(Request $request): JsonResponse
    {
        $count = $this->notificationService->markAllAsRead($request->user()->id);

        return response()->json([
            'message' => 'All notifications marked as read.',
            'count' => $count,
        ]);
    }
}
