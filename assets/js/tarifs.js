
// Scripts pour la gestion des tarifs
document.addEventListener('DOMContentLoaded', function() {
    // Initialisation
    setupModals();
});

function setupModals() {
    // Modales des tarifs
    const addModal = document.getElementById('add-modal');
    const editModal = document.getElementById('edit-modal');
    const deleteModal = document.getElementById('delete-modal');
    
    // Mettre ces modales dans le conteneur de modales
    if (addModal) {
        addModal.parentNode.removeChild(addModal);
        document.getElementById('modal-body').appendChild(addModal);
    }
    
    if (editModal) {
        editModal.parentNode.removeChild(editModal);
        document.getElementById('modal-body').appendChild(editModal);
    }
    
    if (deleteModal) {
        deleteModal.parentNode.removeChild(deleteModal);
        document.getElementById('modal-body').appendChild(deleteModal);
    }
}

// Fonction pour ouvrir la modale d'ajout
function openAddModal() {
    document.getElementById('modal-title').textContent = "Ajouter un tarif";
    document.getElementById('add-modal').classList.remove('hidden');
    document.getElementById('modal-container').classList.remove('hidden');
}

// Fonction pour fermer la modale d'ajout
function closeAddModal() {
    document.getElementById('add-modal').classList.add('hidden');
    document.getElementById('modal-container').classList.add('hidden');
}

// Fonction pour ouvrir la modale de modification
function openEditModal(numTarif) {
    console.log(numTarif)

    // Récupérer les données du tarif
    fetch(`api/tarif.php?num_tarif=${numTarif}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Réponse réseau non valide, statut: ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                const tarif = data.tarif;
                
                // Assurez-vous que tous les éléments existent avant de définir leurs valeurs
                const numTarifInput = document.getElementById('edit-num_tarif');
                const numTarifDisplay = document.getElementById('edit-num_tarif_display');
                const diplomeInput = document.getElementById('edit-diplome');
                const categorieInput = document.getElementById('edit-categorie');
                const montantInput = document.getElementById('edit-montant');
                
                if (numTarifInput) numTarifInput.value = tarif.num_tarif;
                if (numTarifDisplay) numTarifDisplay.value = tarif.num_tarif;
                if (diplomeInput) diplomeInput.value = tarif.diplome;
                if (categorieInput) categorieInput.value = tarif.categorie;
                if (montantInput) montantInput.value = tarif.montant;
                
                document.getElementById('modal-title').textContent = "Modifier un tarif";
                document.getElementById('edit-modal').classList.remove('hidden');
                document.getElementById('modal-container').classList.remove('hidden');
            } else {
                showToast("Erreur: " + (data.message || "Impossible de récupérer les données du tarif"), "error");
            }
        })
        .catch(error => {
            showToast("Erreur: " + error.message, "error");
            console.error("Erreur lors de la récupération du tarif:", error);
        });
}

// Fonction pour fermer la modale de modification
function closeEditModal() {
    document.getElementById('edit-modal').classList.add('hidden');
    document.getElementById('modal-container').classList.add('hidden');
}

// Fonction pour ouvrir la modale de suppression
function openDeleteModal(numTarif) {
    document.getElementById('delete-num_tarif').value = numTarif;
    document.getElementById('modal-title').textContent = "Confirmer la suppression";
    document.getElementById('delete-modal').classList.remove('hidden');
    document.getElementById('modal-container').classList.remove('hidden');
}

// Fonction pour fermer la modale de suppression
function closeDeleteModal() {
    document.getElementById('delete-modal').classList.add('hidden');
    document.getElementById('modal-container').classList.add('hidden');
}

// Fonction pour afficher un toast
function showToast(message, type) {
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.innerHTML = `
        <div class="toast-content">
            <div class="toast-title">${type === 'success' ? 'Succès' : 'Erreur'}</div>
            <div class="toast-message">${message}</div>
        </div>
        <button class="btn-icon" onclick="this.parentElement.remove()">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
        </button>
    `;
    
    document.body.appendChild(toast);
    
    // Supprimer le toast après 5 secondes
    setTimeout(() => {
        if (toast.parentElement) {
            toast.remove();
        }
    }, 5000);

    function updateTarif() {
        const form = document.getElementById('edit-form');
        const formData = new FormData(form);
    
        fetch('api/update_tarif.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            alert(data.message);
            if (data.status === 'success') {
                location.reload(); // Recharge la page après succès
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            alert('Erreur de mise à jour');
        });
    }
    
}