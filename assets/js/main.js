// document.addEventListener('DOMContentLoaded', function () {
//     // Référence au conteneur du modal
//     const modalContainer = document.getElementById('modal-container');

//     // Vérifier si modalContainer existe
//     if (modalContainer) {
//         // Trouver l'élément modal-backdrop
//         const backdrop = modalContainer.querySelector('.modal-backdrop');
        
//         // Ajouter l'écouteur d'événements si backdrop existe
//         if (backdrop) {
//             backdrop.addEventListener('click', closeModal);
//         } else {
//             console.warn('Element with class modal-backdrop not found. Modal backdrop click-to-close functionality is disabled.');
//         }
//     } else {
//         console.error('Element with ID modal-container not found');
//     }
// });

// // Fonction pour fermer tous les modals
// function closeModal() {
//     const modalContainer = document.getElementById('modal-container');
//     const addModal = document.getElementById('add-modal');
//     const editModal = document.getElementById('edit-modal');
//     const deleteModal = document.getElementById('delete-modal');

//     if (modalContainer) {
//         modalContainer.classList.add('hidden');
//     }
//     if (addModal) {
//         addModal.classList.add('hidden');
//     }
//     if (editModal) {
//         editModal.classList.add('hidden');
//     }
//     if (deleteModal) {
//         deleteModal.classList.add('hidden');
//     }
// }