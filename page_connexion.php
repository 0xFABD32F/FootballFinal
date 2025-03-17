<?php
session_start();
if(isset($_SESSION['Type_compte'])){ 
    header("Location: Acceuil.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Add CSRF protection
    if (!isset($_SESSION['csrf_token']) || 
        !isset($_POST['csrf_token']) || 
        $_SESSION['csrf_token'] !== $_POST['csrf_token']) {
        $error = "Invalid session, please try again";
        exit();
    }

    include("connexion.php");
    
    if (!isset($_POST['login']) || !isset($_POST['password'])) {
        $error = "Please provide both login and password";
    } else {
        $login = $_POST['login'];
        $pass = $_POST['password'];
        include("insert_admin_global.php");
        
        try {
            $sql = "SELECT id_compte, type_compte, password FROM comptes WHERE login=:log";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':log', $login);
            $stmt->execute(); 
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if($result === false){
                $error = "Compte inexistant";
            } else {
                
                if (password_verify($pass, $result['password'])) {
                    $_SESSION['Type_compte'] = $result['type_compte'];                    
                    $_SESSION['ID'] = $result['id_compte'];
                    $_SESSION['current_page'] = $_SERVER['REQUEST_URI'];

                    switch($_SESSION['Type_compte']){
                        case 'admin_tournoi':
                            header("Location: ajout_match.php");
                            break;
                        case 'admin_global':
                            header("Location: admin_global.php");
                            break;
                        case 'user':
                            header("Location: Acceuil.php");
                            break;
                        default:
                            header("Location: Acceuil.php");
                            
                            break;
                    }
                    exit();
                } else {
                    $error = "Mot de passe invalide";
                }
            }
        } catch(PDOException $e) {
            $error = "Error: " . $e->getMessage();
        }
        
        $conn = null;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion</title>
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
            text-align: center;
        }
        h2 {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            text-align: left;
        }
        input[type="text"], input[type="password"] {
            width: 100%;
            padding: 10px;
            margin-bottom: 20px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        input[type="submit"] {
            background-color: #4CAF50;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        input[type="submit"]:hover {
            background-color: #45a049;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Connexion</h2>
        

    <?php if(isset($error)): ?>
    <div style="color: red; margin-bottom: 10px;">
        <?php echo htmlspecialchars($error); ?>
    </div>
<?php endif; ?>
        <form action="page_connexion.php" method="post">
            <label for="login">Login:</label>
            <input type="text" id="login" name="login" required><br><br>
            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required><br><br>
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); ?>">
            <input type="submit" value="Se connecter">
        </form>
    </div>
</body>
</html>