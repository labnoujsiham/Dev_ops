<?php


if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Non authentifié']);
    exit;
}

$userId = $_SESSION['user_id'];


try {
    $dsn = "mysql:host=localhost;dbname=gestion_reclamations;charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    $pdo = new PDO($dsn, 'root', '', $options);
} catch (PDOException $e) {
    error_log("Erreur connexion DB get_notifications USER: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Erreur de connexion à la base de données'
    ]);
    exit;
}

try {
    // Récupérer le nombre de notifications non lues
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count_non_lues 
        FROM notifications 
        WHERE user_id = ? AND lu = 0
    ");
    $stmt->execute([$userId]);
    $count = $stmt->fetch()['count_non_lues'];
    
    // Récupérer les notifications récentes (20 dernières)
    $stmt = $pdo->prepare("
        SELECT 
            n.*,
            r.objet AS reclamation_objet
        FROM notifications n
        LEFT JOIN reclamations r ON (n.reference_table = 'reclamations' AND n.reference_id = r.id)
        WHERE n.user_id = ?
        ORDER BY n.date_creation DESC
        LIMIT 20
    ");
    $stmt->execute([$userId]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'count_non_lues' => $count,
        'notifications' => $notifications
    ]);
    
} catch (PDOException $e) {
    error_log("Erreur get_notifications USER: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Erreur serveur'
    ]);
}