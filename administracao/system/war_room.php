<?php
/**
 * administracao/system/war_room.php - Centro de Operações em Tempo Real
 */
session_start();
$admin_base = '../';
$base_url = '../../';
require_once '../../configuracoes/base_dados.php';
require_once '../../inclusoes/auth_check.php';

if (!isAdmin() || !hasPermission('dashboard')) {
    header("Location: ../../autenticacao/entrar.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/png" sizes="32x32" href="<?php echo $base_url; ?>recursos/images/marca/favicon-k-32x32.png">
    <link rel="apple-touch-icon" sizes="180x180" href="<?php echo $base_url; ?>recursos/images/marca/apple-touch-icon-k.png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KALIYE War Room - Operações em Tempo Real</title>
    
    <link rel="stylesheet" href="../../recursos/css/style.css">
    <link rel="stylesheet" href="../../recursos/css/pages/admin_dashboard.css?v=<?= filemtime(__DIR__ . '/../../recursos/css/pages/admin_dashboard.css') ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Leaflet CSS & JS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    
    <style>
        .war-room-container {
            height: calc(100vh - 100px);
            position: relative;
            margin-top: 20px;
            border-radius: 30px;
            overflow: hidden;
            border: 1px solid rgba(255,255,255,0.1);
            box-shadow: 0 20px 50px rgba(0,0,0,0.5);
        }
        #map { height: 100%; width: 100%; background: #0b0f1a; }
        
        .map-overlay-stats {
            position: absolute;
            top: 20px;
            left: 20px;
            z-index: 1000;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        
        .stat-badge-map {
            background: rgba(13, 22, 40, 0.85);
            backdrop-filter: blur(15px);
            padding: 12px 20px;
            border-radius: 15px;
            border: 1px solid rgba(255,255,255,0.1);
            color: #fff;
            font-size: 0.85rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }
        
        .stat-badge-map i { font-size: 1.1rem; color: #f7941d; }
        .pulse-red {
            width: 10px; height: 10px;
            background: #ef4444;
            border-radius: 50%;
            box-shadow: 0 0 0 rgba(239, 68, 68, 0.4);
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7); }
            70% { box-shadow: 0 0 0 10px rgba(239, 68, 68, 0); }
            100% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); }
        }
        
        /* Custom Marker Styles */
        .project-marker {
            background: #f7941d;
            border: 2px solid #fff;
            border-radius: 50%;
            box-shadow: 0 0 15px rgba(247, 148, 29, 0.6);
        }
    </style>
</head>
<body class="admin-dashboard-layout">

    <?php include '../barra_lateral.php'; ?>

    <main class="admin-main-content">
        <header class="dashboard-header">
            <div class="header-title">
                <h1>🛡️ War Room Administrativa</h1>
                <p style="color: rgba(255,255,255,0.5);">Monitorização geo-estratégica do ecossistema KALIYE em Angola.</p>
            </div>
            <div style="display: flex; gap: 10px;">
                <div class="stat-badge-map" style="background: rgba(16, 185, 129, 0.1); color: #10b981; border-color: rgba(16, 185, 129, 0.2);">
                    <div class="pulse-red" style="background: #10b981;"></div> LIVE FEED ACTIVE
                </div>
            </div>
        </header>

        <div class="war-room-container">
            <div class="map-overlay-stats">
                <div class="stat-badge-map">
                    <i class="fas fa-users"></i>
                    <div>
                        <span id="userCount">0</span>
                        <div style="font-size: 0.65rem; opacity: 0.5; text-transform: uppercase;">Utilizadores Online</div>
                    </div>
                </div>
                <div class="stat-badge-map">
                    <i class="fas fa-lightbulb"></i>
                    <div>
                        <span id="projectCount">0</span>
                        <div style="font-size: 0.65rem; opacity: 0.5; text-transform: uppercase;">Projectos Activos</div>
                    </div>
                </div>
            </div>
            
            <div id="map"></div>
        </div>
    </main>

    <script>
        // Inicialização do Mapa com foco em Luanda, Angola
        var map = L.map('map', {
            zoomControl: false,
            attributionControl: false
        }).setView([-8.8368, 13.2343], 6);

        // Camada de Mapa Escura (Premium Look)
        L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
            maxZoom: 19
        }).addTo(map);

        L.control.zoom({ position: 'bottomright' }).addTo(map);

        var userGroup = L.layerGroup().addTo(map);
        var projectGroup = L.layerGroup().addTo(map);

        function loadWarRoomData() {
            fetch('../../interface_programacao/admin/get_war_room_data.php')
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        renderMarkers(data.users, data.projects);
                        document.getElementById('userCount').innerText = data.users.length;
                        document.getElementById('projectCount').innerText = data.projects.length;
                    }
                });
        }

        function renderMarkers(users, projects) {
            userGroup.clearLayers();
            projectGroup.clearLayers();

            // Ícone de Utilizador (Ponto Azul suave)
            var userIcon = L.divIcon({
                className: 'user-marker',
                html: '<div style="width: 8px; height: 8px; background: #3b82f6; border-radius: 50%; box-shadow: 0 0 10px #3b82f6;"></div>',
                iconSize: [8, 8]
            });

            users.forEach(u => {
                L.marker([u.latitude, u.longitude], { icon: userIcon })
                    .bindPopup(`<b>Utilizador em ${u.city}</b><br>Visto pela última vez: ${u.last_login_at}`)
                    .addTo(userGroup);
            });

            // Ícone de Projecto (Foguete Laranja)
            var projectIcon = L.divIcon({
                className: 'project-marker-wrapper',
                html: '<div style="width: 20px; height: 20px; background: #f7941d; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 10px; border: 2px solid #fff; box-shadow: 0 0 15px rgba(247,148,29,0.5);"><i class="fas fa-rocket"></i></div>',
                iconSize: [20, 20]
            });

            projects.forEach(p => {
                L.marker([p.latitude, p.longitude], { icon: projectIcon })
                    .bindPopup(`<b>PROJETO: ${p.title}</b><br>Categoria: ${p.category}<br>Budget: ${parseFloat(p.budget_needed).toLocaleString()} AOA`)
                    .addTo(projectGroup);
            });
        }

        // Auto-refresh a cada 30 segundos
        loadWarRoomData();
        setInterval(loadWarRoomData, 30000);
    </script>
</body>
</html>

