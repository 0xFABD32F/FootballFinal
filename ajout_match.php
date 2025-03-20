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
<html lang="fr" class="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajouter un Match</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        primary: {"50":"#eff6ff","100":"#dbeafe","200":"#bfdbfe","300":"#93c5fd","400":"#60a5fa","500":"#3b82f6","600":"#2563eb","700":"#1d4ed8","800":"#1e40af","900":"#1e3a8a","950":"#172554"}
                    }
                }
            }
        }
    </script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="js/countdown.js"></script>
    <script src="js/darkmode.js"></script>
</head>
<body class="bg-gray-100 dark:bg-gray-900 dark:text-white transition-colors duration-200">
    <nav class="bg-white dark:bg-gray-800 shadow-lg mb-8 transition-colors duration-200">
        <div class="container mx-auto px-4">
            <div class="flex justify-between items-center py-4">
                <div class="flex items-center space-x-8">
                    <div class="flex space-x-4">
                        <a href="publications.php" class="flex items-center text-gray-700 dark:text-gray-300 hover:bg-blue-500 hover:text-white px-4 py-2 rounded-md text-sm font-medium transition-colors duration-200">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                            Ajouter Publication
                        </a>
                        <a href="ajout_match.php" class="flex items-center bg-blue-500 text-white px-4 py-2 rounded-md text-sm font-medium shadow-md hover:bg-blue-600 transition-colors duration-200">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                            </svg>
                            Ajouter Match
                        </a>
                        <a href="mes_matchs.php" class="flex items-center text-gray-700 dark:text-gray-300 hover:bg-blue-500 hover:text-white px-4 py-2 rounded-md text-sm font-medium transition-colors duration-200">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                            Voir Mes Matchs
                        </a>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <button id="navbar-dark-mode-toggle" class="flex items-center p-2 rounded-md bg-gray-200 dark:bg-gray-700 transition-colors duration-200" aria-label="Toggle Dark Mode">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-800 dark:text-yellow-300 hidden dark:block" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-800 dark:text-yellow-300 block dark:hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                        </svg>
                    </button>
                    <a href="deconnexion.php" class="flex items-center bg-red-500 text-white px-4 py-2 rounded-md hover:bg-red-600 transition-colors duration-200">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                        Déconnexion
                    </a>
                </div>
            </div>
        </div>
    </nav>
    <div class="container mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold mb-8 text-center text-gray-900 dark:text-white transition-colors duration-200">Ajouter un Match</h1>
        
        <?php if (isset($error)): ?>
            <div class="bg-red-100 dark:bg-red-900 border border-red-400 dark:border-red-700 text-red-700 dark:text-red-300 px-4 py-3 rounded mb-4 transition-colors duration-200">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($success)): ?>
            <div class="bg-green-100 dark:bg-green-900 border border-green-400 dark:border-green-700 text-green-700 dark:text-green-300 px-4 py-3 rounded mb-4 transition-colors duration-200">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="bg-white dark:bg-gray-800 shadow-md rounded px-8 pt-6 pb-8 mb-4 transition-colors duration-200">
            <div class="grid grid-cols-2 gap-4">
                <div class="mb-4">
                    <label class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2 transition-colors duration-200" for="tournoi">
                        Type de Tournoi
                    </label>
                    <select name="tournoi" id="tournoi" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 dark:text-white dark:bg-gray-700 dark:border-gray-600 leading-tight focus:outline-none focus:shadow-outline transition-colors duration-200">
                        <option value="">Sélectionner un type de tournoi</option>
                        <?php foreach ($tournois as $tournoi): ?>
                            <option value="<?php echo $tournoi['id']; ?>">
                                <?php echo htmlspecialchars($tournoi['nom']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-4">
                    <label class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2 transition-colors duration-200" for="stade">
                        Stade
                    </label>
                    <select name="stade" id="stade" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 dark:text-white dark:bg-gray-700 dark:border-gray-600 leading-tight focus:outline-none focus:shadow-outline transition-colors duration-200">
                        <?php foreach ($stades as $stade): ?>
                            <option value="<?php echo $stade['id']; ?>">
                                <?php echo htmlspecialchars($stade['nom']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-4">
                    <label class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2 transition-colors duration-200" for="arbitre">
                        Arbitre
                    </label>
                    <select name="arbitre" id="arbitre" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 dark:text-white dark:bg-gray-700 dark:border-gray-600 leading-tight focus:outline-none focus:shadow-outline transition-colors duration-200">
                        <?php foreach ($arbitres as $arbitre): ?>
                            <option value="<?php echo $arbitre['id']; ?>">
                                <?php echo htmlspecialchars($arbitre['nom'] . ' (' . $arbitre['grade'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-4">
                    <label class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2 transition-colors duration-200">
                        Date et Heure
                    </label>
                    <div class="grid grid-cols-2 gap-2">
                        <input type="date" name="date_debut" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 dark:text-white dark:bg-gray-700 dark:border-gray-600 leading-tight focus:outline-none focus:shadow-outline transition-colors duration-200">
                        <input type="time" name="heure_debut" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 dark:text-white dark:bg-gray-700 dark:border-gray-600 leading-tight focus:outline-none focus:shadow-outline transition-colors duration-200">
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-8 mt-8">
                <!-- Équipe 1 -->
                <div>
                    <h3 class="text-lg font-bold mb-4 text-gray-900 dark:text-white transition-colors duration-200">Équipe 1</h3>
                    <select name="equipe_1" id="equipe_1" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 dark:text-white dark:bg-gray-700 dark:border-gray-600 leading-tight focus:outline-none focus:shadow-outline mb-4 transition-colors duration-200">
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
                    <h3 class="text-lg font-bold mb-4 text-gray-900 dark:text-white transition-colors duration-200">Équipe 2</h3>
                    <select name="equipe_2" id="equipe_2" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 dark:text-white dark:bg-gray-700 dark:border-gray-600 leading-tight focus:outline-none focus:shadow-outline mb-4 transition-colors duration-200">
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
                <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline transition-colors duration-200">
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
                            <label class="flex items-center space-x-3 text-gray-700 dark:text-gray-300 transition-colors duration-200">
                                <input type="checkbox" name="${targetDiv}[]" value="${player.id}" class="form-checkbox h-5 w-5 text-blue-600">
                                <span>${player.nom} ${player.prenom} (${player.position})</span>
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
            // Check if match creation is allowed based on countdown
            if (typeof window.isMatchEditable === 'function' && !window.isMatchEditable()) {
                e.preventDefault();
                alert('Vous ne pouvez pas créer de nouveaux matchs car un match va bientôt commencer. Veuillez gérer ce match d\'abord.');
                return;
            }
            
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

        // Dark mode toggle in navbar
        $('#navbar-dark-mode-toggle').click(function() {
            if (document.documentElement.classList.contains('dark')) {
                document.documentElement.classList.remove('dark');
                localStorage.setItem('theme', 'light');
            } else {
                document.documentElement.classList.add('dark');
                localStorage.setItem('theme', 'dark');
            }
        });
    </script>
</body>
</html>