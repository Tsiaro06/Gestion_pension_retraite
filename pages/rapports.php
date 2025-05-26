<?php

require_once './config/database.php';
$db = getDatabaseConnection();

// Gestion des filtres
$startDate = isset($_GET['start_date']) ? cleanInput($_GET['start_date']) : '';
$endDate = isset($_GET['end_date']) ? cleanInput($_GET['end_date']) : '';
$chartType = isset($_GET['chart_type']) ? cleanInput($_GET['chart_type']) : 'bar';

// Variables pour les graphiques
$chartData = [];
$jsChartLabels = [] ;
$jsChartValues = [] ;
$isFilterApplied = false;

// Si les dates sont fournies, récupérer les paiements
if (!empty($startDate) && !empty($endDate)) {
    $paiements = getPaymentsBetweenDates($db, $startDate, $endDate);
    $isFilterApplied = true;
    
    // Calculer le total
    $total = 0;
    foreach ($paiements as $paiement) {
        $tarif = getTariffByNum($db, $paiement['num_tarif']);
        if ($tarif) {
            $total += $tarif['montant'];
        }
    }
    
    // Préparer les données pour les graphiques
    $paymentsByMonth = [];
    
    foreach ($paiements as $paiement) {
        $month = date('M Y', strtotime($paiement['date']));
        $tarif = getTariffByNum($db, $paiement['num_tarif']);
        
        if ($tarif) {
            if (!isset($paymentsByMonth[$month])) {
                $paymentsByMonth[$month] = 0;
            }
            $paymentsByMonth[$month] += $tarif['montant'];
        }
    }
    
    // Convertir en tableaux pour JavaScript
    $chartLabels = array_keys($paymentsByMonth);
    $chartValues = array_values($paymentsByMonth);
} else {
    // Données par défaut
    $chartData = getPaymentDataForHistogram($db);
    $chartLabels = array_keys($chartData);
    $chartValues = array_values($chartData);
}

// Encoder les données pour JavaScript
$jsChartLabels = json_encode($chartLabels);
$jsChartValues = json_encode($chartValues);
?>

<div class="page-header mb-4">
    <div>
        <h2 class="text-3xl font-bold">Rapports</h2>
        <p class="text-muted">Consultez et générez des rapports sur les paiements de pensions</p>
    </div>
    
    <?php if ($isFilterApplied): ?>
    <a href="generate_pdf_report.php?start_date=<?php echo $startDate; ?>&end_date=<?php echo $endDate; ?>" class="btn btn-primary" target="_blank">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
        Télécharger PDF
    </a>
    <?php endif; ?>
</div>

<!-- Date filter -->
<div class="card mb-4">
    <div class="card-content">
        <form action="" method="GET" class="form-row">
            <input type="hidden" name="tab" value="rapports">
            <div class="form-col">
                <label class="form-label" for="start-date">Date de début</label>
                <input type="date" id="start-date" name="start_date" class="form-input" value="<?php echo $startDate; ?>">
            </div>
            <div class="form-col">
                <label class="form-label" for="end-date">Date de fin</label>
                <input type="date" id="end-date" name="end_date" class="form-input" value="<?php echo $endDate; ?>">
            </div>
            <div class="form-col" style="display: flex; align-items: flex-end; gap: 0.5rem;">
                <button type="submit" class="btn btn-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                    Générer le rapport
                </button>
                <button type="button" class="btn btn-outline" onclick="window.location.href='index.php?tab=rapports'">
                    Réinitialiser
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Visualisations -->
<div class="card mb-4">
    <div class="card-header">
        <div class="flex-between">
            <h3 class="font-medium">Visualisation des données</h3>
            <div>
                <select id="chart-type" class="form-select" onchange="changeChartType(this.value)">
                    <option value="bar" <?php echo $chartType === 'bar' ? 'selected' : ''; ?>>Histogramme</option>
                    <option value="line" <?php echo $chartType === 'line' ? 'selected' : ''; ?>>Courbe</option>
                    <option value="pie" <?php echo $chartType === 'pie' ? 'selected' : ''; ?>>Camembert</option>
                </select>
            </div>
        </div>
    </div>
    <div class="chart-container" style="height: 400px;">
    <canvas id="paymentsChart"></canvas>
</div>
</div>

<!-- Filtered payments table -->
<?php if ($isFilterApplied): ?>
<div class="card">
    <div class="card-header">
        <div class="flex-between">
            <h3 class="font-medium">Liste des paiements</h3>
            <div>
                <span class="text-primary font-medium">Total: <?php echo formatCurrency($total); ?></span>
            </div>
        </div>
    </div>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>IM</th>
                    <th>Pensionnaire</th>
                    <th>Diplme</th>
                    <th class="text-right">Montant</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($paiements) > 0): ?>
                    <?php foreach ($paiements as $paiement): ?>
                        <?php 
                        $personne = getPersonneByIM($db, $paiement['IM']);
                        $tarif = getTariffByNum($db, $paiement['num_tarif']);
                        ?>
                        <tr>
                            <td><?php echo formatDate($paiement['date']); ?></td>
                            <td><?php echo htmlspecialchars($paiement['IM']); ?></td>
                            <td><?php echo $personne ? htmlspecialchars($personne['Nom'] . ' ' . $personne['Prenoms']) : 'Inconnu'; ?></td>
                            <td><?php echo $tarif ? htmlspecialchars($tarif['diplome']) : 'Inconnu'; ?></td>
                            <td class="text-right font-medium"><?php echo $tarif ? formatCurrency($tarif['montant']) : '-'; ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="text-center text-muted">Aucun paiement trouvé pour cette période</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php else: ?>
<div class="text-center py-8">
    <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round" class="mx-auto text-muted-foreground/50 mb-4"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
    <h3 class="text-lg font-medium mb-2">Aucun rapport généré</h3>
    <p class="text-muted">
        Veuillez sélectionner une plage de dates et générer un rapport pour afficher les données.
    </p>
</div>
<?php endif; ?>

<script src="https://cdn.skypack.dev/chart.js@4.3.0/dist/chart.umd.min.js"></script>
<script src="assets/js/rapports.js"></script>