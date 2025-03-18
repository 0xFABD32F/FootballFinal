<?php
session_start();
if (!isset($_SESSION['Type_compte']) || $_SESSION['Type_compte'] !== 'admin_tournoi') {
    header('Location: page_connexion.php');
    exit();
}

include("connexion.php");

// Fetch matches for current admin with player participation data
$stmt = $conn->prepare("
    SELECT m.*, 
           tt.nom as tournoi_nom,
           s.nom as stade_nom,
           e1.nom as equipe1_nom,
           e2.nom as equipe2_nom,
           a.nom as arbitre_nom,
           a.grade as arbitre_grade,
           GROUP_CONCAT(DISTINCT CONCAT(p1.joueur_id, ':', j1.nom, ':', j1.prenom, ':', j1.position, ':', p1.titulaire) SEPARATOR '|') as equipe1_joueurs,
           GROUP_CONCAT(DISTINCT CONCAT(p2.joueur_id, ':', j2.nom, ':', j2.prenom, ':', j2.position, ':', p2.titulaire) SEPARATOR '|') as equipe2_joueurs
    FROM matches m
    JOIN tournois t ON m.tournois_id = t.id
    JOIN types_tournois tt ON t.type_id = tt.id
    JOIN stades s ON m.stade_id = s.id
    JOIN equipes e1 ON m.equipe_domicile_id = e1.id
    JOIN equipes e2 ON m.equipe_exterieur_id = e2.id
    JOIN arbitres a ON m.arbitre_id = a.id
    LEFT JOIN participations p1 ON m.id = p1.match_id AND m.equipe_domicile_id = p1.equipe_id
    LEFT JOIN participations p2 ON m.id = p2.match_id AND m.equipe_exterieur_id = p2.equipe_id
    LEFT JOIN joueurs j1 ON p1.joueur_id = j1.id
    LEFT JOIN joueurs j2 ON p2.joueur_id = j2.id
    WHERE m.id_admin = :admin_id 
    GROUP BY m.id
    ORDER BY m.date_match DESC, m.heure_debut DESC
");
$stmt->execute([':admin_id' => $_SESSION['ID']]);
$matches = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Matchs</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://unpkg.com/@heroicons/v1/outline/"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="js/match_stats.js"></script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f3f4f6;
        }
    </style>
