<?php


session_start();
require_once 'db_config.php';

$page_title = "Gestion des catégories";

$pdo = getDBConnection();


$categories = [];
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pdo) {
    try {
        
        if (isset($_POST['action']) && $_POST['action'] === 'create') {
            $nom = trim($_POST['nom']);
            $description = trim($_POST['description']);
            
            if (empty($nom)) {
                $message = "Le nom de la catégorie est obligatoire.";
                $messageType = "error";
            } else {
                
                $stmt = $pdo->prepare("SELECT id FROM categories WHERE nom = ?");
                $stmt->execute([$nom]);
                if ($stmt->fetch()) {
                    $message = "Cette catégorie existe déjà.";
                    $messageType = "error";
                } else {
                    $stmt = $pdo->prepare("INSERT INTO categories (nom, description) VALUES (?, ?)");
                    $stmt->execute([$nom, $description]);
                    
                    $message = "Catégorie créée avec succès!";
                    $messageType = "success";
                }
            }
        }
        
        if (isset($_POST['action']) && $_POST['action'] === 'update') {
            $categoryId = intval($_POST['category_id']);
            $nom = trim($_POST['nom']);
            $description = trim($_POST['description']);
            
            if (empty($nom)) {
                $message = "Le nom de la catégorie est obligatoire.";
                $messageType = "error";
            } else {
                $stmt = $pdo->prepare("SELECT id FROM categories WHERE nom = ? AND id != ?");
                $stmt->execute([$nom, $categoryId]);
                if ($stmt->fetch()) {
                    $message = "Ce nom de catégorie est déjà utilisé.";
                    $messageType = "error";
                } else {
                    $stmt = $pdo->prepare("UPDATE categories SET nom = ?, description = ? WHERE id = ?");
                    $stmt->execute([$nom, $description, $categoryId]);
                    
                    $message = "Catégorie modifiée avec succès!";
                    $messageType = "success";
                }
            }
        }
        
        if (isset($_POST['action']) && $_POST['action'] === 'delete') {
            $categoryId = intval($_POST['category_id']);
            
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM reclamations WHERE categorie_id = ?");
            $stmt->execute([$categoryId]);
            $count = $stmt->fetch()['count'];
            
            if ($count > 0) {
                $message = "Impossible de supprimer: cette catégorie est utilisée par $count réclamation(s).";
                $messageType = "error";
            } else {
                $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
                $stmt->execute([$categoryId]);
                
                $message = "Catégorie supprimée avec succès!";
                $messageType = "success";
            }
        }
        
    } catch (PDOException $e) {
        $message = "Erreur: " . $e->getMessage();
        $messageType = "error";
        error_log("Categories CRUD error: " . $e->getMessage());
    }
}

