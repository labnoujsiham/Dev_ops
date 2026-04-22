<?php
session_start();
require_once 'db_config.php';

header('Content-Type: application/json');


if (!isset($_SESSION['reset_email'])) {
    echo json_encode(['success' => false, 'message' => 'Session expirée. Veuillez recommencer']);
    exit();
}


$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['password'])) {
    echo json_encode(['success' => false, 'message' => 'Mot de passe non fourni']);
    exit();
}

$password = $input['password'];
$email = $_SESSION['reset_email'];

// Validation
if (empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Veuillez entrer un mot de passe']);
    exit();
}

if (strlen($password) < 6) {
    echo json_encode(['success' => false, 'message' => 'Le mot de passe doit contenir au moins 6 caractères']);
    exit();
}

try {
   //hachage de mdp
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // Enregistrer le nouveau mot de passe
    $stmt = $pdo->prepare("UPDATE users SET mot_de_passe = ? WHERE email = ?");
    $stmt->execute([$hashedPassword, $email]);
    
    if ($stmt->rowCount() > 0) {
       
        unset($_SESSION['reset_email']);
        unset($_SESSION['reset_time']);
        
        echo json_encode([
            'success' => true, 
            'message' => 'Mot de passe réinitialisé avec succès! Redirection vers la connexion...'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erreur lors de la mise à jour du mot de passe']);
    }
    
} catch (PDOException $e) {
    error_log("Password update error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur de connexion à la base de données']);
}
?>