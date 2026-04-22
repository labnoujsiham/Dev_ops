<?php

error_reporting(E_ALL);
ini_set('display_errors', 0); 
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');

session_start();


header('Content-Type: application/json');


try {
    require_once 'db_config.php';
} catch (Exception $e) {
    error_log("Failed to load db_config.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur de configuration. Contactez l\'administrateur.']);
    exit();
}


$rawInput = file_get_contents('php://input');
error_log("Received input: " . $rawInput);

$input = json_decode($rawInput, true);

if (!$input || !isset($input['action'])) {
    error_log("Invalid input or missing action");
    echo json_encode(['success' => false, 'message' => 'Action non spécifiée']);
    exit();
}

$action = $input['action'];
error_log("Action: " . $action);

// ==================== LOGIN ====================
if ($action === 'login') {
    $identifier = trim($input['identifier'] ?? '');
    $password = $input['password'] ?? '';
    
    error_log("Login attempt for identifier: " . $identifier);
    
    if (empty($identifier) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Veuillez remplir tous les champs']);
        exit();
    }
    
    try {
       
        $stmt = $pdo->prepare("
            SELECT id, nom, email, role, mot_de_passe 
            FROM users 
            WHERE email = ? OR nom = ?
            LIMIT 1
        ");
        $stmt->execute([$identifier, $identifier]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            error_log("User not found: " . $identifier);
            echo json_encode(['success' => false, 'message' => 'Identifiants incorrects']);
            exit();
        }
        
        error_log("User found: " . $user['email']);
        
       
        if (!password_verify($password, $user['mot_de_passe'])) {
            error_log("Password verification failed for user: " . $user['email']);
            echo json_encode(['success' => false, 'message' => 'Identifiants incorrects']);
            exit();
        }
        
        error_log("Password verified successfully");
        
       
        $updateStmt = $pdo->prepare("UPDATE users SET dernier_login = NOW() WHERE id = ?");
        $updateStmt->execute([$user['id']]);
        
       
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['nom'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['last_activity'] = time();
        
        error_log("Session set for user: " . $user['id']);
        
        
        $redirect = '../user/dashboard.php'; 
        if ($user['role'] === 'administrateur') {
            $redirect = '../admin/dashboard.php';
        } elseif ($user['role'] === 'gestionnaire') {
            $redirect = '../gestionnaire/dashboard.php';
        }
        
        echo json_encode([
            'success' => true, 
            'message' => 'Connexion réussie! Redirection...',
            'redirect' => $redirect,
            'user' => [
                'name' => $user['nom'],
                'role' => $user['role']
            ]
        ]);
        
    } catch (PDOException $e) {
        error_log("Login error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Erreur de connexion à la base de données. Veuillez réessayer.']);
    }
}

// ==================== REGISTER ====================
elseif ($action === 'register') {
    $username = trim($input['username'] ?? '');
    $email = trim($input['email'] ?? '');
    $password = $input['password'] ?? '';
    
    error_log("Registration attempt for: " . $email);
    
    // Validation
    if (empty($username) || empty($email) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Veuillez remplir tous les champs']);
        exit();
    }
    
    if (strlen($username) < 3) {
        echo json_encode(['success' => false, 'message' => 'Le nom doit contenir au moins 3 caractères']);
        exit();
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Email invalide']);
        exit();
    }
    
    if (strlen($password) < 6) {
        echo json_encode(['success' => false, 'message' => 'Le mot de passe doit contenir au moins 6 caractères']);
        exit();
    }
    
    try {
       
        $checkStmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $checkStmt->execute([$email]);
        
        if ($checkStmt->fetch()) {
            error_log("Email already exists: " . $email);
            echo json_encode(['success' => false, 'message' => 'Cet email est déjà utilisé']);
            exit();
        }
        
        
        $checkStmt = $pdo->prepare("SELECT id FROM users WHERE nom = ?");
        $checkStmt->execute([$username]);
        
        if ($checkStmt->fetch()) {
            error_log("Username already exists: " . $username);
            echo json_encode(['success' => false, 'message' => 'Ce nom d\'utilisateur est déjà pris']);
            exit();
        }
        
       
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        $insertStmt = $pdo->prepare("
            INSERT INTO users (nom, email, mot_de_passe, role, date_creation) 
            VALUES (?, ?, ?, 'reclamant', NOW())
        ");
        
        $insertStmt->execute([$username, $email, $hashedPassword]);
        
        error_log("User registered successfully: " . $email);
        
        echo json_encode([
            'success' => true, 
            'message' => 'Inscription réussie! Vous pouvez maintenant vous connecter.'
        ]);
        
    } catch (PDOException $e) {
        error_log("Registration error: " . $e->getMessage());
        
        
        if ($e->getCode() == 23000) {
            echo json_encode(['success' => false, 'message' => 'Cet email ou nom d\'utilisateur est déjà utilisé']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'inscription. Veuillez réessayer.']);
        }
    }
}

else {
    error_log("Invalid action: " . $action);
    echo json_encode(['success' => false, 'message' => 'Action invalide']);
}
?>