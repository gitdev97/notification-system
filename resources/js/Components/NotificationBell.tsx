import { Notification } from '@/types';
import { router, usePage } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';

export default function NotificationBell() {
    const { auth, notifications: sharedNotifications } = usePage().props as any;
    const [open, setOpen] = useState(false);
    const [notifications, setNotifications] = useState<Notification[]>([]);
    const [unreadCount, setUnreadCount] = useState(sharedNotifications?.unread_count ?? 0);
    const [loading, setLoading] = useState(false);
    const dropdownRef = useRef<HTMLDivElement>(null);

    useEffect(() => {
        setUnreadCount(sharedNotifications?.unread_count ?? 0);
    }, [sharedNotifications]);

    // Fetch actual unread count on mount to ensure badge is always accurate
    useEffect(() => {
        if (!auth?.user) return;
        window.axios.get('/api/notifications/unread-count').then((res: any) => {
            setUnreadCount(res.data.unread_count);
        }).catch(() => {});
    }, [auth?.user]);

    useEffect(() => {
        function handleClickOutside(e: MouseEvent) {
            if (dropdownRef.current && !dropdownRef.current.contains(e.target as Node)) {
                setOpen(false);
            }
        }
        document.addEventListener('mousedown', handleClickOutside);
        return () => document.removeEventListener('mousedown', handleClickOutside);
    }, []);

    useEffect(() => {
        if (!auth?.user) return;

        const channel = window.Echo?.private(`user.${auth.user.id}`);
        if (!channel) return;

        channel.listen('.notification.created', (e: { notification: Notification }) => {
            setNotifications((prev) => [e.notification, ...prev]);
            setUnreadCount((c: number) => c + 1);
            router.reload({ only: ['tasks', 'task', 'activities'] });
        });

        return () => {
            channel.stopListening('.notification.created');
        };
    }, [auth?.user]);

    const fetchNotifications = async () => {
        if (loading) return;
        setLoading(true);
        try {
            const res = await window.axios.get('/api/notifications');
            setNotifications(res.data.data);
        } finally {
            setLoading(false);
        }
    };

    const toggleDropdown = () => {
        const next = !open;
        setOpen(next);
        if (next) fetchNotifications();
    };

    const handleNotificationClick = async (n: Notification) => {
        if (!n.is_read) {
            await window.axios.post(`/api/notifications/${n.id}/read`);
            setNotifications((prev) =>
                prev.map((item) => (item.id === n.id ? { ...item, is_read: true, read_at: new Date().toISOString() } : item)),
            );
            setUnreadCount((c: number) => Math.max(0, c - 1));
        }

        const taskId = (n.data as any)?.task_id;
        if (taskId) {
            setOpen(false);
            router.visit(`/tasks/${taskId}`);
        }
    };

    const markAllAsRead = async () => {
        await window.axios.post('/api/notifications/mark-all-read');
        setNotifications((prev) => prev.map((n) => ({ ...n, is_read: true, read_at: new Date().toISOString() })));
        setUnreadCount(0);
    };

    const timeAgo = (dateStr: string) => {
        const seconds = Math.floor((Date.now() - new Date(dateStr).getTime()) / 1000);
        if (seconds < 60) return 'just now';
        if (seconds < 3600) return `${Math.floor(seconds / 60)}m ago`;
        if (seconds < 86400) return `${Math.floor(seconds / 3600)}h ago`;
        return `${Math.floor(seconds / 86400)}d ago`;
    };

    const typeIcon = (type: string) => {
        switch (type) {
            case 'task_assigned':
                return (
                    <span className="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-blue-100 text-blue-600">
                        <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                            <path strokeLinecap="round" strokeLinejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                    </span>
                );
            case 'task_completed':
                return (
                    <span className="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-green-100 text-green-600">
                        <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                            <path strokeLinecap="round" strokeLinejoin="round" d="M5 13l4 4L19 7" />
                        </svg>
                    </span>
                );
            case 'task_status_changed':
                return (
                    <span className="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-amber-100 text-amber-600">
                        <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                            <path strokeLinecap="round" strokeLinejoin="round" d="M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5" />
                        </svg>
                    </span>
                );
            case 'task_updated':
                return (
                    <span className="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-purple-100 text-purple-600">
                        <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                            <path strokeLinecap="round" strokeLinejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z" />
                        </svg>
                    </span>
                );
            default:
                return (
                    <span className="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-gray-100 text-gray-600">
                        <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                            <path strokeLinecap="round" strokeLinejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                        </svg>
                    </span>
                );
        }
    };

    return (
        <div className="relative" ref={dropdownRef}>
            <button
                onClick={toggleDropdown}
                className="relative rounded-full p-2 text-gray-500 transition hover:bg-gray-100 hover:text-gray-700 focus:outline-none"
            >
                <svg className="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.5}>
                    <path strokeLinecap="round" strokeLinejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
                </svg>
                {unreadCount > 0 && (
                    <span className="absolute -right-0.5 -top-0.5 flex h-5 w-5 animate-pulse items-center justify-center rounded-full bg-red-500 text-[10px] font-bold text-white">
                        {unreadCount > 9 ? '9+' : unreadCount}
                    </span>
                )}
            </button>

            {open && (
                <div className="absolute right-0 z-50 mt-2 w-96 rounded-xl border border-gray-200 bg-white shadow-xl">
                    <div className="flex items-center justify-between border-b px-4 py-3">
                        <h3 className="text-sm font-semibold text-gray-900">Notifications</h3>
                        {unreadCount > 0 && (
                            <button
                                onClick={markAllAsRead}
                                className="text-xs font-medium text-indigo-600 hover:text-indigo-800"
                            >
                                Mark all as read
                            </button>
                        )}
                    </div>

                    <div className="max-h-96 overflow-y-auto">
                        {loading && notifications.length === 0 ? (
                            <div className="px-4 py-8 text-center text-sm text-gray-400">Loading...</div>
                        ) : notifications.length === 0 ? (
                            <div className="px-4 py-8 text-center text-sm text-gray-400">No notifications yet</div>
                        ) : (
                            notifications.map((n) => (
                                <div
                                    key={n.id}
                                    className={`flex cursor-pointer items-start gap-3 border-b px-4 py-3 transition last:border-0 hover:bg-gray-50 ${
                                        !n.is_read ? 'bg-indigo-50/40' : ''
                                    }`}
                                    onClick={() => handleNotificationClick(n)}
                                >
                                    {typeIcon(n.type)}
                                    <div className="min-w-0 flex-1">
                                        <p className={`text-sm ${!n.is_read ? 'font-medium text-gray-900' : 'text-gray-600'}`}>
                                            {n.message}
                                        </p>
                                        <div className="mt-0.5 flex items-center gap-2">
                                            <span className="text-xs text-gray-400">{timeAgo(n.created_at)}</span>
                                            {(n.data as any)?.task_id && (
                                                <span className="text-xs font-medium text-indigo-500">View task →</span>
                                            )}
                                        </div>
                                    </div>
                                    {!n.is_read && (
                                        <span className="mt-1.5 h-2 w-2 flex-shrink-0 rounded-full bg-indigo-500" />
                                    )}
                                </div>
                            ))
                        )}
                    </div>
                </div>
            )}
        </div>
    );
}
