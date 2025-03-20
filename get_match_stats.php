<?php
session_start();
if (!isset($_SESSION['Type_compte']) || $_SESSION['Type_compte'] !== 'admin_tournoi') {
    http_response_code(403);
    exit('Unauthorized');
}

include("connexion.php");

if (!isset($_GET['id'])) {
    http_response_code(400);
    exit('Missing match ID');
}

try {
    // Get match details
    $stmt = $conn->prepare("
        SELECT m.*, 
               e1.nom as equipe1_nom,
               e2.nom as equipe2_nom,
               s.nom as stade_nom,
               a.nom as arbitre_nom,
               a.grade as arbitre_grade
        FROM matches m
        JOIN equipes e1 ON m.equipe_domicile_id = e1.id
        JOIN equipes e2 ON m.equipe_exterieur_id = e2.id
        JOIN stades s ON m.stade_id = s.id
        JOIN arbitres a ON m.arbitre_id = a.id
        WHERE m.id = :match_id
    ");
    $stmt->execute([':match_id' => $_GET['id']]);
    $match = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$match) {
        throw new Exception("Match non trouvé");
    }

    // Get team statistics
    $stmt = $conn->prepare("
        SELECT * FROM equipe_statistics 
        WHERE match_id = :match_id
    ");
    $stmt->execute([':match_id' => $_GET['id']]);
    $teamStats = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get player statistics
    $stmt = $conn->prepare("
        SELECT 
            p.joueur_id,
            p.equipe_id,
            p.titulaire,
            j.nom,
            j.prenom,
            j.position,
            COALESCE(js.dribbles_reussis, 0) as dribbles_reussis,
            COALESCE(js.interceptions, 0) as interceptions,
            COALESCE(js.passes_decisives, 0) as passes_decisives,
            COALESCE(js.tirs_cadres, 0) as tirs_cadres,
            COALESCE(js.fautes, 0) as fautes
        FROM participations p
        JOIN joueurs j ON p.joueur_id = j.id
        LEFT JOIN joueur_statistics js ON p.match_id = js.match_id AND p.joueur_id = js.joueur_id
        WHERE p.match_id = :match_id
    ");
    $stmt->execute([':match_id' => $_GET['id']]);
    $playerStats = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Organize player stats by team
    $team1Players = array_filter($playerStats, function($stat) use ($match) {
        return $stat['equipe_id'] == $match['equipe_domicile_id'];
    });
    $team2Players = array_filter($playerStats, function($stat) use ($match) {
        return $stat['equipe_id'] == $match['equipe_exterieur_id'];
    });

    // Get team statistics
    $team1Stats = array_filter($teamStats, function($stat) use ($match) {
        return $stat['equipe_id'] == $match['equipe_domicile_id'];
    });
    $team2Stats = array_filter($teamStats, function($stat) use ($match) {
        return $stat['equipe_id'] == $match['equipe_exterieur_id'];
    });

    $team1Stats = reset($team1Stats) ?: [
        'passes' => 0,
        'tirs' => 0,
        'corners' => 0,
        'penalties' => 0,
        'coups_franc' => 0,
        'centres' => 0,
        'hors_jeu' => 0
    ];
    $team2Stats = reset($team2Stats) ?: [
        'passes' => 0,
        'tirs' => 0,
        'corners' => 0,
        'penalties' => 0,
        'coups_franc' => 0,
        'centres' => 0,
        'hors_jeu' => 0
    ];
?>

<form onsubmit="return finishMatch(<?php echo $match['id']; ?>, this)">
    <div class="space-y-6">
        <!-- Match Header -->
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white transition-colors duration-200">
                <?php echo htmlspecialchars($match['equipe1_nom']); ?> vs <?php echo htmlspecialchars($match['equipe2_nom']); ?>
            </h2>
            <div class="flex space-x-4">
                <button type="submit" 
                        class="bg-green-500 text-white px-4 py-2 rounded-md hover:bg-green-600 transition-colors duration-200">
                    Terminer le match
                </button>
            </div>
        </div>

        <!-- Team Statistics -->
        <div class="grid grid-cols-2 gap-6">
            <!-- Team 1 Stats -->
            <div id="team1Stats" class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg transition-colors duration-200" data-team-id="<?php echo $match['equipe_domicile_id']; ?>">
                <h3 class="text-lg font-semibold mb-4 dark:text-white"><?php echo htmlspecialchars($match['equipe1_nom']); ?></h3>
                <div class="space-y-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Passes</label>
                        <input type="number" name="team1[passes]" value="<?php echo $team1Stats['passes']; ?>" 
                               class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-800 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Tirs</label>
                        <input type="number" name="team1[tirs]" value="<?php echo $team1Stats['tirs']; ?>"
                               class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-800 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Corners</label>
                        <input type="number" name="team1[corners]" value="<?php echo $team1Stats['corners']; ?>"
                               class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-800 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Penalties</label>
                        <input type="number" name="team1[penalties]" value="<?php echo $team1Stats['penalties']; ?>"
                               class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-800 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Coups francs</label>
                        <input type="number" name="team1[coups_franc]" value="<?php echo $team1Stats['coups_franc']; ?>"
                               class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-800 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Centres</label>
                        <input type="number" name="team1[centres]" value="<?php echo $team1Stats['centres']; ?>"
                               class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-800 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Hors-jeu</label>
                        <input type="number" name="team1[hors_jeu]" value="<?php echo $team1Stats['hors_jeu']; ?>"
                               class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-800 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Buts</label>
                        <input type="number" name="team1[goals]" value="<?php echo $match['score_domicile'] ?? 0; ?>"
                               class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-800 dark:text-white">
                    </div>
                </div>
            </div>

            <!-- Team 2 Stats -->
            <div id="team2Stats" class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg transition-colors duration-200" data-team-id="<?php echo $match['equipe_exterieur_id']; ?>">
                <h3 class="text-lg font-semibold mb-4 dark:text-white"><?php echo htmlspecialchars($match['equipe2_nom']); ?></h3>
                <div class="space-y-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Passes</label>
                        <input type="number" name="team2[passes]" value="<?php echo $team2Stats['passes']; ?>"
                               class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-800 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Tirs</label>
                        <input type="number" name="team2[tirs]" value="<?php echo $team2Stats['tirs']; ?>"
                               class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-800 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Corners</label>
                        <input type="number" name="team2[corners]" value="<?php echo $team2Stats['corners']; ?>"
                               class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-800 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Penalties</label>
                        <input type="number" name="team2[penalties]" value="<?php echo $team2Stats['penalties']; ?>"
                               class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-800 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Coups francs</label>
                        <input type="number" name="team2[coups_franc]" value="<?php echo $team2Stats['coups_franc']; ?>"
                               class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-800 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Centres</label>
                        <input type="number" name="team2[centres]" value="<?php echo $team2Stats['centres']; ?>"
                               class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-800 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Hors-jeu</label>
                        <input type="number" name="team2[hors_jeu]" value="<?php echo $team2Stats['hors_jeu']; ?>"
                               class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-800 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Buts</label>
                        <input type="number" name="team2[goals]" value="<?php echo $match['score_exterieur'] ?? 0; ?>"
                               class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-800 dark:text-white">
                    </div>
                </div>
            </div>
        </div>

        <!-- Player Statistics -->
        <div class="mt-8">
            <h3 class="text-lg font-semibold mb-4 dark:text-white">Statistiques des joueurs</h3>
            <div class="grid grid-cols-2 gap-6">
                <!-- Team 1 Players -->
                <div>
                    <h4 class="font-medium mb-3 dark:text-white"><?php echo htmlspecialchars($match['equipe1_nom']); ?></h4>
                    <div class="space-y-4">
                        <?php foreach ($team1Players as $player): ?>
                            <div class="player-stats-form bg-gray-50 dark:bg-gray-700 p-4 rounded-lg transition-colors duration-200">
                                <input type="hidden" name="players[<?php echo $player['joueur_id']; ?>][id]" value="<?php echo $player['joueur_id']; ?>">
                                <h5 class="font-medium mb-2 dark:text-white">
                                    <?php echo htmlspecialchars($player['nom'] . ' ' . $player['prenom']); ?>
                                    <span class="text-sm text-gray-500 dark:text-gray-400">(<?php echo $player['titulaire'] ? 'Titulaire' : 'Remplaçant'; ?>)</span>
                                </h5>
                                <div class="grid grid-cols-2 gap-2">
                                    <div>
                                        <label class="block text-sm text-gray-600 dark:text-gray-300">Dribbles réussis</label>
                                        <input type="number" name="players[<?php echo $player['joueur_id']; ?>][dribbles_reussis]" 
                                               value="<?php echo $player['dribbles_reussis']; ?>"
                                               class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-800 dark:text-white">
                                    </div>
                                    <div>
                                        <label class="block text-sm text-gray-600 dark:text-gray-300">Interceptions</label>
                                        <input type="number" name="players[<?php echo $player['joueur_id']; ?>][interceptions]" 
                                               value="<?php echo $player['interceptions']; ?>"
                                               class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-800 dark:text-white">
                                    </div>
                                    <div>
                                        <label class="block text-sm text-gray-600 dark:text-gray-300">Passes décisives</label>
                                        <input type="number" name="players[<?php echo $player['joueur_id']; ?>][passes_decisives]" 
                                               value="<?php echo $player['passes_decisives']; ?>"
                                               class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-800 dark:text-white">
                                    </div>
                                    <div>
                                        <label class="block text-sm text-gray-600 dark:text-gray-300">Tirs cadrés</label>
                                        <input type="number" name="players[<?php echo $player['joueur_id']; ?>][tirs_cadres]" 
                                               value="<?php echo $player['tirs_cadres']; ?>"
                                               class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-800 dark:text-white">
                                    </div>
                                    <div>
                                        <label class="block text-sm text-gray-600 dark:text-gray-300">Fautes</label>
                                        <input type="number" name="players[<?php echo $player['joueur_id']; ?>][fautes]" 
                                               value="<?php echo $player['fautes']; ?>"
                                               class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-800 dark:text-white">
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Team 2 Players -->
                <div>
                    <h4 class="font-medium mb-3 dark:text-white"><?php echo htmlspecialchars($match['equipe2_nom']); ?></h4>
                    <div class="space-y-4">
                        <?php foreach ($team2Players as $player): ?>
                            <div class="player-stats-form bg-gray-50 dark:bg-gray-700 p-4 rounded-lg transition-colors duration-200">
                                <input type="hidden" name="players[<?php echo $player['joueur_id']; ?>][id]" value="<?php echo $player['joueur_id']; ?>">
                                <h5 class="font-medium mb-2 dark:text-white">
                                    <?php echo htmlspecialchars($player['nom'] . ' ' . $player['prenom']); ?>
                                    <span class="text-sm text-gray-500 dark:text-gray-400">(<?php echo $player['titulaire'] ? 'Titulaire' : 'Remplaçant'; ?>)</span>
                                </h5>
                                <div class="grid grid-cols-2 gap-2">
                                    <div>
                                        <label class="block text-sm text-gray-600 dark:text-gray-300">Dribbles réussis</label>
                                        <input type="number" name="players[<?php echo $player['joueur_id']; ?>][dribbles_reussis]" 
                                               value="<?php echo $player['dribbles_reussis']; ?>"
                                               class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-800 dark:text-white">
                                    </div>
                                    <div>
                                        <label class="block text-sm text-gray-600 dark:text-gray-300">Interceptions</label>
                                        <input type="number" name="players[<?php echo $player['joueur_id']; ?>][interceptions]" 
                                               value="<?php echo $player['interceptions']; ?>"
                                               class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-800 dark:text-white">
                                    </div>
                                    <div>
                                        <label class="block text-sm text-gray-600 dark:text-gray-300">Passes décisives</label>
                                        <input type="number" name="players[<?php echo $player['joueur_id']; ?>][passes_decisives]" 
                                               value="<?php echo $player['passes_decisives']; ?>"
                                               class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-800 dark:text-white">
                                    </div>
                                    <div>
                                        <label class="block text-sm text-gray-600 dark:text-gray-300">Tirs cadrés</label>
                                        <input type="number" name="players[<?php echo $player['joueur_id']; ?>][tirs_cadres]" 
                                               value="<?php echo $player['tirs_cadres']; ?>"
                                               class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-800 dark:text-white">
                                    </div>
                                    <div>
                                        <label class="block text-sm text-gray-600 dark:text-gray-300">Fautes</label>
                                        <input type="number" name="players[<?php echo $player['joueur_id']; ?>][fautes]" 
                                               value="<?php echo $player['fautes']; ?>"
                                               class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-800 dark:text-white">
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<?php
} catch (Exception $e) {
    echo '<div class="text-red-500 dark:text-red-400">Erreur: ' . htmlspecialchars($e->getMessage()) . '</div>';
}
?>