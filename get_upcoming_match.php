<?php
session_start();
header('Content-Type: application/json');

// Configuration des logs
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', 'php_errors.log');

if (!isset($_SESSION['Type_compte']) || $_SESSION['Type_compte'] !== 'admin_tournoi') {
    error_log("get_upcoming_match.php: Unauthorized access attempt");
    http_response_code(403);
    exit(json_encode(['success' => false, 'error' => 'Unauthorized']));
}

include("connexion.php");

try {
    error_log("get_upcoming_match.php: Fetching upcoming matches for admin ID: " . $_SESSION['ID']);
    
    // Get the next upcoming match for this admin
    $stmt = $conn->prepare("
        SELECT m.id, m.date_match, m.heure_debut, m.equipe_domicile_id, m.equipe_exterieur_id,
               e1.nom as equipe1_nom, e2.nom as equipe2_nom, s.nom as stade_nom
        FROM matches m
        JOIN equipes e1 ON m.equipe_domicile_id = e1.id
        JOIN equipes e2 ON m.equipe_exterieur_id = e2.id
        JOIN stades s ON m.stade_id = s.id
        WHERE m.id_admin = :admin_id 
        AND m.etat = 'prevu'
        AND CONCAT(m.date_match, ' ', m.heure_debut) >= NOW()
        ORDER BY m.date_match ASC, m.heure_debut ASC
        LIMIT 1
    ");
    
    $stmt->execute([':admin_id' => $_SESSION['ID']]);
    $match = $stmt->fetch(PDO::FETCH_ASSOC);
    
    error_log("get_upcoming_match.php: Query executed. Found match: " . ($match ? 'Yes, ID: ' . $match['id'] : 'No'));
    
    if ($match) {
        // Vérifier si le match est sur le point de commencer pour faciliter les tests
        $matchDateTime = strtotime($match['date_match'] . ' ' . $match['heure_debut']);
        $now = time();
        $diffSeconds = $matchDateTime - $now;
        
        error_log("get_upcoming_match.php: Match time: " . date('Y-m-d H:i:s', $matchDateTime) . 
                  ", Current time: " . date('Y-m-d H:i:s', $now) . 
                  ", Diff in seconds: " . $diffSeconds);
        
        echo json_encode([
            'success' => true,
            'match' => $match,
            'debug' => [
                'match_time' => date('Y-m-d H:i:s', $matchDateTime),
                'current_time' => date('Y-m-d H:i:s', $now),
                'diff_seconds' => $diffSeconds
            ]
        ]);
    } else {
        error_log("get_upcoming_match.php: No upcoming matches found");
        echo json_encode([
            'success' => true,
            'match' => null
        ]);
    }
} catch (Exception $e) {
    error_log("get_upcoming_match.php: Exception: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} 