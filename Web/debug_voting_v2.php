<?php
// debug_voting_v2.php
// Updated to bypass the "1414 OUT argument" error using Session Variables
ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: text/plain');

require_once 'db.php';
$pdo = sp_get_pdo();

echo "--- VOTING DIAGNOSTIC V2 ---\n\n";

// 1. Check if Tables Exist
echo "1. Checking Tables...\n";
$tables = ['votes', 'vote_logs'];
foreach ($tables as $t) {
    try {
        $pdo->query("SELECT 1 FROM $t LIMIT 1");
        echo "   [OK] Table '$t' exists.\n";
    } catch (Exception $e) {
        die("   [FAIL] Table '$t' MISSING. You need to migrate the 'votes' table structure!\n");
    }
}

// 2. Check if we have existing votes (Pyramid Base)
$sql = "SELECT count(*) FROM votes WHERE member_id = 729 AND vote_category = 'topic'";
$count = $pdo->query($sql)->fetchColumn();
echo "   [INFO] Member 729 has $count existing topic votes.\n";
if ($count == 0) {
    echo "   [WARNING] You have 0 existing votes. A new vote of 5 will likely be INSTANTLY demoted to 3 because there is no pyramid structure to support it.\n";
}

// 3. Attempting to cast a Test Vote (Topic 1, Value 5)
echo "\n3. Casting Test Vote (Using Session Variables)...\n";
try {
    $member_id = 729;
    $cat = 'topic';
    $target_id = 1;
    $desired = 5;

    // USE THE @eff FIX
    $stmt = $pdo->prepare("CALL shadowpulse_cast_vote(:member, :cat, :target, :desired, @eff)");
    $stmt->bindValue(':member', $member_id, PDO::PARAM_INT);
    $stmt->bindValue(':cat', $cat, PDO::PARAM_STR);
    $stmt->bindValue(':target', $target_id, PDO::PARAM_INT);
    $stmt->bindValue(':desired', $desired, PDO::PARAM_INT);
    
    $stmt->execute();
    $stmt->closeCursor();

    // Retrieve Output
    $row = $pdo->query("SELECT @eff as val")->fetch(PDO::FETCH_ASSOC);
    $effective = $row['val'];
    
    echo "   [SUCCESS] Vote executed!\n";
    echo "   Desired: $desired -> Effective: $effective\n";
    
    if ($desired == 5 && $effective < 5) {
        echo "   [NOTE] Vote was demoted. This is normal if your pyramid is empty/small.\n";
    }

    // Verify DB
    $check = $pdo->query("SELECT * FROM votes WHERE member_id=729 AND target_id=1");
    $row = $check->fetch(PDO::FETCH_ASSOC);
    echo "   [VERIFY] Row found in DB: " . ($row ? "YES (Value: " . $row['effective_value'] . ")" : "NO") . "\n";

} catch (Exception $e) {
    echo "   [FAIL] SQL ERROR: " . $e->getMessage() . "\n";
}
?>