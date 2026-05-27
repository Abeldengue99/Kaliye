<?php
/**
 * Component: Mentorship Modals 
 */
?>

<!-- Modal: Add Task -->
<div id="addTaskModal" class="modal">
    <div class="modal-content">
        <button onclick="closeModal('addTaskModal')" class="modal-close-btn"><i class="fas fa-times"></i></button>
        <h3><i class="fas fa-tasks" style="color: #f7941d;"></i> Atribuir Nova Tarefa</h3>
        <form id="addTaskForm">
            <div class="form-group">
                <label>Estudante Mentorado</label>
                <select name="student_id" id="task_student_id" required>
                    <option value="">Selecionar Estudante...</option>
                </select>
            </div>
            <div class="form-group">
                <label>Título da Tarefa</label>
                <input type="text" name="task_name" placeholder="Ex: Finalizar Pitch Deck v1" required>
            </div>
            <div class="form-group">
                <label>Instruções Detalhadas</label>
                <textarea name="description" placeholder="Descreva o que o estudante deve realizar..." rows="3"></textarea>
            </div>
            <div class="form-group">
                <label>Prazo de Entrega</label>
                <input type="date" name="deadline">
            </div>
            <button type="submit" class="btn-primary-small" style="width: 100%;">
                <i class="fas fa-paper-plane"></i> Enviar Tarefa
            </button>
        </form>
    </div>
</div>

<!-- Modal: Add Slot (Agenda) -->
<div id="addSlotModal" class="modal">
    <div class="modal-content" style="max-width: 600px;">
        <button onclick="closeModal('addSlotModal')" class="modal-close-btn"><i class="fas fa-times"></i></button>
        <h3><i class="fas fa-calendar-plus" style="color: #f7941d;"></i> Disponibilizar Horário</h3>
        <form id="addSlotForm">
            <div class="form-group">
                <label>Título/Pauta da Sessão</label>
                <input type="text" name="title" placeholder="Ex: Orientação de Negócios" required>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label>Categoria</label>
                    <select name="category">
                        <option value="Carreira">Carreira</option>
                        <option value="Tecnologia">Tecnologia</option>
                        <option value="Negócios">Negócios</option>
                        <option value="Soft Skills">Soft Skills</option>
                        <option value="Outro">Outro</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Duração</label>
                    <select name="duration">
                        <option value="15">15 min</option>
                        <option value="30">30 min</option>
                        <option value="45">45 min</option>
                        <option value="60" selected>1 hora</option>
                        <option value="90">1h 30min</option>
                        <option value="120">2 horas</option>
                        <option value="180">3 horas</option>
                        <option value="240">4 horas</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label>Estudante Específico (Opcional)</label>
                <select name="target_user_id" id="participantSelect">
                    <option value="">-- Aberto para qualquer mentorado --</option>
                </select>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label>Início</label>
                    <input type="datetime-local" name="start_time" required>
                </div>
                <div class="form-group">
                    <label>Fim</label>
                    <input type="datetime-local" name="end_time" required>
                </div>
            </div>

            <div style="background: rgba(16, 185, 129, 0.05); padding: 1.25rem; border-radius: 16px; border: 1px solid rgba(16, 185, 129, 0.1); margin: 0.5rem 0;">
                <p style="margin: 0; font-size: 0.82rem; color: #10b981; line-height: 1.5;">
                    <i class="fas fa-video-slash" style="margin-right: 8px;"></i> 
                    <strong>Link Automático:</strong> Uma sala de videochamada segura será gerada e enviada ao participante.
                </p>
            </div>

            <button type="submit" class="btn-primary-small" style="width: 100%;">
                <i class="fas fa-save"></i> Confirmar Horário
            </button>
        </form>
    </div>
</div>

