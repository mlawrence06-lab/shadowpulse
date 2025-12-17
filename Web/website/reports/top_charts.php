<?php
// top_charts.php
$pageTitle = "Top Charts";
$pageSubtitle = "Top Ranked Content & Members";
$activePage = 'reports';
include __DIR__ . '/../header.php';
?>

<!-- SyncFusion Dependencies -->
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
        padding: 20px;
        border-radius: 8px;
        width: 100%;
        box-sizing: border-box;
    }

    /* Dashboard Grid Layout (2x2) */
    .dashboard-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        /* Two Columns */
        gap: 20px;
        width: 100%;
        max-width: 1400px;
        /* Constrain max width */
        margin: 0 auto;
    }

    /* Card Wrapper for each Grid */
    .chart-card {
        background-color: #252525;
        border: 1px solid #333;
        border-radius: 6px;
        padding: 10px;
        display: flex;
        flex-direction: column;
    }

    .card-title {
        font-size: 1.1em;
        font-weight: bold;
        margin-bottom: 10px;
        color: #ddd;
        text-align: center;
        border-bottom: 1px solid #444;
        padding-bottom: 5px;
    }

    /* Link Styling */
    .chart-link {
        color: #4ade80;
        text-decoration: none;
    }

    .chart-link:hover {
        text-decoration: underline;
    }

    /* Responsive: Stack on mobile */
    @media (max-width: 1000px) {
        .dashboard-grid {
            grid-template-columns: 1fr;
        }
    }

    /* OVERRIDE GLOBAL LAYOUT FOR THIS PAGE TO ALLOW FULL WIDTH */
    .site-main {
        display: block !important;
        width: 100% !important;
    }

    .content {
        width: 95% !important;
        margin: 0 auto;
    }
</style>

<section class="content">
    <div class="content-header" style="text-align: center; margin-bottom: 20px;">
        <div class="content-title">Top Charts</div>
        <div class="content-subtitle">Global Rankings</div>
    </div>

    <div class="content-body">
        <div class="dashboard-grid">

            <!-- 1. Top Profiles -->
            <div class="chart-card">
                <div class="card-title">Top Ranked Profiles (Score)</div>
                <div id="grid-members"></div>
            </div>

            <!-- 2. Top Boards -->
            <div class="chart-card">
                <div class="card-title">Top Ranked Boards</div>
                <div id="grid-boards"></div>
            </div>

            <!-- 3. Top Topics -->
            <div class="chart-card">
                <div class="card-title">Top Ranked Topics</div>
                <div id="grid-topics"></div>
            </div>

            <!-- 4. Top Posts -->
            <div class="chart-card">
                <div class="card-title">Top Ranked Posts</div>
                <div id="grid-posts"></div>
            </div>

        </div>
    </div>
</section>

<script>
    ej.base.registerLicense('Ngo9BigBOggjHTQxAR8/V1JFaF5cXGRCf1FpRmJGdld5fUVHYVZUTXxaS00DNHVRdkdmWH1fc3RWRmlfWEF1WEtWYEg=');

    const chartConfigs = {
        'members': {
            header: 'Member',
            valHeader: 'Score',
            valField: 'score',
            format: 'N2',
            template: (props) => `<a href="https://bitcointalk.org/index.php?action=profile;u=${props.id}" target="_blank" class="chart-link">${props.label}</a>`
        },
        'boards': {
            header: 'Board',
            valHeader: 'Score',
            valField: 'score',
            format: 'N2',
            template: (props) => `<a href="https://bitcointalk.org/index.php?board=${props.id}.0" target="_blank" class="chart-link">${props.label}</a>`
        },
        'topics': {
            header: 'Topic',
            valHeader: 'Score',
            valField: 'score',
            format: 'N2',
            template: (props) => `<a href="https://bitcointalk.org/index.php?topic=${props.id}.0" target="_blank" class="chart-link">${props.label}</a>`
        },
        'posts': {
            header: 'Post',
            valHeader: 'Score',
            valField: 'score',
            format: 'N2',
            template: (props) => {
                // Link format: index.php?topic=TOPIC_ID.msgPOST_ID#msgPOST_ID
                // Label format: Post ID (Member Name)
                let displayLabel = props.author_name
                    ? `Post ${props.id} (${props.author_name})`
                    : `Post ${props.id}`;

                if (props.topic_id && props.topic_id > 0) {
                    return `<a href="https://bitcointalk.org/index.php?topic=${props.topic_id}.msg${props.id}#msg${props.id}" target="_blank" class="chart-link">${displayLabel}</a>`;
                }
                return `<a href="https://bitcointalk.org/index.php?msg=${props.id}" target="_blank" class="chart-link">${displayLabel}</a>`;
            }
        }
    };

    function loadGrid(targetId, action) {
        fetch(`../../api/v1/top_lists.php?action=${action}&limit=50`)
            .then(r => r.json())
            .then(res => {
                if (res.ok) {
                    let data = res.data.map((item, index) => {
                        item.Rank = index + 1;
                        return item;
                    });

                    const config = chartConfigs[action];

                    let grid = new ej.grids.Grid({
                        dataSource: data,
                        allowPaging: true,
                        pageSettings: { pageSize: 10 },
                        columns: [
                            { field: 'Rank', headerText: '#', width: 50, textAlign: 'Center' },
                            {
                                field: 'label',
                                headerText: config.header,
                                width: 250, // Increased for names
                                template: config.template
                            },
                            {
                                field: config.valField,
                                headerText: config.valHeader,
                                width: 100,
                                textAlign: 'Center',
                                format: config.format
                            }
                        ]
                    });

                    grid.appendTo(targetId);

                } else {
                    document.getElementById(targetId.substring(1)).innerHTML = "Error: " + res.error;
                }
            })
            .catch(e => {
                console.error(e);
                document.getElementById(targetId.substring(1)).innerHTML = "Failed to load.";
            });
    }

    document.addEventListener('DOMContentLoaded', () => {
        loadGrid('#grid-members', 'members');
        loadGrid('#grid-boards', 'boards');
        loadGrid('#grid-topics', 'topics');
        loadGrid('#grid-posts', 'posts');
    });
</script>

<div style="font-size: 10px; color: #666; text-align: center; margin-top: 20px;">Top Charts Report v1.5</div>
<?php include __DIR__ . '/../footer.php'; ?>