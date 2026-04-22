<?php


session_start();
require_once 'db_config.php';

$page_title = "Détail réclamation";
$pdo = getDBConnection();
$reclamation_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$reclamation = null;
$commentaires = [];
$statuts = [];
$pieces_jointes = [];
$gestionnaires = [];
$message = '';
$messageType = '';
$show_assign_modal = false; 

$current_admin_id = $_SESSION['user_id'] ?? 2;
$current_admin_nom = $_SESSION['user_name'] ?? 'Admin';

if (!$pdo || $reclamation_id <= 0) {
    header('Location: reclamation.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        
        if (isset($_POST['action']) && $_POST['action'] === 'assign_from_popup') {
            $gestionnaire_id = intval($_POST['gestionnaire_id']);
            
            if ($gestionnaire_id > 0) {
                $stmt = $pdo->prepare("
                    UPDATE reclamations 
                    SET gestionnaire_id = :gestionnaire_id,
                        date_dernier_update = NOW()
                    WHERE id = :id
                ");
                $stmt->execute([
                    ':gestionnaire_id' => $gestionnaire_id,
                    ':id' => $reclamation_id
                ]);
                
                try {
                    $stmt = $pdo->prepare("SELECT objet, priorite FROM reclamations WHERE id = ?");
                    $stmt->execute([$reclamation_id]);
                    $recl = $stmt->fetch();
                    
                    if ($recl) {
                        $contenu = ($recl['priorite'] === 'haute') 
                            ? '⚠️ URGENT - Vous avez été assigné à : ' . $recl['objet']
                            : 'Vous avez été assigné à : ' . $recl['objet'];
                        
                        $stmt = $pdo->prepare("
                            INSERT INTO notifications (user_id, type, reference_table, reference_id, contenu, lu, date_creation)
                            VALUES (?, 'assignation', 'reclamations', ?, ?, 0, NOW())
                        ");
                        $stmt->execute([$gestionnaire_id, $reclamation_id, $contenu]);
                    }
                } catch (PDOException $e) {
                    error_log("Erreur notification assignation: " . $e->getMessage());
                }
                
                $message = "Gestionnaire assigné avec succès !";
                $messageType = "success";
            }
        }
        
        if (isset($_POST['action']) && $_POST['action'] === 'update_priority') {
            $new_priorite = $_POST['priorite'];
            
            if (in_array($new_priorite, ['basse', 'moyenne', 'haute'])) {
                $stmt = $pdo->prepare("
                    UPDATE reclamations 
                    SET priorite = :priorite,
                        date_dernier_update = NOW()
                    WHERE id = :id
                ");
                $stmt->execute([
                    ':priorite' => $new_priorite,
                    ':id' => $reclamation_id
                ]);
             
                if ($new_priorite === 'haute') {
                    $show_assign_modal = true;
                } else {
                    $message = "Priorité mise à jour !";
                    $messageType = "success";
                }
            }
        }
        
        if (isset($_POST['action']) && $_POST['action'] === 'update_status') {
            $new_statut_id = intval($_POST['statut_id']);
            
            $stmt = $pdo->prepare("
                UPDATE reclamations 
                SET statut_id = :statut_id, 
                    date_dernier_update = NOW()
                WHERE id = :id
            ");
            $stmt->execute([
                ':statut_id' => $new_statut_id,
                ':id' => $reclamation_id
            ]);
            
            $stmt = $pdo->prepare("SELECT cle FROM statuts WHERE id = ?");
            $stmt->execute([$new_statut_id]);
            $new_statut = $stmt->fetch();
            
            if ($new_statut && $new_statut['cle'] === 'fermee') {
                try {
                    $stmt = $pdo->prepare("SELECT user_id FROM reclamations WHERE id = ?");
                    $stmt->execute([$reclamation_id]);
                    $recl = $stmt->fetch();
                    
                    if ($recl) {
                        $stmt = $pdo->prepare("
                            INSERT INTO notifications (user_id, type, reference_table, reference_id, contenu, lu, date_creation) 
                            VALUES (?, 'statut_ferme', 'reclamations', ?, 'Votre réclamation a été clôturée', 0, NOW())
                        ");
                        $stmt->execute([$recl['user_id'], $reclamation_id]);
                    }
                } catch (PDOException $e) {
                    error_log("Erreur notification fermée: " . $e->getMessage());
                }
            }
            
            $message = "Statut mis à jour avec succès !";
            $messageType = "success";
        }
        
    } catch (PDOException $e) {
        $message = "Erreur: " . $e->getMessage();
        $messageType = "error";
        error_log("Admin detail error: " . $e->getMessage());
    }
}

try {
    $stmt = $pdo->prepare("
        SELECT 
            r.*,
            c.nom AS categorie_nom,
            s.libelle AS statut_libelle,
            s.cle AS statut_cle,
            u.nom AS reclamant_nom,
            u.email AS reclamant_email,
            g.nom AS gestionnaire_nom,
            g.id AS gestionnaire_id
        FROM reclamations r
        LEFT JOIN categories c ON r.categorie_id = c.id
        LEFT JOIN statuts s ON r.statut_id = s.id
        LEFT JOIN users u ON r.user_id = u.id
        LEFT JOIN users g ON r.gestionnaire_id = g.id
        WHERE r.id = :id
    ");
    $stmt->execute([':id' => $reclamation_id]);
    $reclamation = $stmt->fetch();
    
    if (!$reclamation) {
        header('Location: reclamation.php');
        exit;
    }
    
    $stmt = $pdo->query("SELECT id, cle, libelle FROM statuts ORDER BY id");
    $statuts = $stmt->fetchAll();
    
    $stmt = $pdo->query("SELECT id, nom FROM users WHERE role IN ('gestionnaire', 'administrateur') ORDER BY nom");
    $gestionnaires = $stmt->fetchAll();
    
    $stmt = $pdo->prepare("
        SELECT 
            cm.*,
            u.nom AS auteur_nom,
            u.role AS auteur_role
        FROM commentaires cm
        LEFT JOIN users u ON cm.auteur_id = u.id
        WHERE cm.reclamation_id = :id
        ORDER BY cm.date_commentaire ASC
    ");
    $stmt->execute([':id' => $reclamation_id]);
    $commentaires = $stmt->fetchAll();
    
    $stmt = $pdo->prepare("
        SELECT * FROM pieces_jointes 
        WHERE reclamation_id = :id 
        ORDER BY date_ajout ASC
    ");
    $stmt->execute([':id' => $reclamation_id]);
    $pieces_jointes = $stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log("Fetch error: " . $e->getMessage());
    header('Location: reclamation.php');
    exit;
}

function getStatusClass($statutCle) {
    $classes = [
        'en_cours' => 'status-processing',
        'en_attente' => 'status-pending',
        'acceptee' => 'status-accepted',
        'rejetee' => 'status-rejected',
        'fermee' => 'status-closed',
        'attente_info_reclamant' => 'status-info',
    ];
    return $classes[$statutCle] ?? 'status-default';
}

function formatDateShort($date) {
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
    <title>Détail Réclamation - Admin</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link href="https://fonts.googleapis.com/css2?family=Afacad:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="sidebar2.css">
    <link rel="stylesheet" href="topbar2.css">
    <link rel="stylesheet" href="detail_reclamation.css">
    <link rel="stylesheet" href="../gestionnaire/detail_reclamation.css">
    
    <style>
    .modal-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.6);
        z-index: 9999;
        align-items: center;
        justify-content: center;
    }
    
    .modal-overlay.show {
        display: flex;
    }
    
    .modal-content {
        background: white;
        border-radius: 16px;
        padding: 0;
        width: 90%;
        max-width: 500px;
        box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        animation: modalSlide 0.3s ease;
    }
    
    @keyframes modalSlide {
        from {
            opacity: 0;
            transform: translateY(-50px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .modal-header {
        background: linear-gradient(135deg, #e74c3c, #c0392b);
        color: white;
        padding: 25px;
        border-radius: 16px 16px 0 0;
        display: flex;
        align-items: center;
        gap: 15px;
    }
    
    .modal-header i {
        font-size: 32px;
    }
    
    .modal-header h3 {
        margin: 0;
        font-size: 20px;
    }
    
    .modal-body {
        padding: 30px;
    }
    
    .modal-body label {
        display: block;
        font-weight: 600;
        margin-bottom: 10px;
        color: #2c3e50;
    }
    
    .modal-body select {
        width: 100%;
        padding: 12px;
        border: 2px solid #e0e0e0;
        border-radius: 8px;
        font-size: 15px;
        font-family: 'Afacad', sans-serif;
        transition: 0.3s;
    }
    
    .modal-body select:focus {
        outline: none;
        border-color: #e74c3c;
    }
    
    .urgent-warning {
        background: #fff3cd;
        border-left: 4px solid #f39c12;
        padding: 15px;
        margin-bottom: 20px;
        border-radius: 8px;
        color: #856404;
        font-size: 14px;
    }
    
    .urgent-warning i {
        margin-right: 8px;
        font-size: 18px;
    }
    
    .modal-footer {
        padding: 20px 30px;
        border-top: 1px solid #f0f0f0;
        display: flex;
        gap: 10px;
        justify-content: flex-end;
    }
    
    .modal-btn {
        padding: 12px 24px;
        border: none;
        border-radius: 8px;
        font-size: 15px;
        font-weight: 600;
        cursor: pointer;
        transition: 0.3s;
        font-family: 'Afacad', sans-serif;
    }
    
    .modal-btn-cancel {
        background: #ecf0f1;
        color: #7f8c8d;
    }
    
    .modal-btn-cancel:hover {
        background: #d5dbdb;
    }
    
    .modal-btn-assign {
        background: #e74c3c;
        color: white;
    }
    
    .modal-btn-assign:hover {
        background: #c0392b;
    }
    </style>
</head>
<body>

    <div class="main-container">
        
        <a href="reclamation.php" class="back-link">
            <i class='bx bx-arrow-back'></i> Retour à la liste
        </a>

        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div class="detail-container">
            
            <div class="detail-left">
                
                <div class="reclamation-header">
                    <div class="header-top">
                        <h1 class="reclamation-title"><?php echo htmlspecialchars($reclamation['objet']); ?></h1>
                    </div>
                    <span class="status-badge <?php echo getStatusClass($reclamation['statut_cle']); ?>">
                        <?php echo htmlspecialchars($reclamation['statut_libelle']); ?>
                    </span>
                </div>

                <div class="section">
                    <h3 class="section-title-detail">description</h3>
                    <p class="description-text"><?php echo nl2br(htmlspecialchars($reclamation['description'])); ?></p>
                </div>

                <div class="info-grid">
                    <div class="info-block">
                        <h3 class="section-title-detail">informations sur le réclamant</h3>
                        <div class="info-item">
                            <i class='bx bx-user'></i>
                            <span><?php echo htmlspecialchars($reclamation['reclamant_nom']); ?></span>
                        </div>
                        <div class="info-item">
                            <i class='bx bx-envelope'></i>
                            <span><?php echo htmlspecialchars($reclamation['reclamant_email']); ?></span>
                        </div>
                    </div>
                    
                    <div class="info-block">
                        <h3 class="section-title-detail">historique</h3>
                        <div class="history-item">
                            <span class="history-label">soumis le :</span>
                            <span class="history-value"><?php echo formatDateShort($reclamation['date_soumission']); ?></span>
                        </div>
                        <div class="history-item">
                            <span class="history-label">Dernière mise à jour :</span>
                            <span class="history-value"><?php echo formatDateShort($reclamation['date_dernier_update']); ?></span>
                        </div>
                    </div>
                </div>

                <?php

?>

<?php if (!empty($pieces_jointes)): ?>
<div class="section">
    <h3 class="section-title-detail">pièces jointes</h3>
    <div class="attachments-list">
        <?php foreach ($pieces_jointes as $pj): ?>
            <?php
            $cheminFichier = '../' . $pj['chemin_fichier'];
            
            $isImage = in_array(strtolower(pathinfo($pj['nom_original'], PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif']);
            ?>
            <div class="attachment-item">
                <?php if ($isImage): ?>
                    
                    <div class="attachment-preview">
                        <img src="<?php echo htmlspecialchars($cheminFichier); ?>" 
                             alt="<?php echo htmlspecialchars($pj['nom_original']); ?>"
                             style="max-width: 100px; max-height: 80px; border-radius: 8px; object-fit: cover;">
                    </div>
                <?php else: ?>
                    <i class='bx bx-file'></i>
                <?php endif; ?>
                
                <div class="attachment-info">
                    <a href="<?php echo htmlspecialchars($cheminFichier); ?>" 
                       target="_blank" 
                       download="<?php echo htmlspecialchars($pj['nom_original']); ?>">
                        <?php echo htmlspecialchars($pj['nom_original']); ?>
                    </a>
                    <?php if (isset($pj['taille'])): ?>
                        <span class="file-size">(<?php echo round($pj['taille'] / 1024, 1); ?> Ko)</span>
                    <?php endif; ?>
                </div>
                
                <!-- Bouton télécharger -->
                <a href="<?php echo htmlspecialchars($cheminFichier); ?>" 
                   download="<?php echo htmlspecialchars($pj['nom_original']); ?>"
                   class="btn-download"
                   title="Télécharger">
                    <i class='bx bx-download'></i>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<style>
/* CSS pour les pièces jointes */
.attachments-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.attachment-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 16px;
    background: #f8f9fa;
    border-radius: 10px;
    border: 1px solid #e9ecef;
    transition: all 0.2s ease;
}

.attachment-item:hover {
    background: #e9ecef;
    border-color: #45AECC;
}

.attachment-item i {
    font-size: 1.5rem;
    color: #45AECC;
}

.attachment-info {
    flex: 1;
}

.attachment-info a {
    color: #333;
    text-decoration: none;
    font-weight: 500;
}

.attachment-info a:hover {
    color: #45AECC;
    text-decoration: underline;
}

.file-size {
    font-size: 0.8rem;
    color: #888;
    margin-left: 8px;
}

.attachment-preview {
    width: 60px;
    height: 60px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #fff;
    border-radius: 8px;
    overflow: hidden;
    border: 1px solid #ddd;
}

.attachment-preview img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.btn-download {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    background: #45AECC;
    color: white;
    border-radius: 8px;
    text-decoration: none;
    transition: all 0.2s ease;
}

.btn-download:hover {
    background: #3a9ab8;
    transform: scale(1.05);
}

.btn-download i {
    font-size: 1.2rem;
    color: white;
}
</style>

                <!-- Gestionnaire assigné -->
                <div class="section">
                    <h3 class="section-title-detail">affecté à</h3>
                    <p class="assigned-name">
                        <?php 
                        if ($reclamation['gestionnaire_nom']) {
                            echo htmlspecialchars($reclamation['gestionnaire_nom']);
                        } else {
                            echo "Non assigné";
                        }
                        ?>
                    </p>
                </div>

                

                <!--  Prioriorité -->
                <div class="action-section">
                    <h3 class="action-title">mettre à jour la priorité</h3>
                    <form method="POST" class="action-form">
                        <input type="hidden" name="action" value="update_priority">
                        <select name="priorite" class="action-select">
                            <option value="basse" <?php echo ($reclamation['priorite'] === 'basse') ? 'selected' : ''; ?>>Basse</option>
                            <option value="moyenne" <?php echo ($reclamation['priorite'] === 'moyenne') ? 'selected' : ''; ?>>Moyenne</option>
                            <option value="haute" <?php echo ($reclamation['priorite'] === 'haute') ? 'selected' : ''; ?>>⚠️ Haute (Urgent)</option>
                        </select>
                        <button type="submit" class="btn-action">changer priorité</button>
                    </form>
                </div>

            </div>

            <div class="detail-right">
                <div class="comments-section">
                    <div class="comments-header">
                        <i class='bx bx-message-square-detail'></i>
                        <h3>Commentaires</h3>
                    </div>

                    <div class="comments-list">
                        <?php if (empty($commentaires)): ?>
                            <p class="no-comments">Pas encore de commentaires</p>
                        <?php else: ?>
                            <?php foreach ($commentaires as $comment): ?>
                                <div class="comment-item <?php echo ($comment['auteur_role'] === 'gestionnaire' || $comment['auteur_role'] === 'administrateur') ? 'comment-gestionnaire' : 'comment-reclamant'; ?>">
                                    <div class="comment-header">
                                        <span class="comment-author">
                                            <?php echo htmlspecialchars($comment['auteur_nom'] ?? 'Utilisateur'); ?>
                                            <span class="comment-role">(<?php echo htmlspecialchars($comment['auteur_role'] ?? 'inconnu'); ?>)</span>
                                        </span>
                                        <span class="comment-date"><?php echo formatDateShort($comment['date_commentaire']); ?></span>
                                    </div>
                                    <p class="comment-message"><?php echo nl2br(htmlspecialchars($comment['message'])); ?></p>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- POPUP ASSIGNATION URGENTE -->
    <div class="modal-overlay <?php echo $show_assign_modal ? 'show' : ''; ?>" id="assignModal">
        <div class="modal-content">
            <div class="modal-header">
                <i class='bx bx-error-alt'></i>
                <h3>Réclamation Urgente !</h3>
            </div>
            
            <form method="POST">
                <input type="hidden" name="action" value="assign_from_popup">
                
                <div class="modal-body">
                    <div class="urgent-warning">
                        <i class='bx bx-error-circle'></i>
                        <strong>Cette réclamation est prioritaire !</strong><br>
                        Vous devez assigner un gestionnaire immédiatement.
                    </div>
                    
                    <label>Assigner à un gestionnaire :</label>
                    <select name="gestionnaire_id" required>
                        <option value="">Choisir un gestionnaire</option>
                        <?php foreach ($gestionnaires as $gest): ?>
                            <option value="<?php echo $gest['id']; ?>">
                                <?php echo htmlspecialchars($gest['nom']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="modal-btn modal-btn-cancel" onclick="closeModal()">Annuler</button>
                    <button type="submit" class="modal-btn modal-btn-assign">Assigner maintenant</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function closeModal() {
            document.getElementById('assignModal').classList.remove('show');
        }
        
        <?php if ($show_assign_modal): ?>
        document.querySelector('#assignModal select').focus();
        <?php endif; ?>
    </script>

</body>
</html>