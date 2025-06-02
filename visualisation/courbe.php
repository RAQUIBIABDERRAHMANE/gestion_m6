<?php
// Afficher les erreurs pour le débogage
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Inclure la connexion à la base de données
include_once("../db/index.php");

// Vérifier si la connexion à la base de données est établie
if (!isset($db) || $db->connect_error) {
    die("Erreur de connexion à la base de données: " . ($db->connect_error ?? "Variable \$db non définie"));
}

session_start();

if (!isset($_SESSION["nom_prenom"]) || $_SESSION["role"] !== "admin") {
    header('Location: ../');
    exit;
}

// Reste du code...
include_once("../db/index.php");


if (!isset($_SESSION["nom_prenom"]) || $_SESSION["role"] !== "admin") {
    header('Location: ./');
    exit;
}

// Récupérer tous les services pour générer une courbe par service
$services_query = $db->query("SELECT id, nom_service FROM services ORDER BY nom_service");
$services = [];
while ($service = $services_query->fetch_assoc()) {
    $services[$service['id']] = $service;
}

// Période pour l'analyse (par défaut: derniers 30 jours)
$periode = isset($_GET['periode']) ? intval($_GET['periode']) : 30;
$periodes_disponibles = [7 => '7 jours', 30 => '30 jours', 90 => '3 mois', 180 => '6 mois', 365 => '1 an'];

// Date de début pour la période sélectionnée
$date_debut = date('Y-m-d', strtotime("-{$periode} days"));

// Données pour chaque service
$donnees_services = [];

foreach ($services as $service_id => $service_info) {
    // Nombre de déplacements vers ce service par jour
    $sql = "
        SELECT 
            DATE(date_heure) as jour,
            COUNT(*) as nombre_deplacements
        FROM 
            deplacements
        WHERE 
            service_destination_id = ? AND
            date_heure >= ?
        GROUP BY 
            DATE(date_heure)
        ORDER BY 
            jour ASC
    ";
    
    $stmt = $db->prepare($sql);
    $stmt->bind_param("is", $service_id, $date_debut);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $data_points = [];
    while ($row = $result->fetch_assoc()) {
        $data_points[] = [
            'jour' => $row['jour'],
            'nombre' => intval($row['nombre_deplacements'])
        ];
    }
    
    // Compter le nombre total d'équipements actuellement dans ce service
    $sql_total = "
        SELECT COUNT(*) as total FROM equipements e
        LEFT JOIN (
            SELECT equipement_id, service_destination_id
            FROM deplacements
            WHERE (equipement_id, date_heure) IN (
                SELECT equipement_id, MAX(date_heure)
                FROM deplacements
                GROUP BY equipement_id
            )
        ) d ON e.id = d.equipement_id
        WHERE COALESCE(d.service_destination_id, e.service_source_id) = ?
    ";
    
    $stmt_total = $db->prepare($sql_total);
    $stmt_total->bind_param("i", $service_id);
    $stmt_total->execute();
    $result_total = $stmt_total->get_result();
    $row_total = $result_total->fetch_assoc();
    $total_equipements = $row_total['total'];
    
    $donnees_services[$service_id] = [
        'nom' => $service_info['nom_service'],
        'data_points' => $data_points,
        'total_equipements' => $total_equipements
    ];
}

// Récupérer les 5 équipements les plus déplacés
$sql_top_equipements = "
    SELECT 
        e.id,
        e.nom_equipement,
        COUNT(d.id) as nombre_deplacements
    FROM 
        equipements e
    JOIN 
        deplacements d ON e.id = d.equipement_id
    WHERE 
        d.date_heure >= ?
    GROUP BY 
        e.id, e.nom_equipement
    ORDER BY 
        nombre_deplacements DESC
    LIMIT 5
";

$stmt_top = $db->prepare($sql_top_equipements);
$stmt_top->bind_param("s", $date_debut);
$stmt_top->execute();
$result_top = $stmt_top->get_result();

$top_equipements = [];
while ($row = $result_top->fetch_assoc()) {
    $top_equipements[] = $row;
}

// Génération des couleurs pour les graphiques
function generateColors($count) {
    $colors = [
        '#4285F4', // Bleu Google
        '#EA4335', // Rouge Google
        '#FBBC05', // Jaune Google
        '#34A853', // Vert Google
        '#8E24AA', // Violet
        '#006064', // Bleu-vert foncé
        '#F57C00', // Orange
        '#0288D1', // Bleu clair
        '#689F38', // Vert clair
        '#D81B60', // Rose
        '#5D4037', // Marron
        '#455A64'  // Bleu-gris
    ];
    
    $result = [];
    for ($i = 0; $i < $count; $i++) {
        $result[] = $colors[$i % count($colors)];
    }
    return $result;
}

