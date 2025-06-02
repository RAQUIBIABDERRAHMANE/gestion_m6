<?php
include_once("../../db/index.php");
session_start();

if (!isset($_SESSION["nom_prenom"]) || $_SESSION["role"] !== "admin") {
    header('Location: ../../');
    exit;
}

// Traitement du formulaire lors de la soumission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nom_prenom = $_POST['nom_prenom'];
    $role = $_POST['role'];
    $email = $_POST['email'];
    $mot_de_passe = $_POST['mot_de_passe'];
    
    // Hachage du mot de passe pour plus de sécurité
    $hashed_password = password_hash($mot_de_passe, PASSWORD_DEFAULT);
    
    // Préparation de la requête SQL pour éviter les injections SQL
    $stmt = $db->prepare("INSERT INTO utilisateurs (nom_prenom, role, email, mot_de_passe) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $nom_prenom, $role, $email, $hashed_password);
    
    if ($stmt->execute()) {
        // Redirection vers la page dashboard avec un message de succès
        header("Location: ../../admin/index.php?message=Utilisateur ajouté avec succès");
    } else {
        $error = "Erreur lors de l'ajout : " . $stmt->error;
    }
    
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Ajouter un Service</title>
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
    <h1>Ajouter un Utilisateur</h1>
    
    <?php if (isset($error)): ?>
        <div class="error"><?= $error ?></div>
    <?php endif; ?>
    
    <form method="POST">
        <div class="form-group">
            <label for="nom_prenom">Nom d'utilisateur:</label>
            <input type="text" id="nom_prenom" name="nom_prenom" required>
        </div>
        
        <div class="form-group">
            <label for="role">Rôle:</label>
            <input type="text" id="role" name="role" required value="utili" disabled>
        </div>
        <div class="form-group">
            <label for="email">Email :</label>
            <input type="text" id="email" name="email" required>
        </div>
        <div class="form-group">
            <label for="mot_de_passe">Mot de passe :</label>
            <input type="text" id="mot_de_passe" name="mot_de_passe" required>
        </div>
        <button type="submit">Ajouter</button>
    </form>
    
    <a href="../../admin/index.php" class="back-link">Retour au tableau de bord</a>
</body>
</html>