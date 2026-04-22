<?php
session_start();
require_once 'db_config.php';

header('Content-Type: application/json');


$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['email'])) {
    echo json_encode(['success' => false, 'message' => 'Email non fourni']);
    exit();
}

$email = trim($input['email']);

// Validation
if (empty($email)) {
    echo json_encode(['success' => false, 'message' => 'Veuillez entrer votre email']);
    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Email invalide']);
    exit();
}

try {
  
    $stmt = $pdo->prepare("SELECT id, nom FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'Aucun compte associé à cet email']);
        exit();
    }
    
  
    $_SESSION['reset_email'] = $email;
    $_SESSION['reset_time'] = time();
    
    echo json_encode([
        'success' => true, 
        'message' => 'Email vérifié! Redirection vers la réinitialisation...'
    ]);
    
} catch (PDOException $e) {
    error_log("Email verification error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur de connexion à la base de données']);
}
?>