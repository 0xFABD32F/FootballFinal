<?php
include("connexion.php");

try {
    // First, add a temporary column
    $conn->exec("ALTER TABLE Compte ADD COLUMN temp_password VARCHAR(255)");
    
    // Update the temporary column with hashed passwords
    $sql = "SELECT id_compte, password FROM Compte";
    $stmt = $conn->query($sql);
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $hashed = password_hash($row['password'], PASSWORD_DEFAULT);
        $update = $conn->prepare("UPDATE Compte SET temp_password = ? WHERE id_compte = ?");
        $update->execute([$hashed, $row['id_compte']]);
    }
    
    // Replace old password column with new one
    $conn->exec("ALTER TABLE Compte DROP COLUMN password");
    $conn->exec("ALTER TABLE Compte CHANGE temp_password password VARCHAR(255)");
    
    echo "Passwords successfully updated!";
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}

$conn = null;
?>