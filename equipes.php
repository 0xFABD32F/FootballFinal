<!DOCTYPE html>
<html lang="fr" class="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Équipes</title>
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
    <script src="js/darkmode.js"></script>
</head>
<body class="bg-gray-100 dark:bg-gray-900 dark:text-white transition-colors duration-200">
    <div class="container mx-auto p-4">
        <h1 class="text-3xl font-bold mb-6 text-center text-gray-800 dark:text-white transition-colors duration-200">Gestion des Équipes</h1>

        <!-- Boutons de navigation -->
        <div class="flex justify-center space-x-4 mb-8">
            <a href="gestion_tournois.php" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded transition-colors duration-200">
                Tournois
            </a>
            <a href="equipes.php" class="bg-blue-700 text-white font-bold py-2 px-4 rounded transition-colors duration-200">
                Équipes
            </a>
            <a href="joueurs.php" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded transition-colors duration-200">
                Joueurs
            </a>
            <button id="navbar-dark-mode-toggle" class="flex items-center p-2 bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-yellow-300 font-bold rounded transition-colors duration-200" aria-label="Toggle Dark Mode">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1 hidden dark:block" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1 block dark:hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                </svg>
                <span class="hidden sm:inline">Thème</span>
            </button>
            <a href="deconnexion.php" class="bg-red-500 hover:bg-red-600 text-white font-bold py-2 px-4 rounded transition-colors duration-200">
                Déconnexion
            </a>
        </div>

        <!-- Message d'erreur ou de succès -->
        <?php if (isset($error)): ?>
            <div class="bg-red-100 dark:bg-red-900 border dark:border-red-700 border-red-400 text-red-700 dark:text-red-300 px-4 py-3 rounded mb-4 transition-colors duration-200">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <?php if (isset($success)): ?>
            <div class="bg-green-100 dark:bg-green-900 border dark:border-green-700 border-green-400 text-green-700 dark:text-green-300 px-4 py-3 rounded mb-4 transition-colors duration-200">
                <?php echo $success; ?>
            </div>
        <?php endif; ?>

        <!-- Formulaire d'ajout d'équipe -->
        <div class="mb-8">
            <h2 class="text-2xl font-bold mb-4 text-gray-800 dark:text-white transition-colors duration-200">Ajouter une équipe</h2>
            <form method="POST" action="equipes.php" class="bg-white dark:bg-gray-800 shadow-md rounded px-8 pt-6 pb-8 mb-4 transition-colors duration-200">
                <div class="mb-4">
                    <label class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2 transition-colors duration-200" for="nom">
                        Nom de l'équipe
                    </label>
                    <input type="text" id="nom" name="nom" required
                        class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 dark:text-white dark:bg-gray-700 dark:border-gray-600 leading-tight focus:outline-none focus:shadow-outline transition-colors duration-200">
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2 transition-colors duration-200" for="pays">
                        Pays
                    </label>
                    <input type="text" id="pays" name="pays" required
                        class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 dark:text-white dark:bg-gray-700 dark:border-gray-600 leading-tight focus:outline-none focus:shadow-outline transition-colors duration-200">
                </div>
                <div class="flex items-center justify-between">
                    <button type="submit" name="add_equipe" 
                        class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline transition-colors duration-200">
                        Ajouter l'équipe
                    </button>
                </div>
            </form>
        </div>

        <!-- Liste des équipes -->
        <div>
            <h2 class="text-2xl font-bold mb-4 text-gray-800 dark:text-white transition-colors duration-200">Liste des équipes</h2>
            <div class="bg-white dark:bg-gray-800 shadow-md rounded px-8 pt-6 pb-8 mb-4 overflow-x-auto transition-colors duration-200">
                <table class="min-w-full">
                    <thead>
                        <tr>
                            <th class="px-6 py-3 border-b-2 border-gray-300 dark:border-gray-700 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider transition-colors duration-200">
                                ID
                            </th>
                            <th class="px-6 py-3 border-b-2 border-gray-300 dark:border-gray-700 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider transition-colors duration-200">
                                Nom
                            </th>
                            <th class="px-6 py-3 border-b-2 border-gray-300 dark:border-gray-700 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider transition-colors duration-200">
                                Pays
                            </th>
                            <th class="px-6 py-3 border-b-2 border-gray-300 dark:border-gray-700 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider transition-colors duration-200">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($equipes)): ?>
                            <?php foreach ($equipes as $equipe): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap border-b border-gray-300 dark:border-gray-700 text-gray-800 dark:text-gray-300 transition-colors duration-200">
                                        <?php echo $equipe['id']; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap border-b border-gray-300 dark:border-gray-700 text-gray-800 dark:text-gray-300 transition-colors duration-200">
                                        <?php echo htmlspecialchars($equipe['nom']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap border-b border-gray-300 dark:border-gray-700 text-gray-800 dark:text-gray-300 transition-colors duration-200">
                                        <?php echo htmlspecialchars($equipe['pays']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap border-b border-gray-300 dark:border-gray-700 text-gray-800 dark:text-gray-300 transition-colors duration-200">
                                        <form method="POST" action="equipes.php" class="inline">
                                            <input type="hidden" name="equipe_id" value="<?php echo $equipe['id']; ?>">
                                            <button type="submit" name="delete_equipe" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette équipe ?')" 
                                                class="bg-red-500 hover:bg-red-700 text-white font-bold py-1 px-2 rounded focus:outline-none focus:shadow-outline text-xs transition-colors duration-200">
                                                Supprimer
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="px-6 py-4 whitespace-nowrap border-b border-gray-300 dark:border-gray-700 text-gray-800 dark:text-gray-300 text-center transition-colors duration-200">
                                    Aucune équipe trouvée.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <script>
        // Dark mode toggle in navbar
        document.getElementById('navbar-dark-mode-toggle').addEventListener('click', function() {
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