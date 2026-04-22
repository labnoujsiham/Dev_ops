<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: ../connexion/connexion.php');
    exit();
}

$userId = $_SESSION['user_id'];
$userName = $_SESSION['user_name'] ?? '';
$userEmail = $_SESSION['user_email'] ?? '';
$userRole = $_SESSION['user_role'] ?? 'reclamant';

require_once '../connexion/db_config.php';

$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validation
    if (empty($nom) || empty($email)) {
        $error_message = "Le nom et l'email sont obligatoires";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Adresse email invalide";
    } elseif (strlen($nom) < 2 || strlen($nom) > 120) {
        $error_message = "Le nom doit contenir entre 2 et 120 caractères";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$email, $userId]);
            if ($stmt->fetch()) {
                $error_message = "Cet email est déjà utilisé par un autre compte";
            } else {
                if (!empty($new_password)) {
                    $stmt = $pdo->prepare("SELECT mot_de_passe FROM users WHERE id = ?");
                    $stmt->execute([$userId]);
                    $userPassword = $stmt->fetch();
                    
                    if (!password_verify($current_password, $userPassword['mot_de_passe'])) {
                        $error_message = "Le mot de passe actuel est incorrect";
                    } elseif (strlen($new_password) < 6) {
                        $error_message = "Le nouveau mot de passe doit contenir au moins 6 caractères";
                    } elseif ($new_password !== $confirm_password) {
                        $error_message = "Les mots de passe ne correspondent pas";
                    } else {
                        $hashedPassword = password_hash($new_password, PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare("UPDATE users SET nom = ?, email = ?, mot_de_passe = ? WHERE id = ?");
                        $stmt->execute([$nom, $email, $hashedPassword, $userId]);
                        
                        $_SESSION['user_name'] = $nom;
                        $_SESSION['user_email'] = $email;
                        
                        $success_message = "Profil et mot de passe mis à jour avec succès !";
                        $userName = $nom;
                        $userEmail = $email;
                    }
                } else {
                    $stmt = $pdo->prepare("UPDATE users SET nom = ?, email = ? WHERE id = ?");
                    $stmt->execute([$nom, $email, $userId]);
                    
                    $_SESSION['user_name'] = $nom;
                    $_SESSION['user_email'] = $email;
                    
                    $success_message = "Profil mis à jour avec succès !";
                    $userName = $nom;
                    $userEmail = $email;
                }
            }
        } catch (PDOException $e) {
            $error_message = "Erreur lors de la mise à jour du profil";
        }
    }
}

try {
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM reclamations WHERE user_id = ?");
    $stmt->execute([$userId]);
    $stats = $stmt->fetch();
    $total_reclamations = $stats['total'];
    
    //  en attente
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as en_attente 
        FROM reclamations r 
        JOIN statuts s ON r.statut_id = s.id 
        WHERE r.user_id = ? AND s.cle = 'en_attente'
    ");
    $stmt->execute([$userId]);
    $stats = $stmt->fetch();
    $reclamations_en_attente = $stats['en_attente'];
    
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as resolues 
        FROM reclamations r 
        JOIN statuts s ON r.statut_id = s.id 
        WHERE r.user_id = ? AND (s.cle = 'acceptee' OR s.cle = 'fermee')
    ");
    $stmt->execute([$userId]);
    $stats = $stmt->fetch();
    $reclamations_resolues = $stats['resolues'];
    
} catch (PDOException $e) {
    $total_reclamations = 0;
    $reclamations_en_attente = 0;
    $reclamations_resolues = 0;
}


$user = [
    'id' => $userId,
    'nom' => $userName,
    'email' => $userEmail,
    'role' => $userRole
];
?>
<?php include 'sidebar2.php'; ?>
<?php include 'topbar2.php'; ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon profil - ReclaNova</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link href="https://fonts.googleapis.com/css2?family=Afacad:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="profil.css">
    <link rel="stylesheet" href="sidebar2.css">
    <link rel="stylesheet" href="topbar2.css">
