<?php


require_once 'db_config.php';

$pdo = getDBConnection();

$totalReclamations = 0;
$enCoursCount = 0;
$accepteCount = 0;
$fermeCount = 0;
$recentReclamations = [];
$statuts = [];

$searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';
$filterStatut = isset($_GET['statut']) ? $_GET['statut'] : '';
$sortBy = isset($_GET['sort']) ? $_GET['sort'] : 'date';
$sortOrder = isset($_GET['order']) ? $_GET['order'] : 'DESC';

$sortOrder = strtoupper($sortOrder) === 'ASC' ? 'ASC' : 'DESC';

if ($pdo) {
    try {
       
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM reclamations");
        $totalReclamations = $stmt->fetch()['total'];

        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count 
            FROM reclamations r 
            JOIN statuts s ON r.statut_id = s.id 
            WHERE s.cle = 'en_cours'
        ");
        $stmt->execute();
        $enCoursCount = $stmt->fetch()['count'];

      
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count 
            FROM reclamations r 
            JOIN statuts s ON r.statut_id = s.id 
            WHERE s.cle = 'acceptee'
        ");
        $stmt->execute();
        $accepteCount = $stmt->fetch()['count'];

        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count 
            FROM reclamations r 
            JOIN statuts s ON r.statut_id = s.id 
            WHERE s.cle = 'fermee'
        ");
        $stmt->execute();
        $fermeCount = $stmt->fetch()['count'];

        $stmt = $pdo->query("SELECT id, cle, libelle FROM statuts WHERE 1 ORDER BY id");
        $statuts = $stmt->fetchAll();

        $sql = "
            SELECT 
                r.id,
                r.objet,
                r.date_soumission,
                c.nom AS categorie_nom,
                s.libelle AS statut_libelle,
                s.cle AS statut_cle,
                u.nom AS user_nom
            FROM reclamations r
            LEFT JOIN categories c ON r.categorie_id = c.id
            LEFT JOIN statuts s ON r.statut_id = s.id
            LEFT JOIN users u ON r.user_id = u.id
            WHERE 1=1
        ";
        
        $params = [];

        
        if (!empty($searchTerm)) {
            $sql .= " AND (r.objet LIKE :search OR u.nom LIKE :search2)";
            $params[':search'] = '%' . $searchTerm . '%';
            $params[':search2'] = '%' . $searchTerm . '%';
        }

        
        if (!empty($filterStatut)) {
            $sql .= " AND s.id = :statut_id";
            $params[':statut_id'] = $filterStatut;
        }

        
        if ($sortBy === 'nom') {
            $sql .= " ORDER BY r.objet " . $sortOrder;
        } else {
            $sql .= " ORDER BY r.date_soumission " . $sortOrder;
        }

        $sql .= " LIMIT 4";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $recentReclamations = $stmt->fetchAll();

    } catch (PDOException $e) {
        error_log("Dashboard query error: " . $e->getMessage());
    }
}

function getStatusClass($statutCle) {
    $classes = [
        'en_cours'    => 'status-processing',
        'en_attente'  => 'status-pending',
        'acceptee'    => 'status-accepted',
        'rejetee'     => 'status-rejected',
        'fermee'      => 'status-closed',
    ];
    return $classes[$statutCle] ?? 'status-default';
}

function formatDate($date) {
    return date('d/m/Y', strtotime($date));
}
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
    <link rel="stylesheet" href="topbar2.css">
