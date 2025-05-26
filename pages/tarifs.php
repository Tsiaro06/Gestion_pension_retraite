<?php
// Inclusion des dépendances
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/functions.php';

$db = getDatabaseConnection();


// Gestion des actions
if (isset($_POST['action'])) {
    if ($_POST['action'] === 'create' && isset($_POST['num_tarif'], $_POST['diplome'], $_POST['categorie'], $_POST['montant'])) {
        // Traitement de la création d'un tarif
        $num_tarif = cleanInput($_POST['num_tarif']);
        $diplome = cleanInput($_POST['diplome']);
        $categorie = cleanInput($_POST['categorie']);
        $montant = (int)cleanInput($_POST['montant']);
        
        try {
            $stmt = $db->prepare("INSERT INTO tarif (num_tarif, diplome, categorie, montant) VALUES (:num_tarif, :diplome, :categorie, :montant)");
            $stmt->bindParam(':num_tarif', $num_tarif);
            $stmt->bindParam(':diplome', $diplome);
            $stmt->bindParam(':categorie', $categorie);
            $stmt->bindParam(':montant', $montant);
            $stmt->execute();
            
            $alertMessage = "Tarif ajouté avec succès";
            $alertType = "success";
        } catch (PDOException $e) {
            $alertMessage = "Erreur: " . $e->getMessage();
            $alertType = "error";
        }
    } elseif ($_POST['action'] === 'update' && isset($_POST['num_tarif'], $_POST['diplome'], $_POST['categorie'], $_POST['montant'])) {
        // Traitement de la mise à jour d'un tarif
        $num_tarif = cleanInput($_POST['num_tarif']);
        $diplome = cleanInput($_POST['diplome']);
        $categorie = cleanInput($_POST['categorie']);
        $montant = (int)cleanInput($_POST['montant']);
        
        try {
            $stmt = $db->prepare("UPDATE tarif SET diplome = :diplome, categorie = :categorie, montant = :montant WHERE num_tarif = :num_tarif");
            $stmt->bindParam(':num_tarif', $num_tarif);
            $stmt->bindParam(':diplome', $diplome);
            $stmt->bindParam(':categorie', $categorie);
            $stmt->bindParam(':montant', $montant);
            $stmt->execute();
            
            $alertMessage = "Tarif mis à jour avec succès";
            $alertType = "success";
        } catch (PDOException $e) {
            $alertMessage = "Erreur: " . $e->getMessage();
            $alertType = "error";
        }
    } elseif ($_POST['action'] === 'delete' && isset($_POST['num_tarif'])) {
        // Traitement de la suppression d'un tarif
        $num_tarif = cleanInput($_POST['num_tarif']);
        
        try {
            // Vérifier si le tarif est utilisé par des pensionnaires
            $stmt = $db->prepare("SELECT COUNT(*) FROM personne WHERE num_tarif = :num_tarif");
            $stmt->bindParam(':num_tarif', $num_tarif);
            $stmt->execute();
            
            if ($stmt->fetchColumn() > 0) {
                $alertMessage = "Impossible de supprimer ce tarif car il est utilisé par des pensionnaires";
                $alertType = "error";
            } else {
                // Vérifier si le tarif est utilisé dans des paiements
                $stmt = $db->prepare("SELECT COUNT(*) FROM payer WHERE num_tarif = :num_tarif");
                $stmt->bindParam(':num_tarif', $num_tarif);
                $stmt->execute();
                
                if ($stmt->fetchColumn() > 0) {
                    $alertMessage = "Impossible de supprimer ce tarif car il est utilisé dans des paiements";
                    $alertType = "error";
                } else {
                    $stmt = $db->prepare("DELETE FROM tarif WHERE num_tarif = :num_tarif");
                    $stmt->bindParam(':num_tarif', $num_tarif);
                    $stmt->execute();
                    
                    $alertMessage = "Tarif supprimé avec succès";
                    $alertType = "success";
                }
            }
        } catch (PDOException $e) {
            $alertMessage = "Erreur: " . $e->getMessage();
            $alertType = "error";
        }
    }
}

// Récupérer les tarifs
$stmt = $db->query("SELECT * FROM tarif ORDER BY diplome ASC");
$tarifs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="page-header mb-4">
    <div>
        <h2 class="text-3xl font-bold">Tarifs</h2>
        <p class="text-muted">Gérez les tarifs des pensions en fonction des diplômes et catégories</p>
    </div>
</div>

