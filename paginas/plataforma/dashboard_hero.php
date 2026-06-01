<?php
/**
 * paginas/plataforma/dashboard_hero.php
 * Seção Hero do Dashboard para Usuários Não Autenticados
 * Mostra apresentação com imagens e CTAs
 */

// Variáveis globais se não definidas
$base_url = $base_url ?? '../../';
$user_type = $_SESSION['user_type'] ?? 'guest';
$is_authenticated = isset($_SESSION['user_id']);

// Array de imagens para hero
$dashboard_hero_images = [
    [
        'url' => 'recursos/images/dashboard/hero-mentoria.jpg',
        'title' => 'Programa de Mentoria',
        'description' => 'Conecte-se com mentores experientes',
        'icon' => '🎯',
        'cta' => 'Explorar Mentores',
        'link' => 'explorar/mentoria.php'
    ],
    [
        'url' => 'recursos/images/dashboard/hero-investimento.jpg',
        'title' => 'Oportunidades de Investimento',
        'description' => 'Encontre projetos promissores',
        'icon' => '💰',
        'cta' => 'Ver Projetos',
        'link' => 'explorar/projetos.php'
    ],
    [
        'url' => 'recursos/images/dashboard/hero-comunidade.jpg',
        'title' => 'Comunidade de Aprendizado',
        'description' => 'Faça parte de uma rede de inovadores',
        'icon' => '👥',
        'cta' => 'Explorar Comunidade',
        'link' => 'social/forum.php'
    ],
    [
        'url' => 'recursos/images/dashboard/hero-recursos.jpg',
        'title' => 'Recursos e Ferramentas',
        'description' => 'Acesse conteúdo exclusivo e ferramentas',
        'icon' => '📚',
        'cta' => 'Acessar Recursos',
        'link' => 'explorar/recursos.php'
    ]
];

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Aksanti Referências</title>
    
    <style>
        :root {
            --primary-color: #2563eb;
            --secondary-color: #1e40af;
            --accent-color: #f59e0b;
            --success-color: #10b981;
            --danger-color: #ef4444;
            --gray-light: #f3f4f6;
            --gray-dark: #1f2937;
            --border-radius: 8px;
            --shadow: 0 1px 3px rgba(0,0,0,0.12), 0 1px 2px rgba(0,0,0,0.24);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 20px;
            color: #333;
        }

        .dashboard-container {
            max-width: 1200px;
            width: 100%;
        }

        .hero-header {
            text-align: center;
            color: white;
            margin-bottom: 50px;
            animation: fadeInDown 0.8s ease-out;
        }

        .hero-header h1 {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 15px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
        }

        .hero-header p {
            font-size: 1.25rem;
            opacity: 0.95;
            font-weight: 300;
        }

        .hero-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }

        .hero-card {
            background: white;
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: var(--shadow);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            flex-direction: column;
            height: 100%;
        }

        .hero-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);
        }

        .hero-card-image {
            width: 100%;
            height: 200px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 4rem;
            color: white;
            overflow: hidden;
            position: relative;
        }

        .hero-card-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .hero-card-image::after {
            content: attr(data-icon);
            position: absolute;
            font-size: 4rem;
            opacity: 0.3;
        }

        .hero-card-content {
            padding: 25px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }

        .hero-card-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--gray-dark);
            margin-bottom: 10px;
        }

        .hero-card-description {
            font-size: 0.95rem;
            color: #6b7280;
            margin-bottom: 20px;
            flex-grow: 1;
        }

        .hero-card-cta {
            display: inline-block;
            background: var(--primary-color);
            color: white;
            padding: 10px 20px;
            border-radius: 4px;
            text-decoration: none;
            font-weight: 600;
            transition: background 0.3s ease;
            border: none;
            cursor: pointer;
            font-size: 0.95rem;
        }

        .hero-card-cta:hover {
            background: var(--secondary-color);
        }

        .cta-section {
            background: white;
            padding: 40px;
            border-radius: var(--border-radius);
            text-align: center;
            box-shadow: var(--shadow);
            animation: fadeInUp 0.8s ease-out 0.3s both;
        }

        .cta-section h2 {
            font-size: 2rem;
            color: var(--gray-dark);
            margin-bottom: 15px;
        }

        .cta-section p {
            font-size: 1.1rem;
            color: #6b7280;
            margin-bottom: 30px;
        }

        .cta-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn {
            padding: 12px 30px;
            border-radius: 4px;
            font-weight: 600;
            font-size: 1rem;
            text-decoration: none;
            display: inline-block;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: var(--primary-color);
            color: white;
        }

        .btn-primary:hover {
            background: var(--secondary-color);
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(37, 99, 235, 0.3);
        }

        .btn-secondary {
            background: transparent;
            color: white;
            border: 2px solid white;
        }

        .btn-secondary:hover {
            background: white;
            color: var(--primary-color);
        }

        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (max-width: 768px) {
            .hero-header h1 {
                font-size: 2rem;
            }

            .hero-header p {
                font-size: 1rem;
            }

            .hero-grid {
                grid-template-columns: 1fr;
                gap: 15px;
            }

            .cta-section {
                padding: 30px 20px;
            }

            .cta-section h2 {
                font-size: 1.5rem;
            }

            .cta-buttons {
                gap: 10px;
            }

            .btn {
                padding: 10px 20px;
                font-size: 0.9rem;
            }
        }

        .version-info {
            position: fixed;
            bottom: 10px;
            right: 10px;
            color: white;
            font-size: 0.75rem;
            opacity: 0.7;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Hero Header -->
        <div class="hero-header">
            <h1>🚀 Bem-vindo ao Aksanti</h1>
            <p>Sua plataforma de mentoria, investimento e inovação</p>
        </div>

        <!-- Hero Cards Grid -->
        <div class="hero-grid">
            <?php foreach ($dashboard_hero_images as $card): ?>
            <div class="hero-card">
                <div class="hero-card-image" data-icon="<?php echo $card['icon']; ?>">
                    <?php if (file_exists($base_url . $card['url'])): ?>
                        <img src="<?php echo $base_url . $card['url']; ?>" alt="<?php echo htmlspecialchars($card['title']); ?>">
                    <?php endif; ?>
                </div>
                <div class="hero-card-content">
                    <h3 class="hero-card-title"><?php echo htmlspecialchars($card['title']); ?></h3>
                    <p class="hero-card-description"><?php echo htmlspecialchars($card['description']); ?></p>
                    <a href="<?php echo $base_url . $card['link']; ?>" class="hero-card-cta">
                        <?php echo htmlspecialchars($card['cta']); ?> →
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- CTA Section -->
        <div class="cta-section">
            <h2>Comece Agora</h2>
            <p>Crie sua conta e acesse todas as funcionalidades da plataforma</p>
            <div class="cta-buttons">
                <?php if (!$is_authenticated): ?>
                    <a href="<?php echo $base_url; ?>index.php" class="btn btn-primary">
                        ← Voltar ao Login
                    </a>
                    <a href="<?php echo $base_url; ?>autenticacao/registrar.php" class="btn btn-secondary">
                        Criar Conta
                    </a>
                <?php else: ?>
                    <a href="<?php echo $base_url; ?>paginas/plataforma/dashboard.php" class="btn btn-primary">
                        Voltar ao Dashboard
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="version-info">
        v1.0.0 | Dashboard Hero | <?php echo date('d/m/Y'); ?>
    </div>
</body>
</html>
