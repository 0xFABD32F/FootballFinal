CREATE DATABASE footballdb;
-- Table des comptes utilisateurs
CREATE TABLE IF NOT EXISTS comptes (
    id_compte BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    login VARCHAR(15) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    nom VARCHAR(15) NOT NULL,
    prenom VARCHAR(15) NOT NULL,
    type_compte ENUM('user', 'admin_tournoi', 'admin_global') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table des équipes
CREATE TABLE IF NOT EXISTS equipes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    ville VARCHAR(100) NOT NULL,
    photo VARCHAR(255),
    date_creation date,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table des joueurs
CREATE TABLE IF NOT EXISTS joueurs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    equipe_id INT NOT NULL,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    date_naissance DATE NOT NULL,    
    position ENUM('gardien', 'defenseur_central', 'defenseur_lateral', 'milieu_defensif', 'milieu_offensif', 'ailier_droit', 'ailier_gauche', 'attaquant') NOT NULL,
    numero_maillot INT NOT NULL,
    date_debut DATE NOT NULL,
    date_fin DATE DEFAULT NULL,
    nationalite VARCHAR(50) NOT NULL,
    origine VARCHAR(50),
    photo VARCHAR(255),
    CONSTRAINT fk_joueur_equipe FOREIGN KEY (equipe_id) REFERENCES equipes(id) ON DELETE CASCADE
);

-- Table des stades
CREATE TABLE IF NOT EXISTS stades (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(255) NOT NULL,
    ville VARCHAR(255) NOT NULL,
    capacite INT NOT NULL
);

-- Table du staff technique
CREATE TABLE IF NOT EXISTS staff (
    id INT AUTO_INCREMENT PRIMARY KEY,
    equipe_id INT NOT NULL,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    date_naissance DATE NOT NULL,
    poste ENUM('Entraineur_principal', 'Entraineur_adjoint', 'Preparateur_physique', 'Medecin') NOT NULL,
    date_debut DATE NOT NULL,
    date_fin DATE DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (equipe_id) REFERENCES equipes(id) ON DELETE CASCADE
);

-- Table des arbitres
CREATE TABLE IF NOT EXISTS arbitres (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    grade VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table des publications
CREATE TABLE IF NOT EXISTS publications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    photo VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
-- Table des types de tournoi
CREATE TABLE IF NOT EXISTS types_tournois (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) UNIQUE NOT NULL
);


-- Table des tournois
CREATE TABLE IF NOT EXISTS tournois (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type_id INT NOT NULL,
    date_debut DATE,
    date_fin DATE,    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (type_id) REFERENCES types_tournois(id) ON DELETE CASCADE
);


-- Table des matches
CREATE TABLE IF NOT EXISTS matches (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_admin INT UNSIGNED NOT NULL,
    tournois_id INT NOT NULL,
    stade_id INT NOT NULL,
    arbitre_id INT NOT NULL,
    equipe_domicile_id INT NOT NULL,
    equipe_exterieur_id INT NOT NULL,
    heure_debut TIME NOT NULL,
    heure_fin TIME DEFAULT NULL,
    date_match DATE NOT NULL,
    etat ENUM('prevu', 'en_cours', 'termine', 'annule') NOT NULL DEFAULT 'prevu',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    score_domicile_prolongation INT DEFAULT NULL,
    score_exterieur_prolongation INT DEFAULT NULL,
    score_domicile_tirs INT DEFAULT NULL,
    score_exterieur_tirs INT DEFAULT NULL,
    score_domicile INT DEFAULT NULL,
    score_exterieur INT DEFAULT NULL,
    FOREIGN KEY (tournois_id) REFERENCES tournois(id) ON DELETE CASCADE,
    FOREIGN KEY (stade_id) REFERENCES stades(id) ON DELETE CASCADE,
    FOREIGN KEY (arbitre_id) REFERENCES arbitres(id) ON DELETE CASCADE,
    FOREIGN KEY (equipe_domicile_id) REFERENCES equipes(id) ON DELETE CASCADE,
    FOREIGN KEY (equipe_exterieur_id) REFERENCES equipes(id) ON DELETE CASCADE,
    FOREIGN KEY (id_admin) REFERENCES comptes(id_compte) ON DELETE CASCADE
);

