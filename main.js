document.addEventListener('DOMContentLoaded', function() {
    // Form validation
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const requiredFields = form.querySelectorAll('[required]');
            let valid = true;
            requiredFields.forEach(field => {
                if (!field.value) {
                    valid = false;
                    field.classList.add('border-red-500');
                } else {
                    field.classList.remove('border-red-500');
                }
            });
            if (!valid) {
                e.preventDefault();
                alert('Please fill in all required fields');
            }
        });
    });

    // Dynamic form fields
    const playerPositions = ['Gardien', 'Défenseur', 'Milieu', 'Attaquant'];
    const staffPositions = ['Entraîneur', 'Assistant', 'Préparateur physique', 'Médecin'];
    
    // Populate position dropdowns
    const positionSelect = document.querySelector('select[name="position"]');
    if (positionSelect) {
        playerPositions.forEach(position => {
            const option = document.createElement('option');
            option.value = position;
            option.textContent = position;
            positionSelect.appendChild(option);
        });
    }
});