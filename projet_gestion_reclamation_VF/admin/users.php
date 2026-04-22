<?php


session_start();
require_once 'db_config.php';

$page_title = "Gestion des utilisateurs";

$pdo = getDBConnection();

$users = [];
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pdo) {
    try {
        if (isset($_POST['action']) && $_POST['action'] === 'create') {
            $nom = trim($_POST['nom']);
            $email = trim($_POST['email']);
            $role = $_POST['role'];
            $password = $_POST['password'];
            
            if (empty($nom) || empty($email) || empty($password)) {
                $message = "Tous les champs sont obligatoires.";
                $messageType = "error";
            } else {
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                $stmt->execute([$email]);
                if ($stmt->fetch()) {
                    $message = "Cet email existe déjà.";
                    $messageType = "error";
                } else {
                    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("
                        INSERT INTO users (nom, email, role, mot_de_passe, date_creation) 
                        VALUES (?, ?, ?, ?, NOW())
                    ");
                    $stmt->execute([$nom, $email, $role, $hashedPassword]);
                    
                    $message = "Utilisateur créé avec succès!";
                    $messageType = "success";
                }
            }
        }
        
        if (isset($_POST['action']) && $_POST['action'] === 'update') {
            $userId = intval($_POST['user_id']);
            $nom = trim($_POST['nom']);
            $email = trim($_POST['email']);
            $role = $_POST['role'];
            $newPassword = $_POST['password'] ?? '';
            
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$email, $userId]);
            if ($stmt->fetch()) {
                $message = "Cet email est déjà utilisé par un autre utilisateur.";
                $messageType = "error";
            } else {
                if (!empty($newPassword)) {
                    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("
                        UPDATE users SET nom = ?, email = ?, role = ?, mot_de_passe = ? WHERE id = ?
                    ");
                    $stmt->execute([$nom, $email, $role, $hashedPassword, $userId]);
                } else {
                    $stmt = $pdo->prepare("
                        UPDATE users SET nom = ?, email = ?, role = ? WHERE id = ?
                    ");
                    $stmt->execute([$nom, $email, $role, $userId]);
                }
                
                $message = "Utilisateur modifié avec succès!";
                $messageType = "success";
            }
        }
        
        if (isset($_POST['action']) && $_POST['action'] === 'delete') {
            $userId = intval($_POST['user_id']);
            
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            
            $message = "Utilisateur supprimé avec succès!";
            $messageType = "success";
        }
        
    } catch (PDOException $e) {
        $message = "Erreur: " . $e->getMessage();
        $messageType = "error";
        error_log("Users CRUD error: " . $e->getMessage());
    }
}

if ($pdo) {
    try {
        $stmt = $pdo->query("
            SELECT id, nom, email, role, date_creation, dernier_login 
            FROM users 
            ORDER BY date_creation DESC
        ");
        $users = $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Fetch users error: " . $e->getMessage());
    }
}

function formatRole($role) {
    $roles = [
        'reclamant' => 'Réclamant',
        'gestionnaire' => 'Gestionnaire',
        'administrateur' => 'Administrateur'
    ];
    return $roles[$role] ?? $role;
}

function getRoleClass($role) {
    $classes = [
        'reclamant' => 'role-reclamant',
        'gestionnaire' => 'role-gestionnaire',
        'administrateur' => 'role-admin'
    ];
    return $classes[$role] ?? 'role-default';
}

function formatDate($date) {
    if (!$date) return 'Jamais';
    return date('d/m/Y H:i', strtotime($date));
}
?>
<?php include 'sidebar2.php'; ?>
<?php include 'topbar2.php'; ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Utilisateurs - ReclaNova Admin</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link href="https://fonts.googleapis.com/css2?family=Afacad:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="sidebar2.css">
    <link rel="stylesheet" href="topbar2.css">
    <link rel="stylesheet" href="users.css">
</head>
<body>

    <div class="main-container">
        
        <div class="page-header">
            <div class="header-left">
                <h1 class="page-title">Gestion des utilisateurs</h1>
                <p class="page-subtitle">Gérer tous les comptes utilisateurs du système</p>
            </div>
            <button class="btn-create" onclick="openCreateModal()">
                <i class='bx bx-plus'></i>
                Nouvel utilisateur
            </button>
        </div>

        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $messageType; ?>">
                <i class='bx <?php echo $messageType === 'success' ? 'bx-check-circle' : 'bx-error-circle'; ?>'></i>
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div class="table-container">
            <div class="table-header">
                <h3><i class='bx bx-group'></i> Liste des utilisateurs</h3>
                <span class="user-count"><?php echo count($users); ?> utilisateur(s)</span>
            </div>
            
            <div class="table-wrapper">
                <table class="users-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nom</th>
                            <th>Email</th>
                            <th>Rôle</th>
                            <th>Date création</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($users)): ?>
                            <tr>
                                <td colspan="6" class="empty-message">
                                    <i class='bx bx-user-x'></i>
                                    <p>Aucun utilisateur trouvé</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td class="id-cell">#<?php echo $user['id']; ?></td>
                                    <td class="name-cell">
                                        <div class="user-avatar-small">
                                            <?php echo strtoupper(substr($user['nom'], 0, 1)); ?>
                                        </div>
                                        <?php echo htmlspecialchars($user['nom']); ?>
                                    </td>
                                    <td class="email-cell"><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td>
                                        <span class="role-badge <?php echo getRoleClass($user['role']); ?>">
                                            <?php echo formatRole($user['role']); ?>
                                        </span>
                                    </td>
                                    <td class="date-cell"><?php echo formatDate($user['date_creation']); ?></td>
                                    <td class="actions-cell">
                                        <button class="btn-action btn-edit" onclick="openEditModal(<?php echo htmlspecialchars(json_encode($user)); ?>)" title="Modifier">
                                            <i class='bx bx-edit-alt'></i>
                                        </button>
                                        <button class="btn-action btn-delete" onclick="openDeleteModal(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['nom'], ENT_QUOTES); ?>')" title="Supprimer">
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

    <div id="createModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class='bx bx-user-plus'></i> Créer un utilisateur</h2>
                <button class="modal-close" onclick="closeModal('createModal')">&times;</button>
            </div>
            <form method="POST" action="users.php">
                <input type="hidden" name="action" value="create">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="create-nom">Nom complet</label>
                        <input type="text" id="create-nom" name="nom" required placeholder="Entrez le nom complet">
                    </div>
                    <div class="form-group">
                        <label for="create-email">Email</label>
                        <input type="email" id="create-email" name="email" required placeholder="exemple@email.com">
                    </div>
                    <div class="form-group">
                        <label for="create-role">Rôle</label>
                        <select id="create-role" name="role" required>
                            <option value="reclamant">Réclamant</option>
                            <option value="gestionnaire">Gestionnaire</option>
                            <option value="administrateur">Administrateur</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="create-password">Mot de passe</label>
                        <input type="password" id="create-password" name="password" required placeholder="Minimum 6 caractères">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-cancel" onclick="closeModal('createModal')">Annuler</button>
                    <button type="submit" class="btn-submit">Créer l'utilisateur</button>
                </div>
            </form>
        </div>
    </div>

    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class='bx bx-edit'></i> Modifier l'utilisateur</h2>
                <button class="modal-close" onclick="closeModal('editModal')">&times;</button>
            </div>
            <form method="POST" action="users.php">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="user_id" id="edit-user-id">
                <div class="modal-body">
                    <div class="user-profile-header">
                        <div class="profile-avatar" id="edit-avatar">A</div>
                        <div class="profile-info">
                            <span class="profile-name" id="edit-display-name">Nom</span>
                            <span class="profile-email" id="edit-display-email">email@example.com</span>
                        </div>
                    </div>
                    <div class="form-divider"></div>
                    <div class="form-group">
                        <label for="edit-nom">Nom complet</label>
                        <input type="text" id="edit-nom" name="nom" required>
                    </div>
                    <div class="form-group">
                        <label for="edit-email">Email</label>
                        <input type="email" id="edit-email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label for="edit-role">Rôle</label>
                        <select id="edit-role" name="role" required>
                            <option value="reclamant">Réclamant</option>
                            <option value="gestionnaire">Gestionnaire</option>
                            <option value="administrateur">Administrateur</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="edit-password">Nouveau mot de passe <span class="optional">(laisser vide pour ne pas changer)</span></label>
                        <input type="password" id="edit-password" name="password" placeholder="Nouveau mot de passe">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-cancel" onclick="closeModal('editModal')">Annuler</button>
                    <button type="submit" class="btn-submit">Enregistrer les modifications</button>
                </div>
            </form>
        </div>
    </div>

    <div id="deleteModal" class="modal">
        <div class="modal-content modal-small">
            <div class="modal-header modal-header-danger">
                <h2><i class='bx bx-error-circle'></i> Confirmer la suppression</h2>
                <button class="modal-close" onclick="closeModal('deleteModal')">&times;</button>
            </div>
            <form method="POST" action="users.php">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="user_id" id="delete-user-id">
                <div class="modal-body">
                    <div class="delete-warning">
                        <i class='bx bx-trash'></i>
                        <p>Êtes-vous sûr de vouloir supprimer l'utilisateur</p>
                        <strong id="delete-user-name">Nom</strong>?
                        <p class="warning-text">Cette action est irréversible.</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-cancel" onclick="closeModal('deleteModal')">Annuler</button>
                    <button type="submit" class="btn-delete-confirm">Confirmer la suppression</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openCreateModal() {
            document.getElementById('createModal').classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function openEditModal(user) {
            document.getElementById('edit-user-id').value = user.id;
            document.getElementById('edit-nom').value = user.nom;
            document.getElementById('edit-email').value = user.email;
            document.getElementById('edit-role').value = user.role;
            document.getElementById('edit-password').value = '';
            document.getElementById('edit-avatar').textContent = user.nom.charAt(0).toUpperCase();
            document.getElementById('edit-display-name').textContent = user.nom;
            document.getElementById('edit-display-email').textContent = user.email;
            
            document.getElementById('editModal').classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function openDeleteModal(userId, userName) {
            document.getElementById('delete-user-id').value = userId;
            document.getElementById('delete-user-name').textContent = userName;
            document.getElementById('deleteModal').classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
            document.body.style.overflow = 'auto';
        }

        document.querySelectorAll('.modal').forEach(modal => {
            modal.addEventListener('click', function(e) {
                if (e.target === this) {
                    this.classList.remove('active');
                    document.body.style.overflow = 'auto';
                }
            });
        });

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