-- Table des événements de match
CREATE TABLE match_events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    match_id INT NOT NULL,
    joueur_id INT DEFAULT NULL,
    event_type ENUM('but', 'carton_jaune', 'carton_rouge', 'remplacement', 'blessure') NOT NULL,
    minute INT NOT NULL,
    details TEXT,
    FOREIGN KEY (match_id) REFERENCES matches(id) ON DELETE CASCADE,
    FOREIGN KEY (joueur_id) REFERENCES joueurs(id) ON DELETE SET NULL
);

-- Table des statistiques individuelles par joueur
CREATE TABLE joueur_statistics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    match_id INT NOT NULL,
    joueur_id INT NOT NULL,
    dribbles_reussis SMALLINT DEFAULT 0,
    interceptions SMALLINT DEFAULT 0,
    passes_decisives SMALLINT DEFAULT 0,
    tirs_cadres SMALLINT DEFAULT 0,
    fautes SMALLINT DEFAULT 0,   
    minutes_jouees INT DEFAULT 0,
    FOREIGN KEY (match_id) REFERENCES matches(id) ON DELETE CASCADE,
    FOREIGN KEY (joueur_id) REFERENCES joueurs(id) ON DELETE CASCADE
);

-- Table des statistiques par équipe
CREATE TABLE equipe_statistics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    match_id INT NOT NULL,    
    equipe_id INT NOT NULL,
    passes INT DEFAULT 0,
    tirs INT DEFAULT 0,
    corners INT DEFAULT 0,
    penalties INT DEFAULT 0,
    coups_franc INT DEFAULT 0,
    centres INT DEFAULT 0,
    hors_jeu INT DEFAULT 0,
    FOREIGN KEY (match_id) REFERENCES matches(id) ON DELETE CASCADE,
    FOREIGN KEY (equipe_id) REFERENCES equipes(id) ON DELETE CASCADE
);

-- Table des participations des joueurs dans les matches
CREATE TABLE IF NOT EXISTS participations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    match_id INT NOT NULL,
    equipe_id INT NOT NULL,
    joueur_id INT NOT NULL,
    titulaire BOOLEAN DEFAULT TRUE, 
    minute_sortie SMALLINT DEFAULT NULL, 
    joueur_remplacant_id INT DEFAULT NULL,
    minute_entree SMALLINT DEFAULT NULL, 
    position ENUM('gardien', 'defenseur_central', 'defenseur_lateral', 'milieu_defensif', 'milieu_offensif', 'ailier_droit', 'ailier_gauche', 'attaquant'),
    FOREIGN KEY (match_id) REFERENCES matches(id) ON DELETE CASCADE,
    FOREIGN KEY (equipe_id) REFERENCES equipes(id) ON DELETE CASCADE,
    FOREIGN KEY (joueur_id) REFERENCES joueurs(id) ON DELETE CASCADE,
    FOREIGN KEY (joueur_remplacant_id) REFERENCES joueurs(id) ON DELETE CASCADE
);


-- Table des votes (sondages)
CREATE TABLE IF NOT EXISTS votes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    match_id INT NOT NULL,
    voted_team_id INT NOT NULL,
    vote_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_user_match (user_id, match_id),
    FOREIGN KEY (user_id) REFERENCES comptes(id_compte) ON DELETE CASCADE,
    FOREIGN KEY (match_id) REFERENCES matches(id) ON DELETE CASCADE,
    FOREIGN KEY (voted_team_id) REFERENCES equipes(id) ON DELETE CASCADE
);
-- Table des abonnements
CREATE TABLE IF NOT EXISTS Abonnement (
    id INT AUTO_INCREMENT PRIMARY KEY,
    utilisateur_id BIGINT UNSIGNED NOT NULL,
    type ENUM('match', 'equipe', 'tournoi') NOT NULL,
    reference_id INT NOT NULL,
    FOREIGN KEY (utilisateur_id) REFERENCES comptes(id_compte) ON DELETE CASCADE
);
-- Table du classement des equipes 
CREATE TABLE IF NOT EXISTS classement_equipes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tournoi_id INT NOT NULL,
    equipe_id INT NOT NULL,
    points INT DEFAULT 0,
    matchs_joues INT DEFAULT 0,
    victoires INT DEFAULT 0,
    defaites INT DEFAULT 0,
    nuls INT DEFAULT 0,
    buts_marques INT DEFAULT 0,
    buts_encaisse INT DEFAULT 0,
    saison YEAR NOT NULL,
    FOREIGN KEY (tournoi_id) REFERENCES tournois(id) ON DELETE CASCADE,
    FOREIGN KEY (equipe_id) REFERENCES equipes(id) ON DELETE CASCADE
);
-- Mise a jour automatique du classement apres chaque match
DELIMITER $$

