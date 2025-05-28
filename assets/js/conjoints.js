// Scripts pour la gestion des conjoints
document.addEventListener('DOMContentLoaded', function() {
    // Initialisation
    setupModals();
});

function setupModals() {
    // Modales des conjoints
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
    document.getElementById('modal-title').textContent = "Ajouter un conjoint";
    document.getElementById('add-form').reset(); // Réinitialiser le formulaire
    document.getElementById('add-modal').classList.remove('hidden');
    document.getElementById('modal-container').classList.remove('hidden');
}

// Fonction pour fermer la modale d'ajout
function closeAddModal() {
    document.getElementById('add-modal').classList.add('hidden');
    document.getElementById('modal-container').classList.add('hidden');
}

// Fonction pour ouvrir la modale de modification
function openEditModal(numPension) {
    // Récupérer les données du conjoint
    fetch(`api/conjoint.php?numPension=${numPension}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const conjoint = data.conjoint;
                
                document.getElementById('edit-numPension').value = conjoint.numPension;
                document.getElementById('edit-NomConjoint').value = conjoint.NomConjoint;
                document.getElementById('edit-PrenomConjoint').value = conjoint.PrenomConjoint;
                document.getElementById('edit-montant').value = conjoint.montant;
                
                document.getElementById('modal-title').textContent = "Modifier un conjoint";
                document.getElementById('edit-modal').classList.remove('hidden');
                document.getElementById('modal-container').classList.remove('hidden');
            } else {
                showToast("Erreur: Impossible de récupérer les données du conjoint", "error");
            }
        })
        .catch(error => {
            showToast("Erreur: " + error.message, "error");
            console.error("Erreur lors de la récupération du conjoint:", error);
        });
}

// Fonction pour fermer la modale de modification
function closeEditModal() {
    document.getElementById('edit-modal').classList.add('hidden');
    document.getElementById('modal-container').classList.add('hidden');
}

// Fonction pour ouvrir la modale de suppression
function openDeleteModal(numPension) {
    document.getElementById('delete-numPension').value = numPension;
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
}