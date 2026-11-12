<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';
require_once __DIR__ . '/../src/functions.php';

$user = require_admin();
$pdo  = db();

if (isset($_GET['download'])) {
    $tables = [];
    $result = $pdo->query("SHOW TABLES");
    while ($row = $result->fetch(PDO::FETCH_NUM)) {
        $tables[] = $row[0];
    }

    $sqlDump = "-- Дамп базы данных project_vtoryh\n";
    $sqlDump .= "-- Дата создания: " . date('Y-m-d H:i:s') . "\n";
    $sqlDump .= "-- Создано: " . $user['login'] . "\n\n";
    $sqlDump .= "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n";
    $sqlDump .= "SET time_zone = \"+00:00\";\n\n";

    foreach ($tables as $table) {
        $createTable = $pdo->query("SHOW CREATE TABLE `$table`")->fetch(PDO::FETCH_NUM);
        $sqlDump .= "\n\n-- --------------------------------------------------------\n";
        $sqlDump .= "-- Структура таблицы `$table`\n";
        $sqlDump .= "-- --------------------------------------------------------\n\n";
        $sqlDump .= "DROP TABLE IF EXISTS `$table`;\n";
        $sqlDump .= $createTable[1] . ";\n\n";

        $rows = $pdo->query("SELECT * FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($rows) > 0) {
            $sqlDump .= "-- Дамп данных таблицы `$table`\n";
            $sqlDump .= "INSERT INTO `$table` (";
            
            $columns = array_keys($rows[0]);
            $colsStr = [];
            foreach ($columns as $col) {
                $colsStr[] = "`$col`";
            }
            $sqlDump .= implode(", ", $colsStr) . ") VALUES\n";

            $valuesStr = [];
            foreach ($rows as $row) {
                $vals = [];
                foreach ($row as $val) {
                    if ($val === null) {
                        $vals[] = "NULL";
                    } else {
                        $vals[] = $pdo->quote((string)$val);
                    }
                }
                $valuesStr[] = "(" . implode(", ", $vals) . ")";
            }
            $sqlDump .= implode(",\n", $valuesStr) . ";\n";
        }
        $sqlDump .= "\n";
    }

    $filename = 'backup_project_vtoryh_' . date('Y-m-d_H-i-s') . '.sql';
    
    header('Content-Description: File Transfer');
    header('Content-Type: application/sql; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . strlen($sqlDump));
    
    echo $sqlDump;
    exit;
}

require __DIR__ . '/../templates/admin_backup.view.php';