<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';
require_once __DIR__ . '/../src/functions.php';

$errors = [];
$login  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check($_POST['csrf'] ?? null);

    $login    = strtolower(trim((string)($_POST['login'] ?? '')));
    $password = (string)($_POST['password'] ?? '');
    
    $isTestMode   = isset($_GET['test_mode']) && $_GET['test_mode'] === '1';
    $puzzleSolved = $isTestMode ? true : (($_POST['puzzle_solved'] ?? '') === '1');

    if ($login === '' || $password === '') {
        $errors[] = 'Введите логин и пароль';
    } elseif (!$puzzleSolved) {
        $errors[] = 'Соберите капчу';
    } else {
        if ($login === 'admin' && $password === 'admin') {
            session_regenerate_id(true);
            
            $_SESSION['user'] = [
                'user_id'     => 1,
                'login'       => 'admin',
                'first_name'  => 'Админ',
                'middle_name' => '',
                'last_name'   => 'Главный',
                'email'       => 'admin@test.ru',
                'role'        => 'admin',
            ];
            $_SESSION['just_logged_in'] = true;

            header('Location: admin_users.php');
            exit;
        } else {
            $errors[] = 'Неверный логин или пароль (используйте admin/admin)';
        }
    }
}

$order = puzzle_generate();
$isTest = isset($_GET['test_mode']) && $_GET['test_mode'] === '1';
$isLocked = false;
$puzzleSolved = false;
$testReport = null;

require __DIR__ . '/../templates/login.view.php';