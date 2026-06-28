/* NMS — app.js */

// ── Auto-dismiss success alerts after 3s ─────────────────────────
document.addEventListener('DOMContentLoaded', function () {
    setTimeout(function () {
        document.querySelectorAll('.alert.alert-success').forEach(function (el) {
            var alert = bootstrap.Alert.getOrCreateInstance(el);
            if (alert) alert.close();
        });
    }, 3000);
});

// ── Status badge tooltips ─────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function () {
    var statusLabels = {
        'suw':    'Severely Underweight (WFA < -3 SD)',
        'uw':     'Underweight (WFA -3 to -2 SD)',
        'normal': 'Normal nutritional status',
        'ow':     'Overweight',
        'ob':     'Obese',
        'sw':     'Severely Wasted (WFL/H < -3 SD)',
        'mw':     'Moderately Wasted (WFL/H -3 to -2 SD)',
        'stunted':'Stunted (HFA < -2 SD)',
        'tall':   'Tall for age',
    };
    document.querySelectorAll('.badge[class*="status-"]').forEach(function (el) {
        var match = el.className.match(/status-(\S+)/);
        if (!match) return;
        var key = match[1].toLowerCase();
        if (statusLabels[key]) {
            el.setAttribute('data-bs-toggle', 'tooltip');
            el.setAttribute('data-bs-placement', 'top');
            el.setAttribute('title', statusLabels[key]);
            new bootstrap.Tooltip(el, { trigger: 'hover' });
        }
    });
});

// ── Sidebar Toggle ───────────────────────────────────────────────
(function initSidebar() {
    const btn     = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('sidebar');
    if (!btn || !sidebar) return;

    let backdrop = null;

    function openSidebar() {
        sidebar.classList.add('sidebar-open');
        backdrop = document.createElement('div');
        backdrop.className = 'sidebar-backdrop';
        backdrop.addEventListener('click', closeSidebar);
        document.body.appendChild(backdrop);
    }

    function closeSidebar() {
        sidebar.classList.remove('sidebar-open');
        if (backdrop) { backdrop.remove(); backdrop = null; }
    }

    btn.addEventListener('click', function () {
        sidebar.classList.contains('sidebar-open') ? closeSidebar() : openSidebar();
    });

    window.addEventListener('resize', function () {
        if (window.innerWidth >= 992) closeSidebar();
    });
})();

// ── Dashboard Charts ────────────────────────────────────────────
(function () {
    const data = window.__dashboardData;
    if (!data) return;

    // Status colours
    const statusColor = {
        SUW:    '#dc2626',
        UW:     '#f59e0b',
        Normal: '#16a34a',
        OW:     '#0ea5e9',
        OB:     '#6366f1',
    };

    // 1. Barangay chart
    const barangayCtx = document.getElementById('barangayChart');
    if (barangayCtx && data.statusData.length) {
        const barangays = [...new Set(data.statusData.map(r => r.barangay))];
        const statuses  = Object.keys(statusColor);
        const datasets  = statuses.map(s => ({
            label: s,
            data: barangays.map(b => {
                const row = data.statusData.find(r => r.barangay === b && r.nutritional_status === s);
                return row ? parseInt(row.count) : 0;
            }),
            backgroundColor: statusColor[s],
        }));

        new Chart(barangayCtx, {
            type: 'bar',
            data: { labels: barangays, datasets },
            options: {
                responsive: true,
                plugins: { legend: { position: 'top' } },
                scales: { x: { stacked: true }, y: { stacked: true, beginAtZero: true } },
            },
        });
    }

    // 2. Enrollment pie
    const pieCtx = document.getElementById('enrollmentPie');
    if (pieCtx) {
        const totalEnrollments = data.enrollmentData.reduce((s, r) => s + parseInt(r.count), 0);
        if (totalEnrollments === 0) {
            pieCtx.parentElement.innerHTML = '<p class="text-center text-muted py-4">No active enrollments yet.</p>';
        } else {
            new Chart(pieCtx, {
                type: 'doughnut',
                data: {
                    labels:   data.enrollmentData.map(r => r.name && r.name !== 'unidentified' ? r.name : (r.code || 'Other')),
                    datasets: [{ data: data.enrollmentData.map(r => parseInt(r.count)),
                                 backgroundColor: data.enrollmentData.map((r, i) => {
                                     const map = { primary:'#2563eb', success:'#16a34a', warning:'#f59e0b', danger:'#dc2626', info:'#0891b2', secondary:'#6b7280' };
                                     return map[r.color] || ['#2563eb','#f59e0b','#16a34a','#8b5cf6','#ec4899'][i % 5];
                                 }) }],
                },
                options: { responsive: true, plugins: { legend: { position: 'bottom' } } },
            });
        }
    }

    // 3. OPT trend line
    const trendCtx = document.getElementById('trendChart');
    if (trendCtx && data.trendData.length) {
        const labels   = [...new Set(data.trendData.map(r => r.assessment_year + ' ' + r.period))];
        const statuses = Object.keys(statusColor);
        const datasets = statuses.map(s => ({
            label: s,
            data: labels.map(lbl => {
                const [yr, pd] = lbl.split(' ');
                const row = data.trendData.find(r =>
                    String(r.assessment_year) === yr && r.period === pd && r.nutritional_status === s
                );
                return row ? parseInt(row.count) : 0;
            }),
            borderColor:     statusColor[s],
            backgroundColor: statusColor[s] + '33',
            tension: 0.3,
            fill: false,
        }));

        new Chart(trendCtx, {
            type: 'line',
            data: { labels, datasets },
            options: { responsive: true, plugins: { legend: { position: 'top' } }, scales: { y: { beginAtZero: true } } },
        });
    }
})();

