<?php
/**
 * ShadowPulse - cast_vote.php
 * Updated to fix "OUT argument" error by using MySQL session variables.
 */

ini_set('display_errors', 0);
error_reporting(E_ALL);

require __DIR__ . '/cors.php';

try {
    if (!file_exists('db.php')) {
        throw new Exception("db.php not found.");
    }
    require_once 'db.php';
    $pdo = sp_get_pdo();

    $input = json_decode(file_get_contents('php://input'), true);

    $member_id = $input['member_id'] ?? null;
    $category = $input['vote_category'] ?? null;
    $target_id = $input['target_id'] ?? null;
    $desired_value = $input['desired_value'] ?? null;

    if (!$member_id || !$category || !$target_id || !$desired_value) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Missing required fields']);
        exit;
    }

    // 1. Call Stored Procedure using a MySQL Session Variable (@eff)
    // We replace :effective with @eff to avoid the binding error.
    $stmt = $pdo->prepare("CALL shadowpulse_cast_vote(:member, :cat, :target, :desired, @eff)");

    $stmt->bindValue(':member', (int) $member_id, PDO::PARAM_INT);
    $stmt->bindValue(':cat', $category, PDO::PARAM_STR);
    $stmt->bindValue(':target', (int) $target_id, PDO::PARAM_INT);
    $stmt->bindValue(':desired', (int) $desired_value, PDO::PARAM_INT);

    $stmt->execute();

    // Flush result sets to fix 2014 error
    do {
        try {
            $stmt->fetchAll();
        } catch (Exception $e) {
        }
    } while ($stmt->nextRowset());
    $stmt = null; // Force close

    // 2. Retrieve the Output Value
    $row = $pdo->query("SELECT @eff as val")->fetch(PDO::FETCH_ASSOC);
    $effective_value = $row ? (int) $row['val'] : (int) $desired_value;

    // 3. Update Member Stats (Increment the counter)
    $colToUpdate = ($category === 'post') ? 'post_votes' : 'topic_votes';

    $sqlStats = "INSERT INTO member_stats (member_id, $colToUpdate) 
                 VALUES (:id, 1) 
                 ON DUPLICATE KEY UPDATE $colToUpdate = $colToUpdate + 1";

    $stmtStats = $pdo->prepare($sqlStats);
    $stmtStats->execute(['id' => $member_id]);

    // 4. Success Response
    echo json_encode([
        'ok' => true,
        'data' => [
            'member_id' => $member_id,
            'target_id' => $target_id,
            'effective_value' => $effective_value
        ]
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
?>