<?php if (isset($alertMessage)): ?>
<div class="toast toast-<?php echo $alertType; ?>" id="alert-toast">
    <div class="toast-content">
        <div class="toast-title"><?php echo $alertType === 'success' ? 'Succès' : 'Erreur'; ?></div>
        <div class="toast-message"><?php echo $alertMessage; ?></div>
    </div>
    <button class="btn-icon" onclick="this.parentElement.remove()">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
    </button>
</div>
<?php endif; ?>

<!-- Bouton ajouter -->
<div class="mb-4">
    <button class="btn btn-primary" onclick="openAddModal()">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
        Nouveau Tarif
    </button>
</div>

<!-- Liste des tarifs -->
<div class="card">
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Numéro</th>
                    <th>Diplôme</th>
                    <th>Catégorie</th>
                    <th class="text-right">Montant</th>
                    <th class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($tarifs) > 0): ?>
                    <?php foreach ($tarifs as $tarif): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($tarif['num_tarif']); ?></td>
                            <td><?php echo htmlspecialchars($tarif['diplome']); ?></td>
                            <td><?php echo htmlspecialchars($tarif['categorie']); ?></td>
                            <td class="text-right font-medium"><?php echo formatCurrency($tarif['montant']); ?></td>
                            <td>
                                <div class="action-buttons">
                                    <button class="btn btn-icon" onclick="openEditModal('<?php echo $tarif['num_tarif']; ?>')" title="Modifier">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                                    </button>
                                    <button class="btn btn-icon" onclick="openDeleteModal('<?php echo $tarif['num_tarif']; ?>')" title="Supprimer">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="text-center text-muted">Aucun tarif trouvé</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modals -->
<!-- Formulaire d'ajout -->
<div id="add-modal" class="hidden">
    <div class="modal-header">
        <h3>Ajouter un tarif</h3>
        <button class="modal-close-btn" onclick="closeAddModal()">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
        </button>
    </div>
    <div class="modal-body">
        <form action="" method="POST" id="add-form">
            <input type="hidden" name="action" value="create">
            
            <div class="form-group">
                <label class="form-label" for="add-num_tarif">Numéro de tarif</label>
                <input type="text" id="add-num_tarif" name="num_tarif" class="form-input" placeholder="T007" required>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="add-diplome">Diplôme</label>
                <input type="text" id="add-diplome" name="diplome" class="form-input" placeholder="Master" required>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="add-categorie">Catégorie</label>
                <input type="text" id="add-categorie" name="categorie" class="form-input" placeholder="E" required>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="add-montant">Montant</label>
                <input type="number" id="add-montant" name="montant" class="form-input" placeholder="400000" required>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeAddModal()">Annuler</button>
                <button type="submit" class="btn btn-primary">Ajouter</button>
            </div>
        </form>
    </div>
</div>

<!-- Formulaire de modification -->
<div id="edit-modal" class="hidden">
    <div class="modal-header">
        <h3>Modifier un tarif</h3>
        <button class="modal-close-btn" onclick="closeEditModal()">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
        </button>
    </div>
    <div class="modal-body">
        <form action="" method="POST" id="edit-form">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="num_tarif" id="edit-num_tarif">
            
            <div class="form-group">
                <label class="form-label" for="edit-num_tarif_display">Numéro de tarif</label>
                <input type="text" id="edit-num_tarif_display" class="form-input" readonly>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="edit-diplome">Diplôme</label>
                <input type="text" id="edit-diplome" name="diplome" class="form-input" required>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="edit-categorie">Catégorie</label>
                <input type="text" id="edit-categorie" name="categorie" class="form-input" required>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="edit-montant">Montant</label>
                <input type="number" id="edit-montant" name="montant" class="form-input" required>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeEditModal()">Annuler</button>
                <button type="submit" class="btn btn-primary">Enregistrer les modifications</button>
            </div>
        </form>
    </div>
</div>

<!-- Confirmation de suppression -->
<div id="delete-modal" class="hidden">
    <div class="modal-header">
        <h3>Confirmer la suppression</h3>
        <button class="modal-close-btn" onclick="closeDeleteModal()">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
        </button>
    </div>
    <div class="modal-body">
        <p>Êtes-vous sûr de vouloir supprimer ce tarif ? Cette action est irréversible.</p>
        
        <form action="" method="POST" id="delete-form">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="num_tarif" id="delete-num_tarif">
            
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeDeleteModal()">Annuler</button>
                <button type="submit" class="btn btn-destructive">Supprimer</button>
            </div>
        </form>
    </div>
</div>

<script src="assets/js/tarifs.js"></script>