<!-- Modal: Feedback -->
<div id="feedbackModal" class="modal">
    <div class="modal-content">
        <button onclick="closeModal('feedbackModal')" class="modal-close-btn"><i class="fas fa-times"></i></button>
        <div style="text-align: center; margin-bottom: 2rem;">
            <div style="width: 60px; height: 60px; background: rgba(251, 191, 36, 0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem; color: #fbbf24; font-size: 1.5rem;">
                <i class="fas fa-star"></i>
            </div>
            <h3 style="margin: 0; justify-content: center;">Avaliar Experiência</h3>
            <p style="color: var(--surface-40); font-size: 0.9rem; margin-top: 0.5rem;">Como foi a sua sessão de mentoria?</p>
        </div>

        <form id="feedbackForm">
            <input type="hidden" name="booking_id" id="feedback_booking_id">
            <div style="display: flex; gap: 1rem; margin-bottom: 2rem; justify-content: center; font-size: 2rem;">
                <?php for($i=1; $i<=5; $i++): ?>
                    <i class="far fa-star star-rating" data-value="<?php echo $i; ?>" style="cursor: pointer; color: var(--surface-10); transition: all 0.2s;"></i>
                <?php endfor; ?>
            </div>
            <input type="hidden" name="rating" id="rating_input" required>
            
            <div class="form-group">
                <label>O seu comentário</label>
                <textarea name="notes" rows="3" placeholder="O que mais gostou ou o que podemos melhorar?"></textarea>
            </div>
            
            <button type="submit" class="btn-primary-small" style="width: 100%;">
                Enviar Avaliação
            </button>
        </form>
    </div>
</div>

<!-- Modal: Request Mentorship -->
<div id="requestMentorshipModal" class="modal">
    <div class="modal-content" style="max-width: 650px; padding: 0; overflow: hidden;">
        <button onclick="closeModal('requestMentorshipModal')" class="modal-close-btn" style="z-index: 10;"><i class="fas fa-times"></i></button>
        
        <div id="mentorSlotsHeader" style="background: linear-gradient(135deg, rgba(247, 148, 29, 0.15), rgba(0,0,0,0)); padding: 3rem 2rem 2rem; text-align: center; border-bottom: 1px solid var(--surface-5); position: relative;">
            <div id="mentorPic" style="width: 100px; height: 100px; border-radius: 24px; border: 3px solid #f7941d; margin: 0 auto 1.5rem; overflow: hidden; background: #000; box-shadow: 0 15px 30px rgba(0,0,0,0.4);">
                <i class="fas fa-user-tie" style="font-size: 3rem; line-height: 100px; color: var(--surface-20);"></i>
            </div>
            <a id="mentorProfileLink" href="#" style="text-decoration: none;">
                <h3 id="mentorSlotsName" style="margin: 0; color: white; justify-content: center; font-size: 1.5rem;">Nome do Mentor</h3>
            </a>
            <p id="mentorSlotsSpecialty" style="margin: 8px 0 0; color: #f7941d; font-size: 0.95rem; font-weight: 600;">Especialidade</p>
        </div>
        
        <div style="padding: 2rem;">
            <h4 style="color: white; margin: 0 0 1.5rem 0; display: flex; align-items: center; gap: 0.75rem;">
                <i class="fas fa-calendar-check" style="color: #f7941d;"></i> Horários Disponíveis
            </h4>
            <div id="mentorAvailableSlotsList" class="vertical-list" style="max-height: 350px; overflow-y: auto; padding-right: 1rem; scroll-behavior: smooth;">
                <!-- Content loaded via JS -->
            </div>
        </div>
    </div>
</div>

<!-- Modal: Offer Mentorship -->
<div id="offerMentorshipModal" class="modal">
    <div class="modal-content">
        <button onclick="closeModal('offerMentorshipModal')" class="modal-close-btn"><i class="fas fa-times"></i></button>
        <h3><i class="fas fa-handshake" style="color: #f7941d;"></i> Oferecer Mentoria</h3>
        
        <div id="offerStudentHeader" style="text-align: center; margin-bottom: 2rem; background: rgba(255,255,255,0.02); padding: 1.5rem; border-radius: 20px;">
            <div id="offerStudentPic" style="width: 70px; height: 70px; border-radius: 50%; border: 2px solid #f7941d; margin: 0 auto 1rem; overflow: hidden; background: #000;">
                <i class="fas fa-user-graduate" style="font-size: 2rem; line-height: 70px; color: var(--surface-20);"></i>
            </div>
            <h4 id="offerStudentName" style="margin: 0; color: white;">Nome do Estudante</h4>
            <p id="offerStudentSpecialty" style="margin: 5px 0 0; color: #f7941d; font-size: 0.85rem;">Especialidade</p>
        </div>

        <form id="offerMentorshipForm">
            <input type="hidden" name="student_id" id="offer_student_id">
            
            <div class="form-group">
                <label>Selecione um Horário Disponível</label>
                <select name="slot_id" id="offerSlotSelect" required>
                    <option value="">Carregando seus horários...</option>
                </select>
            </div>

            <div class="form-group">
                <label>Mensagem de Convite</label>
                <textarea name="message" rows="3" placeholder="Olá! Gostaria de ajudar com o seu projeto..."></textarea>
            </div>

            <button type="submit" class="btn-primary-small" style="width: 100%;">
                Enviar Proposta de Mentoria
            </button>
        </form>
    </div>
