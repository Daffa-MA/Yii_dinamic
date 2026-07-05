(function() {
    'use strict';

    window.MasterChartManager = {
        instances: {},
        chartsData: {},

        init: function(container) {
            var scope = container || document;
            var chartContainers = scope.querySelectorAll('[data-master-chart]');
            if (!chartContainers.length) return;
            var self = this;
            chartContainers.forEach(function(el) {
                var chartId = el.getAttribute('data-master-chart');
                if (!chartId || self.instances[chartId]) return;
                self.loadAndRender(el, chartId);
            });
        },

        loadAndRender: function(container, chartId) {
            var self = this;
            var url = '/master-chart/data?id=' + encodeURIComponent(chartId);
            var params = new URLSearchParams();
            var dtParams = this.collectFilterParams();
            dtParams.forEach(function(value, key) {
                params.set(key, value);
            });
            var qs = params.toString();
            if (qs) url += '&' + qs;

            self.showLoading(container);

            fetch(url, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(function(res) { return res.json(); })
            .then(function(data) {
                if (!data || !data.success) {
                    self.showError(container, data && data.message ? data.message : 'Gagal memuat chart');
                    return;
                }
                self.renderChart(container, data);
            })
            .catch(function() {
                self.showError(container, 'Gagal terhubung ke server');
            });
        },

        collectFilterParams: function() {
            var params = new URLSearchParams();
            var urlParams = new URLSearchParams(window.location.search);
            urlParams.forEach(function(value, key) {
                if (key.indexOf('dt_') === 0 || key === 'search' || key === 'filter') {
                    params.set(key, value);
                }
            });
            return params;
        },

        renderChart: function(container, data) {
            var config = data.config;
            var chartData = data.chart;
            var palette = data.palette || [];

            var chartType = config.chart_type || 'bar';
            var apexType = this.mapChartType(chartType);
            var height = config.height || 300;
            var theme = config.theme || 'light';
            var isDark = document.documentElement.classList.contains('dark') || theme === 'dark' || (theme === 'auto' && window.matchMedia('(prefers-color-scheme: dark)').matches);

            var series = chartData.series || chartData.series || [];
            var labels = chartData.labels || [];

            if (chartType === 'pie' || chartType === 'donut') {
                series = chartData.series || [];
                labels = chartData.labels || [];
            }

            var options = {
                chart: {
                    type: apexType,
                    height: height,
                    toolbar: { show: !!config.show_toolbar },
                    animations: { enabled: config.animation !== 'none', easing: config.animation === 'none' ? 'linear' : config.animation, dynamicAnimation: { enabled: true } },
                    background: 'transparent',
                    foreColor: isDark ? '#e2e8f0' : '#64748b',
                    zoom: { enabled: chartType === 'scatter' || chartType === 'bubble' },
                },
                series: series,
                labels: labels,
                colors: palette.length ? palette : undefined,
                dataLabels: { enabled: !!config.show_label },
                legend: { show: !!config.show_legend, position: 'bottom', fontSize: '12px', labels: { colors: isDark ? '#e2e8f0' : '#64748b' } },
                grid: { show: !!config.show_grid, borderColor: isDark ? '#334155' : '#e2e8f0' },
                stroke: { show: true, curve: 'smooth', width: chartType === 'line' || chartType === 'area' ? 2 : 0 },
                fill: { opacity: chartType === 'area' || chartType === 'stacked_area' ? 0.5 : 1 },
                plotOptions: {
                    bar: {
                        horizontal: chartType === 'bar_horizontal',
                        barHeight: '70%',
                        columnWidth: '60%',
                        borderRadius: 4,
                        dataLabels: { position: 'top' },
                    },
                    pie: {
                        donut: { labels: { show: !!config.show_total, total: { show: !!config.show_total, label: 'Total', formatter: function() { return chartData.total || 0; } } } },
                    },
                },
                tooltip: { enabled: true, theme: isDark ? 'dark' : 'light', shared: true, intersect: false },
                noData: { text: 'Tidak ada data', align: 'center', verticalAlign: 'middle', style: { fontSize: '14px', color: '#94a3b8' } },
                responsive: [{ breakpoint: 768, options: { chart: { height: Math.min(height, 250) }, legend: { position: 'bottom', fontSize: '11px' } } }],
            };

            if (chartType === 'bar_horizontal') {
                options.plotOptions = options.plotOptions || {};
                options.plotOptions.bar = options.plotOptions.bar || {};
                options.plotOptions.bar.horizontal = true;
            }

            if (chartType === 'bubble' || chartType === 'scatter') {
                options.chart.zoom = { enabled: true, type: 'xy' };
            }

            if (chartType === 'mixed') {
                options.chart.type = 'line';
                options.plotOptions = { bar: { horizontal: false, columnWidth: '60%' } };
            }

            if (chartType === 'stacked_bar' || chartType === 'stacked_area') {
                if (options.plotOptions && options.plotOptions.bar) {
                    options.plotOptions.bar.stacked = true;
                }
                if (options.chart) {
                    options.chart.stacked = true;
                }
            }

            if (chartType === 'radar') {
                options.chart.type = 'radar';
                options.plotOptions = { radar: { polygons: { strokeColors: isDark ? '#334155' : '#e2e8f0', connectorColors: isDark ? '#334155' : '#e2e8f0' } } };
                options.stroke = { show: true, width: 2, colors: palette };
                options.fill = { opacity: 0.3 };
                options.markers = { size: 4 };
            }

            if (chartType === 'polar_area') {
                options.chart.type = 'polarArea';
                options.stroke = { show: false };
                options.fill = { opacity: 0.8 };
            }

            if (isDark) {
                options.chart.foreColor = '#e2e8f0';
            }

            var chartId = 'chart-' + (config.id || Math.random().toString(36).substr(2, 6));
            container.innerHTML = '';
            var chartEl = document.createElement('div');
            chartEl.id = chartId;
            container.appendChild(chartEl);

            try {
                var chart = new ApexCharts(chartEl, options);
                chart.render();
                var key = String(config.id || chartId);
                this.instances[key] = chart;
                this.chartsData[key] = data;
            } catch (e) {
                this.showError(container, 'Gagal render chart: ' + e.message);
            }
        },

        mapChartType: function(type) {
            var map = {
                bar: 'bar', bar_horizontal: 'bar', line: 'line', area: 'area',
                pie: 'pie', donut: 'donut', radar: 'radar', polar_area: 'polarArea',
                bubble: 'bubble', scatter: 'scatter', stacked_bar: 'bar',
                stacked_area: 'area', mixed: 'line', multi_series: 'bar'
            };
            return map[type] || 'bar';
        },

        showLoading: function(container) {
            var height = container.getAttribute('data-chart-height') || '300';
            container.innerHTML =
                '<div class="chart-skeleton" style="height:' + height + 'px;display:flex;align-items:center;justify-content:center;background:#f8fafc;border-radius:12px;">' +
                    '<div style="text-align:center;color:#94a3b8;">' +
                        '<svg class="animate-spin" style="width:32px;height:32px;margin:0 auto 8px;display:block;" viewBox="0 0 24 24" fill="none">' +
                            '<circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" stroke-dasharray="31.4 31.4" stroke-linecap="round" />' +
                        '</svg>' +
                        '<div style="font-size:13px;">Memuat chart...</div>' +
                    '</div>' +
                '</div>';
        },

        showError: function(container, message) {
            container.innerHTML =
                '<div class="chart-error" style="display:flex;align-items:center;justify-content:center;min-height:200px;background:#fef2f2;border:1px solid #fecaca;border-radius:12px;">' +
                    '<div style="text-align:center;color:#991b1b;padding:20px;">' +
                        '<span style="font-size:28px;display:block;margin-bottom:8px;">!</span>' +
                        '<div style="font-size:14px;font-weight:600;margin-bottom:4px;">Gagal Memuat Chart</div>' +
                        '<div style="font-size:12px;color:#b91c1c;">' + this.escapeHtml(message) + '</div>' +
                    '</div>' +
                '</div>';
        },

        escapeHtml: function(text) {
            var div = document.createElement('div');
            div.appendChild(document.createTextNode(text || ''));
            return div.innerHTML;
        },

        refresh: function(chartId) {
            var key = String(chartId);
            if (this.instances[key]) {
                this.instances[key].destroy();
                delete this.instances[key];
            }
            var container = document.querySelector('[data-master-chart="' + key + '"]');
            if (container) this.loadAndRender(container, chartId);
        },

        refreshAll: function() {
            var self = this;
            Object.keys(self.instances).forEach(function(key) {
                self.refresh(key);
            });
        },

        destroy: function(chartId) {
            var key = String(chartId);
            if (this.instances[key]) {
                this.instances[key].destroy();
                delete this.instances[key];
                delete this.chartsData[key];
            }
        },

        destroyAll: function() {
            var self = this;
            Object.keys(self.instances).forEach(function(key) {
                self.destroy(key);
            });
        }
    };

    document.addEventListener('DOMContentLoaded', function() {
        if (typeof ApexCharts !== 'undefined') {
            MasterChartManager.init();
        }
    });

    window.addEventListener('load', function() {
        if (typeof ApexCharts !== 'undefined') {
            MasterChartManager.init();
        }
    });

    if (document.readyState === 'complete' && typeof ApexCharts !== 'undefined') {
        MasterChartManager.init();
    }
})();
