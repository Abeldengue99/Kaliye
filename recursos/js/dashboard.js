/**
 * DASHBOARD.JS - Scripts Específicos do Dashboard
 * Inicializa componentes e funcionalidades do dashboard
 */

// ============================================================
// 1. GERENCIADOR DO DASHBOARD
// ============================================================

const DashboardManager = {
    state: {
        sidebarOpen: false,
        currentTab: 'overview',
        filters: {}
    },

    /**
     * Inicializa o dashboard
     */
    init() {
        console.log('Inicializando Dashboard...');

        this.initSidebar();
        this.initTabs();
        this.initWidgets();
        this.initCharts();
        this.loadStats();
        this.setupEventListeners();
    },

    /**
     * Inicializa sidebar
     */
    initSidebar() {
        const toggleBtn = document.querySelector('[data-toggle-sidebar]');
        const sidebar = document.querySelector('.dashboard-sidebar');
        const overlay = document.querySelector('.sidebar-overlay');

        if (toggleBtn) {
            toggleBtn.addEventListener('click', () => {
                this.state.sidebarOpen = !this.state.sidebarOpen;
                
                if (sidebar) {
                    if (this.state.sidebarOpen) {
                        sidebar.classList.add('open');
                    } else {
                        sidebar.classList.remove('open');
                    }
                }
            });
        }

        // Fechar sidebar ao clicar fora
        if (overlay) {
            overlay.addEventListener('click', () => {
                this.state.sidebarOpen = false;
                if (sidebar) sidebar.classList.remove('open');
            });
        }

        // Marcar link ativo no menu
        this.updateActiveLink();
    },

    /**
     * Atualiza link ativo no menu
     */
    updateActiveLink() {
        const currentPath = window.location.pathname;
        const menuLinks = document.querySelectorAll('.sidebar-menu-link');

        menuLinks.forEach(link => {
            if (link.getAttribute('href') === currentPath) {
                link.classList.add('active');
            } else {
                link.classList.remove('active');
            }
        });
    },

    /**
     * Inicializa tabs
     */
    initTabs() {
        const tabButtons = document.querySelectorAll('[data-tab]');

        tabButtons.forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                const tabName = btn.getAttribute('data-tab');
                this.switchTab(tabName);
            });
        });
    },

    /**
     * Muda de tab
     */
    switchTab(tabName) {
        // Desativa todas as tabs
        document.querySelectorAll('[data-tab]').forEach(btn => {
            btn.classList.remove('active');
        });

        document.querySelectorAll('[data-tab-panel]').forEach(panel => {
            panel.classList.add('hidden');
        });

        // Ativa a tab selecionada
        const activeBtn = document.querySelector(`[data-tab="${tabName}"]`);
        const activePanel = document.querySelector(`[data-tab-panel="${tabName}"]`);

        if (activeBtn) activeBtn.classList.add('active');
        if (activePanel) activePanel.classList.remove('hidden');

        this.state.currentTab = tabName;
    },

    /**
     * Inicializa widgets
     */
    initWidgets() {
        const widgets = document.querySelectorAll('.dashboard-widget');

        widgets.forEach(widget => {
            const refreshBtn = widget.querySelector('[data-refresh]');
            if (refreshBtn) {
                refreshBtn.addEventListener('click', async () => {
                    await this.refreshWidget(widget);
                });
            }

            const collapseBtn = widget.querySelector('[data-collapse]');
            if (collapseBtn) {
                collapseBtn.addEventListener('click', () => {
                    this.toggleWidget(widget);
                });
            }
        });
    },

    /**
     * Atualiza widget
     */
    async refreshWidget(widget) {
        const loadingSpinner = document.createElement('div');
        loadingSpinner.className = 'loading-spinner';

        const originalContent = widget.innerHTML;
        const content = widget.querySelector('[data-content]');

        if (content) {
            content.appendChild(loadingSpinner);

            try {
                // Simulação de carregamento
                await new Promise(resolve => setTimeout(resolve, 1000));
                
                // Limpar spinner
                loadingSpinner.remove();

                // Emitir evento
                App.EventManager.emit('widget:refreshed', { widget });

            } catch (error) {
                console.error('Erro ao atualizar widget:', error);
                loadingSpinner.remove();
                widget.innerHTML = originalContent;
            }
        }
    },

    /**
     * Toggle widget collapse
     */
    toggleWidget(widget) {
        const body = widget.querySelector('[data-content]');
        if (body) {
            body.classList.toggle('hidden');
            widget.classList.toggle('collapsed');
        }
    },

    /**
     * Inicializa gráficos
     */
    initCharts() {
        const charts = document.querySelectorAll('[data-chart]');

        charts.forEach(chartEl => {
            const type = chartEl.getAttribute('data-chart');
            const data = chartEl.getAttribute('data-chart-data');

            if (type && data) {
                try {
                    const parsedData = JSON.parse(data);
                    this.renderChart(chartEl, type, parsedData);
                } catch (e) {
                    console.error('Erro ao parsear dados do gráfico:', e);
                }
            }
        });
    },

    /**
     * Renderiza gráfico
     */
    renderChart(container, type, data) {
        // Implementação básica (usar Chart.js se necessário)
        container.innerHTML = `<p>Gráfico de tipo "${type}" pronto para dados</p>`;
    },

    /**
     * Carrega estatísticas
     */
    async loadStats() {
        const statElements = document.querySelectorAll('[data-stat]');

        for (let stat of statElements) {
            const endpoint = stat.getAttribute('data-stat');
            
            try {
                const data = await App.Utils.fetchAPI(endpoint);
                
                const valueEl = stat.querySelector('[data-stat-value]');
                const changeEl = stat.querySelector('[data-stat-change]');

                if (valueEl) valueEl.textContent = data.value;
                if (changeEl) {
                    changeEl.textContent = `${data.change > 0 ? '+' : ''}${data.change}%`;
                    changeEl.className = `stat-change ${data.change > 0 ? 'positive' : 'negative'}`;
                }

            } catch (error) {
                console.error('Erro ao carregar estatística:', error);
                stat.querySelector('[data-stat-value]').textContent = '—';
            }
        }
    },

    /**
     * Setup de event listeners
     */
    setupEventListeners() {
        // Filtros
        const filterInputs = document.querySelectorAll('[data-filter]');
        filterInputs.forEach(input => {
            input.addEventListener('change', (e) => {
                this.applyFilters();
            });
        });

        // Ações em tabelas
        const deleteButtons = document.querySelectorAll('[data-action="delete"]');
        deleteButtons.forEach(btn => {
            btn.addEventListener('click', async (e) => {
                e.preventDefault();
                if (confirm('Tem certeza que deseja deletar?')) {
                    await this.deleteItem(btn);
                }
            });
        });

        const editButtons = document.querySelectorAll('[data-action="edit"]');
        editButtons.forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                const itemId = btn.getAttribute('data-id');
                this.editItem(itemId);
            });
        });
    },

    /**
     * Aplica filtros
     */
    applyFilters() {
        const filters = {};
        document.querySelectorAll('[data-filter]').forEach(input => {
            const key = input.getAttribute('data-filter');
            filters[key] = input.value;
        });

        this.state.filters = filters;

        // Emitir evento para recarregar dados
        App.EventManager.emit('dashboard:filtersChanged', filters);
    },

    /**
     * Deleta item
     */
    async deleteItem(btn) {
        const itemId = btn.getAttribute('data-id');
        const endpoint = btn.getAttribute('data-endpoint');

        try {
            const response = await App.Utils.fetchAPI(`${endpoint}/${itemId}`, {
                method: 'DELETE'
            });

            App.Utils.showToast('✅ Item deletado com sucesso', 'success');
            
            // Remover da tabela
            btn.closest('tr')?.remove();

            // Recarregar stats
            this.loadStats();

        } catch (error) {
            App.Utils.showToast('❌ Erro ao deletar item', 'danger');
        }
    },

    /**
     * Edita item
     */
    editItem(itemId) {
        // Implementação específica do formulário de edição
        const editModal = document.querySelector('[data-modal="edit"]');
        if (editModal) {
            editModal.classList.remove('hidden');
            // Carregar dados do item...
        }
    }
};