CREATE TRIGGER update_classement AFTER UPDATE ON matches
FOR EACH ROW
BEGIN
    -- Vérifier si le match fait partie du tournoi "Botola Pro"
    DECLARE is_botola_pro BOOL;

    -- Vérifie si le tournoi correspond à la Botola Pro
    SELECT EXISTS (SELECT 1 FROM tournois t
                   JOIN types_tournois tt ON t.type_id = tt.id
                   WHERE t.id = NEW.tournois_id AND tt.nom = 'Botola Pro') INTO is_botola_pro;

    -- Si ce n'est pas un match de la Botola Pro, ne pas mettre à jour le classement
    IF is_botola_pro THEN
        -- Vérifier si le match est terminé et que les scores sont disponibles
        IF NEW.etat = 'termine' AND 
           (NEW.score_domicile_prolongation IS NOT NULL AND NEW.score_exterieur_prolongation IS NOT NULL) THEN
            
            -- Mise à jour des statistiques pour l'équipe domicile après les prolongations
            UPDATE classement_equipes
            SET 
                matchs_joues = matchs_joues + 1,
                buts_marques = buts_marques + NEW.score_domicile_prolongation,
                buts_encaisse = buts_encaisse + NEW.score_exterieur_prolongation,
                victoires = victoires + IF(NEW.score_domicile_prolongation > NEW.score_exterieur_prolongation, 1, 0),
                nuls = nuls + IF(NEW.score_domicile_prolongation = NEW.score_exterieur_prolongation, 1, 0),
                defaites = defaites + IF(NEW.score_domicile_prolongation < NEW.score_exterieur_prolongation, 1, 0),
                points = points + IF(NEW.score_domicile_prolongation > NEW.score_exterieur_prolongation, 3, IF(NEW.score_domicile_prolongation = NEW.score_exterieur_prolongation, 1, 0))
            WHERE equipe_id = NEW.equipe_domicile_id AND tournoi_id = NEW.tournois_id;

            -- Mise à jour des statistiques pour l'équipe extérieure après les prolongations
            UPDATE classement_equipes
            SET 
                matchs_joues = matchs_joues + 1,
                buts_marques = buts_marques + NEW.score_exterieur_prolongation,
                buts_encaisse = buts_encaisse + NEW.score_domicile_prolongation,
                victoires = victoires + IF(NEW.score_exterieur_prolongation > NEW.score_domicile_prolongation, 1, 0),
                nuls = nuls + IF(NEW.score_exterieur_prolongation = NEW.score_domicile_prolongation, 1, 0),
                defaites = defaites + IF(NEW.score_exterieur_prolongation < NEW.score_domicile_prolongation, 1, 0),
                points = points + IF(NEW.score_exterieur_prolongation > NEW.score_domicile_prolongation, 3, IF(NEW.score_exterieur_prolongation = NEW.score_domicile_prolongation, 1, 0))
            WHERE equipe_id = NEW.equipe_exterieur_id AND tournoi_id = NEW.tournois_id;

        -- Si le match a été décidé par les tirs au but
        ELSEIF NEW.etat = 'termine' AND 
               (NEW.score_domicile_tirs IS NOT NULL AND NEW.score_exterieur_tirs IS NOT NULL) THEN
            
            -- Mise à jour des statistiques pour l'équipe domicile après tirs au but
            UPDATE classement_equipes
            SET 
                matchs_joues = matchs_joues + 1,
                buts_marques = buts_marques + NEW.score_domicile_tirs,
                buts_encaisse = buts_encaisse + NEW.score_exterieur_tirs,
                victoires = victoires + IF(NEW.score_domicile_tirs > NEW.score_exterieur_tirs, 1, 0),
                nuls = nuls + IF(NEW.score_domicile_tirs = NEW.score_exterieur_tirs, 1, 0),
                defaites = defaites + IF(NEW.score_domicile_tirs < NEW.score_exterieur_tirs, 1, 0),
                points = points + IF(NEW.score_domicile_tirs > NEW.score_exterieur_tirs, 3, IF(NEW.score_domicile_tirs = NEW.score_exterieur_tirs, 1, 0))
            WHERE equipe_id = NEW.equipe_domicile_id AND tournoi_id = NEW.tournois_id;

            -- Mise à jour des statistiques pour l'équipe extérieure après tirs au but
            UPDATE classement_equipes
            SET 
                matchs_joues = matchs_joues + 1,
                buts_marques = buts_marques + NEW.score_exterieur_tirs,
                buts_encaisse = buts_encaisse + NEW.score_domicile_tirs,
                victoires = victoires + IF(NEW.score_exterieur_tirs > NEW.score_domicile_tirs, 1, 0),
                nuls = nuls + IF(NEW.score_exterieur_tirs = NEW.score_domicile_tirs, 1, 0),
                defaites = defaites + IF(NEW.score_exterieur_tirs < NEW.score_domicile_tirs, 1, 0),
                points = points + IF(NEW.score_exterieur_tirs > NEW.score_domicile_tirs, 3, IF(NEW.score_exterieur_tirs = NEW.score_domicile_tirs, 1, 0))
            WHERE equipe_id = NEW.equipe_exterieur_id AND tournoi_id = NEW.tournois_id;
        
        -- Si le match est un match de phase classique (sans prolongation ni tirs au but)
        ELSEIF NEW.etat = 'termine' AND 
               (NEW.score_domicile IS NOT NULL AND NEW.score_exterieur IS NOT NULL) THEN

            -- Mise à jour des statistiques pour l'équipe domicile
            UPDATE classement_equipes
            SET 
                matchs_joues = matchs_joues + 1,
                buts_marques = buts_marques + NEW.score_domicile,
                buts_encaisse = buts_encaisse + NEW.score_exterieur,
                victoires = victoires + IF(NEW.score_domicile > NEW.score_exterieur, 1, 0),
                nuls = nuls + IF(NEW.score_domicile = NEW.score_exterieur, 1, 0),
                defaites = defaites + IF(NEW.score_domicile < NEW.score_exterieur, 1, 0),
                points = points + IF(NEW.score_domicile > NEW.score_exterieur, 3, IF(NEW.score_domicile = NEW.score_exterieur, 1, 0))
            WHERE equipe_id = NEW.equipe_domicile_id AND tournoi_id = NEW.tournois_id;

            -- Mise à jour des statistiques pour l'équipe extérieure
            UPDATE classement_equipes
            SET 
                matchs_joues = matchs_joues + 1,
                buts_marques = buts_marques + NEW.score_exterieur,
                buts_encaisse = buts_encaisse + NEW.score_domicile,
                victoires = victoires + IF(NEW.score_exterieur > NEW.score_domicile, 1, 0),
                nuls = nuls + IF(NEW.score_exterieur = NEW.score_domicile, 1, 0),
                defaites = defaites + IF(NEW.score_exterieur < NEW.score_domicile, 1, 0),
                points = points + IF(NEW.score_exterieur > NEW.score_domicile, 3, IF(NEW.score_exterieur = NEW.score_domicile, 1, 0))
            WHERE equipe_id = NEW.equipe_exterieur_id AND tournoi_id = NEW.tournois_id;
        END IF;
    END IF;
