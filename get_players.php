<?php
session_start();
if (!isset($_SESSION['Type_compte']) || $_SESSION['Type_compte'] !== 'admin_tournoi') {
    http_response_code(403);
    exit('Unauthorized');
}

include("connexion.php");

if (!isset($_GET['equipe_id'])) {
    http_response_code(400);
    exit('Missing equipe_id parameter');
}

try {
    $stmt = $conn->prepare("SELECT id, nom, prenom, position 
                           FROM joueurs 
                           WHERE equipe_id = :equipe_id 
                           AND (date_fin IS NULL OR date_fin >= CURRENT_DATE)");
    $stmt->execute([':equipe_id' => $_GET['equipe_id']]);
    $players = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    header('Content-Type: application/json');
    echo json_encode($players);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}