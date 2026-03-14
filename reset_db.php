<?php
try {
    $pdo = new PDO('mysql:host=127.0.0.1', 'root', '');
    $pdo->exec('DROP DATABASE IF EXISTS maxton_laravel');
    $pdo->exec('CREATE DATABASE maxton_laravel');
    echo "Database reset.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
