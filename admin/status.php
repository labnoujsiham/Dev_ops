<?php


session_start();
require_once 'db_config.php';

$page_title = "Gestion des statuts";

$pdo = getDBConnection();

$statuts = [];
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pdo) {
    try {
        if (isset($_POST['action']) && $_POST['action'] === 'create') {
            $cle = trim($_POST['cle']);
            $libelle = trim($_POST['libelle']);
            
            $cle = strtolower(preg_replace('/\s+/', '_', $cle));
            
            if (empty($cle) || empty($libelle)) {
                $message = "La clé et le libellé sont obligatoires.";
                $messageType = "error";
            } else {
                $stmt = $pdo->prepare("SELECT id FROM statuts WHERE cle = ?");
                $stmt->execute([$cle]);
                if ($stmt->fetch()) {
                    $message = "Cette clé de statut existe déjà.";
                    $messageType = "error";
                } else {
                    $stmt = $pdo->prepare("INSERT INTO statuts (cle, libelle) VALUES (?, ?)");
                    $stmt->execute([$cle, $libelle]);
                    
                    $message = "Statut créé avec succès!";
                    $messageType = "success";
                }
            }
        }
        
        if (isset($_POST['action']) && $_POST['action'] === 'update') {
            $statutId = intval($_POST['statut_id']);
            $cle = trim($_POST['cle']);
            $libelle = trim($_POST['libelle']);
            
            $cle = strtolower(preg_replace('/\s+/', '_', $cle));
            
            if (empty($cle) || empty($libelle)) {
                $message = "La clé et le libellé sont obligatoires.";
                $messageType = "error";
            } else {
                $stmt = $pdo->prepare("SELECT id FROM statuts WHERE cle = ? AND id != ?");
                $stmt->execute([$cle, $statutId]);
                if ($stmt->fetch()) {
                    $message = "Cette clé de statut est déjà utilisée.";
                    $messageType = "error";
                } else {
                    $stmt = $pdo->prepare("UPDATE statuts SET cle = ?, libelle = ? WHERE id = ?");
                    $stmt->execute([$cle, $libelle, $statutId]);
                    
                    $message = "Statut modifié avec succès!";
                    $messageType = "success";
                }
            }
        }
        
        if (isset($_POST['action']) && $_POST['action'] === 'delete') {
            $statutId = intval($_POST['statut_id']);
            
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM reclamations WHERE statut_id = ?");
            $stmt->execute([$statutId]);
            $count = $stmt->fetch()['count'];
            
            if ($count > 0) {
                $message = "Impossible de supprimer: ce statut est utilisé par $count réclamation(s).";
                $messageType = "error";
            } else {
                $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM historique_statuts WHERE ancien_statut_id = ? OR nouveau_statut_id = ?");
                $stmt->execute([$statutId, $statutId]);
                $histCount = $stmt->fetch()['count'];
                
                if ($histCount > 0) {
                    $message = "Impossible de supprimer: ce statut est référencé dans l'historique.";
                    $messageType = "error";
                } else {
                    $stmt = $pdo->prepare("DELETE FROM statuts WHERE id = ?");
                    $stmt->execute([$statutId]);
                    
                    $message = "Statut supprimé avec succès!";
                    $messageType = "success";
                }
            }
        }
        
    } catch (PDOException $e) {
        $message = "Erreur: " . $e->getMessage();
        $messageType = "error";
        error_log("Statuts CRUD error: " . $e->getMessage());
    }
}

