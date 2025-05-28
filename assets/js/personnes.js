document.addEventListener('DOMContentLoaded', function () {
    const modalContainer = document.getElementById('modal-container');
    const addModal = document.getElementById('add-modal');
    const editModal = document.getElementById('edit-modal');
    const deleteModal = document.getElementById('delete-modal');
    const backdrop = modalContainer ? modalContainer.querySelector('.modal-backdrop') : null;

    // Vérifier si modalContainer existe
    if (!modalContainer) {
        console.error('Element with ID modal-container not found');
        return;
    }

    // Ajouter l'écouteur d'événements pour le backdrop
    if (backdrop) {
        backdrop.addEventListener('click', closeModal);
    } else {
        console.warn('Element with class modal-backdrop not found. Modal backdrop click-to-close functionality is disabled.');
    }

    // Fonction pour ouvrir un modal
    function openModal(modal) {
        modalContainer.classList.remove('hidden');
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden'; // Désactiver le défilement
    }

    // Fonction pour fermer tous les modals
    function closeModal() {
        modalContainer.classList.add('hidden');
        if (addModal) addModal.classList.add('hidden');
        if (editModal) editModal.classList.add('hidden');
        if (deleteModal) deleteModal.classList.add('hidden');
        document.body.style.overflow = ''; // Réactiver le défilement
        // Réinitialiser les formulaires
        document.getElementById('add-form')?.reset();
        document.getElementById('edit-form')?.reset();
    }

    // Fonction pour afficher un toast
    function showToast(message, type) {
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.innerHTML = `
            <div class="toast-content">
                <div class="toast-title">${type === 'success' ? 'Succès' : (type === 'warning' ? 'Avertissement' : 'Erreur')}</div>
                <div class="toast-message">${message}</div>
            </div>
            <button class="btn-icon" onclick="this.parentElement.remove()">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
            </button>
        `;
        document.body.appendChild(toast);
        toast.scrollIntoView({ behavior: 'smooth' });
        setTimeout(() => toast.remove(), 5000); // Supprimer après 5 secondes
    }

    // Gestion des modales
    window.openAddModal = function () {
        document.getElementById('add-form').reset(); // Réinitialiser le formulaire
        document.getElementById('add-IM').value = Math.floor(100000 + Math.random() * 900000); // Générer un IM aléatoire
        openModal(addModal);
        // Masquer les champs du conjoint par défaut
        const conjointFields = document.getElementById('add-conjoint-fields');
        const situation = document.getElementById('add-situation').value;
        conjointFields.style.display = situation === 'marié(e)' ? 'flex' : 'none';
    };

    window.closeAddModal = function () {
        closeModal();
    };

    window.openEditModal = function (IM) {
        fetch(`/pension_retraite/api/personne.php?IM=${encodeURIComponent(IM)}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const personne = data.personne;
                    document.getElementById('edit-IM').value = personne.IM;
                    document.getElementById('edit-Nom').value = personne.Nom;
                    document.getElementById('edit-Prenoms').value = personne.Prenoms;
                    document.getElementById('edit-datenais').value = personne.datenais || '';
                    document.getElementById('edit-Contact').value = personne.Contact || '';
                    document.getElementById('edit-statut').checked = personne.statut == 1;
                    document.getElementById('edit-situation').value = personne.situation || 'divorcé(e)';
                    document.getElementById('edit-NomConjoint').value = personne.NomConjoint || '';
                    document.getElementById('edit-PrenomConjoint').value = personne.PrenomConjoint || '';
                    // Vérifier que num_tarif est valide
                    const numTarifSelect = document.getElementById('edit-num_tarif');
                    numTarifSelect.value = personne.num_tarif || '';
                    if (!numTarifSelect.value) {
                        console.warn(`num_tarif non défini pour IM: ${IM}`);
                    }
                    openModal(editModal);
                    // Masquer les champs du conjoint par défaut
                    const conjointFields = document.getElementById('edit-conjoint-fields');
                    conjointFields.style.display = personne.situation === 'marié(e)' ? 'flex' : 'none';
                } else {
                    showToast(data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                showToast('Une erreur est survenue lors de la récupération des données.', 'error');
            });
    };

    window.closeEditModal = function () {
        closeModal();
    };

    window.openDeleteModal = function (IM) {
        document.getElementById('delete-IM').value = IM;
        openModal(deleteModal);
    };

    window.closeDeleteModal = function () {
        closeModal();
    };

    // Gestion du conjoint dans le formulaire d'ajout
    document.getElementById('add-situation')?.addEventListener('change', function () {
        const conjointFields = document.getElementById('add-conjoint-fields');
        if (this.value === 'marié(e)') {
            conjointFields.style.display = 'flex';
        } else {
            conjointFields.style.display = 'none';
        }
    });

    // Gestion du conjoint dans le formulaire de modification
    document.getElementById('edit-situation')?.addEventListener('change', function () {
        const conjointFields = document.getElementById('edit-conjoint-fields');
        if (this.value === 'marié(e)') {
            conjointFields.style.display = 'flex';
        } else {
            conjointFields.style.display = 'none';
        }
    });

    // Gestion de la soumission du formulaire d'ajout
    document.getElementById('add-form')?.addEventListener('submit', function (event) {
        event.preventDefault(); // Empêcher la soumission par défaut

        const formData = new FormData(this);
        fetch('', {
            method: 'POST',
            body: formData
        })
            .then(response => setTimeout(() => window.location.reload(), 1000))
            .catch(error => {
                console.error('Erreur:', error);
                closeAddModal();
                showToast('Une erreur est survenue lors de l\'ajout du pensionnaire.', 'error');
            });
    });
});