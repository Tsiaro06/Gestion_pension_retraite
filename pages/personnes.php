<?php
// Inclusion des dépendances
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/functions.php';

$db = getDatabaseConnection();

// Initialisation des variables pour les messages d'alerte
$alertMessage = null;
$alertType = null;

// Fonction pour envoyer une réponse JSON et arrêter l'exécution
function sendJsonResponse($success, $message) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'message' => $message
    ]);
    exit;
}

// Vérifier si la requête est AJAX
$isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

// Endpoint pour récupérer un pensionnaire par IM (pour openEditModal)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['IM'])) {
    $IM = cleanInput($_GET['IM']);
    try {
        $stmt_personne = $db->prepare("SELECT Nom, Prenoms, IM, datenais, Contact, statut, situation, NomConjoint, PrenomConjoint, num_tarif FROM personne WHERE IM = :IM");
        $stmt_personne->bindParam(':IM', $IM);
        $stmt_personne->execute();
        $personne = $stmt_personne->fetch(PDO::FETCH_ASSOC);
        if ($personne) {
            sendJsonResponse(true, $personne);
        } else {
            sendJsonResponse(false, 'Pensionnaire non trouvé');
        }
    } catch (PDOException $e) {
        sendJsonResponse(false, 'Erreur: ' . $e->getMessage());
    }
}

