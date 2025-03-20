<?php
// Script pour créer la table des commentaires

include("connexion.php");

try {
    $sql = file_get_contents('create_comments_table.sql');
    $conn->exec($sql);
    echo "Table de commentaires créée avec succès!";
} catch (PDOException $e) {
    echo "Erreur lors de la création de la table: " . $e->getMessage();
}
?> 