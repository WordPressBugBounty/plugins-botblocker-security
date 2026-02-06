(function ($) {
    "use strict";

    var bbcs_charts = (function () {
        function init() {
            initPieDonut();
            initDaily();
            initVisitorsMap(); 
            initHitsUniques();
            initRules();
            initHealthGauge();
        }

        function initPieDonut() {
            var nodes = document.querySelectorAll('.bbcs-statistics-chart');
            if (!nodes || !nodes.length) return;
            nodes.forEach(function (node) {
                if (node.dataset.bbcsInitialized === '1') return;
                try {
                    createPieDonut(node);
                    node.dataset.bbcsInitialized = '1';
                } catch (e) {}
            });
        }

        function createPieDonut(node) {
            var typeAttr = (node.getAttribute('data-bbcs-type') || 'pie').toLowerCase();
            var isDonut = (typeAttr === 'donut' || typeAttr === 'doughnut');
            var chartType = isDonut ? 'doughnut' : 'pie';
            var labels = parseJSONAttr(node, 'data-bbcs-labels') || [];
            var values = parseJSONAttr(node, 'data-bbcs-values') || [];
            var canvas = createCanvas(node);
            var ctx = canvas.getContext('2d');
            var colors = buildColors(labels.length);
            new Chart(ctx, {
                type: chartType,
                data: { labels: labels, datasets: [{ data: values, backgroundColor: colors, borderColor: colors, borderWidth: 1 }] },
                options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false }, title: { display: false } }, cutout: isDonut ? '40%' : undefined }
            });
        }

        function initDaily() {
            var nodes = document.querySelectorAll('.bbcs-daily-hits-chart');
            if (!nodes || !nodes.length) return;
            nodes.forEach(function (node) {
                if (node.dataset.bbcsInitialized === '1') return;
                try {
                    createDaily(node);
                    node.dataset.bbcsInitialized = '1';
                } catch (e) {}
            });
        }

        function createDaily(node) {
            var labels = parseJSONAttr(node, 'data-bbcs-labels') || [];
            var values = parseJSONAttr(node, 'data-bbcs-values') || [];
            var canvas = createCanvas(node);
            var ctx = canvas.getContext('2d');
            new Chart(ctx, {
                type: 'bar',
                data: { labels: labels, datasets: [{ data: values, backgroundColor: '#4285F4' }] },
                options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false }, title: { display: false } }, scales: { x: { ticks: { maxRotation: 45, minRotation: 45, font: { size: 10 } } }, y: { beginAtZero: true, ticks: { display: false }, grid: { display: false }, border: { display: false } } } }
            });
        }

        function initHitsUniques() {
            var nodes = document.querySelectorAll('.bbcs-hits-uniques-chart');
            if (!nodes || !nodes.length) return;
            nodes.forEach(function (node) {
                if (node.dataset.bbcsInitialized === '1') return;
                try {
                    createHitsUniques(node);
                    node.dataset.bbcsInitialized = '1';
                } catch (e) {}
            });
        }

        function createHitsUniques(node) {
            var labels = parseJSONAttr(node, 'data-bbcs-labels') || [];
            var uniques = parseJSONAttr(node, 'data-bbcs-values-uniques') || [];
            var hits = parseJSONAttr(node, 'data-bbcs-values-hits') || [];
            var canvas = createCanvas(node);
            var ctx = canvas.getContext('2d');
            new Chart(ctx, {
                type: 'line',
                data: { labels: labels, datasets: [ { label: 'Uniques', data: uniques, borderColor: '#4285F4', backgroundColor: 'transparent', pointRadius: 2, tension: 0 }, { label: 'Hits', data: hits, borderColor: '#DB4437', backgroundColor: 'transparent', pointRadius: 2, tension: 0 } ] },
                options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false }, title: { display: false }, tooltip: { mode: 'index', intersect: false } }, interaction: { mode: 'index', intersect: false }, scales: { y: { beginAtZero: true } } }
            });
        }

        function initRules() {
            var nodes = document.querySelectorAll('.bbcs-rules-stats-chart');
            if (!nodes || !nodes.length) return;
            nodes.forEach(function (node) {
                if (node.dataset.bbcsInitialized === '1') return;
                try {
                    createRules(node);
                    node.dataset.bbcsInitialized = '1';
                } catch (e) {}
            });
        }

        function createRules(node) {
            var labels = parseJSONAttr(node, 'data-bbcs-labels') || [];
            var blocked = parseJSONAttr(node, 'data-bbcs-values-blocked') || [];
            var allowed = parseJSONAttr(node, 'data-bbcs-values-allowed') || [];
            var canvas = createCanvas(node);
            var ctx = canvas.getContext('2d');
            new Chart(ctx, {
                type: 'bar',
                data: { labels: labels, datasets: [ { label: 'Blocked', data: blocked, backgroundColor: '#FF0000', stack: 'rules' }, { label: 'Allowed', data: allowed, backgroundColor: '#0000FF', stack: 'rules' } ] },
                options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: true, position: 'top' }, title: { display: false } }, scales: { x: { stacked: true }, y: { stacked: true, beginAtZero: true } } }
            });
        }

        var bbcsMeterPlugin = {
            id: 'bbcsMeter',
            afterDraw: function(chart) {
                var opts = chart.options.plugins && chart.options.plugins.bbcsMeter ? chart.options.plugins.bbcsMeter : null;
                if (!opts) return;
                var meta = chart.getDatasetMeta(0);
                if (!meta || !meta.data || !meta.data[0]) return;
                var bar = meta.data[0];
                var ctx = chart.ctx;
                var x = bar.x, y = bar.y, h = bar.height;
                var min = Number(opts.min)||0, max = Number(opts.max)||100, val = Number(opts.value)||0;
                var p = (val - min) / (Math.max(1e-9, max - min));
                p = Math.max(0, Math.min(1, p));
                var xScale = chart.scales.x;
                var endX = xScale.getPixelForValue((max - min) * p);
                ctx.save();
                ctx.strokeStyle = '#11182733';
                ctx.lineWidth = 2;
                ctx.beginPath();
                ctx.moveTo(endX, y - h/2 - 6);
                ctx.lineTo(endX, y + h/2 + 6);
                ctx.stroke();
                var dec = Number(opts.decimals) || 0;
                var t = (dec > 0 ? val.toFixed(dec) : Math.round(val).toString()) + (opts.symbol || '');
                ctx.fillStyle = '#111827';
                ctx.textAlign = 'center';
                ctx.textBaseline = 'bottom';
                ctx.font = '600 16px Poppins, Arial, sans-serif';
                ctx.fillText(t, (xScale.left + xScale.right) / 2, y - h/2 - 10);
                if (opts.label) {
                    ctx.fillStyle = '#6b7280';
                    ctx.font = '500 12px Poppins, Arial, sans-serif';
                    ctx.textBaseline = 'top';
                    ctx.fillText(opts.label, (xScale.left + xScale.right) / 2, y + h/2 + 10);
                }
                ctx.restore();
            }
        };

        function initHealthGauge() {
            var nodes = document.querySelectorAll('.bbcs-health-gauge');
            if (!nodes || !nodes.length) return;
            nodes.forEach(function (node) {
                if (node.dataset.bbcsInitialized === '1') return;
                try {
                    createHealthGauge(node);
                    node.dataset.bbcsInitialized = '1';
                } catch (e) {}
            });
        }

        function createHealthGauge(node) {
            if (!node.style.height || node.clientHeight === 0) {
                node.style.height = '160px';
            }
            var min = toNumber(node.getAttribute('data-bbcs-min'), 0);
            var max = toNumber(node.getAttribute('data-bbcs-max'), 100);
            var value = toNumber(node.getAttribute('data-bbcs-value'), 0);
            var decimals = toNumber(node.getAttribute('data-bbcs-decimals'), 0);
            var label = node.getAttribute('data-bbcs-label') || '';
            var symbol = node.getAttribute('data-bbcs-symbol') || '';
            var levelColors = parseJSONAttr(node, 'data-bbcs-level-colors') || ['#ff3b30','#d8ca00','#43bf58'];
            var span = Math.max(1e-9, max - min);
            var filled = Math.max(0, Math.min(span, value - min));
            var rest = Math.max(0, span - filled);
            var canvas = createCanvas(node);
            var ctx = canvas.getContext('2d');
            var grad = ctx.createLinearGradient(0, 0, canvas.width, 0);
            for (var i = 0; i < levelColors.length; i++) {
                grad.addColorStop(i / (levelColors.length - 1 || 1), levelColors[i]);
            }
            new Chart(ctx, {
                type: 'bar',
                data: { labels: [''], datasets: [
                    { data: [filled], backgroundColor: grad, stack: 'm', borderRadius: 8, barPercentage: 0.8, categoryPercentage: 0.8 },
                    { data: [rest], backgroundColor: '#e5e7eb', stack: 'm', borderRadius: 8, barPercentage: 0.8, categoryPercentage: 0.8 }
                ] },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    indexAxis: 'y',
                    plugins: { legend: { display: false }, title: { display: false }, bbcsMeter: { label: label, value: value, min: min, max: max, symbol: symbol, decimals: decimals } },
                    scales: {
                        x: { beginAtZero: true, stacked: true, max: span, grid: { display: false }, ticks: { display: false }, border: { display: false } },
                        y: { stacked: true, grid: { display: false }, ticks: { display: false }, border: { display: false } }
                    }
                },
                plugins: [bbcsMeterPlugin]
            });
        }

        function initVisitorsMap() {
            var nodes = document.querySelectorAll('.bbcs-visitors-map');
            if (!nodes || !nodes.length) return;
            nodes.forEach(function (node) {
                if (node.dataset.bbcsInitialized === '1') return;
                try {
                    createVisitorsMap(node);
                    node.dataset.bbcsInitialized = '1';
                } catch (e) {}
            });
        }

        function createVisitorsMap(node) {
            var mapData = parseJSONAttr(node, 'data-bbcs-values') || {};
            // ensure the node has an id for selector usage
            if (!node.id) {
                node.id = 'bbcs_visitors_jsvectormap_' + Math.random().toString(36).substr(2, 9);
            }
            var selector = '#' + node.id;
            new jsVectorMap({
                selector: selector,
                map: 'world',
                visualizeData: {
                    scale: ['#e3f2fd', '#1565c0'],
                    values: mapData
                },
                regionStyle: {
                    initial: {
                        fill: '#CCCCCC',
                        stroke: "#ffffff",
                        strokeWidth: 1,
                        fillOpacity: 1
                    }
                },
                onRegionTooltipShow: function(event, tooltip, code) {
                    var visitors = mapData[code] || 0;
                    tooltip.css({
                        backgroundColor: '#61639F'
                    });
                    tooltip.text(
                        `<h5 class="bbcs-map-label-h">${tooltip.text()}</h5>` +
                        `<p class="bbcs-map-label-p">Visitors: ${visitors}</p>`,
                        true
                    );
                }
            });
        }

        function createCanvas(node) {
            var canvas = document.createElement('canvas');
            canvas.width = node.clientWidth || 300;
            canvas.height = node.clientHeight || 300;
            node.innerHTML = '';
            node.appendChild(canvas);
            return canvas;
        }

        function parseJSONAttr(node, attr) {
            var val = node.getAttribute(attr);
            if (!val) return null;
            try { return JSON.parse(val); } catch (e) { return null; }
        }

        function buildColors(n) {
            var palette = ['#4e79a7','#f28e2b','#e15759','#76b7b2','#59a14f','#edc948','#b07aa1','#ff9da7','#9c755f','#bab0ab'];
            var out = [];
            for (var i = 0; i < n; i++) out.push(palette[i % palette.length]);
            return out;
        }

        function toNumber(v, d) { var n = parseFloat(v); return isNaN(n) ? d : n; }

        return { init: init };
    })();

    $(function () { bbcs_charts.init(); });

})(jQuery);
