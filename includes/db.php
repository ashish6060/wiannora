<?php

/*
|--------------------------------------------------------------------------
| Wiannora Database Connection
|--------------------------------------------------------------------------
| Database: wiannora
| XAMPP default:
| Host: localhost
| Username: root
| Password: empty
|--------------------------------------------------------------------------
*/

$host = 'localhost';
$dbname = 'wiannora';
$username = 'root';
$password = '';

try {

    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false
        ]
    );

} catch (PDOException $e) {

    /*
     * Do not display the actual database credentials
     * or technical database details to website visitors.
     */
    error_log('Wiannora Database Error: ' . $e->getMessage());

    die('Database connection failed. Please try again later.');
}