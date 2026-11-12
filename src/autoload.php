<?php
declare(strict_types=1);

// Подключаем все классы библиотеки Selenium вручную
spl_autoload_register(function ($class) {
    // Префикс пространства имен библиотеки
    $prefix = 'Facebook\\WebDriver\\';
    
    // Базовая директория, где лежит скачанная библиотека
    $base_dir = __DIR__ . '/php-webdriver/lib/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    // Получаем имя класса без префикса
    $relative_class = substr($class, $len);

    // Формируем путь к файлу (заменяем \ на /)
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    // Если файл существует - подключаем
    if (file_exists($file)) {
        require $file;
    }
});