</head>
<body>

    <div class="profile-container">
        <h1 class="page-title">Mon profil</h1>

        <?php if ($success_message): ?>
        <div class="alert alert-success">
            <i class='bx bx-check-circle'></i>
            <?php echo htmlspecialchars($success_message); ?>
        </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
        <div class="alert alert-error">
            <i class='bx bx-error-circle'></i>
            <?php echo htmlspecialchars($error_message); ?>
        </div>
        <?php endif; ?>

        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-icon" style="background: #667eea;">
                    <i class='bx bx-file'></i>
                </div>
                <div class="stat-info">
                    <div class="stat-value"><?php echo $total_reclamations; ?></div>
                    <div class="stat-label">Total réclamations</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="background: #ffa500;">
                    <i class='bx bx-time'></i>
                </div>
                <div class="stat-info">
                    <div class="stat-value"><?php echo $reclamations_en_attente; ?></div>
                    <div class="stat-label">En attente</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="background: #28a745;">
                    <i class='bx bx-check'></i>
                </div>
                <div class="stat-info">
                    <div class="stat-value"><?php echo $reclamations_resolues; ?></div>
                    <div class="stat-label">Résolues</div>
                </div>
            </div>
        </div>

        <div class="profile-card">
            <div class="profile-picture-section">
                <div class="profile-picture-wrapper">
                    <div class="profile-picture">
                        <span class="avatar-letter">
                            <?php echo strtoupper(substr($userName, 0, 1)); ?>
                        </span>
                    </div>
                </div>
                <h2 class="profile-name"><?php echo htmlspecialchars($userName); ?></h2>
                <p class="profile-email-display"><?php echo htmlspecialchars($userEmail); ?></p>
                <p class="profile-role"><?php echo ucfirst($userRole); ?></p>
            </div>

            <form class="profile-form" method="POST">
                
                <div class="form-group">
                    <label for="nom">
                        <i class='bx bx-user'></i>
                        Nom complet
                    </label>
                    <input 
                        type="text" 
                        id="nom"
                        name="nom" 
                        class="form-input" 
                        value="<?php echo htmlspecialchars($userName); ?>"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="email">
                        <i class='bx bx-envelope'></i>
                        Email
                    </label>
                    <input 
                        type="email" 
                        id="email"
                        name="email" 
                        class="form-input" 
                        value="<?php echo htmlspecialchars($userEmail); ?>"
                        required
                    >
                </div>

                <div style="border-top: 2px solid #f0f0f0; margin: 25px 0; padding-top: 15px;">
                    <h3 style="color: #2d3748; font-size: 18px; margin-bottom: 15px;">
                        <i class='bx bx-lock-alt'></i> Changer le mot de passe
                    </h3>
                    <p style="color: #999; font-size: 13px; margin-bottom: 20px;">
                        Laissez vide si vous ne souhaitez pas changer votre mot de passe
                    </p>
                </div>

                <div class="form-group">
                    <label for="current_password">
                        <i class='bx bx-lock-alt'></i>
                        Mot de passe actuel
                    </label>
                    <div class="password-group">
                        <input 
                            type="password" 
                            id="current_password"
                            name="current_password" 
                            class="form-input" 
                            placeholder="Entrez votre mot de passe actuel"
                        >
                        <button type="button" class="toggle-password" onclick="togglePassword('current_password', 'current-icon')">
                            <i class='bx bx-hide' id="current-icon"></i>
                        </button>
                    </div>
                </div>

                <div class="form-group">
                    <label for="new_password">
                        <i class='bx bx-lock-alt'></i>
                        Nouveau mot de passe
                    </label>
                    <div class="password-group">
                        <input 
                            type="password" 
                            id="new_password"
                            name="new_password" 
                            class="form-input" 
                            placeholder="Minimum 6 caractères"
                        >
                        <button type="button" class="toggle-password" onclick="togglePassword('new_password', 'new-icon')">
                            <i class='bx bx-hide' id="new-icon"></i>
                        </button>
                    </div>
                </div>

                <div class="form-group">
                    <label for="confirm_password">
                        <i class='bx bx-lock-alt'></i>
                        Confirmer le nouveau mot de passe
                    </label>
                    <div class="password-group">
                        <input 
                            type="password" 
                            id="confirm_password"
                            name="confirm_password" 
                            class="form-input" 
                            placeholder="Retapez le nouveau mot de passe"
                        >
                        <button type="button" class="toggle-password" onclick="togglePassword('confirm_password', 'confirm-icon')">
                            <i class='bx bx-hide' id="confirm-icon"></i>
                        </button>
                    </div>
                </div>

                <div class="info-message">
                    <i class='bx bx-info-circle'></i>
                    <span>Modifiez vos informations et cliquez sur "Enregistrer" pour sauvegarder les changements.</span>
                </div>

                <div class="button-group">
                    <button type="button" class="btn btn-cancel" onclick="window.location.reload()">
                        Annuler
                    </button>
                    <button type="submit" class="btn btn-save">
                        Enregistrer
                    </button>
                </div>
            </form>
        </div>
    </div>

    <style>
        .alert {
            padding: 15px 20px;
            margin-bottom: 20px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .alert i {
            font-size: 20px;
        }

        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }

        .stat-icon i {
            font-size: 24px;
        }

        .stat-value {
            font-size: 28px;
            font-weight: 700;
            color: #2d3748;
        }

        .stat-label {
            font-size: 13px;
            color: #999;
        }

        .avatar-letter {
            font-size: 48px;
            font-weight: 700;
            color: #667eea;
        }

        .profile-role {
            background: #667eea;
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            display: inline-block;
            margin-top: 8px;
        }
    </style>

    <script>
        function togglePassword(inputId, iconId) {
            const passwordInput = document.getElementById(inputId);
            const toggleIcon = document.getElementById(iconId);
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('bx-hide');
                toggleIcon.classList.add('bx-show');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('bx-show');
                toggleIcon.classList.add('bx-hide');
            }
        }
    </script>

</body>
</html>