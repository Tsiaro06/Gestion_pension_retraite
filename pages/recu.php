<?php
// Inclure la configuration de la base de données
require_once '../config/database.php';

// Vérifier l'existence du fichier TCPDF
$tcpdfPath = '../TCPDF-main/tcpdf.php';
if (!file_exists($tcpdfPath)) {
    die('Erreur : Le fichier tcpdf.php n\'existe pas à l\'emplacement : ' . realpath('../tcpdf'));
}
require_once $tcpdfPath;

// Vérifier si la classe TCPDF est définie
if (!class_exists('TCPDF')) {
    die('Erreur : La classe TCPDF n\'est pas trouvée. Vérifiez que le fichier tcpdf.php est correct.');
}

// Connexion à la base de données
$db = getDatabaseConnection();

// Vérifier si IM et date sont passés en paramètres
if (isset($_GET['IM']) && isset($_GET['date'])) {
    $IM = $_GET['IM'];
    $date = $_GET['date'];

    // Récupérer les détails du paiement
    $query = "SELECT * FROM payer WHERE IM = :IM AND date = :date";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':IM', $IM);
    $stmt->bindParam(':date', $date);
    $stmt->execute();
    $paiement = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($paiement) {
        // Récupérer les informations du retraité
        $query_personne = "SELECT Nom, Prenoms FROM personne WHERE IM = :IM";
        $stmt_personne = $db->prepare($query_personne);
        $stmt_personne->bindParam(':IM', $paiement['IM']);
        $stmt_personne->execute();
        $personne = $stmt_personne->fetch(PDO::FETCH_ASSOC);

        // Récupérer les informations du tarif
        $query_tarif = "SELECT diplome, montant FROM tarif WHERE num_tarif = :num_tarif";
        $stmt_tarif = $db->prepare($query_tarif);
        $stmt_tarif->bindParam(':num_tarif', $paiement['num_tarif']);
        $stmt_tarif->execute();
        $tarif = $stmt_tarif->fetch(PDO::FETCH_ASSOC);

        // if (!$tarif) {
        //     die("Erreur : Tarif non trouvé pour num_tarif = " . htmlspecialchars($paiement['num_tarif']));
        // }

        // Créer une instance de TCPDF
        $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);

        // Définir les métadonnées
        $pdf->SetCreator('Pension Retraite');
        $pdf->SetAuthor('Admin');
        $pdf->SetTitle('Reçu de Paiement');
        $pdf->SetSubject('Reçu de Paiement');
        $pdf->SetKeywords('Reçu, Paiement, Pension, Retraite');

        // Supprimer les en-têtes et pieds de page par défaut
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);

        // Ajouter une page
        $pdf->AddPage();

        // Définir la police
        $pdf->SetFont('helvetica', '', 12);

        // Contenu HTML pour le reçu
        $html = '
        <h1 style="text-align: center;">Reçu de Paiement</h1>
        <p><strong>ID Paiement :</strong> ' . htmlspecialchars($paiement['IM']) . '</p>
        <p><strong>Nom :</strong> ' . htmlspecialchars($personne['Nom'] . ' ' . $personne['Prenoms']) . '</p>
        <p><strong>Numéro Tarif :</strong> ' . htmlspecialchars($paiement['num_tarif']) . '</p>
        <p><strong>Diplôme :</strong> ' . htmlspecialchars($tarif['diplome']) . '</p>
        <p><strong>Date de Paiement :</strong> ' . htmlspecialchars($paiement['date']) . '</p>
        <p><strong>Montant :</strong> ' . htmlspecialchars($tarif['montant']) . ' Ar</p>
        <h3>Détails</h3>
        <table border="1" cellpadding="5">
            <tr>
                <th>ID</th>
                <th>Nom</th>
                <th>Diplôme</th>
                <th>Date</th>
                <th>Montant</th>
            </tr>
            <tr>
                <td>' . htmlspecialchars($paiement['IM']) . '</td>
                <td>' . htmlspecialchars($personne['Nom'] . ' ' . $personne['Prenoms']) . '</td>
                <td>' . htmlspecialchars($tarif['diplome']) . '</td>
                <td>' . htmlspecialchars($paiement['date']) . '</td>
                <td>' . htmlspecialchars($tarif['montant']) . ' Ar</td>
            </tr>
        </table>';

        // Écrire le contenu HTML dans le PDF
        $pdf->writeHTML($html, true, false, true, false, '');

        // Générer le PDF (téléchargement)
        $pdf->Output('recu_paiement_' . $paiement['IM'] . '_' . $paiement['date'] . '.pdf', 'D');
    } else {
        echo "Paiement non trouvé.";
    }
} else {
    echo "IM ou date de paiement non spécifié.";
}
?>