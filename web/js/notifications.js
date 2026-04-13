/**
 * Notification System
 * Handles notification panel, badge, and interactions
 */
class NotificationSystem {
    constructor() {
        this.unreadCount = 0;
        this.notifications = [];
        this.isOpen = false;
        this.init();
    }

    init() {
        this.createNotificationPanel();
        this.setupEventListeners();
        this.updateUnreadCount();
        
        // Poll for new notifications every 30 seconds
        setInterval(() => this.updateUnreadCount(), 30000);
    }

    createNotificationPanel() {
        const panel = document.createElement('div');
        panel.id = 'notification-panel';
        panel.className = 'fixed top-20 right-8 w-[420px] max-h-[600px] bg-surface-container-lowest rounded-2xl shadow-[0_24px_48px_rgba(11,28,48,0.12)] border border-outline-variant/20 z-[60] opacity-0 invisible transition-all duration-200 transform translate-y-2';
        
        panel.innerHTML = `
            <!-- Panel Header -->
            <div class="flex items-center justify-between p-4 border-b border-outline-variant/20">
                <h3 class="text-lg font-bold font-headline text-on-surface">Notifications</h3>
                <div class="flex items-center gap-2">
                    <button id="mark-all-read-btn" class="text-xs font-semibold text-primary-container hover:underline" title="Mark all as read">
                        Mark all read
                    </button>
                    <button id="close-panel-btn" class="material-symbols-outlined text-on-surface-variant hover:bg-surface-container rounded-full p-1 transition-colors">
                        close
                    </button>
                </div>
            </div>
            
            <!-- Notifications List -->
            <div id="notifications-list" class="overflow-y-auto max-h-[520px] p-3 space-y-2">
                <div class="flex items-center justify-center py-8">
                    <div class="text-center">
                        <span class="material-symbols-outlined text-4xl text-outline-variant mb-2">notifications_none</span>
                        <p class="text-sm text-outline-variant">No notifications yet</p>
                    </div>
                </div>
            </div>
        `;
        
        document.body.appendChild(panel);
    }

