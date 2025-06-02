<?php
include_once("../db/index.php");
session_start();

if (!isset($_SESSION["nom_prenom"]) || $_SESSION["role"] !== "admin") {
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

// Récupération de l'équipement_id sélectionné pour le filtre
$service_filter = isset($_GET['service_source_id']) ? intval($_GET['service_source_id']) : 0;

// Préparation de la requête des déplacements avec ou sans filtre
$sql_dep = "
    SELECT 
        d.id,
        d.equipement_id,
        d.date_heure,
        d.service_source_id,
        d.service_destination_id,
        ss.nom_service AS service_source,
        sd.nom_service AS service_destination
    FROM deplacements d
    LEFT JOIN services ss ON d.service_source_id = ss.id
    LEFT JOIN services sd ON d.service_destination_id = sd.id";

if ($service_filter > 0) {
    $sql_dep .= " WHERE d.service_source_id = ?";
}

$sql_dep .= " ORDER BY d.date_heure DESC";

$dep = $db->prepare($sql_dep);

if ($service_filter > 0) {
    $dep->bind_param("i", $service_filter);
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
    <title>Dashboard Administrateur</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --success-color: #2ecc71;
            --danger-color: #e74c3c;
            --warning-color: #f39c12;
            --light-color: #ecf0f1;
            --dark-color: #2c3e50;
            --border-radius: 8px;
            --box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f5f7fa;
            padding: 0;
            margin: 0;
        }

        .container {
            width: 95%;
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        header {
            background-color: var(--primary-color);
            color: white;
            padding: 1rem;
            box-shadow: var(--box-shadow);
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1400px;
            margin: 0 auto;
        }

        .logo-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.5rem 0;
        }

        .logo-container img {
            height: 60px;
            max-width: 180px;
            object-fit: contain;
        }

        .user-section {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .user-info {
            text-align: right;
        }

        .user-name {
            font-weight: bold;
            font-size: 1.1rem;
        }

        .user-role {
            font-size: 0.9rem;
            color: rgba(255, 255, 255, 0.8);
        }

        .btn {
            display: inline-block;
            padding: 0.6rem 1.2rem;
            border: none;
            border-radius: var(--border-radius);
            background-color: var(--secondary-color);
            color: white;
            text-decoration: none;
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
            text-align: center;
        }

        .btn:hover {
            opacity: 0.9;
            transform: translateY(-2px);
        }

        .btn-sm {
            padding: 0.4rem 0.8rem;
            font-size: 0.9rem;
        }

        .btn-primary {
            background-color: var(--secondary-color);
        }

        .btn-success {
            background-color: var(--success-color);
        }

        .btn-danger {
            background-color: var(--danger-color);
        }

        .btn-warning {
            background-color: var(--warning-color);
        }

        .btn-info {
            background-color: #17a2b8;
        }

        h1,
        h2,
        h3 {
            color: var(--primary-color);
            margin-bottom: 1rem;
        }

        h1 {
            font-size: 1.8rem;
            margin-top: 1.5rem;
        }

        h2 {
            font-size: 1.5rem;
            margin-top: 2rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid var(--primary-color);
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            flex-wrap: wrap;
            gap: 10px;
        }

        .section-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .card {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            margin-bottom: 2rem;
            overflow: hidden;
        }

        .card-header {
            background-color: var(--primary-color);
            color: white;
            padding: 1rem;
            font-weight: bold;
        }

        .card-body {
            padding: 1rem;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 1rem;
            background-color: white;
            box-shadow: var(--box-shadow);
            border-radius: var(--border-radius);
            overflow: hidden;
        }

        th,
        td {
            padding: 0.8rem;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background-color: var(--primary-color);
            color: white;
            font-weight: 500;
            position: sticky;
            top: 0;
        }

        tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        tr:hover {
            background-color: #f1f1f1;
        }

        .table-responsive {
            overflow-x: auto;
            max-height: 500px;
            margin-bottom: 2rem;
        }

        .scroll-link {
            color: var(--secondary-color);
            cursor: pointer;
            text-decoration: underline;
            font-weight: 500;
        }

        /* Indicateurs LED */
        .led {
            display: inline-block;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-right: 5px;
        }

        .led-green {
            background-color: var(--success-color);
            box-shadow: 0 0 5px var(--success-color);
        }

        .led-yellow {
            background-color: var(--warning-color);
            box-shadow: 0 0 5px var(--warning-color);
        }

        .led-red {
            background-color: var(--danger-color);
            box-shadow: 0 0 5px var(--danger-color);
        }

        /* Légende */
        .legend {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            background-color: white;
            padding: 15px;
            border-radius: var(--border-radius);
            margin-bottom: 1rem;
            box-shadow: var(--box-shadow);
        }

        .legend-title {
            font-weight: bold;
            width: 100%;
            margin-bottom: 8px;
        }

        .legend-item {
            display: flex;
            align-items: center;
            margin-right: 20px;
        }

        .action-buttons {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
        }

        /* Formulaire de filtre */
        .filter-form {
            background-color: white;
            padding: 1rem;
            border-radius: var(--border-radius);
            margin-bottom: 1rem;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 10px;
            box-shadow: var(--box-shadow);
        }

        .filter-form label {
            font-weight: 500;
        }

        .filter-form select {
            padding: 0.5rem;
            border-radius: 4px;
            border: 1px solid #ddd;
            background-color: white;
            min-width: 200px;
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

        /* Responsive design */
        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                text-align: center;
            }

            .logo-container {
                margin-bottom: 1rem;
            }

            .user-section {
                flex-direction: column;
                margin-bottom: 1rem;
            }

            .user-info {
                text-align: center;
                margin-bottom: 0.5rem;
            }

            .filter-form {
                flex-direction: column;
                align-items: stretch;
            }

            .filter-form select,
            .filter-form button {
                width: 100%;
            }

            .btn {
                width: 100%;
                margin-bottom: 0.5rem;
            }

            .action-buttons {
                flex-direction: column;
            }

            .section-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .section-header .btn {
                margin-top: 0.5rem;
            }
        }
    </style>
</head>

<body>
    <header>
        <div class="header-content">
            <div class="logo-container">
                <img src="https://cdn-07.9rayti.com/rsrc/cache/widen_292/uploads/2015/06/Logo-ISSS.jpg" alt="Logo ISSS">
                <img src="https://www.infomediaire.net/wp-content/uploads/2020/05/CHU-Mohammed-VI-de-Marrakech.png"
                    alt="Logo CHU">
            </div>
            <div class="user-section">
                <div class="user-info">
                    <div class="user-name"><?= htmlspecialchars($_SESSION["nom_prenom"]); ?></div>
                    <div class="user-role">Admin</div>
                </div>
                <a href="../déconnexion.php" class="btn btn-danger btn-sm">
                    <i class="fas fa-sign-out-alt"></i> Déconnexion
                </a>
            </div>
        </div>
    </header>

    <div class="container">
        <?php if ($message): ?>
            <div class="notification" id="notification">
                <span class="close-notification" onclick="this.parentElement.style.display='none';">&times;</span>
                <?= $message ?>
            </div>
        <?php endif; ?>

        <!-- Tableau 1 : Déplacements -->
        <div class="section-header">
            <h2>Liste des Déplacements</h2>
            <div class="section-actions">
                <a href="../visualisation/courbe.php" class="btn btn-info">
                    <i class="fas fa-chart-line"></i> Visualiser les courbes
                </a>
            </div>
        </div>

        <!-- Formulaire de filtre par équipement -->
        <form method="GET" class="filter-form">
            <label for="service_source_id">Filtrer par Service Source:</label>
            <select name="service_source_id" id="service_source_id" onchange="this.form.submit()">
                <option value="0">Tous les services</option>
                <?php
                $services = $db->query("SELECT id, nom_service FROM services ORDER BY nom_service ASC");
                while ($service = $services->fetch_assoc()) {
                    $selected = ($service_filter == $service['id']) ? 'selected' : '';
                    echo "<option value='{$service['id']}' $selected>{$service['nom_service']}</option>";
                }
                ?>
            </select>
        </form>


        <div class="table-responsive">
            <table>
                <tr>
                    <th>ID</th>
                    <th>Équipement ID</th>
                    <th>Date / Heure</th>
                    <th>Service Source</th>
                    <th>Service Destination</th>
                </tr>
                <?php
                if (count($deplacements) > 0) {
                    foreach ($deplacements as $row) {
                        echo "<tr>
                                <td>{$row['id']}</td>
                                <td class='scroll-link' data-id='{$row['equipement_id']}'>{$row['equipement_id']}</td>
                                <td>{$row['date_heure']}</td>
                                <td>{$row['service_source']}</td>
                                <td>{$row['service_destination']}</td>
                            </tr>";
                    }
                } else {
                    echo "<tr><td colspan='5'>Aucun déplacement trouvé pour cet équipement.</td></tr>";
                }
                ?>
            </table>
        </div>

        <!-- Tableau 2 : Equipements -->
        <div class="section-header">
            <h2>Liste des Équipements</h2>
            <a href="../db/table_equipements/ajouter.php" class="btn btn-success">
                <i class="fas fa-plus"></i> Ajouter un Équipement
            </a>
        </div>

        <!-- Légende pour les indicateurs -->
        <div class="legend">
            <div class="legend-title">
                <i class="fas fa-info-circle"></i> Légende des indicateurs:
            </div>
            <div class="legend-item"><span class="led led-green"></span> Équipement disponible dans son service
                d'origine</div>
            <div class="legend-item"><span class="led led-yellow"></span> Équipement déplacé vers un autre service</div>
            <div class="legend-item"><span class="led led-red"></span> Équipement en panne</div>
        </div>

        <div class="table-responsive">
            <table>
                <tr>
                    <th>État</th>
                    <th>ID</th>
                    <th>Nom équipement</th>
                    <th>N° inventaire</th>
                    <th>N° série</th>
                    <th>Marque</th>
                    <th>Modèle</th>
                    <th>Tag ID</th>
                    <th>Service source</th>
                    <th>Service actuel</th>
                    <th>Actions</th>
                </tr>
                <?php
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

                $result = $db->query($query);

                while ($row = $result->fetch_assoc()) {
                    // Déterminer l'état de l'équipement
                    $led_class = 'led-green'; // Par défaut, disponible dans son service d'origine
                
                    if ($row['est_en_panne']) {
                        $led_class = 'led-red'; // En panne
                    } elseif ($row['service_source_id'] != $row['service_actuel_id']) {
                        $led_class = 'led-yellow'; // Déplacé
                    }

                    echo "<tr id='equipement-{$row['id']}'>
                            <td><span class='led {$led_class}'></span></td>
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
                                <a class='btn btn-sm btn-success' href='../db/table_equipements/modifier.php?id={$row['id']}'>
                                    <i class='fas fa-edit'></i> Modifier
                                </a>
                                
                                <a class='btn btn-sm btn-danger' href='../db/table_equipements/supprimer.php?id={$row['id']}' onclick='return confirm(\"Êtes-vous sûr de vouloir supprimer cet équipement ?\")'>
                                    <i class='fas fa-trash'></i> Supprimer
                                </a>
                            </td>
                          </tr>";
                }
                ?>
            </table>
        </div>

        <!-- Tableau 3 : Services -->
        <div class="section-header">
            <h2>Liste des Services</h2>
            <a href="../db/table_services/ajouter.php" class="btn btn-success">
                <i class="fas fa-plus"></i> Ajouter un Service
            </a>
        </div>
        <div class="table-responsive">
            <table>
                <tr>
                    <th>ID</th>
                    <th>Nom du service</th>
                    <th>Adresse MAC ESP</th>
                    <th>Actions</th>
                </tr>
                <?php
                $result = $db->query("SELECT * FROM services");
                while ($row = $result->fetch_assoc()) {
                    echo "<tr>
                            <td>{$row['id']}</td>
                            <td>{$row['nom_service']}</td>
                            <td>{$row['adresse_MAC_esp']}</td>
                            <td class='action-buttons'>
                                <a class='btn btn-sm btn-success' href='../db/table_services/modifier.php?id={$row['id']}'>
                                    <i class='fas fa-edit'></i> Modifier
                                </a>
                                <a class='btn btn-sm btn-danger' href='../db/table_services/supprimer.php?id={$row['id']}' onclick='return confirm(\"Êtes-vous sûr de vouloir supprimer ce service ?\")'>
                                    <i class='fas fa-trash'></i> Supprimer
                                </a>
                            </td>
                          </tr>";
                }
                ?>
            </table>
        </div>

        <!-- Tableau 4 : Utilisateurs -->
        <div class="section-header">
            <h2>Liste des Utilisateurs</h2>
            <a href="../db/table_utilisateurs/ajouter.php" class="btn btn-success">
                <i class="fas fa-plus"></i> Ajouter un Utilisateur
            </a>
        </div>
        <div class="table-responsive">
            <table>
                <tr>
                    <th>ID</th>
                    <th>Nom & Prénom</th>
                    <th>Rôle</th>
                    <th>Email</th>
                    <th>Mot de passe</th>
                    <th>Actions</th>
                </tr>
                <?php
                $result = $db->query("SELECT * FROM utilisateurs");
                while ($row = $result->fetch_assoc()) {
                    echo "<tr>
                            <td>{$row['id']}</td>
                            <td>{$row['nom_prenom']}</td>
                            <td>{$row['role']}</td>
                            <td>{$row['email']}</td>
                            <td>{$row['mot_de_passe']}</td>
                            <td class='action-buttons'>
                                <a class='btn btn-sm btn-success' href='../db/table_utilisateurs/modifier.php?id={$row['id']}'>
                                    <i class='fas fa-edit'></i> Modifier
                                </a>
                                <a class='btn btn-sm btn-danger' href='../db/table_utilisateurs/supprimer.php?id={$row['id']}' onclick='return confirm(\"Êtes-vous sûr de vouloir supprimer cet utilisateur ?\")'>
                                    <i class='fas fa-trash'></i> Supprimer
                                </a>
                            </td>
                          </tr>";
                }
                ?>
            </table>
        </div>
    </div>

    <!-- Script JS pour surbrillance dans la table des équipements et fermeture automatique des notifications -->
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            // Gestion des liens de défilement et mise en surbrillance
            const links = document.querySelectorAll('.scroll-link');

            links.forEach(link => {
                link.addEventListener('click', function (e) {
                    e.preventDefault();
                    const targetId = this.getAttribute('data-id');
                    const target = document.getElementById('equipement-' + targetId);

                    if (target) {
                        // Supprimer les surbrillances précédentes
                        document.querySelectorAll('tr').forEach(row => {
                            row.style.backgroundColor = '';
                            row.style.boxShadow = '';
                        });

                        // Défilement fluide
                        target.scrollIntoView({ behavior: 'smooth', block: 'center' });

                        // Mettre en surbrillance la ligne
                        target.style.backgroundColor = '#e3f2fd';
                        target.style.boxShadow = '0 0 10px rgba(52, 152, 219, 0.5)';

                        // Retirer la surbrillance après 3 secondes
                        setTimeout(() => {
                            target.style.backgroundColor = '';
                            target.style.boxShadow = '';
                        }, 3000);
                    }
                });
            });

            // Fermeture automatique des notifications après 5 secondes
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