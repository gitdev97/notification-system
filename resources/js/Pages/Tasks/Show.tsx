import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Task, TaskActivity, TaskStatus, User } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { FormEvent, useState } from 'react';

interface Props {
    task: { data: Task };
    activities: { data: TaskActivity[] };
    users: User[];
    statuses: TaskStatus[];
}

const statusConfig: Record<string, { bg: string; dot: string; text: string }> = {
    pending: { bg: 'bg-amber-50 ring-amber-200', dot: 'bg-amber-400', text: 'text-amber-700' },
    in_progress: { bg: 'bg-blue-50 ring-blue-200', dot: 'bg-blue-400', text: 'text-blue-700' },
    completed: { bg: 'bg-emerald-50 ring-emerald-200', dot: 'bg-emerald-400', text: 'text-emerald-700' },
};

const activityIcons: Record<string, { bg: string; color: string; icon: JSX.Element }> = {
    created: {
        bg: 'bg-emerald-100',
        color: 'text-emerald-600',
        icon: (
            <svg className="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2.5}>
                <path strokeLinecap="round" strokeLinejoin="round" d="M12 4v16m8-8H4" />
            </svg>
        ),
    },
    assigned: {
        bg: 'bg-indigo-100',
        color: 'text-indigo-600',
        icon: (
            <svg className="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2.5}>
                <path strokeLinecap="round" strokeLinejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0" />
            </svg>
        ),
    },
    reassigned: {
        bg: 'bg-purple-100',
        color: 'text-purple-600',
        icon: (
            <svg className="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2.5}>
                <path strokeLinecap="round" strokeLinejoin="round" d="M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5" />
            </svg>
        ),
    },
    status_changed: {
        bg: 'bg-blue-100',
        color: 'text-blue-600',
        icon: (
            <svg className="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2.5}>
                <path strokeLinecap="round" strokeLinejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182" />
            </svg>
        ),
    },
    updated: {
        bg: 'bg-amber-100',
        color: 'text-amber-600',
        icon: (
            <svg className="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2.5}>
                <path strokeLinecap="round" strokeLinejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z" />
            </svg>
        ),
    },
};

function Avatar({ name, size = 'md', color = 'indigo' }: { name: string; size?: 'sm' | 'md' | 'lg'; color?: string }) {
    const sizes = { sm: 'h-8 w-8 text-xs', md: 'h-10 w-10 text-sm', lg: 'h-12 w-12 text-base' };
    const colors: Record<string, string> = {
        indigo: 'bg-indigo-100 text-indigo-700',
        gray: 'bg-gray-100 text-gray-600',
        blue: 'bg-blue-100 text-blue-700',
    };
    return (
        <span className={`inline-flex items-center justify-center rounded-full font-semibold ${sizes[size]} ${colors[color]}`}>
            {name.split(' ').map(w => w[0]).join('').slice(0, 2).toUpperCase()}
        </span>
    );
}