    setupEventListeners() {
        // Notification button click
        const notifBtn = document.querySelector('.notification-button');
        if (notifBtn) {
            notifBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.togglePanel();
            });
        }

        // Close panel button
        const closeBtn = document.getElementById('close-panel-btn');
        if (closeBtn) {
            closeBtn.addEventListener('click', () => this.closePanel());
        }

        // Mark all as read button
        const markAllBtn = document.getElementById('mark-all-read-btn');
        if (markAllBtn) {
            markAllBtn.addEventListener('click', () => this.markAllAsRead());
        }

        // Close panel when clicking outside
        document.addEventListener('click', (e) => {
            if (this.isOpen && 
                !document.getElementById('notification-panel').contains(e.target) && 
                !e.target.closest('.notification-button')) {
                this.closePanel();
            }
        });
    }

    togglePanel() {
        if (this.isOpen) {
            this.closePanel();
        } else {
            this.openPanel();
        }
    }

    openPanel() {
        const panel = document.getElementById('notification-panel');
        panel.classList.remove('opacity-0', 'invisible', 'translate-y-2');
        panel.classList.add('opacity-100', 'visible', 'translate-y-0');
        this.isOpen = true;
        
        // Load notifications
        this.loadNotifications();
    }

    closePanel() {
        const panel = document.getElementById('notification-panel');
        panel.classList.add('opacity-0', 'invisible', 'translate-y-2');
        panel.classList.remove('opacity-100', 'visible', 'translate-y-0');
        this.isOpen = false;
    }

    async updateUnreadCount() {
        try {
            const response = await fetch('/api/notification/count');
            const data = await response.json();
            
            if (data.success) {
                this.unreadCount = data.count;
                this.updateBadge();
            }
        } catch (error) {
            console.error('Error fetching unread count:', error);
        }
    }

    updateBadge() {
        // Remove existing badge
        const existingBadge = document.querySelector('.notification-badge');
        if (existingBadge) {
            existingBadge.remove();
        }

        // Add new badge if there are unread notifications
        if (this.unreadCount > 0) {
            const notifBtn = document.querySelector('.notification-button');
            if (notifBtn) {
                const badge = document.createElement('span');
                badge.className = 'notification-badge absolute -top-1 -right-1 bg-error text-white text-xs font-bold rounded-full w-5 h-5 flex items-center justify-center';
                badge.textContent = this.unreadCount > 99 ? '99+' : this.unreadCount;
                notifBtn.style.position = 'relative';
                notifBtn.appendChild(badge);
            }
        }
    }

    async loadNotifications() {
        try {
            const response = await fetch('/api/notification/list?limit=20');
            const data = await response.json();
            
            if (data.success) {
                this.notifications = data.notifications;
                this.renderNotifications();
            }
        } catch (error) {
            console.error('Error loading notifications:', error);
            this.showError();
        }
    }

    renderNotifications() {
        const list = document.getElementById('notifications-list');
        
        if (this.notifications.length === 0) {
            list.innerHTML = `
                <div class="flex items-center justify-center py-8">
                    <div class="text-center">
                        <span class="material-symbols-outlined text-4xl text-outline-variant mb-2">notifications_none</span>
                        <p class="text-sm text-outline-variant">No notifications yet</p>
                    </div>
                </div>
            `;
            return;
        }

        list.innerHTML = this.notifications.map(notif => this.renderNotificationItem(notif)).join('');
        
        // Add event listeners to action buttons
        this.notifications.forEach(notif => {
            // Delete button
            const deleteBtn = document.querySelector(`[data-delete-id="${notif.id}"]`);
            if (deleteBtn) {
                deleteBtn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    this.deleteNotification(notif.id);
                });
            }
            
            // Action button (View, Chat, etc.)
            const actionBtn = document.querySelector(`[data-action-id="${notif.id}"]`);
            if (actionBtn && notif.action_url) {
                actionBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    this.markAsRead(notif.id);
                    window.location.href = notif.action_url;
                });
            }
            
            // Mark as read on click
            const card = document.querySelector(`[data-notif-id="${notif.id}"]`);
            if (card) {
                card.addEventListener('click', () => {
                    if (!notif.is_read) {
                        this.markAsRead(notif.id);
                    }
                });
            }
        });
    }

    renderNotificationItem(notif) {
        const iconHtml = this.getIconHtml(notif);
        const actionBtnHtml = notif.action_text && notif.action_url 
            ? `<button data-action-id="${notif.id}" class="mt-3 w-full bg-primary-container text-white px-4 py-2 rounded-lg font-semibold text-sm hover:opacity-90 transition-opacity">${notif.action_text}</button>`
            : '';
        
        return `
            <div data-notif-id="${notif.id}" class="bg-surface-container rounded-xl p-4 ${!notif.is_read ? 'ring-2 ring-primary-container/30' : ''} hover:shadow-md transition-all cursor-pointer">
                <div class="flex items-start gap-3">
                    ${iconHtml}
                    <div class="flex-1 min-w-0">
                        <div class="flex items-start justify-between gap-2">
                            <div class="flex-1">
                                <p class="text-sm font-semibold text-on-surface leading-tight">${this.escapeHtml(notif.title)}</p>
                                <p class="text-xs text-on-surface-variant mt-1 leading-relaxed">${this.escapeHtml(notif.message)}</p>
                            </div>
                            <div class="flex items-center gap-1 flex-shrink-0">
                                <span class="text-xs text-outline-variant whitespace-nowrap">${notif.relative_time}</span>
                                <button data-delete-id="${notif.id}" class="material-symbols-outlined text-on-surface-variant hover:text-error hover:bg-surface-container-high rounded-full p-1 transition-colors" title="Delete">
                                    more_vert
                                </button>
                            </div>
                        </div>
                        ${actionBtnHtml}
                    </div>
                </div>
            </div>
        `;
    }

    getIconHtml(notif) {
        if (notif.icon) {
            // Check if it's a URL
            if (notif.icon.startsWith('http') || notif.icon.startsWith('data:')) {
                return `
                    <div class="w-10 h-10 rounded-lg bg-surface-container-high flex-shrink-0 overflow-hidden">
                        <img src="${this.escapeHtml(notif.icon)}" alt="" class="w-full h-full object-cover" />
                    </div>
                `;
            }
            // Material icon
            return `
                <div class="w-10 h-10 rounded-lg bg-surface-container-high flex items-center justify-center flex-shrink-0">
                    <span class="material-symbols-outlined text-xl text-primary-container">${notif.icon}</span>
                </div>
            `;
        }
        
        // Default icon based on type
        const defaultIcons = {
            'info': 'info',
            'success': 'check_circle',
            'warning': 'warning',
            'error': 'error'
        };
        const icon = defaultIcons[notif.type] || 'notifications';
        
        return `
            <div class="w-10 h-10 rounded-lg bg-surface-container-high flex items-center justify-center flex-shrink-0">
                <span class="material-symbols-outlined text-xl text-primary-container">${icon}</span>
            </div>
        `;
    }

    showError() {
        const list = document.getElementById('notifications-list');
        list.innerHTML = `
            <div class="flex items-center justify-center py-8">
                <div class="text-center">
                    <span class="material-symbols-outlined text-4xl text-error mb-2">error_outline</span>
                    <p class="text-sm text-error">Failed to load notifications</p>
                </div>
            </div>
        `;
    }

    async markAsRead(id) {
        try {
            await fetch('/api/notification/mark-read', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ id })
            });
            
            this.updateUnreadCount();
        } catch (error) {
            console.error('Error marking notification as read:', error);
        }
    }

    async markAllAsRead() {
        try {
            await fetch('/api/notification/mark-all-read', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            });
            
            this.updateUnreadCount();
            this.loadNotifications();
            
            // Show success notification
            if (window.notify) {
                window.notify('All notifications marked as read', 'success');
            }
        } catch (error) {
            console.error('Error marking all as read:', error);
        }
    }

    async deleteNotification(id) {
        try {
            await fetch('/api/notification/delete', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ id })
            });
            
            this.updateUnreadCount();
            this.loadNotifications();
        } catch (error) {
            console.error('Error deleting notification:', error);
        }
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// Initialize notification system when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    window.notificationSystem = new NotificationSystem();
});
