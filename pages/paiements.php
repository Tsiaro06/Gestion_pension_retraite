<?php
// Inclusion des dépendances
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/functions.php';

$db = getDatabaseConnection();

// Gestion des actions
if (isset($_POST['action'])) {
    if ($_POST['action'] === 'create' && isset($_POST['IM'], $_POST['num_tarif'], $_POST['date'])) {
        $IM = cleanInput($_POST['IM']);
        $num_tarif = cleanInput($_POST['num_tarif']);
        $date = cleanInput($_POST['date']);
        
        // Vérifier que l'IM est non vide et existe dans personne
        if (empty($IM) || !getPersonneByIM($db, $IM)) {
            $alertMessage = "Erreur : IM invalide ou pensionnaire non trouvé";
            $alertType = "error";
        } else {
            try {
                $stmt = $db->prepare("INSERT INTO payer (IM, num_tarif, date) VALUES (:IM, :num_tarif, :date)");
                $stmt->bindParam(':IM', $IM);
                $stmt->bindParam(':num_tarif', $num_tarif);
                $stmt->bindParam(':date', $date);
                $stmt->execute();
                
                $alertMessage = "Paiement enregistré avec succès";
                $alertType = "success";
            } catch (PDOException $e) {
                $alertMessage = "Erreur: " . $e->getMessage();
                $alertType = "error";
            }
        }
    } elseif ($_POST['action'] === 'delete' && isset($_POST['IM'], $_POST['date'])) {
        $IM = cleanInput($_POST['IM']);
        $date = cleanInput($_POST['date']);
        
        try {
            $stmt = $db->prepare("DELETE FROM payer WHERE IM = :IM AND date = :date");
            $stmt->bindParam(':IM', $IM);
            $stmt->bindParam(':date', $date);
            $stmt->execute();
            
            $alertMessage = "Paiement supprimé avec succès";
            $alertType = "success";
        } catch (PDOException $e) {
            $alertMessage = "Erreur: " . $e->getMessage();
            $alertType = "error";
        }
    }
}

// Gestion des filtres
$startDate = isset($_GET['start_date']) ? cleanInput($_GET['start_date']) : '';
$endDate = isset($_GET['end_date']) ? cleanInput($_GET['end_date']) : '';

// Récupérer les paiements
$query = "
    SELECT 
        p.IM, 
        p.num_tarif, 
        p.date,
        CONCAT(pe.Nom, ' ', pe.Prenoms) AS pensionnaire,
        t.diplome,
        t.montant
    FROM payer p
    LEFT JOIN personne pe ON p.IM = pe.IM
    LEFT JOIN tarif t ON p.num_tarif = t.num_tarif
    WHERE 1=1
";

if (!empty($startDate)) {
    $query .= " AND p.date >= :start_date";
}
if (!empty($endDate)) {
    $query .= " AND p.date <= :end_date";
}

$query .= " ORDER BY p.date DESC";

$stmt = $db->prepare($query);

if (!empty($startDate)) {
    $stmt->bindParam(':start_date', $startDate);
}
if (!empty($endDate)) {
    $stmt->bindParam(':end_date', $endDate);
}

$stmt->execute();
$paiements = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les pensionnaires et tarifs uniquement si aucun filtre n'est appliqué
if (empty($startDate) && empty($endDate)) {
    // Récupérer les pensionnaires pour le formulaire
    $stmt = $db->query("SELECT IM, Nom, Prenoms, num_tarif FROM personne ORDER BY Nom ASC");
    $personnes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Récupérer les tarifs pour le formulaire
    $stmt = $db->query("SELECT * FROM tarif ORDER BY diplome ASC");
    $tarifs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $personnes = [];
    $tarifs = [];
}
?>

<div class="page-header mb-4">
    <div>
        <h2 class="text-3xl font-bold">Paiements</h2>
        <p class="text-muted">Gérez les paiements des pensions et générez les reçus</p>
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

<!-- Filtres -->
<div class="card mb-4">
    <div class="card-content">
        <form action="pages/paiements.php" method="GET" class="form-row">
            <div class="form-col">
                <label class="form-label" for="start_date">Date de début</label>
                <input type="date" id="start_date" name="start_date" class="form-input" value="<?php echo htmlspecialchars($startDate); ?>">
            </div>
            <div class="form-col">
                <label class="form-label" for="end_date">Date de fin</label>
                <input type="date" id="end_date" name="end_date" class="form-input" value="<?php echo htmlspecialchars($endDate); ?>">
            </div>
            <div style="flex: 0 0 auto; align-self: flex-end;">
                <button type="submit" class="btn btn-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 2H2a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h20a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2z"></path><path d="M7 2v20"></path><path d="M12 2v20"></path><path d="M17 2v20"></path></svg>
                    Filtrer
                </button>
                <a href="pages/paiements.php" class="btn btn-outline">
                    Réinitialiser
                </a>
            </div>
        </form>
    </div>
