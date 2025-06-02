<?php
session_start();
include_once("./db/index.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $stmt = $db->prepare("SELECT * FROM utilisateurs WHERE nom_prenom = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if ($password === $user['mot_de_passe']) {
            $_SESSION['nom_prenom'] = $user['nom_prenom'];
            $_SESSION['role'] = $user['role'];

            if ($user['role'] === 'admin') {
                header('Location: ./admin/');
                exit;
            } elseif ($user['role'] === 'utili') {
                header('Location: ./utili');
                exit;
            } else {
                $error = 'Rôle non valide.';
            }
        } else {
            $error = 'Mot de passe incorrect.';
        }
    } else {
        $error = 'Utilisateur non trouvé.';
    }

    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - Traçabilité des Équipements Biomédicaux</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #0057a8;
            --secondary-color: #0c7cd5;
            --accent-color: #e74c3c;
            --light-color: #f8f9fa;
            --dark-color: #343a40;
            --success-color: #28a745;
            --info-color: #17a2b8;
            --warning-color: #ffc107;
        }
        
        body {
            background-color: #f5f5f5;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-image: linear-gradient(135deg, rgba(0, 87, 168, 0.07), rgba(12, 124, 213, 0.15));
            background-attachment: fixed;
            min-height: 100vh;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 30px;
            background-color: white;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            position: relative;
            border-bottom: 3px solid var(--primary-color);
        }
        
        .header img {
            height: 70px;
            object-fit: contain;
            transition: transform 0.3s ease, filter 0.3s ease;
        }
        
        .header img:hover {
            transform: scale(1.05);
            filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.2));
        }
        
        .header-title {
            text-align: center;
            flex-grow: 1;
            font-size: 24px;
            font-weight: 600;
            color: var(--primary-color);
            letter-spacing: 0.5px;
            background: linear-gradient(90deg, #0057a8, #0c7cd5);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-shadow: 0 1px 1px rgba(255, 255, 255, 0.5);
        }
        
        .login-container {
            max-width: 950px;
            margin: 2.5rem auto;
            padding: 0;
            background-color: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.15);
            display: flex;
            transform: translateY(0);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .login-container:hover {
            transform: translateY(-5px);
            box-shadow: 0 18px 35px rgba(0, 0, 0, 0.2);
        }
        
        .login-image {
            flex: 1.2;
            background: linear-gradient(145deg, var(--primary-color), var(--secondary-color));
            padding: 30px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            color: white;
            position: relative;
        }
        
        .login-image::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image: url("data:image/svg+xml,%3Csvg width='100' height='100' viewBox='0 0 100 100' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M11 18c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm48 25c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm-43-7c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm63 31c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM34 90c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm56-76c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM12 86c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm28-65c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm23-11c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-6 60c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm29 22c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zM32 63c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm57-13c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-9-21c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM60 91c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM35 41c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM12 60c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2z' fill='%23ffffff' fill-opacity='0.1' fill-rule='evenodd'/%3E%3C/svg%3E");
            opacity: 0.7;
            z-index: 0;
        }
        
        .login-image-content {
            position: relative;
            z-index: 1;
            width: 100%;
        }
        
        .login-image img {
            width: 100%;
            max-width: 380px;
            border-radius: 12px;
            box-shadow: 0 12px 25px rgba(0, 0, 0, 0.25);
            transition: transform 0.4s ease, box-shadow 0.4s ease;
            border: 4px solid rgba(255, 255, 255, 0.2);
            object-fit: cover;
        }
        
        .login-image img:hover {
            transform: scale(1.03) translateY(-5px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.35);
        }
        
        .login-image-caption {
            margin-top: 28px;
            text-align: center;
            font-size: 1.25rem;
            font-weight: 500;
        }
        
        .login-image-caption h4 {
            font-weight: 700;
            margin-bottom: 10px;
            text-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
            letter-spacing: 0.5px;
        }
        
        .login-form {
            flex: 0.8;
            padding: 40px;
            position: relative;
        }
        
        .login-header {
            color: var(--primary-color);
            margin-bottom: 30px;
            text-align: center;
            font-weight: 600;
        }
        
        .login-header h4 {
            margin-top: 12px;
            color: var(--primary-color);
            font-weight: 700;
            letter-spacing: 0.5px;
        }
        
        .form-control {
            border: 1px solid #ddd;
            border-radius: 10px;
            padding: 14px;
            transition: all 0.3s;
            font-size: 1rem;
        }
        
        .form-control:focus {
            border-color: var(--secondary-color);
            box-shadow: 0 0 0 0.25rem rgba(0, 105, 180, 0.25);
            transform: translateY(-2px);
        }
        
        .input-group {
            transition: transform 0.3s ease;
        }
        
        .input-group-text {
            background-color: var(--light-color);
            border: 1px solid #ddd;
            border-radius: 10px 0 0 10px;
            padding-left: 15px;
            padding-right: 15px;
        }
        
        .btn-primary {
            background: linear-gradient(to right, var(--primary-color), var(--secondary-color));
            border: none;
            border-radius: 10px;
            padding: 14px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 8px rgba(0, 87, 168, 0.3);
        }
        
        .btn-primary:hover, .btn-primary:focus {
            background: linear-gradient(to right, var(--secondary-color), var(--primary-color));
            transform: translateY(-3px);
            box-shadow: 0 8px 15px rgba(0, 87, 168, 0.4);
        }
        
        .btn-primary::after {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(to right, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: all 0.6s;
        }
        
        .btn-primary:hover::after {
            left: 100%;
        }
        
        .alert {
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 25px;
            border-left: 4px solid transparent;
            animation: fadeIn 0.5s ease-in-out;
        }
        
        .alert-danger {
            background-color: #fff5f5;
            border-left-color: var(--accent-color);
            color: #721c24;
        }
        
        .footer {
            text-align: center;
            margin-top: 2rem;
            padding: 15px;
            color: #6c757d;
            font-size: 0.9rem;
            transition: color 0.3s ease;
        }
        
        .footer:hover {
            color: var(--primary-color);
        }

        .animated {
            animation: fadeIn 0.7s ease-in-out;
        }

        @keyframes fadeIn {
            0% { opacity: 0; transform: translateY(-20px); }
            100% { opacity: 1; transform: translateY(0); }
        }
        
        .features-list {
            list-style: none;
            padding: 0;
            margin-top: 25px;
        }
        
        .features-list li {
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            background-color: rgba(255, 255, 255, 0.15);
            padding: 12px 18px;
            border-radius: 10px;
            transition: all 0.3s ease;
            backdrop-filter: blur(5px);
        }
        
        .features-list li:hover {
            background-color: rgba(255, 255, 255, 0.25);
            transform: translateX(8px);
            box-shadow: 0 3px 8px rgba(0, 0, 0, 0.1);
        }
        
        .features-list li i {
            color: #fff;
            margin-right: 12px;
            font-size: 1.2rem;
            min-width: 25px;
            text-align: center;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
        }
                
        .user-type {
            display: flex;
            margin-top: 25px;
            border-top: 1px solid rgba(0, 0, 0, 0.1);
            padding-top: 25px;
        }
        
        .user-type-option {
            flex: 1;
            text-align: center;
            padding: 15px;
            cursor: pointer;
            border-radius: 10px;
            transition: all 0.3s ease;
            margin: 0 8px;
        }
        
        .user-type-option:hover {
            background-color: rgba(0, 87, 168, 0.05);
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }
        
        .user-type-option.active {
            background-color: rgba(0, 87, 168, 0.08);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
        }
        
        .user-type-option i {
            font-size: 32px;
            color: var(--primary-color);
            margin-bottom: 10px;
            transition: transform 0.3s ease;
        }
        
        .user-type-option:hover i {
            transform: scale(1.2);
        }
        
        .user-type-label {
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 5px;
        }
        
        .user-type-description {
            font-size: 0.85rem;
            color: #6c757d;
        }
        
        .form-check-input:checked {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.15rem rgba(0, 87, 168, 0.2);
        }
        
        .form-check-input:focus {
            box-shadow: 0 0 0 0.15rem rgba(0, 87, 168, 0.2);
        }
        
        .form-label {
            color: var(--dark-color);
            margin-bottom: 8px;
            font-size: 0.95rem;
        }
        
        a.text-decoration-none {
            color: var(--primary-color);
            transition: color 0.3s ease;
            position: relative;
        }
        
        a.text-decoration-none:hover {
            color: var(--secondary-color);
        }
        
        a.text-decoration-none::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 0;
            height: 1px;
            background-color: var(--secondary-color);
            transition: width 0.3s ease;
        }
        
        a.text-decoration-none:hover::after {
            width: 100%;
        }
        
        @media (max-width: 992px) {
            .login-container {
                flex-direction: column;
                max-width: 600px;
            }
            
            .login-image {
                padding: 35px;
            }
        }
        
        @media (max-width: 576px) {
            .header {
                flex-direction: column;
                padding: 15px;
            }
            
            .header-title {
                margin: 15px 0;
                font-size: 20px;
            }
            
            .login-container {
                margin: 1rem auto;
            }
            
            .login-image img {
                max-width: 250px;
            }
            
            .login-form {
                padding: 25px;
            }
            
            .user-type {
                flex-direction: column;
            }
            
            .user-type-option {
                margin-bottom: 15px;
            }
        }
    </style>
