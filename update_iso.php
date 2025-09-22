<?php

// Массив соответствия локальных файлов
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
    'CentOS_7.iso' => [
        'local_subdir' => 'CentOS',
        'url_dir'     => 'https://mirror.yandex.ru/centos/centos/7/isos/x86_64/',
        'remote_name' => 'CentOS-7-x86_64-DVD-2207-02.iso',
    ],
    'CentOS_9.iso' => [
        'local_subdir' => 'CentOS',
        'url_dir'     => 'https://ftp.byfly.by/pub/centos-stream/9-stream/BaseOS/x86_64/iso/',
        'remote_name' => 'latest',
    ],
    'CentOS_10.iso' => [
        'local_subdir' => 'CentOS',
        'url_dir'     => 'https://ftp.byfly.by/pub/centos-stream/10-stream/BaseOS/x86_64/iso/',
        'remote_name' => 'latest',
    ],
    'AlmaLinux_8.10.iso' => [
        'local_subdir' => 'AlmaLinux',
        'url_dir'     => 'https://raw.repo.almalinux.org/almalinux/8.10/isos/x86_64/',
        'remote_name' => 'AlmaLinux-8-latest-x86_64-dvd.iso',
    ],
    'AlmaLinux_9.6.iso' => [
        'local_subdir' => 'AlmaLinux',
        'url_dir'     => 'https://raw.repo.almalinux.org/almalinux/9.6/isos/x86_64/',
        'remote_name' => 'AlmaLinux-9-latest-x86_64-dvd.iso',
    ],
    'AlmaLinux_10.0.iso' => [
        'local_subdir' => 'AlmaLinux',
        'url_dir'     => 'https://raw.repo.almalinux.org/almalinux/10/isos/x86_64/',
        'remote_name' => 'AlmaLinux-10-latest-x86_64-dvd.iso',
    ],
    'ProxmoxVE_7.4.iso' => [
        'local_subdir' => 'Proxmox',
        'url_dir'     => 'https://enterprise.proxmox.com/iso/',
        'remote_name' => 'proxmox-ve_7.4-1.iso',
    ],
    'ProxmoxVE_8.4.iso' => [
        'local_subdir' => 'Proxmox',
        'url_dir'     => 'https://enterprise.proxmox.com/iso/',
        'remote_name' => 'proxmox-ve_8.4-1.iso',
    ],
    'ProxmoxVE_9.0.iso' => [
        'local_subdir' => 'Proxmox',
        'url_dir'     => 'https://enterprise.proxmox.com/iso/',
        'remote_name' => 'proxmox-ve_9.0-1.iso',
    ],
    'Proxmox_BackUP_4.0.iso' => [
        'local_subdir' => 'Proxmox',
        'url_dir'     => 'https://enterprise.proxmox.com/iso/',
        'remote_name' => 'proxmox-backup-server_4.0-1.iso',
    ],
    'Proxmox_MailGateway_7.3.iso' => [
        'local_subdir' => 'Proxmox',
        'url_dir'     => 'https://enterprise.proxmox.com/iso/',
        'remote_name' => 'proxmox-mailgateway_7.3-1.iso',
    ],
];

$localDir = __DIR__ . DIRECTORY_SEPARATOR . 'files';
$cacheDir = __DIR__ . DIRECTORY_SEPARATOR . '.hash_cache';

/**
 * Функция скачивания файла с визуальным прогресс-баром
 */
function downloadFile(string $url, string $destination): bool
{
    $fp = fopen($destination, 'w+');
    if ($fp === false) {
        return false;
    }

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_FILE, $fp);
    curl_setopt($ch, CURLOPT_TIMEOUT, 0);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_NOPROGRESS, false);

    curl_setopt($ch, CURLOPT_PROGRESSFUNCTION, function ($resource, $downloadSize, $downloaded, $uploadSize, $uploaded) {
        if ($downloadSize > 0) {
            $percent = ($downloaded / $downloadSize) * 100;
            $filledBars = round($percent / 2); // 50 символов в полосе
            $emptyBars = 50 - $filledBars;
            $bar = str_repeat('=', $filledBars) . str_repeat(' ', $emptyBars);
            printf("\rСкачивание: %3d%% [%s]", round($percent), $bar);
        }
    });

    $result = curl_exec($ch);
    curl_close($ch);
    fclose($fp);

    echo "\n";

    return $result !== false;
}

