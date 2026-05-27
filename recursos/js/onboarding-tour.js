// Tour Guiado - KALIYE Platform
// Sistema de onboarding interativo para novos utilizadors

(function () {
    'use strict';

    class OnboardingTour {
        constructor() {
            this.currentStep = 0;
            this.steps = [];
            this.overlay = null;
            this.tooltip = null;
            this.isActive = false;
        }

        // Definir passos do tour baseado no tipo de utilizador
        defineSteps(userType) {
            const commonSteps = [
                {
                    target: '.logo',
                    title: 'Bem-vindo à Aksanti! 🎉',
                    content: 'Esta é a plataforma que conecta estudantes, mentores e investidores.',
                    position: 'bottom'
                },
                {
                    target: '#themeToggle',
                    title: 'Personalize sua Experiência',
                    content: 'Alterne entre modo escuro e claro clicando aqui.',
                    position: 'bottom'
                }
            ];

            const studentSteps = [
                {
                    target: 'a[href*="index.php"]',
                    title: 'Feed de Projectos 💡',
                    content: 'Veja projectos de outros estudantes e oportunidades de investidores.',
                    position: 'bottom'
                },
                {
                    target: 'a[href*="analytics.php"]',
                    title: 'Seu Dashboard 📊',
                    content: 'Acompanhe o desempenho das seus projectos e estatísticas.',
                    position: 'bottom'
                },
                {
                    target: 'a[href*="mentorship.php"]',
                    title: 'Encontre Mentores 👨‍🏫',
                    content: 'Conecte-se com mentores experientes para orientação.',
                    position: 'bottom'
                },
                {
                    target: 'a[href*="profile.php"]',
                    title: 'Complete seu Perfil ✅',
                    content: 'Verifique sua conta com KYC para desbloquear todas as funcionalidades.',
                    position: 'bottom'
                }
            ];

            const investorSteps = [
                {
                    target: 'a[href*="projects.php"]',
                    title: 'Pipeline de Negócios 💼',
                    content: 'Explore projectos validados e prontos para investimento.',
                    position: 'bottom'
                },
                {
                    target: 'a[href*="investor_dashboard.php"]',
                    title: 'Seu Painel 📈',
                    content: 'Acompanhe seus investimentos e novas oportunidades.',
                    position: 'bottom'
                }
            ];

            const mentorSteps = [
                {
                    target: 'a[href*="mentorship.php"]',
                    title: 'Painel do Mentor 🎓',
                    content: 'Gerencie suas sessões de mentoria e mentorandos.',
                    position: 'bottom'
                }
            ];

            // Combinar passos baseado no tipo de utilizador
            this.steps = [...commonSteps];

            if (userType === 'univ_student' || userType === 'high_student') {
                this.steps.push(...studentSteps);
            } else if (userType === 'investor') {
                this.steps.push(...investorSteps);
            } else if (userType === 'mentor') {
                this.steps.push(...mentorSteps);
            }

            // Passo final comum
            this.steps.push({
                target: 'body',
                title: 'Pronto para Começar! 🚀',
                content: 'Explore a plataforma e não hesite em contactar o suporte se precisar de ajuda.',
                position: 'center'
            });
        }

        start(userType) {
            // Verificar se já completou o tour
            if (localStorage.getItem('aksanti-tour-completed')) {
                return;
            }

            this.defineSteps(userType);
            this.currentStep = 0;
            this.isActive = true;
            this.createOverlay();
            this.showStep();
        }

        createOverlay() {
            // Criar overlay escuro
            this.overlay = document.createElement('div');
            this.overlay.id = 'tour-overlay';
            this.overlay.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.7);
                z-index: 9998;
                transition: opacity 0.3s ease;
            `;
            document.body.appendChild(this.overlay);

            // Criar tooltip
            this.tooltip = document.createElement('div');
            this.tooltip.id = 'tour-tooltip';
            this.tooltip.style.cssText = `
                position: fixed;
                background: var(--secondary-bg);
                border: 2px solid var(--accent-orange);
                border-radius: 16px;
                padding: 1.5rem;
                max-width: 350px;
                z-index: 9999;
                box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
                animation: fadeInScale 0.3s ease;
            `;
            document.body.appendChild(this.tooltip);
        }

        showStep() {
            const step = this.steps[this.currentStep];
            if (!step) return;

            // Atualizar conteúdo do tooltip
            this.tooltip.innerHTML = `
                <div style="margin-bottom: 1rem;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                        <span style="color: var(--accent-orange); font-size: 0.75rem; font-weight: 700;">
                            PASSO ${this.currentStep + 1} DE ${this.steps.length}
                        </span>
                        <button onclick="window.onboardingTour.skip()" style="background: none; border: none; color: var(--text-secondary); cursor: pointer; font-size: 0.85rem;">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <h3 style="font-size: 1.25rem; margin-bottom: 0.75rem; color: var(--text-primary);">
                        ${step.title}
                    </h3>
                    <p style="color: var(--text-secondary); font-size: 0.95rem; line-height: 1.6;">
                        ${step.content}
                    </p>
                </div>
                <div style="display: flex; gap: 0.75rem; justify-content: space-between;">
                    ${this.currentStep > 0 ? `
                        <button onclick="window.onboardingTour.previous()" class="btn-secondary" style="flex: 1; padding: 0.75rem; background: var(--glass-bg); border: 1px solid var(--glass-border); border-radius: 8px; color: var(--text-primary); cursor: pointer; font-weight: 600; transition: all 0.3s;">
                            <i class="fas fa-arrow-left"></i> Anterior
                        </button>
                    ` : ''}
                    <button onclick="window.onboardingTour.next()" class="btn-primary" style="flex: 2; padding: 0.75rem; background: linear-gradient(135deg, var(--accent-orange), var(--accent-gold)); border: none; border-radius: 8px; color: white; cursor: pointer; font-weight: 600; transition: all 0.3s;">
                        ${this.currentStep === this.steps.length - 1 ? 'Concluir' : 'Próximo'} <i class="fas fa-arrow-right"></i>
                    </button>
                </div>
            `;

            // Posicionar tooltip
            this.positionTooltip(step);

            // Destacar elemento alvo
            this.highlightTarget(step.target);
        }

        positionTooltip(step) {
            const target = document.querySelector(step.target);

            if (!target || step.position === 'center') {
                // Centralizar
                this.tooltip.style.top = '50%';
                this.tooltip.style.left = '50%';
                this.tooltip.style.transform = 'translate(-50%, -50%)';
                return;
            }

            const rect = target.getBoundingClientRect();
            const tooltipRect = this.tooltip.getBoundingClientRect();

            switch (step.position) {
                case 'bottom':
                    this.tooltip.style.top = `${rect.bottom + 20}px`;
                    this.tooltip.style.left = `${rect.left + (rect.width / 2) - (tooltipRect.width / 2)}px`;
                    this.tooltip.style.transform = 'none';
                    break;
                case 'top':
                    this.tooltip.style.top = `${rect.top - tooltipRect.height - 20}px`;
                    this.tooltip.style.left = `${rect.left + (rect.width / 2) - (tooltipRect.width / 2)}px`;
                    this.tooltip.style.transform = 'none';
                    break;
                case 'left':
                    this.tooltip.style.top = `${rect.top + (rect.height / 2) - (tooltipRect.height / 2)}px`;
                    this.tooltip.style.left = `${rect.left - tooltipRect.width - 20}px`;
                    this.tooltip.style.transform = 'none';
                    break;
                case 'right':
                    this.tooltip.style.top = `${rect.top + (rect.height / 2) - (tooltipRect.height / 2)}px`;
                    this.tooltip.style.left = `${rect.right + 20}px`;
                    this.tooltip.style.transform = 'none';
                    break;
            }
        }

        highlightTarget(selector) {
            // Remover highlight anterior
            document.querySelectorAll('.tour-highlight').forEach(el => {
                el.classList.remove('tour-highlight');
                el.style.position = '';
                el.style.zIndex = '';
            });

            if (selector === 'body') return;

            const target = document.querySelector(selector);
            if (target) {
                target.classList.add('tour-highlight');
                target.style.position = 'relative';
                target.style.zIndex = '9999';
            }
        }

        next() {
            if (this.currentStep < this.steps.length - 1) {
                this.currentStep++;
                this.showStep();
            } else {
                this.complete();
            }
        }

        previous() {
            if (this.currentStep > 0) {
                this.currentStep--;
                this.showStep();
            }
        }

        skip() {
            if (confirm('Tem certeza que deseja pular o tour? Você pode reiniciá-lo a qualquer momento nas configurações.')) {
                this.complete();
            }
        }

        complete() {
            localStorage.setItem('aksanti-tour-completed', 'true');
            this.cleanup();

            // Mostrar mensagem de conclusão
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'success',
                    title: 'Tour Concluído! 🎉',
                    text: 'Agora você está pronto para explorar a plataforma.',
                    timer: 2000,
                    showConfirmButton: false,
                    background: '#1e293b',
                    color: '#fff'
                });
            }
        }

        cleanup() {
            if (this.overlay) this.overlay.remove();
            if (this.tooltip) this.tooltip.remove();
            document.querySelectorAll('.tour-highlight').forEach(el => {
                el.classList.remove('tour-highlight');
                el.style.position = '';
                el.style.zIndex = '';
            });
            this.isActive = false;
        }

        // Método para reiniciar o tour
        restart() {
            localStorage.removeItem('aksanti-tour-completed');
            location.reload();
        }
    }

    // Adicionar CSS para highlight
    const style = document.createElement('style');
    style.textContent = `
        .tour-highlight {
            box-shadow: 0 0 0 4px var(--accent-orange), 0 0 0 9999px rgba(0, 0, 0, 0.7) !important;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
    `;
    document.head.appendChild(style);

    // Inicializar e exportar globalmente
    window.onboardingTour = new OnboardingTour();

    // Auto-iniciar para novos utilizadors
    window.addEventListener('load', () => {
        // Aguardar 2 segundos após o carregamento
        setTimeout(() => {
            const userType = document.body.getAttribute('data-user-type');
            if (userType && !localStorage.getItem('aksanti-tour-completed')) {
                window.onboardingTour.start(userType);
            }
        }, 2000);
    });
})();
