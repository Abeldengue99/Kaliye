<?php
/**
 * paginas/plataforma/dashboard.php
 * Dashboard Principal - Agregador por Tipo de Usuário
 * Redireciona para dashboard específico ou mostra hero
 */

$base_url = '../../';
require_once '../../inclusoes/cabecalho.php';
require_once '../../inclusoes/auth_check.php';
require_once '../../inclusoes/asset_helper.php';

// ============================================================
// 1. VARIÁVEIS GLOBAIS
// ============================================================
$user_type = $_SESSION['user_type'] ?? 'guest';
$user_id = $_SESSION['user_id'] ?? null;
$is_authenticated = isset($_SESSION['user_id']);

// ============================================================
// 2. REDIRECIONAMENTO BASEADO EM TIPO DE USUÁRIO
// ============================================================
if (!$is_authenticated) {
    // Usuário não autenticado - mostrar hero
    require 'dashboard_hero.php';
    exit;
}

// Redirecionar para dashboard específico
switch ($user_type) {
    case 'student':
        header("Location: student_dashboard.php");
        break;
    case 'mentor':
        header("Location: mentor_dashboard.php");
        break;
    case 'investor':
        header("Location: investor_dashboard.php");
        break;
    case 'admin':
        header("Location: ../../administracao/");
        break;
    default:
        // Hero section padrão
        require 'dashboard_hero.php';
}

exit;
?>
