(function() {
    'use strict';

    const VERSION_KEY = 'aksanti.platform.version.seen';
    const VERSION_SNOOZE_KEY = 'aksanti.platform.version.snoozed_until';
    const INSTALL_SNOOZE_KEY = 'aksanti.pwa.install.snoozed_until';
    const INSTALL_DONE_KEY = 'aksanti.pwa.install.done';
    const DAY_MS = 24 * 60 * 60 * 1000;
    const UPDATE_SNOOZE_MS = 12 * 60 * 60 * 1000;
    let deferredInstallPrompt = null;
    let refreshingForServiceWorker = false;

    function baseUrl() {
        const base = window.BASE_URL || './';
        const link = document.createElement('a');
        link.href = base;
        return link.href.endsWith('/') ? link.href : link.href + '/';
    }

    function canUseStorage() {
        try {
            const key = '__aksanti_storage_test__';
            localStorage.setItem(key, '1');
            localStorage.removeItem(key);
            return true;
        } catch (e) {
            return false;
        }
    }

    const storageReady = canUseStorage();

    function getStored(key) {
        if (!storageReady) return null;
        return localStorage.getItem(key);
    }

    function setStored(key, value) {
        if (!storageReady) return;
        localStorage.setItem(key, String(value));
    }

    function removeStored(key) {
        if (!storageReady) return;
        localStorage.removeItem(key);
    }

    function isStandalone() {
        return window.matchMedia('(display-mode: standalone)').matches
            || window.navigator.standalone === true;
    }

    function isIos() {
        return /iphone|ipad|ipod/i.test(window.navigator.userAgent || '');
    }

    function registerServiceWorker() {
        if (!('serviceWorker' in navigator) || window.__aksantiServiceWorkerRegistrationStarted) return;
        window.__aksantiServiceWorkerRegistrationStarted = true;
        window.addEventListener('load', function() {
            setTimeout(function() {
                navigator.serviceWorker.register(baseUrl() + 'sw.js').catch(function() {});
            }, 5000);
        });
    }

    function installInstantNavigationPrefetch() {
        if (window.__aksantiInstantNavigationPrefetch) return;
        if (window.matchMedia && window.matchMedia('(hover: none)').matches) return;
        if (navigator.connection && (navigator.connection.saveData || /2g/.test(navigator.connection.effectiveType || ''))) return;
        window.__aksantiInstantNavigationPrefetch = true;
        const prefetched = new Set();
        const ignoredPatterns = [
            '/autenticacao/sair.php',
            '/interface_programacao/',
            '/administracao/',
            'delete',
            'logout',
            'sair'
        ];

        function shouldPrefetch(anchor) {
            if (!anchor || !anchor.href || anchor.target === '_blank') return false;
            if (anchor.hasAttribute('download')) return false;
            const url = new URL(anchor.href, window.location.href);
            if (url.origin !== window.location.origin) return false;
            if (url.href === window.location.href) return false;
            if (!['http:', 'https:'].includes(url.protocol)) return false;
            const normalized = (url.pathname + url.search).toLowerCase();
            return !ignoredPatterns.some(function(pattern) {
                return normalized.includes(pattern);
            });
        }

        function prefetch(anchor) {
            if (!shouldPrefetch(anchor)) return;
            const url = new URL(anchor.href, window.location.href);
            url.hash = '';
            const href = url.href;
            if (prefetched.has(href)) return;
            prefetched.add(href);

            const link = document.createElement('link');
            link.rel = 'prefetch';
            link.href = href;
            link.as = 'document';
            document.head.appendChild(link);
        }

        function schedulePrefetch(event) {
            const anchor = event.target.closest && event.target.closest('a[href]');
            if (!anchor) return;
            if ('requestIdleCallback' in window) {
                window.requestIdleCallback(function() { prefetch(anchor); }, { timeout: 1200 });
            } else {
                setTimeout(function() { prefetch(anchor); }, 120);
            }
        }

        document.addEventListener('mouseover', schedulePrefetch, { passive: true });
    }

    function optimizeImages() {
        const images = document.querySelectorAll('img:not([loading]):not([data-eager])');
        images.forEach(function(img, index) {
            if (index > 1) img.loading = 'lazy';
            img.decoding = 'async';
        });
    }

    function isSnoozed(key) {
        const until = parseInt(getStored(key) || '0', 10);
        return until && Date.now() < until;
    }

    function injectStyles() {
        if (document.getElementById('aksanti-platform-prompts-style')) return;
        const style = document.createElement('style');
        style.id = 'aksanti-platform-prompts-style';
        style.textContent = `
            .aksanti-prompt-stack {
                position: fixed;
                right: 18px;
                bottom: 18px;
                z-index: 100000;
                display: grid;
                gap: 12px;
                width: min(420px, calc(100vw - 28px));
                pointer-events: none;
            }
            .aksanti-platform-card {
                pointer-events: auto;
                position: relative;
                display: grid;
                grid-template-columns: 46px 1fr;
                gap: 14px;
                padding: 16px;
                border: 1px solid rgba(247, 148, 29, 0.28);
                border-radius: 18px;
                background: linear-gradient(145deg, rgba(8, 14, 28, 0.96), rgba(17, 24, 39, 0.96));
                color: #fff;
                box-shadow: 0 20px 55px rgba(0, 0, 0, 0.45), 0 0 0 1px rgba(255, 255, 255, 0.04) inset;
                backdrop-filter: blur(18px);
                overflow: hidden;
                animation: aksantiPromptIn 0.34s ease both;
            }
            .aksanti-platform-card:before {
                content: "";
                position: absolute;
                inset: 0 auto 0 0;
                width: 4px;
                background: linear-gradient(180deg, #f7941d, #10b981);
            }
            .aksanti-platform-icon {
                width: 46px;
                height: 46px;
                display: grid;
                place-items: center;
                border-radius: 14px;
                background: rgba(247, 148, 29, 0.13);
                color: #f7941d;
                font-size: 1.15rem;
            }
            .aksanti-platform-body {
                min-width: 0;
                padding-right: 26px;
            }
            .aksanti-platform-eyebrow {
                margin: 0 0 3px;
                color: #fbbf24;
                font-size: 0.7rem;
                font-weight: 800;
                text-transform: uppercase;
                letter-spacing: 0;
            }
            .aksanti-platform-title {
                margin: 0;
                color: #fff;
                font-size: 0.98rem;
                font-weight: 850;
                line-height: 1.25;
            }
            .aksanti-platform-text {
                margin: 7px 0 13px;
                color: rgba(255, 255, 255, 0.72);
                font-size: 0.84rem;
                line-height: 1.5;
            }
            .aksanti-platform-actions {
                display: flex;
                flex-wrap: wrap;
                gap: 8px;
            }
            .aksanti-platform-primary,
            .aksanti-platform-secondary,
            .aksanti-platform-close {
                border: 0;
                cursor: pointer;
                font: inherit;
            }
            .aksanti-platform-primary {
                display: inline-flex;
                align-items: center;
                gap: 8px;
                padding: 9px 13px;
                border-radius: 11px;
                background: #f7941d;
                color: #101827;
                font-size: 0.78rem;
                font-weight: 850;
                box-shadow: 0 10px 24px rgba(247, 148, 29, 0.24);
            }
            .aksanti-platform-secondary {
                padding: 9px 11px;
                border-radius: 11px;
                background: rgba(255, 255, 255, 0.07);
                color: rgba(255, 255, 255, 0.82);
                font-size: 0.78rem;
                font-weight: 750;
            }
            .aksanti-platform-close {
                position: absolute;
                top: 10px;
                right: 10px;
                width: 30px;
                height: 30px;
                display: grid;
                place-items: center;
                border-radius: 10px;
                background: rgba(255, 255, 255, 0.06);
                color: rgba(255, 255, 255, 0.7);
            }
            .aksanti-platform-close:hover,
            .aksanti-platform-secondary:hover {
                background: rgba(255, 255, 255, 0.12);
                color: #fff;
            }
            @keyframes aksantiPromptIn {
                from { opacity: 0; transform: translateY(14px) scale(0.98); }
                to { opacity: 1; transform: translateY(0) scale(1); }
            }
            @media (max-width: 640px) {
                .aksanti-prompt-stack {
                    left: 12px;
                    right: 12px;
                    bottom: max(12px, calc(76px + env(safe-area-inset-bottom, 0px)));
                    width: auto;
                }
                .aksanti-platform-card {
                    grid-template-columns: 40px 1fr;
                    border-radius: 16px;
                    padding: 14px;
                    font-size: 0.9rem;
                }
                .aksanti-platform-icon {
                    width: 40px;
                    height: 40px;
                    border-radius: 12px;
                }
                .aksanti-platform-title {
                    font-size: 0.9rem;
                }
                .aksanti-platform-text {
                    font-size: 0.8rem;
                }
                .aksanti-platform-actions {
                    gap: 6px;
                }
                .aksanti-platform-primary,
                .aksanti-platform-secondary {
                    font-size: 0.7rem;
                    padding: 8px 10px;
                }
            }
        `;
        document.head.appendChild(style);
    }

    function promptStack() {
        injectStyles();
        let stack = document.querySelector('.aksanti-prompt-stack');
        if (!stack) {
            stack = document.createElement('div');
            stack.className = 'aksanti-prompt-stack';
            document.body.appendChild(stack);
        }
        return stack;
    }

    function removeCard(id) {
        const existing = document.getElementById(id);
        if (existing) existing.remove();
    }

    function escapeHtml(value) {
        return String(value || '').replace(/[&<>"']/g, function(ch) {
            return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' })[ch];
        });
    }

    function renderCard(options) {
        removeCard(options.id);
        const card = document.createElement('section');
        card.id = options.id;
        card.className = 'aksanti-platform-card';
        card.setAttribute('role', 'dialog');
        card.setAttribute('aria-live', 'polite');
        card.innerHTML = `
            <div class="aksanti-platform-icon"><i class="${options.icon}"></i></div>
            <div class="aksanti-platform-body">
                <p class="aksanti-platform-eyebrow">${escapeHtml(options.eyebrow)}</p>
                <h3 class="aksanti-platform-title">${escapeHtml(options.title)}</h3>
                <p class="aksanti-platform-text">${escapeHtml(options.text)}</p>
                <div class="aksanti-platform-actions">
                    <button type="button" class="aksanti-platform-primary">${options.primary}</button>
                    <button type="button" class="aksanti-platform-secondary">${options.secondary}</button>
                </div>
            </div>
            <button type="button" class="aksanti-platform-close" aria-label="Fechar"><i class="fas fa-times"></i></button>
        `;
        card.querySelector('.aksanti-platform-primary').addEventListener('click', options.onPrimary);
        card.querySelector('.aksanti-platform-secondary').addEventListener('click', options.onSecondary);
        card.querySelector('.aksanti-platform-close').addEventListener('click', options.onClose || options.onSecondary);
        promptStack().appendChild(card);
        return card;
    }

    function reloadForUpdate(version) {
        setStored(VERSION_KEY, version);
        removeStored(VERSION_SNOOZE_KEY);

        if (navigator.serviceWorker && navigator.serviceWorker.controller) {
            navigator.serviceWorker.getRegistration().then(function(registration) {
                if (registration && registration.waiting) {
                    registration.waiting.postMessage({ type: 'SKIP_WAITING' });
                    return;
                }
                window.location.reload();
            }).catch(function() {
                window.location.reload();
            });
        } else {
            window.location.reload();
        }
    }

    function showUpdatePrompt(meta) {
        if (!meta || !meta.version || isSnoozed(VERSION_SNOOZE_KEY)) return;

        renderCard({
            id: 'aksanti-update-card',
            icon: 'fas fa-rotate',
            eyebrow: 'Atualização disponível',
            title: meta.title || 'Nova versão da KALIYE',
            text: meta.note || 'Atualize para receber as melhorias mais recentes da plataforma.',
            primary: '<i class="fas fa-bolt"></i> Atualizar agora',
            secondary: 'Mais tarde',
            onPrimary: function() {
                reloadForUpdate(meta.version);
            },
            onSecondary: function() {
                setStored(VERSION_SNOOZE_KEY, Date.now() + UPDATE_SNOOZE_MS);
                removeCard('aksanti-update-card');
            }
        });
    }

    function checkPlatformVersion() {
        fetch(baseUrl() + 'interface_programacao/system/platform_version.php', {
            cache: 'no-store',
            credentials: 'same-origin'
        })
            .then(function(response) { return response.json(); })
            .then(function(meta) {
                if (!meta || !meta.success || !meta.version) return;

                const seenVersion = getStored(VERSION_KEY);
                if (!seenVersion) {
                    setStored(VERSION_KEY, meta.version);
                    return;
                }

                if (seenVersion !== meta.version) {
                    showUpdatePrompt(meta);
                }
            })
            .catch(function() {});
    }

    function showIosInstallHelp() {
        if (window.Swal) {
            window.Swal.fire({
                title: 'Instalar a KALIYE',
                html: '<p style="color: rgba(255,255,255,.75); line-height: 1.6;">No iPhone ou iPad, toque em <b>Partilhar</b> e escolha <b>Adicionar ao Ecrã Principal</b>. Assim a KALIYE fica como uma app no seu dispositivo.</p>',
                icon: 'info',
                confirmButtonText: 'Entendi',
                confirmButtonColor: '#f7941d',
                background: '#111827',
                color: '#fff'
            });
        }
    }

    function showInstallPrompt(manualHelpOnly) {
        if (isStandalone() || getStored(INSTALL_DONE_KEY) === '1' || isSnoozed(INSTALL_SNOOZE_KEY)) return;
        if (!deferredInstallPrompt && !manualHelpOnly) return;
        if (document.getElementById('aksanti-update-card')) return;

        const text = isIos()
            ? 'Leve a KALIYE para o ecrã principal e entre mais rápido nas mentorias, projetos e notificações.'
            : 'Instale a KALIYE no telefone ou computador para abrir mais rápido e manter a plataforma sempre à mão.';

        renderCard({
            id: 'aksanti-install-card',
            icon: 'fas fa-mobile-screen-button',
            eyebrow: 'Acesso rápido',
            title: 'Use a KALIYE como aplicativo',
            text: text,
            primary: isIos() ? '<i class="fas fa-circle-plus"></i> Como instalar' : '<i class="fas fa-download"></i> Instalar',
            secondary: 'Agora não',
            onPrimary: function() {
                if (deferredInstallPrompt) {
                    deferredInstallPrompt.prompt();
                    deferredInstallPrompt.userChoice.then(function(choice) {
                        if (choice && choice.outcome === 'accepted') {
                            setStored(INSTALL_DONE_KEY, '1');
                        } else {
                            setStored(INSTALL_SNOOZE_KEY, Date.now() + 7 * DAY_MS);
                        }
                        deferredInstallPrompt = null;
                        removeCard('aksanti-install-card');
                    });
                    return;
                }

                showIosInstallHelp();
                setStored(INSTALL_SNOOZE_KEY, Date.now() + 3 * DAY_MS);
                removeCard('aksanti-install-card');
            },
            onSecondary: function() {
                setStored(INSTALL_SNOOZE_KEY, Date.now() + 7 * DAY_MS);
                removeCard('aksanti-install-card');
            }
        });
    }

    window.addEventListener('beforeinstallprompt', function(event) {
        event.preventDefault();
        deferredInstallPrompt = event;
        setTimeout(function() {
            showInstallPrompt(false);
        }, 9000);
    });

    window.addEventListener('appinstalled', function() {
        setStored(INSTALL_DONE_KEY, '1');
        removeCard('aksanti-install-card');
    });

    if (navigator.serviceWorker) {
        navigator.serviceWorker.addEventListener('controllerchange', function() {
            if (refreshingForServiceWorker) return;
            refreshingForServiceWorker = true;
            window.location.reload();
        });
    }

    document.addEventListener('DOMContentLoaded', function() {
        setTimeout(checkPlatformVersion, 2200);
        installInstantNavigationPrefetch();
        optimizeImages();

        if (isIos() && !isStandalone() && !getStored(INSTALL_DONE_KEY)) {
            setTimeout(function() {
                showInstallPrompt(true);
            }, 12000);
        }
    });

    registerServiceWorker();
})();