</head>
<body>
    <!-- Top Header with images and title -->
    <div class="header">
        <img src="https://pbs.twimg.com/profile_images/1242043249210130434/wNN-y6oF_400x400.jpg" alt="Logo Hôpital">
        <div class="header-title">
            Système de Gestion des Déplacements des Équipements Biomédicaux
        </div>
        <img src="https://epimafrique.com/wp-content/uploads/2021/04/isss-logo.png" alt="Logo ISSS">
    </div>

    <div class="container animated">
        <div class="login-container">
            <div class="login-image">
                <div class="login-image-content">
                    
                    
                    <div class="login-image-caption">
                        <h4>Traçabilité & Gestion des Déplacements</h4>
                        <p class="mb-3">Solution intégrée pour le suivi des équipements biomédicaux</p>
                    </div>
                    
                    <ul class="features-list">
                        <li><i class="fas fa-map-marker-alt"></i> Localisation précise en temps réel des équipements</li>
                        <li><i class="fas fa-exchange-alt"></i> Gestion des transferts entre services</li>
                        <li><i class="fas fa-history"></i> Historique complet des déplacements</li>
                        <li><i class="fas fa-qrcode"></i> Identification tags RFID</li>
                        
                    </ul>
                </div>
            </div>
            
            <div class="login-form">
                <div class="login-header">
                    <i class="fas fa-laptop-medical" style="font-size: 48px; color: var(--primary-color);"></i>
                    <h4>GESTION DES ÉQUIPEMENTS BIOMÉDICAUX</h4>
                </div>
                
                <h5 class="text-center mb-4">Connectez-vous à votre compte</h5>

                <?php if (isset($error)) : ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <div class="mb-4">
                        <label for="username" class="form-label fw-semibold">Nom d'utilisateur</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                            <input type="text" class="form-control" id="username" name="username" placeholder="Entrez votre nom d'utilisateur" required>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="password" class="form-label fw-semibold">Mot de passe</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input type="password" class="form-control" id="password" name="password" placeholder="Entrez votre mot de passe" required>
                        </div>
                    </div>

                    <div class="mb-4 form-check">
                        <input type="checkbox" class="form-check-input" id="rememberMe">
                        <label class="form-check-label" for="rememberMe">Se souvenir de moi</label>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 py-3 mt-2">
                        <i class="fas fa-sign-in-alt me-2"></i>Connexion
                    </button>
                </form>
                
                <div class="text-center mt-4 mb-4">
                    <a href="#" class="text-decoration-none text-primary small">Mot de passe oublié?</a>
                </div>
                
                <div class="user-type">
                    <div class="user-type-option">
                        <i class="fas fa-user-md"></i>
                        <div class="user-type-label">Utilisateur</div>
                        <div class="user-type-description">Personnel médical</div>
                    </div>
                    <div class="user-type-option">
                        <i class="fas fa-tools"></i>
                        <div class="user-type-label">Administrateur</div>
                        <div class="user-type-description">Service biomédical</div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="footer">
            <p>&copy; 2025 Système de Gestion des Équipements Biomédicaux</p>
        </div>
    </div>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Animation pour focus des éléments du formulaire
        document.querySelectorAll('.form-control').forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.style.transform = 'translateX(5px)';
                setTimeout(() => {
                    this.parentElement.style.transform = 'translateX(0)';
                }, 300);
            });
        });
        
        // Sélection du type d'utilisateur
        document.querySelectorAll('.user-type-option').forEach(option => {
            option.addEventListener('click', function() {
                document.querySelectorAll('.user-type-option').forEach(opt => {
                    opt.classList.remove('active');
                });
                this.classList.add('active');
            });
        });
    </script>
</body>
</html>