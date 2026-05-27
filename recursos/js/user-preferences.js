(function () {
    const root = document.documentElement;
    const storageKey = 'kaliye.preferences.v1';
    const supportedLangs = ['pt', 'en', 'fr', 'es'];

    const palettes = {
        orange: { primary: '#f7941d', secondary: '#fbbf24', glow: 'rgba(247, 148, 29, 0.26)' },
        blue: { primary: '#3b82f6', secondary: '#38bdf8', glow: 'rgba(59, 130, 246, 0.26)' },
        green: { primary: '#10b981', secondary: '#84cc16', glow: 'rgba(16, 185, 129, 0.24)' },
        rose: { primary: '#f43f5e', secondary: '#fb7185', glow: 'rgba(244, 63, 94, 0.24)' },
        violet: { primary: '#8b5cf6', secondary: '#a78bfa', glow: 'rgba(139, 92, 246, 0.24)' }
    };

    const phrasebook = {
        en: {
            'A KALIYE é uma plataforma criada para aproximar pessoas, conhecimento e oportunidades. Aqui, talentos, mentores, empreendedores e investidores encontram um espaço digital para desenvolver projectos, fortalecer competências, construir relações profissionais e transformar projectos em impacto real para Angola.': 'KALIYE is a platform created to connect people, knowledge and opportunities. Here, talents, mentors, entrepreneurs and investors find a digital space to develop projects, strengthen skills, build professional relationships and turn projects into real impact for Angola.',
            'Português': 'Portuguese', 'Français': 'French', 'Español': 'Spanish',
            'Inicio': 'Home', 'Início': 'Home', 'Home': 'Home', 'Explorar': 'Explore', 'Mentoria': 'Mentorship',
            'Mentorias': 'Mentorships', 'Perfil': 'Profile', 'Mensagens': 'Messages', 'Dashboard': 'Dashboard',
            'Projectos': 'Projects', 'Projetos': 'Projects', 'Mentores': 'Mentors', 'Estudantes': 'Students',
            'Carteira': 'Wallet', 'Termos': 'Terms', 'Privacidade': 'Privacy', 'Idioma': 'Language',
            'Brilho': 'Brightness', 'Modo': 'Mode', 'Claro': 'Light', 'Escuro': 'Dark', 'Automático': 'Automatic', 'Automatico': 'Automatic', 'Cor': 'Color',
            'Suave': 'Soft', 'Normal': 'Normal', 'Alto': 'High', 'Pesquisar...': 'Search...',
            'Pesquisar': 'Search', 'Ações Rápidas': 'Quick Actions', 'Ações Rapidas': 'Quick Actions',
            'Resultados da Busca': 'Search Results', 'Pesquise por projectos ou pessoas...': 'Search projects or people...',
            'Notificações': 'Notifications', 'Notificacoes': 'Notifications', 'Marcar como lidas': 'Mark as read',
            'Sem novas notificações': 'No new notifications', 'Sem novas notificacoes': 'No new notifications',
            'Dúvidas na Comunidade': 'Community Questions', 'Duvidas na Comunidade': 'Community Questions',
            'A Minha Conta': 'My Account', 'Todos os direitos reservados.': 'All rights reserved.',
            'O teu acelerador de sucesso profissional.': 'Your professional success accelerator.',
            'Recebe novidades e oportunidades sem perder o ritmo.': 'Receive news and opportunities without losing momentum.',
            'O teu nome': 'Your name', 'O teu e-mail': 'Your email', 'Subscrever': 'Subscribe',
            'Subscrito!': 'Subscribed!', 'A enviar...': 'Sending...', 'Publicidade': 'Advertising',
            'Saber Mais': 'Learn more', 'Ver Detalhes': 'View details', 'Publicar Agora': 'Publish now',
            'Comentar': 'Comment', 'Gostei': 'Like', 'Enviar': 'Send', 'Guardar Alterações': 'Save Changes',
            'Guardar Alteracoes': 'Save Changes', 'Editar Perfil': 'Edit Profile', 'Nome Completo': 'Full Name',
            'Encontrar Mentor': 'Find Mentor', 'Encontrar Mentores': 'Find Mentors', 'Explorar Projectos': 'Explore Projects',
            'Publicar Projecto': 'Post Project', 'Investimento?': 'Investment?', 'Contactar': 'Contact',
            'Foto': 'Photo', 'Vídeo': 'Video', 'Video': 'Video', 'Evento': 'Event', 'Editar': 'Edit',
            'Eliminar': 'Delete', 'Mensagem': 'Message', 'Ver todos': 'View all', 'Novo Projecto': 'New Project',
            'Título do Projecto': 'Project Title', 'Titulo do Projecto': 'Project Title', 'Categoria': 'Category',
            'Descrição Detalhada': 'Detailed Description', 'Descricao Detalhada': 'Detailed Description',
            'Investidor': 'Investor', 'Administrador': 'Administrator', 'Ensino Médio': 'High School',
            'Ensino Medio': 'High School', 'Universitário': 'University', 'Universitario': 'University',
            'Membro desde': 'Member since', 'Aplicar Filtros': 'Apply Filters', 'Limpar Filtros': 'Clear Filters',
            'Painel Administrador': 'Admin Panel', 'Carteira Digital': 'Digital Wallet',
            'Verificação Necessária': 'Verification Required', 'Verificar Agora': 'Verify Now',
            'A tua conta ainda não foi verificada para publicar projectos ou investir.': 'Your account has not been verified yet to publish projects or invest.',
            'Investimentos desativados': 'Investments disabled', 'A funcionalidade de investimento monetário está desativada nesta versão.': 'Monetary investment functionality is disabled in this version.',
            'Preferencias de apresentacao': 'Display preferences', 'Preferências de apresentação': 'Display preferences',
            'Modo claro/escuro': 'Light/dark mode', 'A Minha Conta': 'My Account',
            'Dúvidas': 'Questions', 'Dúvidas na Comunidade': 'Community Questions',
            'Criar': 'Create', 'Cancelar': 'Cancel', 'Confirmar': 'Confirm', 'Fechar': 'Close',
            'Carregando...': 'Loading...', 'A carregar...': 'Loading...', 'Nenhum resultado encontrado': 'No results found',
            'Publicado por': 'Published by', 'Publicado em': 'Published on', 'Há pouco': 'Just now'
        },
        fr: {
            'A KALIYE é uma plataforma criada para aproximar pessoas, conhecimento e oportunidades. Aqui, talentos, mentores, empreendedores e investidores encontram um espaço digital para desenvolver projectos, fortalecer competências, construir relações profissionais e transformar projectos em impacto real para Angola.': 'KALIYE est une plateforme créée pour rapprocher les personnes, les connaissances et les opportunités. Ici, talents, mentors, entrepreneurs et investisseurs trouvent un espace numérique pour développer des projets, renforcer des compétences, construire des relations professionnelles et transformer des projets en impact réel pour l’Angola.',
            'Português': 'Portugais', 'Français': 'Français', 'Español': 'Espagnol',
            'Inicio': 'Accueil', 'Início': 'Accueil', 'Home': 'Accueil', 'Explorar': 'Explorer',
            'Mentoria': 'Mentorat', 'Mentorias': 'Mentorats', 'Perfil': 'Profil', 'Mensagens': 'Messages',
            'Projectos': 'Projets', 'Projetos': 'Projets', 'Mentores': 'Mentors', 'Estudantes': 'Etudiants',
            'Carteira': 'Portefeuille', 'Termos': 'Conditions', 'Privacidade': 'Confidentialite',
            'Idioma': 'Langue', 'Brilho': 'Luminosite', 'Modo': 'Mode', 'Claro': 'Clair', 'Escuro': 'Sombre', 'Automático': 'Automatique', 'Automatico': 'Automatique',
            'Cor': 'Couleur', 'Suave': 'Doux', 'Alto': 'Eleve', 'Pesquisar...': 'Rechercher...',
            'Pesquisar': 'Rechercher', 'Notificações': 'Notifications', 'Notificacoes': 'Notifications',
            'Todos os direitos reservados.': 'Tous droits reserves.', 'Subscrever': 'Abonnement',
            'A enviar...': 'Envoi...', 'Saber Mais': 'En savoir plus', 'Ver Detalhes': 'Voir les details',
            'Publicar Agora': 'Publier', 'Comentar': 'Commenter', 'Gostei': "J'aime", 'Enviar': 'Envoyer',
            'O teu nome': 'Votre nom', 'O teu e-mail': 'Votre e-mail',
            'Recebe novidades e oportunidades sem perder o ritmo.': 'Recevez les nouvelles et opportunités sans perdre le rythme.',
            'Criar': 'Créer', 'Cancelar': 'Annuler', 'Confirmar': 'Confirmer', 'Fechar': 'Fermer',
            'Carregando...': 'Chargement...', 'A carregar...': 'Chargement...', 'Nenhum resultado encontrado': 'Aucun résultat trouvé'
        },
        es: {
            'A KALIYE é uma plataforma criada para aproximar pessoas, conhecimento e oportunidades. Aqui, talentos, mentores, empreendedores e investidores encontram um espaço digital para desenvolver projectos, fortalecer competências, construir relações profissionais e transformar projectos em impacto real para Angola.': 'KALIYE es una plataforma creada para acercar personas, conocimiento y oportunidades. Aquí, talentos, mentores, emprendedores e inversores encuentran un espacio digital para desarrollar proyectos, fortalecer competencias, construir relaciones profesionales y transformar proyectos en impacto real para Angola.',
            'Português': 'Portugués', 'Français': 'Francés', 'Español': 'Español',
            'Inicio': 'Inicio', 'Início': 'Inicio', 'Home': 'Inicio', 'Explorar': 'Explorar',
            'Mentoria': 'Mentoria', 'Mentorias': 'Mentorias', 'Perfil': 'Perfil', 'Mensagens': 'Mensajes',
            'Projectos': 'Proyectos', 'Projetos': 'Proyectos', 'Mentores': 'Mentores', 'Estudantes': 'Estudiantes',
            'Carteira': 'Cartera', 'Termos': 'Terminos', 'Privacidade': 'Privacidad', 'Idioma': 'Idioma',
            'Brilho': 'Brillo', 'Modo': 'Modo', 'Claro': 'Claro', 'Escuro': 'Oscuro', 'Automático': 'Automático', 'Automatico': 'Automático', 'Cor': 'Color',
            'Pesquisar...': 'Buscar...', 'Pesquisar': 'Buscar', 'Notificações': 'Notificaciones',
            'Notificacoes': 'Notificaciones', 'Todos os direitos reservados.': 'Todos los derechos reservados.',
            'Subscrever': 'Suscribirse', 'A enviar...': 'Enviando...', 'Saber Mais': 'Saber mas',
            'Ver Detalhes': 'Ver detalles', 'Publicar Agora': 'Publicar ahora', 'Comentar': 'Comentar',
            'Gostei': 'Me gusta', 'Enviar': 'Enviar',
            'O teu nome': 'Tu nombre', 'O teu e-mail': 'Tu correo',
            'Recebe novidades e oportunidades sem perder o ritmo.': 'Recibe novedades y oportunidades sin perder el ritmo.',
            'Criar': 'Crear', 'Cancelar': 'Cancelar', 'Confirmar': 'Confirmar', 'Fechar': 'Cerrar',
            'Carregando...': 'Cargando...', 'A carregar...': 'Cargando...', 'Nenhum resultado encontrado': 'No se encontraron resultados'
        }
    };

    const originalText = new WeakMap();
    const originalAttrs = new WeakMap();
    let observer = null;
    let scheduled = false;

    function cookieLang() {
        const match = document.cookie.match(/(?:^|;\s*)kaliye_lang=([^;]+)/);
        return match ? decodeURIComponent(match[1]) : '';
    }

    function loadPrefs() {
        try {
            return Object.assign(
                { lang: root.lang || cookieLang() || 'pt', accent: 'orange', brightness: 'normal', theme: 'auto' },
                JSON.parse(localStorage.getItem(storageKey) || '{}')
            );
        } catch (e) {
            return { lang: root.lang || cookieLang() || 'pt', accent: 'orange', brightness: 'normal', theme: 'auto' };
        }
    }

    function savePrefs(prefs) {
        localStorage.setItem(storageKey, JSON.stringify(prefs));
    }

    function persistLanguage(lang) {
        if (!supportedLangs.includes(lang)) lang = 'pt';
        document.cookie = `kaliye_lang=${encodeURIComponent(lang)};path=/;max-age=31536000;samesite=lax`;
        try {
            const url = new URL(window.location.href);
            url.searchParams.set('lang', lang);
            window.history.replaceState({}, '', url);
            fetch(url.href, { cache: 'no-store', credentials: 'same-origin' }).catch(() => {});
        } catch (e) {}
    }

    function applyTheme(prefs) {
        const palette = palettes[prefs.accent] || palettes.orange;
        const requestedTheme = ['light', 'dark', 'auto'].includes(prefs.theme) ? prefs.theme : 'auto';
        const systemLight = window.matchMedia && window.matchMedia('(prefers-color-scheme: light)').matches;
        const theme = requestedTheme === 'auto' ? (systemLight ? 'light' : 'dark') : requestedTheme;
        root.style.setProperty('--kaliye-accent', palette.primary);
        root.style.setProperty('--kaliye-accent-2', palette.secondary);
        root.style.setProperty('--kaliye-glow', palette.glow);
        root.style.setProperty('--brand-primary', palette.primary);
        root.style.setProperty('--brand-secondary', palette.secondary);
        root.style.setProperty('--accent-orange', palette.primary);
        root.style.setProperty('--accent-gold', palette.secondary);
        root.style.setProperty('--cor-destaque-laranja', palette.primary);
        root.style.setProperty('--cor-destaque-dourado', palette.secondary);
        root.style.setProperty('--glow-orange', palette.glow);
        root.style.setProperty('--brilho-laranja', palette.glow);
        root.dataset.kaliyeBrightness = prefs.brightness || 'normal';
        root.dataset.kaliyeTheme = theme;
        root.dataset.kaliyeThemePreference = requestedTheme;
        root.lang = supportedLangs.includes(prefs.lang) ? prefs.lang : 'pt';
        if (document.body) {
            document.body.classList.toggle('light-theme', theme === 'light');
            document.body.classList.toggle('dark-theme', theme !== 'light');
        }
    }

    function normalize(value) {
        return String(value || '').replace(/\s+/g, ' ').trim();
    }

    function escapeRegExp(value) {
        return String(value).replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    }

    function translateValue(value, lang) {
        const clean = normalize(value);
        if (!clean || lang === 'pt') return value;
        const dict = phrasebook[lang] || {};
        if (dict[clean]) return value.replace(clean, dict[clean]);
        let output = value;
        Object.keys(dict)
            .sort((a, b) => b.length - a.length)
            .forEach((key) => {
                if (key.length < 3 || !normalize(output).toLowerCase().includes(key.toLowerCase())) return;
                output = output.replace(new RegExp(escapeRegExp(key), 'gi'), (match) => {
                    if (match.toUpperCase() === match && match.length > 2) return String(dict[key]).toUpperCase();
                    return dict[key];
                });
            });
        return output;
    }

    function shouldSkip(node) {
        const parent = node.parentElement;
        if (!parent) return true;
        if (parent.closest('[data-no-translate], script, style, textarea, code, pre, svg, canvas')) return true;
        return !normalize(node.nodeValue);
    }

    function translateTextNodes(lang) {
        if (!document.body) return;
        if (observer) observer.disconnect();
        document.body.classList.toggle('kaliye-translation-pending', lang !== 'pt');

        const walker = document.createTreeWalker(document.body, NodeFilter.SHOW_TEXT, {
            acceptNode(node) {
                return shouldSkip(node) ? NodeFilter.FILTER_REJECT : NodeFilter.FILTER_ACCEPT;
            }
        });

        let node;
        while ((node = walker.nextNode())) {
            if (!originalText.has(node)) originalText.set(node, node.nodeValue);
            const source = originalText.get(node);
            node.nodeValue = lang === 'pt' ? source : translateValue(source, lang);
        }

        document.querySelectorAll('input[placeholder], textarea[placeholder], [title], [aria-label], [alt], option, input[type="button"], input[type="submit"], button[value]').forEach((el) => {
            if (el.closest('[data-no-translate]')) return;
            if (!originalAttrs.has(el)) {
                originalAttrs.set(el, {
                    placeholder: el.getAttribute('placeholder'),
                    title: el.getAttribute('title'),
                    aria: el.getAttribute('aria-label'),
                    alt: el.getAttribute('alt'),
                    value: el.getAttribute('value'),
                    text: el.tagName === 'OPTION' ? el.textContent : null
                });
            }
            const source = originalAttrs.get(el);
            ['placeholder', 'title', 'alt', 'value'].forEach((attr) => {
                if (source[attr] !== null) el.setAttribute(attr, lang === 'pt' ? source[attr] : translateValue(source[attr], lang));
            });
            if (source.aria !== null) el.setAttribute('aria-label', lang === 'pt' ? source.aria : translateValue(source.aria, lang));
            if (source.text !== null) el.textContent = lang === 'pt' ? source.text : translateValue(source.text, lang);
        });

        document.body.classList.remove('kaliye-translation-pending');
        installObserver(lang);
        window.dispatchEvent(new CustomEvent('kaliye:translated', { detail: { lang } }));
    }

    function scheduleTranslate(lang) {
        if (scheduled) return;
        scheduled = true;
        window.requestAnimationFrame(() => {
            scheduled = false;
            translateTextNodes(lang);
        });
    }

    function installObserver(lang) {
        observer = new MutationObserver((mutations) => {
            if (mutations.some((m) => m.addedNodes.length || m.type === 'characterData')) scheduleTranslate(lang);
        });
        observer.observe(document.body, { childList: true, subtree: true, characterData: true });
    }

    function syncControls(prefs) {
        document.querySelectorAll('[data-pref-lang]').forEach((el) => {
            el.value = prefs.lang || 'pt';
        });
        document.querySelectorAll('[data-pref-accent]').forEach((el) => {
            el.classList.toggle('is-active', el.dataset.prefAccent === prefs.accent);
        });
        document.querySelectorAll('[data-pref-brightness]').forEach((el) => {
            el.classList.toggle('is-active', el.dataset.prefBrightness === prefs.brightness);
        });
        document.querySelectorAll('[data-pref-theme]').forEach((el) => {
            el.classList.toggle('is-active', el.dataset.prefTheme === (prefs.theme || 'auto'));
        });
    }

    function bindControls() {
        document.addEventListener('change', (event) => {
            const control = event.target.closest('[data-pref-lang]');
            if (!control) return;
            const prefs = loadPrefs();
            prefs.lang = supportedLangs.includes(control.value) ? control.value : 'pt';
            savePrefs(prefs);
            persistLanguage(prefs.lang);
            applyTheme(prefs);
            syncControls(prefs);
            translateTextNodes(prefs.lang);
        });

        document.addEventListener('click', (event) => {
            const accent = event.target.closest('[data-pref-accent]');
            const brightness = event.target.closest('[data-pref-brightness]');
            const theme = event.target.closest('[data-pref-theme]');
            const themeToggle = event.target.closest('[data-pref-theme-toggle]');
            if (!accent && !brightness && !theme && !themeToggle) return;
            const prefs = loadPrefs();
            if (accent) prefs.accent = accent.dataset.prefAccent;
            if (brightness) prefs.brightness = brightness.dataset.prefBrightness;
            if (theme) prefs.theme = theme.dataset.prefTheme;
            if (themeToggle) prefs.theme = prefs.theme === 'light' ? 'dark' : 'light';
            savePrefs(prefs);
            applyTheme(prefs);
            syncControls(prefs);
        });

        if (window.matchMedia) {
            const media = window.matchMedia('(prefers-color-scheme: light)');
            const handleSystemTheme = () => {
                const prefs = loadPrefs();
                if ((prefs.theme || 'auto') === 'auto') applyTheme(prefs);
            };
            if (media.addEventListener) {
                media.addEventListener('change', handleSystemTheme);
            } else if (media.addListener) {
                media.addListener(handleSystemTheme);
            }
        }
    }

    function bindNewsletter() {
        document.addEventListener('submit', async (event) => {
            const form = event.target.closest('[data-kaliye-newsletter]');
            if (!form) return;
            event.preventDefault();

            const btn = form.querySelector('button[type="submit"]');
            const original = btn ? btn.innerHTML : '';
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> A enviar...';
            }

            try {
                const res = await fetch((window.BASE_URL || './') + 'interface_programacao/system/subscribe_newsletter.php', {
                    method: 'POST',
                    body: new FormData(form)
                });
                const data = await res.json();
                if (!data.success) throw new Error(data.message || 'Erro');
                form.reset();
                if (btn) btn.innerHTML = '<i class="fas fa-check"></i> Subscrito!';
            } catch (e) {
                if (window.Swal) {
                    Swal.fire({ icon: 'info', title: e.message || 'Nao foi possivel subscrever.', background: '#111827', color: '#fff' });
                }
                if (btn) btn.innerHTML = original;
            } finally {
                if (btn) {
                    setTimeout(() => {
                        btn.disabled = false;
                        btn.innerHTML = original || '<i class="fas fa-paper-plane"></i> Subscrever';
                    }, 1800);
                }
            }
        });
    }

    window.KaliyeTranslator = {
        setLanguage(lang) {
            const prefs = loadPrefs();
            prefs.lang = supportedLangs.includes(lang) ? lang : 'pt';
            savePrefs(prefs);
            persistLanguage(prefs.lang);
            applyTheme(prefs);
            syncControls(prefs);
            translateTextNodes(prefs.lang);
        },
        translatePage(lang) {
            translateTextNodes(lang || loadPrefs().lang || 'pt');
        },
        translateValue(value, lang) {
            return translateValue(value, lang || loadPrefs().lang || 'pt');
        }
    };

    const prefs = loadPrefs();
    applyTheme(prefs);

    document.addEventListener('DOMContentLoaded', () => {
        applyTheme(loadPrefs());
        syncControls(loadPrefs());
        translateTextNodes(loadPrefs().lang || 'pt');
        bindControls();
        bindNewsletter();
    });
})();
