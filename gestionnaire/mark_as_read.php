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
    error_log("Erreur connexion DB mark_as_read: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Erreur de connexion à la base de données'
    ]);
    exit;
}


$input = json_decode(file_get_contents('php://input'), true);

try {
    
    if (isset($input['mark_all']) && $input['mark_all'] === true) {
        $stmt = $pdo->prepare("
            UPDATE notifications 
            SET lu = 1 
            WHERE user_id = ? AND lu = 0
        ");
        $stmt->execute([$userId]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Toutes les notifications marquées comme lues'
        ]);
        exit;
    }
    
    
    if (isset($input['notification_id'])) {
        $notificationId = intval($input['notification_id']);
        
        $stmt = $pdo->prepare("
            UPDATE notifications 
            SET lu = 1 
            WHERE id = ? AND user_id = ?
        ");
        $stmt->execute([$notificationId, $userId]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Notification marquée comme lue'
        ]);
        exit;
    }
    
    echo json_encode([
        'success' => false,
        'message' => 'Paramètres manquants'
    ]);
    
} catch (PDOException $e) {
    error_log("Erreur mark_as_read gestionnaire: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Erreur serveur'
    ]);
}