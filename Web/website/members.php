<?php
require_once __DIR__ . '/../api/v1/cors.php';

// members.php
// ShadowPulse Members Overview

$pageTitle = "Members";
$pageSubtitle = "Bitcointalk member insights & activity";

include __DIR__ . '/header.php';
?>

<section class="content">
    <div class="content-header">
        <div class="content-title">Members</div>
        <div class="content-subtitle">
            Explore Bitcointalk members tracked by ShadowPulse.
        </div>
    </div>

    <div class="content-body">
        <!-- Simple member search/filter bar -->
        <form class="search-form" method="get" action="">
            <input type="text" name="q" placeholder="Search members by username, ID, or keyword…"
                value="<?php echo isset($_GET['q']) ? htmlspecialchars($_GET['q'], ENT_QUOTES, 'UTF-8') : ''; ?>"
                autocomplete="off">
            <button type="submit">Filter</button>
        </form>

        <p class="hint">
            This page is styled using <code>shadowpulse.css</code>. Replace the demo list
            below with your live member data (from your DB or API).
        </p>

        <?php
        // Demo data – replace with real query later.
        $fakeMembers = [
            [
                'username' => 'Vod',
                'user_id' => 12345,
                'rank' => 'Legendary',
                'activity' => 2450,
                'merit' => 1600,
                'last_seen' => '2025-11-25 13:42:00',
                'boards' => 'Economics, Meta',
            ],
            [
                'username' => 'Satoshi',
                'user_id' => 3,
                'rank' => 'Legendary',
                'activity' => 500,
                'merit' => 1000,
                'last_seen' => '2010-12-13 19:00:00',
                'boards' => 'Technical Discussion, Development & Technical',
            ],
            [
                'username' => 'ExampleUser',
                'user_id' => 987654,
                'rank' => 'Sr. Member',
                'activity' => 450,
                'merit' => 210,
                'last_seen' => '2025-11-28 09:15:00',
                'boards' => 'Altcoins, Gambling',
            ],
        ];

        // Basic filtering if q is present (case-insensitive contains match)
        $q = isset($_GET['q']) ? trim((string) $_GET['q']) : '';
        if ($q !== '') {
            $lcQ = mb_strtolower($q);
            $fakeMembers = array_filter($fakeMembers, function ($m) use ($lcQ) {
                $haystack = mb_strtolower(
                    $m['username'] . ' ' .
                    $m['user_id'] . ' ' .
                    $m['rank'] . ' ' .
                    $m['boards']
                );
                return strpos($haystack, $lcQ) !== false;
            });
        }
        ?>

        <?php if ($q !== ''): ?>
            <p class="hint">
                Filtered by:
                <strong><?php echo htmlspecialchars($q, ENT_QUOTES, 'UTF-8'); ?></strong>
            </p>
        <?php endif; ?>

        <?php if (empty($fakeMembers)): ?>
            <p class="hint">No members match your filter yet.</p>
        <?php else: ?>
            <ul class="result-list">
                <?php foreach ($fakeMembers as $member): ?>
                    <li class="result-item">
                        <div class="result-title">
                            <a href="https://bitcointalk.org/index.php?action=profile;u=<?php
                            echo (int) $member['user_id']; ?>" target="_blank" rel="noopener noreferrer">
                                <?php echo htmlspecialchars($member['username'], ENT_QUOTES, 'UTF-8'); ?>
                            </a>
                        </div>

                        <div class="result-meta">
                            <?php
                            $metaBits = [];
                            if (!empty($member['rank'])) {
                                $metaBits[] = $member['rank'];
                            }
                            $metaBits[] = 'UID ' . (int) $member['user_id'];
                            $metaBits[] = 'Activity ' . (int) $member['activity'];
                            $metaBits[] = 'Merit ' . (int) $member['merit'];
                            echo htmlspecialchars(implode(' • ', $metaBits), ENT_QUOTES, 'UTF-8');
                            ?>
                        </div>

                        <?php if (!empty($member['boards']) || !empty($member['last_seen'])): ?>
                            <div class="result-snippet">
                                <?php if (!empty($member['boards'])): ?>
                                    Boards: <?php echo htmlspecialchars($member['boards'], ENT_QUOTES, 'UTF-8'); ?>
                                <?php endif; ?>
                                <?php if (!empty($member['boards']) && !empty($member['last_seen'])): ?>
                                    &nbsp;•&nbsp;
                                <?php endif; ?>
                                <?php if (!empty($member['last_seen'])): ?>
                                    Last seen: <?php echo htmlspecialchars($member['last_seen'], ENT_QUOTES, 'UTF-8'); ?>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</section>

<aside class="side-panel">
    <div class="side-panel-title">Member metrics</div>
    <div class="side-panel-highlight">
        Hook this panel up to live stats: total tracked members, average activity,
        top boards, or recent joins.
    </div>
    <div class="side-panel-note">
        This is just placeholder copy. Replace with real ShadowPulse member telemetry when ready.
    </div>
</aside>

<?php include __DIR__ . '/footer.php'; ?>