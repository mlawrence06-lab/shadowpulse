<?php
$pageTitle = 'Vote Diagnostics';
$activePage = 'reports';
include __DIR__ . '/../header.php';
?>

<!-- SyncFusion Dependencies -->
<link href="https://cdn.syncfusion.com/ej2/31.2.16/material-dark.css" rel="stylesheet">
<script src="https://cdn.syncfusion.com/ej2/31.2.16/dist/ej2.min.js"></script>

<style>
    /* Global Override for Full Centering */
    .site-main {
        display: flex !important;
        flex-direction: column !important;
        align-items: center !important;
        grid-template-columns: none !important;
        width: 100% !important;
    }

    .content-wrapper {
        width: 100%;
        max-width: 1200px;
        margin: 0 auto;
        display: flex;
        flex-direction: column;
        gap: 30px;
        min-height: 400px;
    }

    /* Container Styling */
    .chart-card, .grid-card {
        background-color: #1e1e1e;
        padding: 20px;
        border-radius: 8px;
        width: 100%;
        box-shadow: 0 4px 6px rgba(0,0,0,0.3);
    }

    .section-header {
        text-align: center;
        margin-bottom: 15px;
    }
    .main-header {
        text-align:center; margin-bottom:10px; border-bottom:1px solid #333; padding-bottom:10px;
    }
    .section-title {
        font-size: 1.2rem;
        font-weight: 600;
        color: #fff;
    }
    .section-desc {
        font-size: 0.9rem;
        color: #aaa;
    }

    /* Search Form */
    .search-input-group {
        display: flex;
        gap: 10px;
        justify-content: center;
        margin-top: 20px;
    }
    .search-input-group input {
        padding: 10px;
        border-radius: 4px;
        border: 1px solid #444;
        background: #2d2d2d;
        color: white;
        width: 300px;
    }
    .search-input-group button {
        padding: 10px 20px;
        background: #1E90FF;
        color: white;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-weight: bold;
    }
    .col-2-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
    }
    @media (max-width: 900px) {
        .col-2-grid { grid-template-columns: 1fr; }
    }
</style>

