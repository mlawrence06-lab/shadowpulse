<?php
// ranking.php
// Ranking Reports using SyncFusion Grid and Chart (Synced with Top Ranked Profiles)

require_once __DIR__ . '/../../api/v1/cors.php';

$pageTitle = "Ranking Reports";
$pageSubtitle = "Top Ranked Profiles"; // Updated subtitle
$activePage = 'reports';
include __DIR__ . '/../header.php';
?>

<!-- SyncFusion Dependencies (CDN) -->
<link href="https://cdn.syncfusion.com/ej2/31.2.16/material-dark.css" rel="stylesheet">
<script src="https://cdn.syncfusion.com/ej2/31.2.16/dist/ej2.min.js"></script>

<style>
    /* Dark Theme Override */
    body {
        background-color: #121212;
        color: #ffffff;
    }

    .content-body {
        background-color: #1e1e1e;
        padding: 10px;
        border-radius: 8px;
        text-align: center;
    }

    .grid-container {
        margin-bottom: 20px;
        width: fit-content;
        margin-left: auto;
        margin-right: auto;
    }

    .sort-hint {
        text-align: center;
        font-size: 0.9em;
        color: #aaa;
        margin-bottom: 15px;
        width: 100%;
    }

    .e-input-group .e-input,
    .e-input-group.e-control-wrapper .e-input {
        color: #fff !important;
    }

    .site-main {
        display: flex !important;
        flex-direction: column !important;
        align-items: center !important;
        grid-template-columns: none !important;
        width: 100% !important;
    }

    .content {
        display: flex !important;
        flex-direction: column !important;
        align-items: center !important;
        width: fit-content;
        max-width: 95%;
        margin: 0 auto !important;
    }

    .content-header,
    .content-title,
    .content-subtitle {
        text-align: center !important;
        width: 100%;
    }

    .content-body {
        width: 100%;
    }
</style>

<section class="content">
    <div class="content-header">
        <div class="content-title">Ranking Reports</div>
        <div class="content-subtitle">Extension Usage Ranking</div>
    </div>

    <div class="content-body">
        <div class="sort-hint">
            <i class="e-icons e-info"></i> Ranked by Page Views by default. Click headers to sort.
        </div>
        <div id="container-grid" class="grid-container"></div>
    </div>
</section>

<div style="text-align: center; font-size: 0.8em; color: #666; margin-top: 20px; margin-bottom: 20px;">Ranking Report
    v18</div>

<script>
    ej.base.registerLicense('Ngo9BigBOggjHTQxAR8/V1JFaF5cXGRCf1FpRmJGdld5fUVHYVZUTXxaS00DNHVRdkdmWH1fc3RWRmlfWEF1WEtWYEg=');

    let grid;

    function initComponents() {
        grid = new ej.grids.Grid({
            dataSource: [],
            allowPaging: true,
            pageSettings: { pageSize: 20, pageSizes: [20, 50, 100] },
            allowSorting: true,
            toolbar: ['Search'],
            width: '100%',
            allowTextWrap: false,
            sortSettings: { columns: [{ field: 'page_views', direction: 'Descending' }] },

            // STRICT DESCENDING SORT LOGIC
            actionBegin: function (args) {
                if (args.requestType === 'sorting') {
                    // If the user tries to sort Ascending, we cancel and force Descending.
                    if (args.direction === 'Ascending') {
                        args.cancel = true;
                        this.sortColumn(args.columnName, 'Descending');
                    }
                }
            },

            // Dynamic Rank Numbering via DOM manipulation (Failsafe)
            dataBound: function (args) {
                // Get all rendered rows
                var rows = this.getRows();
                var page = this.pageSettings.currentPage || 1;
                var size = this.pageSettings.pageSize || 20;
                var start = (page - 1) * size;

                for (var i = 0; i < rows.length; i++) {
                    var row = rows[i];
                    // Rank is the first column (index 0)
                    var cell = row.querySelector('.e-rowcell');
                    if (cell) {
                        cell.innerText = (start + i + 1).toString();
                    }
                }
            },

            columns: [
                { field: 'Rank', headerText: 'Rank', width: 70, textAlign: 'Center', allowSorting: false },
                {
                    field: 'Username',
                    headerText: 'Member',
                    width: 200,
                    textAlign: 'Left',
                    allowSorting: false,
                    // Removed BTT Link as requested (Extension Member ID != Forum ID)
                    template: (props) => `<span style="color: #4ade80; font-weight: 500;">${props.Username}</span>`
                },
                { field: 'page_views', headerText: 'Page Views', width: 100, textAlign: 'Center', format: 'N0' },
                { field: 'topic_votes', headerText: 'Topic Votes', width: 100, textAlign: 'Center', format: 'N0' },
                { field: 'post_votes', headerText: 'Post Votes', width: 100, textAlign: 'Center', format: 'N0' },
                { field: 'total_votes', headerText: 'Total Votes', width: 120, textAlign: 'Center', format: 'N0' },
                { field: 'searches', headerText: 'Searches', width: 100, textAlign: 'Center', format: 'N0' }
            ]
        });

        grid.appendTo('#container-grid');
    }

    function loadData() {
        // Cache Busting via timestamp
        const apiUrl = `../../api/v1/ranking_reports.php?limit=100&t=${Date.now()}`;

        fetch(apiUrl)
            .then(res => res.json())
            .then(res => {
                if (res.data) {
                    // Force Default Sort: Page Views Descending
                    res.data.sort((a, b) => Number(b.page_views) - Number(a.page_views));

                    // No need to assign static Rank property anymore; queryCellInfo handles visual rank.

                    grid.dataSource = res.data;
                } else if (res.error) {
                    console.error("API Error Payload:", res.error);
                }
            })
            .catch(err => {
                console.error("Fetch Error:", err);
            });
    }

    document.addEventListener('DOMContentLoaded', () => {
        initComponents();
        loadData();
    });
</script>

<?php include __DIR__ . '/../footer.php'; ?>