<?php
session_start();
header('Content-Type: application/json');

// Configuration des logs
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', 'php_errors.log');

if (!isset($_SESSION['Type_compte']) || $_SESSION['Type_compte'] !== 'admin_tournoi') {
    error_log("start_match.php: Unauthorized access attempt");
    http_response_code(403);
    exit(json_encode(['success' => false, 'error' => 'Unauthorized']));
}

include("connexion.php");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    error_log("start_match.php: Method not allowed: " . $_SERVER['REQUEST_METHOD']);
    http_response_code(405);
    exit(json_encode(['success' => false, 'error' => 'Method not allowed']));
}

try {
    // Log les données reçues
    error_log("start_match.php: Received data: " . print_r($_POST, true));
    
    // Get the match ID from POST data
    $match_id = $_POST['match_id'] ?? null;
    
    if (!$match_id) {
        error_log("start_match.php: Missing match_id parameter");
        throw new Exception("Missing match_id parameter");
    }
    
    error_log("start_match.php: Processing match ID: $match_id for admin ID: " . $_SESSION['ID']);
    
    // Verify match belongs to this admin and is in 'prevu' state
    $stmt = $conn->prepare("
        SELECT id FROM matches 
        WHERE id = :match_id 
        AND id_admin = :admin_id
        AND etat = 'prevu'
    ");
    
    $stmt->execute([
        ':match_id' => $match_id,
        ':admin_id' => $_SESSION['ID']
    ]);
    
    $match = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$match) {
        error_log("start_match.php: Match not found or not in 'prevu' state. Match ID: $match_id, Admin ID: " . $_SESSION['ID']);
        throw new Exception("Match not found or not in 'prevu' state");
    }
    
    error_log("start_match.php: Match found, updating status to 'en_cours'");
    
    // Update match status to 'en_cours'
    $stmt = $conn->prepare("
        UPDATE matches 
        SET etat = 'en_cours',
            heure_debut_effective = CURRENT_TIME
        WHERE id = :match_id
    ");
    
    $result = $stmt->execute([':match_id' => $match_id]);
    
    if (!$result) {
        error_log("start_match.php: Failed to update match status. Error: " . print_r($stmt->errorInfo(), true));
        throw new Exception("Failed to update match status: " . $stmt->errorInfo()[2]);
    }
    
    $rowCount = $stmt->rowCount();
    error_log("start_match.php: Update successful. Rows affected: $rowCount");
    
    echo json_encode([
        'success' => true,
        'message' => 'Match status updated to en_cours',
        'rows_affected' => $rowCount
    ]);
    
} catch (Exception $e) {
    error_log("start_match.php: Exception: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} 