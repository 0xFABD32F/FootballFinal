<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['Type_compte']) || $_SESSION['Type_compte'] !== 'admin_tournoi') {
    header('Location: page_connexion.php');
    exit();
}

include("connexion.php");

// Fonction pour obtenir le nom de la table matches
function getTableName($conn, $table) {
    $sql = "SHOW TABLES LIKE '$table'";
    $result = $conn->query($sql);
    if ($result->rowCount() > 0) {
        return $table;
    }
    return null;
}

// Fonction pour obtenir la structure de la table matches
function getTableStructure($conn, $table) {
    $structure = [];
    $sql = "DESCRIBE $table";
    $result = $conn->query($sql);
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        $structure[] = $row;
    }
    return $structure;
}

// Vérifier si la colonne heure_debut_effective existe
function checkColumnExists($structure, $column) {
    foreach ($structure as $field) {
        if ($field['Field'] === $column) {
            return true;
        }
    }
    return false;
}

// Récupérer tous les matchs pour cet admin
$sql = "
    SELECT m.*, 
           e1.nom as equipe1_nom, 
           e2.nom as equipe2_nom,
           s.nom as stade_nom,
           a.nom as arbitre_nom
    FROM matches m
    JOIN equipes e1 ON m.equipe_domicile_id = e1.id
    JOIN equipes e2 ON m.equipe_exterieur_id = e2.id
    JOIN stades s ON m.stade_id = s.id
    JOIN arbitres a ON m.arbitre_id = a.id
    WHERE m.id_admin = :admin_id 
    ORDER BY m.date_match DESC, m.heure_debut DESC
";

$stmt = $conn->prepare($sql);
$stmt->execute([':admin_id' => $_SESSION['ID']]);
$matches = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Vérifier la structure de la table
$matchesTable = getTableName($conn, 'matches');
$tableStructure = $matchesTable ? getTableStructure($conn, $matchesTable) : [];
$hasHeureDebutEffective = checkColumnExists($tableStructure, 'heure_debut_effective');

// Exécuter les requêtes SQL pour vérifier l'état
$checkQueries = [
    "SELECT COUNT(*) as count FROM matches WHERE etat = 'prevu'" => "Matchs prévus",
    "SELECT COUNT(*) as count FROM matches WHERE etat = 'en_cours'" => "Matchs en cours",
    "SELECT COUNT(*) as count FROM matches WHERE etat = 'termine'" => "Matchs terminés",
    "SELECT COUNT(*) as count FROM matches WHERE id_admin = " . $_SESSION['ID'] => "Matchs pour cet admin",
    "SELECT CONCAT(DATE_FORMAT(NOW(), '%Y-%m-%d %H:%i:%s'), ' (', @@system_time_zone, ')') as server_time" => "Heure du serveur",
];

$checkResults = [];
foreach ($checkQueries as $query => $label) {
    $stmt = $conn->query($query);
    $checkResults[$label] = $stmt->fetch(PDO::FETCH_ASSOC);
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vérification des matchs</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-5xl mx-auto">
        <h1 class="text-3xl font-bold mb-6">Vérification de l'état des matchs</h1>
        
        <div class="mb-8">
            <h2 class="text-xl font-semibold mb-3">Informations système</h2>
            <div class="bg-white rounded-lg shadow-md p-4">
                <p><strong>Table 'matches' trouvée:</strong> <?php echo $matchesTable ? 'Oui' : 'Non'; ?></p>
                <p><strong>Colonne 'heure_debut_effective' présente:</strong> <?php echo $hasHeureDebutEffective ? 'Oui' : 'Non'; ?></p>
                
                <h3 class="font-semibold mt-4 mb-2">Statistiques de la base de données:</h3>
                <ul class="list-disc pl-5">
                    <?php foreach ($checkResults as $label => $result): ?>
                        <li><strong><?php echo $label; ?>:</strong> <?php echo isset($result['count']) ? $result['count'] : (isset($result['server_time']) ? $result['server_time'] : 'N/A'); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        
        <h2 class="text-xl font-semibold mb-3">Vos matchs (<?php echo count($matches); ?>)</h2>
        
        <div class="overflow-x-auto">
            <table class="w-full bg-white rounded-lg shadow-md">
                <thead class="bg-gray-200">
                    <tr>
                        <th class="px-4 py-2 text-left">ID</th>
                        <th class="px-4 py-2 text-left">Équipes</th>
                        <th class="px-4 py-2 text-left">Date / Heure</th>
                        <th class="px-4 py-2 text-left">État</th>
                        <th class="px-4 py-2 text-left">Heure effective</th>
                        <th class="px-4 py-2 text-left">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($matches)): ?>
                        <tr>
                            <td colspan="6" class="px-4 py-2 text-center text-gray-500">Aucun match trouvé</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($matches as $match): ?>
                            <tr class="border-t">
                                <td class="px-4 py-2"><?php echo $match['id']; ?></td>
                                <td class="px-4 py-2">
                                    <?php echo htmlspecialchars($match['equipe1_nom'] . ' vs ' . $match['equipe2_nom']); ?>
                                </td>
                                <td class="px-4 py-2">
                                    <?php echo $match['date_match']; ?> à <?php echo $match['heure_debut']; ?>
                                </td>
                                <td class="px-4 py-2">
                                    <?php if ($match['etat'] === 'prevu'): ?>
                                        <span class="bg-yellow-100 text-yellow-800 px-2 py-1 rounded-full text-xs font-semibold">Prévu</span>
                                    <?php elseif ($match['etat'] === 'en_cours'): ?>
                                        <span class="bg-blue-100 text-blue-800 px-2 py-1 rounded-full text-xs font-semibold">En cours</span>
                                    <?php elseif ($match['etat'] === 'termine'): ?>
                                        <span class="bg-green-100 text-green-800 px-2 py-1 rounded-full text-xs font-semibold">Terminé</span>
                                    <?php else: ?>
                                        <span class="bg-gray-100 text-gray-800 px-2 py-1 rounded-full text-xs font-semibold"><?php echo $match['etat']; ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-2">
                                    <?php echo $match['heure_debut_effective'] ?? 'Non définie'; ?>
                                </td>
                                <td class="px-4 py-2">
                                    <?php if ($match['etat'] === 'prevu'): ?>
                                        <a href="force_start_match.php?match_id=<?php echo $match['id']; ?>" class="text-blue-500 hover:underline">Forcer le démarrage</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <div class="mt-6">
            <a href="mes_matchs.php" class="inline-block bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                Retour à mes matchs
            </a>
        </div>
    </div>
</body>
</html> 