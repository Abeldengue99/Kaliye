<?php
/**
 * inclusoes/components/evaluation_modal.php
 * Modal premium de avaliação da plataforma (Aksanti Feedback)
 * RESTRITO: Apenas para utilizadores logados com experiência de uso.
 */

// Só renderiza se o utilizador estiver logado
if (isset($_SESSION['user_id'])): 
?>
<div id="evaluationModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; width: 100vw; height: 100vh; background: rgba(0, 0, 0, 0.85); backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px); z-index: 99999; align-items: center; justify-content: center;">
    <div class="evaluation-card glass-premium animate-pop">
        <button class="modal-close-btn" onclick="closeEvaluationModal()">&times;</button>
        
        <!-- Passo 1: Avaliação por Estrelas -->
        <div id="evalStep1">
            <div class="evaluation-header">
                <div class="evaluation-icon-box">
                    <i class="fas fa-star"></i>
                </div>
                <h2>Gostas da KALIYE?</h2>
                <p>A tua opinião ajuda-nos a melhorar a plataforma para todos.</p>
            </div>

            <div class="star-rating">
                <input type="radio" id="star5" name="rating" value="5" onclick="handleStarClick(5)" /><label for="star5" title="5 estrelas"><i class="fas fa-star"></i></label>
                <input type="radio" id="star4" name="rating" value="4" onclick="handleStarClick(4)" /><label for="star4" title="4 estrelas"><i class="fas fa-star"></i></label>
                <input type="radio" id="star3" name="rating" value="3" onclick="handleStarClick(3)" /><label for="star3" title="3 estrelas"><i class="fas fa-star"></i></label>
                <input type="radio" id="star2" name="rating" value="2" onclick="handleStarClick(2)" /><label for="star2" title="2 estrelas"><i class="fas fa-star"></i></label>
                <input type="radio" id="star1" name="rating" value="1" onclick="handleStarClick(1)" /><label for="star1" title="1 estrela"><i class="fas fa-star"></i></label>
            </div>
            
            <div id="suggestionArea" style="display: block;" class="animate-fade-in">
                <p style="color: #f7941d; font-size: 0.85rem; font-weight: 700; margin-bottom: 1rem;">O que achou da plataforma?</p>
                <textarea id="negativeSuggestion" name="suggestion" placeholder="Deixe um comentário sobre a sua experiência (opcional)..." rows="3" style="margin-bottom: 1rem;"></textarea>
            </div>

            <div class="evaluation-actions">
                <button type="button" class="btn-later" onclick="closeEvaluationModal()">Cancelar</button>
                <button type="button" id="btnSubmitEval" class="btn-submit-feedback" onclick="submitAksantiEvaluation()">
                    <i class="fas fa-paper-plane"></i> Enviar Avaliação
                </button>
            </div>
        </div>

        <!-- Passo 3: Agradecimento Final -->
        <div id="evalThanks" style="display: none;" class="animate-pop">
            <div class="evaluation-icon-box" style="background: rgba(16, 185, 129, 0.1); color: #10b981;">
                <i class="fas fa-check-circle"></i>
            </div>
            <h2 id="thanksTitle">Obrigado!</h2>
            <p id="thanksMsg">O teu feedback foi registado com sucesso. Juntos construímos uma KALIYE melhor.</p>
            <button type="button" class="btn-submit-feedback" style="background: var(--surface-10); color: #fff;" onclick="closeEvaluationModal()">Fechar</button>
        </div>
    </div>
</div>

<style>
.evaluation-card {
    width: 100%;
    max-width: 450px;
    padding: 2.5rem;
    position: relative;
    text-align: center;
    border-radius: 24px;
}