$colors = generateColors(count($services));
$color_index = 0;

// Préparation des données pour les graphiques
$labels = [];
$datasets = [];

// Générer les dates pour l'axe X (tous les jours de la période)
$current_date = new DateTime($date_debut);
$end_date = new DateTime();
$date_labels = [];

while ($current_date <= $end_date) {
    $date_labels[] = $current_date->format('Y-m-d');
    $current_date->modify('+1 day');
}

// Préparer les datasets pour Chart.js
foreach ($services as $service_id => $service_info) {
    $data_by_date = [];
    
    // Initialiser toutes les dates à 0
    foreach ($date_labels as $date) {
        $data_by_date[$date] = 0;
    }
    
    // Remplir avec les valeurs réelles
    foreach ($donnees_services[$service_id]['data_points'] as $point) {
        $data_by_date[$point['jour']] = $point['nombre'];
    }
    
    // Extraire uniquement les valeurs dans l'ordre des dates
    $data_values = [];
    foreach ($date_labels as $date) {
        $data_values[] = $data_by_date[$date];
    }
    
    $datasets[] = [
        'label' => $service_info['nom_service'],
        'data' => $data_values,
        'backgroundColor' => 'rgba(' . hexToRgb($colors[$color_index]) . ', 0.2)',
        'borderColor' => $colors[$color_index],
        'borderWidth' => 2,
        'tension' => 0.3,
        'pointRadius' => 3
    ];
    
    $color_index++;
}

// Fonction pour convertir hex en rgb
function hexToRgb($hex) {
    $hex = str_replace('#', '', $hex);
    
    if (strlen($hex) == 3) {
        $r = hexdec(substr($hex, 0, 1) . substr($hex, 0, 1));
        $g = hexdec(substr($hex, 1, 1) . substr($hex, 1, 1));
        $b = hexdec(substr($hex, 2, 1) . substr($hex, 2, 1));
    } else {
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
    }
    
    return "$r, $g, $b";
}

// Données encodées pour JavaScript
$chart_data = [
    'labels' => $date_labels,
    'datasets' => $datasets
];

$services_for_pie = [];
$total_count = 0;

foreach ($services as $service_id => $service_info) {
    $count = $donnees_services[$service_id]['total_equipements'];
    $services_for_pie[] = [
        'name' => $service_info['nom_service'],
        'count' => $count
    ];
    $total_count += $count;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visualisation des Courbes de Déplacements</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.7.0/chart.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script>
    <style>
        :root {
            --primary-color: #3f51b5;
            --secondary-color: #1976d2;
            --accent-color: #03a9f4;
            --background-light: #f8f9fa;
            --text-primary: #212529;
            --text-secondary: #6c757d;
            --border-color: #e0e0e0;
        }
        
        body {
            font-family: 'Roboto', Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: var(--background-light);
            color: var(--text-primary);
        }
        
        .header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            padding: 1.5rem 2rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            position: relative;
            overflow: hidden;
        }
        
        .header:before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('https://www.transparenttextures.com/patterns/medical-icons.png');
            opacity: 0.07;
            z-index: 0;
        }
        
        .header-content {
            position: relative;
            z-index: 1;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .dashboard-title {
            margin: 0 0 10px 0;
            font-size: 2.2rem;
            font-weight: 700;
        }
        
        .dashboard-subtitle {
            margin: 0;
            font-size: 1.1rem;
            opacity: 0.9;
            font-weight: 400;
        }
        
        .logos {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            padding: 10px 20px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        
        .logos img {
            max-height: 80px;
        }
        
        .card {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            margin-bottom: 25px;
            overflow: hidden;
        }
        
        .card-header {
            padding: 15px 20px;
            background-color: rgba(63, 81, 181, 0.05);
            border-bottom: 1px solid var(--border-color);
        }
        
        .card-title {
            margin: 0;
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--primary-color);
        }
        
        .card-body {
            padding: 20px;
        }
        
        .chart-container {
            position: relative;
            height: 400px;
            padding: 20px;
        }
        
        .period-selector {
            display: flex;
            justify-content: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .period-btn {
            background-color: #e9ecef;
            border: 1px solid #ced4da;
            color: var(--text-primary);
            padding: 8px 15px;
            margin: 0 5px 10px;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 0.9rem;
            text-decoration: none;
        }
        
        .period-btn:hover {
            background-color: #dde2e6;
        }
        
        .period-btn.active {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            color: white;
        }
        
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }
        
        .stat-card {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            padding: 20px;
            text-align: center;
        }
        
        .stat-value {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary-color);
            margin: 10px 0;
        }
        
        .stat-label {
            font-size: 1rem;
            color: var(--text-secondary);
        }
        
        .two-columns {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 25px;
        }
        
        @media (max-width: 992px) {
            .two-columns {
                grid-template-columns: 1fr;
            }
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .table th,
        .table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }
        
        .table th {
            background-color: rgba(63, 81, 181, 0.05);
            font-weight: 600;
            color: var(--text-primary);
        }
        
        .table tr:last-child td {
            border-bottom: none;
        }
        
        .back-btn {
            display: inline-block;
            background-color: var(--primary-color);
            color: white;
            padding: 10px 15px;
            border-radius: 4px;
            text-decoration: none;
            margin-top: 20px;
            transition: background-color 0.3s;
        }
        
        .back-btn:hover {
            background-color: #303f9f;
        }
        
        .biomedical-icon {
            width: 40px;
            height: 40px;
            margin-right: 10px;
            vertical-align: middle;
        }
        
        .footer {
            background-color: #f1f3f5;
            color: var(--text-secondary);
            padding: 30px 0;
            margin-top: 50px;
            border-top: 1px solid #dee2e6;
            text-align: center;
            font-size: 0.9rem;
        }
        
        .top-equipment-bar {
            margin-top: 10px;
            height: 30px;
            background-color: var(--primary-color);
            border-radius: 4px;
            position: relative;
        }
        
        .top-equipment-bar-fill {
            height: 100%;
            border-radius: 4px;
            background-color: var(--accent-color);
            transition: width 0.5s ease-in-out;
        }
        
        .top-equipment-bar-label {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            left: 10px;
            color: white;
            font-weight: 500;
            font-size: 0.9rem;
        }
    </style>
