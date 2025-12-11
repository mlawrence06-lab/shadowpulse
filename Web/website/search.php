<?php
require __DIR__ . '/cors.php';

// search.php
// Endpoint: https://vod.fan/shadowpulse/search.php?q=<term>

declare(strict_types=1);

// Basic settings
$apiBase = 'https://api.ninjastic.space/shadowpulse';

// Get search term from query string
$term = isset($_GET['q']) ? trim((string)$_GET['q']) : '';

$results = [];
$errorMessage = '';

if ($term !== '') {
    $url = $apiBase . '?searchTerm=' . rawurlencode($term);

    try {
        // Prefer cURL if available
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

            $body   = curl_exec($ch);
            $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $errNo  = curl_errno($ch);
            $errStr = curl_error($ch);
            curl_close($ch);

            if ($body === false || $errNo !== 0) {
                $errorMessage = 'The search backend is currently unavailable. Please try again in a moment.';
            } elseif ($status !== 200) {
                $errorMessage = 'The search backend returned an error (HTTP ' . $status . ').';
            } else {
                $data = json_decode($body, true);
                if (!is_array($data)) {
                    $errorMessage = 'Unexpected response format from the search backend.';
                } else {
                    $results = $data;
                }
            }
        } else {
            // Fallback to file_get_contents if cURL is not available
            $context = stream_context_create([
                'http' => [
                    'method'  => 'GET',
                    'timeout' => 10,
                ]
            ]);
            $body = @file_get_contents($url, false, $context);

            if ($body === false) {
                $errorMessage = 'The search backend is currently unavailable. Please try again in a moment.';
            } else {
                $data = json_decode($body, true);
                if (!is_array($data)) {
                    $errorMessage = 'Unexpected response format from the search backend.';
                } else {
                    $results = $data;
                }
            }
        }
    } catch (Throwable $e) {
        $errorMessage = 'An unexpected error occurred while contacting the search backend.';
    }
}

// Include site header
include __DIR__ . '/header.php';
?>
<section class="shadowpulse-section">
    <div class="container">
        <h1 class="page-title">ShadowPulse Search</h1>

        <form class="search-form" method="get" action="search.php">
            <label for="q" class="search-label">Search Bitcointalk</label>
            <div class="search-input-row">
                <input
                    type="text"
                    id="q"
                    name="q"
                    class="search-input"
                    placeholder="Search topics, posts, or usernames…"
                    value="<?php echo htmlspecialchars($term, ENT_QUOTES, 'UTF-8'); ?>"
                >
                <button type="submit" class="search-button">Search</button>
            </div>
        </form>

        <?php if ($term === ''): ?>
            <p class="search-hint">
                Enter a term above to search ShadowPulse-indexed Bitcointalk posts.
            </p>
        <?php endif; ?>

        <?php if ($errorMessage !== ''): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php endif; ?>

        <?php if ($term !== '' && empty($results) && $errorMessage === ''): ?>
            <p class="search-empty">
                No results found for
                <strong><?php echo htmlspecialchars($term, ENT_QUOTES, 'UTF-8'); ?></strong>.
            </p>
        <?php endif; ?>

        <?php if (!empty($results)): ?>
            <ul class="result-list">
                <?php foreach ($results as $row): ?>
                    <?php
                    // Expected structure from Ninjastic ShadowPulse:
                    // {
                    //   "topic_id": 123,
                    //   "post_id": 456,
                    //   "title": "Post title",
                    //   "content": "Post body ...",
                    //   "board_name": "Board",
                    //   "author": "User",
                    //   "date": "YYYY-MM-DD HH:MM:SS"
                    // }

                    $title   = isset($row['title']) ? (string)$row['title'] : 'Untitled';
                    $topicId = isset($row['topic_id']) ? (int)$row['topic_id'] : 0;
                    $postId  = isset($row['post_id']) ? (int)$row['post_id'] : 0;

                    if ($topicId > 0 && $postId > 0) {
                        $url = 'https://bitcointalk.org/index.php?topic=' . $topicId . '.msg' . $postId . '#msg' . $postId;
                    } else {
                        $url = '#';
                    }

                    $rawSnippet = isset($row['content']) ? (string)$row['content'] : '';
                    $snippet = '';

                    if ($rawSnippet !== '') {
                        $snippet = strip_tags($rawSnippet);

                        if (function_exists('mb_strlen') && function_exists('mb_substr')) {
                            if (mb_strlen($snippet, 'UTF-8') > 260) {
                                $snippet = mb_substr($snippet, 0, 260, 'UTF-8') . '…';
                            }
                        } else {
                            if (strlen($snippet) > 260) {
                                $snippet = substr($snippet, 0, 260) . '…';
                            }
                        }
                    }

                    $boardName = isset($row['board_name']) ? (string)$row['board_name'] : '';
                    $author    = isset($row['author']) ? (string)$row['author'] : '';
                    $date      = isset($row['date']) ? (string)$row['date'] : '';
                    ?>
                    <li class="result-item">
                        <a class="result-title"
                           href="<?php echo htmlspecialchars($url, ENT_QUOTES, 'UTF-8'); ?>"
                           target="_blank"
                           rel="noopener">
                            <?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?>
                        </a>

                        <?php if ($snippet !== ''): ?>
                            <p class="result-snippet">
                                <?php echo htmlspecialchars($snippet, ENT_QUOTES, 'UTF-8'); ?>
                            </p>
                        <?php endif; ?>

                        <div class="result-meta">
                            <?php if ($author !== ''): ?>
                                <span class="result-author">
                                    by <?php echo htmlspecialchars($author, ENT_QUOTES, 'UTF-8'); ?>
                                </span>
                            <?php endif; ?>
                            <?php if ($boardName !== ''): ?>
                                <span class="result-board">
                                    in <?php echo htmlspecialchars($boardName, ENT_QUOTES, 'UTF-8'); ?>
                                </span>
                            <?php endif; ?>
                            <?php if ($date !== ''): ?>
                                <span class="result-date">
                                    on <?php echo htmlspecialchars($date, ENT_QUOTES, 'UTF-8'); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</section>

<aside class="side-panel">
    <div class="side-panel-title">Search tips</div>
    <div class="side-panel-highlight">
        Try usernames, topic IDs, or specific phrases from posts to narrow down results.
    </div>
    <div class="side-panel-note">
        Results are powered by the Ninjastic ShadowPulse API and reflect the current public Bitcointalk index.
    </div>
</aside>

<?php include __DIR__ . '/footer.php'; ?>
