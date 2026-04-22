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

$reclamationId = $_GET['id'] ?? 0;

// Récupérer la réclamation
try {
    $stmt = $pdo->prepare("
        SELECT r.*, c.nom AS categorie_nom
        FROM reclamations r
        LEFT JOIN categories c ON r.categorie_id = c.id
        WHERE r.id = ? AND r.user_id = ?
    ");
    $stmt->execute([$reclamationId, $userId]);
    $reclamation = $stmt->fetch();
    
    if (!$reclamation) {
        header('Location: mes_reclamations.php');
        exit;
    }
    
    // Récupérer les catégories
    $stmt = $pdo->query("SELECT * FROM categories ORDER BY nom");
    $categories = $stmt->fetchAll();
    
} catch (PDOException $e) {
    header('Location: mes_reclamations.php');
    exit;
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $objet = trim($_POST['objet'] ?? '');
    $categorieId = $_POST['categorie_id'] ?? 0;
    $description = trim($_POST['description'] ?? '');
    
    if (!empty($objet) && !empty($description) && $categorieId > 0) {
        try {
            $stmt = $pdo->prepare("
                UPDATE reclamations 
                SET objet = ?, categorie_id = ?, description = ?
                WHERE id = ? AND user_id = ?
            ");
            $stmt->execute([$objet, $categorieId, $description, $reclamationId, $userId]);
            
            header('Location: detail_reclamation.php?id=' . $reclamationId);
            exit;
        } catch (PDOException $e) {
            $error = "Erreur lors de la modification";
        }
    } else {
        $error = "Tous les champs sont obligatoires";
    }
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
    <title>Modifier Réclamation - ReclaNova</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link href="https://fonts.googleapis.com/css2?family=Afacad:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="sidebar2.css">
    <link rel="stylesheet" href="topbar2.css">
    <link rel="stylesheet" href="ajouter_reclamation.css">
</head>
<body>

    <div class="form-wrapper">
        <div class="form-header">
            <h1>Modifier la réclamation</h1>
            <p>Modifiez les informations de votre réclamation</p>
        </div>

        <?php if (isset($error)): ?>
        <div style="background: #f8d7da; color: #721c24; padding: 15px; margin-bottom: 20px; border-radius: 5px;">
            <strong>Erreur:</strong> <?php echo $error; ?>
        </div>
        <?php endif; ?>

        <form method="POST">
            <!-- Objet -->
            <div class="form-group">
                <label class="form-label">
                    <i class='bx bx-bookmarks'></i>
                    Objet
                </label>
                <input 
                    type="text" 
                    name="objet"
                    class="form-input" 
                    value="<?php echo htmlspecialchars($reclamation['objet']); ?>"
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
                    <select name="categorie_id" class="form-input" required>
                        <option value="">Sélectionnez une catégorie</option>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>" <?php echo ($reclamation['categorie_id'] == $cat['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat['nom']); ?>
                        </option>
                        <?php endforeach; ?>
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
                    name="description"
                    class="form-input" 
                    required
                ><?php echo htmlspecialchars($reclamation['description']); ?></textarea>
            </div>

            <!-- Submit Button -->
            <div style="display: flex; gap: 10px;">
                <button type="submit" class="submit-btn">
                    Enregistrer les modifications
                </button>
                <a href="detail_reclamation.php?id=<?php echo $reclamationId; ?>" class="submit-btn" style="background: #6c757d; text-decoration: none; text-align: center;">
                    Annuler
                </a>
            </div>
        </form>
    </div>

</body>
</html>