// Gestion des actions
if (isset($_POST['action'])) {
    if ($_POST['action'] === 'create' && isset($_POST['IM'], $_POST['Nom'], $_POST['Prenoms'], $_POST['num_tarif'], $_POST['datenais'], $_POST['situation'])) {
        error_log("Données POST reçues (create): " . print_r($_POST, true)); // Log des données POST
        $IM = cleanInput($_POST['IM']);
        $Nom = cleanInput($_POST['Nom']);
        $Prenoms = cleanInput($_POST['Prenoms']);
        $datenais = cleanInput($_POST['datenais']);
        $Contact = cleanInput($_POST['Contact'] ?? '');
        $statut = isset($_POST['statut']) ? 1 : 0;
        $situation = cleanInput($_POST['situation']);
        $NomConjoint = cleanInput($_POST['NomConjoint'] ?? '');
        $PrenomConjoint = cleanInput($_POST['PrenomConjoint'] ?? '');
        $num_tarif = cleanInput($_POST['num_tarif']);

        // Validation des champs obligatoires
        if (empty($IM) || empty($Nom) || empty($Prenoms) || empty($num_tarif) || empty($datenais) || empty($situation)) {
            $message = "Erreur : Tous les champs obligatoires (IM, Nom, Prénoms, Date de naissance, Situation, Tarif) doivent être remplis";
            if ($isAjax) {
                sendJsonResponse(false, $message);
            } else {
                $alertMessage = $message;
                $alertType = "error";
            }
        } else {
            // Vérifier que num_tarif existe
            $tarif = getTariffByNum($db, $num_tarif);
            error_log("Create - num_tarif: $num_tarif, tarif: " . print_r($tarif, true));
            if (!$tarif) {
                $message = "Erreur : Tarif invalide ou non trouvé pour num_tarif=$num_tarif";
                if ($isAjax) {
                    sendJsonResponse(false, $message);
                } else {
                    $alertMessage = $message;
                    $alertType = "error";
                }
            } else {
                try {
                    // Insérer le pensionnaire
                    $stmt = $db->prepare("INSERT INTO personne (IM, Nom, Prenoms, datenais, Contact, statut, situation, NomConjoint, PrenomConjoint, num_tarif) VALUES (:IM, :Nom, :Prenoms, :datenais, :Contact, :statut, :situation, :NomConjoint, :PrenomConjoint, :num_tarif)");
                    $stmt->bindParam(':IM', $IM);
                    $stmt->bindParam(':Nom', $Nom);
                    $stmt->bindParam(':Prenoms', $Prenoms);
                    $stmt->bindParam(':datenais', $datenais);
                    $stmt->bindParam(':Contact', $Contact);
                    $stmt->bindParam(':statut', $statut, PDO::PARAM_INT);
                    $stmt->bindParam(':situation', $situation);
                    $stmt->bindParam(':NomConjoint', $NomConjoint);
                    $stmt->bindParam(':PrenomConjoint', $PrenomConjoint);
                    $stmt->bindParam(':num_tarif', $num_tarif);
                    $stmt->execute();

                    // Si le pensionnaire est décédé et marié avec un conjoint, ajouter une entrée dans la table conjoint
                    if ($statut == 0 && $situation === 'marié(e)' && !empty($NomConjoint) && !empty($PrenomConjoint)) {
                        $newMontant = (float)$tarif['montant'] * 0.4;
                        try {
                            $stmt_conjoint = $db->prepare("INSERT INTO conjoint (numPension, NomConjoint, PrenomConjoint, montant) VALUES (:numPension, :NomConjoint, :PrenomConjoint, :montant)");
                            $stmt_conjoint->bindParam(':numPension', $IM);
                            $stmt_conjoint->bindParam(':NomConjoint', $NomConjoint);
                            $stmt_conjoint->bindParam(':PrenomConjoint', $PrenomConjoint);
                            $stmt_conjoint->bindParam(':montant', $newMontant);
                            $stmt_conjoint->execute();
                            $message = "Pensionnaire et conjoint ajoutés avec succès";
                        } catch (PDOException $e) {
                            $message = "Pensionnaire ajouté, mais échec de l'ajout du conjoint: " . $e->getMessage();
                            if ($isAjax) {
                                sendJsonResponse(true, $message);
                            } else {
                                $alertMessage = $message;
                                $alertType = "warning";
                            }
                            error_log("Erreur ajout conjoint: " . $e->getMessage());
                        }
                    } else {
                        $message = "Pensionnaire ajouté avec succès";
                    }

                    if ($isAjax) {
                        sendJsonResponse(true, $message);
                    } else {
                        $alertMessage = $message;
                        $alertType = "success";
                    }
                } catch (PDOException $e) {
                    $message = "Erreur lors de l'ajout: " . $e->getMessage();
                    if ($isAjax) {
                        sendJsonResponse(false, $message);
                    } else {
                        $alertMessage = $message;
                        $alertType = "error";
                        error_log("Erreur dans create personne: " . $e->getMessage());
                    }
                }
            }
        }
    } elseif ($_POST['action'] === 'update' && isset($_POST['IM'], $_POST['Nom'], $_POST['Prenoms'], $_POST['num_tarif'], $_POST['datenais'], $_POST['situation'])) {
        error_log("Données POST reçues (update): " . print_r($_POST, true)); // Log des données POST
        $IM = cleanInput($_POST['IM']);
        $Nom = cleanInput($_POST['Nom']);
        $Prenoms = cleanInput($_POST['Prenoms']);
        $datenais = cleanInput($_POST['datenais']);
        $Contact = cleanInput($_POST['Contact'] ?? '');
        $statut = isset($_POST['statut']) ? 1 : 0;
        $situation = cleanInput($_POST['situation']);
        $NomConjoint = cleanInput($_POST['NomConjoint'] ?? '');
        $PrenomConjoint = cleanInput($_POST['PrenomConjoint'] ?? '');
        $num_tarif = cleanInput($_POST['num_tarif']);

        // Validation des champs obligatoires
        if (empty($IM) || empty($Nom) || empty($Prenoms) || empty($num_tarif) || empty($datenais) || empty($situation)) {
            $alertMessage = "Erreur : Tous les champs obligatoires (IM, Nom, Prénoms, Date de naissance, Situation, Tarif) doivent être remplis";
            $alertType = "error";
            error_log("Validation échouée dans update: IM=$IM, Nom=$Nom, Prenoms=$Prenoms, num_tarif=$num_tarif, datenais=$datenais, situation=$situation");
        } elseif (!is_string($num_tarif) || empty($num_tarif)) {
            $alertMessage = "Erreur : Le tarif sélectionné est invalide";
            $alertType = "error";
        } else {
            // Vérifier que num_tarif existe
            $tarif = getTariffByNum($db, $num_tarif);
            error_log("Update - num_tarif: $num_tarif, tarif: " . print_r($tarif, true));
            if (!$tarif) {
                $alertMessage = "Erreur : Tarif invalide ou non trouvé pour num_tarif=$num_tarif";
                $alertType = "error";
                error_log("Tarif non trouvé dans update pour num_tarif: $num_tarif");
            } else {
                try {
                    $stmt = $db->prepare("UPDATE personne SET Nom = :Nom, Prenoms = :Prenoms, datenais = :datenais, Contact = :Contact, statut = :statut, situation = :situation, NomConjoint = :NomConjoint, PrenomConjoint = :PrenomConjoint, num_tarif = :num_tarif WHERE IM = :IM");
                    $stmt->bindParam(':IM', $IM);
                    $stmt->bindParam(':Nom', $Nom);
                    $stmt->bindParam(':Prenoms', $Prenoms);
                    $stmt->bindParam(':datenais', $datenais);
                    $stmt->bindParam(':Contact', $Contact);
                    $stmt->bindParam(':statut', $statut, PDO::PARAM_INT);
                    $stmt->bindParam(':situation', $situation);
                    $stmt->bindParam(':NomConjoint', $NomConjoint);
                    $stmt->bindParam(':PrenomConjoint', $PrenomConjoint);
                    $stmt->bindParam(':num_tarif', $num_tarif);
                    $stmt->execute();

                    if ($stmt->rowCount() === 0) {
                        $alertMessage = "Aucune modification effectuée : le pensionnaire n'existe pas ou aucune donnée n'a changé";
                        $alertType = "warning";
                        error_log("Aucune ligne affectée dans update pour IM: $IM");
                    } else {
                        // Si le pensionnaire est décédé et marié avec un conjoint, ajouter une entrée dans la table conjoint
                        if ($statut == 0 && $situation === 'marié(e)' && !empty($NomConjoint) && !empty($PrenomConjoint)) {
                            $newMontant = (float)$tarif['montant'] * 0.4;
                            try {
                                // Vérifier si une entrée existe déjà pour éviter les doublons
                                $stmt_check = $db->prepare("SELECT COUNT(*) FROM conjoint WHERE numPension = :numPension");
                                $stmt_check->bindParam(':numPension', $IM);
                                $stmt_check->execute();
                                $conjointExists = $stmt_check->fetchColumn();

                                if (!$conjointExists) {
                                    $stmt_conjoint = $db->prepare("INSERT INTO conjoint (numPension, NomConjoint, PrenomConjoint, montant) VALUES (:numPension, :NomConjoint, :PrenomConjoint, :montant)");
                                    $stmt_conjoint->bindParam(':numPension', $IM);
                                    $stmt_conjoint->bindParam(':NomConjoint', $NomConjoint);
                                    $stmt_conjoint->bindParam(':PrenomConjoint', $PrenomConjoint);
                                    $stmt_conjoint->bindParam(':montant', $newMontant);
                                    $stmt_conjoint->execute();
                                    $alertMessage = "Pensionnaire mis à jour et conjoint ajouté avec succès";
                                    $alertType = "success";
                                } else {
                                    $alertMessage = "Pensionnaire mis à jour avec succès (conjoint déjà enregistré)";
                                    $alertType = "success";
                                }
                            } catch (PDOException $e) {
                                $alertMessage = "Pensionnaire mis à jour, mais échec de l'ajout du conjoint: " . $e->getMessage();
                                $alertType = "warning";
                                error_log("Erreur ajout conjoint dans update: " . $e->getMessage());
                            }
                        } else {
                            $alertMessage = "Pensionnaire mis à jour avec succès";
                            $alertType = "success";
                        }
                    }
                } catch (PDOException $e) {
                    $alertMessage = "Erreur lors de la modification: " . $e->getMessage();
                    $alertType = "error";
                    error_log("Erreur dans update personne: " . $e->getMessage());
                }
            }
        }
    } elseif ($_POST['action'] === 'delete' && isset($_POST['IM'])) {
        $IM = cleanInput($_POST['IM']);

        try {
            if (hasPayments($db, $IM)) {
                $alertMessage = "Impossible de supprimer ce pensionnaire car il a des paiements associés";
                $alertType = "error";
            } else {
                $personne = getPersonneByIM($db, $IM);
                $conjointAdded = false;
                if ($personne && $personne['situation'] === 'marié(e)' && !empty($personne['NomConjoint']) && !empty($personne['PrenomConjoint'])) {
                    $tarif = getTariffByNum($db, $personne['num_tarif']);
                    $newMontant = $tarif ? (float)$tarif['montant'] * 0.4 : 0;
                    $conjointAdded = addConjointAsPensionnaire($db, $personne, $newMontant);
                }

                $stmt = $db->prepare("DELETE FROM personne WHERE IM = :IM");
                $stmt->bindParam(':IM', $IM);
                $stmt->execute();

                $alertMessage = $conjointAdded ? "Pensionnaire supprimé et conjoint ajouté comme pensionnaire avec succès" : "Pensionnaire supprimé avec succès";
                $alertType = "success";
            }
        } catch (PDOException $e) {
            $alertMessage = "Erreur lors de la suppression: " . $e->getMessage();
            $alertType = "error";
            error_log("Erreur dans delete personne: " . $e->getMessage());
        }
    }
}

