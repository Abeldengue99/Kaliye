/**
 * MAIN.JS - Scripts Globais da Plataforma
 * Inicializa componentes, event listeners, e funcionalidades gerais
 */

// ============================================================
// 1. CONFIGURAÇÕES GLOBAIS
// ============================================================

const APP = {
    name: 'Aksanti Referências',
    version: '1.0.0',
    baseUrl: document.querySelector('base')?.href || '/',
    isProduction: window.location.hostname !== 'localhost',
    debug: !this.isProduction,
};

// ============================================================
// 2. UTILIDADES
// ============================================================

const Utils = {
    /**
     * Log seguro (apenas em desenvolvimento)
     */
    log(...args) {
        if (APP.debug) console.log('[APP]', ...args);
    },

    /**
     * Fetch com tratamento de erros
     */
    async fetchAPI(url, options = {}) {
        try {
            const response = await fetch(url, {
                ...options,
                headers: {
                    'Content-Type': 'application/json',
                    ...options.headers,
                }
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            return await response.json();
        } catch (error) {
            Utils.log('Fetch Error:', error);
            throw error;
        }
    },

    /**
     * Mostra notificação toast
     */
    showToast(message, type = 'info') {
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.textContent = message;
        document.body.appendChild(toast);

        setTimeout(() => toast.remove(), 3000);
    },

    /**
     * Formata data
     */
    formatDate(date, format = 'pt-BR') {
        return new Date(date).toLocaleDateString(format);
    },

    /**
     * Limpa cache local
     */
    clearCache() {
        if ('caches' in window) {
            caches.keys().then(names => {
                names.forEach(name => caches.delete(name));
            });
        }
        Utils.log('Cache limpo');
    },

    /**
     * Detecta plataforma
     */
    getPlatform() {
        const ua = navigator.userAgent;
        if (/mobile/i.test(ua)) return 'mobile';
        if (/tablet/i.test(ua)) return 'tablet';
        return 'desktop';
    }
};

// ============================================================
// 3. GERENCIADOR DE DOM
// ============================================================

const DOM = {
    /**
     * Seleciona elemento
     */
    select(selector) {
        return document.querySelector(selector);
    },

    /**
     * Seleciona múltiplos elementos
     */
    selectAll(selector) {
        return document.querySelectorAll(selector);
    },

    /**
     * Adiciona classe
     */
    addClass(el, className) {
        if (el) el.classList.add(className);
    },

    /**
     * Remove classe
     */
    removeClass(el, className) {
        if (el) el.classList.remove(className);
    },

    /**
     * Toggle classe
     */
    toggleClass(el, className) {
        if (el) el.classList.toggle(className);
    },

    /**
     * Define atributo
     */
    setAttribute(el, attr, value) {
        if (el) el.setAttribute(attr, value);
    },

    /**
     * Obtém valor de input
     */
    getValue(selector) {
        const el = DOM.select(selector);
        return el ? el.value : '';
    },

    /**
     * Define valor de input
     */
    setValue(selector, value) {
        const el = DOM.select(selector);
        if (el) el.value = value;
    },

    /**
     * Limpa elemento
     */
    clear(el) {
        if (el) el.innerHTML = '';
    }
};

// ============================================================
// 4. GERENCIADOR DE EVENTOS
// ============================================================

const EventManager = {
    listeners: {},

    /**
     * Registra event listener
     */
    on(selector, event, handler) {
        const elements = typeof selector === 'string' 
            ? document.querySelectorAll(selector) 
            : [selector];

        elements.forEach(el => {
            el.addEventListener(event, handler);
        });
    },

    /**
     * Dispara evento customizado
     */
    emit(eventName, detail) {
        const event = new CustomEvent(eventName, { detail });
        document.dispatchEvent(event);
    },

    /**
     * Escuta evento customizado
     */
    listen(eventName, handler) {
        document.addEventListener(eventName, handler);
    }
};

// ============================================================
// 5. GERENCIADOR DE STORAGE
// ============================================================

const Storage = {
    /**
     * Salva no localStorage
     */
    set(key, value) {
        try {
            localStorage.setItem(key, JSON.stringify(value));
        } catch (e) {
            Utils.log('Storage Error:', e);
        }
    },

    /**
     * Recupera do localStorage
     */
    get(key) {
        try {
            const item = localStorage.getItem(key);
            return item ? JSON.parse(item) : null;
        } catch (e) {
            Utils.log('Storage Error:', e);
            return null;
        }
    },

    /**
     * Remove do localStorage
     */
    remove(key) {
        localStorage.removeItem(key);
    },

    /**
     * Limpa localStorage
     */
    clear() {
        localStorage.clear();
    }
};

// ============================================================
// 6. INICIALIZADORES
// ============================================================

const Initializers = {
    /**
     * Inicializa Service Worker (PWA)
     */
    initServiceWorker() {
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('sw.js')
                .then(reg => {
                    Utils.log('Service Worker registrado:', reg);
                })
                .catch(err => {
                    Utils.log('Erro ao registrar Service Worker:', err);
                });
        }
    },

    /**
     * Inicializa observador de mudanças de conectividade
     */
    initConnectivityMonitor() {
        window.addEventListener('online', () => {
            Utils.showToast('✅ Conectado à internet', 'success');
            EventManager.emit('app:online');
        });

        window.addEventListener('offline', () => {
            Utils.showToast('⚠️ Desconectado da internet', 'warning');
            EventManager.emit('app:offline');
        });
    },

    /**
     * Inicializa detectores de scroll
     */
    initScrollDetectors() {
        let lastScrollY = 0;

        window.addEventListener('scroll', () => {
            const scrollY = window.scrollY;

            // Esconde header ao scroll para baixo
            const header = DOM.select('.dashboard-header');
            if (header) {
                if (scrollY > lastScrollY && scrollY > 64) {
                    DOM.addClass(header, 'hidden');
                } else {
                    DOM.removeClass(header, 'hidden');
                }
            }

            lastScrollY = scrollY;
        });
    },

    /**
     * Inicializa delegação de formulários
     */
    initFormHandlers() {
        document.addEventListener('submit', async (e) => {
            const form = e.target;

            // Apenas para formulários com classe 'ajax-form'
            if (!form.classList.contains('ajax-form')) return;

            e.preventDefault();

            const formData = new FormData(form);
            const url = form.action || window.location.href;
            const method = form.method || 'POST';

            try {
                const response = await Utils.fetchAPI(url, {
                    method,
                    body: new URLSearchParams(formData)
                });

                Utils.showToast('✅ ' + (response.message || 'Sucesso!'), 'success');
                EventManager.emit('form:success', response);

                // Limpa formulário
                form.reset();

            } catch (error) {
                Utils.showToast('❌ Erro: ' + error.message, 'danger');
                EventManager.emit('form:error', error);
            }
        });
    },

    /**
     * Inicializa dark mode
     */
    initDarkMode() {
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        const savedMode = Storage.get('darkMode');
        const isDark = savedMode !== null ? savedMode : prefersDark;

        if (isDark) {
            document.documentElement.classList.add('dark-mode');
        }

        // Toggle dark mode
        const darkModeToggle = DOM.select('[data-toggle-dark-mode]');
        if (darkModeToggle) {
            darkModeToggle.addEventListener('click', () => {
                document.documentElement.classList.toggle('dark-mode');
                const newState = document.documentElement.classList.contains('dark-mode');
                Storage.set('darkMode', newState);
            });
        }
    },

    /**
     * Inicializa popovers/tooltips
     */
    initTooltips() {
        EventManager.on('[data-tooltip]', 'mouseenter', function() {
            const text = this.getAttribute('data-tooltip');
            // Implementação simples de tooltip
            const tooltip = document.createElement('div');
            tooltip.className = 'tooltip-popup';
            tooltip.textContent = text;
            this.parentElement.appendChild(tooltip);

            setTimeout(() => tooltip.remove(), 3000);
        });
    }
};

// ============================================================
// 7. BOOTSTRAP DA APLICAÇÃO
// ============================================================

function initApp() {
    Utils.log('Iniciando aplicação...', APP);

    // Registrar listeners globais
    Initializers.initConnectivityMonitor();
    Initializers.initScrollDetectors();
    Initializers.initFormHandlers();
    Initializers.initDarkMode();
    Initializers.initTooltips();
    Initializers.initServiceWorker();

    // Evento customizado de aplicação iniciada
    EventManager.emit('app:ready');

    Utils.log('Aplicação iniciada com sucesso');
}

// ============================================================
// 8. WAIT FOR DOM
// ============================================================

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initApp);
} else {
    initApp();
}

// ============================================================
// 9. EXPORTAR PARA WINDOW (Acesso global)
// ============================================================

window.App = {
    APP,
    Utils,
    DOM,
    EventManager,
    Storage,
    Initializers
};

Utils.log('Scripts globais carregados');
