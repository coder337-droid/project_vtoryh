<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/auth.php';

$pdo    = db();
$errors = [];
$login  = '';
$lock   = ['locked' => false, 'minutes' => 0];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check($_POST['csrf'] ?? null);

    $login    = strtolower(trim((string)($_POST['login'] ?? '')));
    $password = (string)($_POST['password'] ?? '');
    $lock     = auth_lock_state($pdo, $login);

    if ($lock['locked']) {
        $errors[] = 'Слишком много неудачных попыток входа.';
    } elseif ($login === '' || $password === '') {
        $errors[] = 'Введите логин и пароль';
    } elseif (!captcha_verify()) {
        $errors[] = 'Пазл не собран. Нажмите фрагменты в порядке образца.';
    } else {
        [$ok, $err, $row] = auth_attempt($pdo, $login, $password);
        if ($ok) {
            auth_start_session($pdo, $row);
            header('Location: admin_users.php');
            exit;
        }
        $errors[] = $err;
        $lock = auth_lock_state($pdo, $login);
    }
}

$order       = captcha_issue();
$isLocked    = $lock['locked'];
$lockMinutes = $lock['minutes'];
$isTest      = auth_test_mode();

require __DIR__ . '/../templates/login.view.php';