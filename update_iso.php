<?php

// Массив соответствия: ключ - локальное имя файла
// Значение - массив с ключами:
//  - 'local_subdir' - поддиректория внутри папки $localDir,
//  - 'url_dir'     - ссылка на папку с файлами на сервере,
//  - 'remote_name' - имя файла на сервере (как указано в SHA256SUMS)
$filesToUpdate = [
    'Debian_11.iso' => [
        'local_subdir' => 'Debian',
        'url_dir'     => 'https://cdimage.debian.org/cdimage/archive/11.11.0/amd64/iso-dvd/',
        'remote_name' => 'debian-11.11.0-amd64-DVD-1.iso',
    ],
    'Debian_12.iso' => [
        'local_subdir' => 'Debian',
        'url_dir'     => 'https://cdimage.debian.org/cdimage/archive/12.12.0/amd64/iso-dvd/',
        'remote_name' => 'debian-12.12.0-amd64-DVD-1.iso',
    ],
    'Debian_13.iso' => [
        'local_subdir' => 'Debian',
        'url_dir'     => 'https://cdimage.debian.org/cdimage/archive/13.0.0/amd64/iso-dvd/',
        'remote_name' => 'debian-13.0.0-amd64-DVD-1.iso',
    ],
    'Ubuntu_22.04.iso' => [
        'local_subdir' => 'Ubuntu',
        'url_dir'     => 'https://releases.ubuntu.com/22.04/',
        'remote_name' => 'ubuntu-22.04.5-live-server-amd64.iso',
    ],
    'Ubuntu_24.04.iso' => [
        'local_subdir' => 'Ubuntu',
        'url_dir'     => 'https://releases.ubuntu.com/24.04/',
        'remote_name' => 'ubuntu-24.04.3-live-server-amd64.iso',
    ],
    'Ubuntu_25.04.iso' => [
        'local_subdir' => 'Ubuntu',
        'url_dir'     => 'https://releases.ubuntu.com/25.04/',
        'remote_name' => 'ubuntu-25.04-live-server-amd64.iso',
    ],
    // Добавляйте другие по аналогии
];

$localDir = __DIR__ . DIRECTORY_SEPARATOR . 'files';
$cacheDir = __DIR__ . DIRECTORY_SEPARATOR . '.hash_cache';

/**
 * Скачивает файл из удаленного URL и сохраняет в $destination
 * @return bool
 */
function downloadFile(string $url, string $destination): bool
{
    $fp = fopen($destination, 'w+');
    if ($fp === false) {
        return false;
    }

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_FILE, $fp);
    curl_setopt($ch, CURLOPT_TIMEOUT, 300); // 5 мин таймаута
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

    $result = curl_exec($ch);
    curl_close($ch);
    fclose($fp);

    return $result !== false;
}

/**
 * Парсит содержимое SHA256SUMS, возвращает ассоциативный массив [имя_файла => sha256]
 */
function parseSHA256SUMS(string $content): array
{
    $hashes = [];
    $lines  = explode("\n", $content);

    foreach ($lines as $line) {
        $line = trim($line);
        if (preg_match('/^([a-f0-9]{64})\s+\*?(\S+)$/i', $line, $matches)) {
            $hashes[$matches[2]] = $matches[1];
        }
    }

    return $hashes;
}

/**
 * Получает локальный sha256 хэш с использованием кэша
 * Кэш хранится в $cacheDir, имя файла кэша - md5 пути файла
 * Возвращает строку хэша или false при отсутствии/ошибке
 */
function getLocalFileHashCached(string $localPath, string $cacheDir)
{
    $cacheFile = $cacheDir . DIRECTORY_SEPARATOR . md5($localPath) . '.cache';

    if (file_exists($cacheFile)) {
        $cacheData = json_decode(file_get_contents($cacheFile), true);
        $fileMTime = filemtime($localPath);
        $fileSize  = filesize($localPath);

        if ($cacheData && $cacheData['mtime'] === $fileMTime && $cacheData['size'] === $fileSize) {
            return $cacheData['hash'];  // используем кэш
        }
    }

    if (!file_exists($localPath) || !is_readable($localPath)) {
        return false;
    }

    $hash = hash_file('sha256', $localPath);
    $cacheDataToSave = [
        'hash'  => $hash,
        'mtime' => filemtime($localPath),
        'size'  => filesize($localPath),
    ];

    if (!is_dir($cacheDir)) {
        mkdir($cacheDir, 0755, true);
    }

    file_put_contents($cacheFile, json_encode($cacheDataToSave));
    return $hash;
}

// Обработка каждого файла из списка
foreach ($filesToUpdate as $localName => $info) {
    echo "Обрабатываем файл: {$localName}\n";

    $localSubdir = $info['local_subdir'] ?? '';
    $urlDir     = rtrim($info['url_dir'], '/') . '/';
    $remoteName = $info['remote_name'];
    $shaUrl     = $urlDir . 'SHA256SUMS';

    // Полный путь к локальному файлу с поддиректорией (если указано)
    $localPath  = $localDir
        . ($localSubdir !== '' ? DIRECTORY_SEPARATOR . $localSubdir : '')
        . DIRECTORY_SEPARATOR . $localName;

    echo "Локальный файл: $localPath\n";

    if (!file_exists($localPath)) {
        echo "Файл отсутствует локально — будет загружен\n";
    } elseif (!is_readable($localPath)) {
        echo "Ошибка: файл существует, но недоступен для чтения\n\n";
        continue;
    }

    echo "Скачиваем SHA256SUMS: {$shaUrl}\n";

    $shaContent = @file_get_contents($shaUrl);
    if ($shaContent === false) {
        echo "Не удалось загрузить SHA256SUMS с {$shaUrl}\n\n";
        continue;
    }

    $remoteHashes = parseSHA256SUMS($shaContent);
    if (!isset($remoteHashes[$remoteName])) {
        echo "Контрольная сумма для файла {$remoteName} отсутствует в SHA256SUMS\n\n";
        continue;
    }

    $remoteHash = $remoteHashes[$remoteName];
    $localHash  = getLocalFileHashCached($localPath, $cacheDir);

    // Удаляем префикс sha256: из локального хэша при сравнении, если он есть
    $localHashForCompare = $localHash !== false && strpos($localHash, 'sha256:') === 0
        ? substr($localHash, strlen('sha256:'))
        : $localHash;

    echo "Локальный хэш: " . ($localHash !== false ? $localHash : 'отсутствует') . "\n";
    echo "Удалённый хэш: {$remoteHash}\n";

    if ($localHashForCompare === $remoteHash) {
        echo "Файл актуален, скачивание не нужно.\n\n";
        continue;
    }

    echo "Файл устарел или отсутствует, скачиваем обновленную версию...\n";

    $fileUrl = $urlDir . $remoteName;
    $tmpFile = $localPath . '.tmp';

    if (!is_dir(dirname($localPath))) {
        mkdir(dirname($localPath), 0755, true);
    }

    if (downloadFile($fileUrl, $tmpFile)) {
        rename($tmpFile, $localPath);
        echo "Файл обновлен: {$localName}\n\n";
    } else {
        echo "Ошибка скачивания: {$fileUrl}\n\n";
        if (file_exists($tmpFile)) {
            unlink($tmpFile);
        }
    }
}

echo "Обновление файлов завершено.\n";