// Gestion des filtres
$statusFilter = isset($_GET['status']) ? $_GET['status'] : 'all';
$searchTerm = isset($_GET['search']) ? cleanInput($_GET['search']) : '';

// Récupérer les pensionnaires avec jointure pour le diplôme
$query = "
    SELECT 
        p.*,
        t.diplome
    FROM personne p
    LEFT JOIN tarif t ON p.num_tarif = t.num_tarif
    WHERE 1=1
";

if ($statusFilter === 'vivant') {
    $query .= " AND p.statut = 1";
} elseif ($statusFilter === 'decede') {
    $query .= " AND p.statut = 0";
}

if (!empty($searchTerm)) {
    $query .= " AND (p.IM LIKE :searchTerm OR p.Nom LIKE :searchTerm OR p.Prenoms LIKE :searchTerm)";
}

$query .= " ORDER BY p.Nom ASC";

$stmt = $db->prepare($query);

if (!empty($searchTerm)) {
    $searchParam = "%{$searchTerm}%";
    $stmt->bindParam(':searchTerm', $searchParam);
}

$stmt->execute();
$personnes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les tarifs pour le formulaire
$stmt = $db->query("SELECT * FROM tarif ORDER BY diplome ASC");
$tarifs = $stmt->fetchAll(PDO::FETCH_ASSOC);
error_log("Tarifs récupérés: " . print_r($tarifs, true)); // Log des tarifs

