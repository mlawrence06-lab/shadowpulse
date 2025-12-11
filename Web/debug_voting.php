<?php
// debug_voting.php
// Diagnoses why votes are not saving
ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: text/plain');

require_once 'db.php';
$pdo = sp_get_pdo();

echo "--- VOTING DIAGNOSTIC ---\n\n";

// 1. Check if Tables Exist
echo "1. Checking Database Tables...\n";
$tables = ['votes', 'vote_logs', 'members', 'member_stats'];
foreach ($tables as $t) {
    try {
        $pdo->query("SELECT 1 FROM $t LIMIT 1");
        echo "   [OK] Table '$t' exists.\n";
    } catch (Exception $e) {
        echo "   [FAIL] Table '$t' is MISSING! Error: " . $e->getMessage() . "\n";
        $missing = true;
    }
}

if (isset($missing)) {
    die("\nCRITICAL: You are missing tables. The Stored Procedure cannot save votes if the table isn't there.\n");
}

// 2. Check Stored Procedure
echo "\n2. Checking Stored Procedure 'shadowpulse_cast_vote'...\n";
try {
    $stmt = $pdo->query("SHOW CREATE PROCEDURE shadowpulse_cast_vote");
    $res = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($res) {
        echo "   [OK] Procedure exists.\n";
    } else {
        echo "   [FAIL] Procedure NOT FOUND.\n";
    }
} catch (Exception $e) {
    echo "   [FAIL] Error checking procedure: " . $e->getMessage() . "\n";
}

// 3. Simulate a Vote
echo "\n3. Attempting to cast a Test Vote (Member 729, Topic 1, Value 5)...\n";
try {
    $member_id = 729;
    $cat = 'topic';
    $target_id = 1;
    $desired = 5;
    $effective = 0;

    $stmt = $pdo->prepare("CALL shadowpulse_cast_vote(:member, :cat, :target, :desired, :effective)");
    $stmt->bindValue(':member', $member_id, PDO::PARAM_INT);
    $stmt->bindValue(':cat', $cat, PDO::PARAM_STR);
    $stmt->bindValue(':target', $target_id, PDO::PARAM_INT);
    $stmt->bindValue(':desired', $desired, PDO::PARAM_INT);
    $stmt->bindParam(':effective', $effective, PDO::PARAM_INT | PDO::PARAM_INPUT_OUTPUT, 4);
    
    $stmt->execute();
    
    echo "   [SUCCESS] Vote executed without error!\n";
    echo "   Effective Value Returned: " . $effective . "\n";
    
    // Check if it's actually in the table
    $check = $pdo->query("SELECT * FROM votes WHERE member_id=729 AND target_id=1");
    $row = $check->fetch(PDO::FETCH_ASSOC);
    echo "   [VERIFY] Row in DB: " . ($row ? "YES (Value: " . $row['effective_value'] . ")" : "NO") . "\n";

} catch (Exception $e) {
    echo "   [FAIL] SQL ERROR: " . $e->getMessage() . "\n";
}
?>