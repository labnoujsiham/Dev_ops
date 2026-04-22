<?php
session_start();


if (!isset($_SESSION['reset_email']) || !isset($_SESSION['reset_time'])) {
    header('Location: forgot_password.php');
    exit();
}

//expiration de session au bout de 10 minutes
if (time() - $_SESSION['reset_time'] > 600) {
    unset($_SESSION['reset_email']);
    unset($_SESSION['reset_time']);
    header('Location: forgot_password.php?expired=1');
    exit();
}

$email = $_SESSION['reset_email'];
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ReclaNova - Réinitialiser le mot de passe</title>
    <link rel="stylesheet" href="style.css">
    <link href='https://cdn.jsdelivr.net/npm/boxicons@latest/css/boxicons.min.css' rel='stylesheet'>
</head>

<body>

    <div class="container forgot-container">
        <div class="form-box single">
            <form id="resetForm">
               
                
                <h1>Nouveau mot de passe</h1>
                <p class="subtitle">Définissez un nouveau mot de passe pour <strong><?php echo htmlspecialchars($email); ?></strong></p>
                
                <!-- Alert Box -->
                <div id="resetAlert" class="alert-box" style="display: none;"></div>
                
                <div class="input-box">
                    <input type="password" id="newPassword" placeholder="Nouveau mot de passe" required>
                    <i class='bx bxs-lock'></i>
                    <i class='bx bx-show toggle-password' data-target="newPassword"></i>
                </div>
                
                <div class="password-strength">
                    <div class="strength-bar"></div>
                    <div class="strength-text"></div>
                </div>
                
                <div class="input-box">
                    <input type="password" id="confirmPassword" placeholder="Confirmer le mot de passe" required>
                    <i class='bx bxs-lock'></i>
                    <i class='bx bx-show toggle-password' data-target="confirmPassword"></i>
                </div>
                
                <button type="submit" class="btn">
                    <span class="btn-text">Réinitialiser le mot de passe</span>
                    <span class="btn-loader" style="display: none;">
                        <i class='bx bx-loader-alt bx-spin'></i>
                    </span>
                </button>
            </form>
        </div>

        <div class="toggle-box">
            <div class="toggle-panel toggle-left">
                <h1>Bonjour!</h1>
                <p>Retourner à la page de connexion?</p>
                <button class="btn login-btn" onclick="window.location.href='connexion.php'">Se connecter</button>
            </div>
        </div>
    </div>

    <script>
        // visibilité mdp
        document.querySelectorAll('.toggle-password').forEach(icon => {
            icon.addEventListener('click', function() {
                const targetId = this.getAttribute('data-target');
                const input = document.getElementById(targetId);
                
                if (input.type === 'password') {
                    input.type = 'text';
                    this.classList.remove('bx-show');
                    this.classList.add('bx-hide');
                } else {
                    input.type = 'password';
                    this.classList.remove('bx-hide');
                    this.classList.add('bx-show');
                }
            });
        });

        // Force mdp
        const newPassword = document.getElementById('newPassword');
        const strengthBar = document.querySelector('.strength-bar');
        const strengthText = document.querySelector('.strength-text');

        if (newPassword) {
            newPassword.addEventListener('input', function() {
                const password = this.value;
                let strength = 0;
                
                if (password.length >= 6) strength++;
                if (password.length >= 10) strength++;
                if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
                if (/\d/.test(password)) strength++;
                if (/[^a-zA-Z0-9]/.test(password)) strength++;
                
                const strengthLevels = ['Très faible', 'Faible', 'Moyen', 'Fort', 'Très fort'];
                const strengthColors = ['#ff4444', '#ff8800', '#ffbb33', '#00C851', '#007E33'];
                
                strengthBar.style.width = (strength * 20) + '%';
                strengthBar.style.backgroundColor = strengthColors[strength - 1] || '#ddd';
                strengthText.textContent = strength > 0 ? strengthLevels[strength - 1] : '';
            });
        }

        // afiicher les messages
        function showAlert(elementId, message, type = 'error') {
            const alertBox = document.getElementById(elementId);
            alertBox.textContent = message;
            alertBox.className = `alert-box ${type}`;
            alertBox.style.display = 'block';
            alertBox.style.animation = 'slideIn 0.3s ease';
        }

        // Fonction pour afficher/masquer le loader
        function toggleLoader(button, show) {
            const btnText = button.querySelector('.btn-text');
            const btnLoader = button.querySelector('.btn-loader');
            
            if (show) {
                btnText.style.display = 'none';
                btnLoader.style.display = 'inline-block';
                button.disabled = true;
            } else {
                btnText.style.display = 'inline';
                btnLoader.style.display = 'none';
                button.disabled = false;
            }
        }

       
        document.getElementById('resetForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const submitBtn = this.querySelector('.btn');
            const newPassword = document.getElementById('newPassword').value;
            const confirmPassword = document.getElementById('confirmPassword').value;
            
           
            if (!newPassword || !confirmPassword) {
                showAlert('resetAlert', 'Veuillez remplir tous les champs', 'error');
                return;
            }
            
            if (newPassword.length < 6) {
                showAlert('resetAlert', 'Le mot de passe doit contenir au moins 6 caractères', 'error');
                return;
            }
            
            if (newPassword !== confirmPassword) {
                showAlert('resetAlert', 'Les mots de passe ne correspondent pas', 'error');
                return;
            }
            
            toggleLoader(submitBtn, true);
            
            try {
                const response = await fetch('update_password.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        password: newPassword
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showAlert('resetAlert', data.message, 'success');
                    setTimeout(() => {
                        window.location.href = 'connexion.php';
                    }, 2000);
                } else {
                    showAlert('resetAlert', data.message, 'error');
                    toggleLoader(submitBtn, false);
                }
            } catch (error) {
                showAlert('resetAlert', 'Erreur de connexion. Veuillez réessayer.', 'error');
                toggleLoader(submitBtn, false);
            }
        });

        
        window.addEventListener('load', () => {
            const container = document.querySelector('.container');
            container.style.opacity = '0';
            container.style.transform = 'scale(0.9)';
            
            setTimeout(() => {
                container.style.transition = 'all 0.5s ease';
                container.style.opacity = '1';
                container.style.transform = 'scale(1)';
            }, 100);
        });
    </script>
</body>

</html>