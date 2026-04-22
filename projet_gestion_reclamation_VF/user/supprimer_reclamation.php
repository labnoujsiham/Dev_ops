<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: ../connexion/connexion.php');
    exit();
}

$userId = $_SESSION['user_id'];

require_once '../connexion/db_config.php';

$reclamationId = $_GET['id'] ?? 0;

try {
    $stmt = $pdo->prepare("SELECT id FROM reclamations WHERE id = ? AND user_id = ?");
    $stmt->execute([$reclamationId, $userId]);
    $reclamation = $stmt->fetch();
    
    if (!$reclamation) {
        $_SESSION['error'] = "Réclamation introuvable ou accès refusé";
        header('Location: mes_reclamations.php');
        exit;
    }
    
    
    $stmt = $pdo->prepare("DELETE FROM reclamations WHERE id = ? AND user_id = ?");
    $stmt->execute([$reclamationId, $userId]);
    
    $_SESSION['success'] = "Réclamation supprimée avec succès";
    header('Location: mes_reclamations.php');
    exit;
    
} catch (PDOException $e) {
    $_SESSION['error'] = "Erreur lors de la suppression de la réclamation";
    header('Location: mes_reclamations.php');
    exit;
}