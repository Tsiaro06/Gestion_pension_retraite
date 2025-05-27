<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/functions.php';

$db = getDatabaseConnection();


// Endpoint pour récupérer un pensionnaire par IM
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['IM'])) {
    $IM = cleanInput($_GET['IM']);
    try {
        $stmt = $db->prepare("SELECT * FROM personne WHERE IM = :IM");
        $stmt->bindParam(':IM', $IM);
        $stmt->execute();
        $personne = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($personne) {
            echo json_encode([
                'success' => true,
                'personne' => $personne
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Pensionnaire non trouvé'
            ]);
        }
    } catch (PDOException $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Erreur: ' . $e->getMessage()
        ]);
    }
    exit;
}

// Endpoint pour rechercher un pensionnaire par IM, nom ou prénom
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['query'])) {
    $query = cleanInput($_GET['query']);
    try {
        // Vérifier si c'est un IM
        if (is_numeric($query)) {
            $stmt = $db->prepare("SELECT * FROM personne WHERE IM = :query");
            $stmt->bindParam(':query', $query);
            $stmt->execute();
            $personne = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($personne) {
                echo json_encode([
                    'success' => true,
                    'personne' => $personne
                ]);
                exit;
            }
        }

        // Rechercher par nom ou prénom
        $searchQuery = "%$query%";
        $stmt = $db->prepare("SELECT * FROM personne WHERE Nom LIKE :query OR Prenoms LIKE :query");
        $stmt->bindParam(':query', $searchQuery);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (count($results) > 0) {
            echo json_encode([
                'success' => true,
                'personnes' => $results
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Pensionnaire non trouvé'
            ]);
        }
    } catch (PDOException $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Erreur: ' . $e->getMessage()
        ]);
    }
    exit;
}

// Endpoint pour créer un pensionnaire (pour createConjointPensionnaire)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $IM = generateUniqueIM($db);
    $Nom = isset($data['Nom']) ? cleanInput($data['Nom']) : '';
    $Prenoms = isset($data['Prenoms']) ? cleanInput($data['Prenoms']) : '';
    $Contact = isset($data['Contact']) ? cleanInput($data['Contact']) : '';
    $situation = isset($data['situation']) ? cleanInput($data['situation']) : 'veuf(ve)';
    $num_tarif = isset($data['num_tarif']) ? cleanInput($data['num_tarif']) : null;
    $statut = isset($data['statut']) ? 1 : 0;

    if (!$Nom || !$Prenoms || !$num_tarif) {
        echo json_encode([
            'success' => false,
            'message' => 'Données manquantes pour créer le pensionnaire'
        ]);
        exit;
    }

    try {
        $stmt = $db->prepare("INSERT INTO personne (IM, Nom, Prenoms, Contact, statut, situation, num_tarif) VALUES (:IM, :Nom, :Prenoms, :Contact, :statut, :situation, :num_tarif)");
        $stmt->bindParam(':IM', $IM);
        $stmt->bindParam(':Nom', $Nom);
        $stmt->bindParam(':Prenoms', $Prenoms);
        $stmt->bindParam(':Contact', $Contact);
        $stmt->bindParam(':statut', $statut);
        $stmt->bindParam(':situation', $situation);
        $stmt->bindParam(':num_tarif', $num_tarif);
        $stmt->execute();

        echo json_encode([
            'success' => true,
            'message' => 'Pensionnaire créé avec succès'
        ]);
    } catch (PDOException $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Erreur: ' . $e->getMessage()
        ]);
    }
    exit;
}

// Endpoint pour mettre à jour le statut d'un pensionnaire
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'updateStatus') {
    $data = json_decode(file_get_contents('php://input'), true);
    $IM = isset($data['IM']) ? cleanInput($data['IM']) : null;
    $statut = isset($data['status']) && $data['status'] === 'deceased' ? 0 : 1;

    if (!$IM) {
        echo json_encode([
            'success' => false,
            'message' => 'IM manquant'
        ]);
        exit;
    }

    try {
        // Mettre à jour le statut
        $stmt = $db->prepare("UPDATE personne SET statut = :statut WHERE IM = :IM");
        $stmt->bindParam(':statut', $statut, PDO::PARAM_INT);
        $stmt->bindParam(':IM', $IM);
        $stmt->execute();

        // Si décédé, gérer le conjoint
        if ($statut === 0) {
            $stmt = $db->prepare("SELECT * FROM personne WHERE IM = :IM");
            $stmt->bindParam(':IM', $IM);
            $stmt->execute();
            $personne = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($personne && $personne['situation'] === 'marié(e)' && !empty($personne['NomConjoint']) && !empty($personne['PrenomConjoint'])) {
                // Récupérer le tarif
                $stmt = $db->prepare("SELECT montant FROM tarif WHERE num_tarif = :num_tarif");
                $stmt->bindParam(':num_tarif', $personne['num_tarif']);
                $stmt->execute();
                $tarif = $stmt->fetch(PDO::FETCH_ASSOC);
                $newMontant = $tarif['montant'] * 0.4;

                // Ajouter le conjoint comme pensionnaire
                $newIM = generateUniqueIM($db);
                $stmt = $db->prepare("INSERT INTO personne (IM, Nom, Prenoms, Contact, statut, situation, num_tarif) VALUES (:IM, :Nom, :Prenoms, :Contact, :statut, :situation, :num_tarif)");
                $stmt->bindParam(':IM', $newIM);
                $stmt->bindParam(':Nom', $personne['NomConjoint']);
                $stmt->bindParam(':Prenoms', $personne['PrenomConjoint']);
                $stmt->bindParam(':Contact', $personne['Contact']);
                $newStatut = 1;
                $stmt->bindParam(':statut', $newStatut, PDO::PARAM_INT);
                $newSituation = 'veuf(ve)';
                $stmt->bindParam(':situation', $newSituation);
                $stmt->bindParam(':num_tarif', $newMontant);
                $stmt->execute();
            }
        }

        echo json_encode([
            'success' => true,
            'message' => 'Statut mis à jour avec succès'
        ]);
    } catch (PDOException $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Erreur: ' . $e->getMessage()
        ]);
    }
    exit;
}

// Si aucune action n'est spécifiée
echo json_encode([
    'success' => false,
    'message' => 'Action non spécifiée'
]);


?>