<?php
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

    $match_id = $_POST['match_id'];
    $team_id = $_POST['team_id'];
    
    // Check if team is participating in the match
    $stmt = $conn->prepare("
        SELECT 1 FROM matches 
        WHERE id = ? AND (equipe_domicile_id = ? OR equipe_exterieur_id = ?)
    ");
    $stmt->execute([$match_id, $team_id, $team_id]);
    
    if (!$stmt->fetch()) {
        throw new Exception("L'équipe ne participe pas à ce match");
    }

    // Update match score if goals are provided
    if (isset($_POST['goals'])) {
        $stmt = $conn->prepare("
            UPDATE matches 
            SET score_domicile = CASE WHEN equipe_domicile_id = ? THEN ? ELSE score_domicile END,
                score_exterieur = CASE WHEN equipe_exterieur_id = ? THEN ? ELSE score_exterieur END
            WHERE id = ?
        ");
        $stmt->execute([$team_id, $_POST['goals'], $team_id, $_POST['goals'], $match_id]);
    }

    // Update team statistics
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

    $stmt->execute([
        ':match_id' => $match_id,
        ':equipe_id' => $team_id,
        ':passes' => $_POST['passes'] ?? 0,
        ':tirs' => $_POST['tirs'] ?? 0,
        ':corners' => $_POST['corners'] ?? 0,
        ':penalties' => $_POST['penalties'] ?? 0,
        ':coups_franc' => $_POST['coups_franc'] ?? 0,
        ':centres' => $_POST['centres'] ?? 0,
        ':hors_jeu' => $_POST['hors_jeu'] ?? 0
    ]);

    $conn->commit();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    $conn->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?> 