<?php
/**
 * admin/moderation/terms_signatures.php
 * Registo de aceitação dos termos de publicação.
 */
session_start();
$admin_base = '../';
$base_url = '../../';
require_once '../../configuracoes/base_dados.php';
require_once '../../inclusoes/auth_check.php';

if (!isAdmin()) {
    header("Location: ../../autenticacao/entrar.php");
    exit();
}

$database = new Database();
/** @var PDO $db */
$db = $database->getConnection();

// ✅ AUTO-MIGRATE: Garantir que a tabela de assinaturas existe
try {
    $db->exec("
        CREATE TABLE IF NOT EXISTS project_terms_signatures (
            signature_id  SERIAL PRIMARY KEY,
            project_id    INTEGER NOT NULL,
            user_id       INTEGER NOT NULL,
            ip_address    VARCHAR(100),
            terms_version VARCHAR(20) DEFAULT 'v1.0',
            accepted_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
} catch (Exception $e) {
    // Silenciar — tabela pode já existir com outro driver
}

// Exportar CSV
if (isset($_GET['export']) && $_GET['export'] == 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=assinaturas_termos_' . date('Y-m-d') . '.csv');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID Assinatura', 'Utilizador', 'Email', 'ID Projecto', 'Titulo Projecto', 'IP Utilizador', 'Versao Termos', 'Data/Hora']);
    
    $stmt = $db->query("
        SELECT s.signature_id, u.full_name, u.email, p.project_id, p.title, s.ip_address, s.terms_version, s.accepted_at 
        FROM project_terms_signatures s
        JOIN users u ON s.user_id = u.user_id
        JOIN projects p ON s.project_id = p.project_id
        ORDER BY s.accepted_at DESC
    ");
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, $row);
    }
    fclose($output);
    exit();
}

// Obter as assinaturas para exibição
$stmt = $db->query("
    SELECT s.signature_id, u.full_name, u.email, p.project_id, p.title, s.ip_address, s.terms_version, s.accepted_at 
    FROM project_terms_signatures s
    JOIN users u ON s.user_id = u.user_id
    JOIN projects p ON s.project_id = p.project_id
    ORDER BY s.accepted_at DESC
    LIMIT 100
");
$signatures = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assinaturas de Termos - Admin</title>
    <link rel="stylesheet" href="../../recursos/css/style.css">
    <link rel="stylesheet" href="../../recursos/css/mobile-elite.css">
    <link rel="stylesheet" href="../../recursos/css/pages/admin_dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- jsPDF para exportação PDF -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js"></script>
    <style>
        .table-glass {
            width: 100%;
            border-collapse: collapse;
            background: rgba(13, 22, 40, 0.6);
            backdrop-filter: blur(10px);
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid rgba(255,255,255,0.05);
        }
        .table-glass th, .table-glass td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid rgba(255,255,255,0.05);
            font-size: 0.9rem;
        }
        .table-glass th {
            background: rgba(0,0,0,0.3);
            font-weight: 700;
            color: #f7941d;
        }
        .table-glass tr:hover {
            background: rgba(255,255,255,0.02);
        }
        .badge {
            background: rgba(16, 185, 129, 0.1);
            color: #10b981;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 0.8rem;
            font-weight: bold;
        }
    </style>
