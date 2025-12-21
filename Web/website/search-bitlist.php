<?php
declare(strict_types=1);
require_once __DIR__ . '/../api/v1/cors.php';

// search-bitlist.php
// Endpoint: https://vod.fan/shadowpulse/website/search-bitlist.php?q=<term>

// Basic settings
$apiBase = 'https://api.ninjastic.space/shadowpulse';

// Get search term from query string
$term = isset($_GET['q']) ? trim((string) $_GET['q']) : '';

$results = [];
$errorMessage = '';

if ($term !== '') {
    // API has a hard limit of 20 results and ignores pagination.
    $url = $apiBase . '?searchTerm=' . rawurlencode($term);

    try {
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15); // Increased timeout slightly
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

            $body = curl_exec($ch);
            $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $errNo = curl_errno($ch);
            if ($body === false || $errNo !== 0) {
                $errorMessage = 'The search backend is currently unavailable.';
            } elseif ($status !== 200) {
                $errorMessage = 'The search backend returned an error (HTTP ' . $status . ').';
            } else {
                $data = json_decode($body, true);
                if (!is_array($data)) {
                    $errorMessage = 'Unexpected response format.';
                } else {
                    if (isset($data['data'])) {
                        if (is_array($data['data']) && isset($data['data']['posts'])) {
                            $results = $data['data']['posts'];
                        } elseif (is_array($data['data'])) {
                            $results = $data['data'];
                        }
                    } elseif (isset($data['posts'])) {
                        $results = $data['posts'];
                    } else {
                        $results = $data;
                    }
                }
            }
        } else {
            $context = stream_context_create([
                'http' => [
                    'method' => 'GET',
                    'timeout' => 15,
                ]
            ]);
            $body = @file_get_contents($url, false, $context);
            if ($body === false) {
                $errorMessage = 'The search backend is currently unavailable.';
            } else {
                $data = json_decode($body, true);
                if (!is_array($data)) {
                    $errorMessage = 'Unexpected response format.';
                } else {
                    if (isset($data['data'])) {
                        if (is_array($data['data']) && isset($data['data']['posts'])) {
                            $results = $data['data']['posts'];
                        } elseif (is_array($data['data'])) {
                            $results = $data['data'];
                        }
                    } elseif (isset($data['posts'])) {
                        $results = $data['posts'];
                    } else {
                        $results = $data;
                    }
                }
            }
        }
    } catch (Throwable $e) {
        $errorMessage = 'An unexpected error occurred.';
    }
}

$pageTitle = "Bitlist Search";
$pageSubtitle = "Search Bitcointalk via Bitlist";

include __DIR__ . '/header.php';
?>

<style>
    /* Mimic Reports Dark Theme */
    .content-body {
        background-color: #1e1e1e;
        padding: 20px;
        border-radius: 8px;
        color: #ddd;
    }

    .search-input-row {
        display: flex;
        gap: 10px;
        margin-bottom: 20px;
        justify-content: center;
    }

    .search-input {
        padding: 10px;
        border-radius: 4px;
        border: 1px solid #444;
        background: #2a2a2a;
        color: #fff;
        width: 100%;
        max-width: 400px;
    }

    .search-button {
        padding: 10px 20px;
        border-radius: 4px;
        border: none;
        background: #7cdcff;
        color: #000;
        font-weight: bold;
        cursor: pointer;
    }

    .search-button:hover {
        background: #5abcdf;
    }

    .result-list {
        list-style: none;
        padding: 0;
    }

    .result-item {
        padding: 15px;
        border-bottom: 1px solid #333;
        text-align: left;
        display: flex;
        gap: 15px;
    }

    .result-number {
        font-size: 1.2em;
        font-weight: bold;
        color: #666;
        min-width: 40px;
        text-align: right;
    }

    .result-content {
        flex: 1;
    }

    .result-title {
        font-size: 1.1em;
        font-weight: bold;
        color: #4ade80;
        text-decoration: none;
        display: block;
        margin-bottom: 5px;
    }

    .result-title:hover {
        text-decoration: underline;
    }

    .result-snippet {
        font-size: 0.9em;
        color: #bbb;
        margin: 5px 0;
    }

    .result-meta {
        font-size: 0.8em;
        color: #666;
    }

    .search-hint,
    .search-empty {
        text-align: center;
        color: #888;
    }

    .limit-warning {
        font-size: 0.8em;
        color: #888;
        text-align: right;
        margin-bottom: 10px;
    }
