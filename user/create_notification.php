<?php


/**
 *  Créer une notification
 * 
 * @param PDO $db 
 * @param int $userId 
 * @param int $reclamationId 
 * @param string $message 
 * @param string $type 
 * @param int|null $gestionnaireId 
 * @return bool 
 */
function createNotification($db, $userId, $reclamationId, $message, $type = 'nouveau_commentaire', $gestionnaireId = null) {
    try {
        $stmt = $db->prepare("
            INSERT INTO notifications (user_id, reclamation_id, gestionnaire_id, message, type, lu, date_creation) 
            VALUES (?, ?, ?, ?, ?, 0, NOW())
        ");
        
        return $stmt->execute([$userId, $reclamationId, $gestionnaireId, $message, $type]);
    } catch (PDOException $e) {
        error_log("Erreur création notification: " . $e->getMessage());
        return false;
    }
}

/**
 * Créer une notification pour un nouveau commentaire gestionnaire
 * 
 * @param PDO $db 
 * @param int $reclamationId 
 * @param int $gestionnaireId 
 * @return bool 
 */
function createCommentNotification($db, $reclamationId, $gestionnaireId) {
    try {
        // Récupérer l'user_id de la réclamation
        $stmt = $db->prepare("SELECT user_id, objet FROM reclamations WHERE id = ?");
        $stmt->execute([$reclamationId]);
        $reclamation = $stmt->fetch();
        
        if (!$reclamation) {
            return false;
        }
        
        $message = "Le gestionnaire a répondu à votre réclamation";
        
        return createNotification(
            $db, 
            $reclamation['user_id'], 
            $reclamationId, 
            $message, 
            'nouveau_commentaire',
            $gestionnaireId
        );
    } catch (PDOException $e) {
        error_log("Erreur création notification commentaire: " . $e->getMessage());
        return false;
    }
}

/**
 * Créer une notification pour un changement de statut
 * 
 * @param PDO $db 
 * @param int $reclamationId 
 * @param string $nouveauStatut 
 * @param int|null $gestionnaireId 
 * @return bool 
 */
function createStatusChangeNotification($db, $reclamationId, $nouveauStatut, $gestionnaireId = null) {
    try {
        $stmt = $db->prepare("SELECT user_id FROM reclamations WHERE id = ?");
        $stmt->execute([$reclamationId]);
        $reclamation = $stmt->fetch();
        
        if (!$reclamation) {
            return false;
        }
        
        $message = "Le statut de votre réclamation a été changé en : " . $nouveauStatut;
        
        return createNotification(
            $db, 
            $reclamation['user_id'], 
            $reclamationId, 
            $message, 
            'changement_statut',
            $gestionnaireId
        );
    } catch (PDOException $e) {
        error_log("Erreur création notification statut: " . $e->getMessage());
        return false;
    }
}