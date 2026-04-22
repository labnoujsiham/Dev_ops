<?php


session_start();
require_once 'db_config.php';

$page_title = "Dashboard Admin";

$pdo = getDBConnection();

$totalReclamations = 0;
$enCoursCount = 0;
$accepteCount = 0;
$fermeCount = 0;
$enAttenteCount = 0;
$rejeteeCount = 0;

$categoriesData = [];
$statutsData = [];
$prioritesData = [];
$monthlyData = [];

if ($pdo) {
    try {
        
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM reclamations");
        $totalReclamations = $stmt->fetch()['total'];

        $stmt = $pdo->query("
            SELECT s.cle, COUNT(r.id) as count 
            FROM statuts s 
            LEFT JOIN reclamations r ON r.statut_id = s.id 
            GROUP BY s.id, s.cle
        ");
        while ($row = $stmt->fetch()) {
            switch ($row['cle']) {
                case 'en_cours': $enCoursCount = $row['count']; break;
                case 'acceptee': $accepteCount = $row['count']; break;
                case 'fermee': $fermeCount = $row['count']; break;
                case 'en_attente': $enAttenteCount = $row['count']; break;
                case 'rejetee': $rejeteeCount = $row['count']; break;
            }
        }

        
        // 1. Reclamations par catégories (dognut)
        $stmt = $pdo->query("
            SELECT c.nom as category, COUNT(r.id) as count 
            FROM categories c 
            LEFT JOIN reclamations r ON r.categorie_id = c.id 
            GROUP BY c.id, c.nom 
            ORDER BY count DESC
        ");
        $categoriesData = $stmt->fetchAll();

        // 2. Reclamations par status bar
        $stmt = $pdo->query("
            SELECT s.libelle as status, s.cle, COUNT(r.id) as count 
            FROM statuts s 
            LEFT JOIN reclamations r ON r.statut_id = s.id 
            GROUP BY s.id, s.libelle, s.cle 
            ORDER BY s.id
        ");
        $statutsData = $stmt->fetchAll();

        // 3. Reclamations par priorité bar horizontal
        $stmt = $pdo->query("
            SELECT priorite, COUNT(*) as count 
            FROM reclamations 
            GROUP BY priorite 
            ORDER BY FIELD(priorite, 'haute', 'moyenne', 'basse')
        ");
        $prioritesData = $stmt->fetchAll();

        // 4. Réclamation par mois (6 dernier mois)
        $stmt = $pdo->query("
            SELECT 
                DATE_FORMAT(date_soumission, '%Y-%m') as month,
                DATE_FORMAT(date_soumission, '%M %Y') as month_label,
                COUNT(*) as count 
            FROM reclamations 
            WHERE date_soumission >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
            GROUP BY month, month_label
            ORDER BY month ASC
        ");
        $monthlyData = $stmt->fetchAll();

        // 5. Total users
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM users");
        $totalUsers = $stmt->fetch()['total'];

        // 6. Total gestionnaires
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE role = 'gestionnaire'");
        $totalGestionnaires = $stmt->fetch()['total'];

    } catch (PDOException $e) {
        error_log("Admin Dashboard error: " . $e->getMessage());
    }
}

// Prepare les données pour js (convertir PHP arrays en JSON)
$categoriesLabels = json_encode(array_column($categoriesData, 'category'));
$categoriesValues = json_encode(array_map('intval', array_column($categoriesData, 'count')));

$statutsLabels = json_encode(array_column($statutsData, 'status'));
$statutsValues = json_encode(array_map('intval', array_column($statutsData, 'count')));

$prioritesLabels = json_encode(array_map('ucfirst', array_column($prioritesData, 'priorite')));
$prioritesValues = json_encode(array_map('intval', array_column($prioritesData, 'count')));

$monthlyLabels = json_encode(array_column($monthlyData, 'month_label'));
$monthlyValues = json_encode(array_map('intval', array_column($monthlyData, 'count')));
?>
<?php include 'sidebar2.php'; ?>
<?php include 'topbar2.php'; ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - ReclaNova</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link href="https://fonts.googleapis.com/css2?family=Afacad:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="sidebar2.css">
    <link rel="stylesheet" href="topbar2.css">
    <link rel="stylesheet" href="dashboard.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

    <div class="dashboard-container">
        
        <div class="stats-grid">
            <div class="stat-card total">
                <div class="stat-header">
                    <span class="stat-label">Total Réclamations</span>
                    <div class="stat-icon">
                        <i class='bx bx-file'></i>
                    </div>
                </div>
                <div class="stat-number"><?php echo $totalReclamations; ?></div>
            </div>

            <div class="stat-card ferme">
                <div class="stat-header">
                    <span class="stat-label">Fermées</span>
                    <div class="stat-icon">
                        <i class='bx bx-lock-alt'></i>
                    </div>
                </div>
                <div class="stat-number"><?php echo $fermeCount; ?></div>
            </div>

            <div class="stat-card traite">
                <div class="stat-header">
                    <span class="stat-label">En traitement</span>
                    <div class="stat-icon">
                        <i class='bx bx-time-five'></i>
                    </div>
                </div>
                <div class="stat-number"><?php echo $enCoursCount; ?></div>
            </div>

            <div class="stat-card accepte">
                <div class="stat-header">
                    <span class="stat-label">Acceptées</span>
                    <div class="stat-icon">
                        <i class='bx bx-check-circle'></i>
                    </div>
                </div>
                <div class="stat-number"><?php echo $accepteCount; ?></div>
            </div>
        </div>

        <div class="charts-row">
            <div class="chart-card">
                <div class="chart-header">
                    <h3><i class='bx bx-category'></i> Par Catégorie</h3>
                </div>
                <div class="chart-body">
                    <canvas id="categoryChart"></canvas>
                </div>
            </div>

            <div class="chart-card">
                <div class="chart-header">
                    <h3><i class='bx bx-stats'></i> Par Statut</h3>
                </div>
                <div class="chart-body">
                    <canvas id="statusChart"></canvas>
                </div>
            </div>
        </div>

        <div class="charts-row">
            
            <div class="chart-card large">
                <div class="chart-header">
                    <h3><i class='bx bx-line-chart'></i> Évolution Mensuelle</h3>
                </div>
                <div class="chart-body">
                    <canvas id="monthlyChart"></canvas>
                </div>
            </div>

            <div class="chart-card">
                <div class="chart-header">
                    <h3><i class='bx bx-flag'></i> Par Priorité</h3>
                </div>
                <div class="chart-body">
                    <canvas id="priorityChart"></canvas>
                </div>
            </div>
        </div>

        <div class="quick-stats-row">
            <div class="quick-stat-card">
                <div class="quick-stat-icon users">
                    <i class='bx bx-group'></i>
                </div>
                <div class="quick-stat-info">
                    <span class="quick-stat-number"><?php echo $totalUsers ?? 0; ?></span>
                    <span class="quick-stat-label">Utilisateurs</span>
                </div>
            </div>

            <div class="quick-stat-card">
                <div class="quick-stat-icon gestionnaires">
                    <i class='bx bx-user-check'></i>
                </div>
                <div class="quick-stat-info">
                    <span class="quick-stat-number"><?php echo $totalGestionnaires ?? 0; ?></span>
                    <span class="quick-stat-label">Gestionnaires</span>
                </div>
            </div>

            <div class="quick-stat-card">
                <div class="quick-stat-icon pending">
                    <i class='bx bx-hourglass'></i>
                </div>
                <div class="quick-stat-info">
                    <span class="quick-stat-number"><?php echo $enAttenteCount; ?></span>
                    <span class="quick-stat-label">En attente</span>
                </div>
            </div>

            <div class="quick-stat-card">
                <div class="quick-stat-icon rejected">
                    <i class='bx bx-x-circle'></i>
                </div>
                <div class="quick-stat-info">
                    <span class="quick-stat-number"><?php echo $rejeteeCount; ?></span>
                    <span class="quick-stat-label">Rejetées</span>
                </div>
            </div>
        </div>

    </div>

    <script>
        
        const colors = {
            teal: '#45AECC',
            purple: '#614AA9',
            orange: '#f39c12',
            green: '#27ae60',
            red: '#e74c3c',
            blue: '#3498db',
            pink: '#e91e63',
            grey: '#95a5a6'
        };

        const colorArray = [colors.teal, colors.purple, colors.orange, colors.green, colors.red, colors.blue, colors.pink];

        // ===== 1. DOUGHNUT  =====
        new Chart(document.getElementById('categoryChart'), {
            type: 'doughnut',
            data: {
                labels: <?php echo $categoriesLabels; ?>,
                datasets: [{
                    data: <?php echo $categoriesValues; ?>,
                    backgroundColor: colorArray,
                    borderWidth: 0,
                    hoverOffset: 10
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            usePointStyle: true,
                            font: { family: 'Afacad', size: 13 }
                        }
                    }
                },
                cutout: '65%'
            }
        });

        // ===== 2. BAR : Status =====
        new Chart(document.getElementById('statusChart'), {
            type: 'bar',
            data: {
                labels: <?php echo $statutsLabels; ?>,
                datasets: [{
                    label: 'Réclamations',
                    data: <?php echo $statutsValues; ?>,
                    backgroundColor: [colors.orange, colors.teal, colors.green, colors.red, colors.grey, colors.purple],
                    borderRadius: 8,
                    borderSkipped: false
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { 
                            stepSize: 1,
                            font: { family: 'Afacad' }
                        },
                        grid: { color: '#f0f0f0' }
                    },
                    x: {
                        ticks: { font: { family: 'Afacad', size: 11 } },
                        grid: { display: false }
                    }
                }
            }
        });

        // ===== 3. LINE: Month  =====
        new Chart(document.getElementById('monthlyChart'), {
            type: 'line',
            data: {
                labels: <?php echo $monthlyLabels; ?>,
                datasets: [{
                    label: 'Réclamations',
                    data: <?php echo $monthlyValues; ?>,
                    borderColor: colors.teal,
                    backgroundColor: 'rgba(69, 174, 204, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: colors.teal,
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 6,
                    pointHoverRadius: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { 
                            stepSize: 1,
                            font: { family: 'Afacad' }
                        },
                        grid: { color: '#f0f0f0' }
                    },
                    x: {
                        ticks: { font: { family: 'Afacad', size: 12 } },
                        grid: { display: false }
                    }
                }
            }
        });

        // ===== 4. HORIZONTAL BAR : Priorité =====
        new Chart(document.getElementById('priorityChart'), {
            type: 'bar',
            data: {
                labels: <?php echo $prioritesLabels; ?>,
                datasets: [{
                    label: 'Réclamations',
                    data: <?php echo $prioritesValues; ?>,
                    backgroundColor: [colors.red, colors.orange, colors.green],
                    borderRadius: 8,
                    borderSkipped: false
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        ticks: { 
                            stepSize: 1,
                            font: { family: 'Afacad' }
                        },
                        grid: { color: '#f0f0f0' }
                    },
                    y: {
                        ticks: { font: { family: 'Afacad', size: 13 } },
                        grid: { display: false }
                    }
                }
            }
        });
    </script>

</body>
</html>