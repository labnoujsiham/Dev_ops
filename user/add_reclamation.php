<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Non authentifié']);
    exit;
}

require_once '../connexion/db_config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

$userId = $_SESSION['user_id'];

$objet = trim($_POST['objet'] ?? '');
$categorie = trim($_POST['categorie'] ?? '');
$description = trim($_POST['description'] ?? '');
$priorite = trim($_POST['priorite'] ?? 'moyenne');
$urgent = isset($_POST['urgent']) ? 1 : 0;

if (empty($objet) || empty($categorie) || empty($description)) {
    echo json_encode([
        'success' => false,
        'message' => 'Tous les champs obligatoires doivent être remplis'
    ]);
    exit;
}

if (strlen($objet) < 5 || strlen($objet) > 255) {
    echo json_encode([
        'success' => false,
        'message' => 'L\'objet doit contenir entre 5 et 255 caractères'
    ]);
    exit;
}

if (strlen($description) < 10) {
    echo json_encode([
        'success' => false,
        'message' => 'La description doit contenir au moins 10 caractères'
    ]);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT id FROM categories WHERE nom = ?");
    $stmt->execute([$categorie]);
    $categorieData = $stmt->fetch();
    
    if (!$categorieData) {
        echo json_encode([
            'success' => false,
            'message' => 'Catégorie invalide'
        ]);
        exit;
    }
    
    $categorieId = $categorieData['id'];
    
    $stmt = $pdo->prepare("SELECT id FROM statuts WHERE cle = 'en_attente'");
    $stmt->execute();
    $statutData = $stmt->fetch();
    
    if (!$statutData) {
        echo json_encode([
            'success' => false,
            'message' => 'Erreur de configuration des statuts'
        ]);
        exit;
    }
    
    $statutId = $statutData['id'];
    
    $stmt = $pdo->prepare("
        INSERT INTO reclamations 
        (user_id, categorie_id, objet, description, priorite, statut_id, date_soumission, urgent) 
        VALUES (?, ?, ?, ?, ?, ?, NOW(), ?)
    ");
    
    $stmt->execute([
        $userId,
        $categorieId,
        $objet,
        $description,
        $priorite,
        $statutId,
        $urgent
    ]);
    
    $reclamationId = $pdo->lastInsertId();
    
    try {
        $stmt = $pdo->prepare("
            SELECT id, nom, role FROM users 
            WHERE role IN ('gestionnaire', 'administrateur')
        ");
        $stmt->execute();
        $gestionnaires = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $stmt = $pdo->prepare("SELECT nom FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $userInfo = $stmt->fetch(PDO::FETCH_ASSOC);
        $userName = $userInfo['nom'] ?? 'Un utilisateur';
        
        foreach ($gestionnaires as $gest) {
            $stmt = $pdo->prepare("
                INSERT INTO notifications (user_id, type, reference_table, reference_id, contenu, lu, date_creation)
                VALUES (?, 'nouvelle_reclamation', 'reclamations', ?, ?, 0, NOW())
            ");
            $stmt->execute([
                $gest['id'],
                $reclamationId,
                'Nouvelle réclamation de ' . $userName
            ]);
        }
    } catch (PDOException $e) {
        error_log("Erreur notification: " . $e->getMessage());
    }
    
    $fichiers = [];
    if (isset($_FILES['pieces_jointes']) && !empty($_FILES['pieces_jointes']['name'][0])) {
        
        $uploadDir = dirname(__DIR__) . '/uploads/reclamations/';
        
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $filesCount = count($_FILES['pieces_jointes']['name']);
        
        for ($i = 0; $i < $filesCount; $i++) {
            if ($_FILES['pieces_jointes']['error'][$i] === UPLOAD_ERR_OK) {
                $fileName = $_FILES['pieces_jointes']['name'][$i];
                $fileTmpName = $_FILES['pieces_jointes']['tmp_name'][$i];
                $fileSize = $_FILES['pieces_jointes']['size'][$i];
                $fileMime = $_FILES['pieces_jointes']['type'][$i];
                
                // Validation du fichier
                $allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx'];
                $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                
                if (!in_array($fileExt, $allowedExtensions)) {
                    continue;
                }
                
                if ($fileSize > 5 * 1024 * 1024) { // Max taille 5MB
                    continue;
                }
                
                $uniqueName = uniqid() . '_' . time() . '.' . $fileExt;
                $destination = $uploadDir . $uniqueName;
                
                if (move_uploaded_file($fileTmpName, $destination)) {
                    
                    $stmt = $pdo->prepare("
                        INSERT INTO pieces_jointes 
                        (reclamation_id, chemin_fichier, nom_original, mime, taille, date_ajout) 
                        VALUES (?, ?, ?, ?, ?, NOW())
                    ");
                    
                    $stmt->execute([
                        $reclamationId,
                        'uploads/reclamations/' . $uniqueName,  
                        $fileName,
                        $fileMime,
                        $fileSize
                    ]);
                    
                    $fichiers[] = $fileName;
                }
            }
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Réclamation créée avec succès',
        'reclamation_id' => $reclamationId,
        'fichiers_uploades' => count($fichiers)
    ]);
    
} catch (PDOException $e) {
    error_log("Erreur add_reclamation: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de la création de la réclamation'
    ]);
}