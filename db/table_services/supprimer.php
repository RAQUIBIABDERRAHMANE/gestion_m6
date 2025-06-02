<?php
include_once("../../db/index.php");
session_start();

if (!isset($_SESSION["nom_prenom"]) || $_SESSION["role"] !== "admin") {
    header('Location: ../../');
    exit;
}

// Vérifier que l'ID est bien spécifié dans l'URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: ../../admin/index.php?message=ID service non spécifié');
    exit;
}

$id = $_GET['id'];

// Récupération des données du service pour l'affichage
$stmt = $db->prepare("SELECT * FROM services WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: ../../admin/index.php?message=Service non trouvé');
    exit;
}

$service = $result->fetch_assoc();

// Vérification si le service a des équipements associés
$equipmentStmt = $db->prepare("SELECT COUNT(*) as count FROM equipements WHERE service_source_id = ?");
$equipmentStmt->bind_param("i", $id);
$equipmentStmt->execute();
$equipmentResult = $equipmentStmt->get_result();
$equipmentCount = $equipmentResult->fetch_assoc()['count'];

// Traitement de la suppression lors de la confirmation
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['confirmer']) && $_POST['confirmer'] === 'oui') {
        // Suppression du service
        $stmt = $db->prepare("DELETE FROM services WHERE id = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            // Redirection vers la page dashboard avec un message de succès
            header("Location: ../../admin/index.php?message=Service supprimé avec succès");
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
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Supprimer un Service</title>
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
        
        .service-details {
            margin-bottom: 20px;
        }
        
        .service-details p {
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

    <h1>Supprimer un Service</h1>
    
    <?php if (isset($error)): ?>
        <div class="error"><?= $error ?></div>
    <?php endif; ?>
    
    <div class="confirmation-box">
        <div class="service-details">
            <h2>Détails du service</h2>
            <p><strong>Nom du service :</strong> <?= htmlspecialchars($service['nom_service']) ?></p>
            <p><strong>Adresse MAC ESP :</strong> <?= htmlspecialchars($service['adresse_MAC_esp']) ?></p>
            
            <?php if ($equipmentCount > 0): ?>
                <div class="warning">
                    <p>Ce service est associé à <?= $equipmentCount ?> équipement(s).</p>
                    <p>La suppression de ce service pourrait affecter ces équipements.</p>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="warning">
            Attention ! Cette action est irréversible. Voulez-vous vraiment supprimer ce service ?
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