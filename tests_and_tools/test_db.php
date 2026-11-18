<?php
declare(strict_types=1);

$host = '127.0.0.1';
$db   = 'project_vtoryh';
$user = 'app_user';
$pass = 'app_pass_2026';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    echo "✅ ПОДКЛЮЧЕНИЕ К БАЗЕ ДАННЫХ УСПЕШНО!";
} catch (PDOException $e) {
    echo "❌ ОШИБКА ПОДКЛЮЧЕНИЯ: " . $e->getMessage();
}