<?php
declare(strict_types=1);

class Database
{
    private static ?PDO $pdo = null;

    public static function getConnection(): PDO
    {
        if (self::$pdo === null) {
            // Ищем файл .env на один уровень выше (в корне проекта)
            $envPath = __DIR__ . '/../.env';
            
            if (!file_exists($envPath)) {
                die('ОШИБКА: Файл .env не найден! PHP ищет его по пути: <b>' . realpath(__DIR__ . '/../') . '</b><br>Убедитесь, что файл .env лежит именно там (рядом с папками public и src) и не называется .env.txt');
            }
            
            $config = parse_ini_file($envPath);
            if ($config === false) {
                die('ОШИБКА: Не удалось прочитать файл .env. Проверьте его синтаксис.');
            }

            $dsn = 'mysql:host=' . ($config['DB_HOST'] ?? '127.0.0.1')
                 . ';port=' . ($config['DB_PORT'] ?? '3306')
                 . ';dbname=' . ($config['DB_NAME'] ?? 'project_vtoryh')
                 . ';charset=utf8mb4';

            try {
                self::$pdo = new PDO($dsn, $config['DB_USER'] ?? '', $config['DB_PASS'] ?? '', [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
                ]);
            } catch (PDOException $e) {
                // Если пароль или логин в .env неверные, мы увидим это здесь
                die('ОШИБКА ПОДКЛЮЧЕНИЯ К БАЗЕ ДАННЫХ: ' . $e->getMessage());
            }
        }
        
        return self::$pdo;
    }
}