<?php
try {
    $pdo = new PDO('mysql:host=127.0.0.1;dbname=maxton_laravel', 'root', '');
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
    $stmt = $pdo->query("SELECT TABLE_NAME FROM information_schema.tables WHERE table_schema = 'maxton_laravel'");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $table = $row['TABLE_NAME'];
        echo "Dropping $table\n";
        $pdo->exec("DROP TABLE IF EXISTS `$table`");
    }
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
    echo "All tables dropped.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
