<?php
/**
 * Component: Add Skill Modal
 * Expected Variables: $user (array)
 */
?>
<div id="addSkillModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(5, 10, 20, 0.88); backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px); z-index: 100000; justify-content: center; align-items: flex-start; padding: 1rem; overflow-y: auto;">
    <div style="background: rgba(13, 22, 40, 0.95); border: 1px solid rgba(247,148,29,0.2); border-radius: 24px; width: 100%; max-width: 500px; margin: auto; box-shadow: 0 30px 60px -12px rgba(0,0,0,0.9), 0 0 0 1px rgba(255,255,255,0.04); overflow: hidden; position: relative; top: 0; animation: fadeSlideUp 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);">
        
        <!-- HEADER -->
        <div style="display: flex; justify-content: space-between; align-items: center; padding: 1.8rem 2.5rem; background: rgba(7, 13, 26, 0.8); border-bottom: 1px solid rgba(255,255,255,0.07);">
            <div style="display: flex; align-items: center; gap: 1rem;">
                <div style="width: 42px; height: 42px; background: linear-gradient(135deg, #10b981, #059669); border-radius: 12px; display: flex; align-items: center; justify-content: center; box-shadow: 0 5px 15px rgba(16,185,129,0.3);">
                    <i class="fas fa-tools" style="color: white; font-size: 1rem;"></i>
                </div>
                <div>
                    <h3 style="margin: 0; color: white; font-family: 'Outfit', sans-serif; font-size: 1.3rem; font-weight: 800; letter-spacing: -0.5px;">Adicionar Habilidade</h3>
                    <p style="margin: 0; font-size: 0.75rem; color: var(--surface-40); margin-top: 1px;">Adicione skills técnicas ou interpessoais</p>
                </div>
            </div>
            <button onclick="document.getElementById('addSkillModal').style.display='none'"
                    style="width: 38px; height: 38px; border-radius: 50%; background: var(--surface-5); border: 1px solid var(--surface-10); color: var(--surface-50); cursor: pointer; font-size: 1.1rem; display: flex; align-items: center; justify-content: center; transition: all 0.3s ease;"
                    onmouseover="this.style.background='rgba(239,68,68,0.15)'; this.style.color='#ef4444'; this.style.borderColor='rgba(239,68,68,0.3)';"
                    onmouseout="this.style.background='var(--surface-5)'; this.style.color='var(--surface-50)'; this.style.borderColor='var(--surface-10)';">
                &times;
            </button>
        </div>

        <form id="addSkillForm" style="padding: 2.5rem;">
            <div class="input-group" style="margin-bottom: 1.5rem;">
                <label style="display: flex; align-items: center; gap: 0.4rem; font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px; color: var(--surface-40); margin-bottom: 0.5rem;">
                    Habilidade Central
                </label>
                <input type="text" name="skill_name" placeholder="Ex: Marketing Digital, Python..." required
                       style="width: 100%; background: rgba(15,23,42,0.5); border: 1px solid var(--surface-10); border-radius: 12px; padding: 0.85rem 1rem; color: white; font-size: 0.9rem; outline: none; transition: all 0.3s;"
                       onfocus="this.style.borderColor='#10b981'; this.style.boxShadow='0 0 0 3px rgba(16,185,129,0.12)';"
                       onblur="this.style.borderColor='var(--surface-10)'; this.style.boxShadow='none';">
            </div>

            <div class="input-group" style="margin-bottom: 2rem;">
                <label style="display: flex; align-items: center; gap: 0.4rem; font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px; color: var(--surface-40); margin-bottom: 0.5rem;">
                    Nível de Domínio
                </label>
                <select name="type" style="width: 100%; background: rgba(15,23,42,0.8); border: 1px solid var(--surface-10); border-radius: 12px; padding: 0.85rem 1rem; color: white; font-size: 0.9rem; outline: none; cursor: pointer; transition: all 0.3s;"
                        onfocus="this.style.borderColor='#10b981'; this.style.boxShadow='0 0 0 3px rgba(16,185,129,0.12)';"
                        onblur="this.style.borderColor='var(--surface-10)'; this.style.boxShadow='none';">
                    <?php 
                        $is_any_student = in_array($user['user_type'], ['univ_student', 'high_student', 'sec_student']);
                        if ($is_any_student): 
                    ?>
                        <option value="learner">Quero Aprender (Learner)</option>
                        <option value="expert">Posso Ensinar (Expert)</option>
                    <?php else: ?>
                        <option value="expert">Posso Ensinar (Expert)</option>
                    <?php endif; ?>
                </select>
            </div>

            <button type="submit" style="width: 100%; padding: 0.9rem; background: linear-gradient(135deg, #10b981, #059669); border: none; border-radius: 12px; color: white; font-size: 0.9rem; font-weight: 700; cursor: pointer; transition: all 0.3s; box-shadow: 0 5px 20px rgba(16,185,129,0.35); display: flex; align-items: center; justify-content: center; gap: 0.5rem;"
                    onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 10px 30px rgba(16,185,129,0.45)';"
                    onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 5px 20px rgba(16,185,129,0.35)';">
                <i class="fas fa-plus"></i> Guardar Habilidade
            </button>
        </form>
    </div>
</div>
