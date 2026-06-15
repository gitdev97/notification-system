<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

/**
 * API resource transformation for the Notification model.
 */
#[OA\Schema(
    schema: 'NotificationResource',
    title: 'Notification',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'type', type: 'string', enum: ['task_assigned', 'task_completed', 'task_status_changed', 'task_updated', 'task_commented'], example: 'task_assigned'),
        new OA\Property(property: 'type_label', type: 'string', example: 'Task Assigned'),
        new OA\Property(property: 'message', type: 'string', example: 'John Doe assigned you task: Build REST API'),
        new OA\Property(property: 'data', type: 'object', nullable: true),
        new OA\Property(property: 'read_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'is_read', type: 'boolean', example: false),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
    ],
)]
class NotificationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type->value,
            'type_label' => $this->type->label(),
            'message' => $this->message,
            'data' => $this->data,
            'read_at' => $this->read_at?->toISOString(),
            'is_read' => $this->isRead(),
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}
