#!/usr/bin/env php
<?php
/**
 * Скрипт для предварительного вычисления хэшей файлов с очисткой устаревших кэшей
 * Запуск: php compute_all_hashes.php
 */

$dir = __DIR__ . DIRECTORY_SEPARATOR . 'files';
$cacheDir = __DIR__ . DIRECTORY_SEPARATOR . '.hash_cache';

// Создаем директорию для кэша
if (!is_dir($cacheDir)) {
    mkdir($cacheDir, 0755, true);
}

echo "Начинаем вычисление хэшей для всех файлов...\n";

/**
 * Вычисляет или получает из кэша хэш файла
 */
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

    echo "Вычисляем хэш для: " . basename($filePath) . " (" . number_format($fileSize / 1024 / 1024, 2) . " MB)...\n";

    $startTime = microtime(true);
    $hash = hash_file('sha256', $filePath);
    $endTime = microtime(true);

    echo "  Готово за " . number_format($endTime - $startTime, 2) . " сек. Хэш: " . $hash . "\n";

    // Сохраняем в кэш с префиксом
    file_put_contents($cacheFile, json_encode([
        'hash' => 'sha256:' . $hash,
        'raw_hash' => $hash,
        'mtime' => $fileMTime,
        'size' => $fileSize
    ]));

    return 'sha256:' . $hash;
}

/**
 * Рекурсивно собирает все локальные файлы и формирует массив md5 имен для кэш-файлов
 */
function getAllLocalFileHashNames(string $dir): array {
    $hashes = [];

    $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));

    foreach ($rii as $file) {
        if ($file->isFile()) {
            $path = $file->getPathname();
            $hashes[md5($path)] = true;
        }
    }

    return $hashes;
}

/**
 * Очищает папку кэша от файлов без соответствия в локальных файлах
 */
function cleanCacheDir(string $cacheDir, array $validHashes) {
    if (!is_dir($cacheDir)) return;

    foreach (scandir($cacheDir) as $file) {
        if ($file === '.' || $file === '..') continue;
        $fullPath = $cacheDir . DIRECTORY_SEPARATOR . $file;
        if (!is_file($fullPath)) continue;

        $fileBase = pathinfo($file, PATHINFO_FILENAME); // md5 имя без расширения

        if (!isset($validHashes[$fileBase])) {
            // Удаляем устаревший кеш
            unlink($fullPath);
            echo "Удален устаревший кэш: {$file}\n";
        }
    }
}

// Вычисляем хэши для всех файлов
processDirectory($dir, $cacheDir);

// Получаем список валидных кэшей и очищаем папку кэша
$validHashes = getAllLocalFileHashNames($dir);
cleanCacheDir($cacheDir, $validHashes);

echo "Все хэши вычислены и кэш очищен.\n";

/**
 * Рекурсивный обход каталога для вычисления хэшей
 */
function processDirectory($dirPath, $cacheDir) {
    if (!is_dir($dirPath)) {
        echo "Директория $dirPath не найдена\n";
        return;
    }

    foreach (scandir($dirPath) as $item) {
        if ($item === '.' || $item === '..') continue;

        $itemPath = $dirPath . DIRECTORY_SEPARATOR . $item;

        if (is_dir($itemPath)) {
            echo "Обрабатываем папку: $item\n";
            processDirectory($itemPath, $cacheDir);
        } elseif (is_file($itemPath)) {
            computeHashForFile($itemPath, $cacheDir);
        }
    }
}
?>