</style>

<!-- Full-width wrapper to break grid columns and align center -->
<div style="grid-column: 1 / -1; width: 100%; display: flex; flex-direction: column; align-items: center;">

    <!-- Centered Content Section -->
    <section class="content" style="width: 100%;">
        <div class="content-header">
            <div class="content-title">Bitlist Search</div>
            <div class="content-subtitle">Unified Index</div>
        </div>

        <div class="content-body">
            <form method="get" action="search-bitlist.php">
                <div class="search-input-row">
                    <input type="text" name="q" class="search-input" placeholder="Keyword search on recent posts..."
                        value="<?php echo htmlspecialchars($term, ENT_QUOTES, 'UTF-8'); ?>">
                    <button type="submit" class="search-button">Search</button>
                </div>
            </form>

            <?php if ($term === ''): ?>
                <p class="search-hint">
                    Search the <a href="https://bitlist.co/" target="_blank"
                        style="color: #7cdcff; text-decoration: none;">Bitlist index</a> covering millions of Bitcointalk
                    posts.
                </p>
            <?php endif; ?>

            <?php if ($errorMessage !== ''): ?>
                <div class="alert alert-error" style="color: #ff6b6b; text-align: center;">
                    <?php echo htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8'); ?>
                </div>
            <?php endif; ?>

            <?php if ($term !== '' && empty($results) && $errorMessage === ''): ?>
                <p class="search-empty">No results found for
                    <strong><?php echo htmlspecialchars($term, ENT_QUOTES, 'UTF-8'); ?></strong>.
                </p>
            <?php endif; ?>

            <?php if (!empty($results)): ?>
                <!-- Result Stats -->
                <div class="limit-warning">
                    Found <?php echo count($results); ?> results (API Limit: 20 max)
                </div>

                <ul class="result-list">
                    <?php
                    $globalIndex = 1;
                    foreach ($results as $row):
                        $title = isset($row['title']) ? (string) $row['title'] : 'Untitled';
                        $topicId = isset($row['topic_id']) ? (int) $row['topic_id'] : 0;
                        $postId = isset($row['post_id']) ? (int) $row['post_id'] : 0;
                        $url = ($topicId > 0 && $postId > 0) ? "https://bitcointalk.org/index.php?topic={$topicId}.msg{$postId}#msg{$postId}" : '#';

                        $rawSnippet = isset($row['content']) ? strip_tags((string) $row['content']) : '';
                        $snippet = (strlen($rawSnippet) > 260) ? substr($rawSnippet, 0, 260) . '…' : $rawSnippet;

                        $boardName = isset($row['board_name']) ? (string) $row['board_name'] : '';
                        $author = isset($row['author']) ? (string) $row['author'] : '';
                        $date = isset($row['date']) ? (string) $row['date'] : '';
                        ?>
                        <li class="result-item">
                            <div class="result-number">#<?php echo $globalIndex; ?></div>
                            <div class="result-content">
                                <a class="result-title" href="<?php echo htmlspecialchars($url, ENT_QUOTES, 'UTF-8'); ?>"
                                    target="_blank" rel="noopener">
                                    <?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?>
                                </a>
                                <?php if ($snippet !== ''): ?>
                                    <div class="result-snippet"><?php echo htmlspecialchars($snippet, ENT_QUOTES, 'UTF-8'); ?></div>
                                <?php endif; ?>
                                <div class="result-meta">
                                    <?php if ($author)
                                        echo "by " . htmlspecialchars($author) . " "; ?>
                                    <?php if ($boardName)
                                        echo "in " . htmlspecialchars($boardName) . " "; ?>
                                    <?php if ($date)
                                        echo "on " . htmlspecialchars($date); ?>
                                </div>
                            </div>
                        </li>
                        <?php
                        $globalIndex++;
                    endforeach;
                    ?>
                </ul>
            <?php endif; ?>
        </div>
    </section>

    <div style="text-align: center; font-size: 0.8em; color: #666; margin-top: 20px; margin-bottom: 20px;">Bitlist
        Search v5</div>

</div>

<?php include __DIR__ . '/footer.php'; ?>