if ($pdo) {
    try {
        $stmt = $pdo->query("
            SELECT c.*, COUNT(r.id) as reclamation_count 
            FROM categories c 
            LEFT JOIN reclamations r ON r.categorie_id = c.id 
            GROUP BY c.id 
            ORDER BY c.nom ASC
        ");
        $categories = $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Fetch categories error: " . $e->getMessage());
    }
}

function getCategoryIcon($nom) {
    $icons = [
        'technique' => 'bx-wrench',
        'facturation' => 'bx-credit-card',
        'service' => 'bx-support',
        'livraison' => 'bx-package',
        'autre' => 'bx-dots-horizontal-rounded'
    ];
    $key = strtolower($nom);
    foreach ($icons as $keyword => $icon) {
        if (strpos($key, $keyword) !== false) {
            return $icon;
        }
    }
    return 'bx-category';
}

function getCategoryColor($index) {
    $colors = ['#45AECC', '#614AA9', '#f39c12', '#27ae60', '#e74c3c', '#3498db', '#e91e63'];
    return $colors[$index % count($colors)];
}
?>
<?php include 'sidebar2.php'; ?>
<?php include 'topbar2.php'; ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Catégories - ReclaNova Admin</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link href="https://fonts.googleapis.com/css2?family=Afacad:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="sidebar2.css">
    <link rel="stylesheet" href="topbar2.css">
    <link rel="stylesheet" href="categories.css">
</head>
<body>

    <div class="main-container">
        
        <!--  Header -->
        <div class="page-header">
            <div class="header-left">
                <h1 class="page-title">Gestion des catégories</h1>
                <p class="page-subtitle">Organiser les types de réclamations</p>
            </div>
            <button class="btn-create" onclick="openCreateModal()">
                <i class='bx bx-plus'></i>
                Nouvelle catégorie
            </button>
        </div>

        <!-- Alert  -->
        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $messageType; ?>">
                <i class='bx <?php echo $messageType === 'success' ? 'bx-check-circle' : 'bx-error-circle'; ?>'></i>
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <!-- Categories  -->
        <div class="categories-grid">
            <?php if (empty($categories)): ?>
                <div class="empty-state">
                    <i class='bx bx-category'></i>
                    <h3>Aucune catégorie</h3>
                    <p>Créez votre première catégorie pour commencer</p>
                    <button class="btn-create-empty" onclick="openCreateModal()">
                        <i class='bx bx-plus'></i> Créer une catégorie
                    </button>
                </div>
            <?php else: ?>
                <?php foreach ($categories as $index => $category): ?>
                    <div class="category-card">
                        <div class="card-header" style="background: linear-gradient(135deg, <?php echo getCategoryColor($index); ?>15 0%, #ffffff 100%);">
                            <div class="category-icon" style="background: <?php echo getCategoryColor($index); ?>20; color: <?php echo getCategoryColor($index); ?>;">
                                <i class='bx <?php echo getCategoryIcon($category['nom']); ?>'></i>
                            </div>
                            <div class="card-actions">
                                <button class="btn-card-action btn-edit" onclick="openEditModal(<?php echo htmlspecialchars(json_encode($category)); ?>)" title="Modifier">
                                    <i class='bx bx-edit-alt'></i>
                                </button>
                                <button class="btn-card-action btn-delete" onclick="openDeleteModal(<?php echo $category['id']; ?>, '<?php echo htmlspecialchars($category['nom'], ENT_QUOTES); ?>', <?php echo $category['reclamation_count']; ?>)" title="Supprimer">
                                    <i class='bx bx-trash'></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <h3 class="category-name"><?php echo htmlspecialchars($category['nom']); ?></h3>
                            <p class="category-description">
                                <?php echo htmlspecialchars($category['description'] ?: 'Aucune description'); ?>
                            </p>
                        </div>
                        <div class="card-footer">
                            <div class="reclamation-count">
                                <i class='bx bx-file'></i>
                                <span><?php echo $category['reclamation_count']; ?> réclamation(s)</span>
                            </div>
                            <span class="category-id">#<?php echo $category['id']; ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Table  -->
        <div class="table-container">
            <div class="table-header">
                <h3><i class='bx bx-list-ul'></i> Vue liste</h3>
                <span class="category-count"><?php echo count($categories); ?> catégorie(s)</span>
            </div>
            
            <div class="table-wrapper">
                <table class="categories-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nom</th>
                            <th>Description</th>
                            <th>Réclamations</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($categories)): ?>
                            <tr>
                                <td colspan="5" class="empty-message">
                                    Aucune catégorie trouvée
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($categories as $index => $category): ?>
                                <tr>
                                    <td class="id-cell">#<?php echo $category['id']; ?></td>
                                    <td class="name-cell">
                                        <div class="category-icon-small" style="background: <?php echo getCategoryColor($index); ?>20; color: <?php echo getCategoryColor($index); ?>;">
                                            <i class='bx <?php echo getCategoryIcon($category['nom']); ?>'></i>
                                        </div>
                                        <span><?php echo htmlspecialchars($category['nom']); ?></span>
                                    </td>
                                    <td class="description-cell">
                                        <?php echo htmlspecialchars($category['description'] ?: '—'); ?>
                                    </td>
                                    <td>
                                        <span class="count-badge">
                                            <?php echo $category['reclamation_count']; ?>
                                        </span>
                                    </td>
                                    <td class="actions-cell">
                                        <button class="btn-action btn-edit" onclick="openEditModal(<?php echo htmlspecialchars(json_encode($category)); ?>)" title="Modifier">
                                            <i class='bx bx-edit-alt'></i>
                                        </button>
                                        <button class="btn-action btn-delete" onclick="openDeleteModal(<?php echo $category['id']; ?>, '<?php echo htmlspecialchars($category['nom'], ENT_QUOTES); ?>', <?php echo $category['reclamation_count']; ?>)" title="Supprimer">
                                            <i class='bx bx-trash'></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!--  Modal -->
    <div id="createModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class='bx bx-category-alt'></i> Nouvelle catégorie</h2>
                <button class="modal-close" onclick="closeModal('createModal')">&times;</button>
            </div>
            <form method="POST" action="categories.php">
                <input type="hidden" name="action" value="create">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="create-nom">Nom de la catégorie</label>
                        <input type="text" id="create-nom" name="nom" required placeholder="Ex: Technique, Facturation...">
                    </div>
                    <div class="form-group">
                        <label for="create-description">Description <span class="optional">(optionnel)</span></label>
                        <textarea id="create-description" name="description" rows="3" placeholder="Décrivez cette catégorie..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-cancel" onclick="closeModal('createModal')">Annuler</button>
                    <button type="submit" class="btn-submit">Créer la catégorie</button>
                </div>
            </form>
        </div>
    </div>

    <!-- EDIT -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class='bx bx-edit'></i> Modifier la catégorie</h2>
                <button class="modal-close" onclick="closeModal('editModal')">&times;</button>
            </div>
            <form method="POST" action="categories.php">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="category_id" id="edit-category-id">
                <div class="modal-body">
                    <div class="category-preview" id="edit-preview">
                        <div class="preview-icon" id="edit-preview-icon">
                            <i class='bx bx-category'></i>
                        </div>
                        <span class="preview-name" id="edit-preview-name">Catégorie</span>
                    </div>
                    <div class="form-divider"></div>
                    <div class="form-group">
                        <label for="edit-nom">Nom de la catégorie</label>
                        <input type="text" id="edit-nom" name="nom" required>
                    </div>
                    <div class="form-group">
                        <label for="edit-description">Description <span class="optional">(optionnel)</span></label>
                        <textarea id="edit-description" name="description" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-cancel" onclick="closeModal('editModal')">Annuler</button>
                    <button type="submit" class="btn-submit">Enregistrer les modifications</button>
                </div>
            </form>
        </div>
    </div>

    <!-- DELETE  -->
    <div id="deleteModal" class="modal">
        <div class="modal-content modal-small">
            <div class="modal-header modal-header-danger">
                <h2><i class='bx bx-error-circle'></i> Confirmer la suppression</h2>
                <button class="modal-close" onclick="closeModal('deleteModal')">&times;</button>
            </div>
            <form method="POST" action="categories.php">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="category_id" id="delete-category-id">
                <div class="modal-body">
                    <div class="delete-warning">
                        <i class='bx bx-trash'></i>
                        <p>Êtes-vous sûr de vouloir supprimer la catégorie</p>
                        <strong id="delete-category-name">Catégorie</strong>?
                        <p class="warning-text" id="delete-warning-text">Cette action est irréversible.</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-cancel" onclick="closeModal('deleteModal')">Annuler</button>
                    <button type="submit" class="btn-delete-confirm" id="delete-confirm-btn">Confirmer la suppression</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Create Modal
        function openCreateModal() {
            document.getElementById('createModal').classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        //  Edit Modal 
        function openEditModal(category) {
            document.getElementById('edit-category-id').value = category.id;
            document.getElementById('edit-nom').value = category.nom;
            document.getElementById('edit-description').value = category.description || '';
            document.getElementById('edit-preview-name').textContent = category.nom;
            
            document.getElementById('editModal').classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        //  Delete  Modal
        function openDeleteModal(categoryId, categoryName, reclamationCount) {
            document.getElementById('delete-category-id').value = categoryId;
            document.getElementById('delete-category-name').textContent = categoryName;
            
            const warningText = document.getElementById('delete-warning-text');
            const confirmBtn = document.getElementById('delete-confirm-btn');
            
            if (reclamationCount > 0) {
                warningText.innerHTML = `<strong style="color: #e74c3c;">⚠️ Impossible de supprimer!</strong><br>Cette catégorie contient ${reclamationCount} réclamation(s).`;
                confirmBtn.disabled = true;
                confirmBtn.style.opacity = '0.5';
                confirmBtn.style.cursor = 'not-allowed';
            } else {
                warningText.textContent = 'Cette action est irréversible.';
                confirmBtn.disabled = false;
                confirmBtn.style.opacity = '1';
                confirmBtn.style.cursor = 'pointer';
            }
            
            document.getElementById('deleteModal').classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        // Close Modal
        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
            document.body.style.overflow = 'auto';
        }

        // Close modal si l'utilisateur clique ailleurs
        document.querySelectorAll('.modal').forEach(modal => {
            modal.addEventListener('click', function(e) {
                if (e.target === this) {
                    this.classList.remove('active');
                    document.body.style.overflow = 'auto';
                }
            });
        });

        // Close modal si échape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                document.querySelectorAll('.modal.active').forEach(modal => {
                    modal.classList.remove('active');
                });
                document.body.style.overflow = 'auto';
            }
        });
    </script>

</body>
</html>