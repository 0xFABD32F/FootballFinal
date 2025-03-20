<?php
session_start();

// Configuration des logs
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', 'php_errors.log');

if (!isset($_SESSION['Type_compte']) || $_SESSION['Type_compte'] !== 'admin_tournoi') {
    header('Location: page_connexion.php');
    exit();
}

include("connexion.php");

$success = false;
$error = null;
$match_id = null;

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['match_id'])) {
    $match_id = $_POST['match_id'];
    
    try {
        // Vérifier si le match existe et appartient à cet admin
        $stmt = $conn->prepare("
            SELECT m.*, 
                   e1.nom as equipe1_nom, 
                   e2.nom as equipe2_nom 
            FROM matches m
            JOIN equipes e1 ON m.equipe_domicile_id = e1.id
            JOIN equipes e2 ON m.equipe_exterieur_id = e2.id
            WHERE m.id = :match_id 
            AND m.id_admin = :admin_id
            AND m.etat = 'prevu'
        ");
        
        $stmt->execute([
            ':match_id' => $match_id,
            ':admin_id' => $_SESSION['ID']
        ]);
        
        $match = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$match) {
            throw new Exception("Match non trouvé ou n'est pas à l'état 'prevu'");
        }
        
        // Mettre à jour l'état du match
        $stmt = $conn->prepare("
            UPDATE matches 
            SET etat = 'en_cours',
                heure_debut_effective = CURRENT_TIME
            WHERE id = :match_id
        ");
        
        if (!$stmt->execute([':match_id' => $match_id])) {
            throw new Exception("Erreur lors de la mise à jour de l'état du match");
        }
        
        $success = true;
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Récupérer tous les matchs prévus pour cet admin
$stmt = $conn->prepare("
    SELECT m.id, m.date_match, m.heure_debut, 
           e1.nom as equipe1_nom, 
           e2.nom as equipe2_nom
    FROM matches m
    JOIN equipes e1 ON m.equipe_domicile_id = e1.id
    JOIN equipes e2 ON m.equipe_exterieur_id = e2.id
    WHERE m.id_admin = :admin_id 
    AND m.etat = 'prevu'
    ORDER BY m.date_match ASC, m.heure_debut ASC
");

$stmt->execute([':admin_id' => $_SESSION['ID']]);
$matches = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forcer le démarrage d'un match</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-md mx-auto bg-white rounded-lg shadow-md p-6">
        <h1 class="text-2xl font-bold mb-6">Forcer le démarrage d'un match</h1>
        
        <?php if ($success): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                Le match a été démarré avec succès! <a href="mes_matchs.php" class="underline">Retour à mes matchs</a>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                Erreur: <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <?php if (empty($matches)): ?>
            <p class="text-gray-700">Aucun match prévu à démarrer.</p>
            <div class="mt-4">
                <a href="mes_matchs.php" class="inline-block bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                    Retour à mes matchs
                </a>
            </div>
        <?php else: ?>
            <form method="POST" class="space-y-4">
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="match_id">
                        Sélectionner un match à démarrer
                    </label>
                    <select name="match_id" id="match_id" class="w-full p-2 border rounded">
                        <?php foreach ($matches as $match): ?>
                            <option value="<?php echo $match['id']; ?>" <?php echo $match_id == $match['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($match['equipe1_nom'] . ' vs ' . $match['equipe2_nom'] . ' (' . $match['date_match'] . ' ' . $match['heure_debut'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="flex justify-between">
                    <button type="submit" class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">
                        Démarrer le match
                    </button>
                    <a href="mes_matchs.php" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">
                        Annuler
                    </a>
                </div>
            </form>
        <?php endif; ?>
    </div>
</body>
</html> 