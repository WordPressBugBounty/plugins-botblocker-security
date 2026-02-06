// Health Gauge Chart
jQuery(document).ready(function($){
    const health_gauge = $('#bbcs-health_gauge');

    if (health_gauge.length === 0) return;

    const usedPercent = +health_gauge.data('health-value');   // Change this value
    const usedStatusTxt = health_gauge.data('bbcs-label');    // Change this value
    const freePercent = 100 - usedPercent;
    const backgroundColor = getColorByPercent(usedPercent);

    function getColorByPercent(percent) {
        if (percent < 40) return '#dc3545';
        else if (percent < 50) return '#ff5100ff';
        else if (percent < 60) return '#ff9800';
        else if (percent < 75) return '#ffc107';
        else if (percent < 90) return '#8bc34a';
        else return '#28a745';
    }

    const healthCenterText = {
        id: 'healthCenterText',
        afterDatasetsDraw: function(chart) {
            const ctx = chart.ctx;
            const centerX = chart.width / 2;
            const centerY = chart.height / 2;
            
            ctx.save();
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            
            const percent = chart.data.datasets[0].data[0];
            const color = getColorByPercent(percent);

            ctx.font = 'bold 22px Arial';
            ctx.fillStyle = color;
            ctx.fillText(percent.toFixed(0) + '%', centerX, centerY + 26);
            
            ctx.font = '16px Arial';
            ctx.fillStyle = color;
            ctx.fillText(usedStatusTxt, centerX, centerY + 54);
            
            ctx.restore();
        }
    };

    new Chart(health_gauge, {
        type: 'doughnut',
        data: {
            // labels: [usedPercent, freePercent],
            datasets: [{
                data: [usedPercent, freePercent],
                backgroundColor: [backgroundColor, '#e6e6e6'],
                borderWidth: 0
            }]
        },
        options: {
            responsive: false,
            maintainAspectRatio: false,
            rotation: -90,
            circumference: 180,
            cutout: '70%',
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    enabled: false
                }
            }
        },
        plugins: [healthCenterText]
    });
});
