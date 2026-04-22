<?php


session_start();
require_once 'db_config.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
    header('Location: ../connexion/connexion.php');
    exit;
}

if ($_SESSION['user_role'] !== 'administrateur') {
    header('Location: ../connexion/connexion.php');
    exit;
}

$current_user_id = $_SESSION['user_id'];


$pdo = getDBConnection();

$message = '';
$messageType = '';
$user = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $nom = trim($_POST['nom'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        if (empty($nom) || empty($email)) {
            throw new Exception('Le nom et l\'email sont obligatoires.');
        }
        
        if (strlen($nom) < 3) {
            throw new Exception('Le nom doit contenir au moins 3 caractères.');
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Email invalide.');
        }
        
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $current_user_id]);
        if ($stmt->fetch()) {
            throw new Exception('Cet email est déjà utilisé par un autre utilisateur.');
        }
        
        if (!empty($new_password)) {
            if (empty($current_password)) {
                throw new Exception('Veuillez entrer votre mot de passe actuel pour le modifier.');
            }
            
            $stmt = $pdo->prepare("SELECT mot_de_passe FROM users WHERE id = ?");
            $stmt->execute([$current_user_id]);
            $user_data = $stmt->fetch();
            
            if (!password_verify($current_password, $user_data['mot_de_passe'])) {
                throw new Exception('Le mot de passe actuel est incorrect.');
            }
            
            if (strlen($new_password) < 6) {
                throw new Exception('Le nouveau mot de passe doit contenir au moins 6 caractères.');
            }
            
            if ($new_password !== $confirm_password) {
                throw new Exception('Les mots de passe ne correspondent pas.');
            }
            
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("
                UPDATE users 
                SET nom = ?, email = ?, mot_de_passe = ?
                WHERE id = ?
            ");
            $stmt->execute([$nom, $email, $hashed_password, $current_user_id]);
        } else {
            
            $stmt = $pdo->prepare("
                UPDATE users 
                SET nom = ?, email = ?
                WHERE id = ?
            ");
            $stmt->execute([$nom, $email, $current_user_id]);
        }
        
        $_SESSION['user_name'] = $nom;
        $_SESSION['user_email'] = $email;
        
        $message = 'Profil mis à jour avec succès!';
        $messageType = 'success';
        
    } catch (Exception $e) {
        $message = $e->getMessage();
        $messageType = 'error';
        error_log("Profile update error: " . $e->getMessage());
    }
}

