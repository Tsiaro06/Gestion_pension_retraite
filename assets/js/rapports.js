// Scripts pour la gestion des rapports
document.addEventListener('DOMContentLoaded', function() {
    // Initialiser le graphique
    setupChart();
});

let currentChart;

function setupChart() {
    const ctx = document.getElementById('paymentsChart').getContext('2d');
    const chartType = document.getElementById('chart-type').value;
    
    // Récupérer les données depuis les attributs data
    const chartLabels = JSON.parse(document.getElementById('paymentsChart').dataset.labels || '[]');
    const chartValues = JSON.parse(document.getElementById('paymentsChart').dataset.values || '[]');
    
    // Supprimer l'ancien graphique s'il existe
    if (currentChart) {
        currentChart.destroy();
    }
    
    // Configuration commune
    const commonOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            tooltip: {
                callbacks: {
                    label: function(context) {
                        let label = context.dataset.label || '';
                        if (label) {
                            label += ': ';
                        }
                        label += new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'MGA', minimumFractionDigits: 0 }).format(context.parsed.y || context.parsed || 0);
                        return label;
                    }
                }
            }
        }
    };
    
    // Configuration spécifique aux types de graphiques
    if (chartType === 'bar') {
        // Graphique en barres
        currentChart = new Chart(ctx, {
            type: 'bar',
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
                ...commonOptions,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'MGA', minimumFractionDigits: 0 }).format(value);
                            }
                        }
                    }
                }
            }
        });
    } else if (chartType === 'line') {
        // Graphique en ligne
        currentChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: chartLabels,
                datasets: [{
                    label: 'Montant total',
                    data: chartValues,
                    fill: false,
                    backgroundColor: 'rgba(59, 130, 246, 0.7)',
                    borderColor: 'rgba(59, 130, 246, 1)',
                    borderWidth: 2,
                    tension: 0.1,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                }]
            },
            options: {
                ...commonOptions,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'MGA', minimumFractionDigits: 0 }).format(value);
                            }
                        }
                    }
                }
            }
        });
    } else if (chartType === 'pie') {
        // Graphique en camembert
        const colors = [
            'rgba(54, 162, 235, 0.7)', 
            'rgba(75, 192, 192, 0.7)',
            'rgba(255, 206, 86, 0.7)',
            'rgba(255, 99, 132, 0.7)',
            'rgba(153, 102, 255, 0.7)'
        ];
        
        currentChart = new Chart(ctx, {
            type: 'pie',
            data: {
                labels: chartLabels,
                datasets: [{
                    data: chartValues,
                    backgroundColor: chartLabels.map((_, i) => colors[i % colors.length]),
                    borderColor: chartLabels.map((_, i) => colors[i % colors.length].replace('0.7', '1')),
                    borderWidth: 1
                }]
            },
            options: {
                ...commonOptions,
                plugins: {
                    ...commonOptions.plugins,
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    }
}

function changeChartType(type) {
    // Mettre à jour l'URL pour conserver le type de graphique lors des rechargements
    const currentUrl = new URL(window.location.href);
    currentUrl.searchParams.set('chart_type', type);
    window.history.replaceState({}, '', currentUrl.toString());
    
    // Mettre à jour le graphique
    setupChart();
}