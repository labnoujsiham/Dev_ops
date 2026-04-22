const container = document.querySelector('.container');
const registerBtn = document.querySelector('.register-btn');
const loginBtn = document.querySelector('.login-btn');

registerBtn.addEventListener('click', () => {
    container.classList.add('active');
});

loginBtn.addEventListener('click', () => {
    container.classList.remove('active');
});

// Fonction pour afficher les messages
function showAlert(elementId, message, type = 'error') {
    const alertBox = document.getElementById(elementId);
    alertBox.textContent = message;
    alertBox.className = `alert-box ${type}`;
    alertBox.style.display = 'block';
    
    // Animation d'entrée
    alertBox.style.animation = 'slideIn 0.3s ease';
    
    // Masquer après 5 secondes pour les succès
    if (type === 'success') {
        setTimeout(() => {
            alertBox.style.animation = 'slideOut 0.3s ease';
            setTimeout(() => {
                alertBox.style.display = 'none';
            }, 300);
        }, 5000);
    }
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

// visibilité mdp toggle
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


const registerPassword = document.getElementById('registerPassword');
const strengthBar = document.querySelector('.strength-bar');
const strengthText = document.querySelector('.strength-text');

if (registerPassword) {
    registerPassword.addEventListener('input', function() {
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


document.getElementById('loginForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const submitBtn = this.querySelector('.btn');
    const identifier = document.getElementById('loginIdentifier').value.trim();
    const password = document.getElementById('loginPassword').value;
    
   
    if (!identifier || !password) {
        showAlert('loginAlert', 'Veuillez remplir tous les champs', 'error');
        return;
    }
    
    toggleLoader(submitBtn, true);
    
    try {
        const response = await fetch('auth_handler.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                action: 'login',
                identifier: identifier,
                password: password
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showAlert('loginAlert', data.message, 'success');
            setTimeout(() => {
                window.location.href = data.redirect;
            }, 1000);
        } else {
            showAlert('loginAlert', data.message, 'error');
            toggleLoader(submitBtn, false);
        }
    } catch (error) {
        showAlert('loginAlert', 'Erreur de connexion. Veuillez réessayer.', 'error');
        toggleLoader(submitBtn, false);
    }
});


document.getElementById('registerForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const submitBtn = this.querySelector('.btn');
    const username = document.getElementById('registerUsername').value.trim();
    const email = document.getElementById('registerEmail').value.trim();
    const password = document.getElementById('registerPassword').value;
    
   
    if (!username || !email || !password) {
        showAlert('registerAlert', 'Veuillez remplir tous les champs', 'error');
        return;
    }
    
    if (username.length < 3) {
        showAlert('registerAlert', 'Le nom d\'utilisateur doit contenir au moins 3 caractères', 'error');
        return;
    }
    
    if (password.length < 6) {
        showAlert('registerAlert', 'Le mot de passe doit contenir au moins 6 caractères', 'error');
        return;
    }
    
    toggleLoader(submitBtn, true);
    
    try {
        const response = await fetch('auth_handler.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                action: 'register',
                username: username,
                email: email,
                password: password
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showAlert('registerAlert', data.message, 'success');
            this.reset();
            strengthBar.style.width = '0';
            strengthText.textContent = '';
            
            
            setTimeout(() => {
                container.classList.remove('active');
            }, 2000);
        } else {
            showAlert('registerAlert', data.message, 'error');
        }
        
        toggleLoader(submitBtn, false);
    } catch (error) {
        showAlert('registerAlert', 'Erreur d\'inscription. Veuillez réessayer.', 'error');
        toggleLoader(submitBtn, false);
    }
});


window.addEventListener('load', () => {
    container.style.opacity = '0';
    container.style.transform = 'scale(0.9)';
    
    setTimeout(() => {
        container.style.transition = 'all 0.5s ease';
        container.style.opacity = '1';
        container.style.transform = 'scale(1)';
    }, 100);
});