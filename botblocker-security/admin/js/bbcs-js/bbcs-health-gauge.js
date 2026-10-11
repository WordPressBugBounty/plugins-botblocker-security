// Health Gauge Chart
jQuery(document).ready(function($){
    function getColorByPercent(percent) {
        if (percent < 40) return '#dc3545';
        else if (percent < 50) return '#ff5100ff';
        else if (percent < 60) return '#ff9800';
        else if (percent < 75) return '#ffc107';
        else if (percent < 90) return '#8bc34a';
        else return '#28a745';
    }

    function createHealthGauge(canvas, usedPercent, usedStatusTxt) {
        var freePercent = 100 - usedPercent;
        var backgroundColor = getColorByPercent(usedPercent);

        var healthCenterText = {
            id: 'healthCenterText',
            afterDatasetsDraw: function(chart) {
                var ctx = chart.ctx;
                var centerX = chart.width / 2;
                var centerY = chart.height / 2;

                ctx.save();
                ctx.textAlign = 'center';
                ctx.textBaseline = 'middle';

                var percent = chart.data.datasets[0].data[0];
                var color = getColorByPercent(percent);

                ctx.font = 'bold 22px Arial';
                ctx.fillStyle = color;
                ctx.fillText(percent.toFixed(0) + '%', centerX, centerY + 26);

                ctx.font = '16px Arial';
                ctx.fillStyle = color;
                ctx.fillText(usedStatusTxt, centerX, centerY + 54);

                ctx.restore();
            }
        };

        new Chart(canvas, {
            type: 'doughnut',
            data: {
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
    }

    $('.bbcs-health-gauge-canvas').each(function() {
        var canvas = this;
        var $canvas = $(canvas);
        var usedPercent = +$canvas.data('health-value');
        var usedStatusTxt = $canvas.data('bbcs-label');

        createHealthGauge(canvas, usedPercent, usedStatusTxt);
    });
});
