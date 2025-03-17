<?php
session_start();
include("connexion.php");

$error = null;
$success = null;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || 
        $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Invalid session, please try again";
    } else {
        // Validate input
        $required_fields = ['login', 'password', 'confirm_password', 'nom', 'prenom'];
        $missing_fields = array_filter($required_fields, function($field) {
            return !isset($_POST[$field]) || empty($_POST[$field]);
        });

        if (!empty($missing_fields)) {
            $error = "All fields are required";
        } else {
            $login = $_POST['login'];
            $password = $_POST['password'];
            $confirm_password = $_POST['confirm_password'];
            $nom = $_POST['nom'];
            $prenom = $_POST['prenom'];
            
            // Validate password match
            if ($password !== $confirm_password) {
                $error = "Passwords do not match";
            } else {
                try {
                    // Check if login already exists
                    $check = $conn->prepare("SELECT COUNT(*) FROM comptes WHERE login = ?");
                    $check->execute([$login]);
                    if ($check->fetchColumn() > 0) {
                        $error = "Login already exists";
                    } else {
                        // Create new account
                        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                        $sql = "INSERT INTO comptes (login, password, nom, prenom, type_compte) 
                                VALUES (:login, :password, :nom, :prenom, 'user')";
                        $stmt = $conn->prepare($sql);
                        $stmt->execute([
                            ':login' => $login,
                            ':password' => $hashed_password,
                            ':nom' => $nom,
                            ':prenom' => $prenom
                        ]);
                        $success = "Account created successfully";
                    }
                } catch(PDOException $e) {
                    $error = "Error creating account: " . $e->getMessage();
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .container {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            width: 300px;
        }
        label {
            display: block;
            margin-bottom: 5px;
        }
        input[type="text"], input[type="password"] {
            width: 100%;
            padding: 8px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        input[type="submit"] {
            background-color: #4CAF50;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            width: 100%;
        }
        input[type="submit"]:hover {
            background-color: #45a049;
        }
        .error {
            color: red;
            margin-bottom: 10px;
        }
        .success {
            color: green;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Create Account</h2>
        
        <?php if($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if($success): ?>
            <div class="success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <form method="POST" action="ajout_compte.php">
            <label for="login">Login:</label>
            <input type="text" id="login" name="login" required>
            
            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required>
            
            <label for="confirm_password">Confirm Password:</label>
            <input type="password" id="confirm_password" name="confirm_password" required>
            
            <label for="nom">Last Name:</label>
            <input type="text" id="nom" name="nom" required>
            
            <label for="prenom">First Name:</label>
            <input type="text" id="prenom" name="prenom" required>
            
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); ?>">
            
            <input type="submit" value="Create Account">
        </form>
        <p><a href="page_connexion.php">Already have an account? Login here</a></p>
    </div>
</body>
</html>