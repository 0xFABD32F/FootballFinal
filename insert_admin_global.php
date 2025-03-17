<?php
include("connexion.php");

try {
    // Check if admin_global account already exists
    $stmt = $conn->prepare("SELECT COUNT(*) FROM comptes WHERE type_compte = 'admin_global'");
    $stmt->execute();
    $count = $stmt->fetchColumn();

    if ($count == 0) {
        
        $password = '12345';
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $sql = "INSERT INTO comptes (login, password, nom, prenom, type_compte) 
                VALUES (:login, :password, :nom, :prenom, 'admin_global')";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':login' => 'admin_master',
            ':password' => $hashed_password,
            ':nom' => 'Admin',
            ':prenom' => 'Master'
        ]);
        
        
    }
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
