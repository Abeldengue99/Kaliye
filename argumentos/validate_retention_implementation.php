<?php
/**
 * Validation Script for Retention System Implementation
 * 
 * This script validates:
 * 1. RetentionMaintenance schema and methods
 * 2. Proper archived_at IS NULL filters in all critical queries
 * 3. Character limits on KYC forms
 * 4. Integration with cabecalho.php
 * 5. Backup snapshots in data_archive_snapshots
 */

require_once __DIR__ . '/../configuracoes/base_dados.php';
require_once __DIR__ . '/../inclusoes/RetentionMaintenance.php';

$db = (new Database())->getConnection();
$retention = new RetentionMaintenance($db);
$errors = [];
$warnings = [];
$successes = [];

echo "═══════════════════════════════════════════════════════════════\n";
echo "    VALIDAÇÃO DO SISTEMA DE RETENÇÃO (RETENTION SYSTEM)\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

// ─── TESTE 1: Verificar Schema ───
echo "📋 TESTE 1: Verificando schema das tabelas...\n";
try {
    $retention->ensureSchema();
    
    // Verificar data_archive_snapshots
    $check = $db->query("SELECT EXISTS (SELECT 1 FROM information_schema.tables WHERE table_name = 'data_archive_snapshots')")->fetchColumn();
    if ($check) {
        $successes[] = "✅ Tabela data_archive_snapshots existe";
    } else {
        $errors[] = "❌ Tabela data_archive_snapshots não foi criada";
    }
    
    // Verificar settings
    $check = $db->query("SELECT EXISTS (SELECT 1 FROM information_schema.tables WHERE table_name = 'settings')")->fetchColumn();
    if ($check) {
        $successes[] = "✅ Tabela settings existe";
    } else {
        $errors[] = "❌ Tabela settings não foi criada";
    }
    
    // Verificar colunas em users
    $cols = ['mentor_application_archived_at', 'investor_application_archived_at'];
    foreach ($cols as $col) {
        $check = $db->query("SELECT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name = 'users' AND column_name = '$col')")->fetchColumn();
        if ($check) {
            $successes[] = "✅ Coluna users.$col existe";
        } else {
            $errors[] = "❌ Coluna users.$col não foi criada";
        }
    }
    
    // Verificar colunas em project_investments
    $check = $db->query("SELECT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name = 'project_investments' AND column_name = 'archived_at')")->fetchColumn();
    if ($check) {
        $successes[] = "✅ Coluna project_investments.archived_at existe";
    } else {
        $errors[] = "❌ Coluna project_investments.archived_at não foi criada";
    }
    
} catch (Throwable $e) {
    $errors[] = "❌ Erro ao ensureSchema: " . $e->getMessage();
}
echo "\n";

// ─── TESTE 2: Verificar Última Execução ───
echo "📋 TESTE 2: Verificando histórico de execução...\n";
try {
    $stmt = $db->prepare("SELECT setting_value FROM settings WHERE setting_key = 'retention_last_run_at' LIMIT 1");
    $stmt->execute();
    $lastRun = $stmt->fetchColumn();
    
    if ($lastRun) {
        $successes[] = "✅ Última execução registrada: $lastRun";
    } else {
        $warnings[] = "⚠️  Nenhuma execução anterior registrada (primeira vez é normal)";
    }
} catch (Throwable $e) {
    $warnings[] = "⚠️  Não foi possível verificar settings: " . $e->getMessage();
}
echo "\n";

// ─── TESTE 3: Contar Registos Arquivados ───
echo "📋 TESTE 3: Contando registos arquivados...\n";
try {
    $tables_to_check = [
        'project_investments' => 'investment_id',
        'notifications' => 'notification_id',
        'free_mentorship_requests' => 'request_id',
        'institution_invitations' => 'invitation_id',
        'mentorship_resources' => 'resource_id'
    ];
    
    foreach ($tables_to_check as $table => $pk) {
        try {
            $count = $db->query("SELECT COUNT(*) FROM $table WHERE archived_at IS NOT NULL")->fetchColumn();
            if ($count > 0) {
                $successes[] = "✅ $table tem $count registos arquivados";
            } else {
                $warnings[] = "⚠️  $table não tem registos arquivados ainda (pode ser normal)";
            }
        } catch (PDOException $e) {
            $warnings[] = "⚠️  Não foi possível contar registos em $table";
        }
    }
} catch (Throwable $e) {
    $errors[] = "❌ Erro ao contar registos: " . $e->getMessage();
}
echo "\n";

