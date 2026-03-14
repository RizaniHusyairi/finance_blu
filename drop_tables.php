<?php
try {
    $pdo = new PDO('mysql:host=127.0.0.1;dbname=maxton_laravel', 'root', '');
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
    $pdo->exec('DROP TABLE IF EXISTS contract_addendums, contract_terms, transaction_taxes, bku_logs, approval_logs, transactions, contracts, budgets, suppliers, employees');
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
    echo "Tables dropped.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