</head>
<body>

    <div class="topbar">
        <div class="topbar-left">
            <h2>Dashboard</h2>
        </div>
        <div class="topbar-right">
            <div class="notification-icon">
                <i class='bx bx-bell'></i>
                <span class="notification-badge"></span>
            </div>
            <div class="user-avatar">
                <i class='bx bx-user'></i>
            </div>
        </div>
    </div>

    <div class="dashboard-container">
        
        <div class="stats-grid">
            <div class="stat-card total">
                <div class="stat-header">
                    <span class="stat-label">Nombre totale</span>
                    <div class="stat-icon">
                        <i class='bx bx-file'></i>
                    </div>
                </div>
                <div class="stat-number"><?php echo htmlspecialchars($totalReclamations); ?></div>
            </div>

            <div class="stat-card femme">
                <div class="stat-header">
                    <span class="stat-label">Fermé</span>
                    <div class="stat-icon">
                        <i class='bx bx-lock-alt'></i>
                    </div>
                </div>
                <div class="stat-number"><?php echo htmlspecialchars($fermeCount); ?></div>
            </div>

            <div class="stat-card traite">
                <div class="stat-header">
                    <span class="stat-label">En traitement</span>
                    <div class="stat-icon">
                        <i class='bx bx-time-five'></i>
                    </div>
                </div>
                <div class="stat-number"><?php echo htmlspecialchars($enCoursCount); ?></div>
            </div>

            <div class="stat-card accepte">
                <div class="stat-header">
                    <span class="stat-label">Accepté</span>
                    <div class="stat-icon">
                        <i class='bx bx-check-circle'></i>
                    </div>
                </div>
                <div class="stat-number"><?php echo htmlspecialchars($accepteCount); ?></div>
            </div>
        </div>

        <div class="welcome-banner">
            <div class="banner-content">
                <h2>Bonjour!</h2>
                <p>Voulez-vous consulter vos réclamations? Cliquez ici!</p>
                <a href="reclamation.php" class="banner-btn">Gérer les réclamations</a>
            </div>
        </div>

        <div class="recent-section">
            <div class="section-header">
                <h3 class="section-title">Réclamations Récentes</h3>
            </div>

            <form method="GET" action="dashboard.php" id="filterForm">
                <div class="filters-row">
                    <div class="filter-group">
                        <label class="filter-label">Chercher</label>
                        <input 
                            type="text" 
                            name="search" 
                            class="filter-input" 
                            placeholder="Chercher..."
                            value="<?php echo htmlspecialchars($searchTerm); ?>"
                        >
                    </div>

                    <div class="filter-group">
                        <label class="filter-label">Statut</label>
                        <select name="statut" class="filter-select">
                            <option value="">Tous</option>
                            <?php foreach ($statuts as $statut): ?>
                                <option 
                                    value="<?php echo htmlspecialchars($statut['id']); ?>"
                                    <?php echo ($filterStatut == $statut['id']) ? 'selected' : ''; ?>
                                >
                                    <?php echo htmlspecialchars($statut['libelle']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label class="filter-label">Trier par</label>
                        <select name="sort" class="filter-select">
                            <option value="date" <?php echo ($sortBy === 'date') ? 'selected' : ''; ?>>Date</option>
                            <option value="nom" <?php echo ($sortBy === 'nom') ? 'selected' : ''; ?>>Nom</option>
                        </select>
                    </div>

                    
                    <input type="hidden" name="order" id="sortOrder" value="<?php echo htmlspecialchars($sortOrder); ?>">

                    <button 
                        type="button" 
                        class="filter-btn order-btn" 
                        id="orderToggle"
                        title="<?php echo ($sortOrder === 'ASC') ? 'Croissant' : 'Décroissant'; ?>"
                    >
                        <i class='bx <?php echo ($sortOrder === 'ASC') ? 'bx-sort-up' : 'bx-sort-down'; ?>'></i>
                    </button>

                    <button type="button" class="filter-btn reset-btn" id="resetBtn" title="Réinitialiser">
                        <i class='bx bx-reset'></i>
                    </button>

                    <button type="submit" class="filter-btn search-btn">
                        Chercher
                    </button>
                </div>
            </form>

            <div class="table-wrapper">
                <table class="claims-table">
                    <thead>
                        <tr>
                            <th>Nom</th>
                            <th>Catégorie</th>
                            <th>Statut</th>
                            <th>Date</th>
                            <th>Réclamant</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recentReclamations)): ?>
                            <tr>
                                <td colspan="5" style="text-align: center; padding: 20px;">
                                    Aucune réclamation trouvée
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recentReclamations as $reclamation): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($reclamation['objet']); ?></td>
                                    <td><?php echo htmlspecialchars($reclamation['categorie_nom'] ?? 'N/A'); ?></td>
                                    <td>
                                        <span class="status-badge <?php echo getStatusClass($reclamation['statut_cle']); ?>">
                                            <?php echo htmlspecialchars($reclamation['statut_libelle'] ?? 'N/A'); ?>
                                        </span>
                                    </td>
                                    <td><?php echo formatDate($reclamation['date_soumission']); ?></td>
                                    <td><?php echo htmlspecialchars($reclamation['user_nom'] ?? 'N/A'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    
    <script>
        document.getElementById('orderToggle').addEventListener('click', function() {
            const orderInput = document.getElementById('sortOrder');
            const icon = this.querySelector('i');
            
            if (orderInput.value === 'DESC') {
                orderInput.value = 'ASC';
                icon.className = 'bx bx-sort-up';
                this.title = 'Croissant';
            } else {
                orderInput.value = 'DESC';
                icon.className = 'bx bx-sort-down';
                this.title = 'Décroissant';
            }
        });
 
        document.getElementById('resetBtn').addEventListener('click', function() {
            window.location.href = 'dashboard.php';
        });
    </script>

</body>
</html>