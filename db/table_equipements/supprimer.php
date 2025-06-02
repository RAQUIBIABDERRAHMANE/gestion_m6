<?php
include_once("../../db/index.php");
session_start();

if (!isset($_SESSION["nom_prenom"]) || $_SESSION["role"] !== "admin") {
    header('Location: ../../');
    exit;
}

// Vérifier que l'ID est bien spécifié dans l'URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: ../../admin/index.php?message=ID équipement non spécifié');
    exit;
}

$id = $_GET['id'];

// Récupération des données de l'équipement pour l'affichage
$stmt = $db->prepare("SELECT * FROM equipements WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: ../../admin/index.php?message=Équipement non trouvé');
    exit;
}

$equipement = $result->fetch_assoc();

// Traitement de la suppression lors de la confirmation
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['confirmer']) && $_POST['confirmer'] === 'oui') {
        // Suppression de l'équipement
        $stmt = $db->prepare("DELETE FROM equipements WHERE id = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            // Redirection vers la page dashboard avec un message de succès
            header("Location: ../../admin/index.php?message=Équipement supprimé avec succès");
            exit;
        } else {
            $error = "Erreur lors de la suppression : " . $stmt->error;
        }
        
        $stmt->close();
    } else {
        // Si l'utilisateur a cliqué sur "Annuler"
        header("Location: ../../admin/index.php");
        exit;
    }
}

// Récupération du nom du service source
$serviceStmt = $db->prepare("SELECT nom_service FROM services WHERE id = ?");
$serviceStmt->bind_param("i", $equipement['service_source_id']);
$serviceStmt->execute();
$serviceResult = $serviceStmt->get_result();
$service = $serviceResult->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Supprimer un Équipement</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }
        
        h1 {
            color: #333;
        }
        
        .confirmation-box {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background-color: #f9f9f9;
        }
        
        .equipment-details {
            margin-bottom: 20px;
        }
        
        .equipment-details p {
            margin: 5px 0;
        }
        
        .warning {
            color: #e74c3c;
            font-weight: bold;
            margin: 20px 0;
        }
        
        .buttons {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
        }
        
        .confirm-btn {
            background-color: #e74c3c;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        
        .cancel-btn {
            background-color: #7f8c8d;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        
        .confirm-btn:hover {
            background-color: #c0392b;
        }
        
        .cancel-btn:hover {
            background-color: #6c7a7d;
        }
        
        .error {
            color: red;
            margin-bottom: 15px;
        }
        
        .back-link {
            display: inline-block;
            margin-top: 20px;
            color: #2196F3;
            text-decoration: none;
        }
        
        .back-link:hover {
            text-decoration: underline;
        }
        
        /* Logo Styles */
        #logos {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }
        
        #logos img {
            max-height: 80px;
        }
    </style>
</head>
<body>
    <!-- Logos -->
    <div id="logos">
        <img src="https://cdn-07.9rayti.com/rsrc/cache/widen_292/uploads/2015/06/Logo-ISSS.jpg" alt="Logo_left">
        <img src="https://www.infomediaire.net/wp-content/uploads/2020/05/CHU-Mohammed-VI-de-Marrakech.png" alt="Logo_right">
    </div>

    <h1>Supprimer un Équipement</h1>
    
    <?php if (isset($error)): ?>
        <div class="error"><?= $error ?></div>
    <?php endif; ?>
    
    <div class="confirmation-box">
        <div class="equipment-details">
            <h2>Détails de l'équipement</h2>
            <p><strong>Nom de l'équipement :</strong> <?= htmlspecialchars($equipement['nom_equipement']) ?></p>
            <p><strong>Numéro d'inventaire :</strong> <?= htmlspecialchars($equipement['num_inventaire']) ?></p>
            <p><strong>Numéro de série :</strong> <?= htmlspecialchars($equipement['num_serie']) ?></p>
            <p><strong>Marque :</strong> <?= htmlspecialchars($equipement['marque']) ?></p>
            <p><strong>Modèle :</strong> <?= htmlspecialchars($equipement['modele']) ?></p>
            <p><strong>Tag ID :</strong> <?= htmlspecialchars($equipement['tag_id']) ?></p>
            <p><strong>Service source :</strong> <?= htmlspecialchars($service['nom_service']) ?></p>
        </div>
        
        <div class="warning">
            Attention ! Cette action est irréversible. Voulez-vous vraiment supprimer cet équipement ?
        </div>
        
        <form method="POST">
            <div class="buttons">
                <button type="submit" name="confirmer" value="oui" class="confirm-btn">Confirmer la suppression</button>
                <button type="submit" name="confirmer" value="non" class="cancel-btn">Annuler</button>
            </div>
        </form>
    </div>
    
    <a href="../../admin/index.php" class="back-link">Retour au tableau de bord</a>
</body>
</html>