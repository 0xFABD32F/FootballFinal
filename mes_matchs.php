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
<html lang="fr" class="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Matchs</title>
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
    <script src="https://unpkg.com/@heroicons/v1/outline/"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="js/match_stats.js"></script>
    <script src="js/countdown.js"></script>
    <script src="js/darkmode.js"></script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
        .countdown-badge {
            position: absolute;
            top: -10px;
            right: -10px;
            padding: 3px 8px;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
            z-index: 10;
        }
        .upcoming {
            border-width: 2px !important;
            position: relative;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06) !important;
        }
        .upcoming-soon {
            border-width: 2px !important;
            position: relative;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05) !important;
        }
        .dark .upcoming {
            box-shadow: 0 4px 6px -1px rgba(255, 255, 255, 0.05), 0 2px 4px -1px rgba(255, 255, 255, 0.03) !important;
        }
        .dark .upcoming-soon {
            box-shadow: 0 10px 15px -3px rgba(255, 255, 255, 0.07), 0 4px 6px -2px rgba(255, 255, 255, 0.05) !important;
        }
    </style>
</head>
<body class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-900 dark:to-gray-800 dark:text-white transition-colors duration-200">
    <nav class="bg-white dark:bg-gray-900 shadow-lg mb-8 transition-colors duration-200">
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
                        <a href="ajout_match.php" class="flex items-center text-gray-700 dark:text-gray-300 hover:bg-blue-500 hover:text-white px-4 py-2 rounded-md text-sm font-medium transition-colors duration-200">
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

    <div class="container mx-auto px-4 pb-12">
        <div class="flex gap-6">
            <!-- Left side - Matches List -->
            <div class="w-1/3 space-y-6">
                <div class="flex items-center justify-between">
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Mes Matchs</h1>
                    <span id="matchCount" class="bg-blue-100 text-blue-800 dark:bg-blue-700 dark:text-blue-100 text-sm font-medium px-3 py-1 rounded-full transition-colors duration-200">
                        <?php echo count($matches); ?> match(s)
                    </span>
                </div>
                <div class="space-y-4">
                    <?php foreach ($matches as $match): ?>
                        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm hover:shadow-md transition-all duration-200 cursor-pointer border border-gray-100 dark:border-gray-700 <?php echo $match['etat'] === 'termine' ? 'opacity-75' : ''; ?> relative match-card" 
                             onclick="<?php echo $match['etat'] !== 'termine' ? 'loadMatchStats(' . $match['id'] . ')' : ''; ?>"
                             data-id="<?php echo $match['id']; ?>"
                             data-date="<?php echo $match['date_match']; ?>"
                             data-time="<?php echo $match['heure_debut']; ?>"
                             data-status="<?php echo $match['etat']; ?>">
                            <div class="p-4">
                                <div class="flex items-center justify-between mb-3">
                                    <div class="flex items-center space-x-2">
                                    <span class="text-lg font-semibold text-gray-900 dark:text-white">
                                        <?php echo htmlspecialchars($match['tournoi_nom']); ?>
                                    </span>
                                        <?php if ($match['etat'] === 'termine'): ?>
                                            <span class="px-2 py-1 text-xs font-medium bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100 rounded-full transition-colors duration-200">
                                                Terminé
                                            </span>
                                        <?php elseif ($match['etat'] === 'en_cours'): ?>
                                            <span class="px-2 py-1 text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-100 rounded-full transition-colors duration-200">
                                                En cours
                                            </span>
                                        <?php else: ?>
                                            <span class="px-2 py-1 text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-800 dark:text-yellow-100 rounded-full transition-colors duration-200">
                                                Prévu
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                    <span class="text-sm text-gray-500 dark:text-gray-400">
                                            <?php echo date('d/m/Y', strtotime($match['date_match'])); ?>
                                    </span>
                                        <?php if ($match['etat'] !== 'en_cours'): ?>
                                            <button type="button" onclick="event.stopPropagation(); deleteMatch(<?php echo $match['id']; ?>)" class="bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center hover:bg-red-600 focus:outline-none focus:ring-2 focus:ring-red-500 transition-colors duration-200">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                </svg>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="flex items-center justify-between mb-3">
                                    <div class="text-right flex-1 flex items-center justify-end">
                                        <span class="font-medium text-gray-900 dark:text-white mr-2">
                                            <?php echo htmlspecialchars($match['equipe1_nom']); ?>
                                        </span>
                                        <div class="w-8 h-8 rounded-full bg-gray-200 dark:bg-gray-700 overflow-hidden flex items-center justify-center transition-colors duration-200">
                                            <?php
                                            $logoPath = 'uploads/teams/' . $match['equipe_domicile_id'] . '.png';
                                            if (file_exists($logoPath)): ?>
                                                <img src="<?php echo $logoPath; ?>" alt="<?php echo htmlspecialchars($match['equipe1_nom']); ?>" class="w-full h-full object-cover">
                                            <?php else: ?>
                                                <span class="text-xs font-bold dark:text-white"><?php echo substr($match['equipe1_nom'], 0, 2); ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="px-4">
                                        <span class="text-2xl font-bold text-gray-900 dark:text-white">
                                            <?php echo $match['score_domicile'] ?? '0'; ?> - <?php echo $match['score_exterieur'] ?? '0'; ?>
                                        </span>
                                    </div>
                                    <div class="text-left flex-1 flex items-center">
                                        <div class="w-8 h-8 rounded-full bg-gray-200 dark:bg-gray-700 overflow-hidden flex items-center justify-center mr-2 transition-colors duration-200">
                                            <?php
                                            $logoPath = 'uploads/teams/' . $match['equipe_exterieur_id'] . '.png';
                                            if (file_exists($logoPath)): ?>
                                                <img src="<?php echo $logoPath; ?>" alt="<?php echo htmlspecialchars($match['equipe2_nom']); ?>" class="w-full h-full object-cover">
                                            <?php else: ?>
                                                <span class="text-xs font-bold dark:text-white"><?php echo substr($match['equipe2_nom'], 0, 2); ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <span class="font-medium text-gray-900 dark:text-white">
                                            <?php echo htmlspecialchars($match['equipe2_nom']); ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="flex items-center justify-between text-sm text-gray-500 dark:text-gray-400">
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
                <div id="matchStats" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 dark:text-white transition-colors duration-200">
                    <div class="text-center text-gray-500 dark:text-gray-400">
                        Sélectionnez un match pour voir ses statistiques
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function loadMatchStats(matchId) {
            console.log('Loading stats for match ID:', matchId);
            
            // Get match status from its card
            const matchCard = $(`[data-id="${matchId}"]`);
            const matchStatus = matchCard.data('status');
            
            // Simply prevent loading stats if match is scheduled and not editable
            if (matchStatus === 'prevu' && !window.isMatchEditable(matchId)) {
                showNotification('Ce match ne peut pas être modifié car il commence bientôt', 'error');
                return; // Don't load the stats panel
            }
            
            // Store current match id in panel
            $('#matchStats').data('current-match-id', matchId);
            
            // Immediately fetch match statistics without showing loading message
            $.ajax({
                url: 'get_match_stats.php',
                data: { id: matchId },
                success: function(response) {
                    $('#matchStats').html(response);
                },
                error: function(xhr, status, error) {
                    $('#matchStats').html(`<div class="alert alert-danger">Erreur: ${error}</div>`);
                }
            });
        }

        function finishMatch(matchId, form) {
            event.preventDefault();
            
            // Get match status from its card
            const matchCard = $(`[data-id="${matchId}"]`);
            const matchStatus = matchCard.data('status');
            
            // Only allow finishing matches that are "en_cours" or if past the countdown threshold
            if (matchStatus === 'prevu' && !window.isMatchEditable(matchId)) {
                showNotification('Ce match ne peut pas être modifié car il commence bientôt', 'error');
                return false;
            }
            
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
                        $('#matchStats').removeData('current-match-id');
                        
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
                        
                        // Update match card status data attribute
                        $(`[data-id="${matchId}"]`).data('status', 'termine');
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
            } text-white shadow-lg z-50 transition-all duration-200`;
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

        function deleteMatch(matchId) {
            // Supprimer la vérification d'editabilité pour permettre la suppression à tout moment
            if (confirm('Êtes-vous sûr de vouloir supprimer ce match ?')) {
                $.ajax({
                    url: 'delete_match.php',
                    method: 'POST',
                    data: { match_id: matchId },
                    success: function(response) {
                        console.log('Delete response:', response);
                        if (response.success) {
                            showNotification('Match supprimé avec succès', 'success');
                            
                            // Remove the match card from the DOM
                            $(`[onclick*="loadMatchStats(${matchId})"], [onclick*="deleteMatch(${matchId})"]`).closest('.bg-white').fadeOut(300, function() {
                                $(this).remove();
                                
                                // Update match count
                                const currentCount = parseInt($('#matchCount').text());
                                $('#matchCount').text(`${currentCount - 1} match(s)`);
                                
                                // Re-fetch upcoming match data to update countdown
                                if (response.was_countdown_match && typeof fetchUpcomingMatches === 'function') {
                                    console.log('Refreshing countdown - this was the current countdown match');
                                    fetchUpcomingMatches();
                                }
                                
                                // Clear match stats panel if the deleted match was selected
                                if ($('#matchStats').data('current-match-id') == matchId) {
                                    $('#matchStats').html('<div class="text-center text-gray-500">Sélectionnez un match pour voir ses statistiques</div>');
                                    $('#matchStats').removeData('current-match-id');
                                }
                            });
                        } else {
                            showNotification(response.error || 'Erreur lors de la suppression du match', 'error');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error deleting match:', error);
                        showNotification('Erreur lors de la suppression du match', 'error');
                    }
                });
            }
        }
        
        // Function to check URL parameters for match focus
        function checkUrlForMatchFocus() {
            const urlParams = new URLSearchParams(window.location.search);
            const focusMatchId = urlParams.get('focus');
            if (focusMatchId) {
                loadMatchStats(focusMatchId);
                // Scroll to the match card
                const matchCard = $(`[onclick="loadMatchStats(${focusMatchId})"]`);
                if (matchCard.length) {
                    matchCard[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
                    // Highlight the card briefly
                    matchCard.addClass('ring-2 ring-blue-500');
                    setTimeout(() => {
                        matchCard.removeClass('ring-2 ring-blue-500');
                    }, 2000);
                }
            }
        }
        
        $(document).ready(function() {
            checkUrlForMatchFocus();
            
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
            
            // Add countdown badges to matches
            $('.match-card').each(function() {
                const matchDate = $(this).data('date');
                const matchTime = $(this).data('time');
                if (matchDate && matchTime) {
                    const matchDateTime = new Date(`${matchDate}T${matchTime}`);
                    const now = new Date();
                    const diff = matchDateTime - now;
                    
                    // Add badge only if match is within 24 hours
                    if (diff > 0 && diff < 24 * 60 * 60 * 1000) {
                        const hours = Math.floor(diff / (1000 * 60 * 60));
                        const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
                        let badgeText = '';
                        let badgeClass = '';
                        
                        if (hours < 1) {
                            badgeText = `${minutes}m`;
                            badgeClass = 'bg-red-600 text-white border border-red-300 dark:border-red-400';
                            // Add a special class to the card to highlight it
                            $(this).addClass('upcoming-soon border-red-300 dark:border-red-500');
                        } else {
                            badgeText = `${hours}h${minutes}m`;
                            badgeClass = 'bg-orange-500 text-white border border-orange-300 dark:border-orange-400';
                            // Add a special class to the card to highlight it
                            $(this).addClass('upcoming border-orange-300 dark:border-orange-500');
                        }
                        
                        $(this).append(`
                            <div class="countdown-badge ${badgeClass}" title="Temps restant avant le match">
                                ${badgeText}
                            </div>
                        `);
                    }
                }
            });
        });
    </script>
</body>
</html>