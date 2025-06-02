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

// Récupération des données de l'équipement
$stmt = $db->prepare("SELECT * FROM equipements WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: ../../admin/index.php?message=Équipement non trouvé');
    exit;
}

$equipement = $result->fetch_assoc();

// Traitement du formulaire lors de la soumission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nom_equipement = $_POST['nom_equipement'];
    $num_inventaire = $_POST['num_inventaire'];
    $num_serie = $_POST['num_serie'];
    $marque = $_POST['marque'];
    $modele = $_POST['modele'];
    $tag_id = $_POST['tag_id'];
    $service_source_id = $_POST['service_source_id'];
    
    // Modifiez cette ligne pour changer le type de paramètre pour tag_id de "i" à "s"
    $stmt = $db->prepare("UPDATE equipements SET nom_equipement = ?, num_inventaire = ?, num_serie = ?, marque = ?, modele = ?, tag_id = ?, service_source_id = ? WHERE id = ?");
    $stmt->bind_param("ssssssss", $nom_equipement, $num_inventaire, $num_serie, $marque, $modele, $tag_id, $service_source_id, $id);
    
    if ($stmt->execute()) {
        // Redirection vers la page dashboard avec un message de succès
        header("Location: ../../admin/index.php?message=Équipement modifié avec succès");
    } else {
        $error = "Erreur lors de la modification : " . $stmt->error;
    }
    
    $stmt->close();
}

// Récupération de la liste des services pour le menu déroulant
$services = [];
$result = $db->query("SELECT id, nom_service FROM services");
while ($row = $result->fetch_assoc()) {
    $services[] = $row;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Modifier un Équipement</title>
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
        
        input, select {
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

    <h1>Modifier un Équipement</h1>
    
    <?php if (isset($error)): ?>
        <div class="error"><?= $error ?></div>
    <?php endif; ?>
    
    <form method="POST">
        <div class="form-group">
            <label for="nom_equipement">Nom de l'équipement :</label>
            <input type="text" id="nom_equipement" name="nom_equipement" value="<?= htmlspecialchars($equipement['nom_equipement']) ?>" required>
        </div>
        
        <div class="form-group">
            <label for="num_inventaire">Numéro d'inventaire :</label>
            <input type="text" id="num_inventaire" name="num_inventaire" value="<?= htmlspecialchars($equipement['num_inventaire']) ?>" required>
        </div>
        
        <div class="form-group">
            <label for="num_serie">Numéro de série :</label>
            <input type="text" id="num_serie" name="num_serie" value="<?= htmlspecialchars($equipement['num_serie']) ?>" required>
        </div>
        
        <div class="form-group">
            <label for="marque">Marque :</label>
            <input type="text" id="marque" name="marque" value="<?= htmlspecialchars($equipement['marque']) ?>" required>
        </div>
        
        <div class="form-group">
            <label for="modele">Modèle :</label>
            <input type="text" id="modele" name="modele" value="<?= htmlspecialchars($equipement['modele']) ?>" required>
        </div>
        
        <div class="form-group">
            <label for="tag_id">Tag ID :</label>
            <input type="text" id="tag_id" name="tag_id" value="<?= htmlspecialchars($equipement['tag_id']) ?>" required>
        </div>
        
        <div class="form-group">
            <label for="service_source_id">Service source :</label>
            <select id="service_source_id" name="service_source_id" required>
                <?php foreach ($services as $service): ?>
                    <option value="<?= $service['id'] ?>" <?= ($service['id'] == $equipement['service_source_id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($service['nom_service']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <button type="submit">Enregistrer les modifications</button>
    </form>
    
    <a href="../../admin/index.php" class="back-link">Retour au tableau de bord</a>
</body>
</html>