<?php
require_once './config/database.php';
$db = getDatabaseConnection();

// Gestion des actions
if (isset($_POST['action'])) {
    if ($_POST['action'] === 'create' && isset($_POST['numPension'], $_POST['NomConjoint'], $_POST['PrenomConjoint'], $_POST['montant'])) {
        // Traitement de la création d'un conjoint
        $numPension = cleanInput($_POST['numPension']);
        $NomConjoint = cleanInput($_POST['NomConjoint']);
        $PrenomConjoint = cleanInput($_POST['PrenomConjoint']);
        $montant = (float)cleanInput($_POST['montant']);
        
        try {
            $stmt = $db->prepare("INSERT INTO conjoint (numPension, NomConjoint, PrenomConjoint, montant) VALUES (:numPension, :NomConjoint, :PrenomConjoint, :montant)");
            $stmt->bindParam(':numPension', $numPension);
            $stmt->bindParam(':NomConjoint', $NomConjoint);
            $stmt->bindParam(':PrenomConjoint', $PrenomConjoint);
            $stmt->bindParam(':montant', $montant);
            $stmt->execute();
            
            $alertMessage = "Conjoint ajouté avec succès";
            $alertType = "success";
        } catch (PDOException $e) {
            $alertMessage = "Erreur: " . $e->getMessage();
            $alertType = "error";
        }
    } elseif ($_POST['action'] === 'update' && isset($_POST['numPension'], $_POST['NomConjoint'], $_POST['PrenomConjoint'], $_POST['montant'])) {
        // Traitement de la mise à jour d'un conjoint
        $numPension = cleanInput($_POST['numPension']);
        $NomConjoint = cleanInput($_POST['NomConjoint']);
        $PrenomConjoint = cleanInput($_POST['PrenomConjoint']);
        $montant = (float)cleanInput($_POST['montant']);
        
        try {
            $stmt = $db->prepare("UPDATE conjoint SET NomConjoint = :NomConjoint, PrenomConjoint = :PrenomConjoint, montant = :montant WHERE numPension = :numPension");
            $stmt->bindParam(':numPension', $numPension);
            $stmt->bindParam(':NomConjoint', $NomConjoint);
            $stmt->bindParam(':PrenomConjoint', $PrenomConjoint);
            $stmt->bindParam(':montant', $montant);
            $stmt->execute();
            
            $alertMessage = "Conjoint mis à jour avec succès";
            $alertType = "success";
        } catch (PDOException $e) {
            $alertMessage = "Erreur: " . $e->getMessage();
            $alertType = "error";
        }
    } elseif ($_POST['action'] === 'delete' && isset($_POST['numPension'])) {
        // Traitement de la suppression d'un conjoint
        $numPension = cleanInput($_POST['numPension']);
        
        try {
            $stmt = $db->prepare("DELETE FROM conjoint WHERE numPension = :numPension");
            $stmt->bindParam(':numPension', $numPension);
            $stmt->execute();
            
            $alertMessage = "Conjoint supprimé avec succès";
            $alertType = "success";
        } catch (PDOException $e) {
            $alertMessage = "Erreur: " . $e->getMessage();
            $alertType = "error";
        }
    }
}

// Récupérer les conjoints
$stmt = $db->query("SELECT * FROM conjoint ORDER BY NomConjoint ASC");
$conjoints = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="page-header mb-4">
    <div>
        <h2 class="text-3xl font-bold">Conjoints</h2>
        <p class="text-muted">Gérez les pensions des conjoints de pensionnaires décédés</p>
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
        Nouveau Conjoint
    </button>
</div>

<!-- Liste des conjoints -->
<div class="card">
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Numéro</th>
                    <th>Nom</th>
                    <th>Prénoms</th>
                    <th class="text-right">Montant</th>
                    <th class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($conjoints) > 0): ?>
                    <?php foreach ($conjoints as $conjoint): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($conjoint['numPension']); ?></td>
                            <td><?php echo htmlspecialchars($conjoint['NomConjoint']); ?></td>
                            <td><?php echo htmlspecialchars($conjoint['PrenomConjoint']); ?></td>
                            <td class="text-right font-medium"><?php echo formatCurrency($conjoint['montant']); ?></td>
                            <td>
                                <div class="action-buttons">
                                    <button class="btn btn-icon" onclick="openEditModal('<?php echo $conjoint['numPension']; ?>')" title="Modifier">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                                    </button>
                                    <button class="btn btn-icon" onclick="openDeleteModal('<?php echo $conjoint['numPension']; ?>')" title="Supprimer">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="text-center text-muted">Aucun conjoint trouvé</td>
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
        <h3>Ajouter un conjoint</h3>
        <button class="modal-close-btn" onclick="closeAddModal()">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
        </button>
    </div>
    <div class="modal-body">
        <form action="" method="POST" id="add-form">
            <input type="hidden" name="action" value="create">
            
            <div class="form-group">
                <label class="form-label" for="add-numPension">Numéro de pension</label>
                <input type="text" id="add-numPension" name="numPension" class="form-input" placeholder="C003" required>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="add-NomConjoint">Nom</label>
                <input type="text" id="add-NomConjoint" name="NomConjoint" class="form-input" placeholder="RAKOTO" required>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="add-PrenomConjoint">Prénoms</label>
                <input type="text" id="add-PrenomConjoint" name="PrenomConjoint" class="form-input" placeholder="Marie" required>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="add-montant">Montant</label>
                <input type="number" id="add-montant" name="montant" class="form-input" placeholder="120000" required>
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
        <h3>Modifier un conjoint</h3>
        <button class="modal-close-btn" onclick="closeEditModal()">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
        </button>
    </div>
    <div class="modal-body">
        <form action="" method="POST" id="edit-form">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="numPension" id="edit-numPension">
            
            <div class="form-group">
                <label class="form-label" for="edit-numPension_display">Numéro de pension</label>
                <input type="text" id="edit-numPension_display" class="form-input" readonly>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="edit-NomConjoint">Nom</label>
                <input type="text" id="edit-NomConjoint" name="NomConjoint" class="form-input" required>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="edit-PrenomConjoint">Prénoms</label>
                <input type="text" id="edit-PrenomConjoint" name="PrenomConjoint" class="form-input" required>
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
        <p>Êtes-vous sûr de vouloir supprimer ce conjoint ? Cette action est irréversible.</p>
        
        <form action="" method="POST" id="delete-form">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="numPension" id="delete-numPension">
            
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeDeleteModal()">Annuler</button>
                <button type="submit" class="btn btn-destructive">Supprimer</button>
            </div>
        </form>
    </div>
</div>

<script src="assets/js/conjoints.js"></script>