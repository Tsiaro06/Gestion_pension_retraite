<?php
// Nécessite l'installation de la bibliothèque FPDF
// composer require fpdf/fpdf

require_once 'vendor/autoload.php';
require_once 'config/database.php';
require_once 'utils/functions.php';

class PaymentReceipt extends FPDF
{
    function Header()
    {
        // Logo
        $this->Image('assets/images/logo.png', 10, 6, 30);
        // Police Arial gras 15
        $this->SetFont('Arial', 'B', 15);
        // Décalage à droite
        $this->Cell(80);
        // Titre
        $this->Cell(30, 10, 'REÇU DE PAIEMENT', 0, 0, 'C');
        // Saut de ligne
        $this->Ln(20);
    }
    
    function Footer()
    {
        // Positionnement à 1,5 cm du bas
        $this->SetY(-15);
        // Police Arial italique 8
        $this->SetFont('Arial', 'I', 8);
        // Numéro de page
        $this->Cell(0, 10, 'Page ' . $this->PageNo() . '/{nb}', 0, 0, 'C');
    }
}

function generatePDF($db, $paymentId)
{
    // Récupérer les informations du paiement
    $stmt = $db->prepare("SELECT * FROM payer WHERE id = :id");
    $stmt->bindParam(':id', $paymentId);
    $stmt->execute();
    $payment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$payment) {
        return false;
    }
    
    // Récupérer les informations du pensionnaire
    $personne = getPersonneByIM($db, $payment['IM']);
    
    // Récupérer les informations du tarif
    $tarif = getTariffByNum($db, $payment['num_tarif']);
    
    // Créer le PDF
    $pdf = new PaymentReceipt();
    $pdf->AliasNbPages();
    $pdf->AddPage();
    $pdf->SetFont('Arial', '', 12);
    
    // Numéro de reçu
    $pdf->Cell(0, 10, 'Reçu N°: ' . $paymentId, 0, 1);
    $pdf->Cell(0, 10, 'Date: ' . formatDate($payment['date']), 0, 1);
    $pdf->Ln(10);
    
    // Informations du pensionnaire
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 10, 'Informations du pensionnaire:', 0, 1);
    $pdf->SetFont('Arial', '', 12);
    $pdf->Cell(0, 10, 'IM: ' . $personne['IM'], 0, 1);
    $pdf->Cell(0, 10, 'Nom: ' . $personne['Nom'] . ' ' . $personne['Prenoms'], 0, 1);
    $pdf->Ln(10);
    
    // Détails du paiement
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 10, 'Détails du paiement:', 0, 1);
    $pdf->SetFont('Arial', '', 12);
    $pdf->Cell(0, 10, 'Diplôme: ' . $tarif['diplome'], 0, 1);
    $pdf->Cell(0, 10, 'Catégorie: ' . $tarif['categorie'], 0, 1);
    $pdf->Cell(0, 10, 'Montant: ' . formatCurrency($tarif['montant']), 0, 1);
    $pdf->Ln(10);
    
    // Signature
    $pdf->Cell(0, 10, 'Signature:', 0, 1);
    $pdf->Ln(20);
    $pdf->Cell(0, 10, '________________________', 0, 1);
    
    // Générer le PDF
    $filename = 'receipts/receipt_' . $paymentId . '.pdf';
    $pdf->Output('F', $filename);
    
    return $filename;
}

// Exemple d'utilisation
// $pdfPath = generatePDF($db, 1);
?>