if ($pdo) {
    try {
        $stmt = $pdo->query("
            SELECT s.*, COUNT(r.id) as reclamation_count 
            FROM statuts s 
            LEFT JOIN reclamations r ON r.statut_id = s.id 
            GROUP BY s.id 
            ORDER BY s.id ASC
        ");
        $statuts = $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Fetch statuts error: " . $e->getMessage());
    }
}

function getStatusIcon($cle) {
    $icons = [
        'en_attente' => 'bx-time',
        'en_cours' => 'bx-loader-circle',
        'acceptee' => 'bx-check-circle',
        'rejetee' => 'bx-x-circle',
        'fermee' => 'bx-lock-alt',
        'attente_info' => 'bx-info-circle'
    ];
    foreach ($icons as $keyword => $icon) {
        if (strpos($cle, $keyword) !== false) {
            return $icon;
        }
    }
    return 'bx-radio-circle-marked';
}

function getStatusColor($cle) {
    $colors = [
        'en_attente' => '#f39c12',
        'en_cours' => '#45AECC',
        'acceptee' => '#27ae60',
        'rejetee' => '#e74c3c',
        'fermee' => '#95a5a6',
        'attente_info' => '#614AA9'
    ];
    foreach ($colors as $keyword => $color) {
        if (strpos($cle, $keyword) !== false) {
            return $color;
        }
    }
    return '#3498db';
}
?>
<?php include 'sidebar2.php'; ?>
<?php include 'topbar2.php'; ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Statuts - ReclaNova Admin</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link href="https://fonts.googleapis.com/css2?family=Afacad:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="sidebar2.css">
    <link rel="stylesheet" href="topbar2.css">
    <link rel="stylesheet" href="status.css">
</head>
<body>

    <div class="main-container">
        <div class="page-header">
            <div class="header-left">
                <h1 class="page-title">Gestion des statuts</h1>
                <p class="page-subtitle">Configurer les états des réclamations</p>
            </div>
            <button class="btn-create" onclick="openCreateModal()">
                <i class='bx bx-plus'></i>
                Nouveau statut
            </button>
        </div>

        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $messageType; ?>">
                <i class='bx <?php echo $messageType === 'success' ? 'bx-check-circle' : 'bx-error-circle'; ?>'></i>
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div class="status-flow-container">
            <div class="flow-header">
                <h3><i class='bx bx-git-branch'></i> Flux des statuts</h3>
                <span class="flow-info">Visualisation du parcours d'une réclamation</span>
            </div>
            <div class="status-flow">
                <?php foreach ($statuts as $index => $statut): ?>
                    <div class="flow-item">
                        <div class="flow-badge" style="background: <?php echo getStatusColor($statut['cle']); ?>;">
                            <i class='bx <?php echo getStatusIcon($statut['cle']); ?>'></i>
                        </div>
                        <span class="flow-label"><?php echo htmlspecialchars($statut['libelle']); ?></span>
                        <span class="flow-count"><?php echo $statut['reclamation_count']; ?></span>
                    </div>
                    <?php if ($index < count($statuts) - 1): ?>
                        <div class="flow-arrow">
                            <i class='bx bx-chevron-right'></i>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="statuts-grid">
            <?php if (empty($statuts)): ?>
                <div class="empty-state">
                    <i class='bx bx-flag'></i>
                    <h3>Aucun statut</h3>
                    <p>Créez votre premier statut pour commencer</p>
                    <button class="btn-create-empty" onclick="openCreateModal()">
                        <i class='bx bx-plus'></i> Créer un statut
                    </button>
                </div>
            <?php else: ?>
                <?php foreach ($statuts as $statut): ?>
                    <?php $color = getStatusColor($statut['cle']); ?>
                    <div class="status-card">
                        <div class="card-color-bar" style="background: <?php echo $color; ?>;"></div>
                        <div class="card-content">
                            <div class="card-top">
                                <div class="status-icon" style="background: <?php echo $color; ?>20; color: <?php echo $color; ?>;">
                                    <i class='bx <?php echo getStatusIcon($statut['cle']); ?>'></i>
                                </div>
                                <div class="card-actions">
                                    <button class="btn-card-action btn-edit" onclick="openEditModal(<?php echo htmlspecialchars(json_encode($statut)); ?>)" title="Modifier">
                                        <i class='bx bx-edit-alt'></i>
                                    </button>
                                    <button class="btn-card-action btn-delete" onclick="openDeleteModal(<?php echo $statut['id']; ?>, '<?php echo htmlspecialchars($statut['libelle'], ENT_QUOTES); ?>', <?php echo $statut['reclamation_count']; ?>)" title="Supprimer">
                                        <i class='bx bx-trash'></i>
                                    </button>
                                </div>
                            </div>
                            <div class="card-info">
                                <h3 class="status-libelle"><?php echo htmlspecialchars($statut['libelle']); ?></h3>
                                <span class="status-cle"><?php echo htmlspecialchars($statut['cle']); ?></span>
                            </div>
                            <div class="card-stats">
                                <div class="stat-item">
                                    <i class='bx bx-file'></i>
                                    <span><?php echo $statut['reclamation_count']; ?> réclamation(s)</span>
                                </div>
                                <span class="status-id">#<?php echo $statut['id']; ?></span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="table-container">
            <div class="table-header">
                <h3><i class='bx bx-list-ul'></i> Vue liste</h3>
                <span class="status-count"><?php echo count($statuts); ?> statut(s)</span>
            </div>
            
            <div class="table-wrapper">
                <table class="statuts-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Libellé</th>
                            <th>Clé</th>
                            <th>Réclamations</th>
                            <th>Aperçu</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($statuts)): ?>
                            <tr>
                                <td colspan="6" class="empty-message">
                                    Aucun statut trouvé
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($statuts as $statut): ?>
                                <?php $color = getStatusColor($statut['cle']); ?>
                                <tr>
                                    <td class="id-cell">#<?php echo $statut['id']; ?></td>
                                    <td class="libelle-cell">
                                        <div class="status-icon-small" style="background: <?php echo $color; ?>20; color: <?php echo $color; ?>;">
                                            <i class='bx <?php echo getStatusIcon($statut['cle']); ?>'></i>
                                        </div>
                                        <span><?php echo htmlspecialchars($statut['libelle']); ?></span>
                                    </td>
                                    <td class="cle-cell">
                                        <code><?php echo htmlspecialchars($statut['cle']); ?></code>
                                    </td>
                                    <td>
                                        <span class="count-badge">
                                            <?php echo $statut['reclamation_count']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="preview-badge" style="background: <?php echo $color; ?>20; color: <?php echo $color; ?>;">
                                            <?php echo htmlspecialchars($statut['libelle']); ?>
                                        </span>
                                    </td>
                                    <td class="actions-cell">
                                        <button class="btn-action btn-edit" onclick="openEditModal(<?php echo htmlspecialchars(json_encode($statut)); ?>)" title="Modifier">
                                            <i class='bx bx-edit-alt'></i>
                                        </button>
                                        <button class="btn-action btn-delete" onclick="openDeleteModal(<?php echo $statut['id']; ?>, '<?php echo htmlspecialchars($statut['libelle'], ENT_QUOTES); ?>', <?php echo $statut['reclamation_count']; ?>)" title="Supprimer">
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
                <h2><i class='bx bx-flag'></i> Nouveau statut</h2>
                <button class="modal-close" onclick="closeModal('createModal')">&times;</button>
            </div>
            <form method="POST" action="status.php">
                <input type="hidden" name="action" value="create">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="create-libelle">Libellé (affiché)</label>
                        <input type="text" id="create-libelle" name="libelle" required placeholder="Ex: En cours de traitement">
                        <span class="form-hint">Le nom qui sera affiché aux utilisateurs</span>
                    </div>
                    <div class="form-group">
                        <label for="create-cle">Clé technique</label>
                        <input type="text" id="create-cle" name="cle" required placeholder="Ex: en_cours">
                        <span class="form-hint">Identifiant unique (minuscules, underscores). Auto-formaté.</span>
                    </div>
                    <div class="preview-section">
                        <label>Aperçu</label>
                        <div class="status-preview" id="create-preview">
                            <span class="preview-badge preview-default">Nouveau statut</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-cancel" onclick="closeModal('createModal')">Annuler</button>
                    <button type="submit" class="btn-submit">Créer le statut</button>
                </div>
            </form>
        </div>
    </div>

    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class='bx bx-edit'></i> Modifier le statut</h2>
                <button class="modal-close" onclick="closeModal('editModal')">&times;</button>
            </div>
            <form method="POST" action="statuts.php">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="statut_id" id="edit-statut-id">
                <div class="modal-body">
                    <div class="current-status-display" id="edit-current-display">
                        <div class="current-badge" id="edit-current-badge">
                            <i class='bx bx-radio-circle-marked'></i>
                            <span>Statut actuel</span>
                        </div>
                    </div>
                    <div class="form-divider"></div>
                    <div class="form-group">
                        <label for="edit-libelle">Libellé (affiché)</label>
                        <input type="text" id="edit-libelle" name="libelle" required>
                        <span class="form-hint">Le nom qui sera affiché aux utilisateurs</span>
                    </div>
                    <div class="form-group">
                        <label for="edit-cle">Clé technique</label>
                        <input type="text" id="edit-cle" name="cle" required>
                        <span class="form-hint">Identifiant unique (minuscules, underscores)</span>
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
            <form method="POST" action="statuts.php">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="statut_id" id="delete-statut-id">
                <div class="modal-body">
                    <div class="delete-warning">
                        <i class='bx bx-trash'></i>
                        <p>Êtes-vous sûr de vouloir supprimer le statut</p>
                        <strong id="delete-statut-name">Statut</strong>?
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
        document.getElementById('create-libelle').addEventListener('input', function() {
            const preview = document.querySelector('#create-preview .preview-badge');
            preview.textContent = this.value || 'Nouveau statut';
        });

        function openCreateModal() {
            document.getElementById('create-libelle').value = '';
            document.getElementById('create-cle').value = '';
            document.querySelector('#create-preview .preview-badge').textContent = 'Nouveau statut';
            document.getElementById('createModal').classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function openEditModal(statut) {
            document.getElementById('edit-statut-id').value = statut.id;
            document.getElementById('edit-libelle').value = statut.libelle;
            document.getElementById('edit-cle').value = statut.cle;
            
            const badge = document.getElementById('edit-current-badge');
            badge.querySelector('span').textContent = statut.libelle;
            
            document.getElementById('editModal').classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function openDeleteModal(statutId, statutName, reclamationCount) {
            document.getElementById('delete-statut-id').value = statutId;
            document.getElementById('delete-statut-name').textContent = statutName;
            
            const warningText = document.getElementById('delete-warning-text');
            const confirmBtn = document.getElementById('delete-confirm-btn');
            
            if (reclamationCount > 0) {
                warningText.innerHTML = `<strong style="color: #e74c3c;">⚠️ Impossible de supprimer!</strong><br>Ce statut est utilisé par ${reclamationCount} réclamation(s).`;
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

        document.getElementById('create-cle').addEventListener('input', function() {
            this.value = this.value.toLowerCase().replace(/\s+/g, '_').replace(/[^a-z0-9_]/g, '');
        });

        document.getElementById('edit-cle').addEventListener('input', function() {
            this.value = this.value.toLowerCase().replace(/\s+/g, '_').replace(/[^a-z0-9_]/g, '');
        });
    </script>

</body>
</html>