</head>
<body class="admin-dashboard-layout">
    
    <?php include '../barra_lateral.php'; ?>

    <main class="admin-main-content">
        <header class="dashboard-header" style="margin-bottom: 2rem;">
            <div class="header-title">
                <h1>Assinaturas de Termos</h1>
                <p>Registo legal da aceitação dos termos de publicação (NDA & Gestão Kaliye)</p>
            </div>
            <div class="admin-quick-actions" style="display: flex; gap: 10px; flex-wrap: wrap;">
                <a href="?export=csv" class="btn-admin btn-admin-green">
                    <i class="fas fa-file-csv"></i> Exportar CSV
                </a>
                <button onclick="exportPDF()" class="btn-admin" style="background: rgba(239,68,68,0.15); color: #ef4444; border: 1px solid rgba(239,68,68,0.4); cursor: pointer;">
                    <i class="fas fa-file-pdf"></i> Exportar PDF
                </button>
            </div>
        </header>

        <div class="admin-card-glass">
            <table id="signaturesTable" class="table-glass">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Criador (Utilizador)</th>
                        <th>Projecto Submetido</th>
                        <th>Endereço IP</th>
                        <th>Versão Termos</th>
                        <th>Status</th>
                        <th>Data e Hora (Aceitação)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($signatures) > 0): ?>
                        <?php foreach ($signatures as $sig): ?>
                            <tr>
                                <td style="color: #94a3b8;">#<?= htmlspecialchars($sig['signature_id']) ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($sig['full_name']) ?></strong><br>
                                    <span style="font-size: 0.8rem; color: rgba(255,255,255,0.5);"><?= htmlspecialchars($sig['email']) ?></span>
                                </td>
                                <td>
                                    <strong><?= htmlspecialchars($sig['title']) ?></strong><br>
                                    <span style="font-size: 0.8rem; color: #3b82f6;">ID: <?= htmlspecialchars($sig['project_id']) ?></span>
                                </td>
                                <td><?= htmlspecialchars($sig['ip_address']) ?></td>
                                <td><span class="badge"><?= htmlspecialchars($sig['terms_version']) ?></span></td>
                                <td>
                                    <span style="display: inline-flex; align-items: center; gap: 6px; background: rgba(16,185,129,0.12); color: #10b981; border: 1px solid rgba(16,185,129,0.35); border-radius: 20px; padding: 4px 12px; font-size: 0.78rem; font-weight: 700; white-space: nowrap;">
                                        <i class="fas fa-check-circle"></i> Aceitou os Termos
                                    </span>
                                </td>
                                <td><?= date('d/m/Y H:i:s', strtotime($sig['accepted_at'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 2rem; color: #94a3b8;">Nenhuma assinatura registada ainda.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>

<script>
// Dados das assinaturas para geração do PDF
const signaturesData = <?php echo json_encode(array_map(function($s) {
    return [
        '#' . htmlspecialchars($s['signature_id']),
        htmlspecialchars($s['full_name']) . ' <' . htmlspecialchars($s['email']) . '>',
        htmlspecialchars($s['title']) . ' (ID: ' . htmlspecialchars($s['project_id']) . ')',
        htmlspecialchars($s['ip_address'] ?? '-'),
        htmlspecialchars($s['terms_version']),
        'Aceitou os Termos',
        date('d/m/Y H:i:s', strtotime($s['accepted_at']))
    ];
}, $signatures)); ?>;

function exportPDF() {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF({ orientation: 'landscape', unit: 'mm', format: 'a4' });

    const pageW = doc.internal.pageSize.getWidth();
    const today = new Date().toLocaleDateString('pt-PT', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });

    // ── Cabeçalho ──
    doc.setFillColor(5, 10, 21);
    doc.rect(0, 0, pageW, 28, 'F');

    doc.setFontSize(18);
    doc.setTextColor(247, 148, 29);
    doc.setFont('helvetica', 'bold');
    doc.text('KALIYE', 14, 12);

    doc.setFontSize(11);
    doc.setTextColor(255, 255, 255);
    doc.setFont('helvetica', 'normal');
    doc.text('Relatório de Assinaturas de Termos de Publicação', 14, 20);

    doc.setFontSize(8);
    doc.setTextColor(148, 163, 184);
    doc.text('Gerado em: ' + today, pageW - 14, 20, { align: 'right' });

    // ── Linha separadora ──
    doc.setDrawColor(247, 148, 29);
    doc.setLineWidth(0.5);
    doc.line(14, 29, pageW - 14, 29);

    // ── Tabela ──
    doc.autoTable({
        startY: 33,
        head: [['ID', 'Utilizador / Email', 'Projecto', 'IP', 'Versao', 'Status', 'Data e Hora']],
        body: signaturesData.length > 0 ? signaturesData : [['—', 'Nenhuma assinatura registada', '', '', '', '', '']],
        styles: {
            fontSize: 8,
            cellPadding: 3,
            textColor: [226, 232, 240],
            fillColor: [13, 22, 40]
        },
        headStyles: {
            fillColor: [20, 33, 60],
            textColor: [247, 148, 29],
            fontStyle: 'bold',
            fontSize: 9
        },
        alternateRowStyles: {
            fillColor: [18, 30, 52]
        },
        columnStyles: {
            0: { cellWidth: 12 },
            1: { cellWidth: 55 },
            2: { cellWidth: 58 },
            3: { cellWidth: 30 },
            4: { cellWidth: 16 },
            5: { cellWidth: 38, textColor: [16, 185, 129], fontStyle: 'bold' },
            6: { cellWidth: 36 }
        },
        tableLineColor: [30, 41, 59],
        tableLineWidth: 0.2,
        margin: { left: 14, right: 14 },
        didDrawPage: function(data) {
            // Rodapé em cada página
            const pgH = doc.internal.pageSize.getHeight();
            doc.setFontSize(7);
            doc.setTextColor(100, 116, 139);
            doc.text(
                'Documento confidencial — KALIYE © ' + new Date().getFullYear() + '  |  Página ' + data.pageNumber,
                pageW / 2, pgH - 6, { align: 'center' }
            );
        }
    });

    // ── Sumário no final ──
    const finalY = doc.lastAutoTable.finalY + 8;
    doc.setFontSize(8);
    doc.setTextColor(148, 163, 184);
    doc.text('Total de registos: ' + signaturesData.length, 14, finalY);
    doc.text('Versão dos Termos em vigor: v1.0', 14, finalY + 5);

    doc.save('kaliye_assinaturas_termos_' + new Date().toISOString().slice(0,10) + '.pdf');
}
</script>
</body>
</html>
