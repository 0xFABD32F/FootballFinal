<?php
session_start();
include("connexion.php");

// Récupérer la publication détaillée si un ID est fourni
$publication_details = null;
$comments = [];
if (isset($_GET['id'])) {
    $stmt = $conn->prepare("SELECT id, title, message, photo, created_at FROM publications WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $publication_details = $stmt->fetch();
    
    if ($publication_details) {
        // Récupérer les commentaires pour cette publication
        $stmt = $conn->prepare("SELECT c.id, c.content, c.created_at, u.username, u.Type_compte
                               FROM comments c
                               JOIN comptes u ON c.user_id = u.id_compte
                               WHERE c.publication_id = ?
                               ORDER BY c.created_at DESC");
        $stmt->execute([$_GET['id']]);
        $comments = $stmt->fetchAll();
    }
}

// Traiter l'ajout d'un commentaire
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['ID'])) {
    if (isset($_POST['comment']) && !empty($_POST['comment']) && isset($_POST['publication_id'])) {
        try {
            $stmt = $conn->prepare("INSERT INTO comments (publication_id, user_id, content, created_at) 
                                  VALUES (?, ?, ?, NOW())");
            $stmt->execute([
                $_POST['publication_id'],
                $_SESSION['ID'],
                $_POST['comment']
            ]);
            // Rediriger pour éviter la soumission multiple du formulaire
            header('Location: view_publications.php?id=' . $_POST['publication_id'] . '&success=1');
            exit;
        } catch (PDOException $e) {
            $error = "Erreur lors de l'ajout du commentaire: " . $e->getMessage();
        }
    }
}

// Fetch existing publications
$stmt = $conn->query("SELECT id, title, message, photo, created_at FROM publications ORDER BY created_at DESC");
$publications = $stmt->fetchAll();

// Fonction pour tronquer un texte
function truncate($text, $length = 150) {
    if (strlen($text) > $length) {
        return substr($text, 0, $length) . '...';
    }
    return $text;
}
?>

<!DOCTYPE html>
<html lang="fr" class="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Publications - Football Manager</title>
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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>
<body class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-900 dark:to-gray-800 dark:text-white transition-colors duration-200">
    <nav class="bg-white dark:bg-gray-900 shadow-lg mb-8 transition-colors duration-200">
        <div class="container mx-auto px-4">
            <div class="flex justify-between items-center py-4">
                <a href="index.php" class="text-xl font-bold text-gray-800 dark:text-white transition-colors duration-200">⚽ Football Manager</a>
                <div class="flex space-x-4">
                    <a href="view_publications.php" class="bg-blue-500 text-white px-4 py-2 rounded-md text-sm font-medium shadow-md hover:bg-blue-600 transition-colors duration-200">
                        Publications
                    </a>
                    <?php if (isset($_SESSION['ID'])): ?>
                        <a href="deconnexion.php" class="text-gray-700 dark:text-gray-300 hover:bg-red-500 hover:text-white px-4 py-2 rounded-md text-sm font-medium transition-colors duration-200">
                            Déconnexion
                        </a>
                    <?php else: ?>
                        <a href="page_connexion.php" class="text-gray-700 dark:text-gray-300 hover:bg-blue-500 hover:text-white px-4 py-2 rounded-md text-sm font-medium transition-colors duration-200">
                            Connexion
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mx-auto px-4 pb-12">
        <?php if ($publication_details): ?>
            <!-- Affichage détaillé d'une publication -->
            <div class="mb-6">
                <a href="view_publications.php" class="text-blue-600 dark:text-blue-400 hover:underline flex items-center transition-colors duration-200">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd" />
                    </svg>
                    Retour aux publications
                </a>
            </div>
            
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md overflow-hidden mb-8 transition-colors duration-200">
                <?php if (!empty($publication_details['photo'])): ?>
                    <div class="h-64 overflow-hidden">
                        <img src="<?php echo htmlspecialchars($publication_details['photo']); ?>" alt="<?php echo htmlspecialchars($publication_details['title']); ?>" class="w-full h-full object-cover">
                    </div>
                <?php endif; ?>
                
                <div class="p-6">
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-4 transition-colors duration-200"><?php echo htmlspecialchars($publication_details['title']); ?></h1>
                    <p class="text-gray-600 dark:text-gray-300 mb-4 whitespace-pre-line transition-colors duration-200"><?php echo nl2br(htmlspecialchars($publication_details['message'])); ?></p>
                    <div class="text-sm text-gray-500 dark:text-gray-400 transition-colors duration-200">
                        Publié le <?php echo date('d/m/Y à H:i', strtotime($publication_details['created_at'])); ?>
                    </div>
                </div>
            </div>
            
            <!-- Section des commentaires -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md overflow-hidden transition-colors duration-200">
                <div class="p-6">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 transition-colors duration-200">Commentaires (<?php echo count($comments); ?>)</h2>
                    
                    <?php if (isset($_SESSION['ID'])): ?>
                        <!-- Formulaire d'ajout de commentaire -->
                        <form method="POST" class="mb-6">
                            <input type="hidden" name="publication_id" value="<?php echo $publication_details['id']; ?>">
                            <div class="mb-3">
                                <label for="comment" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 transition-colors duration-200">Ajouter un commentaire</label>
                                <textarea name="comment" id="comment" rows="3" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors duration-200"></textarea>
                            </div>
                            <button type="submit" class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors duration-200">
                                Publier
                            </button>
                        </form>
                    <?php else: ?>
                        <div class="bg-yellow-50 dark:bg-yellow-900 border border-yellow-200 dark:border-yellow-800 rounded-md p-4 mb-6 transition-colors duration-200">
                            <p class="text-yellow-700 dark:text-yellow-300 transition-colors duration-200">Vous devez être connecté pour laisser un commentaire. <a href="page_connexion.php" class="text-blue-600 dark:text-blue-400 hover:underline transition-colors duration-200">Se connecter</a></p>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (empty($comments)): ?>
                        <div class="text-gray-500 dark:text-gray-400 italic transition-colors duration-200">
                            Aucun commentaire pour le moment. Soyez le premier à commenter!
                        </div>
                    <?php else: ?>
                        <div class="space-y-4">
                            <?php foreach ($comments as $comment): ?>
                                <div class="border-b border-gray-200 dark:border-gray-700 pb-4 last:border-0 last:pb-0 transition-colors duration-200">
                                    <div class="flex justify-between items-start mb-1">
                                        <span class="font-medium text-gray-900 dark:text-white transition-colors duration-200"><?php echo htmlspecialchars($comment['username']); ?></span>
                                        <span class="text-xs text-gray-500 dark:text-gray-400 transition-colors duration-200"><?php echo date('d/m/Y à H:i', strtotime($comment['created_at'])); ?></span>
                                    </div>
                                    <p class="text-gray-600 dark:text-gray-300 transition-colors duration-200"><?php echo nl2br(htmlspecialchars($comment['content'])); ?></p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php else: ?>
            <!-- Liste des publications -->
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-8 transition-colors duration-200">Actualités Football</h1>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php if (empty($publications)): ?>
                    <div class="col-span-full">
                        <p class="text-gray-500 dark:text-gray-400 text-center transition-colors duration-200">Aucune publication pour le moment.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($publications as $pub): ?>
                        <a href="?id=<?php echo $pub['id']; ?>" class="block">
                            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm overflow-hidden h-full flex flex-col transition-transform hover:scale-[1.02] hover:shadow-md transition-colors duration-200">
                                <?php if (!empty($pub['photo'])): ?>
                                    <div class="h-48 overflow-hidden">
                                        <img src="<?php echo htmlspecialchars($pub['photo']); ?>" alt="<?php echo htmlspecialchars($pub['title']); ?>" class="w-full h-full object-cover">
                                    </div>
                                <?php endif; ?>
                                
                                <div class="p-6 flex-grow">
                                    <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2 transition-colors duration-200"><?php echo htmlspecialchars($pub['title']); ?></h3>
                                    <p class="text-gray-600 dark:text-gray-300 mb-4 transition-colors duration-200"><?php echo nl2br(htmlspecialchars(truncate($pub['message']))); ?></p>
                                </div>
                                
                                <div class="px-6 pb-4 text-sm text-gray-500 dark:text-gray-400 flex justify-between items-center transition-colors duration-200">
                                    <span>Publié le <?php echo date('d/m/Y', strtotime($pub['created_at'])); ?></span>
                                    <span class="text-blue-600 dark:text-blue-400 transition-colors duration-200">Lire plus...</span>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Afficher un message de succès après l'ajout d'un commentaire
        <?php if (isset($_GET['success'])): ?>
        document.addEventListener('DOMContentLoaded', function() {
            const notification = document.createElement('div');
            notification.className = 'fixed top-4 right-4 bg-green-500 text-white px-4 py-2 rounded shadow-lg z-50';
            notification.textContent = 'Commentaire ajouté avec succès!';
            document.body.appendChild(notification);
            
            setTimeout(function() {
                notification.remove();
            }, 3000);
        });
        <?php endif; ?>
    </script>
</body>
</html> 