<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/functions.php';

$db = getDatabaseConnection();

// Calculer les statistiques
$stats = calculateStats($db);

// Obtenir les données pour l'histogramme
$chartData = getPaymentDataForHistogram($db);

$chartLabels = json_encode(array_keys($chartData));
$chartValues = json_encode(array_values($chartData));

// // If no data is returned or it's empty, use test data
// if (empty($chartData)) {
//     $chartData = [
//         'Jan 2025' => 5000,
//         'Feb 2025' => 7500,
//         'Mar 2025' => 12000,
//         'Apr 2025' => 9000
//     ];
//     error_log("Using test data because actual data is empty");
// }

// Préparer les données pour le graphique circulaire
$pieData = [
    'Vivants' => $stats['activePensioners'] ?? 0,
    'Décédés' => $stats['deceasedPensioners'] ?? 0
];
$pieLabels = json_encode(array_keys($pieData));
$pieValues = json_encode(array_values($pieData));

?>


<div class="page-header mb-4">
    <h2 class="text-3xl font-bold">Tableau de bord</h2>
    <p class="text-muted">Aperçu du système de gestion des pensions des retraités</p>
</div>

<!-- Stats Cards -->
<div class="card-grid stats-grid">
    <div class="card stats-card">
        <div class="stats-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
        </div>
        <div class="stats-info">
            <p>Total Pensionnaires</p>
            <h3><?php echo $stats['totalPensioners'] ?? 0; ?></h3>
        </div>
    </div>
    
    <div class="card stats-card">
        <div class="stats-icon" style="background-color: rgba(16, 185, 129, 0.1); color: #10b981;">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><polyline points="16 11 18 13 22 9"></polyline></svg>
        </div>
        <div class="stats-info">
            <p>Pensionnaires Actifs</p>
            <h3><?php echo $stats['activePensioners'] ?? 0; ?></h3>
        </div>
    </div>
    
    <div class="card stats-card">
        <div class="stats-icon" style="background-color: rgba(100, 116, 139, 0.1); color: #64748b;">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>
        </div>
        <div class="stats-info">
            <p>Pensionnaires Décédés</p>
            <h3><?php echo $stats['deceasedPensioners'] ?? 0; ?></h3>
        </div>
    </div>
    
    <div class="card stats-card">
        <div class="stats-icon" style="background-color: rgba(239, 68, 68, 0.1); color: #ef4444;">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path></svg>
        </div>
        <div class="stats-info">
            <p>Total Conjoints</p>
            <h3><?php echo $stats['totalSpouses'] ?? 0; ?></h3>
        </div>
    </div>
    
    <div class="card stats-card">
        <div class="stats-icon" style="background-color: rgba(139, 92, 246, 0.1); color: #8b5cf6;">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 2v20l2-1 2 1 2-1 2 1 2-1 2 1 2-1 2 1V2l-2 1-2-1-2 1-2-1-2 1-2-1-2 1z"></path></svg>
        </div>
        <div class="stats-info">
            <p>Total Paiements</p>
            <h3><?php echo $stats['totalPayments'] ?? 0; ?></h3>
        </div>
    </div>
    
    <div class="card stats-card">
        <div class="stats-icon" style="background-color: rgba(250, 204, 21, 0.1); color: #facc15;">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline></svg>
        </div>
        <div class="stats-info">
            <p>Statistiques</p>
            <h3>Disponibles</h3>
        </div>
    </div>
</div>

<!-- Charts -->
<div class="card-grid chart-grid">
    <div class="card chart-card">
        <div class="card-header">
            <h3 class="font-medium">Paiements Mensuels</h3>
        </div>
        <div class="chart-container">
            <canvas id="paymentsChart"></canvas>
        </div>
    </div>
    
    <div class="card chart-card">
        <div class="card-header">
            <h3 class="font-medium">Répartition par Statut</h3>
        </div>
        <div class="chart-container">
            <canvas id="statusChart"></canvas>
        </div>
    </div>
</div>

<!-- Recent Payments -->
<div class="card my-4">
    <div class="card-header">
        <h3 class="font-medium">Paiements Récents</h3>
    </div>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Diplôme</th>
                    <th class="text-right">Montant</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($stats['recentPayments'])): ?>
                    <?php foreach ($stats['recentPayments'] as $payment): ?>
                        <tr>
                            <td><?php echo formatDate($payment['date']); ?></td>
                            <td><?php echo $payment['diplome'] ?? 'Inconnu'; ?></td>
                            <td class="text-right font-medium"><?php echo isset($payment['montant']) ? formatCurrency($payment['montant']) : '-'; ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" class="text-center text-muted">Aucun paiement récent</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Include Chart.js library -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Chart.js - Graphique des paiements mensuels
    const paymentsCtx = document.getElementById('paymentsChart').getContext('2d');
    
    // Les variables PHP sont directement disponibles ici
    const chartLabels = <?php echo $chartLabels; ?>;
    const chartValues = <?php echo $chartValues; ?>;

    console.log("Chart Labels:", chartLabels);
    console.log("Chart Values:", chartValues);
    
    const paymentsChart = new Chart(paymentsCtx, {
        type: 'bar',
        data: {
            labels: chartLabels,
            datasets: [{
                label: 'Montant des Paiements',
                data: chartValues,
                backgroundColor: 'rgba(59, 130, 246, 0.7)',
                borderColor: 'rgba(59, 130, 246, 1)',
                borderWidth: 1,
                borderRadius: 4,
                borderSkipped: false,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return value.toString().replace(/\B(?=(\d{3})+(?!\d))/g, " ") + ' Ar';
                        }
                    }
                }
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.dataset.label || '';
                            if (label) {
                                label += ': ';
                            }
                            label += context.parsed.y.toString().replace(/\B(?=(\d{3})+(?!\d))/g, " ") + ' Ar';
                            return label;
                        }
                    }
                }
            }
        }
    });
    
    // Chart.js - Graphique circulaire statut
    const statusCtx = document.getElementById('statusChart').getContext('2d');
    
    // Les variables PHP sont directement disponibles ici
    const pieLabels = <?php echo $pieLabels; ?>;
    const pieValues = <?php echo $pieValues; ?>;
    
    const statusChart = new Chart(statusCtx, {
        type: 'doughnut',
        data: {
            labels: pieLabels,
            datasets: [{
                data: pieValues,
                backgroundColor: [
                    'rgba(16, 185, 129, 0.7)',  // Vert pour Vivants
                    'rgba(10, 10, 10, 1)',   // Noir pour Décédés
                ],
                borderColor: [
                    'rgba(16, 185, 129, 1)',
                    'rgba(10, 10, 10, 1)',
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const label = context.label || '';
                            const value = context.raw;
                            const total = context.chart.data.datasets[0].data.reduce((a, b) => a + b, 0);
                            const percentage = Math.round((value / total) * 100);
                            return `${label}: ${value} (${percentage}%)`;
                        }
                    }
                }
            }
        }
    });
});
</script>

