<!DOCTYPE html>
<html lang="fr" class="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier un Match</title>
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
    <script src="js/darkmode.js"></script>
</head>
<body class="bg-gray-100 dark:bg-gray-900 dark:text-white transition-colors duration-200">
    <nav class="bg-white dark:bg-gray-800 shadow-lg mb-8 transition-colors duration-200">
        <div class="container mx-auto px-4">
            <div class="flex justify-between items-center py-4">
                <div class="flex space-x-4">
                    <a href="publications.php" class="text-gray-700 dark:text-gray-300 hover:bg-blue-500 hover:text-white px-3 py-2 rounded-md text-sm font-medium transition-colors duration-200">
                        Ajouter Publication
                    </a>
                    <a href="ajout_match.php" class="text-gray-700 dark:text-gray-300 hover:bg-blue-500 hover:text-white px-3 py-2 rounded-md text-sm font-medium transition-colors duration-200">
                        Ajouter Match
                    </a>
                    <a href="mes_matchs.php" class="text-gray-700 dark:text-gray-300 hover:bg-blue-500 hover:text-white px-3 py-2 rounded-md text-sm font-medium transition-colors duration-200">
                        Voir Mes Matchs
                    </a>
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
                    <a href="deconnexion.php" class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600 transition-colors duration-200">Déconnexion</a>
                </div>
            </div>
        </div>
    </nav>
    <div class="container mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold mb-8 text-center text-gray-900 dark:text-white transition-colors duration-200">Modifier un Match</h1>
        
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
                        <?php foreach ($tournois as $tournoi): ?>
                            <option value="<?php echo $tournoi['id']; ?>" <?php echo ($match['tournoi_id'] == $tournoi['id']) ? 'selected' : ''; ?>>
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
                            <option value="<?php echo $stade['id']; ?>" <?php echo ($match['stade_id'] == $stade['id']) ? 'selected' : ''; ?>>
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
                            <option value="<?php echo $arbitre['id']; ?>" <?php echo ($match['arbitre_id'] == $arbitre['id']) ? 'selected' : ''; ?>>
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
                        <input type="date" name="date_debut" value="<?php echo date('Y-m-d', strtotime($match['date_debut'])); ?>" required 
                               class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 dark:text-white dark:bg-gray-700 dark:border-gray-600 leading-tight focus:outline-none focus:shadow-outline transition-colors duration-200">
                        <input type="time" name="heure_debut" value="<?php echo date('H:i', strtotime($match['date_debut'])); ?>" required 
                               class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 dark:text-white dark:bg-gray-700 dark:border-gray-600 leading-tight focus:outline-none focus:shadow-outline transition-colors duration-200">
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-8 mt-8">
                <!-- Équipe 1 -->
                <div>
                    <h3 class="text-lg font-bold mb-4 text-gray-900 dark:text-white transition-colors duration-200">Équipe 1: <?php echo htmlspecialchars($equipe1['nom']); ?></h3>
                    <div id="joueurs_equipe1" class="space-y-2">
                        <?php foreach ($joueurs_equipe1 as $joueur): ?>
                            <label class="flex items-center space-x-3 text-gray-700 dark:text-gray-300 transition-colors duration-200">
                                <input type="checkbox" name="joueurs_equipe1[]" value="<?php echo $joueur['id']; ?>" 
                                       <?php echo in_array($joueur['id'], $selected_joueurs_equipe1) ? 'checked' : ''; ?> 
                                       class="form-checkbox h-5 w-5 text-blue-600">
                                <span><?php echo htmlspecialchars($joueur['nom'] . ' ' . $joueur['prenom'] . ' (' . $joueur['position'] . ')'); ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Équipe 2 -->
                <div>
                    <h3 class="text-lg font-bold mb-4 text-gray-900 dark:text-white transition-colors duration-200">Équipe 2: <?php echo htmlspecialchars($equipe2['nom']); ?></h3>
                    <div id="joueurs_equipe2" class="space-y-2">
                        <?php foreach ($joueurs_equipe2 as $joueur): ?>
                            <label class="flex items-center space-x-3 text-gray-700 dark:text-gray-300 transition-colors duration-200">
                                <input type="checkbox" name="joueurs_equipe2[]" value="<?php echo $joueur['id']; ?>" 
                                       <?php echo in_array($joueur['id'], $selected_joueurs_equipe2) ? 'checked' : ''; ?> 
                                       class="form-checkbox h-5 w-5 text-blue-600">
                                <span><?php echo htmlspecialchars($joueur['nom'] . ' ' . $joueur['prenom'] . ' (' . $joueur['position'] . ')'); ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-center mt-8">
                <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline transition-colors duration-200">
                    Modifier le match
                </button>
            </div>
        </form>
    </div>

    <script>
        $('form').submit(function(e) {
            const equipe1Players = $('input[name="joueurs_equipe1[]"]:checked').length;
            const equipe2Players = $('input[name="joueurs_equipe2[]"]:checked').length;
            
            if (equipe1Players > 11 || equipe2Players > 11) {
                e.preventDefault();
                alert('Une équipe ne peut pas avoir plus de 11 joueurs');
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