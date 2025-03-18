<?php
session_start();
if (!isset($_SESSION['Type_compte']) || $_SESSION['Type_compte'] !== 'admin_tournoi') {
    http_response_code(403);
    exit('Unauthorized');
}

include("connexion.php");

if (!isset($_GET['id'])) {
    http_response_code(400);
    exit('Missing match ID');
}

try {
    // Get match details
    $stmt = $conn->prepare("
        SELECT m.equipe_domicile_id, m.equipe_exterieur_id,
               e1.nom as equipe1_nom, e2.nom as equipe2_nom
        FROM matches m
        JOIN equipes e1 ON m.equipe_domicile_id = e1.id
        JOIN equipes e2 ON m.equipe_exterieur_id = e2.id
        WHERE m.id = ?
    ");
    $stmt->execute([$_GET['id']]);
    $match = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$match) {
        throw new Exception("Match non trouvé");
    }

    // Get players for both teams
    $stmt = $conn->prepare("
        SELECT p.joueur_id as id, j.nom, j.prenom, j.position, p.titulaire, p.equipe_id
        FROM participations p
        JOIN joueurs j ON p.joueur_id = j.id
        WHERE p.match_id = ?
    ");
    $stmt->execute([$_GET['id']]);
    $players = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Organize players by team
    $team1Players = array_filter($players, function($player) use ($match) {
        return $player['equipe_id'] == $match['equipe_domicile_id'];
    });
    $team2Players = array_filter($players, function($player) use ($match) {
        return $player['equipe_id'] == $match['equipe_exterieur_id'];
    });

    // Get existing statistics
    $stmt = $conn->prepare("
        SELECT * FROM joueur_statistics 
        WHERE match_id = ?
    ");
    $stmt->execute([$_GET['id']]);
    $stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Add statistics to players
    foreach ($stats as $stat) {
        foreach ($players as &$player) {
            if ($player['id'] == $stat['joueur_id']) {
                $player['dribbles_reussis'] = $stat['dribbles_reussis'];
                $player['interceptions'] = $stat['interceptions'];
                $player['passes_decisives'] = $stat['passes_decisives'];
                $player['tirs_cadres'] = $stat['tirs_cadres'];
                $player['fautes'] = $stat['fautes'];
                break;
            }
        }
    }

    // Set default values for players without statistics
    foreach ($players as &$player) {
        if (!isset($player['dribbles_reussis'])) {
            $player['dribbles_reussis'] = 0;
            $player['interceptions'] = 0;
            $player['passes_decisives'] = 0;
            $player['tirs_cadres'] = 0;
            $player['fautes'] = 0;
        }
    }

    header('Content-Type: application/json');
    echo json_encode([
        'team1' => array_values($team1Players),
        'team2' => array_values($team2Players)
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?> 