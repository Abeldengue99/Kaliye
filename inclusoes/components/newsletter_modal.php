<?php
/**
 * inclusoes/components/newsletter_modal.php
 * Modal elegante e responsivo para mensagens de newsletter
 */
?>

<!-- Newsletter Response Modal -->
<div id="newsletterResponseModal" class="newsletter-modal-overlay">
    <div class="newsletter-modal-content">
        <!-- Close Button -->
        <button class="newsletter-modal-close" onclick="closeNewsletterModal()">
            <i class="fas fa-times"></i>
        </button>

        <!-- Modal Icon & Title -->
        <div class="newsletter-modal-header">
            <div id="modalIcon" class="newsletter-modal-icon">
                <i class="fas fa-envelope"></i>
            </div>
            <h2 id="modalTitle" class="newsletter-modal-title">Subscrição</h2>
        </div>

        <!-- Modal Message -->
        <div class="newsletter-modal-body">
            <p id="modalMessage" class="newsletter-modal-message">
                Processando sua subscrição...
            </p>
        </div>

        <!-- Modal Footer with Action -->
        <div class="newsletter-modal-footer">
            <button class="newsletter-modal-btn" onclick="closeNewsletterModal()">
                Fechar
            </button>
        </div>
    </div>
</div>

<style>
    /* Newsletter Modal Overlay */
    .newsletter-modal-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.7);
        backdrop-filter: blur(5px);
        z-index: 9999;
        animation: fadeInOverlay 0.3s ease-out;
        justify-content: center;
        align-items: center;
    }

    .newsletter-modal-overlay.show {
        display: flex;
    }

    @keyframes fadeInOverlay {
        from {
            opacity: 0;
        }
        to {
            opacity: 1;
        }
    }

    /* Modal Content */
    .newsletter-modal-content {
        background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
        border-radius: 20px;
        padding: 3rem 2.5rem;
        max-width: 500px;
        width: 90%;
        position: relative;
        box-shadow: 0 25px 50px rgba(0, 0, 0, 0.3), 0 0 0 1px rgba(255, 255, 255, 0.1);
        animation: slideInModal 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
        border: 1px solid rgba(255, 255, 255, 0.05);
    }

    @keyframes slideInModal {
        from {
            opacity: 0;
            transform: translateY(30px) scale(0.95);
        }
        to {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }

    /* Close Button */
    .newsletter-modal-close {
        position: absolute;
        top: 1.5rem;
        right: 1.5rem;
        background: rgba(255, 255, 255, 0.1);
        border: 1px solid rgba(255, 255, 255, 0.2);
        color: #fff;
        width: 40px;
        height: 40px;
        border-radius: 50%;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
        transition: all 0.3s ease;
    }

    .newsletter-modal-close:hover {
        background: rgba(255, 255, 255, 0.2);
        border-color: rgba(255, 255, 255, 0.3);
        transform: rotate(90deg);
    }

    /* Modal Header */
    .newsletter-modal-header {
        text-align: center;
        margin-bottom: 2rem;
    }

    .newsletter-modal-icon {
        width: 80px;
        height: 80px;
        margin: 0 auto 1.5rem;
        background: rgba(247, 148, 29, 0.15);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2.5rem;
        color: #f7941d;
        animation: bounceIcon 0.6s ease-in-out;
    }

    @keyframes bounceIcon {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.1); }
    }

    .newsletter-modal-icon.success {
        background: rgba(16, 185, 129, 0.15);
        color: #10b981;
    }

    .newsletter-modal-icon.error {
        background: rgba(239, 68, 68, 0.15);
        color: #ef4444;
    }

    .newsletter-modal-icon.warning {
        background: rgba(245, 158, 11, 0.15);
        color: #f59e0b;
    }

    .newsletter-modal-title {
        font-family: 'Outfit', sans-serif;
        font-size: 1.8rem;
        font-weight: 800;
        color: #fff;
        margin: 0;
        letter-spacing: -0.5px;
    }

    /* Modal Body */
    .newsletter-modal-body {
        margin-bottom: 2rem;
    }

    .newsletter-modal-message {
        font-size: 1rem;
        color: rgba(255, 255, 255, 0.85);
        line-height: 1.6;
        margin: 0;
        text-align: center;
    }

    /* Modal Footer */
    .newsletter-modal-footer {
        display: flex;
        gap: 1rem;
        justify-content: center;
    }

    .newsletter-modal-btn {
        flex: 1;
        padding: 0.95rem 2rem;
        background: #f7941d;
        color: #000;
        border: none;
        border-radius: 10px;
        font-weight: 800;
        font-family: 'Outfit', sans-serif;
        font-size: 0.95rem;
        cursor: pointer;
        transition: all 0.3s ease;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .newsletter-modal-btn:hover {
        background: #ffb347;
        transform: translateY(-2px);
        box-shadow: 0 10px 25px rgba(247, 148, 29, 0.3);
    }

    .newsletter-modal-btn:active {
        transform: translateY(0);
    }

    /* Status Variants */
    .newsletter-modal-content.status-success {
        border-color: rgba(16, 185, 129, 0.3);
    }

    .newsletter-modal-content.status-error {
        border-color: rgba(239, 68, 68, 0.3);
    }

    .newsletter-modal-content.status-warning {
        border-color: rgba(245, 158, 11, 0.3);
    }

    /* Responsive */
    @media (max-width: 600px) {
        .newsletter-modal-content {
            padding: 2rem 1.5rem;
            margin: 1rem;
        }

        .newsletter-modal-title {
            font-size: 1.4rem;
        }

        .newsletter-modal-icon {
            width: 60px;
            height: 60px;
            font-size: 2rem;
        }

        .newsletter-modal-message {
            font-size: 0.9rem;
        }

        .newsletter-modal-btn {
            padding: 0.8rem 1.5rem;
            font-size: 0.85rem;
        }
    }
</style>

<script>
    // Global functions for newsletter modal
    function showNewsletterModal(title, message, type = 'info', icon = 'fa-envelope') {
        const modal = document.getElementById('newsletterResponseModal');
        const titleEl = document.getElementById('modalTitle');
        const messageEl = document.getElementById('modalMessage');
        const iconEl = document.getElementById('modalIcon');
        const contentEl = modal.querySelector('.newsletter-modal-content');

        // Set content
        titleEl.textContent = title;
        messageEl.textContent = message;
        iconEl.innerHTML = `<i class="fas ${icon}"></i>`;

        // Remove all status classes
        contentEl.classList.remove('status-success', 'status-error', 'status-warning');
        iconEl.classList.remove('success', 'error', 'warning');

        // Add status class
        if (type === 'success') {
            contentEl.classList.add('status-success');
            iconEl.classList.add('success');
            iconEl.innerHTML = '<i class="fas fa-check-circle"></i>';
        } else if (type === 'error') {
            contentEl.classList.add('status-error');
            iconEl.classList.add('error');
            iconEl.innerHTML = '<i class="fas fa-exclamation-circle"></i>';
        } else if (type === 'warning') {
            contentEl.classList.add('status-warning');
            iconEl.classList.add('warning');
            iconEl.innerHTML = '<i class="fas fa-info-circle"></i>';
        }

        // Show modal
        modal.classList.add('show');
    }

    function closeNewsletterModal() {
        const modal = document.getElementById('newsletterResponseModal');
        modal.classList.remove('show');
    }

    // Close modal when clicking outside
    document.addEventListener('click', function(event) {
        const modal = document.getElementById('newsletterResponseModal');
        if (event.target === modal) {
            closeNewsletterModal();
        }
    });

    // Close modal with Escape key
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            closeNewsletterModal();
        }
    });
</script>
