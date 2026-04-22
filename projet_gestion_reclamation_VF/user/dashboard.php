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

try {
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM reclamations WHERE user_id = ?");
    $stmt->execute([$userId]);
    $total_reclamations = $stmt->fetch()['total'];
    
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as fermees 
        FROM reclamations r 
        JOIN statuts s ON r.statut_id = s.id 
        WHERE r.user_id = ? AND s.cle = 'fermee'
    ");
    $stmt->execute([$userId]);
    $reclamations_fermees = $stmt->fetch()['fermees'];
    
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as en_traitement 
        FROM reclamations r 
        JOIN statuts s ON r.statut_id = s.id 
        WHERE r.user_id = ? AND s.cle = 'en_cours'
    ");
    $stmt->execute([$userId]);
    $reclamations_traitement = $stmt->fetch()['en_traitement'];
    
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as acceptees 
        FROM reclamations r 
        JOIN statuts s ON r.statut_id = s.id 
        WHERE r.user_id = ? AND s.cle = 'acceptee'
    ");
    $stmt->execute([$userId]);
    $reclamations_acceptees = $stmt->fetch()['acceptees'];
    
    // Réclamations en attente 
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as en_attente 
        FROM reclamations r 
        JOIN statuts s ON r.statut_id = s.id 
        WHERE r.user_id = ? AND s.cle = 'en_attente'
    ");
    $stmt->execute([$userId]);
    $reclamations_attente = $stmt->fetch()['en_attente'];
    
    // Réclamations récentes 
    $stmt = $pdo->prepare("
        SELECT 
            r.id,
            r.objet,
            r.date_soumission,
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
    $reclamations_recentes = $stmt->fetchAll();
    
    // Catégories pour le filtre
    $stmt = $pdo->query("SELECT DISTINCT nom FROM categories ORDER BY nom");
    $categories = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $total_reclamations = 0;
    $reclamations_fermees = 0;
    $reclamations_traitement = 0;
    $reclamations_acceptees = 0;
    $reclamations_attente = 0;
    $reclamations_recentes = [];
    $categories = [];
}

$page_title = 'Dashboard';

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
    <title>Dashboard - ReclaNova</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link href="https://fonts.googleapis.com/css2?family=Afacad:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="sidebar2.css">
    <link rel="stylesheet" href="dashboard.css">
</head>
<body>

    <div class="dashboard-container">
        
        <div class="stats-grid">
            <div class="stat-card total">
                <div class="stat-header">
                    <span class="stat-label">Nombre total</span>
                    <div class="stat-icon">
                        <i class='bx bx-file'></i>
                    </div>
                </div>
                <div class="stat-number" data-target="<?php echo $total_reclamations; ?>">0</div>
            </div>

            <div class="stat-card femme">
                <div class="stat-header">
                    <span class="stat-label">Fermé</span>
                    <div class="stat-icon">
                        <i class='bx bx-lock-alt'></i>
                    </div>
                </div>
                <div class="stat-number" data-target="<?php echo $reclamations_fermees; ?>">0</div>
            </div>

            <div class="stat-card traite">
                <div class="stat-header">
                    <span class="stat-label">En traitement</span>
                    <div class="stat-icon">
                        <i class='bx bx-time-five'></i>
                    </div>
                </div>
                <div class="stat-number" data-target="<?php echo $reclamations_traitement; ?>">0</div>
            </div>

            <div class="stat-card accepte">
                <div class="stat-header">
                    <span class="stat-label">Accepté</span>
                    <div class="stat-icon">
                        <i class='bx bx-check-circle'></i>
                    </div>
                </div>
                <div class="stat-number" data-target="<?php echo $reclamations_acceptees; ?>">0</div>
            </div>
        </div>

        <div class="welcome-banner">
            <div class="banner-content">
                <h2>Bonjour <?php echo htmlspecialchars($userName); ?> !</h2>
                <p>Avez-vous un problème? n'hésitez pas de le réclamer!</p>
                <a href="ajouter_reclamation.php" class="banner-btn">Ajouter une réclamation</a>
            </div>
        </div>

        <div class="recent-section">
            <div class="section-header">
                <h3 class="section-title">Réclamations Récentes</h3>
            </div>

            <div class="filters-row">
                <div class="filter-group">
                    <label class="filter-label">chercher</label>
                    <input type="text" id="searchInput" class="filter-input" placeholder="chercher...">
                </div>

                <div class="filter-group">
                    <label class="filter-label">catégorie</label>
                    <select id="categoryFilter" class="filter-select">
                        <option value="">Tous</option>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo htmlspecialchars($cat['nom']); ?>">
                            <?php echo htmlspecialchars($cat['nom']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="filter-group">
                    <label class="filter-label">trier par</label>
                    <select id="sortFilter" class="filter-select">
                        <option value="date-desc">Date (plus récent)</option>
                        <option value="date-asc">Date (plus ancien)</option>
                        <option value="status">Statut</option>
                        <option value="category">Catégorie</option>
                    </select>
                </div>

                <button class="filter-btn reset-btn" onclick="resetFilters()" title="Réinitialiser">
                    <i class='bx bx-reset'></i>
                </button>

                <button class="filter-btn search-btn" onclick="applyFilters()">
                    chercher
                </button>
            </div>

            <!-- Table -->
            <?php if (count($reclamations_recentes) > 0): ?>
            <div class="table-wrapper">
                <table class="claims-table">
                    <thead>
                        <tr>
                            <th>Nom</th>
                            <th>Catégorie</th>
                            <th>Statut</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody id="tableBody">
                        <?php foreach ($reclamations_recentes as $reclam): ?>
                        <tr class="table-row clickable-row" 
                            data-id="<?php echo $reclam['id']; ?>"
                            data-name="<?php echo htmlspecialchars(strtolower($reclam['objet'])); ?>"
                            data-category="<?php echo htmlspecialchars($reclam['categorie_nom'] ?? ''); ?>"
                            data-status="<?php echo htmlspecialchars($reclam['statut_cle'] ?? ''); ?>"
                            data-date="<?php echo strtotime($reclam['date_soumission']); ?>"
                            onclick="goToDetail(<?php echo $reclam['id']; ?>)">
                            <td><?php echo htmlspecialchars($reclam['objet']); ?></td>
                            <td><?php echo htmlspecialchars($reclam['categorie_nom'] ?? 'N/A'); ?></td>
                            <td>
                                <?php 
                                $statusClass = 'status-pending';
                                switch($reclam['statut_cle']) {
                                    case 'en_cours':
                                        $statusClass = 'status-processing';
                                        break;
                                    case 'acceptee':
                                        $statusClass = 'status-accepted';
                                        break;
                                    case 'rejetee':
                                        $statusClass = 'status-rejected';
                                        break;
                                    case 'fermee':
                                        $statusClass = 'status-closed';
                                        break;
                                }
                                ?>
                                <span class="status-badge <?php echo $statusClass; ?>">
                                    <?php echo htmlspecialchars($reclam['statut_libelle'] ?? 'En attente'); ?>
                                </span>
                            </td>
                            <td><?php echo date('d/m/Y', strtotime($reclam['date_soumission'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="empty-state">
                <i class='bx bx-file'></i>
                <p>Aucune réclamation trouvée</p>
                <p style="color: #666; margin-top: 10px;">Créez votre première réclamation pour commencer</p>
                <a href="ajouter_reclamation.php" class="banner-btn" style="margin-top: 20px; display: inline-block;">
                    Ajouter une réclamation
                </a>
            </div>
            <?php endif; ?>
        </div>

    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const counters = document.querySelectorAll('.stat-number');
            counters.forEach(counter => {
                const target = parseInt(counter.getAttribute('data-target'));
                let count = 0;
                const increment = target / 50; 
                
                const updateCounter = () => {
                    if (count < target) {
                        count += increment;
                        counter.textContent = Math.floor(count);
                        setTimeout(updateCounter, 20);
                    } else {
                        counter.textContent = target;
                    }
                };
                
                updateCounter();
            });
        });

        function goToDetail(id) {
            window.location.href = 'detail_reclamation.php?id=' + id;
        }

        function applyFilters() {
            const searchValue = document.getElementById('searchInput').value.toLowerCase();
            const categoryValue = document.getElementById('categoryFilter').value;
            const sortValue = document.getElementById('sortFilter').value;
            
            const tbody = document.getElementById('tableBody');
            if (!tbody) return;
            
            let rows = Array.from(tbody.querySelectorAll('.table-row'));
            
            rows.forEach(row => {
                const name = row.getAttribute('data-name');
                const category = row.getAttribute('data-category');
                
                const matchSearch = !searchValue || name.includes(searchValue);
                const matchCategory = !categoryValue || category === categoryValue;
                
                if (matchSearch && matchCategory) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
            
            const visibleRows = rows.filter(row => row.style.display !== 'none');
            
            visibleRows.sort((a, b) => {
                switch(sortValue) {
                    case 'date-desc':
                        return parseInt(b.getAttribute('data-date')) - parseInt(a.getAttribute('data-date'));
                    case 'date-asc':
                        return parseInt(a.getAttribute('data-date')) - parseInt(b.getAttribute('data-date'));
                    case 'status':
                        return a.getAttribute('data-status').localeCompare(b.getAttribute('data-status'));
                    case 'category':
                        return a.getAttribute('data-category').localeCompare(b.getAttribute('data-category'));
                    default:
                        return 0;
                }
            });
            
            visibleRows.forEach(row => tbody.appendChild(row));
        }

        function resetFilters() {
            document.getElementById('searchInput').value = '';
            document.getElementById('categoryFilter').value = '';
            document.getElementById('sortFilter').value = 'date-desc';
            applyFilters();
        }

        document.getElementById('searchInput')?.addEventListener('input', applyFilters);
        document.getElementById('categoryFilter')?.addEventListener('change', applyFilters);
        document.getElementById('sortFilter')?.addEventListener('change', applyFilters);

        document.querySelectorAll('.clickable-row').forEach(row => {
            row.style.cursor = 'pointer';
        });
    </script>

</body>
</html>