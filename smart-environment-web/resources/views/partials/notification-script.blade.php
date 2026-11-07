<script>
// Notification Bell Handler
document.addEventListener('DOMContentLoaded', function() {
    // Function to load notifications
    function loadNotifications() {
        fetch('{{ route("admin.notifications.unread") }}')
            .then(response => response.json())
            .then(data => {
                updateNotificationBadge(data.unread_count);
                updateNotificationDropdown(data.notifications);
            })
            .catch(error => console.error('Error loading notifications:', error));
    }

    // Update notification badge count
    function updateNotificationBadge(count) {
        const badge = document.getElementById('notification-badge');
        if (badge) {
            if (count > 0) {
                badge.classList.remove('hidden');
            } else {
                badge.classList.add('hidden');
            }
        }
    }

    // Update notification dropdown content
    function updateNotificationDropdown(notifications) {
        const dropdownContent = document.querySelector('.notification-dropdown-content');
        if (!dropdownContent) return;

        if (notifications.length === 0) {
            dropdownContent.innerHTML = `
                <div class="px-4 py-8 text-center text-sm text-gray-500">
                    Tidak ada notifikasi baru
                </div>
            `;
            return;
        }

        let html = '';
        notifications.forEach(notification => {
            const data = notification.data;
            const icon = getNotificationIcon(data.icon);
            const color = getNotificationColor(data.color);
            
            html += `
                <a href="${data.url || '#'}" 
                   class="flex items-start gap-3 border-b border-gray-200 px-4 py-3 hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-800"
                   onclick="markAsRead('${notification.id}')">
                    <div class="flex-shrink-0 mt-1">
                        <div class="w-8 h-8 rounded-full ${color} flex items-center justify-center">
                            ${icon}
                        </div>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold text-black dark:text-white">
                            ${data.title}
                        </p>
                        <p class="text-xs text-gray-600 dark:text-gray-400 mt-0.5">
                            ${data.message}
                        </p>
                        <p class="text-xs text-gray-500 mt-1">
                            ${timeAgo(notification.created_at)}
                        </p>
                    </div>
                    <span class="flex-shrink-0 w-2 h-2 bg-blue-600 rounded-full mt-2"></span>
                </a>
            `;
        });

        dropdownContent.innerHTML = html;
    }

    // Get icon HTML based on icon type
    function getNotificationIcon(iconType) {
        const icons = {
            'user-plus': '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" /></svg>',
            'user-x': '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7a4 4 0 11-8 0 4 4 0 018 0zM9 14a6 6 0 00-6 6v1h12v-1a6 6 0 00-6-6zM21 12h-6" /></svg>',
            'map-pin': '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg>',
        };
        return icons[iconType] || '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" /></svg>';
    }

    // Get color classes based on color type
    function getNotificationColor(color) {
        const colors = {
            'blue': 'bg-blue-100 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400',
            'green': 'bg-green-100 text-green-600 dark:bg-green-900/30 dark:text-green-400',
            'red': 'bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400',
            'yellow': 'bg-yellow-100 text-yellow-600 dark:bg-yellow-900/30 dark:text-yellow-400',
        };
        return colors[color] || colors['blue'];
    }

    // Time ago helper
    function timeAgo(dateString) {
        const date = new Date(dateString);
        const now = new Date();
        const seconds = Math.floor((now - date) / 1000);

        const intervals = {
            tahun: 31536000,
            bulan: 2592000,
            minggu: 604800,
            hari: 86400,
            jam: 3600,
            menit: 60,
            detik: 1
        };

        for (const [name, secondsInInterval] of Object.entries(intervals)) {
            const interval = Math.floor(seconds / secondsInInterval);
            if (interval >= 1) {
                return `${interval} ${name} yang lalu`;
            }
        }
        return 'Baru saja';
    }

    // Mark notification as read
    window.markAsRead = function(notificationId) {
        fetch(`/admin/notifications/${notificationId}/read`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                loadNotifications();
            }
        })
        .catch(error => console.error('Error marking notification as read:', error));
    };

    // Load notifications on page load
    loadNotifications();

    // Refresh notifications every 30 seconds
    setInterval(loadNotifications, 30000);
});
</script>