</div>

<!-- Modal: Project Details -->
<div id="projectDetailsModal" class="modal">
    <div class="modal-content" style="max-width: 850px; padding: 0;">
        <button onclick="closeModal('projectDetailsModal')" class="modal-close-btn" style="z-index: 10;"><i class="fas fa-times"></i></button>
        <div id="projectDetailsBody" style="max-height: 85vh; overflow-y: auto; padding: 2.5rem;"></div>
    </div>
</div>
<!-- Modal: Add Resource (Material) -->
<div id="addResourceModal" class="modal">
    <div class="modal-content">
        <button onclick="closeModal('addResourceModal')" class="modal-close-btn"><i class="fas fa-times"></i></button>
        <h3><i class="fas fa-file-upload" style="color: #f7941d;"></i> Partilhar Material</h3>
        <form id="addResourceForm" enctype="multipart/form-data">
            <input type="hidden" name="resource_type" value="file">
            <div class="form-group">
                <label>Título do Recurso</label>
                <input type="text" name="title" placeholder="Ex: Manual de Pitch Deck" required>
            </div>
            <div class="form-group">
                <label>Ficheiro (PDF, Word, CSV, etc.)</label>
                <div style="position: relative;">
                    <input type="file" name="file" id="resourceFileInput" style="opacity: 0; position: absolute; inset: 0; cursor: pointer; z-index: 2;" required>
                    <div style="background: var(--surface-5); border: 1px dashed var(--surface-20); border-radius: 16px; padding: 1.5rem; text-align: center; color: var(--surface-50);">
                        <i class="fas fa-cloud-upload-alt" style="font-size: 1.5rem; display: block; margin-bottom: 0.5rem; color: #f7941d;"></i>
                        <span id="fileNameDisplay">Clica para selecionar o ficheiro</span>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label>Partilhar com Estudantes:</label>
                <div id="menteeSelectResource" style="min-height: 50px;">
                    <!-- Preenchido via JS -->
                </div>
            </div>
            <button type="submit" class="btn-primary-small" style="width: 100%;">
                <i class="fas fa-paper-plane"></i> Subir e Partilhar
            </button>
        </form>
    </div>
</div>

<!-- Modal: Add Notice (Aviso) -->
<div id="addNoticeModal" class="modal">
    <div class="modal-content">
        <button onclick="closeModal('addNoticeModal')" class="modal-close-btn"><i class="fas fa-times"></i></button>
        <h3><i class="fas fa-bullhorn" style="color: #f7941d;"></i> Postar Aviso</h3>
        <form id="addNoticeForm">
            <div class="form-group">
                <label>Título do Aviso</label>
                <input type="text" name="title" placeholder="Ex: Mudança de horário" required>
            </div>
            <div class="form-group">
                <label>Mensagem</label>
                <textarea name="message" rows="3" placeholder="Escreva o aviso para os seus mentorados..." required></textarea>
            </div>
            <div class="form-group">
                <label>Selecionar Alunos</label>
                <div id="menteeSelectNotice"></div>
            </div>
            <button type="submit" class="btn-primary-small" style="width: 100%;">
                <i class="fas fa-paper-plane"></i> Publicar Aviso
            </button>
        </form>
    </div>
</div>

<style>
.modal-content {
    animation: modalAppear 0.4s cubic-bezier(0.16, 1, 0.3, 1);
    background: #0b111e;
    border: 1px solid var(--surface-8);
    border-radius: 32px;
}

.modal-close-btn {
    position: absolute;
    top: 1.5rem;
    right: 1.5rem;
    width: 40px;
    height: 40px;
    background: var(--surface-3);
    border: 1px solid var(--surface-5);
    color: var(--surface-50);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.3s;
}

.modal-close-btn:hover {
    background: rgba(239, 68, 68, 0.1);
    color: #ef4444;
    transform: rotate(90deg);
}

.star-rating.active {
    color: #fbbf24 !important;
}

/* Custom Scrollbar for list in modal */
#mentorAvailableSlotsList::-webkit-scrollbar {
    width: 4px;
}
#mentorAvailableSlotsList::-webkit-scrollbar-track {
    background: rgba(255, 255, 255, 0.02);
}
#mentorAvailableSlotsList::-webkit-scrollbar-thumb {
    background: rgba(247, 148, 29, 0.3);
    border-radius: 10px;
}
</style>

