// Function to load match statistics
function loadMatchStats(matchId) {
    $.ajax({
        url: 'get_match_stats.php',
        method: 'GET',
        data: { id: matchId },
        success: function(response) {
            $('#matchStats').html(response);
        },
        error: function(xhr, status, error) {
            console.error('Error loading match stats:', error);
            alert('Erreur lors du chargement des statistiques du match');
        }
    });
}

// Function to finish match and save statistics
function finishMatch(matchId) {
    // Collect team statistics
    const team1Stats = {
        passes: $('#team1Stats input[name="passes"]').val(),
        tirs: $('#team1Stats input[name="tirs"]').val(),
        corners: $('#team1Stats input[name="corners"]').val(),
        penalties: $('#team1Stats input[name="penalty"]').val(),
        coups_franc: $('#team1Stats input[name="coups_franc"]').val(),
        centres: $('#team1Stats input[name="centres"]').val(),
        hors_jeu: $('#team1Stats input[name="hors_jeu"]').val(),
        goals: $('#team1Stats input[name="goals"]').val()
    };

    const team2Stats = {
        passes: $('#team2Stats input[name="passes"]').val(),
        tirs: $('#team2Stats input[name="tirs"]').val(),
        corners: $('#team2Stats input[name="corners"]').val(),
        penalties: $('#team2Stats input[name="penalty"]').val(),
        coups_franc: $('#team2Stats input[name="coups_franc"]').val(),
        centres: $('#team2Stats input[name="centres"]').val(),
        hors_jeu: $('#team2Stats input[name="hors_jeu"]').val(),
        goals: $('#team2Stats input[name="goals"]').val()
    };

    // Collect player statistics
    const playerStats = [];
    $('.player-stats-form').each(function() {
        const form = $(this);
        playerStats.push({
            player_id: form.find('input[name="player_id"]').val(),
            dribbles_reussis: form.find('input[name="dribbles_reussis"]').val(),
            interceptions: form.find('input[name="interceptions"]').val(),
            passes_decisives: form.find('input[name="passes_decisives"]').val(),
            tirs_cadres: form.find('input[name="tirs_cadres"]').val(),
            fautes: form.find('input[name="fautes"]').val()
        });
    });

    // Prepare data for submission
    const formData = {
        match_id: matchId,
        equipe1_id: $('#team1Stats').data('team-id'),
        equipe2_id: $('#team2Stats').data('team-id'),
        passes1: team1Stats.passes,
        tirs1: team1Stats.tirs,
        corners1: team1Stats.corners,
        penalty1: team1Stats.penalties,
        coups_franc1: team1Stats.coups_franc,
        centres1: team1Stats.centres,
        hors_jeu1: team1Stats.hors_jeu,
        goals1: team1Stats.goals,
        passes2: team2Stats.passes,
        tirs2: team2Stats.tirs,
        corners2: team2Stats.corners,
        penalty2: team2Stats.penalties,
        coups_franc2: team2Stats.coups_franc,
        centres2: team2Stats.centres,
        hors_jeu2: team2Stats.hors_jeu,
        goals2: team2Stats.goals,
        player_stats: JSON.stringify(playerStats)
    };

    // Submit data to server
    $.ajax({
        url: 'finish_match.php',
        method: 'POST',
        data: formData,
        success: function(response) {
            if (response.success) {
                alert('Match terminé avec succès!');
                window.location.reload();
            } else {
                alert('Erreur lors de la fin du match: ' + response.error);
            }
        },
        error: function(xhr, status, error) {
            console.error('Error finishing match:', error);
            alert('Erreur lors de la fin du match');
        }
    });
}

// Add input validation
$(document).on('input', 'input[type="number"]', function() {
    const value = parseInt($(this).val());
    if (value < 0) {
        $(this).val(0);
    }
}); 