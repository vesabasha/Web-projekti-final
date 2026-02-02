<?php
// basically the communication line to the database
//e bona gen most of it eshte boilerplate code
$host = 'localhost';
$dbname = 'quest_db';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;port=3306", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $dbExists = $pdo->query("SHOW DATABASES LIKE '$dbname'")->fetch();

    if (!$dbExists) {
        $pdo->exec("CREATE DATABASE `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        echo "Database $dbname created.\n";
        $pdo->exec("USE `$dbname`");

        $sql = file_get_contents(__DIR__ . '/create_tables.sql');
        $pdo->exec($sql);
        echo "Tables created from create_tables.sql.\n";
    } else {
        $pdo->exec("USE `$dbname`");
    }
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>