<?php
/**
 * Fichier de fonctions utilitaires pour l'application de gestion des pensions.
 * Contient des fonctions pour nettoyer les entrées, formater les données, et interagir avec la base de données.
 */

/**
 * Nettoie les entrées utilisateur pour éviter les injections XSS et autres attaques.
 * @param string $data Données à nettoyer
 * @return string Données nettoyées
 */
function cleanInput($data) {
    if (is_null($data)) {
        return '';
    }
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Récupère un pensionnaire par son IM dans la table personne.
 * @param PDO $db Connexion à la base de données
 * @param string $IM Identifiant du pensionnaire
 * @return array|null Tableau associatif contenant les données du pensionnaire, ou null si non trouvé
 */
function getPersonneByIM($db, $IM) {
    try {
        $stmt = $db->prepare("SELECT * FROM personne WHERE IM = :IM");
        $stmt->bindParam(':IM', $IM, PDO::PARAM_STR);
        $stmt->execute();
        $personne = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$personne) {
            error_log("Pensionnaire non trouvé pour IM: $IM");
        }
        return $personne ?: null;
    } catch (PDOException $e) {
        error_log("Erreur dans getPersonneByIM: " . $e->getMessage());
        return null;
    }
}

/**
 * Récupère un tarif par son num_tarif dans la table tarif.
 * @param PDO $db Connexion à la base de données
 * @param int $num_tarif Identifiant du tarif
 * @return array|null Tableau associatif contenant les données du tarif, ou null si non trouvé
 */
function getTariffByNum($db, $num_tarif) {
    if (empty($num_tarif)) {
        error_log("num_tarif invalide: $num_tarif");
        return null;
    }
    try {
        $stmt = $db->prepare("SELECT * FROM tarif WHERE num_tarif = :num_tarif");
        $stmt->bindParam(':num_tarif', $num_tarif, PDO::PARAM_INT); // Traiter comme entier
        $stmt->execute();
        $tarif = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$tarif) {
            error_log("Tarif non trouvé pour num_tarif: $num_tarif");
        }
        return $tarif ?: null;
    } catch (PDOException $e) {
        error_log("Erreur dans getTariffByNum: " . $e->getMessage());
        return null;
    }
}

/**
 * Formate une date au format jj/mm/aaaa.
 * @param string $date Date au format Y-m-d
 * @return string Date formatée, ou "Non défini" si la date est vide
 */
function formatDate($date) {
    if (empty($date) || $date === '0000-00-00') {
        return 'Non défini';
    }
    try {
        return date('d/m/Y', strtotime($date));
    } catch (Exception $e) {
        error_log("Erreur dans formatDate: " . $e->getMessage());
        return 'Non défini';
    }
}

/**
 * Formate un montant en devise (Ariary) avec des séparateurs de milliers.
 * @param float $amount Montant à formater
 * @return string Montant formaté (ex: "1 500 000 Ar")
 */
function formatCurrency($amount) {
    if (!is_numeric($amount)) {
        return '0 Ar';
    }
    return number_format((float)$amount, 0, ',', ' ') . ' Ar';
}

/**
 * Vérifie si un pensionnaire a des paiements associés.
 * @param PDO $db Connexion à la base de données
 * @param string $IM Identifiant du pensionnaire
 * @return bool True s'il y a des paiements, False sinon
 */
