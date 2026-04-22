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
?>
<?php include 'sidebar2.php'; ?>
<?php include 'topbar2.php'; ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajouter une réclamation - ReclaNova</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link href="https://fonts.googleapis.com/css2?family=Afacad:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="ajouter_reclamation.css">
    <link rel="stylesheet" href="sidebar2.css">
    <link rel="stylesheet" href="topbar2.css">
</head>
<body>

    <div class="form-wrapper">
        <div class="form-header">
            <h1>Ajouter une réclamation</h1>
            <p>Remplissez les informations ci-dessous pour soumettre votre réclamation</p>
        </div>

        <div class="info-box">
            <i class='bx bx-info-circle'></i>
            <p>Veuillez fournir des détails clairs et précis pour faciliter le traitement de votre réclamation.</p>
        </div>

        <form id="reclamationForm">
            <!-- Objet -->
            <div class="form-group">
                <label class="form-label">
                    <i class='bx bx-bookmarks'></i>
                    Objet
                </label>
                <input 
                    type="text" 
                    class="form-input" 
                    placeholder="Ex: Problème de facturation"
                    required
                >
            </div>

            <!-- Catégorie -->
            <div class="form-group">
                <label class="form-label">
                    <i class='bx bx-category'></i>
                    Catégorie
                </label>
                <div class="select-wrapper">
                    <select class="form-input" required>
                        <option value="">Sélectionnez une catégorie</option>
                        <option value="Technique">Technique</option>
                        <option value="Facturation">Facturation</option>
                        <option value="Service">Service</option>
                        <option value="Livraison">Livraison</option>
                        <option value="Autre">Autre</option>
                    </select>
                    <i class='bx bx-chevron-down'></i>
                </div>
            </div>

            <!-- Description -->
            <div class="form-group">
                <label class="form-label">
                    <i class='bx bx-message-square-detail'></i>
                    Description
                </label>
                <textarea 
                    class="form-input" 
                    placeholder="Décrivez votre réclamation en détail..."
                    required
                ></textarea>
            </div>

            <!-- Pièces jointes -->
            <div class="form-group">
                <label class="form-label">
                    <i class='bx bx-paperclip'></i>
                    Pièces jointes
                </label>
                <div class="file-input-wrapper">
                    <input 
                        type="file" 
                        id="fileInput" 
                        name="pieces_jointes[]"
                        accept=".pdf,.jpg,.jpeg,.png,.doc,.docx"
                        multiple
                        onchange="updateFileName()"
                    >
                    <label for="fileInput" class="file-input-label">
                        <i class='bx bx-cloud-upload'></i>
                        <span>Cliquez pour joindre des fichiers</span>
                    </label>
                </div>
                <div class="file-name" id="fileName"></div>
            </div>

            <!-- Submit Button -->
            <button type="submit" class="submit-btn">
                Envoyer la réclamation
            </button>
        </form>
    </div>

    <!-- Script pour gérer le formulaire -->
    <script src="reclamation_handler.js"></script>

</body>
</html>