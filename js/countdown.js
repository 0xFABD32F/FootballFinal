/**
 * Countdown timer for upcoming matches
 */
document.addEventListener('DOMContentLoaded', function() {
    // Fetch upcoming matches data
    fetchUpcomingMatches();
    
    // Refresh every minute
    setInterval(fetchUpcomingMatches, 60000);
});

function fetchUpcomingMatches() {
    console.log('Fetching upcoming matches data...');
    fetch('get_upcoming_match.php')
        .then(response => response.json())
        .then(data => {
            console.log('Upcoming match data:', data);
            if (data.success && data.match) {
                console.log('Found upcoming match: ' + data.match.equipe1_nom + ' vs ' + data.match.equipe2_nom);
                updateCountdownUI(data.match);
            } else {
                console.log('No upcoming matches found');
                hideCountdown();
            }
        })
        .catch(error => {
            console.error('Error fetching upcoming match:', error);
        });
}

function updateCountdownUI(match) {
    const countdownContainer = document.getElementById('countdown-container');
    if (!countdownContainer) {
        createCountdownContainer();
        setTimeout(() => updateCountdownUI(match), 100);
        return;
    }
    
    // Make container visible
    countdownContainer.classList.remove('hidden');
    
    // Calculate countdown
    const matchDateTime = new Date(`${match.date_match}T${match.heure_debut}`);
    const now = new Date();
    const diff = matchDateTime - now;
    
    // Store match data for reference
    window.upcomingMatch = {
        id: match.id,
        time: matchDateTime,
        timeRemaining: diff
    };
    
    if (diff <= 0) {
        // Match time has arrived
        document.getElementById('countdown-message').textContent = `Match ${match.equipe1_nom} vs ${match.equipe2_nom} commence maintenant!`;
        document.getElementById('countdown-timer').textContent = '';
        
        // Add action button
        const actionBtn = document.getElementById('countdown-action');
        actionBtn.textContent = 'Voir le match';
        actionBtn.classList.remove('hidden');
        actionBtn.onclick = () => window.location.href = `mes_matchs.php?focus=${match.id}`;
        
        // Match is starting, no longer editable
        window.matchEditable = false;
        
        // Automatically update match status to 'en_cours'
        startMatch(match.id);
        
        return;
    }
    
    // Match is still in the future
    window.matchEditable = diff > 15 * 60 * 1000; // Editable if more than 15 minutes before match
    
    // Format countdown
    const days = Math.floor(diff / (1000 * 60 * 60 * 24));
    const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
    const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
    
    let countdownText = '';
    if (days > 0) {
        countdownText += `${days}j `;
    }
    if (hours > 0 || days > 0) {
        countdownText += `${hours}h `;
    }
    countdownText += `${minutes}m`;
    
    // Update UI elements
    document.getElementById('countdown-message').textContent = `Prochain match: ${match.equipe1_nom} vs ${match.equipe2_nom}`;
    document.getElementById('countdown-timer').textContent = countdownText;
    
    // Update action button based on editability
    const actionBtn = document.getElementById('countdown-action');
    if (!window.matchEditable) {
        actionBtn.textContent = 'Voir le match';
        actionBtn.classList.remove('hidden');
        actionBtn.onclick = () => window.location.href = `mes_matchs.php?focus=${match.id}`;
    } else {
        actionBtn.classList.add('hidden');
    }
    
    // Set timer color based on urgency
    const timerElement = document.getElementById('countdown-timer');
    if (diff < 30 * 60 * 1000) { // Less than 30 minutes
        timerElement.className = 'text-red-600 dark:text-red-400 font-bold';
    } else if (diff < 2 * 60 * 60 * 1000) { // Less than 2 hours
        timerElement.className = 'text-orange-500 dark:text-orange-400 font-bold';
    } else {
        timerElement.className = 'text-blue-600 dark:text-blue-400 font-semibold';
    }
}

function createCountdownContainer() {
    // Create countdown container if it doesn't exist
    const container = document.createElement('div');
    container.id = 'countdown-container';
    container.className = 'fixed bottom-0 left-0 right-0 bg-white dark:bg-gray-800 shadow-lg p-3 border-t border-gray-200 dark:border-gray-700 z-50 flex items-center justify-between dark:text-white transition-colors duration-200';
    
    // Create countdown content
    container.innerHTML = `
        <div class="flex items-center">
            <div class="bg-blue-500 dark:bg-blue-600 p-2 rounded-full mr-3">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div>
                <div id="countdown-message" class="font-medium"></div>
                <div id="countdown-timer" class="text-blue-600 dark:text-blue-400 font-semibold"></div>
            </div>
        </div>
        <button id="countdown-action" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md hidden transition-colors duration-200">
            Voir le match
        </button>
        <button id="countdown-close" class="ml-4 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    `;
    
    document.body.appendChild(container);
    
    // Add close functionality
    document.getElementById('countdown-close').addEventListener('click', function() {
        hideCountdown();
    });
}

function hideCountdown() {
    const countdown = document.getElementById('countdown-container');
    if (countdown) {
        countdown.classList.add('hidden');
    }
}

// Export for global use
window.isMatchEditable = function(matchId) {
    // If no upcoming match info available, allow editing by default
    if (!window.upcomingMatch) {
        return true;
    }
    
    // If a specific match ID is provided, check if it's the imminent match
    if (matchId && matchId !== window.upcomingMatch.id) {
        return true; // Allow editing other matches
    }
    
    // Otherwise use the global editable flag based on countdown time
    return window.matchEditable !== false;
};

// Function to start the match by updating its status to 'en_cours'
function startMatch(matchId) {
    console.log(`Starting match with ID: ${matchId}`);
    
    fetch('start_match.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `match_id=${matchId}`
    })
    .then(response => {
        console.log('Response status:', response.status);
        return response.json();
    })
    .then(data => {
        console.log('Server response data:', data);
        if (data.success) {
            console.log('Match started successfully');
            // Refresh page if we're on mes_matchs.php to show updated status
            if (window.location.pathname.includes('mes_matchs.php')) {
                window.location.reload();
            }
        } else {
            console.error('Error starting match:', data.error);
        }
    })
    .catch(error => {
        console.error('Error starting match (fetch error):', error);
    });
} 