<?php
/**
 * inclusoes/components/newsletter_section.php
 * Secção Premium de Newsletter Aksanti.
 */
?>
<section class="newsletter-premium-section" id="newsletter">
    <div class="newsletter-container">
        <div class="newsletter-content" data-aos="fade-up">
            <h3 class="newsletter-label">NEWSLETTER</h3>
            <p class="newsletter-text">Subscreva a nossa newsletter para receber as últimas atualizações e notícias.</p>
            
            <form id="newsletterForm" class="newsletter-form">
                <div class="newsletter-input-group">
                    <input type="text" name="name" placeholder="Seu nome" class="newsletter-input" required>
                </div>
                <div class="newsletter-input-group">
                    <input type="email" name="email" placeholder="Seu e-mail" class="newsletter-input" required>
                </div>
                <button type="submit" id="newsletterBtn" class="newsletter-submit-btn">
                    <span>Subscrever</span>
                    <i class="fas fa-paper-plane"></i>
                </button>
            </form>
        </div>
    </div>
</section>

<style>
.newsletter-premium-section {
    padding: 5rem 0;
    background: #000;
    border-top: 1px solid rgba(255,255,255,0.05);
    position: relative;
    overflow: hidden;
}

.newsletter-premium-section::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -10%;
    width: 400px;
    height: 400px;
    background: radial-gradient(circle, rgba(132, 204, 22, 0.05) 0%, transparent 70%);
    filter: blur(60px);
    pointer-events: none;
}

.newsletter-container {
    max-width: 600px;
    margin: 0 auto;
    padding: 0 2rem;
}

.newsletter-label {
    color: #84cc16; /* Verde limão conforme imagem */
    font-family: 'Outfit', sans-serif;
    font-weight: 900;
    font-size: 1.4rem;
    letter-spacing: 1px;
    margin-bottom: 1.2rem;
}

.newsletter-text {
    color: rgba(255,255,255,0.8);
    font-size: 1rem;
    line-height: 1.6;
    margin-bottom: 2.5rem;
}

.newsletter-form {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.newsletter-input-group {
    position: relative;
}

.newsletter-input {
    width: 100%;
    padding: 1.1rem 1.4rem;
    background: #1e293b; /* Fundo escuro conforme imagem */
    border: 1px solid rgba(255,255,255,0.1);
    border-radius: 10px;
    color: #fff;
    font-size: 0.95rem;
    transition: 0.3s;
}

.newsletter-input:focus {
    border-color: #84cc16;
    background: #243044;
    outline: none;
}

.newsletter-submit-btn {
    width: 100%;
    padding: 1.1rem;
    background: #84cc16;
    color: #000;
    border: none;
    border-radius: 10px;
    font-weight: 800;
    font-family: 'Outfit', sans-serif;
    font-size: 1rem;
    cursor: pointer;
    transition: 0.3s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    margin-top: 0.5rem;
}

.newsletter-submit-btn:hover {
    background: #a3e635;
    transform: translateY(-3px);
    box-shadow: 0 10px 20px rgba(132, 204, 22, 0.2);
}

.newsletter-submit-btn:active {
    transform: translateY(-1px);
}

.newsletter-submit-btn.loading {
    opacity: 0.7;
    pointer-events: none;
}
</style>

<script>
(function() {
    const form = document.getElementById('newsletterForm');
    if (!form) return;
    
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        const btn = document.getElementById('newsletterBtn');
        const fd = new FormData(form);
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> A enviar...';
        
        try {
            const baseUrl = window.BASE_URL || './';
            const url = baseUrl + 'interface_programacao/system/subscribe_newsletter.php';
            console.log('Newsletter POST:', url);
            
            const res = await fetch(url, { method: 'POST', body: fd });
            const responseText = await res.text();
            console.log('Newsletter Response:', responseText);
            
            const data = JSON.parse(responseText);
            
            if (data.success) {
                btn.innerHTML = '<i class="fas fa-check"></i> Subscrito!';
                btn.style.background = '#10b981';
                btn.style.color = '#000';
                form.querySelector('input[name="name"]').value = '';
                form.querySelector('input[name="email"]').value = '';
                
                if (typeof showNewsletterModal === 'function') {
                    showNewsletterModal(
                        '✓ Subscrito com Sucesso!',
                        data.message || 'Bem-vindo à nossa comunidade. Receberá as nossas atualizações em breve.',
                        'success',
                        'fa-check-circle'
                    );
                } else { alert(data.message); }
                
                setTimeout(() => {
                    btn.innerHTML = '<span>Subscrever</span><i class="fas fa-paper-plane"></i>';
                    btn.style.background = '#84cc16';
                    btn.style.color = '#000';
                    btn.disabled = false;
                }, 2500);
            } else {
                btn.innerHTML = '<span>Subscrever</span><i class="fas fa-paper-plane"></i>';
                btn.disabled = false;
                
                if (typeof showNewsletterModal === 'function') {
                    const isAlreadySubscribed = data.message && data.message.includes('Já está subscrito');
                    showNewsletterModal(
                        isAlreadySubscribed ? 'Já Subscrito' : 'Erro',
                        data.message || 'Erro ao subscrever',
                        isAlreadySubscribed ? 'warning' : 'error',
                        isAlreadySubscribed ? 'fa-info-circle' : 'fa-exclamation-circle'
                    );
                } else { alert(data.message); }
            }
        } catch(err) {
            console.error('Newsletter Error:', err);
            btn.innerHTML = '<span>Subscrever</span><i class="fas fa-paper-plane"></i>';
            btn.disabled = false;
            
            if (typeof showNewsletterModal === 'function') {
                showNewsletterModal(
                    'Erro na Subscrição',
                    err.message || 'Não foi possível subscrever. Tenta novamente mais tarde.',
                    'error',
                    'fa-exclamation-circle'
                );
            } else { alert(err.message || 'Erro ao subscrever'); }
        }
    });
})();
</script>
