<?php
require_once '../config/database.php';
require_once '../utils/functions.php';

header('Content-Type: application/json');
$db = getDatabaseConnection();

// Récupérer les infos d'un conjoint par son numéro
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['numPension'])) {
    $numPension = cleanInput($_GET['numPension']);
    
    try {
        $stmt = $db->prepare("SELECT * FROM conjoint WHERE numPension = :numPension");
        $stmt->bindParam(':numPension', $numPension);
        $stmt->execute();
        $conjoint = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($conjoint) {
            echo json_encode([
                'success' => true,
                'conjoint' => $conjoint
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Conjoint non trouvé'
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

// Créer un conjoint
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    $numPension = cleanInput($_POST['numPension']);
    $NomConjoint = cleanInput($_POST['NomConjoint']);
    $PrenomConjoint = cleanInput($_POST['PrenomConjoint']);
    $montant = cleanInput($_POST['montant']);
    
    try {
        // Vérifier si le conjoint existe déjà
        $stmt = $db->prepare("SELECT COUNT(*) FROM conjoint WHERE numPension = :numPension");
        $stmt->bindParam(':numPension', $numPension);
        $stmt->execute();
        
        if ($stmt->fetchColumn() > 0) {
            echo json_encode([
                'success' => false,
                'message' => 'Un conjoint existe déjà pour ce numéro de pension'
            ]);
            exit;
        }
        
        // Insérer le conjoint dans la table conjoint
        $stmt = $db->prepare("INSERT INTO conjoint (numPension, NomConjoint, PrenomConjoint, montant) VALUES (:numPension, :NomConjoint, :PrenomConjoint, :montant)");
        $stmt->bindParam(':numPension', $numPension);
        $stmt->bindParam(':NomConjoint', $NomConjoint);
        $stmt->bindParam(':PrenomConjoint', $PrenomConjoint);
        $stmt->bindParam(':montant', $montant);
        $stmt->execute();
        
        echo json_encode([
            'success' => true,
            'message' => 'Conjoint ajouté avec succès'
        ]);
    } catch (PDOException $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Erreur: ' . $e->getMessage()
        ]);
    }
    
    exit;
}

// Mettre à jour un conjoint
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    $numPension = cleanInput($_POST['numPension']);
    $NomConjoint = cleanInput($_POST['NomConjoint']);
    $PrenomConjoint = cleanInput($_POST['PrenomConjoint']);
    $montant = cleanInput($_POST['montant']);
    
    try {
        // Vérifier si le conjoint existe
        $stmt = $db->prepare("SELECT COUNT(*) FROM conjoint WHERE numPension = :numPension");
        $stmt->bindParam(':numPension', $numPension);
        $stmt->execute();
        
        if ($stmt->fetchColumn() == 0) {
            echo json_encode([
                'success' => false,
                'message' => 'Conjoint non trouvé'
            ]);
            exit;
        }
        
        // Mettre à jour le conjoint
        $stmt = $db->prepare("UPDATE conjoint SET NomConjoint = :NomConjoint, PrenomConjoint = :PrenomConjoint, montant = :montant WHERE numPension = :numPension");
        $stmt->bindParam(':numPension', $numPension);
        $stmt->bindParam(':NomConjoint', $NomConjoint);
        $stmt->bindParam(':PrenomConjoint', $PrenomConjoint);
        $stmt->bindParam(':montant', $montant);
        $stmt->execute();
        
        echo json_encode([
            'success' => true,
            'message' => 'Conjoint mis à jour avec succès'
        ]);
    } catch (PDOException $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Erreur: ' . $e->getMessage()
        ]);
    }
    
    exit;
}

// Supprimer un conjoint
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $numPension = cleanInput($_POST['numPension']);
    
    try {
        // Vérifier si le conjoint existe
        $stmt = $db->prepare("SELECT COUNT(*) FROM conjoint WHERE numPension = :numPension");
        $stmt->bindParam(':numPension', $numPension);
        $stmt->execute();
        
        if ($stmt->fetchColumn() == 0) {
            echo json_encode([
                'success' => false,
                'message' => 'Conjoint non trouvé'
            ]);
            exit;
        }
        
        // Supprimer le conjoint
        $stmt = $db->prepare("DELETE FROM conjoint WHERE numPension = :numPension");
        $stmt->bindParam(':numPension', $numPension);
        $stmt->execute();
        
        echo json_encode([
            'success' => true,
            'message' => 'Conjoint supprimé avec succès'
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