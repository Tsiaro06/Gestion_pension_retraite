<?php
header('Content-Type: application/json');
require_once '../config/database.php'; // Connexion PDO

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $num_tarif = $_POST['num_tarif'] ?? null;
    $diplome = $_POST['diplome'] ?? null;
    $categorie = $_POST['categorie'] ?? null;
    $montant = $_POST['montant'] ?? null;

    if ($num_tarif && $diplome && $categorie && $montant) {
        try {
            $sql = "UPDATE tarif SET diplome = :diplome, categorie = :categorie, montant = :montant WHERE num_tarif = :num_tarif";
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':diplome', $diplome);
            $stmt->bindParam(':categorie', $categorie);
            $stmt->bindParam(':montant', $montant);
            $stmt->bindParam(':num_tarif', $num_tarif);
            $stmt->execute();

            echo json_encode(['status' => 'success', 'message' => 'Tarif mis à jour avec succès']);
        } catch (PDOException $e) {
            echo json_encode(['status' => 'error', 'message' => 'Erreur SQL : ' . $e->getMessage()]);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Tous les champs sont requis']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Méthode non autorisée']);
}
