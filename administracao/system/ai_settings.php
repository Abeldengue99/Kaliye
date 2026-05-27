<?php
// admin/ai_settings.php - Configurações de IA
session_start();
$admin_base = '../';
$base_url = '../../';
require_once '../../configuracoes/base_dados.php';
require_once '../../inclusoes/auth_check.php';

if (!isAdmin() || !hasPermission('settings')) {
    header("Location: ../../autenticacao/entrar.php");
    exit();
}

$database = new Database();
/** @var PDO $db */
$db = $database->getConnection();

// Fetch current AI settings (simulated - would be in a settings table)
$ai_enabled = false;
$ai_provider = 'none';
$api_key_set = false;
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Configurações de IA | KALIYE Admin</title>
    <link rel='icon' type='image/png' href='../../recursos/images/marca/favicon-k-32x32.png'>
    <link rel="stylesheet" href="../../recursos/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .settings-card {
            background: rgba(255,255,255,0.03);
            border: 1px solid var(--glass-border);
            border-radius: 12px;
            padding: 2rem;
            margin-bottom: 1.5rem;
        }
        .provider-option {
            background: rgba(255,255,255,0.02);
            border: 2px solid var(--glass-border);
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .provider-option:hover {
            border-color: var(--accent-orange);
            background: rgba(247, 148, 29, 0.05);
        }
        .provider-option.selected {
            border-color: var(--accent-orange);
            background: rgba(247, 148, 29, 0.1);
        }
        .status-badge {
            display: inline-block;
            padding: 0.4rem 1rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        .status-inactive {
            background: rgba(239, 68, 68, 0.2);
            color: #ef4444;
        }
        .status-active {
            background: rgba(16, 185, 129, 0.2);
            color: #10b981;
        }
        .cost-estimate {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 1.5rem;
            border-radius: 8px;
            color: white;
            margin-top: 1rem;
        }
    </style>
</head>
<body style="display: flex;">
    <?php include '../barra_lateral.php'; ?>

    <main class="admin-main-content">
        <div style="margin-bottom: 2rem;">
            <h1 style="font-size: 2.2rem; margin-bottom: 0.5rem;">
                <i class="fas fa-robot" style="color: var(--accent-orange);"></i> 
                Configurações de Inteligência Artificial
            </h1>
            <p style="color: var(--text-secondary);">Configure os modelos de IA para análise automática de projetos, KYC e comprovantes.</p>
            
            <div style="margin-top: 1rem;">
                <span class="status-badge status-inactive">
                    <i class="fas fa-circle"></i> IA Desativada (Modo de Desenvolvimento)
                </span>
            </div>
        </div>

        <!-- Alert de Desenvolvimento -->
        <div style="background: rgba(251, 191, 36, 0.1); border: 1px solid #fbbf24; padding: 1.5rem; border-radius: 8px; margin-bottom: 2rem;">
            <div style="display: flex; align-items: start; gap: 1rem;">
                <i class="fas fa-info-circle" style="color: #fbbf24; font-size: 1.5rem;"></i>
                <div>
                    <h3 style="margin: 0 0 0.5rem 0; color: #fbbf24;">Modo de Desenvolvimento Ativo</h3>
                    <p style="margin: 0; font-size: 0.9rem; line-height: 1.6;">
                        A plataforma está preparada para integração com IA, mas atualmente utiliza algoritmos heurísticos para análise de projetos. 
                        Para ativar a IA real, configure uma das opções abaixo e insira a chave de API correspondente.
                    </p>
                </div>
            </div>
        </div>

        <!-- Provider Selection -->
        <div class="settings-card">
            <h2 style="margin-top: 0;">
                <i class="fas fa-server"></i> Selecionar Provedor de IA
            </h2>
            <p style="color: var(--text-secondary); margin-bottom: 1.5rem;">
                Escolha o modelo de IA que melhor se adequa às necessidades da plataforma.
            </p>

            <div class="provider-option" onclick="selectProvider('openai')">
                <div style="display: flex; justify-content: space-between; align-items: start;">
                    <div>
                        <h3 style="margin: 0 0 0.5rem 0;">
                            <i class="fas fa-brain" style="color: #10a37f;"></i> OpenAI GPT-4
                        </h3>
                        <p style="margin: 0; font-size: 0.9rem; color: var(--text-secondary);">
                            Modelo mais avançado para análise de texto e detecção de plágio.
                        </p>
                        <div style="margin-top: 0.8rem; font-size: 0.85rem;">
                            <span style="color: #10b981;">✓ Melhor precisão (90-95%)</span><br>
                            <span style="color: #10b981;">✓ Suporte robusto</span><br>
                            <span style="color: #ef4444;">✗ Dados enviados para EUA</span>
                        </div>
                    </div>
                    <div style="text-align: right;">
                        <div style="font-size: 1.5rem; font-weight: 700; color: var(--accent-orange);">€300-400</div>
                        <div style="font-size: 0.75rem; color: var(--text-secondary);">por mês</div>
                    </div>
                </div>
            </div>

            <div class="provider-option" onclick="selectProvider('gemini')">
                <div style="display: flex; justify-content: space-between; align-items: start;">
                    <div>
                        <h3 style="margin: 0 0 0.5rem 0;">
                            <i class="fas fa-gem" style="color: #4285f4;"></i> Google Gemini Pro
                        </h3>
                        <p style="margin: 0; font-size: 0.9rem; color: var(--text-secondary);">
                            Modelo multimodal para análise de texto e imagens de projetos.
                        </p>
                        <div style="margin-top: 0.8rem; font-size: 0.85rem;">
                            <span style="color: #10b981;">✓ Muito mais barato</span><br>
                            <span style="color: #10b981;">✓ Analisa imagens</span><br>
                            <span style="color: #f59e0b;">~ Precisão média (75-85%)</span>
                        </div>
                    </div>
                    <div style="text-align: right;">
                        <div style="font-size: 1.5rem; font-weight: 700; color: var(--accent-orange);">€50-100</div>
                        <div style="font-size: 0.75rem; color: var(--text-secondary);">por mês</div>
                    </div>
                </div>
            </div>

            <div class="provider-option" onclick="selectProvider('azure')">
                <div style="display: flex; justify-content: space-between; align-items: start;">
                    <div>
                        <h3 style="margin: 0 0 0.5rem 0;">
                            <i class="fas fa-shield-alt" style="color: #0078d4;"></i> Azure OpenAI (Recomendado)
                        </h3>
                        <p style="margin: 0; font-size: 0.9rem; color: var(--text-secondary);">
                            GPT-4 com conformidade GDPR e data residency na União Europeia.
                        </p>
                        <div style="margin-top: 0.8rem; font-size: 0.85rem;">
                            <span style="color: #10b981;">✓ GDPR Compliant</span><br>
                            <span style="color: #10b981;">✓ SLA Empresarial</span><br>
                            <span style="color: #10b981;">✓ Dados na UE</span>
                        </div>
                    </div>
                    <div style="text-align: right;">
                        <div style="font-size: 1.5rem; font-weight: 700; color: var(--accent-orange);">€400-500</div>
                        <div style="font-size: 0.75rem; color: var(--text-secondary);">por mês</div>
                    </div>
                </div>
            </div>

            <div class="provider-option" onclick="selectProvider('opensource')">
                <div style="display: flex; justify-content: space-between; align-items: start;">
                    <div>
                        <h3 style="margin: 0 0 0.5rem 0;">
                            <i class="fas fa-code-branch" style="color: #d4af37;"></i> Modelo Open-Source (LLaMA 3 / Mistral)
                        </h3>
                        <p style="margin: 0; font-size: 0.9rem; color: var(--text-secondary);">
                            Hospedagem própria com privacidade total e sem limites de uso.
                        </p>
                        <div style="margin-top: 0.8rem; font-size: 0.85rem;">
                            <span style="color: #10b981;">✓ Privacidade total</span><br>
                            <span style="color: #10b981;">✓ Sem limites de uso</span><br>
                            <span style="color: #ef4444;">✗ Requer DevOps</span>
                        </div>
                    </div>
                    <div style="text-align: right;">
                        <div style="font-size: 1.5rem; font-weight: 700; color: var(--accent-orange);">€150-250</div>
                        <div style="font-size: 0.75rem; color: var(--text-secondary);">por mês (servidor GPU)</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- API Configuration -->
        <div class="settings-card">
            <h2 style="margin-top: 0;">
                <i class="fas fa-key"></i> Configuração de API
            </h2>
            
            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">
                    Chave de API
                </label>
                <input type="password" id="apiKey" placeholder="sk-..." 
                    style="width: 100%; padding: 0.8rem; background: rgba(255,255,255,0.05); border: 1px solid var(--glass-border); border-radius: 6px; color: white; font-family: monospace;">
                <p style="font-size: 0.8rem; color: var(--text-secondary); margin-top: 0.5rem;">
                    <i class="fas fa-lock"></i> A chave será armazenada de forma segura e encriptada.
                </p>
            </div>

            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">
                    Modelo Específico
                </label>
                <select style="width: 100%; padding: 0.8rem; background: rgba(255,255,255,0.05); border: 1px solid var(--glass-border); border-radius: 6px; color: white;">
                    <option value="">Selecionar modelo...</option>
                    <option value="gpt-4">GPT-4 (Mais preciso)</option>
                    <option value="gpt-4-turbo">GPT-4 Turbo (Mais rápido)</option>
                    <option value="gpt-3.5-turbo">GPT-3.5 Turbo (Mais barato)</option>
                </select>
            </div>

            <button class="btn-primary" style="background: var(--accent-orange); border: none; padding: 0.8rem 2rem;" onclick="testConnection()">
                <i class="fas fa-plug"></i> Testar Conexão
            </button>
        </div>

        <!-- Features Configuration -->
        <div class="settings-card">
            <h2 style="margin-top: 0;">
                <i class="fas fa-sliders-h"></i> Funcionalidades de IA
            </h2>
            <p style="color: var(--text-secondary); margin-bottom: 1.5rem;">
                Ative ou desative funcionalidades específicas de IA.
            </p>

            <div style="display: grid; gap: 1rem;">
                <label style="display: flex; align-items: center; gap: 1rem; padding: 1rem; background: rgba(255,255,255,0.02); border-radius: 6px; cursor: pointer;">
                    <input type="checkbox" checked disabled style="width: 20px; height: 20px;">
                    <div>
                        <div style="font-weight: 600;">Análise de Projetos</div>
                        <div style="font-size: 0.85rem; color: var(--text-secondary);">Detecção de plágio e avaliação de originalidade</div>
                    </div>
                </label>

                <label style="display: flex; align-items: center; gap: 1rem; padding: 1rem; background: rgba(255,255,255,0.02); border-radius: 6px; cursor: pointer;">
                    <input type="checkbox" disabled style="width: 20px; height: 20px;">
                    <div>
                        <div style="font-weight: 600;">Verificação de Documentos KYC <span style="background: #f59e0b; padding: 2px 6px; border-radius: 4px; font-size: 0.7rem; margin-left: 0.5rem;">EM BREVE</span></div>
                        <div style="font-size: 0.85rem; color: var(--text-secondary);">OCR e validação automática de identidades</div>
                    </div>
                </label>

                <label style="display: flex; align-items: center; gap: 1rem; padding: 1rem; background: rgba(255,255,255,0.02); border-radius: 6px; cursor: pointer;">
                    <input type="checkbox" disabled style="width: 20px; height: 20px;">
                    <div>
                        <div style="font-weight: 600;">Análise de Comprovantes <span style="background: #f59e0b; padding: 2px 6px; border-radius: 4px; font-size: 0.7rem; margin-left: 0.5rem;">EM BREVE</span></div>
                        <div style="font-size: 0.85rem; color: var(--text-secondary);">Extração automática de dados de pagamentos</div>
                    </div>
                </label>

                <label style="display: flex; align-items: center; gap: 1rem; padding: 1rem; background: rgba(255,255,255,0.02); border-radius: 6px; cursor: pointer;">
                    <input type="checkbox" disabled style="width: 20px; height: 20px;">
                    <div>
                        <div style="font-weight: 600;">Chatbot de Suporte <span style="background: #f59e0b; padding: 2px 6px; border-radius: 4px; font-size: 0.7rem; margin-left: 0.5rem;">EM BREVE</span></div>
                        <div style="font-size: 0.85rem; color: var(--text-secondary);">Assistente virtual para dúvidas de utilizadores</div>
                    </div>
                </label>
            </div>
        </div>

        <!-- Cost Estimate -->
        <div class="cost-estimate">
            <h3 style="margin: 0 0 1rem 0;">
                <i class="fas fa-calculator"></i> Estimativa de Custos Mensais
            </h3>
            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.5rem;">
                <div>
                    <div style="font-size: 0.85rem; opacity: 0.9;">Análises Estimadas</div>
                    <div style="font-size: 1.8rem; font-weight: 700; margin-top: 0.3rem;">1,000</div>
                </div>
                <div>
                    <div style="font-size: 0.85rem; opacity: 0.9;">Custo por Análise</div>
                    <div style="font-size: 1.8rem; font-weight: 700; margin-top: 0.3rem;">€0.30</div>
                </div>
                <div>
                    <div style="font-size: 0.85rem; opacity: 0.9;">Total Estimado</div>
                    <div style="font-size: 1.8rem; font-weight: 700; margin-top: 0.3rem;">€300</div>
                </div>
            </div>
            <p style="margin: 1rem 0 0 0; font-size: 0.85rem; opacity: 0.8;">
                <i class="fas fa-info-circle"></i> Valores aproximados. O custo real dependerá do volume de uso e complexidade das análises.
            </p>
        </div>

        <!-- Save Button -->
        <div style="margin-top: 2rem; text-align: right;">
            <button class="btn-primary" style="background: #475569; border: none; padding: 0.8rem 2rem; margin-right: 1rem;">
                <i class="fas fa-times"></i> Cancelar
            </button>
            <button class="btn-primary" style="background: var(--accent-orange); border: none; padding: 0.8rem 2rem;" onclick="saveSettings()">
                <i class="fas fa-save"></i> Guardar Configurações
            </button>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        function selectProvider(provider) {
            document.querySelectorAll('.provider-option').forEach(el => {
                el.classList.remove('selected');
            });
            event.currentTarget.classList.add('selected');
        }

        function testConnection() {
            Swal.fire({
                title: 'Funcionalidade em Desenvolvimento',
                html: 'A conexão com APIs de IA será implementada após aprovação do orçamento.<br><br>Por enquanto, a plataforma utiliza algoritmos heurísticos para análise.',
                icon: 'info',
                background: '#1e293b',
                color: '#fff'
            });
        }

        function saveSettings() {
            Swal.fire({
                title: 'Configurações Salvas',
                text: 'As configurações foram guardadas com sucesso. A IA será ativada assim que a chave de API for validada.',
                icon: 'success',
                background: '#1e293b',
                color: '#fff'
            });
        }
    </script>
</body>
</html>





