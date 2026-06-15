import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

export interface User {
    id: number;
    name: string;
    email: string;
    email_verified_at?: string;
}

export interface Task {
    id: number;
    title: string;
    description: string | null;
    status: 'pending' | 'in_progress' | 'completed';
    status_label: string;
    assignee: User;
    creator: User;
    created_at: string;
    updated_at: string;
}

export interface Notification {
    id: number;
    type: 'task_assigned' | 'task_completed' | 'task_commented';
    type_label: string;
    message: string;
    data: Record<string, unknown>;
    read_at: string | null;
    is_read: boolean;
    created_at: string;
}

export interface TaskActivity {
    id: number;
    type: 'created' | 'assigned' | 'updated' | 'reassigned' | 'status_changed';
    description: string;
    changes: Record<string, unknown> | null;
    user: User;
    created_at: string;
    created_at_human: string;
}

export interface TaskStatus {
    value: string;
    label: string;
}

export interface PaginatedResponse<T> {
    data: T[];
    links: {
        first: string;
        last: string;
        prev: string | null;
        next: string | null;
    };
    meta: {
        current_page: number;
        from: number;
        last_page: number;
        per_page: number;
        to: number;
        total: number;
    };
}

export type PageProps<
    T extends Record<string, unknown> = Record<string, unknown>,
> = T & {
    auth: {
        user: User;
    };
    notifications: {
        unread_count: number;
    } | null;
};

declare global {
    interface Window {
        axios: typeof import('axios').default;
        Pusher: typeof Pusher;
        Echo: Echo;
    }
}
