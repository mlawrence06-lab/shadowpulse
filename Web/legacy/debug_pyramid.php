<?php
// debug_pyramid.php
// Shows the inner workings of the Voting Logic for User 729
ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: text/plain');

require_once 'db.php';
$pdo = sp_get_pdo();

$my_id = 729; 
$cat   = 'topic'; // Default to checking Topic votes

echo "--- PYRAMID STATUS FOR MEMBER $my_id ($cat) ---\n\n";

// 1. Calculate Shape
$sql = "SELECT 
    IFNULL(SUM(CASE WHEN effective_value = 3 THEN 1 ELSE 0 END), 0) as C,
    IFNULL(SUM(CASE WHEN effective_value IN (2, 4) THEN 1 ELSE 0 END), 0) as M,
    IFNULL(SUM(CASE WHEN effective_value IN (1, 5) THEN 1 ELSE 0 END), 0) as E
    FROM votes WHERE member_id = ? AND vote_category = ?";

$stmt = $pdo->prepare($sql);
$stmt->execute([$my_id, $cat]);
$shape = $stmt->fetch(PDO::FETCH_ASSOC);

$C = $shape['C'];
$M = $shape['M'];
$E = $shape['E'];

// 2. Calculate Caps
$M_cap = floor($C / 2);
$E_cap = floor($M / 2);

echo "SHAPE:\n";
echo "  Center (3):   $C\n";
echo "  Moderate (2,4): $M  (Cap: $M_cap)\n";
echo "  Extreme (1,5):  $E  (Cap: $E_cap)\n\n";

if ($M > $M_cap) echo "[!] MODERATE OVERFLOW: Logic should have demoted $M > $M_cap\n";
if ($E > $E_cap) echo "[!] EXTREME OVERFLOW: Logic should have demoted $E > $E_cap\n";

if ($M <= $M_cap && $E <= $E_cap) {
    echo "STATUS: Pyramid is Healthy/Valid.\n";
    if ($E < $E_cap) {
        echo "NOTE: You have space for " . ($E_cap - $E) . " more Extreme votes. A new 5 will stay 5.\n";
    } else {
        echo "NOTE: Pyramid is FULL. A new 5 will force an older 5 to become 4.\n";
    }
}

// 3. Show Recent Votes
echo "\n--- YOUR LAST 10 VOTES ---\n";
$sql = "SELECT id, target_id, desired_value, effective_value, updated_at 
        FROM votes WHERE member_id = ? AND vote_category = ? 
        ORDER BY updated_at DESC LIMIT 10";
$stmt = $pdo->prepare($sql);
$stmt->execute([$my_id, $cat]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($rows as $r) {
    $status = ($r['desired_value'] == $r['effective_value']) ? "Held" : "DEMOTED";
    echo "Vote ID " . $r['id'] . " (Target " . $r['target_id'] . "): " . 
         "Wanted " . $r['desired_value'] . " -> Got " . $r['effective_value'] . 
         " [$status] @ " . $r['updated_at'] . "\n";
}
?>