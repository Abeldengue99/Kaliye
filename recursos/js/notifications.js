// Sistema de NotificaÃ§Ãµes em Tempo Real - Aksanti ReferÃªncias
class NotificationSystem {
    constructor() {
        this.pollInterval = 30000; // 30 segundos
        this.notificationCount = 0;
        this.init();
    }

    init() {
        this.createNotificationBell();
        this.startPolling();
    }

    createNotificationBell() {
        const bellHTML = `
            <div class="notification-bell" id="notif-bell">
                <i class="fas fa-bell"></i>
                <span class="notif-badge" id="notif-count" style="display:none;">0</span>
            </div>
        `;
        // Adicionar ao header (implementar conforme estrutura)
    }

    async fetchNotifications() {
        try {
            const base = window.BASE_URL || './';
            const response = await fetch(base + 'interface_programacao/social/get_notifications.php', { cache: 'no-store' });
            const data = await response.json();
            this.updateBadge(data.unread_count);
        } catch (error) {
            console.error('Erro ao buscar notificaÃ§Ãµes:', error);
        }
    }

    updateBadge(count) {
        const badge = document.getElementById('notif-count') || document.getElementById('notifBadge');
        if (!badge) return;
        if (count > 0) {
            badge.textContent = count > 99 ? '99+' : count;
            badge.style.display = 'flex';
        } else {
            badge.style.display = 'none';
        }
    }

    startPolling() {
        this.fetchNotifications();
        setInterval(() => this.fetchNotifications(), this.pollInterval);
    }
}

// Inicializar
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.notificationSystem = new NotificationSystem();
    });
} else {
    window.notificationSystem = new NotificationSystem();
}

