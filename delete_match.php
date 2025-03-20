<?php
session_start();
header('Content-Type: application/json');

// Configuration des logs
error_reporting(E_ALL);
ini_set('display_errors', 0); 
ini_set('log_errors', 1);
ini_set('error_log', 'php_errors.log');

if (!isset($_SESSION['Type_compte']) || $_SESSION['Type_compte'] !== 'admin_tournoi') {
    error_log("delete_match.php: Unauthorized access attempt");
    http_response_code(403);
    exit(json_encode(['success' => false, 'error' => 'Unauthorized']));
}

include("connexion.php");

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['match_id'])) {
    error_log("delete_match.php: Invalid request - missing match_id");
    http_response_code(400);
    exit(json_encode(['success' => false, 'error' => 'Invalid request']));
}

$match_id = $_POST['match_id'];
error_log("delete_match.php: Attempting to delete match ID: $match_id");

try {
    // Determine if this is the match with the countdown
    $stmt = $conn->prepare("
        SELECT m.id, m.date_match, m.heure_debut, m.etat,
               e1.nom as equipe1_nom, e2.nom as equipe2_nom
        FROM matches m 
        JOIN equipes e1 ON m.equipe_domicile_id = e1.id
        JOIN equipes e2 ON m.equipe_exterieur_id = e2.id
        WHERE m.id_admin = :admin_id
        AND m.etat = 'prevu'
        AND CONCAT(m.date_match, ' ', m.heure_debut) >= NOW()
        ORDER BY m.date_match ASC, m.heure_debut ASC
        LIMIT 1
    ");
    $stmt->execute([':admin_id' => $_SESSION['ID']]);
    $nextMatch = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $isCountdownMatch = false;
    if ($nextMatch && $nextMatch['id'] == $match_id) {
        $isCountdownMatch = true;
        error_log("delete_match.php: The match being deleted is the current countdown match");
    }
    
    $conn->beginTransaction();
    
    // Verify the match belongs to the current admin
    $stmt = $conn->prepare("
        SELECT m.id, m.etat, e1.nom as equipe1_nom, e2.nom as equipe2_nom 
        FROM matches m
        JOIN equipes e1 ON m.equipe_domicile_id = e1.id
        JOIN equipes e2 ON m.equipe_exterieur_id = e2.id
        WHERE m.id = ? AND m.id_admin = ?
    ");
    $stmt->execute([$match_id, $_SESSION['ID']]);
    $match = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$match) {
        error_log("delete_match.php: Match not found or not owned by current admin");
        throw new Exception("Match not found or not owned by you");
    }
    
    // Check if match is in progress
    if ($match['etat'] === 'en_cours') {
        error_log("delete_match.php: Cannot delete a match in progress");
        throw new Exception("Impossible de supprimer un match en cours");
    }
    
    error_log("delete_match.php: Deleting match: " . $match['equipe1_nom'] . " vs " . $match['equipe2_nom']);
    
    // Delete related data first (foreign key constraints)
    // 1. Delete player statistics
    $stmt = $conn->prepare("DELETE FROM joueur_statistics WHERE match_id = ?");
    $stmt->execute([$match_id]);
    error_log("delete_match.php: Deleted player statistics");
    
    // 2. Delete team statistics
    $stmt = $conn->prepare("DELETE FROM equipe_statistics WHERE match_id = ?");
    $stmt->execute([$match_id]);
    error_log("delete_match.php: Deleted team statistics");
    
    // 3. Delete participations
    $stmt = $conn->prepare("DELETE FROM participations WHERE match_id = ?");
    $stmt->execute([$match_id]);
    error_log("delete_match.php: Deleted participations");
    
    // 4. Delete match events
    $stmt = $conn->prepare("DELETE FROM match_events WHERE match_id = ?");
    $stmt->execute([$match_id]);
    error_log("delete_match.php: Deleted match events");
    
    // 5. Finally delete the match
    $stmt = $conn->prepare("DELETE FROM matches WHERE id = ?");
    $stmt->execute([$match_id]);
    error_log("delete_match.php: Deleted match");
    
    $conn->commit();
    
    // If we deleted the countdown match, find the next one
    if ($isCountdownMatch) {
        $stmt = $conn->prepare("
            SELECT m.id
            FROM matches m 
            WHERE m.id_admin = :admin_id
            AND m.etat = 'prevu'
            AND CONCAT(m.date_match, ' ', m.heure_debut) >= NOW()
            ORDER BY m.date_match ASC, m.heure_debut ASC
            LIMIT 1
        ");
        $stmt->execute([':admin_id' => $_SESSION['ID']]);
        $newNextMatch = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($newNextMatch) {
            error_log("delete_match.php: New countdown match ID: " . $newNextMatch['id']);
        } else {
            error_log("delete_match.php: No more upcoming matches");
        }
    }
    
    echo json_encode([
        'success' => true, 
        'was_countdown_match' => $isCountdownMatch
    ]);
    
} catch (Exception $e) {
    $conn->rollBack();
    error_log("delete_match.php: Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?> 