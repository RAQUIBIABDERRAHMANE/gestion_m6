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

// Récupération des données du service
$stmt = $db->prepare("SELECT * FROM services WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: ../../admin/index.php?message=Service non trouvé');
    exit;
}

$service = $result->fetch_assoc();

// Traitement du formulaire lors de la soumission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nom_service = $_POST['nom_service'];
    $adresse_MAC_esp = $_POST['adresse_MAC_esp'];
    
    // Préparation de la requête SQL pour éviter les injections SQL
    $stmt = $db->prepare("UPDATE services SET nom_service = ?, adresse_MAC_esp = ? WHERE id = ?");
    $stmt->bind_param("ssi", $nom_service, $adresse_MAC_esp, $id);
    
    if ($stmt->execute()) {
        // Redirection vers la page dashboard avec un message de succès
        header("Location: ../../admin/index.php?message=Service modifié avec succès");
    } else {
        $error = "Erreur lors de la modification : " . $stmt->error;
    }
    
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Modifier un Service</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }
        
        h1 {
            color: #333;
        }
        
        form {
            max-width: 600px;
            margin: 0 auto;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        
        input {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        
        button {
            background-color: #4CAF50;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        
        button:hover {
            background-color: #45a049;
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

    <h1>Modifier un Service</h1>
    
    <?php if (isset($error)): ?>
        <div class="error"><?= $error ?></div>
    <?php endif; ?>
    
    <form method="POST">
        <div class="form-group">
            <label for="nom_service">Nom du service :</label>
            <input type="text" id="nom_service" name="nom_service" value="<?= htmlspecialchars($service['nom_service']) ?>" required>
        </div>
        
        <div class="form-group">
            <label for="adresse_MAC_esp">Adresse MAC ESP :</label>
            <input type="text" id="adresse_MAC_esp" name="adresse_MAC_esp" value="<?= htmlspecialchars($service['adresse_MAC_esp']) ?>" required>
        </div>
        
        <button type="submit">Enregistrer les modifications</button>
    </form>
    
    <a href="../../admin/index.php" class="back-link">Retour au tableau de bord</a>
</body>
</html>