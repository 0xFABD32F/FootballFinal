<?php
include("connexion.php");

if (isset($_GET['equipe_id'])) {
    $stmt = $conn->prepare("SELECT id_joueur, Nom, Prenom, position FROM Joueur WHERE id_equipe = ?");
    $stmt->execute([$_GET['equipe_id']]);
    $players = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    header('Content-Type: application/json');
    echo json_encode($players);
}
?>