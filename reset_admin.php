<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only');
}

require_once __DIR__ . '/src/Database.php';

$login = $argv[1] ?? 'admin';
$pass  = $argv[2] ?? 'Admin#2026';

$pdo = Database::getConnection();
$rid = (int)$pdo->query("SELECT role_id FROM roles WHERE code = 'admin'")->fetchColumn();
if (!$rid) {
    exit("В таблице roles нет роли admin\n");
}

$st = $pdo->prepare(
    "INSERT INTO users (login, password_hash, last_name, first_name, email, role_id, is_active)
     VALUES (?, ?, ?, ?, ?, ?, 1)
     ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash),
                             role_id = VALUES(role_id),
                             is_active = 1"
);
$st->execute([
    $login,
    password_hash($pass, PASSWORD_DEFAULT),
    'Главный',
    'Админ',
    'admin@test.ru',
    $rid,
]);

echo "Готово: {$login} / {$pass}\n";