<?php
// Prevent any output before headers
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', 'php_errors.log');

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['Type_compte']) || $_SESSION['Type_compte'] !== 'admin_tournoi') {
    http_response_code(403);
    exit(json_encode(['success' => false, 'error' => 'Unauthorized']));
}

include("connexion.php");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit(json_encode(['success' => false, 'error' => 'Method not allowed']));
}

try {
    $conn->beginTransaction();

    // Get POST data
    $input = file_get_contents('php://input');
    error_log("Received data: " . $input);
    
    $data = json_decode($input, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Invalid JSON data: " . json_last_error_msg());
    }
    
    if (!isset($data['match_id'])) {
        throw new Exception("Missing match_id in request data");
    }
    
    $match_id = $data['match_id'];
    error_log("Processing match ID: " . $match_id);

    // Check if match is editable based on time
    $stmt = $conn->prepare("
        SELECT etat, DATE_FORMAT(CONCAT(date_match, ' ', heure_debut), '%Y-%m-%d %H:%i:%s') as match_datetime 
        FROM matches 
        WHERE id = :match_id
    ");
    
    if (!$stmt->execute([':match_id' => $match_id])) {
        throw new Exception("Failed to get match details: " . print_r($stmt->errorInfo(), true));
    }
    
    $match_details = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$match_details) {
        throw new Exception("No match found for ID: " . $match_id);
    }
    
    // If match is scheduled (prevu) and it's less than 15 minutes until start time, block modification
    if ($match_details['etat'] === 'prevu') {
        $match_time = strtotime($match_details['match_datetime']);
        $current_time = time();
        $time_diff_minutes = ($match_time - $current_time) / 60;
        
        error_log("Match time: " . date('Y-m-d H:i:s', $match_time) . ", Current time: " . date('Y-m-d H:i:s', $current_time));
        error_log("Time difference in minutes: " . $time_diff_minutes);
        
        if ($time_diff_minutes <= 15 && $time_diff_minutes > 0) {
            throw new Exception("Match cannot be modified as it starts in less than 15 minutes");
        }
    }

    // Validate required data
    if (!isset($data['team1']) || !isset($data['team2'])) {
        throw new Exception("Missing team data in request");
    }

    // Update match scores and state
    $stmt = $conn->prepare("UPDATE matches 
                          SET score_domicile = :score1, 
                              score_exterieur = :score2,
                              etat = 'termine',
                              heure_fin = CURRENT_TIME
                          WHERE id = :match_id");
    
    $params = [
        ':score1' => $data['team1']['goals'] ?? 0,
        ':score2' => $data['team2']['goals'] ?? 0,
        ':match_id' => $match_id
    ];
    error_log("Executing match update with params: " . print_r($params, true));
    
    if (!$stmt->execute($params)) {
        throw new Exception("Failed to update match: " . print_r($stmt->errorInfo(), true));
    }

    // Get team IDs from match
    $stmt2 = $conn->prepare("
        SELECT m.equipe_domicile_id, m.equipe_exterieur_id, m.tournois_id, t.type_id, tt.nom as tournoi_nom 
        FROM matches m
        JOIN tournois t ON m.tournois_id = t.id
        JOIN types_tournois tt ON t.type_id = tt.id
        WHERE m.id = :match_id
    ");
    
    if (!$stmt2->execute([':match_id' => $match_id])) {
        throw new Exception("Failed to get team IDs: " . print_r($stmt2->errorInfo(), true));
    }
    
    $teams = $stmt2->fetch(PDO::FETCH_ASSOC);
    if (!$teams) {
        throw new Exception("No teams found for match ID: " . $match_id);
    }
    
    error_log("Found teams and tournament info: " . print_r($teams, true));

    // Update team 1 statistics
    $stmt = $conn->prepare("
        INSERT INTO equipe_statistics 
        (match_id, equipe_id, passes, tirs, corners, penalties, coups_franc, centres, hors_jeu)
        VALUES 
        (:match_id, :equipe_id, :passes, :tirs, :corners, :penalties, :coups_franc, :centres, :hors_jeu)
        ON DUPLICATE KEY UPDATE 
            passes = VALUES(passes),
        tirs = VALUES(tirs),
        corners = VALUES(corners),
        penalties = VALUES(penalties),
        coups_franc = VALUES(coups_franc),
            centres = VALUES(centres),
        hors_jeu = VALUES(hors_jeu)
    ");

    // Update team 1 stats
    $params1 = [
        ':match_id' => $match_id,
        ':equipe_id' => $teams['equipe_domicile_id'],
        ':passes' => $data['team1']['passes'] ?? 0,
        ':tirs' => $data['team1']['tirs'] ?? 0,
        ':corners' => $data['team1']['corners'] ?? 0,
        ':penalties' => $data['team1']['penalties'] ?? 0,
        ':coups_franc' => $data['team1']['coups_franc'] ?? 0,
        ':centres' => $data['team1']['centres'] ?? 0,
        ':hors_jeu' => $data['team1']['hors_jeu'] ?? 0
    ];
    
    if (!$stmt->execute($params1)) {
        throw new Exception("Failed to update team 1 stats: " . print_r($stmt->errorInfo(), true));
    }

    // Update team 2 stats
    $params2 = [
        ':match_id' => $match_id,
        ':equipe_id' => $teams['equipe_exterieur_id'],
        ':passes' => $data['team2']['passes'] ?? 0,
        ':tirs' => $data['team2']['tirs'] ?? 0,
        ':corners' => $data['team2']['corners'] ?? 0,
        ':penalties' => $data['team2']['penalties'] ?? 0,
        ':coups_franc' => $data['team2']['coups_franc'] ?? 0,
        ':centres' => $data['team2']['centres'] ?? 0,
        ':hors_jeu' => $data['team2']['hors_jeu'] ?? 0
    ];
    
    if (!$stmt->execute($params2)) {
        throw new Exception("Failed to update team 2 stats: " . print_r($stmt->errorInfo(), true));
    }

    // Update player statistics
    $stmt = $conn->prepare("
        INSERT INTO joueur_statistics 
        (match_id, joueur_id, dribbles_reussis, interceptions, passes_decisives, tirs_cadres, fautes)
        VALUES 
        (:match_id, :joueur_id, :dribbles_reussis, :interceptions, :passes_decisives, :tirs_cadres, :fautes)
        ON DUPLICATE KEY UPDATE
        dribbles_reussis = VALUES(dribbles_reussis),
        interceptions = VALUES(interceptions),
        passes_decisives = VALUES(passes_decisives),
        tirs_cadres = VALUES(tirs_cadres),
        fautes = VALUES(fautes)
    ");

    foreach ($data['players'] as $playerId => $stats) {
        $params = [
            ':match_id' => $match_id,
            ':joueur_id' => $playerId,
            ':dribbles_reussis' => $stats['dribbles_reussis'] ?? 0,
            ':interceptions' => $stats['interceptions'] ?? 0,
            ':passes_decisives' => $stats['passes_decisives'] ?? 0,
            ':tirs_cadres' => $stats['tirs_cadres'] ?? 0,
            ':fautes' => $stats['fautes'] ?? 0
        ];
        
        if (!$stmt->execute($params)) {
            throw new Exception("Failed to update player stats for player $playerId: " . print_r($stmt->errorInfo(), true));
        }
    }

    // Update classement_equipes if tournament is Botola Pro
    if ($teams['tournoi_nom'] === 'Botola Pro') {
        error_log("Updating standings for Botola Pro match");
        $score1 = $data['team1']['goals'] ?? 0;
        $score2 = $data['team2']['goals'] ?? 0;
        $current_year = date('Y');

        // Update home team standings
        $stmt = $conn->prepare("
            INSERT INTO classement_equipes 
            (tournoi_id, equipe_id, points, matchs_joues, victoires, defaites, nuls, buts_marques, buts_encaisse, saison)
            VALUES 
            (:tournoi_id, :equipe_id, :points, 1, :victoires, :defaites, :nuls, :buts_marques, :buts_encaisse, :saison)
            ON DUPLICATE KEY UPDATE
            points = points + :points_update,
            matchs_joues = matchs_joues + 1,
            victoires = victoires + :victoires_update,
            defaites = defaites + :defaites_update,
            nuls = nuls + :nuls_update,
            buts_marques = buts_marques + :buts_marques_update,
            buts_encaisse = buts_encaisse + :buts_encaisse_update
        ");

        // Calculate points and stats for home team
        $points1 = $score1 > $score2 ? 3 : ($score1 == $score2 ? 1 : 0);
        $victoires1 = $score1 > $score2 ? 1 : 0;
        $defaites1 = $score1 < $score2 ? 1 : 0;
        $nuls1 = $score1 == $score2 ? 1 : 0;

        $params1 = [
            ':tournoi_id' => $teams['tournois_id'],
            ':equipe_id' => $teams['equipe_domicile_id'],
            ':points' => $points1,
            ':victoires' => $victoires1,
            ':defaites' => $defaites1,
            ':nuls' => $nuls1,
            ':buts_marques' => $score1,
            ':buts_encaisse' => $score2,
            ':saison' => $current_year,
            ':points_update' => $points1,
            ':victoires_update' => $victoires1,
            ':defaites_update' => $defaites1,
            ':nuls_update' => $nuls1,
            ':buts_marques_update' => $score1,
            ':buts_encaisse_update' => $score2
        ];
        
        error_log("Executing home team standings update with params: " . print_r($params1, true));
        
        if (!$stmt->execute($params1)) {
            throw new Exception("Failed to update home team standings: " . print_r($stmt->errorInfo(), true));
        }

        // Calculate points and stats for away team
        $points2 = $score2 > $score1 ? 3 : ($score2 == $score1 ? 1 : 0);
        $victoires2 = $score2 > $score1 ? 1 : 0;
        $defaites2 = $score2 < $score1 ? 1 : 0;
        $nuls2 = $score2 == $score1 ? 1 : 0;

        $params2 = [
            ':tournoi_id' => $teams['tournois_id'],
            ':equipe_id' => $teams['equipe_exterieur_id'],
            ':points' => $points2,
            ':victoires' => $victoires2,
            ':defaites' => $defaites2,
            ':nuls' => $nuls2,
            ':buts_marques' => $score2,
            ':buts_encaisse' => $score1,
            ':saison' => $current_year,
            ':points_update' => $points2,
            ':victoires_update' => $victoires2,
            ':defaites_update' => $defaites2,
            ':nuls_update' => $nuls2,
            ':buts_marques_update' => $score2,
            ':buts_encaisse_update' => $score1
        ];
        
        error_log("Executing away team standings update with params: " . print_r($params2, true));
        
        if (!$stmt->execute($params2)) {
            throw new Exception("Failed to update away team standings: " . print_r($stmt->errorInfo(), true));
        }
    } else {
        error_log("Not updating standings - tournament is not Botola Pro: " . $teams['tournoi_nom']);
    }

    $conn->commit();
    error_log("Successfully completed all updates for match " . $match_id);
    echo json_encode(['success' => true]);

} catch (Exception $e) {
        $conn->rollBack();
    error_log("Error in finish_match.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'error' => $e->getMessage(),
        'details' => 'Check error log for more information'
    ]);
}
?>