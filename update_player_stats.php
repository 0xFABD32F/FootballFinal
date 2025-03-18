<?php
session_start();
if (!isset($_SESSION['Type_compte']) || $_SESSION['Type_compte'] !== 'admin_tournoi') {
    http_response_code(403);
    exit('Unauthorized');
}

include("connexion.php");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed');
}

header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['match_id']) || !isset($data['players'])) {
        throw new Exception('Missing required data');
    }

    $conn->beginTransaction();

    // Verify match exists and user has access
    $stmt = $conn->prepare("
        SELECT id FROM matches 
        WHERE id = ? AND id_admin = ?
    ");
    $stmt->execute([$data['match_id'], $_SESSION['ID']]);
    if (!$stmt->fetch()) {
        throw new Exception('Match non trouvé ou accès non autorisé');
    }

    // Update or insert player statistics
    $stmt = $conn->prepare("
        INSERT INTO joueur_statistics (
            match_id, joueur_id, dribbles_reussis, interceptions, 
            passes_decisives, tirs_cadres, fautes
        ) VALUES (
            :match_id, :joueur_id, :dribbles_reussis, :interceptions,
            :passes_decisives, :tirs_cadres, :fautes
        ) ON DUPLICATE KEY UPDATE
            dribbles_reussis = VALUES(dribbles_reussis),
            interceptions = VALUES(interceptions),
            passes_decisives = VALUES(passes_decisives),
            tirs_cadres = VALUES(tirs_cadres),
            fautes = VALUES(fautes)
    ");

    foreach ($data['players'] as $playerId => $stats) {
        $stmt->execute([
            ':match_id' => $data['match_id'],
            ':joueur_id' => $playerId,
            ':dribbles_reussis' => $stats['dribbles_reussis'] ?? 0,
            ':interceptions' => $stats['interceptions'] ?? 0,
            ':passes_decisives' => $stats['passes_decisives'] ?? 0,
            ':tirs_cadres' => $stats['tirs_cadres'] ?? 0,
            ':fautes' => $stats['fautes'] ?? 0
        ]);
    }

    $conn->commit();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?> 