// Vérifier si des tarifs existent
if (empty($tarifs)) {
    $alertMessage = "Erreur : Aucun tarif disponible. Veuillez ajouter des tarifs avant de créer ou modifier un pensionnaire.";
    $alertType = "error";
}
?>

<div class="page-header mb-4">
    <div>
        <h2 class="text-3xl font-bold">Pensionnaires</h2>
        <p class="text-muted">Gérez les informations des pensionnaires</p>
    </div>
</div>

<?php if (isset($alertMessage) && !$isAjax): ?>
<div class="toast toast-<?php echo $alertType; ?>" id="alert-toast">
    <div class="toast-content">
        <div class="toast-title"><?php echo $alertType === 'success' ? 'Succès' : ($alertType === 'warning' ? 'Avertissement' : 'Erreur'); ?></div>
        <div class="toast-message"><?php echo htmlspecialchars($alertMessage); ?></div>
    </div>
    <button class="btn-icon" onclick="this.parentElement.remove()">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
    </button>
</div>
<script>
    // Faire défiler jusqu'au toast pour le rendre visible
    document.getElementById('alert-toast').scrollIntoView({ behavior: 'smooth' });
</script>
<?php endif; ?>

<!-- Filtres et recherche -->
<div class="card mb-4">
    <div class="card-content">
        <form action="/pension_retraite/index.php" method="GET" class="form-row">
            <input type="hidden" name="tab" value="personnes">
            <div class="form-col">
                <label class="form-label" for="status">Statut</label>
                <select id="status" name="status" class="form-select" onchange="this.form.submit()">
                    <option value="all" <?php echo $statusFilter === 'all' ? 'selected' : ''; ?>>Tous</option>
                    <option value="vivant" <?php echo $statusFilter === 'vivant' ? 'selected' : ''; ?>>Vivants</option>
                    <option value="decede" <?php echo $statusFilter === 'decede' ? 'selected' : ''; ?>>Décédés</option>
                </select>
            </div>
            <div class="form-col">
                <label class="form-label" for="search">Rechercher</label>
                <div class="form-row">
                    <div class="form-col">
                        <input type="text" id="search" name="search" class="form-input" placeholder="IM, Nom ou Prénom" value="<?php echo htmlspecialchars($searchTerm); ?>">
                    </div>
                    <div style="flex: 0 0 auto; align-self: flex-end;">
                        <button type="submit" class="btn btn-primary">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                            Rechercher
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Bouton ajouter -->
<div class="mb-4">
    <button class="btn btn-primary" onclick="openAddModal()">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
        Nouveau Pensionnaire
    </button>