END $$

DELIMITER ;




-- Table pour gerer la coupe du Throne
CREATE TABLE IF NOT EXISTS knockout_stage (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tournois_id INT NOT NULL,
    phase ENUM('16e de finale', '8e de finale', 'quart de finale', 'demi-finale', 'finale') NOT NULL,
    match_id INT NOT NULL,
    equipe_gagnante_id INT DEFAULT NULL,
    equipe_perdante_id INT DEFAULT NULL,
    next_match_id INT DEFAULT NULL,
    etat ENUM('prevu', 'en_cours', 'termine') DEFAULT 'prevu',
    methode_victoire ENUM('temps réglementaire', 'prolongations', 'tirs au but') DEFAULT NULL,
    saison YEAR NOT NULL,
    FOREIGN KEY (tournois_id) REFERENCES tournois(id) ON DELETE CASCADE,
    FOREIGN KEY (match_id) REFERENCES matches(id) ON DELETE CASCADE,
    FOREIGN KEY (equipe_gagnante_id) REFERENCES equipes(id) ON DELETE SET NULL,
    FOREIGN KEY (equipe_perdante_id) REFERENCES equipes(id) ON DELETE SET NULL,
    FOREIGN KEY (next_match_id) REFERENCES matches(id) ON DELETE SET NULL
);