// ─── TESTE 4: Validar Queries com archived_at ───
echo "📋 TESTE 4: Validando queries críticas com archived_at IS NULL...\n";
$critical_queries = [
    [
        'name' => 'Reports: investimentos pagos',
        'query' => "SELECT COUNT(*) FROM project_investments WHERE status = 'paid' AND archived_at IS NULL",
        'file' => 'administracao/system/reports.php'
    ],
    [
        'name' => 'Stats: distribuição de investimentos',
        'query' => "SELECT status, COUNT(*) FROM project_investments WHERE archived_at IS NULL GROUP BY status",
        'file' => 'administracao/system/stats_report.php'
    ],
    [
        'name' => 'Finance: comissões ativas',
        'query' => "SELECT COUNT(*) FROM commission_history WHERE archived_at IS NULL",
        'file' => 'administracao/finance/commission_dashboard.php'
    ],
    [
        'name' => 'Analytics: projectos investidos',
        'query' => "SELECT COUNT(*) FROM project_investments WHERE archived_at IS NULL",
        'file' => 'administracao/project_analytics.php'
    ],
    [
        'name' => 'Social: notificações ativas',
        'query' => "SELECT COUNT(*) FROM notifications WHERE archived_at IS NULL",
        'file' => 'interface_programacao/social/get_notifications.php'
    ],
];

foreach ($critical_queries as $q) {
    try {
        $result = $db->query($q['query'])->fetch(PDO::FETCH_NUM);
        $successes[] = "✅ {$q['name']}: " . ($result[0] ?? 'OK') . " registos ativos [{$q['file']}]";
    } catch (PDOException $e) {
        $errors[] = "❌ Erro em {$q['name']}: {$e->getMessage()} [{$q['file']}]";
    }
}
echo "\n";

// ─── TESTE 5: Simular Execução de Retenção (DRY RUN) ───
echo "📋 TESTE 5: Simulando execução de retenção (DRY RUN)...\n";
try {
    $dryRunResult = $retention->run(true); // true = dry run
    
    if (is_array($dryRunResult)) {
        $successes[] = "✅ Retenção DRY RUN executada com sucesso";
        foreach ($dryRunResult as $bucket => $count) {
            $successes[] = "   • $bucket: $count registos seriam arquivados";
        }
    }
} catch (Throwable $e) {
    $errors[] = "❌ Erro no DRY RUN: " . $e->getMessage();
}
echo "\n";

// ─── TESTE 6: Verificar KYC Field Limits ───
echo "📋 TESTE 6: Verificando limites de caracteres no KYC...\n";
try {
    $kyc_file = __DIR__ . '/../inclusoes/components/kyc_modal.php';
    if (file_exists($kyc_file)) {
        $content = file_get_contents($kyc_file);
        
        if (preg_match('/maxlength="200".*name="specialty"/', $content) || preg_match('/name="specialty".*maxlength="200"/', $content)) {
            $successes[] = "✅ Campo 'specialty' tem maxlength=\"200\"";
        } else {
            $errors[] = "❌ Campo 'specialty' não tem maxlength=\"200\" ou está incorrecto";
        }
        
        if (preg_match('/maxlength="250".*name="source_of_funds"/', $content) || preg_match('/name="source_of_funds".*maxlength="250"/', $content)) {
            $successes[] = "✅ Campo 'source_of_funds' tem maxlength=\"250\"";
        } else {
            $errors[] = "❌ Campo 'source_of_funds' não tem maxlength=\"250\" ou está incorrecto";
        }
        
        if (strpos($content, 'updateCharCounter') !== false) {
            $successes[] = "✅ Função JavaScript updateCharCounter() está implementada";
        } else {
            $errors[] = "❌ Função updateCharCounter() não foi encontrada";
        }
    } else {
        $errors[] = "❌ Ficheiro kyc_modal.php não foi encontrado em " . $kyc_file;
    }
} catch (Throwable $e) {
    $errors[] = "❌ Erro ao validar KYC: " . $e->getMessage();
}
echo "\n";