</div>

<!-- Liste des pensionnaires -->
<div class="card">
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>IM</th>
                    <th>Nom</th>
                    <th>Prénoms</th>
                    <th>Date de naissance</th>
                    <th>Contact</th>
                    <th>Statut</th>
                    <th>Diplôme</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($personnes) > 0): ?>
                    <?php foreach ($personnes as $personne): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($personne['IM']); ?></td>
                            <td><?php echo htmlspecialchars($personne['Nom']); ?></td>
                            <td><?php echo htmlspecialchars($personne['Prenoms']); ?></td>
                            <td><?php echo formatDate($personne['datenais']); ?></td>
                            <td><?php echo htmlspecialchars($personne['Contact'] ?? ''); ?></td>
                            <td>
                                <span class="badge <?php echo $personne['statut'] ? 'badge-success' : 'badge-danger'; ?>">
                                    <?php echo $personne['statut'] ? 'Vivant' : 'Décédé'; ?>
                                </span>
                            </td>
                            <td><?php echo !empty($personne['diplome']) ? htmlspecialchars($personne['diplome']) : 'Non défini'; ?></td>
                            <td>
                                <div class="action-buttons">
                                    <button class="btn btn-icon" onclick="openEditModal('<?php echo $personne['IM']; ?>')" title="Modifier">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                                    </button>
                                    <button class="btn btn-icon" onclick="openDeleteModal('<?php echo $personne['IM']; ?>')" title="Supprimer">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" class="text-center text-muted">Aucun pensionnaire trouvé</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modals -->
