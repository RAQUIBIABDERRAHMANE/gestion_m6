<?php
session_start();
include_once("../db/index.php");

// Redirect if not 'utili'
if (!isset($_SESSION["nom_prenom"]) || $_SESSION["role"] !== "utili") {
    header('Location: ../');
    exit;
}

// Gestion des demandes de changement d'état pour les équipements
if (isset($_GET['action']) && $_GET['action'] == 'toggle_panne' && isset($_GET['id'])) {
    $equipement_id = $_GET['id'];

    // Récupérer l'état actuel de l'équipement
    $stmt = $db->prepare("SELECT est_en_panne FROM equipements WHERE id = ?");
    $stmt->bind_param("i", $equipement_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    // Inverser l'état de panne
    $nouvel_etat = $row['est_en_panne'] ? 0 : 1;

    // Mettre à jour l'état
    $stmt = $db->prepare("UPDATE equipements SET est_en_panne = ? WHERE id = ?");
    $stmt->bind_param("ii", $nouvel_etat, $equipement_id);
    $stmt->execute();

    // Rediriger pour éviter la réexécution lors d'un rafraîchissement
    header('Location: index.php?message=État de panne mis à jour');
    exit;
}

// Récupération de tous les équipements avec le service actuel (dernier déplacement)
$query = "SELECT e.*, 
         COALESCE(d.service_destination_id, e.service_source_id) as service_actuel_id,
         (SELECT nom_service FROM services WHERE id = e.service_source_id) as nom_service_source,
         (SELECT nom_service FROM services WHERE id = COALESCE(d.service_destination_id, e.service_source_id)) as nom_service_actuel
         FROM equipements e
         LEFT JOIN (
             SELECT equipement_id, service_destination_id
             FROM deplacements
             WHERE (equipement_id, date_heure) IN (
                 SELECT equipement_id, MAX(date_heure)
                 FROM deplacements
                 GROUP BY equipement_id
             )
         ) d ON e.id = d.equipement_id
         ORDER BY e.id";

$result_equipements = $db->query($query);

// Récupération de l'équipement_id sélectionné pour le filtre
$equipement_filter = isset($_GET['equipement_id']) ? intval($_GET['equipement_id']) : 0;

// Préparation de la requête des déplacements avec ou sans filtre
$sql_dep = "
    SELECT 
        d.id,
        d.equipement_id,
        d.date_heure,
        ss.nom_service AS service_source,
        sd.nom_service AS service_destination
    FROM deplacements d
    LEFT JOIN services ss ON d.service_source_id = ss.id
    LEFT JOIN services sd ON d.service_destination_id = sd.id";

if ($equipement_filter > 0) {
    $sql_dep .= " WHERE d.equipement_id = ?";
}
$sql_dep .= " ORDER BY d.date_heure DESC";

$dep = $db->prepare($sql_dep);

if ($equipement_filter > 0) {
    $dep->bind_param("i", $equipement_filter);
}

$dep->execute();
$result_deplacements = $dep->get_result();

$deplacements = [];
while ($row = $result_deplacements->fetch_assoc()) {
    $deplacements[] = $row;
}

// Vérifier si un message de notification existe
$message = isset($_GET['message']) ? htmlspecialchars($_GET['message']) : '';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Utilisateur</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Variables pour la cohérence des couleurs et styles */
        :root {
            --primary: #3f51b5;
            --primary-light: #e8eaf6;
            --primary-dark: #303f9f;
            --secondary: #ff4081;
            --success: #4caf50;
            --warning: #ff9800;
            --danger: #f44336;
            --light-grey: #f5f5f5;
            --dark-grey: #424242;
            --medium-grey: #9e9e9e;
            --white: #ffffff;
            --shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --border-radius: 8px;
            --transition: all 0.3s ease;
        }

        /* Reset et styles de base */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', sans-serif;
            background-color: #f8f9fa;
            color: #333;
            line-height: 1.6;
        }

        .container {
            width: 95%;
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px 0;
        }

        /* Header et navigation */
        header {
            background-color: var(--white);
            box-shadow: var(--shadow);
            margin-bottom: 30px;
            border-radius: var(--border-radius);
            padding: 15px 20px;
        }

        .header-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .logo-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
        }

        .logo-container img {
            max-height: 70px;
            object-fit: contain;
        }

        .user-nav {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-top: 15px;
        }

        .welcome-message {
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 500;
            color: var(--dark-grey);
        }

        .welcome-message i {
            color: var(--primary);
            font-size: 1.2rem;
        }

        .logout-btn {
            background-color: var(--danger);
            color: white;
            padding: 8px 15px;
            border-radius: var(--border-radius);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 500;
            transition: var(--transition);
        }

        .logout-btn:hover {
            background-color: #d32f2f;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        }

        /* Cards */
        .card {
            background-color: var(--white);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            margin-bottom: 30px;
            overflow: hidden;
        }

        .card-header {
            background-color: var(--primary);
            color: white;
            padding: 15px 20px;
            font-size: 1.1rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card-body {
            padding: 20px;
        }

        /* Filter */
        .filter-form {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            align-items: flex-end;
        }

        .form-group {
            flex: 1;
            min-width: 250px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--dark-grey);
        }

        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: var(--border-radius);
            font-size: 1rem;
            transition: var(--transition);
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 2px rgba(63, 81, 181, 0.2);
        }

        .btn {
            background-color: var(--primary);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: var(--border-radius);
            cursor: pointer;
            font-size: 1rem;
            font-weight: 500;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn:hover {
            background-color: var(--primary-dark);
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        }

        /* Tables */
        .table-container {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        thead th {
            background-color: var(--primary-light);
            color: var(--primary-dark);
            font-weight: 600;
            text-align: left;
            padding: 12px 15px;
            border-bottom: 2px solid var(--primary);
        }

        tbody td {
            padding: 12px 15px;
            border-bottom: 1px solid #eee;
        }

        tbody tr:hover {
            background-color: rgba(63, 81, 181, 0.05);
        }

        .table-link {
            color: var(--primary);
            text-decoration: underline;
            cursor: pointer;
            font-weight: 500;
        }

        .table-link:hover {
            color: var(--primary-dark);
        }

        /* Status Indicators */
        .legend {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            background-color: var(--light-grey);
            padding: 15px;
            border-radius: var(--border-radius);
            margin-bottom: 20px;
        }

        .legend-title {
            font-weight: 600;
            margin-right: 10px;
            display: flex;
            align-items: center;
            gap: 5px;
            color: var(--dark-grey);
        }

        .legend-items {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .status-indicator {
            display: inline-block;
            width: 12px;
            height: 12px;
            border-radius: 50%;
        }

        .status-green {
            background-color: var(--success);
        }

        .status-yellow {
            background-color: var(--warning);
        }

        .status-red {
            background-color: var(--danger);
        }

        /* Action buttons */
        .action-buttons {
            display: flex;
            gap: 5px;
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 0.9rem;
        }

        .btn-success {
            background-color: var(--success);
        }

        .btn-success:hover {
            background-color: #388e3c;
        }

        .btn-warning {
            background-color: var(--warning);
        }

        .btn-warning:hover {
            background-color: #f57c00;
        }

        /* Notification */
        .notification {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: var(--border-radius);
            background-color: #d4edda;
            color: #155724;
            border-left: 4px solid #155724;
            animation: fadeOut 5s forwards;
            position: relative;
        }

        .notification.error {
            background-color: #f8d7da;
            color: #721c24;
            border-left-color: #721c24;
        }

        .close-notification {
            position: absolute;
            right: 10px;
            top: 10px;
            cursor: pointer;
            color: inherit;
        }

        @keyframes fadeOut {
            0% {
                opacity: 1;
            }
            80% {
                opacity: 1;
            }
            100% {
                opacity: 0;
            }
        }

        /* Responsive */
        @media (max-width: 768px) {
            .logo-container {
                flex-direction: column;
                gap: 15px;
            }
            
            .user-nav {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .filter-form {
                flex-direction: column;
                align-items: stretch;
            }
            
            .legend {
                flex-direction: column;
            }
            
            .legend-items {
                flex-direction: column;
                gap: 10px;
            }

            .action-buttons {
                flex-direction: column;
            }

            .btn-sm {
                width: 100%;
            }
        }

        /* Highlighted row */
        .highlight {
            background-color: rgba(63, 81, 181, 0.15) !important;
            transition: background-color 0.5s ease;
        }

        /* Empty state */
        .empty-state {
            text-align: center;
            padding: 30px 20px;
            color: var(--medium-grey);
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 15px;
        }

        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            gap: 5px;
            margin-top: 20px;
        }

        .page-item {
            list-style: none;
        }

        .page-link {
            display: inline-block;
            padding: 8px 12px;
            border-radius: 4px;
            background-color: var(--white);
            border: 1px solid #ddd;
            color: var(--primary);
            text-decoration: none;
            transition: var(--transition);
        }

        .page-link:hover, .page-link.active {
            background-color: var(--primary);
            color: white;
            border-color: var(--primary);
        }

        /* Back to top button */
        .back-to-top {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background-color: var(--primary);
            color: white;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            box-shadow: var(--shadow);
            transition: var(--transition);
            opacity: 0;
            visibility: hidden;
        }

        .back-to-top.visible {
            opacity: 1;
            visibility: visible;
        }

        .back-to-top:hover {
            background-color: var(--primary-dark);
            transform: translateY(-3px);
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <header>
            <div class="header-top">
                <div class="logo-container">
                    <img src="https://cdn-07.9rayti.com/rsrc/cache/widen_292/uploads/2015/06/Logo-ISSS.jpg" alt="Logo ISSS">
                    <img src="https://www.infomediaire.net/wp-content/uploads/2020/05/CHU-Mohammed-VI-de-Marrakech.png" alt="Logo CHU Mohammed VI">
                </div>
            </div>
            <div class="user-nav">
                <div class="welcome-message">
                    <i class="fas fa-user-circle"></i>
                    Bienvenue, <?= htmlspecialchars($_SESSION['nom_prenom']) ?> (Utilisateur)
                </div>
                <a href="../déconnexion.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i> Déconnexion
                </a>
            </div>
        </header>

        <!-- Notification Message -->
        <?php if ($message): ?>
            <div class="notification" id="notification">
                <span class="close-notification" onclick="this.parentElement.style.display='none';">&times;</span>
                <?= $message ?>
            </div>
        <?php endif; ?>

        <!-- Filter Section -->
        <div class="card">
            <div class="card-header">
                <i class="fas fa-filter"></i> Filtrer les Déplacements
            </div>
            <div class="card-body">
                <form method="GET" class="filter-form">
                    <div class="form-group">
                        <label for="equipement_id">Sélectionnez l'équipement :</label>
                        <select class="form-control" name="equipement_id" id="equipement_id">
                            <option value="0">-- Tous les équipements --</option>
                            <?php
                            // Remplir le select avec les équipements
                            $result_equipements_select = $db->query("SELECT id, nom_equipement FROM equipements ORDER BY id");
                            while ($equip = $result_equipements_select->fetch_assoc()) {
                                $selected = ($equipement_filter == $equip['id']) ? 'selected' : '';
                                echo "<option value='{$equip['id']}' $selected>{$equip['id']} - {$equip['nom_equipement']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <button type="submit" class="btn">
                        <i class="fas fa-search"></i> Rechercher
                    </button>
                </form>
            </div>
        </div>

        <!-- Déplacements Section -->
        <div class="card">
            <div class="card-header">
                <i class="fas fa-exchange-alt"></i> Liste des Déplacements
            </div>
            <div class="card-body">
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Équipement ID</th>
                                <th>Date & Heure</th>
                                <th>Service Source</th>
                                <th>Service Destination</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($deplacements) > 0): ?>
                                <?php foreach ($deplacements as $deplacement): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($deplacement['id']) ?></td>
                                        <td>
                                            <a href="#equipement-<?= htmlspecialchars($deplacement['equipement_id']) ?>" class="table-link"
                                                data-id="<?= htmlspecialchars($deplacement['equipement_id']) ?>">
                                                <?= htmlspecialchars($deplacement['equipement_id']) ?>
                                            </a>
                                        </td>
                                        <td><?= htmlspecialchars($deplacement['date_heure']) ?></td>
                                        <td><?= htmlspecialchars($deplacement['service_source']) ?></td>
                                        <td><?= htmlspecialchars($deplacement['service_destination']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="empty-state">
                                        <i class="fas fa-info-circle"></i>
                                        <p>Aucun déplacement trouvé pour cet équipement.</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Équipements Section -->
        <div class="card">
            <div class="card-header">
                <i class="fas fa-laptop-medical"></i> Liste des Équipements
            </div>
            <div class="card-body">
                <!-- Légende des indicateurs -->
                <div class="legend">
                    <div class="legend-title">
                        <i class="fas fa-info-circle"></i> Légende des statuts:
                    </div>
                    <div class="legend-items">
                        <div class="legend-item">
                            <span class="status-indicator status-green"></span> Disponible dans son service d'origine
                        </div>
                        <div class="legend-item">
                            <span class="status-indicator status-yellow"></span> Déplacé vers un autre service
                        </div>
                        <div class="legend-item">
                            <span class="status-indicator status-red"></span> Équipement en panne
                        </div>
                    </div>
                </div>

                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>État</th>
                                <th>ID</th>
                                <th>Nom équipement</th>
                                <th>Num inventaire</th>
                                <th>Num série</th>
                                <th>Marque</th>
                                <th>Modèle</th>
                                <th>Tag ID</th>
                                <th>Service source</th>
                                <th>Service actuel</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $result_equipements->data_seek(0); // Remettre le curseur au début
                            while ($row = $result_equipements->fetch_assoc()) {
                                $status_class = 'status-green';
                                $status_title = 'Disponible dans son service d\'origine';
                                
                                if ($row['est_en_panne']) {
                                    $status_class = 'status-red';
                                    $status_title = 'Équipement en panne';
                                } elseif ($row['service_source_id'] != $row['service_actuel_id']) {
                                    $status_class = 'status-yellow';
                                    $status_title = 'Déplacé vers un autre service';
                                }
                                
                                echo "<tr id='equipement-{$row['id']}'>
                                        <td title='{$status_title}'><span class='status-indicator {$status_class}'></span></td>
                                        <td>{$row['id']}</td>
                                        <td>{$row['nom_equipement']}</td>
                                        <td>{$row['num_inventaire']}</td>
                                        <td>{$row['num_serie']}</td>
                                        <td>{$row['marque']}</td>
                                        <td>{$row['modele']}</td>
                                        <td>{$row['tag_id']}</td>
                                        <td>{$row['nom_service_source']}</td>
                                        <td>{$row['nom_service_actuel']}</td>
                                        <td class='action-buttons'>
                                            <a class='btn btn-sm " . ($row['est_en_panne'] ? "btn-success" : "btn-warning") . "' href='index.php?action=toggle_panne&id={$row['id']}'>
                                                <i class='fas " . ($row['est_en_panne'] ? "fa-wrench" : "fa-exclamation-triangle") . "'></i> 
                                                " . ($row['est_en_panne'] ? "Réparer" : "Signaler panne") . "
                                            </a>
                                        </td>
                                      </tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Back to top button -->
    <a href="#" class="back-to-top" id="backToTop">
        <i class="fas fa-arrow-up"></i>
    </a>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            // Scroll links functionality
            const links = document.querySelectorAll('.table-link');

            links.forEach(link => {
                link.addEventListener('click', function (e) {
                    e.preventDefault();
                    const targetId = this.getAttribute('data-id');
                    const target = document.getElementById('equipement-' + targetId);

                    if (target) {
                        // Remove highlight from all rows
                        document.querySelectorAll('tr').forEach(row => {
                            row.classList.remove('highlight');
                        });

                        // Smooth scroll to target and highlight
                        target.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        target.classList.add('highlight');
                        
                        // Remove highlight after 3 seconds
                        setTimeout(() => {
                            target.classList.remove('highlight');
                        }, 3000);
                    }
                });
            });

            // Back to top functionality
            const backToTopButton = document.getElementById('backToTop');
            
            window.addEventListener('scroll', () => {
                if (window.pageYOffset > 300) {
                    backToTopButton.classList.add('visible');
                } else {
                    backToTopButton.classList.remove('visible');
                }
            });
            
            backToTopButton.addEventListener('click', (e) => {
                e.preventDefault();
                window.scrollTo({ top: 0, behavior: 'smooth' });
            });

            // Auto close notification after 5 seconds
            const notification = document.getElementById('notification');
            if (notification) {
                setTimeout(() => {
                    notification.style.display = 'none';
                }, 5000);
            }
        });
    </script>
</body>
</html>