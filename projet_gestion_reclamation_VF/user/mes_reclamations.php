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

$reclamations = [];
try {
    $stmt = $pdo->prepare("
        SELECT 
            r.id,
            r.objet,
            r.description,
            r.priorite,
            r.date_soumission,
            r.urgent,
            c.nom AS categorie_nom,
            s.libelle AS statut_libelle,
            s.cle AS statut_cle
        FROM reclamations r
        LEFT JOIN categories c ON r.categorie_id = c.id
        LEFT JOIN statuts s ON r.statut_id = s.id
        WHERE r.user_id = ?
        ORDER BY r.date_soumission DESC
    ");
    $stmt->execute([$userId]);
    $reclamations = $stmt->fetchAll();
} catch (PDOException $e) {
    $error_message = "Erreur lors de la récupération des réclamations";
}

// Récupérer les catégories pour les filtres
$categories = [];
try {
    $stmt = $pdo->query("SELECT nom FROM categories ORDER BY nom");
    $categories = $stmt->fetchAll();
} catch (PDOException $e) {
    $categories = [
        ['nom' => 'Technique'],
        ['nom' => 'Facturation'],
        ['nom' => 'Service'],
        ['nom' => 'Livraison'],
        ['nom' => 'Autre']
    ];
}
?>
<?php include 'sidebar2.php'; ?>
<?php include 'topbar2.php'; ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes réclamations - ReclaNova</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link href="https://fonts.googleapis.com/css2?family=Afacad:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="sidebar2.css">
    <link rel="stylesheet" href="topbar2.css">
    <link rel="stylesheet" href="mes_reclamation.css">
    
    <style>
      
        .action-menu {
            position: absolute;
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            padding: 8px 0;
            min-width: 180px;
            z-index: 1000;
            display: none;
        }
        
        .action-menu.show {
            display: block;
            animation: slideDown 0.2s ease;
        }
        
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .action-menu-item {
            padding: 12px 20px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: all 0.2s;
            color: #333;
            text-decoration: none;
            font-size: 14px;
        }
        
        .action-menu-item:hover {
            background: #f5f5f5;
        }
        
        .action-menu-item i {
            font-size: 18px;
        }
        
        .action-menu-item.view {
            color: #667eea;
        }
        
        .action-menu-item.edit {
            color: #ffa500;
        }
        
        .action-menu-item.delete {
            color: #dc3545;
        }
        
        .action-menu-item.delete:hover {
            background: #fff5f5;
        }
        
        .actions-btn {
            position: relative;
            background: transparent;
            border: none;
            cursor: pointer;
            padding: 8px;
            border-radius: 50%;
            transition: background 0.2s;
        }
        
        .actions-btn:hover {
            background: #f0f0f0;
        }
        
        .status-badge {
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 500;
            display: inline-block;
        }
        
        .status-en_attente { background: #ffc107; color: #000; }
        .status-en_cours { background: #17a2b8; color: white; }
        .status-acceptee { background: #28a745; color: white; }
        .status-rejetee { background: #dc3545; color: white; }
        .status-fermee { background: #6c757d; color: white; }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }
        
        .empty-state i {
            font-size: 64px;
            margin-bottom: 20px;
            display: block;
            color: #ddd;
        }
    </style>
</head>
<body>
    <button class="menu-toggle">
        <i class='bx bx-menu'></i>
    </button>

    <div class="page-header">
        <h1 class="page-title">Mes réclamations</h1>
        <a href="ajouter_reclamation.php" class="btn-create">Créer</a>
    </div>

    <?php if (isset($error_message)): ?>
    <div style="background: #f8d7da; color: #721c24; padding: 15px; margin: 20px; border-radius: 5px;">
        <strong>Erreur:</strong> <?php echo $error_message; ?>
    </div>
    <?php endif; ?>

    <!-- Filter Section -->
    <div class="filter-section">
        <div class="filter-row">
            <div class="filter-group">
                <label for="search-name">chercher</label>
                <input type="text" id="search-name" class="filter-input" placeholder="chercher">
            </div>

            <div class="filter-group">
                <label for="filter-category">catégorie</label>
                <select id="filter-category" class="filter-select">
                    <option value="">tout</option>
                    <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo htmlspecialchars($cat['nom']); ?>">
                        <?php echo htmlspecialchars($cat['nom']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="filter-group">
                <label for="filter-status">statut</label>
                <select id="filter-status" class="filter-select">
                    <option value="">tout</option>
                    <option value="en_attente">En attente</option>
                    <option value="en_cours">En cours</option>
                    <option value="acceptee">Acceptée</option>
                    <option value="rejetee">Rejetée</option>
                    <option value="fermee">Fermée</option>
                </select>
            </div>

            <button class="btn-reset" onclick="resetFilters()">
                <i class='bx bx-reset'></i>
            </button>

            <button class="btn-search" onclick="applyFilters()">chercher</button>
        </div>
    </div>

    <!-- Table Section -->
    <div class="table-container">
        <div class="table-header">
            <h3>Liste des réclamations (<?php echo count($reclamations); ?>)</h3>
        </div>

        <?php if (count($reclamations) > 0): ?>
        <table id="reclamationsTable">
            <thead>
                <tr>
                    <th>Objet</th>
                    <th>Catégorie</th>
                    <th>Statut</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($reclamations as $reclamation): ?>
                <tr data-category="<?php echo htmlspecialchars($reclamation['categorie_nom'] ?? ''); ?>" 
                    data-status="<?php echo htmlspecialchars($reclamation['statut_cle'] ?? ''); ?>"
                    data-objet="<?php echo htmlspecialchars(strtolower($reclamation['objet'])); ?>">
                    <td>
                        <?php echo htmlspecialchars($reclamation['objet']); ?>
                        <?php if ($reclamation['urgent']): ?>
                            <span style="color: red; font-weight: bold;" title="Urgent">🔥</span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo htmlspecialchars($reclamation['categorie_nom'] ?? 'N/A'); ?></td>
                    <td>
                        <span class="status-badge status-<?php echo $reclamation['statut_cle'] ?? 'en_attente'; ?>">
                            <?php echo htmlspecialchars($reclamation['statut_libelle'] ?? 'En attente'); ?>
                        </span>
                    </td>
                    <td><?php echo date('d/m/Y', strtotime($reclamation['date_soumission'])); ?></td>
                    <td>
                        <button class="actions-btn" onclick="toggleMenu(event, <?php echo $reclamation['id']; ?>)">
                            <i class='bx bx-dots-vertical-rounded'></i>
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div class="empty-state">
            <i class='bx bx-file'></i>
            <p>Aucune réclamation trouvée</p>
            <p style="color: #666; margin-top: 10px;">Créez votre première réclamation pour commencer</p>
            <a href="ajouter_reclamation.php" class="btn-create" style="margin-top: 20px; display: inline-block; padding: 12px 30px; text-decoration: none;">
                <i class='bx bx-plus'></i> Créer une réclamation
            </a>
        </div>
        <?php endif; ?>
    </div>

    <!-- Menu d'actions (caché par défaut) -->
    <div id="actionMenu" class="action-menu">
        <a href="#" class="action-menu-item view" onclick="viewReclamation(currentReclamationId); return false;">
            <i class='bx bx-show'></i>
            <span>Voir détails</span>
        </a>
        <a href="#" class="action-menu-item edit" onclick="editReclamation(currentReclamationId); return false;">
            <i class='bx bx-edit'></i>
            <span>Modifier</span>
        </a>
        <a href="#" class="action-menu-item delete" onclick="deleteReclamation(currentReclamationId); return false;">
            <i class='bx bx-trash'></i>
            <span>Supprimer</span>
        </a>
    </div>

    <script>
        let currentReclamationId = null;
        const actionMenu = document.getElementById('actionMenu');

        // Toggle menu
        document.addEventListener("DOMContentLoaded", () => {
            const toggle = document.querySelector(".menu-toggle");
            const sidebar = document.querySelector(".sidebar");
            const content = document.querySelector(".content");

            if (toggle && sidebar) {
                toggle.addEventListener("click", () => {
                    sidebar.classList.toggle("open");
                    if (content) content.classList.toggle("shift");
                });
            }

            // Fermer le menu si on clique ailleurs
            document.addEventListener('click', (e) => {
                if (!e.target.closest('.actions-btn') && !e.target.closest('.action-menu')) {
                    actionMenu.classList.remove('show');
                }
            });
        });

        // Fonction pour afficher/masquer le menu
        function toggleMenu(event, reclamationId) {
            event.stopPropagation();
            currentReclamationId = reclamationId;
            
            const button = event.currentTarget;
            const rect = button.getBoundingClientRect();
            
            actionMenu.style.top = (rect.bottom + window.scrollY) + 'px';
            actionMenu.style.left = (rect.left - 150 + window.scrollX) + 'px';
            
            actionMenu.classList.toggle('show');
        }

        // Voir les détails
        function viewReclamation(id) {
            actionMenu.classList.remove('show');
            window.location.href = 'detail_reclamation.php?id=' + id;
        }

        // Modifier
        function editReclamation(id) {
            actionMenu.classList.remove('show');
            window.location.href = 'modifier_reclamation.php?id=' + id;
        }

        // Supprimer
        function deleteReclamation(id) {
            actionMenu.classList.remove('show');
            if (confirm('Êtes-vous sûr de vouloir supprimer cette réclamation ?')) {
                window.location.href = 'supprimer_reclamation.php?id=' + id;
            }
        }

        // Filtres
        function resetFilters() {
            document.getElementById('search-name').value = '';
            document.getElementById('filter-category').value = '';
            document.getElementById('filter-status').value = '';
            applyFilters();
        }

        function applyFilters() {
            const searchValue = document.getElementById('search-name').value.toLowerCase();
            const categoryValue = document.getElementById('filter-category').value;
            const statusValue = document.getElementById('filter-status').value;
            
            const table = document.getElementById('reclamationsTable');
            if (!table) return;
            
            const rows = table.querySelectorAll('tbody tr');
            
            rows.forEach(row => {
                const objet = row.getAttribute('data-objet') || '';
                const category = row.getAttribute('data-category') || '';
                const status = row.getAttribute('data-status') || '';
                
                const matchSearch = !searchValue || objet.includes(searchValue);
                const matchCategory = !categoryValue || category === categoryValue;
                const matchStatus = !statusValue || status === statusValue;
                
                if (matchSearch && matchCategory && matchStatus) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        // Recherche en temps réel
        const searchInput = document.getElementById('search-name');
        if (searchInput) {
            searchInput.addEventListener('input', applyFilters);
        }
    </script>
</body>
</html>