<!-- Formulaire d'ajout -->
<div id="modal-container" class="modal-container hidden">
    <div id="modal-body">
        <div id="add-modal" class="modal hidden">
            <div class="modal-header">
                <h3 id="modal-title">Ajouter un pensionnaire</h3>
                <button class="modal-close-btn" onclick="closeAddModal()">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                </button>
            </div>
            <div class="modal-body">
                <form action="" method="POST" id="add-form">
                    <input type="hidden" name="action" value="create">
                    
                    <div class="form-row">
                        <div class="form-col">
                            <label class="form-label" for="add-IM">IM</label>
                            <input type="text" id="add-IM" name="IM" class="form-input" required>
                        </div>
                        <div class="form-col">
                            <label class="form-label" for="add-num_tarif">Tarif</label>
                            <select id="add-num_tarif" name="num_tarif" class="form-select" required>
                                <?php foreach ($tarifs as $tarif): ?>
                                    <option value="<?php echo htmlspecialchars($tarif['num_tarif']); ?>"><?php echo htmlspecialchars($tarif['diplome']) . ' - ' . formatCurrency($tarif['montant']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-col">
                            <label class="form-label" for="add-Nom">Nom</label>
                            <input type="text" id="add-Nom" name="Nom" class="form-input" required>
                        </div>
                        <div class="form-col">
                            <label class="form-label" for="add-Prenoms">Prénoms</label>
                            <input type="text" id="add-Prenoms" name="Prenoms" class="form-input" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-col">
                            <label class="form-label" for="add-datenais">Date de naissance</label>
                            <input type="date" id="add-datenais" name="datenais" class="form-input" required>
                        </div>
                        <div class="form-col">
                            <label class="form-label" for="add-Contact">Contact</label>
                            <input type="text" id="add-Contact" name="Contact" class="form-input">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-col">
                            <label class="form-label" for="add-statut">Statut</label>
                            <div class="form-check">
                                <input type="checkbox" id="add-statut" name="statut" class="form-check-input" checked>
                                <label class="form-check-label" for="add-statut">Vivant</label>
                            </div>
                        </div>
                        <div class="form-col">
                            <label class="form-label" for="add-situation">Situation</label>
                            <select id="add-situation" name="situation" class="form-select" required>
                                <option value="marié(e)">Marié(e)</option>
                                <option value="divorcé(e)">Divorcé(e)</option>
                                <option value="veuf(ve)">Veuf(ve)</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row" id="add-conjoint-fields">
                        <div class="form-col">
                            <label class="form-label" for="add-NomConjoint">Nom du conjoint</label>
                            <input type="text" id="add-NomConjoint" name="NomConjoint" class="form-input">
                        </div>
                        <div class="form-col">
                            <label class="form-label" for="add-PrenomConjoint">Prénom du conjoint</label>
                            <input type="text" id="add-PrenomConjoint" name="PrenomConjoint" class="form-input">
                        </div>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline" onclick="closeAddModal()">Annuler</button>
                        <button type="submit" class="btn btn-primary">Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Formulaire de modification -->
        <div id="edit-modal" class="modal hidden">
            <div class="modal-header">
                <h3 id="modal-title">Modifier un pensionnaire</h3>
                <button class="modal-close-btn" onclick="closeEditModal()">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                </button>
            </div>
            <div class="modal-body">
                <form action="" method="POST" id="edit-form">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="IM" id="edit-IM">
                    
                    <div class="form-row">
                        <div class="form-col">
                            <label class="form-label" for="edit-Nom">Nom</label>
                            <input type="text" id="edit-Nom" name="Nom" class="form-input" required>
                        </div>
                        <div class="form-col">
                            <label class="form-label" for="edit-Prenoms">Prénoms</label>
                            <input type="text" id="edit-Prenoms" name="Prenoms" class="form-input" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-col">
                            <label class="form-label" for="edit-datenais">Date de naissance</label>
                            <input type="date" id="edit-datenais" name="datenais" class="form-input" required>
                        </div>
                        <div class="form-col">
                            <label class="form-label" for="edit-Contact">Contact</label>
                            <input type="text" id="edit-Contact" name="Contact" class="form-input">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-col">
                            <label class="form-label" for="edit-statut">Statut</label>
                            <div class="form-check">
                                <input type="checkbox" id="edit-statut" name="statut" class="form-check-input">
                                <label class="form-check-label" for="edit-statut">Vivant</label>
                            </div>
                        </div>
                        <div class="form-col">
                            <label class="form-label" for="edit-situation">Situation</label>
                            <select id="edit-situation" name="situation" class="form-select" required>
                                <option value="marié(e)">Marié(e)</option>
                                <option value="divorcé(e)">Divorcé(e)</option>
                                <option value="veuf(ve)">Veuf(ve)</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row" id="edit-conjoint-fields">
                        <div class="form-col">
                            <label class="form-label" for="edit-NomConjoint">Nom du conjoint</label>
                            <input type="text" id="edit-NomConjoint" name="NomConjoint" class="form-input">
                        </div>
                        <div class="form-col">
                            <label class="form-label" for="edit-PrenomConjoint">Prénom du conjoint</label>
                            <input type="text" id="edit-PrenomConjoint" name="PrenomConjoint" class="form-input">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-col">
                            <label class="form-label" for="edit-num_tarif">Tarif</label>
                            <select id="edit-num_tarif" name="num_tarif" class="form-select" required>
                                <?php foreach ($tarifs as $tarif): ?>
                                    <option value="<?php echo htmlspecialchars($tarif['num_tarif']); ?>"><?php echo htmlspecialchars($tarif['diplome']) . ' - ' . formatCurrency($tarif['montant']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline" onclick="closeEditModal()">Annuler</button>
                        <button type="submit" class="btn btn-primary">Enregistrer les modifications</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Confirmation de suppression -->
        <div id="delete-modal" class="modal hidden">
            <div class="modal-header">
                <h3 id="modal-title">Confirmer la suppression</h3>
                <button class="modal-close-btn" onclick="closeDeleteModal()">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                </button>
            </div>
            <div class="modal-body">
                <p>Êtes-vous sûr de vouloir supprimer ce pensionnaire ? Cette action est irréversible.</p>
                
                <form action="" method="POST" id="delete-form">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="IM" id="delete-IM">
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline" onclick="closeDeleteModal()">Annuler</button>
                        <button type="submit" class="btn btn-destructive">Supprimer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
    #add-conjoint-fields, #edit-conjoint-fields {
        display: none;
    }
</style>

<script src="/pension_retraite/assets/js/personnes.js"></script>