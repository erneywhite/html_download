#!/usr/bin/env php
<?php
/**
 * Скрипт для предварительного вычисления хэшей файлов
 * Запуск: php compute_all_hashes.php
 */

$dir = __DIR__ . DIRECTORY_SEPARATOR . 'files';
$cacheDir = __DIR__ . DIRECTORY_SEPARATOR . '.hash_cache';

// Создаем директорию для кэша
if (!is_dir($cacheDir)) {
    mkdir($cacheDir, 0755, true);
}

echo "Начинаем вычисление хэшей для всех файлов...
";

function computeHashForFile($filePath, $cacheDir) {
    $cacheFile = $cacheDir . DIRECTORY_SEPARATOR . md5($filePath) . '.cache';
    $fileSize = filesize($filePath);
    $fileMTime = filemtime($filePath);

    // Проверяем кэш
    if (file_exists($cacheFile)) {
        $cacheData = json_decode(file_get_contents($cacheFile), true);
        if ($cacheData && $cacheData['mtime'] == $fileMTime && $cacheData['size'] == $fileSize) {
            return $cacheData['hash']; // Уже есть в кэше
        }
    }

    echo "Вычисляем хэш для: " . basename($filePath) . " (" . number_format($fileSize / 1024 / 1024, 2) . " MB)...
";

    $startTime = microtime(true);
    $hash = hash_file('sha256', $filePath);
    $endTime = microtime(true);

    echo "  Готово за " . number_format($endTime - $startTime, 2) . " сек. Хэш: " . $hash . "
";

    // Сохраняем в кэш с префиксом
    file_put_contents($cacheFile, json_encode([
        'hash' => 'sha256:' . $hash,
        'raw_hash' => $hash,
        'mtime' => $fileMTime,
        'size' => $fileSize
    ]));

    return 'sha256:' . $hash;
}

function processDirectory($dirPath, $cacheDir) {
    if (!is_dir($dirPath)) {
        echo "Директория $dirPath не найдена
";
        return;
    }

    foreach (scandir($dirPath) as $item) {
        if ($item === '.' || $item === '..') continue;

        $itemPath = $dirPath . DIRECTORY_SEPARATOR . $item;

        if (is_dir($itemPath)) {
            echo "Обрабатываем папку: $item
";
            processDirectory($itemPath, $cacheDir);
        } elseif (is_file($itemPath)) {
            computeHashForFile($itemPath, $cacheDir);
        }
    }
}

$startTime = microtime(true);
processDirectory($dir, $cacheDir);
$endTime = microtime(true);

echo "
Все хэши вычислены за " . number_format($endTime - $startTime, 2) . " сек.
";
echo "Теперь можно запускать веб-интерфейс - все хэши будут загружаться мгновенно!
";
?>
