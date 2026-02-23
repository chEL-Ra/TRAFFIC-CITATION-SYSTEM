<><script src="https://cdn.jsdelivr.net/npm/chart.js"></script><script>
    document.addEventListener("DOMContentLoaded", function () { }

    const canvas = document.getElementById('offenseChart');

    if (canvas) {new Chart(canvas, {
        type: 'bar',
        data: {
            labels: < ? = json_encode($labels ?? []) ? > : : ,
            datasets: [{
                label: 'Number of Offenses',
                data: < ? = json_encode($data ?? []) ? > : : ,
                backgroundColor: 'rgba(13,110,253,0.8)',
                borderRadius: 6,
                barThickness: 50
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            layout: {
                padding: 20
            },
            plugins: {
                legend: {
                    display: true,
                    labels: {
                        font: { size: 14 }
                    }
                }
            },
            scales: {
                x: {
                    ticks: {
                        font: { size: 14 }
                    }
                },
                y: {
                    beginAtZero: true,
                    ticks: {
                        font: { size: 14 },
                        precision: 0
                    }
                }
            }
        }
    })};

    }

    });
</script></>