export default function TaskShow({ task: { data: task }, activities: { data: activities }, users, statuses }: Props) {
    const currentUser = (usePage().props as any).auth.user;
    const [editing, setEditing] = useState(false);
    const [form, setForm] = useState({
        title: task.title,
        description: task.description ?? '',
        assigned_to: String(task.assignee.id),
    });
    const [errors, setErrors] = useState<Record<string, string[]>>({});
    const [submitting, setSubmitting] = useState(false);
    const [statusUpdating, setStatusUpdating] = useState(false);

    const handleUpdate = async (e: FormEvent) => {
        e.preventDefault();
        setSubmitting(true);
        setErrors({});
        try {
            await window.axios.put(`/api/tasks/${task.id}`, {
                ...form,
                assigned_to: Number(form.assigned_to),
            });
            setEditing(false);
            router.reload();
        } catch (err: any) {
            if (err.response?.status === 422) setErrors(err.response.data.errors);
        } finally {
            setSubmitting(false);
        }
    };

    const updateStatus = async (status: string) => {
        setStatusUpdating(true);
        try {
            await window.axios.patch(`/api/tasks/${task.id}/status`, { status });
            router.reload();
        } finally {
            setStatusUpdating(false);
        }
    };

    const cancelEdit = () => {
        setEditing(false);
        setForm({ title: task.title, description: task.description ?? '', assigned_to: String(task.assignee.id) });
        setErrors({});
    };

    const sc = statusConfig[task.status] || statusConfig.pending;

    const formatDate = (d: string) =>
        new Date(d).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit' });

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center gap-3">
                    <Link href="/dashboard" className="rounded-lg p-1.5 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600">
                        <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                            <path strokeLinecap="round" strokeLinejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                    </Link>
                    <h2 className="text-xl font-semibold text-gray-800">Task Details</h2>
                </div>
            }
        >
            <Head title={task.title} />

            <div className="py-8">
                <div className="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                    <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">

                        {/* Main content - left 2/3 */}
                        <div className="space-y-6 lg:col-span-2">
                            {/* Title & Description Card */}
                            <div className="rounded-2xl border border-gray-200 bg-white shadow-sm">
                                <div className="flex items-start justify-between border-b border-gray-100 px-6 py-4">
                                    <div className="flex items-center gap-3">
                                        <span className="rounded-lg bg-indigo-50 px-2.5 py-1 text-xs font-semibold text-indigo-600">
                                            #{task.id}
                                        </span>
                                        <div className={`flex items-center gap-1.5 rounded-full px-3 py-1 ring-1 ring-inset ${sc.bg} ${sc.text}`}>
                                            <span className={`h-1.5 w-1.5 rounded-full ${sc.dot}`} />
                                            <span className="text-xs font-semibold">{task.status_label}</span>
                                        </div>
                                    </div>
                                    {!editing && (
                                        <button
                                            onClick={() => setEditing(true)}
                                            className="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 px-3 py-1.5 text-xs font-medium text-gray-600 transition hover:border-gray-300 hover:bg-gray-50"
                                        >
                                            <svg className="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                                                <path strokeLinecap="round" strokeLinejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                                            </svg>
                                            Edit
                                        </button>
                                    )}
                                </div>

                                <div className="px-6 py-5">
                                    {editing ? (
                                        <form onSubmit={handleUpdate} className="space-y-5">
                                            <div>
                                                <label className="mb-1.5 block text-sm font-medium text-gray-700">Title</label>
                                                <input
                                                    type="text"
                                                    value={form.title}
                                                    onChange={(e) => setForm({ ...form, title: e.target.value })}
                                                    className="w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                    required
                                                />
                                                {errors.title && <p className="mt-1 text-xs text-red-500">{errors.title[0]}</p>}
                                            </div>
                                            <div>
                                                <label className="mb-1.5 block text-sm font-medium text-gray-700">Description</label>
                                                <textarea
                                                    value={form.description}
                                                    onChange={(e) => setForm({ ...form, description: e.target.value })}
                                                    className="w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                    rows={5}
                                                    placeholder="Add a description..."
                                                />
                                                {errors.description && <p className="mt-1 text-xs text-red-500">{errors.description[0]}</p>}
                                            </div>
                                            <div>
                                                <label className="mb-1.5 block text-sm font-medium text-gray-700">Assignee</label>
                                                <select
                                                    value={form.assigned_to}
                                                    onChange={(e) => setForm({ ...form, assigned_to: e.target.value })}
                                                    className="w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                    required
                                                >
                                                    {users.map((u) => (
                                                        <option key={u.id} value={u.id}>{u.name} ({u.email})</option>
                                                    ))}
                                                </select>
                                                {errors.assigned_to && <p className="mt-1 text-xs text-red-500">{errors.assigned_to[0]}</p>}
                                            </div>
                                            <div className="flex items-center gap-3 border-t border-gray-100 pt-4">
                                                <button
                                                    type="submit"
                                                    disabled={submitting}
                                                    className="rounded-lg bg-indigo-600 px-5 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-indigo-700 disabled:opacity-50"
                                                >
                                                    {submitting ? 'Saving...' : 'Save Changes'}
                                                </button>
                                                <button
                                                    type="button"
                                                    onClick={cancelEdit}
                                                    className="rounded-lg px-4 py-2 text-sm font-medium text-gray-500 transition hover:text-gray-700"
                                                >
                                                    Cancel
                                                </button>
                                            </div>
                                        </form>
                                    ) : (
                                        <>
                                            <h1 className="text-xl font-bold text-gray-900">{task.title}</h1>
                                            <div className="mt-4">
                                                {task.description ? (
                                                    <p className="whitespace-pre-wrap leading-relaxed text-gray-600">{task.description}</p>
                                                ) : (
                                                    <p className="italic text-gray-400">No description provided.</p>
                                                )}
                                            </div>
                                        </>
                                    )}
                                </div>
                            </div>

                            {/* Activity Timeline */}
                            <div className="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                                <h3 className="mb-5 text-sm font-semibold uppercase tracking-wider text-gray-400">Activity</h3>
                                {activities.length === 0 ? (
                                    <p className="text-sm text-gray-400 italic">No activity recorded yet.</p>
                                ) : (
                                    <div className="relative">
                                        <div className="absolute left-[15px] top-2 h-[calc(100%-16px)] w-px bg-gray-200" />
                                        <div className="space-y-0">
                                            {activities.map((activity, idx) => {
                                                const iconCfg = activityIcons[activity.type] || activityIcons.updated;
                                                const isLast = idx === activities.length - 1;
                                                return (
                                                    <div key={activity.id} className={`relative flex gap-4 ${isLast ? '' : 'pb-5'}`}>
                                                        <span
                                                            className={`z-10 flex h-8 w-8 shrink-0 items-center justify-center rounded-full ring-4 ring-white ${iconCfg.bg} ${iconCfg.color}`}
                                                        >
                                                            {iconCfg.icon}
                                                        </span>
                                                        <div className="min-w-0 flex-1 pt-0.5">
                                                            <p className="text-sm text-gray-700">
                                                                <span className="font-semibold text-gray-900">{activity.user.name}</span>
                                                                {' '}
                                                                {activity.description}
                                                            </p>
                                                            <p className="mt-0.5 text-xs text-gray-400">{activity.created_at_human}</p>
                                                        </div>
                                                    </div>
                                                );
                                            })}
                                        </div>
                                    </div>
                                )}
                            </div>
                        </div>

                        {/* Sidebar - right 1/3 */}
                        <div className="space-y-6">
                            {/* Status Card */}
                            <div className="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                                <h3 className="mb-3 text-xs font-semibold uppercase tracking-wider text-gray-400">Status</h3>
                                <div className="flex flex-wrap gap-2">
                                    {statuses.map((s) => {
                                        const active = task.status === s.value;
                                        const cfg = statusConfig[s.value] || statusConfig.pending;
                                        return (
                                            <button
                                                key={s.value}
                                                onClick={() => !active && updateStatus(s.value)}
                                                disabled={statusUpdating || active}
                                                className={`inline-flex items-center gap-1.5 rounded-full px-3.5 py-1.5 text-xs font-semibold ring-1 ring-inset transition disabled:cursor-default ${
                                                    active
                                                        ? `${cfg.bg} ${cfg.text}`
                                                        : 'bg-white text-gray-500 ring-gray-200 hover:bg-gray-50'
                                                }`}
                                            >
                                                <span className={`h-1.5 w-1.5 rounded-full ${active ? cfg.dot : 'bg-gray-300'}`} />
                                                {s.label}
                                            </button>
                                        );
                                    })}
                                </div>
                            </div>

                            {/* Assignee Card */}
                            <div className="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                                <h3 className="mb-3 text-xs font-semibold uppercase tracking-wider text-gray-400">Assignee</h3>
                                <div className="flex items-center gap-3">
                                    <Avatar name={task.assignee.name} color="indigo" />
                                    <div className="min-w-0">
                                        <p className="truncate text-sm font-semibold text-gray-900">{task.assignee.name}</p>
                                        <p className="truncate text-xs text-gray-400">{task.assignee.email}</p>
                                    </div>
                                </div>
                            </div>

                            {/* Creator Card */}
                            <div className="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                                <h3 className="mb-3 text-xs font-semibold uppercase tracking-wider text-gray-400">Created by</h3>
                                <div className="flex items-center gap-3">
                                    <Avatar name={task.creator.name} color="gray" />
                                    <div className="min-w-0">
                                        <p className="truncate text-sm font-semibold text-gray-900">{task.creator.name}</p>
                                        <p className="truncate text-xs text-gray-400">{task.creator.email}</p>
                                    </div>
                                </div>
                            </div>

                            {/* Dates Card */}
                            <div className="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                                <h3 className="mb-3 text-xs font-semibold uppercase tracking-wider text-gray-400">Dates</h3>
                                <div className="space-y-3">
                                    <div className="flex items-center gap-2.5">
                                        <svg className="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.5}>
                                            <path strokeLinecap="round" strokeLinejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                                        </svg>
                                        <div>
                                            <p className="text-xs text-gray-400">Created</p>
                                            <p className="text-sm font-medium text-gray-700">{formatDate(task.created_at)}</p>
                                        </div>
                                    </div>
                                    <div className="flex items-center gap-2.5">
                                        <svg className="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.5}>
                                            <path strokeLinecap="round" strokeLinejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <div>
                                            <p className="text-xs text-gray-400">Updated</p>
                                            <p className="text-sm font-medium text-gray-700">{formatDate(task.updated_at)}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