<div class="content-wrapper">
    
    <!-- SEARCH SECTION -->
    <div id="search-section" class="chart-card" style="display:none;">
        <div class="section-header">
            <div class="section-title">Member Diagnostic</div>
            <div class="section-desc">Enter a Member UUID to view their vote distribution</div>
        </div>
        <div class="search-input-group">
            <input type="text" id="uuid-input" placeholder="Enter Member UUID...">
            <button onclick="loadMember()">Analyze</button>
        </div>
    </div>

    <!-- REPORT SECTION -->
    <div id="report-section" style="display:none; flex-direction:column; gap:40px; width:100%;">
        
        <!-- TITLE -->
        <div class="main-header">
             <h2 style="margin:0; color:#1E90FF;" id="member-name-label"></h2>
             <div style="color:#888;">Diagnostic Report</div>
        </div>

        <!-- TOPIC VOTES BLOCK -->
        <div class="section-block">
            <h3 style="margin-left:5px; margin-bottom:10px; color:#ddd; border-left: 4px solid #1E90FF; padding-left:10px;">Topic Votes</h3>
            
            <div class="col-2-grid">
                <!-- CHART -->
                <div class="chart-card">
                    <div class="section-header">
                        <div class="section-desc">Distribution of Effective Levels</div>
                    </div>
                    <div id="topic-chart" style="height: 300px;"></div>
                </div>
                
                <!-- QUEUE -->
                <div class="grid-card">
                    <div class="section-header">
                        <div class="section-desc">Sync Queue (Desired ≠ Effective)</div>
                    </div>
                    <div id="topic-queue"></div>
                </div>
            </div>
        </div>

        <!-- POST VOTES BLOCK -->
        <div class="section-block">
            <h3 style="margin-left:5px; margin-bottom:10px; color:#ddd; border-left: 4px solid #d946ef; padding-left:10px;">Post Votes</h3>
            
            <div class="col-2-grid">
                <!-- CHART -->
                <div class="chart-card">
                    <div class="section-header">
                        <div class="section-desc">Distribution of Effective Levels</div>
                    </div>
                    <div id="post-chart" style="height: 300px;"></div>
                </div>
                
                <!-- QUEUE -->
                <div class="grid-card">
                    <div class="section-header">
                        <div class="section-desc">Sync Queue (Desired ≠ Effective)</div>
                    </div>
                    <div id="post-queue"></div>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
    ej.base.registerLicense('Ngo9BigBOggjHTQxAR8/V1JFaF5cXGRCf1FpRmJGdld5fUVHYVZUTXxaS00DNHVRdkdmWH1fc3RWRmlfWEF1WEtWYEg=');

    document.addEventListener('DOMContentLoaded', function() {
        const urlParams = new URLSearchParams(window.location.search);
        const uuid = urlParams.get('uuid');

        if (uuid) {
            fetchData(uuid);
        } else {
            document.getElementById('search-section').style.display = 'block';
        }
    });

    function loadMember() {
        const uuid = document.getElementById('uuid-input').value.trim();
        if (uuid) {
            window.location.href = '?uuid=' + uuid;
        }
    }

    function fetchData(uuid) {
        fetch('/shadowpulse/api/v1/vote_pyramid.php?uuid=' + uuid)
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    document.getElementById('search-section').style.display = 'none';
                    document.getElementById('report-section').style.display = 'flex';
                    document.getElementById('member-name-label').textContent = data.member_name;
                    
                    // Render Topic Data (Blue Theme)
                    renderChart('#topic-chart', data.topic_pyramid, '#1E90FF');
                    renderGrid('#topic-queue', data.topic_queue);

                    // Render Post Data (Pink/Purple Theme)
                    renderChart('#post-chart', data.post_pyramid, '#d946ef');
                    renderGrid('#post-queue', data.post_queue);
                } else {
                    alert(data.error || 'Error loading report');
                    document.getElementById('search-section').style.display = 'block';
                }
            })
            .catch(err => alert("Network error"));
    }

    function renderChart(containerId, data, color) {
        if (!data || data.length === 0) {
            document.querySelector(containerId).innerHTML = "<div style='text-align:center; padding:50px; color:#444;'>No data</div>";
            return;
        }

        // --- Calculate Limits (Pyramid Rules) ---
        // Rule 1: Max (2,4) allowed = Floor(Count(3) / 2)
        // Rule 2: Max (1,5) allowed = Floor(Count(2+4) / 2)

        let count3 = 0;
        let count24 = 0;

        data.forEach(d => {
            const level = parseInt(d.level);
            if (level === 3) count3 = d.count;
            if (level === 2 || level === 4) count24 += d.count;
        });

        const limit24 = Math.floor(count3 / 2);
        const limit15 = Math.floor(count24 / 2);

        let chart = new ej.charts.Chart({
            primaryXAxis: { valueType: 'Category', title: 'Level', labelStyle: { color: '#aaa' } },
            primaryYAxis: { 
                title: 'Count', 
                labelStyle: { color: '#aaa' },
                stripLines: [
                    { start: limit24, size: 2, sizeType: 'Pixel', color: '#fbbf24', text: 'Max 2,4 (' + limit24 + ')', textStyle: { color: '#fbbf24' }, horizontalAlignment: 'End', verticalAlignment: 'Start' },
                     { start: limit15, size: 2, sizeType: 'Pixel', color: '#f87171', text: 'Max 1,5 (' + limit15 + ')', textStyle: { color: '#f87171' }, horizontalAlignment: 'End', verticalAlignment: 'Start' }
                ]
            },
            series: [{
                type: 'Column',
                dataSource: data,
                xName: 'level',
                yName: 'count',
                marker: { dataLabel: { visible: true, position: 'Top', font: { color: '#fff', fontWeight: 'bold' } } },
                fill: color,
                cornerRadius: { topLeft: 4, topRight: 4 }
            }],
            background: 'transparent',
            theme: 'MaterialDark',
            height: '300px'
        });
        chart.appendTo(containerId);
    }

    function renderGrid(containerId, data) {
        if (!data || data.length === 0) {
            document.querySelector(containerId).innerHTML = "<div style='text-align:center; padding:50px; color:#444; border:1px dashed #333; border-radius:4px;'>Queue Empty (Synced)</div>";
            return;
        }
        let grid = new ej.grids.Grid({
            dataSource: data,
            allowPaging: true,
            pageSettings: { pageSize: 5 },
            width: '100%',
            columns: [
                { field: 'target_id', headerText: 'ID', width: 80, textAlign: 'Center' },
                { field: 'desired_value', headerText: 'Des', width: 60, textAlign: 'Center', template: '<span style="color:#4ade80;">${desired_value}</span>' },
                { field: 'effective_value', headerText: 'Eff', width: 60, textAlign: 'Center', template: '<span style="color:#f87171;">${effective_value}</span>' },
                { field: 'created_at', headerText: 'Date', width: 120, textAlign: 'Right', type: 'datetime', format: 'y/M/d HH:mm' }
            ]
        });
        grid.appendTo(containerId);
    }
</script>

<?php include __DIR__ . '/../footer.php'; ?>