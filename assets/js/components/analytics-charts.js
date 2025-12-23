/**
 * Analytics Charts
 * Gestion des graphiques Chart.js pour la page analytics
 */

/**
 * Initialise tous les graphiques de la page analytics
 */
export function initAnalyticsCharts() {
    // Vérifier que Chart.js est chargé
    if (typeof Chart === 'undefined') {
        console.error('[analytics-charts] Chart.js n\'est pas chargé');
        return;
    }

    // Couleurs du design system
    const colors = {
        primary: 'rgb(0, 48, 128)',
        primaryLight: 'rgba(0, 48, 128, 0.1)',
        secondary: 'rgb(57, 191, 239)',
        secondaryLight: 'rgba(57, 191, 239, 0.1)',
        success: 'rgb(40, 167, 69)',
        warning: 'rgb(255, 193, 7)',
        danger: 'rgb(220, 53, 69)',
    };

    // Configuration globale Chart.js
    Chart.defaults.font.family = "'Poppins', sans-serif";
    Chart.defaults.color = '#6c757d';

    // 1. Performance par canal (Bar Chart)
    const channelCtx = document.getElementById('channelChart')?.getContext('2d');
    if (!channelCtx) {
        console.warn('[analytics-charts] Canvas channelChart non trouvé');
        return;
    }

    const channelChart = new Chart(channelCtx, {
        type: 'bar',
        data: {
            labels: ['LinkedIn', 'GoogleAds', 'Facebook', 'Email', 'Article'],
            datasets: [{
                label: 'Budget dépensé (K€)',
                data: [68, 52, 45, 32, 25],
                backgroundColor: colors.primary,
                borderRadius: 8,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    padding: 12,
                    borderRadius: 8,
                    callbacks: {
                        label: function(context) {
                            return context.parsed.y + 'K€';
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)'
                    },
                    ticks: {
                        callback: function(value) {
                            return value + 'K€';
                        }
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            }
        }
    });

    // 2. Répartition budgétaire (Doughnut Chart)
    const budgetCtx = document.getElementById('budgetChart')?.getContext('2d');
    if (budgetCtx) {
        new Chart(budgetCtx, {
            type: 'doughnut',
            data: {
                labels: ['Dépensé', 'Restant'],
                datasets: [{
                    data: [222, 118],
                    backgroundColor: [colors.primary, colors.secondary],
                    borderWidth: 0,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 15,
                            usePointStyle: true,
                            pointStyle: 'circle'
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        padding: 12,
                        borderRadius: 8,
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.parsed;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = Math.round((value / total) * 100);
                                return label + ': ' + value + 'K€ (' + percentage + '%)';
                            }
                        }
                    }
                }
            }
        });
    }

    // 3. Évolution du ROI (Line Chart)
    const roiCtx = document.getElementById('roiChart')?.getContext('2d');
    if (roiCtx) {
        new Chart(roiCtx, {
            type: 'line',
            data: {
                labels: ['Sem 1', 'Sem 2', 'Sem 3', 'Sem 4', 'Sem 5', 'Sem 6', 'Sem 7', 'Sem 8'],
                datasets: [{
                    label: 'ROI',
                    data: [2.1, 2.4, 2.8, 3.2, 3.5, 3.6, 3.7, 3.8],
                    borderColor: colors.success,
                    backgroundColor: 'rgba(40, 167, 69, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 5,
                    pointBackgroundColor: colors.success,
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointHoverRadius: 7,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        padding: 12,
                        borderRadius: 8,
                        callbacks: {
                            label: function(context) {
                                return 'ROI: ' + context.parsed.y + 'x';
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        },
                        ticks: {
                            callback: function(value) {
                                return value + 'x';
                            }
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    }

    // 4. Évolution des leads (Line Chart)
    const leadsCtx = document.getElementById('leadsChart')?.getContext('2d');
    if (leadsCtx) {
        new Chart(leadsCtx, {
            type: 'line',
            data: {
                labels: ['Sem 1', 'Sem 2', 'Sem 3', 'Sem 4', 'Sem 5', 'Sem 6', 'Sem 7', 'Sem 8'],
                datasets: [{
                    label: 'Leads',
                    data: [187, 234, 298, 356, 412, 478, 521, 602],
                    borderColor: colors.warning,
                    backgroundColor: 'rgba(255, 193, 7, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 5,
                    pointBackgroundColor: colors.warning,
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointHoverRadius: 7,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        padding: 12,
                        borderRadius: 8,
                        callbacks: {
                            label: function(context) {
                                return 'Leads: ' + context.parsed.y;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    }

    // Switch entre métriques pour le graphique canaux
    document.querySelectorAll('input[name="channelMetric"]').forEach(input => {
        input.addEventListener('change', function() {
            if (this.id === 'metric1') {
                channelChart.data.datasets[0].label = 'Budget dépensé (K€)';
                channelChart.data.datasets[0].data = [68, 52, 45, 32, 25];
            } else if (this.id === 'metric2') {
                channelChart.data.datasets[0].label = 'Leads générés';
                channelChart.data.datasets[0].data = [724, 598, 512, 387, 197];
            } else if (this.id === 'metric3') {
                channelChart.data.datasets[0].label = 'ROI';
                channelChart.data.datasets[0].data = [4.2, 3.8, 3.5, 4.0, 3.2];
            }
            channelChart.update();
        });
    });

    console.log('[analytics-charts] Graphiques initialisés');
}