// ============================================================
// 2. NOTIFICAÇÕES DO DASHBOARD
// ============================================================

const DashboardNotifications = {
    /**
     * Inicializa notificações
     */
    init() {
        this.setupWebSocket();
        this.loadInitialNotifications();
    },

    /**
     * Setup WebSocket (ou polling alternativo)
     */
    setupWebSocket() {
        // Implementar WebSocket ou polling para notificações em tempo real
        const pollInterval = 30000; // 30s

        setInterval(() => {
            this.loadInitialNotifications();
        }, pollInterval);
    },

    /**
     * Carrega notificações iniciais
     */
    async loadInitialNotifications() {
        try {
            const response = await App.Utils.fetchAPI('/interface_programacao/notifications/get_notifications.php');
            
            if (response.notifications && response.notifications.length > 0) {
                this.renderNotifications(response.notifications);
            }

        } catch (error) {
            console.error('Erro ao carregar notificações:', error);
        }
    },

    /**
     * Renderiza notificações
     */
    renderNotifications(notifications) {
        const container = document.querySelector('[data-notifications]');
        if (!container) return;

        container.innerHTML = notifications.map(notif => `
            <div class="notification-item" data-notification-id="${notif.id}">
                <span class="notification-message">${notif.message}</span>
                <button class="notification-close" data-close-notification="${notif.id}">×</button>
            </div>
        `).join('');

        // Event listeners para fechar
        container.querySelectorAll('[data-close-notification]').forEach(btn => {
            btn.addEventListener('click', async (e) => {
                e.preventDefault();
                const notifId = btn.getAttribute('data-close-notification');
                await this.markAsRead(notifId);
            });
        });
    },

    /**
     * Marca notificação como lida
     */
    async markAsRead(notificationId) {
        try {
            await App.Utils.fetchAPI('/interface_programacao/notifications/mark_read.php', {
                method: 'POST',
                body: JSON.stringify({ notification_id: notificationId })
            });

            document.querySelector(`[data-notification-id="${notificationId}"]`)?.remove();
        } catch (error) {
            console.error('Erro ao marcar notificação como lida:', error);
        }
    }
};

// ============================================================
// 3. INICIALIZAÇÃO
// ============================================================

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        DashboardManager.init();
        DashboardNotifications.init();
    });
} else {
    DashboardManager.init();
    DashboardNotifications.init();
}

// ============================================================
// 4. EXPORTAR
// ============================================================

window.Dashboard = {
    DashboardManager,
    DashboardNotifications
};

console.log('Dashboard scripts carregados');