// ── Dashboard: Malnutrition Rate by Barangay ────────────────────
(function () {
    const data = window.__dashboardData;
    const ctx  = document.getElementById('malnutritionRateChart');
    if (!ctx || !data || !data.statusData.length) return;

    const barangays = [...new Set(data.statusData.map(r => r.barangay))];
    const rates = barangays.map(b => {
        const rows = data.statusData.filter(r => r.barangay === b);
        const total = rows.reduce((s, r) => s + parseInt(r.count), 0);
        const malnut = rows.filter(r => r.nutritional_status === 'SUW' || r.nutritional_status === 'UW')
                           .reduce((s, r) => s + parseInt(r.count), 0);
        return total > 0 ? Math.round(malnut / total * 1000) / 10 : 0;
    });

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: barangays,
            datasets: [{
                label: 'Malnutrition Rate (%)',
                data: rates,
                backgroundColor: rates.map(r => r >= 30 ? '#dc2626cc' : r >= 10 ? '#f59e0bcc' : '#16a34acc'),
                borderRadius: 4,
            }],
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, max: 100, ticks: { callback: v => v + '%' } } },
        },
    });
})();

// ── Beneficiary Growth Chart ──────────────────────────────────
(function () {
    const raw = window.__growthData;
    if (!raw || !raw.length) return;

    const ctx = document.getElementById('growthChart');
    if (!ctx) return;

    const sex = (window.__beneficiarySex || '').toLowerCase();

    // WHO median weight for age (months 0-59), male and female
    const whoMedianWeight = {
        male:   [3.3,4.5,5.6,6.4,7.0,7.5,7.9,8.3,8.6,8.9,9.2,9.4,9.6,9.9,10.1,10.3,10.5,10.7,10.9,11.1,11.3,11.5,11.8,12.0,12.2,12.4,12.5,12.7,12.9,13.1,13.3,13.5,13.7,13.8,14.0,14.2,14.3,14.5,14.7,14.9,15.1,15.2,15.4,15.6,15.7,15.9,16.1,16.3,16.4,16.6,16.8,17.0,17.2,17.3,17.5,17.7,17.9,18.1,18.3,18.6],
        female: [3.2,4.2,5.1,5.8,6.4,6.9,7.3,7.6,7.9,8.2,8.5,8.7,8.9,9.2,9.4,9.6,9.8,10.0,10.2,10.4,10.6,10.9,11.1,11.3,11.5,11.7,11.9,12.1,12.3,12.5,12.7,12.9,13.1,13.3,13.5,13.7,13.9,14.1,14.3,14.5,14.7,14.8,15.0,15.2,15.4,15.6,15.8,16.0,16.2,16.4,16.6,16.8,17.0,17.1,17.3,17.5,17.7,17.9,18.1,18.4],
    };
    // WHO -2SD weight (UW threshold), approximate
    const whoMinus2SD = {
        male:   [2.5,3.4,4.3,5.0,5.6,6.1,6.4,6.7,7.0,7.2,7.5,7.7,7.8,8.1,8.3,8.4,8.6,8.8,9.0,9.2,9.4,9.6,9.8,10.0,10.2,10.4,10.5,10.7,10.9,11.1,11.3,11.5,11.6,11.8,12.0,12.1,12.3,12.5,12.6,12.8,13.0,13.1,13.3,13.5,13.6,13.8,14.0,14.1,14.3,14.5,14.6,14.8,15.0,15.1,15.3,15.5,15.7,15.8,16.0,16.2],
        female: [2.4,3.2,4.0,4.6,5.1,5.5,5.9,6.2,6.5,6.7,6.9,7.2,7.4,7.6,7.8,7.9,8.1,8.3,8.5,8.7,8.9,9.1,9.3,9.5,9.7,9.9,10.1,10.3,10.5,10.7,10.8,11.0,11.2,11.4,11.6,11.7,11.9,12.1,12.3,12.5,12.6,12.8,13.0,13.2,13.3,13.5,13.7,13.9,14.0,14.2,14.4,14.6,14.7,14.9,15.1,15.3,15.5,15.6,15.8,16.0],
    };
    // WHO -3SD (SUW threshold)
    const whoMinus3SD = {
        male:   [2.1,2.9,3.8,4.4,4.9,5.3,5.7,6.0,6.2,6.4,6.6,6.8,7.0,7.2,7.4,7.5,7.7,7.9,8.1,8.2,8.4,8.6,8.8,9.0,9.2,9.3,9.5,9.7,9.8,10.0,10.2,10.3,10.5,10.7,10.8,11.0,11.1,11.3,11.4,11.6,11.8,11.9,12.1,12.2,12.4,12.5,12.7,12.8,13.0,13.1,13.3,13.4,13.6,13.7,13.9,14.0,14.2,14.3,14.5,14.7],
        female: [2.0,2.7,3.4,3.9,4.4,4.8,5.1,5.4,5.6,5.8,6.0,6.2,6.4,6.6,6.8,6.9,7.1,7.3,7.4,7.6,7.8,8.0,8.2,8.3,8.5,8.7,8.9,9.0,9.2,9.4,9.6,9.7,9.9,10.1,10.2,10.4,10.6,10.7,10.9,11.1,11.2,11.4,11.5,11.7,11.9,12.0,12.2,12.3,12.5,12.7,12.8,13.0,13.1,13.3,13.5,13.6,13.8,13.9,14.1,14.3],
    };

    const sexKey = sex === 'female' ? 'female' : 'male';
    const labels  = raw.map(d => d.date);
    const ages    = raw.map(d => Math.min(d.ageMonths, 59));
    const weights = raw.map(d => d.weight);
    const heights = raw.map(d => d.height);

    const medians  = ages.map(a => whoMedianWeight[sexKey][a] ?? null);
    const minus2sd = ages.map(a => whoMinus2SD[sexKey][a] ?? null);
    const minus3sd = ages.map(a => whoMinus3SD[sexKey][a] ?? null);

    function buildWeightDatasets() {
        const ds = [{
            label: 'Weight (kg)',
            data: weights,
            borderColor: '#2563eb',
            backgroundColor: '#2563eb33',
            tension: 0.3,
            borderWidth: 2,
            pointRadius: 4,
            yAxisID: 'y',
            order: 1,
        }, {
            label: 'WHO Median',
            data: medians,
            borderColor: '#3b82f6',
            borderDash: [5, 3],
            borderWidth: 1,
            pointRadius: 0,
            tension: 0.3,
            fill: false,
            yAxisID: 'y',
            order: 3,
        }, {
            label: 'UW threshold (−2 SD)',
            data: minus2sd,
            borderColor: '#f97316',
            borderDash: [4, 4],
            borderWidth: 1,
            pointRadius: 0,
            tension: 0.3,
            fill: false,
            yAxisID: 'y',
            order: 3,
        }, {
            label: 'SUW threshold (−3 SD)',
            data: minus3sd,
            borderColor: '#ef4444',
            borderDash: [3, 5],
            borderWidth: 1,
            pointRadius: 0,
            tension: 0.3,
            fill: false,
            yAxisID: 'y',
            order: 3,
        }];
        return ds;
    }

    function buildHeightDatasets() {
        if (!heights.some(h => h !== null)) return null;
        return [{
            label: 'Height (cm)',
            data: heights,
            borderColor: '#16a34a',
            backgroundColor: '#16a34a33',
            tension: 0.3,
            borderWidth: 2,
            pointRadius: 4,
            yAxisID: 'y',
            order: 1,
        }];
    }

    const chart = new Chart(ctx, {
        type: 'line',
        data: { labels, datasets: buildWeightDatasets() },
        options: {
            responsive: true,
            interaction: { mode: 'index', intersect: false },
            plugins: { legend: { position: 'top' } },
            scales: {
                y: { type: 'linear', display: true, position: 'left', title: { display: true, text: 'Weight (kg)' } },
            },
        },
    });

    const btnW = document.getElementById('chartToggleWeight');
    const btnH = document.getElementById('chartToggleHeight');
    if (btnW) btnW.addEventListener('click', function() {
        chart.data.datasets = buildWeightDatasets();
        chart.options.scales.y.title.text = 'Weight (kg)';
        chart.update();
        btnW.classList.add('active'); btnH.classList.remove('active');
    });
    if (btnH) btnH.addEventListener('click', function() {
        const hds = buildHeightDatasets();
        if (!hds) return;
        chart.data.datasets = hds;
        chart.options.scales.y.title.text = 'Height (cm)';
        chart.update();
        btnH.classList.add('active'); btnW.classList.remove('active');
    });
})();