.evaluation-icon-box {
    width: 64px;
    height: 64px;
    background: rgba(247, 148, 29, 0.1);
    color: var(--elite-orange, #f7941d);
    border-radius: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.8rem;
    margin: 0 auto 1.5rem;
}

.evaluation-header h2 {
    font-family: 'Outfit', sans-serif;
    font-weight: 800;
    font-size: 1.6rem;
    margin-bottom: 0.75rem;
    color: #fff;
}

.evaluation-header p {
    color: rgba(255,255,255,0.6);
    font-size: 0.9rem;
    line-height: 1.5;
    margin-bottom: 2rem;
}

.star-rating {
    display: flex;
    flex-direction: row-reverse;
    justify-content: center;
    gap: 0.5rem;
    margin-bottom: 2rem;
}

.star-rating input { display: none; }

.star-rating label {
    cursor: pointer;
    font-size: 2.2rem;
    color: rgba(255,255,255,0.1);
    transition: 0.2s;
}

.star-rating label:hover,
.star-rating label:hover ~ label,
.star-rating input:checked ~ label {
    color: #f7941d;
}

.evaluation-card textarea {
    width: 100%;
    background: rgba(255,255,255,0.03);
    border: 1px solid rgba(255,255,255,0.08);
    border-radius: 14px;
    padding: 1rem;
    color: #fff;
    font-size: 0.9rem;
    outline: none;
    resize: none;
    margin-bottom: 2rem;
    transition: 0.3s;
    font-family: inherit;
}

.evaluation-card textarea:focus {
    border-color: rgba(247, 148, 29, 0.4);
    background: rgba(255,255,255,0.05);
}

.modal-close-btn {
    position: absolute;
    top: 1.25rem;
    right: 1.25rem;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: rgba(255,255,255,0.05);
    border: none;
    color: rgba(255,255,255,0.5);
    font-size: 1.2rem;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: 0.3s;
    z-index: 10;
}

.modal-close-btn:hover {
    background: rgba(255,255,255,0.15);
    color: #fff;
    transform: rotate(90deg);
}

.evaluation-actions {
    display: flex;
    gap: 1rem;
}

.btn-later {
    flex: 1;
    background: rgba(255,255,255,0.05);
    border: 1px solid rgba(255,255,255,0.1);
    color: #fff;
    padding: 0.9rem;
    border-radius: 12px;
    font-weight: 700;
    cursor: pointer;
    transition: 0.2s;
}

.btn-later:hover { background: rgba(255,255,255,0.1); }

.btn-submit-feedback {
    flex: 2;
    background: #f7941d;
    color: #000;
    border: none;
    padding: 0.9rem;
    border-radius: 12px;
    font-weight: 800;
    cursor: pointer;
    transition: 0.3s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.75rem;
}

.btn-submit-feedback:hover {
    background: #e8830a;
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(247, 148, 29, 0.3);
}

.animate-pop {
    animation: modalPop 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

@keyframes modalPop {
    from { transform: scale(0.8); opacity: 0; }
    to { transform: scale(1); opacity: 1; }
}
</style>

<script>
function openEvaluationModal() {
    const modal = document.getElementById('evaluationModal');
    if (modal) {
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden'; // impede scroll da página
    }
}

function closeEvaluationModal() {
    const modal = document.getElementById('evaluationModal');
    if (modal) modal.style.display = 'none';
    document.body.style.overflow = ''; // restaura scroll
    localStorage.setItem('aksanti_evaluation_dismissed', Date.now());
}

function handleStarClick(rating) {
    // A caixa de comentário agora está sempre visível
}

async function submitAksantiEvaluation() {
    const btn = document.getElementById('btnSubmitEval');
    const ratingInput = document.querySelector('input[name="rating"]:checked');
    
    if (!ratingInput) {
        Swal.fire({ icon: 'warning', title: 'Ups!', text: 'Por favor, seleciona uma classificação.', background: '#1e293b', color: '#fff' });
        return;
    }

    const rating = parseInt(ratingInput.value);
    const suggestion = document.getElementById('negativeSuggestion').value.trim();

    btn.disabled = true;
    const originalContent = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> A processar...';

    const formData = new FormData();
    formData.append('rating', rating);
    formData.append('comment', suggestion);

    try {
        const res = await fetch('<?php echo $base_url; ?>interface_programacao/system/submit_evaluation.php', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();
        
        if (data.success) {
            localStorage.setItem('aksanti_evaluated', 'true');
            
            // Switch to Thanks Step
            document.getElementById('evalStep1').style.display = 'none';
            document.getElementById('evalThanks').style.display = 'block';
            
            if (rating <= 2) {
                document.getElementById('thanksTitle').innerText = 'Sugestão Recebida!';
                document.getElementById('thanksMsg').innerText = 'Obrigado pela tua honestidade e sugestão. A nossa equipa irá analisar como podemos melhorar este ponto para ti.';
            }
        } else {
            btn.disabled = false;
            btn.innerHTML = originalContent;
            Swal.fire({ icon: 'error', title: 'Erro', text: data.message, background: '#1e293b', color: '#fff' });
        }
    } catch (err) {
        btn.disabled = false;
        btn.innerHTML = originalContent;
        console.error(err);
    }
}

// Auto-trigger removido a pedido do utilizador.
// O modal será aberto apenas por ação manual (clique no botão de feedback).
</script>
<?php endif; ?>
