// Chat em Tempo Real - Aksanti Referências
// Sistema de mensagens com polling AJAX e interface moderna

class RealtimeChat {
    constructor() {
        this.pollInterval = 5000; // 5 segundos
        this.currentConversation = null;
        this.lastMessageId = 0;
        this.isTyping = false;
        this.typingTimeout = null;
        this.init();
    }

    init() {
        this.attachEventListeners();
        this.startPolling();
    }

    attachEventListeners() {
        // Detectar digitação
        const messageInput = document.getElementById('message-input');
        if (messageInput) {
            messageInput.addEventListener('input', () => this.handleTyping());
            messageInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    this.sendMessage();
                }
            });
        }

        // Botão de enviar
        const sendBtn = document.getElementById('send-message-btn');
        if (sendBtn) {
            sendBtn.addEventListener('click', () => this.sendMessage());
        }
    }

    startPolling() {
        // Buscar novas mensagens periodicamente
        setInterval(() => {
            if (this.currentConversation) {
                this.fetchNewMessages();
            }
            this.updateConversationList();
        }, this.pollInterval);
    }

    async fetchNewMessages() {
        try {
            const response = await fetch(`servicos/social/get_new_messages.php?conversation_id=${this.currentConversation}&last_id=${this.lastMessageId}`);
            const data = await response.json();

            // Handle New Messages
            if (data.messages && data.messages.length > 0) {
                data.messages.forEach(msg => this.appendMessage(msg, msg.is_sent));
                this.lastMessageId = data.messages[data.messages.length - 1].message_id;
                this.scrollToBottom();
                this.playNotificationSound();
            }

            // Handle Read Receipts
            if (data.receipts) {
                this.updateMessageStatus(data.receipts);
            }

        } catch (error) {
            console.error('Erro ao buscar mensagens:', error);
        }
    }

    updateMessageStatus(receipts) {
        // receipts.max_read, receipts.max_delivered
        if (!receipts) return;

        const sentMessages = document.querySelectorAll('.message.sent');
        sentMessages.forEach(el => {
            const msgId = parseInt(el.getAttribute('data-id'));
            if (!msgId) return;

            const icon = el.querySelector('.msg-status-icon');
            if (!icon) return;

            if (receipts.max_read >= msgId) {
                icon.className = 'fas fa-check-double msg-status-icon';
                icon.style.color = '#3b82f6'; // Blue
            } else if (receipts.max_delivered >= msgId) {
                icon.className = 'fas fa-check-double msg-status-icon';
                icon.style.color = 'rgba(255,255,255,0.7)'; // Gray Double
            }
            // else it stays as single check (sent)
        });
    }

    async sendMessage() {
        const input = document.getElementById('message-input');
        const content = input.value.trim();

        if (!content || !this.currentConversation) return;

        try {
            const formData = new FormData();
            formData.append('receiver_id', this.currentConversation);
            formData.append('content', content);

            const response = await fetch('servicos/social/send_message.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                input.value = '';
                // Add status to message object for consistent rendering
                const msgWithStatus = { ...data.message, status: 'sent', is_sent: true };
                this.appendMessage(msgWithStatus, true);
                this.scrollToBottom();
                this.stopTyping();
            }
        } catch (error) {
            console.error('Erro ao enviar mensagem:', error);
        }
    }

    appendMessage(message, isSent = false) {
        const messagesContainer = document.getElementById('messages-container');
        if (!messagesContainer) return;

        const messageEl = document.createElement('div');
        messageEl.className = `message ${isSent ? 'sent' : 'received'} animate-slide-up`;
        messageEl.setAttribute('data-id', message.message_id);

        messageEl.style.cssText = `
            display: flex;
            gap: 0.75rem;
            margin-bottom: 1rem;
            ${isSent ? 'flex-direction: row-reverse;' : ''}
        `;

        const avatar = `
            <div style="width: 36px; height: 36px; border-radius: 50%; overflow: hidden; flex-shrink: 0; border: 2px solid var(--accent-orange);">
                <img src="${message.profile_pic || 'recursos/images/default_profile.png'}" style="width: 100%; height: 100%; object-fit: cover;">
            </div>
        `;

        // Determine Status Icon
        let statusIcon = '';
        if (isSent) {
            if (message.status === 'read') {
                statusIcon = '<i class="fas fa-check-double msg-status-icon" style="color: #3b82f6; font-size: 0.7rem;"></i>';
            } else if (message.status === 'delivered') {
                statusIcon = '<i class="fas fa-check-double msg-status-icon" style="color: rgba(255,255,255,0.7); font-size: 0.7rem;"></i>';
            } else {
                statusIcon = '<i class="fas fa-check msg-status-icon" style="color: rgba(255,255,255,0.7); font-size: 0.7rem;"></i>';
            }
        }

        const bubble = `
            <div style="max-width: 70%; background: ${isSent ? 'var(--accent-orange)' : 'var(--glass-bg)'}; padding: 0.75rem 1rem; border-radius: 16px; ${isSent ? 'border-bottom-right-radius: 4px;' : 'border-bottom-left-radius: 4px;'}">
                <p style="margin: 0; color: ${isSent ? 'white' : 'var(--text-primary)'}; font-size: 0.95rem; line-height: 1.4; word-wrap: break-word;">
                    ${this.escapeHtml(message.content)}
                </p>
                <div style="display: flex; justify-content: flex-end; align-items: center; gap: 4px; margin-top: 0.25rem;">
                    <span style="font-size: 0.7rem; color: ${isSent ? 'rgba(255,255,255,0.7)' : 'var(--text-secondary)'};">
                        ${this.formatTime(message.created_at)}
                    </span>
                    ${isSent ? statusIcon : ''}
                </div>
            </div>
        `;

        messageEl.innerHTML = avatar + bubble;
        messagesContainer.appendChild(messageEl);
    }

    handleTyping() {
        if (!this.isTyping) {
            this.isTyping = true;
            this.sendTypingStatus(true);
        }

        clearTimeout(this.typingTimeout);
        this.typingTimeout = setTimeout(() => {
            this.stopTyping();
        }, 3000);
    }

    stopTyping() {
        if (this.isTyping) {
            this.isTyping = false;
            this.sendTypingStatus(false);
        }
    }

    async sendTypingStatus(isTyping) {
        if (!this.currentConversation) return;

        try {
            const formData = new FormData();
            formData.append('receiver_id', this.currentConversation);
            formData.append('is_typing', isTyping ? '1' : '0');

            await fetch('servicos/social/update_typing_status.php', {
                method: 'POST',
                body: formData
            });
        } catch (error) {
            console.error('Erro ao atualizar status de digitação:', error);
        }
    }

    async updateConversationList() {
        try {
            const response = await fetch('servicos/social/get_conversations.php');
            const data = await response.json();

            if (data.conversations) {
                this.renderConversationList(data.conversations);
            }
        } catch (error) {
            console.error('Erro ao atualizar lista de conversas:', error);
        }
    }

    renderConversationList(conversations) {
        const listContainer = document.getElementById('conversations-list');
        if (!listContainer) return;

        listContainer.innerHTML = '';

        conversations.forEach(conv => {
            const item = document.createElement('div');
            item.className = 'conversation-item transition-smooth';
            item.style.cssText = `
                padding: 1rem;
                border-bottom: 1px solid var(--glass-border);
                cursor: pointer;
                transition: all 0.3s ease;
                ${conv.unread_count > 0 ? 'background: rgba(245, 158, 11, 0.05);' : ''}
            `;

            item.innerHTML = `
                <div style="display: flex; gap: 0.75rem; align-items: center;">
                    <div style="position: relative;">
                        <img src="${conv.profile_pic || 'recursos/images/default_profile.png'}" style="width: 48px; height: 48px; border-radius: 50%; object-fit: cover; border: 2px solid var(--accent-orange);">
                        ${conv.is_online ? '<span style="position: absolute; bottom: 0; right: 0; width: 12px; height: 12px; background: #10b981; border: 2px solid var(--secondary-bg); border-radius: 50%;"></span>' : ''}
                    </div>
                    <div style="flex: 1; min-width: 0;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.25rem;">
                            <strong style="font-size: 0.95rem; color: var(--text-primary);">${this.escapeHtml(conv.name)}</strong>
                            <span style="font-size: 0.7rem; color: var(--text-secondary);">${this.formatTime(conv.last_message_time)}</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <p style="margin: 0; font-size: 0.85rem; color: var(--text-secondary); overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                ${conv.is_typing ? '<i class="fas fa-ellipsis-h"></i> digitando...' : this.escapeHtml(conv.last_message || 'Sem mensagens')}
                            </p>
                            ${conv.unread_count > 0 ? `<span style="background: var(--accent-orange); color: white; font-size: 0.7rem; padding: 2px 6px; border-radius: 10px; font-weight: 700;">${conv.unread_count}</span>` : ''}
                        </div>
                    </div>
                </div>
            `;

            item.addEventListener('click', () => this.openConversation(conv.user_id));
            item.addEventListener('mouseenter', () => {
                item.style.background = 'var(--glass-bg)';
            });
            item.addEventListener('mouseleave', () => {
                item.style.background = conv.unread_count > 0 ? 'rgba(245, 158, 11, 0.05)' : 'transparent';
            });

            listContainer.appendChild(item);
        });
    }

    openConversation(userId) {
        this.currentConversation = userId;
        this.lastMessageId = 0;
        this.loadConversation();
    }

    async loadConversation() {
        // Implementar carregamento de conversa
        const messagesContainer = document.getElementById('messages-container');
        if (messagesContainer) {
            messagesContainer.innerHTML = '<div class="skeleton skeleton-text"></div>'.repeat(5);
        }

        try {
            const response = await fetch(`servicos/social/get_messages.php?user_id=${this.currentConversation}`);
            const data = await response.json();

            if (messagesContainer) {
                messagesContainer.innerHTML = '';
                if (data.messages) {
                    data.messages.forEach(msg => this.appendMessage(msg, msg.is_sent));
                    if (data.messages.length > 0) {
                        this.lastMessageId = data.messages[data.messages.length - 1].message_id;
                    }
                }
                this.scrollToBottom();
            }
        } catch (error) {
            console.error('Erro ao carregar conversa:', error);
        }
    }

    scrollToBottom() {
        const container = document.getElementById('messages-container');
        if (container) {
            container.scrollTop = container.scrollHeight;
        }
    }

    playNotificationSound() {
        // Som sutil de notificação
        const audio = new Audio('data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgodDbq2EcBj+a2/LDciUFLIHO8tiJNwgZaLvt559NEAxQp+PwtmMcBjiR1/LMeSwFJHfH8N2QQAoUXrTp66hVFApGn+DyvmwhBSuBzvLZiTYIGWi77eefTRAMUKfj8LZjHAY4ktfyzHksBSR3x/DdkEAKFF606+uoVRQKRp/g8r5sIQUrg87y2Ik2CBlou+3nn00QDFCn4/C2YxwGOJLX8sx5LAUkd8fw3ZBAC');
        audio.volume = 0.3;
        audio.play().catch(() => { }); // Ignorar erros de autoplay
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    formatTime(timestamp) {
        if (!timestamp) return '';
        const date = new Date(timestamp);
        const now = new Date();
        const diff = now - date;

        if (diff < 60000) return 'Agora';
        if (diff < 3600000) return `${Math.floor(diff / 60000)}min`;
        if (diff < 86400000) return `${Math.floor(diff / 3600000)}h`;
        return date.toLocaleDateString('pt-PT', { day: '2-digit', month: '2-digit' });
    }
}

// Inicializar apenas na página de mensagens
if (window.location.pathname.includes('messages.php')) {
    window.realtimeChat = new RealtimeChat();
}

