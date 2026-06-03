<?php
/**
 * Sistema de Validação Automática de Notificações
 * Executa verificação periódica de links de notificações
 * 
 * Este arquivo é incluído automaticamente na inicialização
 * e valida a integridade dos links de notificações
 */

if (!defined('NOTIFICATIONS_VALIDATION_RUNNING')) {
    define('NOTIFICATIONS_VALIDATION_RUNNING', true);

    /**
     * Função: Validar e corrigir links de notificações de dúvida
     * Executa verificação em background sem afetar performance
     */
    function validateAndFixNotificationLinks() {
        try {
            // Apenas valida se em modo admin ou em momento de atualização
            $should_validate = (
                (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin') ||
                (isset($_GET['validate_notifications']) && $_GET['validate_notifications'] === '1') ||
                (mt_rand(1, 1000) <= 5) // 0.5% de chance em cada request (low performance impact)
            );

            if (!$should_validate) {
                return;
            }

            // Verificar cache para evitar validação muito frequente
            $cache_key = 'notifications_last_validation';
            
            // Fallback para quando APCu não está disponível
            if (function_exists('apcu_fetch')) {
                $last_validation = apcu_fetch($cache_key);
            } else {
                // Usar SESSION como fallback se APCu não está disponível
                if (session_status() === PHP_SESSION_NONE) {
                    session_start();
                }
                $last_validation = $_SESSION[$cache_key] ?? false;
            }
            
            if ($last_validation !== false && (time() - $last_validation) < 3600) {
                // Validação executada há menos de 1 hora
                return;
            }

            require_once __DIR__ . '/base_dados.php';
            $database = new Database();
            $db = $database->getConnection();

            // Contar problemas
            $check = $db->query("SELECT COUNT(*) as cnt FROM notifications WHERE link LIKE '%paginas/social/duvidas.php%'");
            $count = $check->fetch(PDO::FETCH_ASSOC)['cnt'] ?? 0;

            if ($count > 0) {
                // Corrigir automaticamente
                $db->exec("UPDATE notifications SET link = REPLACE(link, 'paginas/social/duvidas.php', 'paginas/explorar/doubts.php') WHERE link LIKE '%paginas/social/duvidas.php%'");
                
                // Log
                error_log("[NOTIFICATIONS VALIDATION] Corrigidos $count links de dúvida automaticamente");
            }

            // Cache a próxima validação por 1 hora
            if (function_exists('apcu_store')) {
                apcu_store($cache_key, time(), 3600);
            } else {
                // Fallback: guardar em SESSION quando APCu não está disponível
                if (session_status() === PHP_SESSION_NONE) {
                    session_start();
                }
                $_SESSION[$cache_key] = time();
            }

        } catch (Exception $e) {
            // Falha silenciosa - não afeta funcionamento
            error_log("[NOTIFICATIONS VALIDATION ERROR] " . $e->getMessage());
        }
    }

    // Executar validação
    validateAndFixNotificationLinks();
}
?>