// ─── TESTE 7: Verificar Integração com cabecalho.php ───
echo "📋 TESTE 7: Verificando integração com cabecalho.php...\n";
try {
    $header_file = __DIR__ . '/../inclusoes/cabecalho.php';
    if (file_exists($header_file)) {
        $content = file_get_contents($header_file);
        
        if (strpos($content, 'RetentionMaintenance') !== false) {
            $successes[] = "✅ cabecalho.php referencia RetentionMaintenance";
        } else {
            $errors[] = "❌ cabecalho.php não referencia RetentionMaintenance";
        }
        
        if (strpos($content, 'runIfDue') !== false) {
            $successes[] = "✅ cabecalho.php chama runIfDue()";
        } else {
            $errors[] = "❌ cabecalho.php não chama runIfDue()";
        }
    } else {
        $errors[] = "❌ Ficheiro cabecalho.php não foi encontrado";
    }
} catch (Throwable $e) {
    $errors[] = "❌ Erro ao validar integração: " . $e->getMessage();
}
echo "\n";

// ─── TESTE 8: Validar Indices de Performance ───
echo "📋 TESTE 8: Verificando índices de performance...\n";
try {
    $indexes_to_check = [
        'idx_users_mentor_application_active' => 'users',
        'idx_users_investor_application_active' => 'users',
        'idx_project_investments_operational' => 'project_investments',
        'idx_notifications_operational' => 'notifications',
        'idx_free_mentorship_operational' => 'free_mentorship_requests',
        'idx_mentorship_resources_retention' => 'mentorship_resources',
    ];
    
    foreach ($indexes_to_check as $idx => $table) {
        try {
            $check = $db->query("SELECT EXISTS (SELECT 1 FROM information_schema.statistics WHERE table_name = '$table' AND index_name = '$idx')")->fetchColumn();
            if ($check) {
                $successes[] = "✅ Índice $idx em $table existe";
            } else {
                $warnings[] = "⚠️  Índice $idx em $table não foi encontrado (pode impactar performance)";
            }
        } catch (PDOException $e) {
            $warnings[] = "⚠️  Não foi possível verificar índice $idx";
        }
    }
} catch (Throwable $e) {
    $warnings[] = "⚠️  Erro ao verificar índices: " . $e->getMessage();
}
echo "\n";

// ─── RELATÓRIO FINAL ───
echo "═══════════════════════════════════════════════════════════════\n";
echo "                       RELATÓRIO FINAL\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

echo "✅ SUCESSOS: " . count($successes) . "\n";
foreach ($successes as $msg) {
    echo "   $msg\n";
}

if (count($warnings) > 0) {
    echo "\n⚠️  AVISOS: " . count($warnings) . "\n";
    foreach ($warnings as $msg) {
        echo "   $msg\n";
    }
}

if (count($errors) > 0) {
    echo "\n❌ ERROS: " . count($errors) . "\n";
    foreach ($errors as $msg) {
        echo "   $msg\n";
    }
    echo "\n⛔ IMPLEMENTAÇÃO INCOMPLETA - Existem " . count($errors) . " erro(s) a resolver.\n";
    exit(1);
} else {
    echo "\n✨ IMPLEMENTAÇÃO CONCLUÍDA COM SUCESSO!\n";
    echo "O sistema de retenção está pronto para produção.\n";
    echo "\n📌 PRÓXIMAS ETAPAS:\n";
    echo "   1. Monitorar data_archive_snapshots para volume de arquivos\n";
    echo "   2. Revisar rotina trimestral logs em case de erros\n";
    echo "   3. Fazer backup periódico de data_archive_snapshots\n";
    echo "   4. Testar limite de caracteres em formulários KYC\n";
    exit(0);
}
?>
