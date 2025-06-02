<?php
include_once("../../db/index.php");
session_start();

if (!isset($_SESSION["nom_prenom"]) || $_SESSION["role"] !== "admin") {
    header('Location: ../../');
    exit;
}

// Vérifier que l'ID est bien spécifié dans l'URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: ../index.php?message=ID utilisateur non spécifié');
    exit;
}

$id = $_GET['id'];

// Récupération des données de l'utilisateur
$stmt = $db->prepare("SELECT * FROM utilisateurs WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: ../index.php?message=Utilisateur non trouvé');
    exit;
}

$utilisateur = $result->fetch_assoc();

// Traitement du formulaire lors de la soumission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nom_prenom = $_POST['nom_prenom'];
    $role = $_POST['role'];
    $email = $_POST['email'];
    
    // Si un nouveau mot de passe est fourni, le stocker en clair
    if (!empty($_POST['mot_de_passe'])) {
        $mot_de_passe = $_POST['mot_de_passe'];
        
        // Mise à jour avec un nouveau mot de passe
        $stmt = $db->prepare("UPDATE utilisateurs SET nom_prenom = ?, role = ?, email = ?, mot_de_passe = ? WHERE id = ?");
        $stmt->bind_param("ssssi", $nom_prenom, $role, $email, $mot_de_passe, $id);
    } else {
        // Mise à jour sans changer le mot de passe
        $stmt = $db->prepare("UPDATE utilisateurs SET nom_prenom = ?, role = ?, email = ? WHERE id = ?");
        $stmt->bind_param("sssi", $nom_prenom, $role, $email, $id);
    }
    
    if ($stmt->execute()) {
        // Redirection vers la page dashboard avec un message de succès
        header("Location: ../../admin/index.php?message=Utilisateur modifié avec succès");
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
    <title>Modifier un Utilisateur</title>
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
        
        .password-note {
            font-size: 0.9em;
            color: #666;
            margin-top: 5px;
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

    <h1>Modifier un Utilisateur</h1>
    
    <?php if (isset($error)): ?>
        <div class="error"><?= $error ?></div>
    <?php endif; ?>
    
    <form method="POST">
        <div class="form-group">
            <label for="nom_prenom">Nom et prénom :</label>
            <input type="text" id="nom_prenom" name="nom_prenom" value="<?= htmlspecialchars($utilisateur['nom_prenom']) ?>" required>
        </div>
        
        <div class="form-group">
            <label for="role">Rôle :</label>
            <select id="role" name="role" required>
                <option value="admin" <?= ($utilisateur['role'] == 'admin') ? 'selected' : '' ?>>Administrateur</option>
                <option value="utili" <?= ($utilisateur['role'] == 'utili') ? 'selected' : '' ?>>Utilisateur standard</option>
            </select>
        </div>
        
        <div class="form-group">
            <label for="email">Email :</label>
            <input type="email" id="email" name="email" value="<?= htmlspecialchars($utilisateur['email']) ?>" required>
        </div>
        
        <div class="form-group">
            <label for="mot_de_passe">Nouveau mot de passe (laisser vide pour ne pas changer) :</label>
            <input type="password" id="mot_de_passe" name="mot_de_passe">
            <p class="password-note">Si vous ne souhaitez pas modifier le mot de passe, laissez ce champ vide.</p>
        </div>
        
        <button type="submit">Enregistrer les modifications</button>
    </form>
    
    <a href="../../admin/index.php" class="back-link">Retour au tableau de bord</a>
</body>
</html>