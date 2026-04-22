<?php
session_start();


if (isset($_SESSION['user_id'])) {
    $role = $_SESSION['user_role'] ?? 'reclamant';
    
    if ($role === 'administrateur') {
        header('Location: ../admin/dashboard.php');
    } elseif ($role === 'gestionnaire') {
        header('Location: ../gestionnaire/dashboard.php');
    } else {
        header('Location: ../user/dashboard.php');
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ReclaNova - Connexion</title>
    <link rel="stylesheet" href="style.css">
    <link href='https://cdn.jsdelivr.net/npm/boxicons@latest/css/boxicons.min.css' rel='stylesheet'>
</head>

<body>

    <div class="container">
        <!-- LOGIN FORM -->
        <div class="form-box login">
            <form id="loginForm">
                <h1>Connexion</h1>
                
                <!-- Alert Box -->
                <div id="loginAlert" class="alert-box" style="display: none;"></div>
                
                <div class="input-box">
                    <input type="text" id="loginIdentifier" placeholder="Email ou nom d'utilisateur" required>
                    <i class='bx bxs-user'></i>  
                </div>
                
                <div class="input-box">
                    <input type="password" id="loginPassword" placeholder="Mot de passe" required>
                    <i class='bx bxs-lock'></i>
                    <i class='bx bx-show toggle-password' data-target="loginPassword"></i>
                </div>
                
                <div class="forgot-link">
                    <a href="forgot_password.php">Mot de passe oublié?</a>
                </div>
                
                <button type="submit" class="btn">
                    <span class="btn-text">Se connecter</span>
                    <span class="btn-loader" style="display: none;">
                        <i class='bx bx-loader-alt bx-spin'></i>
                    </span>
                </button>
            </form>
        </div>

        <!-- REGISTER FORM -->
        <div class="form-box register">
            <form id="registerForm">
                <h1>Inscription</h1>
                
                <!-- Alert Box -->
                <div id="registerAlert" class="alert-box" style="display: none;"></div>
                
                <div class="input-box">
                    <input type="text" id="registerUsername" placeholder="Nom complet" required>
                    <i class='bx bxs-user'></i>  
                </div>
                
                <div class="input-box">
                    <input type="email" id="registerEmail" placeholder="Email" required>
                    <i class='bx bxs-envelope'></i>  
                </div>
                
                <div class="input-box">
                    <input type="password" id="registerPassword" placeholder="Mot de passe" required>
                    <i class='bx bxs-lock'></i>
                    <i class='bx bx-show toggle-password' data-target="registerPassword"></i>
                </div>
                
                <div class="password-strength">
                    <div class="strength-bar"></div>
                    <div class="strength-text"></div>
                </div>
                
                <button type="submit" class="btn">
                    <span class="btn-text">S'inscrire</span>
                    <span class="btn-loader" style="display: none;">
                        <i class='bx bx-loader-alt bx-spin'></i>
                    </span>
                </button>
            </form>
        </div>

        <!-- TOGGLE PANELS -->
        <div class="toggle-box">
            <div class="toggle-panel toggle-left">
                <h1>Bienvenue!</h1>
                <p>Vous n'avez pas de compte?</p>
                <button class="btn register-btn">S'inscrire</button>
            </div>
            <div class="toggle-panel toggle-right">
                <h1>Bon retour!</h1>
                <p>Vous avez déjà un compte?</p>
                <button class="btn login-btn">Se connecter</button>
            </div>
        </div>
    </div>

    <script src="script.js"></script>
</body>

</html>