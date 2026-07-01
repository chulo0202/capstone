<?php
try {
    $pdo = new PDO('mysql:host=127.0.0.1;port=3307;dbname=firstwebsite', 'root', 'php');
    echo 'connected' . PHP_EOL;
    $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    echo in_array('announcements', $tables, true) ? 'announcements exists' : 'announcements missing';
    echo PHP_EOL;
} catch (Throwable $e) {
    echo $e->getMessage() . PHP_EOL;
}
