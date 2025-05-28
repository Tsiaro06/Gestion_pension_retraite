// /
// Scripts pour le tableau de bord
document.addEventListener('DOMContentLoaded', function() {
    // Chart.js - Graphique des paiements mensuels
    const paymentsCtx = document.getElementById('paymentsChart').getContext('2d');
    
    // Récupérer les données passées depuis PHP
    const chartLabels = JSON.parse('<?php echo $chartLabels; ?>');
    const chartValues = JSON.parse('<?php echo $chartValues; ?>');
    
    const paymentsChart = new Chart(paymentsCtx, {
        type: 'line',
        data: {
            labels: chartLabels,
            datasets: [{
                label: 'Montant total',
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
    
    // Récupérer les données passées depuis PHP
    const pieLabels = JSON.parse('<?php echo $pieLabels; ?>');
    const pieValues = JSON.parse('<?php echo $pieValues; ?>');
    
    const statusChart = new Chart(statusCtx, {
        type: 'doughnut',
        data: {
            labels: pieLabels,
            datasets: [{
                data: pieValues,
                backgroundColor: [
                    'rgba(16, 185, 129, 0.7)',  // Vert pour Vivants
                    'rgba(10, 10, 10, 1)',   // Rouge pour Décédés
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