try {
    $stmt = $pdo->prepare("
        SELECT id, nom, email, role, date_creation, dernier_login
        FROM users 
        WHERE id = ?
    ");
    $stmt->execute([$current_user_id]);
    $user = $stmt->fetch();
    
    if (!$user) {
        header('Location: ../auth/login.php');
        exit;
    }
    
} catch (PDOException $e) {
    error_log("Fetch user error: " . $e->getMessage());
    header('Location: dashboard.php');
    exit;
}

function formatDate($date) {
    if (!$date) return 'Jamais';
    return date('d/m/Y à H:i', strtotime($date));
}
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
    <link rel="stylesheet" href="sidebar2.css">
    <link rel="stylesheet" href="topbar2.css">
    <link rel="stylesheet" href="profil.css">
    
    
</head>
<body>
    <button class="menu-toggle">
        <i class='bx bx-menu'></i>
    </button>

    <div class="profile-container">
        <h1 class="page-title">Mon profil</h1>

        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $messageType; ?>">
                <i class='bx <?php echo $messageType === 'success' ? 'bx-check-circle' : 'bx-error-circle'; ?>'></i>
                <span><?php echo htmlspecialchars($message); ?></span>
            </div>
        <?php endif; ?>

        <div class="profile-card">
            <div class="profile-picture-section">
                <div class="profile-picture-wrapper">
                    <div class="profile-picture">
                        <i class='bx bx-user'></i>
                    </div>
                </div>
                <h2 class="profile-name"><?php echo htmlspecialchars($user['nom']); ?></h2>
                <p class="profile-email-display"><?php echo htmlspecialchars($user['email']); ?></p>
                <span class="role-badge"><?php echo htmlspecialchars($user['role']); ?></span>
            </div>

            <div class="account-info">
                <div class="account-info-item">
                    <span class="account-info-label">
                        <i class='bx bx-calendar'></i> Membre depuis
                    </span>
                    <span class="account-info-value"><?php echo formatDate($user['date_creation']); ?></span>
                </div>
                <div class="account-info-item">
                    <span class="account-info-label">
                        <i class='bx bx-time'></i> Dernière connexion
                    </span>
                    <span class="account-info-value"><?php echo formatDate($user['dernier_login']); ?></span>
                </div>
            </div>

            <form class="profile-form" method="POST" id="profileForm">
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
                        value="<?php echo htmlspecialchars($user['nom']); ?>"
                        required
                        minlength="3"
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
                        value="<?php echo htmlspecialchars($user['email']); ?>"
                        required
                    >
                </div>

                <div class="password-section">
                    <div class="password-section-title">
                        <i class='bx bx-lock-alt'></i>
                        Changer le mot de passe (optionnel)
                    </div>

                    <div class="form-group">
                        <label for="current_password">
                            <i class='bx bx-shield'></i>
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
                            <button type="button" class="toggle-password" onclick="togglePassword('current_password', 'toggle-icon-1')">
                                <i class='bx bx-hide' id="toggle-icon-1"></i>
                            </button>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="new_password">
                            <i class='bx bx-key'></i>
                            Nouveau mot de passe
                        </label>
                        <div class="password-group">
                            <input 
                                type="password" 
                                id="new_password" 
                                name="new_password"
                                class="form-input"
                                placeholder="Entrez un nouveau mot de passe (min 6 caractères)"
                                minlength="6"
                            >
                            <button type="button" class="toggle-password" onclick="togglePassword('new_password', 'toggle-icon-2')">
                                <i class='bx bx-hide' id="toggle-icon-2"></i>
                            </button>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">
                            <i class='bx bx-check-shield'></i>
                            Confirmer le nouveau mot de passe
                        </label>
                        <div class="password-group">
                            <input 
                                type="password" 
                                id="confirm_password" 
                                name="confirm_password"
                                class="form-input"
                                placeholder="Confirmez le nouveau mot de passe"
                            >
                            <button type="button" class="toggle-password" onclick="togglePassword('confirm_password', 'toggle-icon-3')">
                                <i class='bx bx-hide' id="toggle-icon-3"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="info-message">
                    <i class='bx bx-info-circle'></i>
                    <span>Modifiez vos informations et cliquez sur "Enregistrer" pour sauvegarder les changements.</span>
                </div>

                <div class="button-group">
                    <button type="button" class="btn btn-cancel" onclick="cancelEdit()">
                        Annuler
                    </button>
                    <button type="submit" class="btn btn-save">
                        <i class='bx bx-save'></i>
                        Enregistrer
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const originalValues = {
            nom: <?php echo json_encode($user['nom']); ?>,
            email: <?php echo json_encode($user['email']); ?>
        };

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

        function cancelEdit() {
            
            document.getElementById('nom').value = originalValues.nom;
            document.getElementById('email').value = originalValues.email;
            document.getElementById('current_password').value = '';
            document.getElementById('new_password').value = '';
            document.getElementById('confirm_password').value = '';
            
            ['current_password', 'new_password', 'confirm_password'].forEach((id, index) => {
                const input = document.getElementById(id);
                const icon = document.getElementById('toggle-icon-' + (index + 1));
                input.type = 'password';
                icon.classList.remove('bx-show');
                icon.classList.add('bx-hide');
            });
        }

        document.getElementById('profileForm').addEventListener('submit', function(e) {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const currentPassword = document.getElementById('current_password').value;
            
            if (newPassword || confirmPassword || currentPassword) {
                if (!currentPassword) {
                    e.preventDefault();
                    alert('Veuillez entrer votre mot de passe actuel pour le modifier.');
                    return;
                }
                
                if (newPassword !== confirmPassword) {
                    e.preventDefault();
                    alert('Les nouveaux mots de passe ne correspondent pas.');
                    return;
                }
                
                if (newPassword.length < 6) {
                    e.preventDefault();
                    alert('Le nouveau mot de passe doit contenir au moins 6 caractères.');
                    return;
                }
            }
        });

        document.addEventListener("DOMContentLoaded", () => {
            const toggle = document.querySelector(".menu-toggle");
            const sidebar = document.querySelector(".sidebar");
            const content = document.querySelector(".content");

            if (toggle && sidebar) {
                toggle.addEventListener("click", () => {
                    sidebar.classList.toggle("open");
                    if (content) {
                        content.classList.toggle("shift");
                    }
                });
            }
        });
    </script>

</body>
</html>