</div>

<?php if (empty($startDate) && empty($endDate)): ?>
<!-- Bouton ajouter -->
<div class="mb-4">
    <button class="btn btn-primary" onclick="openAddModal()">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
        Nouveau Paiement
    </button>
</div>
<?php endif; ?>

<!-- Liste des paiements -->
<div class="card">
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>IM</th>
                    <th>Diplôme</th>
                    <th>Montant</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($paiements) > 0): ?>
                    <?php foreach ($paiements as $paiement): ?>
                        <tr>
                            <td><?php echo formatDate($paiement['date']); ?></td>
                            <td><?php echo !empty($paiement['IM']) ? htmlspecialchars($paiement['IM']) : 'IM manquant'; ?></td>
                            <td><?php echo htmlspecialchars($paiement['diplome'] ?? 'Non défini'); ?></td>
                            <td><?php echo formatCurrency($paiement['montant'] ?? 0); ?></td>
                            <td>
                                <div class="action-buttons">
                                    <a href="pages/recu.php?IM=<?php echo $paiement['IM']; ?>&date=<?php echo $paiement['date']; ?>" class="btn btn-icon" title="Générer un reçu">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"></path><line x1="4" y1="22" x2="4" y2="15"></line></svg>
                                    </a>
                                    <?php if (empty($startDate) && empty($endDate)): ?>
                                    <button class="btn btn-icon" onclick="openDeleteModal('<?php echo $paiement['IM']; ?>', '<?php echo $paiement['date']; ?>')" title="Supprimer">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="text-center text-muted">Aucun paiement trouvé</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if (empty($startDate) && empty($endDate)): ?>
<!-- Modals -->
<!-- Formulaire d'ajout -->
<div id="add-modal" class="hidden">
    <div class="modal-header">
        <h3>Ajouter un paiement</h3>
        <button class="modal-close-btn" onclick="closeAddModal()">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
        </button>
    </div>
    <div class="modal-body">
        <form action="" method="POST" id="add-form">
            <input type="hidden" name="action" value="create">
            
            <div class="form-group">
                <label class="form-label" for="add-personne">Pensionnaire</label>
                <select id="add-personne" name="IM" class="form-select" required onchange="updateTarif()">
                    <option value="">Sélectionner un pensionnaire</option>
                    <?php foreach ($personnes as $personne): ?>
                        <option value="<?php echo $personne['IM']; ?>" data-tarif="<?php echo $personne['num_tarif']; ?>">
                            <?php echo $personne['Nom'] . ' ' . $personne['Prenoms'] . ' (' . $personne['IM'] . ')'; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="add-num_tarif">Tarif</label>
                <select id="add-num_tarif" name="num_tarif" class="form-select" required>
                    <option value="">Sélectionner un tarif</option>
                    <?php foreach ($tarifs as $tarif): ?>
                        <option value="<?php echo $tarif['num_tarif']; ?>">
                            <?php echo $tarif['diplome'] . ' - ' . formatCurrency($tarif['montant']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="add-date">Date</label>
                <input type="date" id="add-date" name="date" class="form-input" required>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeAddModal()">Annuler</button>
                <button type="submit" class="btn btn-primary">Enregistrer</button>
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
        <p>Êtes-vous sûr de vouloir supprimer ce paiement ? Cette action est irréversible.</p>
        
        <form action="" method="POST" id="delete-form">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="IM" id="delete-IM">
            <input type="hidden" name="date" id="delete-date">
            
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeDeleteModal()">Annuler</button>
                <button type="submit" class="btn btn-destructive">Supprimer</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<script>
function openAddModal() {
    document.getElementById('add-modal').classList.remove('hidden');
}

function closeAddModal() {
    document.getElementById('add-modal').classList.add('hidden');
    document.getElementById('add-form').reset();
}

function updateTarif() {
    const personneSelect = document.getElementById('add-personne');
    const tarifSelect = document.getElementById('add-num_tarif');
    const selectedOption = personneSelect.options[personneSelect.selectedIndex];
    const num_tarif = selectedOption.getAttribute('data-tarif');
    if (num_tarif) {
        tarifSelect.value = num_tarif;
    } else {
        tarifSelect.value = '';
    }
}

function openDeleteModal(IM, date) {
    document.getElementById('delete-IM').value = IM;
    document.getElementById('delete-date').value = date;
    document.getElementById('delete-modal').classList.remove('hidden');
}

function closeDeleteModal() {
    document.getElementById('delete-modal').classList.add('hidden');
}
</script>