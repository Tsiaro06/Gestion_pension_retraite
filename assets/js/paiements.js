// Scripts pour la gestion des paiements
document.addEventListener('DOMContentLoaded', function() {
    // Pas besoin de setupModals, la modale est déjà bien placée dans paiements.php
});

// Fonction pour ouvrir la modale d'ajout
function openAddModal() {
    document.getElementById('add-modal').classList.remove('hidden');
}

// Fonction pour fermer la modale d'ajout
function closeAddModal() {
    document.getElementById('add-modal').classList.add('hidden');
}

// Fonction pour mettre à jour le tarif en fonction du pensionnaire sélectionné
function updateTarif() {
    const personneSelect = document.getElementById('add-personne');
    const tarifSelect = document.getElementById('add-tarif');
    
    if (personneSelect.value) {
        const selectedOption = personneSelect.options[personneSelect.selectedIndex];
        const numTarif = selectedOption.getAttribute('data-tarif');
        
        if (numTarif) {
            // Sélectionner automatiquement le tarif correspondant au pensionnaire
            tarifSelect.value = numTarif;
        } else {
            tarifSelect.value = '';
        }
    } else {
        tarifSelect.value = '';
    }
}

// Fonction pour formater la monnaie
function formatCurrency(amount) {
    return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'MGA', minimumFractionDigits: 0 }).format(amount);
}