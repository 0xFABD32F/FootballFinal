<?php
session_start();
if (!isset($_SESSION['Type_compte']) || $_SESSION['Type_compte'] !== 'admin_tournoi') {
    header('Location: page_connexion.php');
    exit();
}

include("connexion.php");

// Corriger les requêtes pour correspondre au schéma
$stades = $conn->query("SELECT id, nom, ville, capacite FROM stades")->fetchAll();
$equipes = $conn->query("SELECT id, nom, ville FROM equipes")->fetchAll();
$arbitres = $conn->query("SELECT id, nom, grade FROM arbitres")->fetchAll();
$tournois = $conn->query("SELECT id, nom FROM types_tournois")->fetchAll();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $error = null;
    
    if ($_POST['equipe_1'] === $_POST['equipe_2']) {
        $error = "Les équipes ne peuvent pas être identiques";
    }
    
    if (count($_POST['joueurs_equipe1'] ?? []) > 11 || count($_POST['joueurs_equipe2'] ?? []) > 11) {
        $error = "Une équipe ne peut pas avoir plus de 11 joueurs";
    }
    
    if (!$error) {
        try {
            $conn->beginTransaction();
            
            // Get the active tournament for the selected type
            $stmt = $conn->prepare("SELECT id FROM tournois 
                                  WHERE type_id = :type_id 
                                  AND (date_fin IS NULL OR date_fin >= CURRENT_DATE)
                                  LIMIT 1");
            $stmt->execute([':type_id' => $_POST['tournoi']]);
            $tournoi = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$tournoi) {
                // Create a new tournament for this type
                $stmt = $conn->prepare("INSERT INTO tournois (type_id, date_debut, date_fin) 
                                      VALUES (:type_id, CURRENT_DATE, DATE_ADD(CURRENT_DATE, INTERVAL 1 YEAR))");
                $stmt->execute([':type_id' => $_POST['tournoi']]);
                $tournoi = ['id' => $conn->lastInsertId()];
            }
            
            // Insert match with the found tournament ID
            $stmt = $conn->prepare("INSERT INTO matches (
                tournois_id, stade_id, equipe_domicile_id, equipe_exterieur_id, 
                id_admin, arbitre_id, heure_debut, date_match, etat
            ) VALUES (
                :tournoi, :stade, :equipe1, :equipe2, 
                :compte, :arbitre, :heure, :date, 'prevu'
            )");

            $stmt->execute([
                ':tournoi' => $tournoi['id'],
                ':stade' => $_POST['stade'],
                ':equipe1' => $_POST['equipe_1'],
                ':equipe2' => $_POST['equipe_2'],
                ':compte' => $_SESSION['ID'],
                ':arbitre' => $_POST['arbitre'],
                ':heure' => $_POST['heure_debut'],
                ':date' => $_POST['date_debut']
            ]);
            
            $match_id = $conn->lastInsertId();
            
            // Corriger la requête d'insertion des participations
            $stmt = $conn->prepare("INSERT INTO participations (match_id, equipe_id, joueur_id, titulaire) 
                                  VALUES (:match, :equipe, :joueur, true)");
            
            // Pour l'équipe 1
            foreach ($_POST['joueurs_equipe1'] as $joueur) {
                $stmt->execute([
                    ':match' => $match_id,
                    ':equipe' => $_POST['equipe_1'],
                    ':joueur' => $joueur
                ]);
            }
            
            // Pour l'équipe 2
            foreach ($_POST['joueurs_equipe2'] as $joueur) {
                $stmt->execute([
                    ':match' => $match_id,
                    ':equipe' => $_POST['equipe_2'],
                    ':joueur' => $joueur
                ]);
            }
            
            $conn->commit();
            $success = "Match créé avec succès";
        } catch (PDOException $e) {
            $conn->rollBack();
            $error = "Erreur lors de la création du match: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajouter un Match</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body class="bg-gray-100">
    <nav class="bg-white shadow-lg mb-8">
        <div class="container mx-auto px-4">
            <div class="flex justify-between items-center py-4">
                <div class="flex space-x-4">
                    <a href="ajout_match.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'ajout_match.php' ? 'bg-blue-500 text-white' : 'text-gray-700 hover:bg-blue-500 hover:text-white'; ?> px-3 py-2 rounded-md text-sm font-medium">
                        Ajouter Match
                    </a>
                    <a href="mes_matchs.php" class="text-gray-700 hover:bg-blue-500 hover:text-white px-3 py-2 rounded-md text-sm font-medium">
                        Voir Mes Matchs
                    </a>
                </div>
                <a href="deconnexion.php" class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600">Déconnexion</a>
            </div>
        </div>
    </nav>
    <div class="container mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold mb-8 text-center">Ajouter un Match</h1>
        
        <?php if (isset($error)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($success)): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="bg-white shadow-md rounded px-8 pt-6 pb-8 mb-4">
            <div class="grid grid-cols-2 gap-4">
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="tournoi">
                        Type de Tournoi
                    </label>
                    <select name="tournoi" id="tournoi" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                        <option value="">Sélectionner un type de tournoi</option>
                        <?php foreach ($tournois as $tournoi): ?>
                            <option value="<?php echo $tournoi['id']; ?>">
                                <?php echo htmlspecialchars($tournoi['nom']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="stade">
                        Stade
                    </label>
                    <select name="stade" id="stade" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                        <?php foreach ($stades as $stade): ?>
                            <option value="<?php echo $stade['id']; ?>">
                                <?php echo htmlspecialchars($stade['nom']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="arbitre">
                        Arbitre
                    </label>
                    <select name="arbitre" id="arbitre" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                        <?php foreach ($arbitres as $arbitre): ?>
                            <option value="<?php echo $arbitre['id']; ?>">
                                <?php echo htmlspecialchars($arbitre['nom'] . ' (' . $arbitre['grade'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">
                        Date et Heure
                    </label>
                    <div class="grid grid-cols-2 gap-2">
                        <input type="date" name="date_debut" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                        <input type="time" name="heure_debut" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-8 mt-8">
                <!-- Équipe 1 -->
                <div>
                    <h3 class="text-lg font-bold mb-4">Équipe 1</h3>
                    <select name="equipe_1" id="equipe_1" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline mb-4">
                        <option value="">Sélectionner une équipe</option>
                        <?php foreach ($equipes as $equipe): ?>
                            <option value="<?php echo $equipe['id']; ?>">
                                <?php echo htmlspecialchars($equipe['nom']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div id="joueurs_equipe1" class="space-y-2"></div>
                </div>

                <!-- Équipe 2 -->
                <div>
                    <h3 class="text-lg font-bold mb-4">Équipe 2</h3>
                    <select name="equipe_2" id="equipe_2" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline mb-4">
                        <option value="">Sélectionner une équipe</option>
                        <?php foreach ($equipes as $equipe): ?>
                            <option value="<?php echo $equipe['id']; ?>">
                                <?php echo htmlspecialchars($equipe['nom']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div id="joueurs_equipe2" class="space-y-2"></div>
                </div>
            </div>

            <div class="flex items-center justify-center mt-8">
                <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                    Créer le match
                </button>
            </div>
        </form>
    </div>

    <script>
        function loadPlayers(equipeId, targetDiv) {
            if (!equipeId) return;
            
            $.ajax({
                url: 'get_players.php',
                data: { equipe_id: equipeId },
                success: function(players) {
                    const container = $(`#${targetDiv}`);
                    container.empty();
                    
                    players.forEach(player => {
                        container.append(`
                            <label class="flex items-center space-x-3">
                                <input type="checkbox" name="${targetDiv}[]" value="${player.id}" class="form-checkbox h-5 w-5 text-blue-600">
                                <span class="text-gray-700">${player.nom} ${player.prenom} (${player.position})</span>
                            </label>
                        `);
                    });
                }
            });
        }

        $('#equipe_1').change(function() {
            loadPlayers($(this).val(), 'joueurs_equipe1');
        });

        $('#equipe_2').change(function() {
            loadPlayers($(this).val(), 'joueurs_equipe2');
        });

        $('form').submit(function(e) {
            const equipe1Players = $('input[name="joueurs_equipe1[]"]:checked').length;
            const equipe2Players = $('input[name="joueurs_equipe2[]"]:checked').length;
            
            if (equipe1Players > 11 || equipe2Players > 11) {
                e.preventDefault();
                alert('Une équipe ne peut pas avoir plus de 11 joueurs');
            }
            
            if ($('#equipe_1').val() === $('#equipe_2').val()) {
                e.preventDefault();
                alert('Les équipes ne peuvent pas être identiques');
            }
        });
    </script>
</body>
</html>