</head>

<body>
    <div class="header">
        <div class="container header-content">
            <h1 class="dashboard-title">
                <svg class="biomedical-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="white">
                    <path d="M0 0h24v24H0V0z" fill="none"/>
                    <path d="M17.73 12.02l3.98-3.98c.39-.39.39-1.02 0-1.41l-4.34-4.34c-.39-.39-1.02-.39-1.41 0l-3.98 3.98L8 2.29C7.8 2.1 7.55 2 7.29 2c-.25 0-.51.1-.7.29L2.25 6.63c-.39.39-.39 1.02 0 1.41l3.98 3.98L2.25 16c-.39.39-.39 1.02 0 1.41l4.34 4.34c.39.39 1.02.39 1.41 0l3.98-3.98 3.98 3.98c.2.2.45.29.71.29.26 0 .51-.1.71-.29l4.34-4.34c.39-.39.39-1.02 0-1.41l-3.99-3.98zM12 9c.55 0 1 .45 1 1s-.45 1-1 1-1-.45-1-1 .45-1 1-1zm-4.71 1.96L3.66 7.34l3.63-3.63 3.62 3.62-3.62 3.63zM7.29 19.36l-3.63-3.62 3.63-3.63 3.62 3.62-3.62 3.63zm10.34-6.73l-3.63 3.62-3.62-3.62 3.62-3.63 3.63 3.63zm0 0"/>
                </svg>
                Visualisation des Courbes de Déplacements
            </h1>
            <p class="dashboard-subtitle">Analyse des déplacements d'équipements biomédicaux entre services</p>
        </div>
    </div>

    <div class="container">
        <!-- Logos -->
        <div class="logos">
            <img src="https://cdn-07.9rayti.com/rsrc/cache/widen_292/uploads/2015/06/Logo-ISSS.jpg" alt="Logo ISSS">
            <img src="https://www.infomediaire.net/wp-content/uploads/2020/05/CHU-Mohammed-VI-de-Marrakech.png" alt="Logo CHU">
        </div>
        
        <!-- Sélecteur de période -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Sélectionner une période</h2>
            </div>
            <div class="card-body">
                <div class="period-selector">
                    <?php foreach ($periodes_disponibles as $periode_val => $periode_label): ?>
                        <a href="?periode=<?= $periode_val ?>" class="period-btn <?= ($periode == $periode_val) ? 'active' : '' ?>">
                            <?= $periode_label ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <!-- Statistiques globales -->
        <div class="stats-container">
            <div class="stat-card">
                <div class="stat-value"><?= array_sum(array_column($top_equipements, 'nombre_deplacements')) ?></div>
                <div class="stat-label">Déplacements totaux sur la période</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= count($services) ?></div>
                <div class="stat-label">Services actifs</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= $total_count ?></div>
                <div class="stat-label">Équipements biomédicaux</div>
            </div>
            <?php if (count($top_equipements) > 0): ?>
            <div class="stat-card">
                <div class="stat-value"><?= $top_equipements[0]['nombre_deplacements'] ?></div>
                <div class="stat-label">Déplacements de l'équipement le plus mobile</div>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Graphique principal des déplacements par service -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Courbes de déplacements par service</h2>
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="mainChart"></canvas>
                </div>
            </div>
        </div>
        
        <!-- Section à deux colonnes -->
        <div class="two-columns">
            <!-- Top équipements les plus déplacés -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Les équipements les plus déplacés</h2>
                </div>
                <div class="card-body" style="padding: 20px;">
                    <?php if (count($top_equipements) > 0): ?>
                        <?php 
                        $max_deplacements = $top_equipements[0]['nombre_deplacements']; 
                        foreach ($top_equipements as $index => $equipement): 
                            $percentage = ($equipement['nombre_deplacements'] / $max_deplacements) * 100;
                        ?>
                            <div style="margin-bottom: 15px;">
                                <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                                    <span><strong><?= $equipement['nom_equipement'] ?></strong> (ID: <?= $equipement['id'] ?>)</span>
                                    <span><strong><?= $equipement['nombre_deplacements'] ?></strong> déplacements</span>
                                </div>
                                <div class="top-equipment-bar">
                                    <div class="top-equipment-bar-fill" style="width: <?= $percentage ?>%;"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>Aucun déplacement n'a été enregistré pendant cette période.</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Distribution des équipements par service -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Répartition actuelle des équipements</h2>
                </div>
                <div class="card-body">
                    <div class="chart-container" style="height: 350px;">
                        <canvas id="pieChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        
        
        
        <a href="../admin/index.php" class="back-btn">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" style="vertical-align: text-bottom; margin-right: 5px;">
                <path fill-rule="evenodd" d="M11.354 1.646a.5.5 0 0 1 0 .708L5.707 8l5.647 5.646a.5.5 0 0 1-.708.708l-6-6a.5.5 0 0 1 0-.708l6-6a.5.5 0 0 1 .708 0z"/>
            </svg>
            Retour au tableau de bord
        </a>
    </div>
    
    <div class="footer">
        <div class="container">
            <p>© <?= date('Y') ?> CHU Mohammed VI - Module de gestion des équipements biomédicaux</p>
            <p>Développé pour le service de génie biomédical</p>
        </div>
    </div>

    <script>
        // Fonction pour formater les dates
        function formatDate(dateString) {
            const options = { day: '2-digit', month: '2-digit' };
            return new Date(dateString).toLocaleDateString('fr-FR', options);
        }
        
        // Configuration du graphique principal (courbes de déplacements)
        const ctx = document.getElementById('mainChart').getContext('2d');
        const chartData = <?= json_encode($chart_data) ?>;
        
        // Formater les dates pour l'affichage
        chartData.labels = chartData.labels.map(date => formatDate(date));
        
        const mainChart = new Chart(ctx, {
            type: 'line',
            data: chartData,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        callbacks: {
                            title: function(tooltipItems) {
                                return tooltipItems[0].label;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Nombre de déplacements'
                        },
                        ticks: {
                            precision: 0
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Date'
                        }
                    }
                }
            }
        });
        
        // Configuration du graphique circulaire (répartition des équipements)
        const pieCtx = document.getElementById('pieChart').getContext('2d');
        const pieData = {
            labels: <?= json_encode(array_column($services_for_pie, 'name')) ?>,
            datasets: [{
                data: <?= json_encode(array_column($services_for_pie, 'count')) ?>,
                backgroundColor: <?= json_encode($colors) ?>,
                borderWidth: 1
            }]
        };
        
        const pieChart = new Chart(pieCtx, {
            type: 'pie',
            data: pieData,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            boxWidth: 15,
                            font: {
                                size: 11
                            }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.raw || 0;
                                const total = context.dataset.data.reduce((acc, val) => acc + val, 0);
                                const percentage = Math.round((value / total) * 100);
                                return `${label}: ${value} équipements (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>