/**
 * Универсальный парсер SHA256SUMS/SHA256SUM
 */
function parseChecksumContent(string $content): array
{
    $hashes = [];
    $lines = explode("\n", $content);

    foreach ($lines as $line) {
        $line = trim($line);
        if (preg_match('/^([a-f0-9]{64})\s+\*?(\S+)$/i', $line, $matches)) {
            $hashes[$matches[2]] = $matches[1];
        } elseif (preg_match('/^SHA256\s+\((.+?)\)\s+=\s+([a-f0-9]{64})$/i', $line, $matches)) {
            $hashes[$matches[1]] = $matches[2];
        }
    }

    return $hashes;
}

/**
 * Получение локального хэша с кэшированием
 */
function getLocalFileHashCached(string $localPath, string $cacheDir)
{
    $cacheFile = $cacheDir . DIRECTORY_SEPARATOR . md5($localPath) . '.cache';

    if (file_exists($cacheFile)) {
        $cacheData = json_decode(file_get_contents($cacheFile), true);
        $fileMTime = filemtime($localPath);
        $fileSize  = filesize($localPath);

        if ($cacheData && $cacheData['mtime'] === $fileMTime && $cacheData['size'] === $fileSize) {
            return $cacheData['hash'];
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

foreach ($filesToUpdate as $localName => $info) {

    echo "Обрабатываем файл: {$localName}\n";

    $localSubdir = $info['local_subdir'] ?? '';
    $urlDir     = rtrim($info['url_dir'], '/') . '/';
    $remoteName = $info['remote_name'];

    $localPath = $localDir
        . ($localSubdir !== '' ? DIRECTORY_SEPARATOR . $localSubdir : '')
        . DIRECTORY_SEPARATOR . $localName;

    echo "Локальный файл: {$localPath}\n";

    if (!file_exists($localPath)) {
        echo "Файл отсутствует локально — будет загружен\n";
    } elseif (!is_readable($localPath)) {
        echo "Ошибка: файл существует, но недоступен для чтения\n\n";
        continue;
    }

    $shaUrlsToTry = [
        $urlDir . 'SHA256SUMS',
        $urlDir . 'SHA256SUM',
        $urlDir . 'sha256sum.txt',
        $urlDir . 'CHECKSUM',
    ];

    $shaContent = false;
    foreach ($shaUrlsToTry as $tryUrl) {
        echo "Пытаемся скачать контрольные суммы: {$tryUrl}\n";
        $shaContent = @file_get_contents($tryUrl);
        if ($shaContent !== false) {
            break;
        }
    }

    if ($shaContent === false) {
        echo "Не удалось скачать ни SHA256SUMS, ни SHA256SUM с {$urlDir}\n\n";
        continue;
    }

    $remoteHashes = parseChecksumContent($shaContent);

    if ($remoteName === 'latest' || $remoteName === '') {
        $matchedName = null;
        foreach ($remoteHashes as $fileName => $fileHash) {
            if (stripos($fileName, 'dvd') !== false) {
                $matchedName = $fileName;
                break;
            }
        }
        if ($matchedName === null) {
            echo "Не найден файл с 'dvd' в имени для загрузки\n";
            continue;
        }
        $remoteName = $matchedName;
    }

    if (!isset($remoteHashes[$remoteName])) {
        echo "Нет контрольной суммы для файла {$remoteName} в контрольных суммах\n\n";
        continue;
    }

    $remoteHash = $remoteHashes[$remoteName];
    $localHash  = getLocalFileHashCached($localPath, $cacheDir);

    $localHashForCompare = $localHash !== false && strpos($localHash, 'sha256:') === 0
        ? substr($localHash, strlen('sha256:'))
        : $localHash;

    echo "Локальный хэш: " . ($localHash !== false ? $localHash : 'отсутствует') . "\n";
    echo "Удаленный хэш: {$remoteHash}\n";

    if ($localHashForCompare === $remoteHash) {
        echo "Файл актуален, обновление не требуется.\n\n";
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

?>