</head>
<body class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100">
    <nav class="bg-white shadow-lg mb-8">
        <div class="container mx-auto px-4">
            <div class="flex justify-between items-center py-4">
                <div class="flex items-center space-x-8">
                    <a href="mes_matchs.php" class="text-xl font-bold text-gray-800">⚽ Football Manager</a>
                    <div class="flex space-x-4">
                        <a href="ajout_match.php" class="flex items-center text-gray-700 hover:bg-blue-500 hover:text-white px-4 py-2 rounded-md text-sm font-medium transition-colors duration-200">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                            </svg>
                            Ajouter Match
                        </a>
                        <a href="mes_matchs.php" class="flex items-center bg-blue-500 text-white px-4 py-2 rounded-md text-sm font-medium shadow-md hover:bg-blue-600 transition-colors duration-200">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                            Voir Mes Matchs
                        </a>
                    </div>
                </div>
                <a href="deconnexion.php" class="flex items-center bg-red-500 text-white px-4 py-2 rounded-md hover:bg-red-600 transition-colors duration-200">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                    Déconnexion
                </a>
            </div>
        </div>
    </nav>

    <div class="container mx-auto px-4 pb-12">
        <div class="flex gap-6">
            <!-- Left side - Matches List -->
            <div class="w-1/3 space-y-6">
                <div class="flex items-center justify-between">
                    <h1 class="text-2xl font-bold text-gray-900">Mes Matchs</h1>
                    <span id="matchCount" class="bg-blue-100 text-blue-800 text-sm font-medium px-3 py-1 rounded-full">
                        <?php echo count($matches); ?> match(s)
                    </span>
                </div>
                <div class="space-y-4">
                    <?php foreach ($matches as $match): ?>
                        <div class="bg-white rounded-xl shadow-sm hover:shadow-md transition-shadow duration-200 cursor-pointer border border-gray-100 <?php echo $match['etat'] === 'termine' ? 'opacity-75' : ''; ?>"
                             onclick="<?php echo $match['etat'] !== 'termine' ? 'loadMatchStats(' . $match['id'] . ')' : ''; ?>">
                            <div class="p-4">
                                <div class="flex items-center justify-between mb-3">
                                    <div class="flex items-center space-x-2">
                                        <span class="text-lg font-semibold text-gray-900">
                                            <?php echo htmlspecialchars($match['tournoi_nom']); ?>
                                        </span>
                                        <?php if ($match['etat'] === 'termine'): ?>
                                            <span class="px-2 py-1 text-xs font-medium bg-green-100 text-green-800 rounded-full">
                                                Terminé
                                            </span>
                                        <?php elseif ($match['etat'] === 'en_cours'): ?>
                                            <span class="px-2 py-1 text-xs font-medium bg-blue-100 text-blue-800 rounded-full">
                                                En cours
                                            </span>
                                        <?php else: ?>
                                            <span class="px-2 py-1 text-xs font-medium bg-yellow-100 text-yellow-800 rounded-full">
                                                Prévu
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <span class="text-sm text-gray-500">
                                        <?php echo date('d/m/Y', strtotime($match['date_match'])); ?>
                                    </span>
                                </div>
                                <div class="flex items-center justify-between mb-3">
                                    <div class="text-right flex-1">
                                        <span class="font-medium text-gray-900">
                                            <?php echo htmlspecialchars($match['equipe1_nom']); ?>
                                        </span>
                                    </div>
                                    <div class="px-4">
                                        <span class="text-2xl font-bold text-gray-900">
                                            <?php echo $match['score_domicile'] ?? '0'; ?> - <?php echo $match['score_exterieur'] ?? '0'; ?>
                                        </span>
                                    </div>
                                    <div class="text-left flex-1">
                                        <span class="font-medium text-gray-900">
                                            <?php echo htmlspecialchars($match['equipe2_nom']); ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="flex items-center justify-between text-sm text-gray-500">
                                    <span><?php echo htmlspecialchars($match['stade_nom']); ?></span>
                                    <span><?php echo htmlspecialchars($match['arbitre_nom']); ?> (<?php echo htmlspecialchars($match['arbitre_grade']); ?>)</span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Right side - Match Statistics -->
            <div class="w-2/3">
                <div id="matchStats" class="bg-white rounded-xl shadow-sm p-6">
                    <div class="text-center text-gray-500">
                        Sélectionnez un match pour voir ses statistiques
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function loadMatchStats(matchId) {
            $.get('get_match_stats.php', { id: matchId }, function(data) {
                $('#matchStats').html(data);
            });
        }

        function finishMatch(matchId, form) {
            event.preventDefault();
            
            // Collect form data
            const formData = new FormData(form);
            const data = {
                match_id: matchId,
                team1: {},
                team2: {},
                players: {}
            };

            // Process team statistics
            for (const [key, value] of formData.entries()) {
                if (key.startsWith('team1[')) {
                    const stat = key.slice(6, -1);
                    data.team1[stat] = value;
                } else if (key.startsWith('team2[')) {
                    const stat = key.slice(6, -1);
                    data.team2[stat] = value;
                } else if (key.startsWith('players[')) {
                    const [_, playerId, stat] = key.match(/players\[(\d+)\]\[(.*?)\]/);
                    if (!data.players[playerId]) {
                        data.players[playerId] = {};
                    }
                    data.players[playerId][stat] = value;
                }
            }

            // Validate required fields
            if (!data.team1.goals || !data.team2.goals) {
                showNotification('Veuillez entrer les scores des deux équipes', 'error');
                return false;
            }

            console.log('Sending data to server:', data);

            // Send data to server
            $.ajax({
                url: 'finish_match.php',
                method: 'POST',
                data: JSON.stringify(data),
                contentType: 'application/json',
                success: function(response) {
                    console.log('Server response:', response);
                    if (response.success) {
                        showNotification('Match terminé avec succès', 'success');
                        // Clear the right panel
                        $('#matchStats').html('<div class="text-center text-gray-500">Sélectionnez un match pour voir ses statistiques</div>');
                        
                        // Update the match card instead of removing it
                        const matchCard = $(`[onclick="loadMatchStats(${matchId})"]`);
                        matchCard.removeAttr('onclick');
                        matchCard.addClass('opacity-75');
                        
                        // Update the status badge
                        const statusBadge = matchCard.find('.text-xs');
                        statusBadge.removeClass('bg-yellow-100 text-yellow-800 bg-blue-100 text-blue-800')
                                  .addClass('bg-green-100 text-green-800')
                                  .text('Terminé');
                        
                        // Update the score display
                        const scoreSpan = matchCard.find('.text-2xl');
                        scoreSpan.text(`${data.team1.goals} - ${data.team2.goals}`);
                    } else {
                        console.error('Server error:', response.error);
                        showNotification(response.error || 'Erreur lors de la fin du match', 'error');
                        // Don't update the match card if there was an error
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error finishing match:', error);
                    showNotification('Erreur lors de la fin du match', 'error');
                    // Don't update the match card if there was an error
                }
            });

            return false;
        }

        function showNotification(message, type) {
            const notification = document.createElement('div');
            notification.className = `fixed top-4 right-4 p-4 rounded-lg ${
                type === 'success' ? 'bg-green-500' : 'bg-red-500'
            } text-white shadow-lg z-50`;
            notification.textContent = message;
            document.body.appendChild(notification);
            setTimeout(() => notification.remove(), 3000);
        }

        // Add input validation for numeric fields
        document.addEventListener('input', function(e) {
            if (e.target.type === 'number' && e.target.value < 0) {
                e.target.value = 0;
            }
        });
    </script>
</body>
</html>