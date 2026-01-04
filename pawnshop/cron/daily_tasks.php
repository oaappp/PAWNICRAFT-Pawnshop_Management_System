<?php
// cron/daily_tasks.php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

ini_set('display_errors', 1);
error_reporting(E_ALL);

$db = (new Database())->getConnection();

// Find loans beyond grace period (unredeemed) that are NOT yet in auctions
$sql = "SELECT pt.transaction_id, pt.loan_amount, pt.pawn_ticket_number
        FROM pawn_transactions pt
        WHERE pt.grace_period_end < CURDATE()
          AND pt.status IN ('active','renewed','expired')
          AND NOT EXISTS (
              SELECT 1 FROM auctions a
              WHERE a.transaction_id = pt.transaction_id
          )";

$rows = $db->query($sql)->fetchAll();

$created = 0;

foreach ($rows as $row) {
    $tid         = $row['transaction_id'];
    $loan_amount = (float)$row['loan_amount'];

    // Business rule for starting price – here we use loan amount
    $starting_price = $loan_amount;

    // Insert auction
    $stmt = $db->prepare(
        "INSERT INTO auctions (transaction_id, auction_date, starting_price, status, processed_by)
         VALUES (:tid, CURDATE(), :sp, 'pending', :uid)"
    );
    $stmt->execute([
        ':tid' => $tid,
        ':sp'  => $starting_price,
        ':uid' => 1, // system/admin user id; change if you want
    ]);

    // Update pawn transaction status to 'auctioned'
    $stmt2 = $db->prepare(
        "UPDATE pawn_transactions
         SET status = 'auctioned'
         WHERE transaction_id = :tid"
    );
    $stmt2->execute([':tid' => $tid]);

    $created++;
}

// Simple output so you can see something when run in browser
echo "Daily tasks completed. Auctions created: {$created}";