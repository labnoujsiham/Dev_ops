<?php

session_start();
require_once 'db_config.php';

$page_title = "Gestion des réclamations";

$pdo = getDBConnection();

$reclamations = [];
$statuts = [];
$categories = [];

$searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';
$filterStatut = isset($_GET['statut']) ? $_GET['statut'] : '';
$filterCategorie = isset($_GET['categorie']) ? $_GET['categorie'] : '';
$sortBy = isset($_GET['sort']) ? $_GET['sort'] : 'date';
$sortOrder = isset($_GET['order']) ? $_GET['order'] : 'DESC';

$sortOrder = strtoupper($sortOrder) === 'ASC' ? 'ASC' : 'DESC';

if ($pdo) {
    try {
        
        $stmt = $pdo->query("SELECT id, cle, libelle FROM statuts ORDER BY id");
        $statuts = $stmt->fetchAll();

        $stmt = $pdo->query("SELECT id, nom FROM categories ORDER BY nom");
        $categories = $stmt->fetchAll();

        $sql = "
            SELECT 
                r.id,
                r.objet,
                r.description,
                r.priorite,
                r.date_soumission,
                r.date_dernier_update,
                c.nom AS categorie_nom,
                s.libelle AS statut_libelle,
                s.cle AS statut_cle,
                u.nom AS reclamant_nom,
                u.email AS reclamant_email,
                g.nom AS gestionnaire_nom
            FROM reclamations r
            LEFT JOIN categories c ON r.categorie_id = c.id
            LEFT JOIN statuts s ON r.statut_id = s.id
            LEFT JOIN users u ON r.user_id = u.id
            LEFT JOIN users g ON r.gestionnaire_id = g.id
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

        if (!empty($filterCategorie)) {
            $sql .= " AND c.id = :categorie_id";
            $params[':categorie_id'] = $filterCategorie;
        }

        switch ($sortBy) {
            case 'nom':
                $sql .= " ORDER BY r.objet " . $sortOrder;
                break;
            case 'priorite':
                $sql .= " ORDER BY FIELD(r.priorite, 'haute', 'moyenne', 'basse') " . $sortOrder;
                break;
            case 'statut':
                $sql .= " ORDER BY s.libelle " . $sortOrder;
                break;
            default:
                $sql .= " ORDER BY r.date_soumission " . $sortOrder;
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $reclamations = $stmt->fetchAll();

    } catch (PDOException $e) {
        error_log("Gestion reclamations error: " . $e->getMessage());
    }
}

function getStatusClass($statutCle) {
    $classes = [
        'en_cours'              => 'status-processing',
        'en_attente'            => 'status-pending',
        'acceptee'              => 'status-accepted',
        'rejetee'               => 'status-rejected',
        'fermee'                => 'status-closed',
        'attente_info_reclamant'=> 'status-info',
    ];
    return $classes[$statutCle] ?? 'status-default';
}

function getPriorityClass($priorite) {
    $classes = [
        'haute'   => 'priority-high',
        'moyenne' => 'priority-medium',
        'basse'   => 'priority-low',
    ];
    return $classes[$priorite] ?? 'priority-default';
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
    <title>Gestion des Réclamations - ReclaNova</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link href="https://fonts.googleapis.com/css2?family=Afacad:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="sidebar2.css">
    <link rel="stylesheet" href="topbar2.css">
    <link rel="stylesheet" href="reclamation.css">
</head>
<body>

    <div class="main-container">
        <h1 class="page-title">Gestion des réclamations</h1>

        <form method="GET" action="reclamation.php" id="filterForm">
            <div class="filters-container">
                <div class="filters-row">
                    <div class="filter-group">
                        <label class="filter-label">chercher</label>
                        <input 
                            type="text" 
                            name="search" 
                            class="filter-input" 
                            placeholder="chercher..."
                            value="<?php echo htmlspecialchars($searchTerm); ?>"
                        >
                    </div>

                    <div class="filter-group">
                        <label class="filter-label">statue</label>
                        <select name="statut" class="filter-select">
                            <option value="">tout</option>
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
                        <label class="filter-label">trier par</label>
                        <select name="sort" class="filter-select">
                            <option value="date" <?php echo ($sortBy === 'date') ? 'selected' : ''; ?>>tout</option>
                            <option value="nom" <?php echo ($sortBy === 'nom') ? 'selected' : ''; ?>>nom</option>
                            <option value="priorite" <?php echo ($sortBy === 'priorite') ? 'selected' : ''; ?>>priorité</option>
                            <option value="statut" <?php echo ($sortBy === 'statut') ? 'selected' : ''; ?>>statut</option>
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
                        chercher
                    </button>
                </div>
            </div>
        </form>

        <div class="table-container">
            <div class="table-header">
                <h3>Liste des réclamations</h3>
            </div>
            
            <div class="table-wrapper">
                <table class="reclamations-table">
                    <thead>
                        <tr>
                            <th>Nom</th>
                            <th>réclamant</th>
                            <th>statue</th>
                            <th>catégorie</th>
                            <th>priorité</th>
                            <th>Date</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($reclamations)): ?>
                            <tr>
                                <td colspan="7" class="empty-message">
                                    Aucune réclamation trouvée
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($reclamations as $reclamation): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($reclamation['objet']); ?></td>
                                    <td><?php echo htmlspecialchars($reclamation['reclamant_nom'] ?? 'N/A'); ?></td>
                                    <td>
                                        <span class="status-badge <?php echo getStatusClass($reclamation['statut_cle']); ?>">
                                            <?php echo htmlspecialchars($reclamation['statut_libelle']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($reclamation['categorie_nom'] ?? 'N/A'); ?></td>
                                    <td>
                                        <span class="priority-badge <?php echo getPriorityClass($reclamation['priorite']); ?>">
                                            <?php echo htmlspecialchars(ucfirst($reclamation['priorite'])); ?>
                                        </span>
                                    </td>
                                    <td><?php echo formatDate($reclamation['date_soumission']); ?></td>
                                    <td>
                                        <a href="detail_reclamation.php?id=<?php echo $reclamation['id']; ?>" class="action-btn" title="Voir détails">
                                            <i class='bx bx-dots-horizontal-rounded'></i>
                                        </a>
                                    </td>
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
            window.location.href = 'reclamation.php';
        });
    </script>

</body>
</html>