
function updateFileName() {
    const input = document.getElementById('fileInput');
    const fileNameDiv = document.getElementById('fileName');
    
    if (input.files.length > 0) {
        if (input.files.length === 1) {
            fileNameDiv.textContent = `📎 ${input.files[0].name}`;
        } else {
            fileNameDiv.textContent = `📎 ${input.files.length} fichiers sélectionnés`;
        }
    } else {
        fileNameDiv.textContent = '';
    }
}

document.getElementById('reclamationForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const objet = this.querySelector('input[type="text"]').value.trim();
    const categorie = this.querySelector('select').value;
    const description = this.querySelector('textarea').value.trim();
    const fichiers = document.getElementById('fileInput').files;
    
    if (!objet || !categorie || !description) {
        alert('Veuillez remplir tous les champs obligatoires');
        return;
    }
    
    if (objet.length < 5) {
        alert('L\'objet doit contenir au moins 5 caractères');
        return;
    }
    
    if (description.length < 10) {
        alert('La description doit contenir au moins 10 caractères');
        return;
    }
    
    const formData = new FormData();
    formData.append('objet', objet);
    formData.append('categorie', categorie);
    formData.append('description', description);
    formData.append('priorite', 'moyenne'); 
    
    if (fichiers.length > 0) {
        for (let i = 0; i < fichiers.length; i++) {
            formData.append('pieces_jointes[]', fichiers[i]);
        }
    }
    
    const submitBtn = this.querySelector('.submit-btn');
    const originalText = submitBtn.textContent;
    submitBtn.disabled = true;
    submitBtn.textContent = 'Envoi en cours...';
    
    fetch('add_reclamation.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('✓ Réclamation soumise avec succès !');
            window.location.href = 'mes_reclamations.php';
        } else {
            // Erreur
            alert('Erreur : ' + data.message);
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        alert('Une erreur s\'est produite. Veuillez réessayer.');
        submitBtn.disabled = false;
        submitBtn.textContent = originalText;
    });
});