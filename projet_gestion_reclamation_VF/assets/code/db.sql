CREATE DATABASE IF NOT EXISTS gestion_reclamations CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE gestion_reclamations;

-- ---------------------
-- Table users
-- ---------------------
CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nom VARCHAR(120) NOT NULL,
  email VARCHAR(255) NOT NULL UNIQUE,
  role ENUM('reclamant','gestionnaire','administrateur') NOT NULL DEFAULT 'reclamant',
  mot_de_passe VARCHAR(255) NOT NULL,
  date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  dernier_login DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------
-- Table categories
-- ---------------------
CREATE TABLE categories (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nom VARCHAR(100) NOT NULL,
  description VARCHAR(255),
  UNIQUE KEY (nom)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------
-- Table statuts
-- ---------------------
CREATE TABLE statuts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  cle VARCHAR(50) NOT NULL,
  libelle VARCHAR(100) NOT NULL,
  UNIQUE KEY (cle)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------
-- Table reclamations
-- ---------------------
CREATE TABLE reclamations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  categorie_id INT NOT NULL,
  objet VARCHAR(255) NOT NULL,
  description TEXT NOT NULL,
  gestionnaire_id INT NULL,
  priorite ENUM('basse','moyenne','haute') DEFAULT 'moyenne',
  statut_id INT NOT NULL,
  date_soumission DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  date_dernier_update DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  date_cloture DATETIME NULL,
  urgent TINYINT(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------
-- Table pieces_jointes
-- ---------------------
CREATE TABLE pieces_jointes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  reclamation_id INT NOT NULL,
  chemin_fichier VARCHAR(512) NOT NULL,
  nom_original VARCHAR(255),
  mime VARCHAR(100),
  taille INT,
  date_ajout DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (reclamation_id) REFERENCES reclamations(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------
-- Table commentaires
-- ---------------------
CREATE TABLE commentaires (
  id INT AUTO_INCREMENT PRIMARY KEY,
  reclamation_id INT NOT NULL,
  auteur_id INT NULL,
  message TEXT NOT NULL,
  visible_par_reclamant TINYINT(1) DEFAULT 1,
  date_commentaire DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (reclamation_id) REFERENCES reclamations(id) ON DELETE CASCADE,
  FOREIGN KEY (auteur_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------
-- Table historique_statuts
-- ---------------------
CREATE TABLE historique_statuts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  reclamation_id INT NOT NULL,
  ancien_statut_id INT NULL,
  nouveau_statut_id INT NOT NULL,
  modif_par INT NULL,
  commentaire VARCHAR(500),
  date_modification DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (reclamation_id) REFERENCES reclamations(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------
-- Table notifications
-- ---------------------
CREATE TABLE notifications (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  type VARCHAR(100) NOT NULL,
  reference_table VARCHAR(100),
  reference_id INT,
  contenu TEXT,
  lu TINYINT(1) DEFAULT 0,
  date_creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------
-- Table assignments
-- ---------------------
CREATE TABLE assignments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  reclamation_id INT NOT NULL,
  gestionnaire_id INT NOT NULL,
  assigne_par INT NULL,
  date_assigned DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  date_unassigned DATETIME NULL,
  FOREIGN KEY (reclamation_id) REFERENCES reclamations(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------
-- Foreign Keys pour reclamations
-- ---------------------
ALTER TABLE reclamations
  ADD CONSTRAINT fk_reclamations_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  ADD CONSTRAINT fk_reclamations_categorie FOREIGN KEY (categorie_id) REFERENCES categories(id) ON DELETE RESTRICT,
  ADD CONSTRAINT fk_reclamations_statut FOREIGN KEY (statut_id) REFERENCES statuts(id) ON DELETE RESTRICT,
  ADD CONSTRAINT fk_reclamations_gestionnaire FOREIGN KEY (gestionnaire_id) REFERENCES users(id) ON DELETE SET NULL;

-- ---------------------
-- Foreign Keys pour historique_statuts
-- ---------------------
ALTER TABLE historique_statuts
  ADD CONSTRAINT fk_histo_ancien_statut FOREIGN KEY (ancien_statut_id) REFERENCES statuts(id) ON DELETE SET NULL,
  ADD CONSTRAINT fk_histo_nouveau_statut FOREIGN KEY (nouveau_statut_id) REFERENCES statuts(id) ON DELETE RESTRICT,
  ADD CONSTRAINT fk_histo_modif_par FOREIGN KEY (modif_par) REFERENCES users(id) ON DELETE SET NULL;

-- ---------------------
-- Trigger : historiser les changements de statut
-- ---------------------
DROP TRIGGER IF EXISTS trg_reclamations_statut_change;
DELIMITER $$
CREATE TRIGGER trg_reclamations_statut_change
AFTER UPDATE ON reclamations
FOR EACH ROW
BEGIN
  IF NOT (OLD.statut_id <=> NEW.statut_id) THEN
    INSERT INTO historique_statuts (reclamation_id, ancien_statut_id, nouveau_statut_id, modif_par, commentaire, date_modification)
    VALUES (NEW.id, OLD.statut_id, NEW.statut_id, NEW.gestionnaire_id, CONCAT('Changement automatique - ancien:', IFNULL(OLD.statut_id,'NULL'), ' nouveau:', NEW.statut_id), NOW());
  END IF;
END$$
DELIMITER ;

-- ---------------------
-- Vue utile
-- ---------------------
DROP VIEW IF EXISTS v_reclamation_detail;
CREATE VIEW v_reclamation_detail AS
SELECT r.*, u.nom AS reclamant_nom, u.email AS reclamant_email,
       g.nom AS gestionnaire_nom, c.nom AS categorie_nom, s.libelle AS statut_libelle
FROM reclamations r
LEFT JOIN users u ON r.user_id = u.id
LEFT JOIN users g ON r.gestionnaire_id = g.id
LEFT JOIN categories c ON r.categorie_id = c.id
LEFT JOIN statuts s ON r.statut_id = s.id;

-- =============================================
-- DONNEES INITIALES
-- =============================================

-- Statuts
INSERT INTO statuts (cle, libelle) VALUES
('en_attente', 'En attente'),
('en_cours', 'En cours de traitement'),
('acceptee', 'Acceptée'),
('rejetee', 'Rejetée'),
('fermee', 'Fermée');

-- Categories
INSERT INTO categories (nom, description) VALUES
('Technique', 'Problèmes techniques et bugs'),
('Facturation', 'Questions de facturation et paiement'),
('Service', 'Qualité du service client'),
('Livraison', 'Problèmes de livraison'),
('Autre', 'Autres réclamations');

-- Utilisateur test (mot de passe: password123)
INSERT INTO users (nom, email, role, mot_de_passe) VALUES
('Jean Dupont', 'jean.dupont@example.com', 'reclamant', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
('Admin Test', 'admin@example.com', 'administrateur', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
('Gestionnaire Test', 'gestionnaire@example.com', 'gestionnaire', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- Reclamations test
INSERT INTO reclamations (user_id, categorie_id, objet, description, statut_id, priorite, date_soumission) VALUES
(1, 2, 'Problème de facturation', 'Je constate une erreur sur ma dernière facture.', 2, 'haute', '2025-12-01 10:30:00'),
(1, 1, 'Bug sur l\'application', 'L\'application se ferme de manière inattendue.', 1, 'moyenne', '2025-11-30 14:15:00'),
(1, 3, 'Service client non réactif', 'Pas de réponse depuis 5 jours.', 3, 'haute', '2025-11-28 09:00:00'),
(1, 4, 'Livraison retardée', 'Ma commande devait arriver il y a 2 semaines.', 4, 'basse', '2025-11-25 16:45:00'),
(1, 1, 'Erreur 404 sur le site', 'Plusieurs pages du site ne fonctionnent plus.', 5, 'moyenne', '2025-11-20 11:20:00');

INSERT INTO statuts (cle, libelle) VALUES
('attente_info_reclamant', 'En attente d\'informations');

-- Add comment permission flag
ALTER TABLE reclamations 
ADD COLUMN peut_commenter TINYINT(1) DEFAULT 0
COMMENT 'Permet au réclamant de commenter après demande d\'info';

ALTER TABLE notifications 
ADD COLUMN date_lu DATETIME NULL;

CREATE INDEX idx_notifications_user_lu ON notifications(user_id, lu);
CREATE INDEX idx_commentaires_reclamation ON commentaires(reclamation_id, date_commentaire);

ALTER TABLE `commentaires` 
ADD COLUMN `type_commentaire` ENUM('commentaire', 'demande_info', 'reponse_info') DEFAULT 'commentaire' 
AFTER `message`;

ALTER TABLE `commentaires` 
ADD COLUMN `infos_demandees` TEXT NULL 
AFTER `type_commentaire`;

CREATE TABLE IF NOT EXISTS `pieces_jointes_infos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `commentaire_id` int(11) NOT NULL,
  `nom_original` varchar(255) NOT NULL,
  `nom_stockage` varchar(255) NOT NULL,
  `chemin_fichier` varchar(500) NOT NULL,
  `mime` varchar(100) DEFAULT NULL,
  `taille` int(11) DEFAULT NULL,
  `date_ajout` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `commentaire_id` (`commentaire_id`),
  CONSTRAINT `pieces_jointes_infos_ibfk_1` FOREIGN KEY (`commentaire_id`) REFERENCES `commentaires` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO `statuts` (`cle`, `libelle`) VALUES 
('attente_info_reclamant', 'En attente d\'informations');

-- 5. Index pour performances
CREATE INDEX IF NOT EXISTS idx_commentaires_type ON commentaires(type_commentaire, date_commentaire);
CREATE INDEX IF NOT EXISTS idx_notifications_type ON notifications(type, lu, date_creation);

-- Vérification
SELECT 'Ajouts SQL terminés avec succès !' AS statut;

ALTER TABLE commentaires 

ADD COLUMN audio_transcription TEXT NULL,
ADD COLUMN audio_fichier VARCHAR(255) NULL;