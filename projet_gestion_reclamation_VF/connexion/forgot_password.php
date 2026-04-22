<?php
session_start();


if (isset($_SESSION['user_id'])) {
    header('Location: ../user/dashboard.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ReclaNova - Mot de passe oublié</title>
    <link rel="stylesheet" href="style.css">
    <link href='https://cdn.jsdelivr.net/npm/boxicons@latest/css/boxicons.min.css' rel='stylesheet'>
    
</head>

<body>

    <div class="container forgot-container">
        <div class="form-box single">
            <form id="forgotForm">
                
                
                <h1>Mot de passe oublié?</h1>
                <p class="subtitle">Entrez votre adresse email pour réinitialiser votre mot de passe</p>
                
                <!-- Alert Box -->
                <div id="forgotAlert" class="alert-box" style="display: none;"></div>
                
                <div class="input-box">
                    <input type="email" id="forgotEmail" placeholder="Votre adresse email" required>
                    <i class='bx bxs-envelope'></i>  
                </div>
                
                <button type="submit" class="btn">
                    <span class="btn-text">Vérifier l'email</span>
                    <span class="btn-loader" style="display: none;">
                        <i class='bx bx-loader-alt bx-spin'></i>
                    </span>
                </button>
            </form>
        </div>

        <!-- Decorative gradient background -->
        <div class="toggle-box">
            <div class="toggle-panel toggle-left">
                <h1>Bonjour!</h1>
                <p>Retourner à la page de connexion?</p>
                <button class="btn login-btn" onclick="window.location.href='connexion.php'">Se connecter</button>
            </div>
        </div>
    </div>

    <script>
        // Fonction pour afficher les messages
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

        // Gestion du formulaire
        document.getElementById('forgotForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const submitBtn = this.querySelector('.btn');
            const email = document.getElementById('forgotEmail').value.trim();
            
            // Validation basique
            if (!email) {
                showAlert('forgotAlert', 'Veuillez entrer votre adresse email', 'error');
                return;
            }
            
            toggleLoader(submitBtn, true);
            
            try {
                const response = await fetch('verify_email.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        email: email
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showAlert('forgotAlert', data.message, 'success');
                    setTimeout(() => {
                        window.location.href = 'reset_password.php?email=' + encodeURIComponent(email);
                    }, 1500);
                } else {
                    showAlert('forgotAlert', data.message, 'error');
                    toggleLoader(submitBtn, false);
                }
            } catch (error) {
                showAlert('forgotAlert', 'Erreur de connexion. Veuillez réessayer.', 'error');
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