function hasPayments($db, $IM) {
    try {
        $stmt = $db->prepare("SELECT COUNT(*) FROM payer WHERE IM = :IM");
        $stmt->bindParam(':IM', $IM, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetchColumn() > 0;
    } catch (PDOException $e) {
        error_log("Erreur dans hasPayments: " . $e->getMessage());
        return false;
    }
}

/**
 * Ajoute un conjoint comme pensionnaire si le pensionnaire est décédé.
 * @param PDO $db Connexion à la base de données
 * @param array $personne Données du pensionnaire
 * @param float $newMontant Montant du tarif pour le conjoint (40% du tarif original)
 * @return bool True si le conjoint a été ajouté, False sinon
 */
function addConjointAsPensionnaire($db, $personne, $newMontant) {
    if ($personne['situation'] !== 'marié(e)' || empty($personne['NomConjoint']) || empty($personne['PrenomConjoint'])) {
        error_log("Conditions non remplies pour ajouter conjoint: situation={$personne['situation']}, NomConjoint={$personne['NomConjoint']}, PrenomConjoint={$personne['PrenomConjoint']}");
        return false;
    }

    try {
        // Générer un nouvel IM unique
        $newIM = generateUniqueIM($db);

        // Insérer le conjoint comme pensionnaire dans la table personne
        $stmt = $db->prepare("INSERT INTO personne (IM, Nom, Prenoms, Contact, statut, situation, num_tarif) VALUES (:IM, :Nom, :Prenoms, :Contact, :statut, :situation, :num_tarif)");
        $stmt->bindParam(':IM', $newIM, PDO::PARAM_STR);
        $stmt->bindParam(':Nom', $personne['NomConjoint']);
        $stmt->bindParam(':Prenoms', $personne['PrenomConjoint']);
        $stmt->bindParam(':Contact', $personne['Contact']);
        $statut = 1; // Conjoint est vivant
        $stmt->bindParam(':statut', $statut, PDO::PARAM_INT);
        $situation = 'veuf(ve)';
        $stmt->bindParam(':situation', $situation);
        $stmt->bindParam(':num_tarif', $newMontant, PDO::PARAM_INT); // Note: num_tarif devrait être un identifiant, pas un montant
        $stmt->execute();

        error_log("Conjoint ajouté comme pensionnaire avec IM: $newIM");
        return true;
    } catch (PDOException $e) {
        error_log("Erreur dans addConjointAsPensionnaire: " . $e->getMessage());
        return false;
    }
}

/**
 * Génère un IM unique pour un nouveau pensionnaire.
 * @param PDO $db Connexion à la base de données
 * @return string Nouvel IM unique
 */
function generateUniqueIM($db) {
    do {
        $newIM = mt_rand(100000, 999999); // Génère un IM aléatoire à 6 chiffres
        $stmt = $db->prepare("SELECT COUNT(*) FROM personne WHERE IM = :IM");
        $stmt->bindParam(':IM', $newIM, PDO::PARAM_STR);
        $stmt->execute();
        $count = $stmt->fetchColumn();
    } while ($count > 0); // Continue jusqu'à obtenir un IM unique
    return (string)$newIM;
}

/**
 * Calcule les statistiques globales pour le tableau de bord.
 * @param PDO $db Connexion à la base de données
 * @return array Statistiques (total pensionnaires, actifs, décédés, conjoints, paiements, paiements récents)
 */
function calculateStats($db) {
    $stats = [
        'totalPensioners' => 0,
        'activePensioners' => 0,
        'deceasedPensioners' => 0,
        'totalSpouses' => 0,
        'totalPayments' => 0,
        'recentPayments' => []
    ];

    try {
        // Vérifier si la table personne existe
        $stmt = $db->query("SHOW TABLES LIKE 'personne'");
        if ($stmt->rowCount() == 0) {
            error_log("Table 'personne' n'existe pas dans la base de données.");
            return $stats;
        }

        // Total des pensionnaires
        $stmt = $db->query("SELECT COUNT(*) FROM personne");
        $stats['totalPensioners'] = (int)$stmt->fetchColumn();

        // Pensionnaires actifs (vivants)
        $stmt = $db->query("SELECT COUNT(*) FROM personne WHERE statut = 1");
        $stats['activePensioners'] = (int)$stmt->fetchColumn();

        // Pensionnaires décédés
        $stmt = $db->query("SELECT COUNT(*) FROM personne WHERE statut = 0");
        $stats['deceasedPensioners'] = (int)$stmt->fetchColumn();

        // Total des conjoints (pensionnaires mariés ayant un conjoint défini)
        $stmt = $db->query("SELECT COUNT(*) FROM personne WHERE situation = 'marié(e)' AND NomConjoint IS NOT NULL AND PrenomConjoint IS NOT NULL");
        $stats['totalSpouses'] = (int)$stmt->fetchColumn();

        // Total des paiements
        $stmt = $db->query("SELECT COUNT(*) FROM payer");
        $stats['totalPayments'] = (int)$stmt->fetchColumn();

        // Paiements récents (les 5 derniers) avec jointures pour éviter des appels supplémentaires
        $stmt = $db->query("
            SELECT 
                p.date, 
                p.IM, 
                p.num_tarif,
                CONCAT(pe.Nom, ' ', pe.Prenoms) AS pensionnaire,
                t.diplome,
                t.montant
            FROM payer p
            LEFT JOIN personne pe ON p.IM = pe.IM
            LEFT JOIN tarif t ON p.num_tarif = t.num_tarif
            ORDER BY p.date DESC 
            LIMIT 5
        ");
        $stats['recentPayments'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erreur dans calculateStats: " . $e->getMessage());
    }

    return $stats;
}

/**
 * Récupère les données des paiements pour l'histogramme (par mois).
 * @param PDO $db Connexion à la base de données
 * @return array Données des paiements agrégées par mois (ex: ['Jan 2024' => 1500000, 'Feb 2024' => 2000000])
 */
function getPaymentDataForHistogram($db) {
    $data = [];
    try {
        // Vérifier si les tables existent
        $stmt = $db->query("SHOW TABLES LIKE 'payer'");
        if ($stmt->rowCount() == 0) {
            error_log("Table 'payer' n'existe pas dans la base de données.");
            return $data;
        }
        $stmt = $db->query("SHOW TABLES LIKE 'tarif'");
        if ($stmt->rowCount() == 0) {
            error_log("Table 'tarif' n'existe pas dans la base de données.");
            return $data;
        }

        $stmt = $db->query("
            SELECT
                DATE_FORMAT(p.date, '%m-%Y') AS month_key,
                DATE_FORMAT(p.date, '%b %Y') AS month_label,
                SUM(t.montant) AS total
            FROM payer p
            JOIN tarif t ON p.num_tarif = t.num_tarif
            WHERE p.date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
            GROUP BY month_key
            ORDER BY MIN(p.date)
        ");
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Create a map of results for faster lookup
        $resultMap = [];
        foreach ($results as $row) {
            $resultMap[$row['month_key']] = [
                'label' => $row['month_label'],
                'total' => (float)$row['total']
            ];
        }
        
        // Initialize last 12 months with consistent format
        $formattedData = [];
        for ($i = 11; $i >= 0; $i--) {
            $monthKey = date('m-Y', strtotime("-$i months"));
            $monthLabel = date('M Y', strtotime("-$i months"));
            
            if (isset($resultMap[$monthKey])) {
                $formattedData[$monthLabel] = $resultMap[$monthKey]['total'];
            } else {
                $formattedData[$monthLabel] = 0;
            }
        }
        
        $data = $formattedData;
        
        error_log("Chart data: " . json_encode($data));
        
    } catch (PDOException $e) {
        error_log("Erreur dans getPaymentDataForHistogram: " . $e->getMessage());
    }
    return $data;
}
?>