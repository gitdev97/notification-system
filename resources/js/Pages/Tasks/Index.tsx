import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Task, TaskStatus, User } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { FormEvent, useCallback, useEffect, useRef, useState } from 'react';

interface Props {
    tasks: {
        data: Task[];
        meta: { current_page: number; last_page: number; total: number };
        links: { prev: string | null; next: string | null };
    };
    filters: { search?: string; status?: string; assigned_to?: string };
    users: User[];
    statuses: TaskStatus[];
}

const statusColors: Record<string, string> = {
    pending: 'bg-yellow-100 text-yellow-800',
    in_progress: 'bg-blue-100 text-blue-800',
    completed: 'bg-green-100 text-green-800',
};

export default function TasksIndex({ tasks, filters, users, statuses }: Props) {
    const [showModal, setShowModal] = useState(false);
    const [form, setForm] = useState({ title: '', description: '', assigned_to: '' });
    const [errors, setErrors] = useState<Record<string, string[]>>({});
    const [submitting, setSubmitting] = useState(false);

    const [search, setSearch] = useState(filters.search ?? '');
    const [statusFilter, setStatusFilter] = useState(filters.status ?? '');
    const [assigneeFilter, setAssigneeFilter] = useState(filters.assigned_to ?? '');
    const debounceRef = useRef<ReturnType<typeof setTimeout>>();

    const applyFilters = useCallback((overrides: Record<string, string> = {}) => {
        const params: Record<string, string> = {
            search: overrides.search ?? search,
            status: overrides.status ?? statusFilter,
            assigned_to: overrides.assigned_to ?? assigneeFilter,
        };

        Object.keys(params).forEach((k) => {
            if (!params[k]) delete params[k];
        });

        router.get('/dashboard', params, { preserveState: true, replace: true });
    }, [search, statusFilter, assigneeFilter]);

    const handleSearchChange = (value: string) => {
        setSearch(value);
        if (debounceRef.current) clearTimeout(debounceRef.current);
        debounceRef.current = setTimeout(() => applyFilters({ search: value }), 350);
    };

    useEffect(() => {
        return () => { if (debounceRef.current) clearTimeout(debounceRef.current); };
    }, []);

    const handleStatusFilter = (value: string) => {
        setStatusFilter(value);
        applyFilters({ status: value });
    };

    const handleAssigneeFilter = (value: string) => {
        setAssigneeFilter(value);
        applyFilters({ assigned_to: value });
    };

    const clearFilters = () => {
        setSearch('');
        setStatusFilter('');
        setAssigneeFilter('');
        router.get('/dashboard', {}, { preserveState: true, replace: true });
    };

    const hasFilters = search || statusFilter || assigneeFilter;

    const handleCreate = async (e: FormEvent) => {
        e.preventDefault();
        setSubmitting(true);
        setErrors({});

        try {
            await window.axios.post('/api/tasks', {
                ...form,
                assigned_to: Number(form.assigned_to),
            });
            setForm({ title: '', description: '', assigned_to: '' });
            setShowModal(false);
            router.reload();
        } catch (err: any) {
            if (err.response?.status === 422) {
                setErrors(err.response.data.errors);
            }
        } finally {
            setSubmitting(false);
        }
    };

    const updateStatus = async (taskId: number, status: string) => {
        try {
            await window.axios.patch(`/api/tasks/${taskId}/status`, { status });
            router.reload();
        } catch {
            // silently handle
        }
    };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between">
                    <h2 className="text-xl font-semibold leading-tight text-gray-800">Task Management</h2>
                    <button
                        onClick={() => setShowModal(true)}
                        className="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                    >
                        <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                            <path strokeLinecap="round" strokeLinejoin="round" d="M12 4v16m8-8H4" />
                        </svg>
                        New Task
                    </button>
                </div>
            }
        >
            <Head title="Tasks" />

            <div className="py-8">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    {/* Filters */}
                    <div className="mb-6 rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                        <div className="flex flex-wrap items-end gap-4">
                            <div className="min-w-0 flex-1">
                                <label className="mb-1 block text-xs font-medium text-gray-500">Search</label>
                                <div className="relative">
                                    <svg className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                                        <path strokeLinecap="round" strokeLinejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                    <input
                                        type="text"
                                        value={search}
                                        onChange={(e) => handleSearchChange(e.target.value)}
                                        placeholder="Search by title..."
                                        className="w-full rounded-lg border border-gray-300 py-2 pl-9 pr-3 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    />
                                </div>
                            </div>

                            <div className="w-44">
                                <label className="mb-1 block text-xs font-medium text-gray-500">Status</label>
                                <select
                                    value={statusFilter}
                                    onChange={(e) => handleStatusFilter(e.target.value)}
                                    className="w-full rounded-lg border border-gray-300 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                >
                                    <option value="">All Statuses</option>
                                    {statuses.map((s) => (
                                        <option key={s.value} value={s.value}>{s.label}</option>
                                    ))}
                                </select>
                            </div>

                            <div className="w-52">
                                <label className="mb-1 block text-xs font-medium text-gray-500">Assignee</label>
                                <select
                                    value={assigneeFilter}
                                    onChange={(e) => handleAssigneeFilter(e.target.value)}
                                    className="w-full rounded-lg border border-gray-300 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                >
                                    <option value="">All Assignees</option>
                                    {users.map((u) => (
                                        <option key={u.id} value={u.id}>{u.name}</option>
                                    ))}
                                </select>
                            </div>

                            {hasFilters && (
                                <button
                                    onClick={clearFilters}
                                    className="inline-flex items-center gap-1 rounded-lg border border-gray-300 px-3 py-2 text-sm font-medium text-gray-600 transition hover:bg-gray-50"
                                >
                                    <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                                        <path strokeLinecap="round" strokeLinejoin="round" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                    Clear
                                </button>
                            )}
                        </div>
                    </div>

                    {/* Task Table */}
                    <div className="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                        <table className="min-w-full divide-y divide-gray-200">
                            <thead className="bg-gray-50">
                                <tr>
                                    <th className="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Task</th>
                                    <th className="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Assignee</th>
                                    <th className="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Creator</th>
                                    <th className="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Status</th>
                                    <th className="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Created</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-200">
                                {tasks.data.length === 0 ? (
                                    <tr>
                                        <td colSpan={5} className="px-6 py-12 text-center text-sm text-gray-400">
                                            {hasFilters ? 'No tasks match your filters.' : 'No tasks yet. Create one to get started.'}
                                        </td>
                                    </tr>
                                ) : (
                                    tasks.data.map((task) => (
                                        <tr key={task.id} className="transition hover:bg-gray-50">
                                            <td className="px-6 py-4">
                                                <Link href={`/tasks/${task.id}`} className="text-sm font-medium text-indigo-600 hover:text-indigo-800 hover:underline">
                                                    {task.title}
                                                </Link>
                                                {task.description && (
                                                    <div className="mt-0.5 max-w-xs truncate text-xs text-gray-500">{task.description}</div>
                                                )}
                                            </td>
                                            <td className="whitespace-nowrap px-6 py-4">
                                                <div className="flex items-center gap-2">
                                                    <span className="flex h-7 w-7 items-center justify-center rounded-full bg-indigo-100 text-xs font-semibold text-indigo-700">
                                                        {task.assignee.name.charAt(0)}
                                                    </span>
                                                    <span className="text-sm text-gray-700">{task.assignee.name}</span>
                                                </div>
                                            </td>
                                            <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-500">{task.creator.name}</td>
                                            <td className="whitespace-nowrap px-6 py-4">
                                                <select
                                                    value={task.status}
                                                    onChange={(e) => updateStatus(task.id, e.target.value)}
                                                    className={`rounded-full border-0 px-3 py-1 text-xs font-semibold focus:ring-2 focus:ring-indigo-500 ${statusColors[task.status] || 'bg-gray-100 text-gray-800'}`}
                                                >
                                                    {statuses.map((s) => (
                                                        <option key={s.value} value={s.value}>{s.label}</option>
                                                    ))}
                                                </select>
                                            </td>
                                            <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-400">
                                                {new Date(task.created_at).toLocaleDateString()}
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>

                        {/* Pagination */}
                        {tasks.meta.last_page > 1 && (
                            <div className="flex items-center justify-between border-t px-6 py-3">
                                <p className="text-sm text-gray-500">
                                    Page {tasks.meta.current_page} of {tasks.meta.last_page} ({tasks.meta.total} tasks)
                                </p>
                                <div className="flex gap-2">
                                    {tasks.links.prev && (
                                        <button
                                            onClick={() => router.visit(tasks.links.prev!)}
                                            className="rounded-lg border px-3 py-1 text-sm text-gray-600 hover:bg-gray-50"
                                        >
                                            Previous
                                        </button>
                                    )}
                                    {tasks.links.next && (
                                        <button
                                            onClick={() => router.visit(tasks.links.next!)}
                                            className="rounded-lg border px-3 py-1 text-sm text-gray-600 hover:bg-gray-50"
                                        >
                                            Next
                                        </button>
                                    )}
                                </div>
                            </div>
                        )}
                    </div>
                </div>
            </div>

            {/* Create Task Modal */}
            {showModal && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
                    <div className="w-full max-w-lg rounded-2xl bg-white p-6 shadow-2xl">
                        <div className="mb-5 flex items-center justify-between">
                            <h3 className="text-lg font-semibold text-gray-900">Create New Task</h3>
                            <button onClick={() => setShowModal(false)} className="text-gray-400 hover:text-gray-600">
                                <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <form onSubmit={handleCreate} className="space-y-4">
                            <div>
                                <label className="mb-1 block text-sm font-medium text-gray-700">Title</label>
                                <input
                                    type="text"
                                    value={form.title}
                                    onChange={(e) => setForm({ ...form, title: e.target.value })}
                                    className="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    placeholder="e.g. Build REST API"
                                    required
                                />
                                {errors.title && <p className="mt-1 text-xs text-red-600">{errors.title[0]}</p>}
                            </div>

                            <div>
                                <label className="mb-1 block text-sm font-medium text-gray-700">Description</label>
                                <textarea
                                    value={form.description}
                                    onChange={(e) => setForm({ ...form, description: e.target.value })}
                                    className="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    rows={3}
                                    placeholder="Describe the task..."
                                />
                                {errors.description && <p className="mt-1 text-xs text-red-600">{errors.description[0]}</p>}
                            </div>

                            <div>
                                <label className="mb-1 block text-sm font-medium text-gray-700">Assign To</label>
                                <select
                                    value={form.assigned_to}
                                    onChange={(e) => setForm({ ...form, assigned_to: e.target.value })}
                                    className="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    required
                                >
                                    <option value="">Select a user...</option>
                                    {users.map((u) => (
                                        <option key={u.id} value={u.id}>{u.name} ({u.email})</option>
                                    ))}
                                </select>
                                {errors.assigned_to && <p className="mt-1 text-xs text-red-600">{errors.assigned_to[0]}</p>}
                            </div>

                            <div className="flex justify-end gap-3 pt-2">
                                <button
                                    type="button"
                                    onClick={() => setShowModal(false)}
                                    className="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                                >
                                    Cancel
                                </button>
                                <button
                                    type="submit"
                                    disabled={submitting}
                                    className="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 disabled:opacity-50"
                                >
                                    {submitting ? 'Creating...' : 'Create Task'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </AuthenticatedLayout>
    );
}
