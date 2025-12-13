<?php
// ranking.php
// Ranking Reports using SyncFusion Grid and Chart

require_once __DIR__ . '/../../api/v1/cors.php';

$pageTitle = "Ranking Reports";
$pageSubtitle = "Top Metrics & Stats";
$activePage = 'reports'; // Highlight "Reports" menu

// Use Main Header/Footer
include __DIR__ . '/../header.php';
?>

<!-- SyncFusion Dependencies (CDN) - Upgraded to v31.x (User's Key Version) -->
<link href="https://cdn.syncfusion.com/ej2/31.2.16/material-dark.css" rel="stylesheet">
<script src="https://cdn.syncfusion.com/ej2/31.2.16/dist/ej2.min.js"></script>

<style>
    /* Dark Theme Override for Page Container */
    body {
        background-color: #121212;
        color: #ffffff;
    }

    .content-body {
        background-color: #1e1e1e;
        padding: 10px;
        border-radius: 8px;
        text-align: center;
        overflow-x: hidden;
    }

    .grid-container {
        margin-bottom: 20px;
        /* Robust Centering Strategy */
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

    /* Ensure text is white in inputs (Search Box) */
    .e-input-group .e-input,
    .e-input-group.e-control-wrapper .e-input {
        color: #fff !important;
    }

    /* OVERRIDE GLOBAL LAYOUT FOR THIS PAGE */
    /* The global CSS forces a 2-column grid. We must disable this to center our report. */
    .site-main {
        display: flex !important;
        flex-direction: column !important;
        align-items: center !important;
        grid-template-columns: none !important;
        width: 100% !important;
    }

    /* Main Content Centering - The Flex/Fit Strategy */
    .content {
        display: flex !important;
        flex-direction: column !important;
        align-items: center !important;

        /* Shrink to fit the table width exactly, then center */
        width: fit-content;
        max-width: 95%;
        margin: 0 auto !important;
    }

    .content-header {
        text-align: center !important;
        width: 100%;
        margin-bottom: 20px;
    }

    .content-title,
    .content-subtitle {
        width: 100%;
        text-align: center !important;
    }

    .content-body {
        background-color: #1e1e1e;
        padding: 20px;
        border-radius: 8px;
        /* Fill the fit-content parent */
        width: 100%;
        box-sizing: border-box;

        display: flex;
        flex-direction: column;
        align-items: center;
    }

    .grid-container {
        margin-bottom: 20px;
        width: 100%;
    }

    .sort-hint {
        text-align: center;
        font-size: 0.9em;
        color: #aaa;
        margin-bottom: 15px;
        width: 100%;
    }

    /* Ensure text is white in inputs (Search Box) */
    .e-input-group .e-input,
    .e-input-group.e-control-wrapper .e-input {
        color: #fff !important;
    }
</style>

<section class="content">
    <div class="content-header">
        <div class="content-title">Ranking Reports</div>
        <div class="content-subtitle">Unified Top 100 Rankings</div>
    </div>

    <div class="content-body">
        <div class="sort-hint">
            <i class="e-icons e-info"></i> Click on a column header (Page Views, Votes, Searches) to see the Top 100 for
            that category.
        </div>
        <div id="container-grid" class="grid-container"></div>
    </div>
</section>

<script>
    // Register SyncFusion License - v31.x Key
    ej.base.registerLicense('Ngo9BigBOggjHTQxAR8/V1JFaF5cXGRCf1FpRmJGdld5fUVHYVZUTXxaS00DNHVRdkdmWH1fc3RWRmlfWEF1WEtWYEg=');

    let grid;
    let currentSort = 'page_views'; // Default sort


    function initComponents() {
        grid = new ej.grids.Grid({
            dataSource: [], // Loaded via fetch
            allowPaging: true,
            pageSettings: {                pageSize: 20,
                pageSizes: [20, 50, 100] // Adds "Records per page" dropdown
            },
            allowSorting: true, // We will handle sort manually to reload data
            toolbar: ['Search'],
            width: '100%', // Fil the fit-content body
            allowTextWrap: false,

            columns: [
                { field: 'Rank', headerText: 'Rank', width: 70, textAlign: 'Center', allowSorting: false },
                { field: 'Username', headerText: 'Member', width: 180, textAlign: 'Left', allowSorting: false },
                { field: 'PageViews', headerText: 'Page Views', width: 110, textAlign: 'Center', format: 'N0' },
                { field: 'TopicVotes', headerText: 'Topic Votes', width: 110, textAlign: 'Center', format: 'N0' },
                { field: 'PostVotes', headerText: 'Post Votes', width: 110, textAlign: 'Center', format: 'N0' },
                { field: 'Searches', headerText: 'Searches', width: 110, textAlign: 'Center', format: 'N0' }
            ],

            // Intercept Sort Action
            actionBegin: function (args) {
                if (args.requestType === 'sorting') {
                    args.cancel = true; // Prevent client-side sort

                    let newSortMap = {
                        'PageViews': 'page_views',
                        'TopicVotes': 'topic_votes',
                        'PostVotes': 'post_votes',
                        'Searches': 'searches'
                    };

                    let col = args.columnName;
                    if (newSortMap[col]) {
                        loadData(newSortMap[col]);
                    }
                }
            }
        });

        grid.appendTo('#container-grid');
    }

    function loadData(sortBy) {
        currentSort = sortBy;
        grid.showSpinner();

        const apiUrl = `../../api/v1/ranking_reports.php?sort=${sortBy}&limit=100`;

        fetch(apiUrl)
            .then(res => res.json())
            .then(res => {
                grid.hideSpinner();
                if (res.data) {
                    grid.dataSource = res.data;
                } else if (res.error) {
                    console.error("API Error Payload:", res.error);
                }
            })
            .catch(err => {
                console.error("Fetch Error:", err);
                grid.hideSpinner();
            });
    }

    document.addEventListener('DOMContentLoaded', () => {
        initComponents();
        loadData('page_views'); // Default
    });
</script>

<?php include __DIR__ . '/../footer.php'; ?>