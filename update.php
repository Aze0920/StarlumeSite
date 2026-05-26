<?php
require_once __DIR__ . '/config/app.php';

set_time_limit(300);

$updateLogFile = __DIR__ . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'update.log';

function update_log_write($message)
{
    global $updateLogFile;
    $dir = dirname($updateLogFile);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    $line = '[' . date('Y-m-d H:i:s') . '] ' . $message;
    file_put_contents($updateLogFile, $line . PHP_EOL, FILE_APPEND);
    echo $line . PHP_EOL;
}

function run_cmd($cmd, $cwd = null)
{
    $prefix = '';
    if ($cwd) {
        $prefix = stripos(PHP_OS_FAMILY, 'Windows') !== false
            ? 'cd /d ' . escapeshellarg($cwd) . ' && '
            : 'cd ' . escapeshellarg($cwd) . ' && ';
    }
    $fullCmd = $prefix . $cmd;
    update_log_write('RUN: ' . $fullCmd);
    $output = array();
    $code = 0;
    exec($fullCmd . ' 2>&1', $output, $code);
    foreach ($output as $line) {
        update_log_write($line);
    }
    update_log_write('COMMAND EXIT: ' . $code);
    return (int) $code;
}

function remove_dir_recursive($dir)
{
    if (!is_dir($dir)) {
        return true;
    }

    $items = scandir($dir) ? scandir($dir) : array();
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }
        $path = $dir . DIRECTORY_SEPARATOR . $item;
        if (is_dir($path) && !is_link($path)) {
            if (!remove_dir_recursive($path)) {
                return false;
            }
        } else {
            if (!@unlink($path)) {
                update_log_write('DELETE FILE FAILED: ' . $path);
                return false;
            }
        }
    }

    if (!@rmdir($dir)) {
        update_log_write('DELETE DIR FAILED: ' . $dir);
        return false;
    }
    return true;
}

function restore_install_files($site, $backupDir)
{
    $files = array(
        'config/database.php',
        'data/install.lock',
    );
    foreach ($files as $file) {
        $backup = $backupDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $file);
        $target = $site . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $file);
        if (!is_file($backup)) {
            continue;
        }
        $targetDir = dirname($target);
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }
        if (copy($backup, $target)) {
            update_log_write('RESTORE INSTALL FILE: ' . $file);
        } else {
            update_log_write('RESTORE INSTALL FILE FAILED: ' . $file);
        }
    }
}

function backup_install_files($site)
{
    $backupDir = $site . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'update-backup-' . date('YmdHis');
    $files = array(
        'config/database.php',
        'data/install.lock',
    );
    foreach ($files as $file) {
        $source = $site . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $file);
        if (!is_file($source)) {
            continue;
        }
        $target = $backupDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $file);
        $targetDir = dirname($target);
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }
        if (copy($source, $target)) {
            update_log_write('BACKUP INSTALL FILE: ' . $file);
        } else {
            update_log_write('BACKUP INSTALL FILE FAILED: ' . $file);
        }
    }
    return $backupDir;
}

function copy_update_files($source, $target, $root = null, &$stats = null)
{
    if ($root === null) {
        $root = $source;
    }
    if ($stats === null) {
        $stats = array('updated' => 0, 'skipped' => 0, 'failed' => 0);
    }

    $skipDirs = array('.git', 'logs');
    $skipRelativeDirs = array(
        'data/update-source',
    );
    $protectedFiles = array(
        'config/database.php',
        'data/install.lock',
        'data/update.log',
    );
    $forceContentFiles = array(
        'version.json',
        'config/app.php',
    );
    if (!is_dir($target)) {
        mkdir($target, 0755, true);
    }

    $items = scandir($source) ? scandir($source) : array();
    foreach ($items as $item) {
        $from = $source . DIRECTORY_SEPARATOR . $item;
        $to = $target . DIRECTORY_SEPARATOR . $item;
        if ($item === '.' || $item === '..' || in_array($item, $skipDirs, true)) {
            continue;
        }

        $relative = ltrim(str_replace(array($root, '\\'), array('', '/'), $from), '/');
        if (is_dir($from) && in_array($relative, $skipRelativeDirs, true)) {
            continue;
        }
        if (in_array($relative, $protectedFiles, true)) {
            update_log_write('SKIP PROTECTED FILE: ' . $relative);
            $stats['skipped']++;
            continue;
        }

        if (is_dir($from)) {
            copy_update_files($from, $to, $root, $stats);
            continue;
        }

        $sourceSize = filesize($from);
        $oldSize = is_file($to) ? filesize($to) : -1;
        $needCopy = !is_file($to) || $oldSize !== $sourceSize;
        if (!$needCopy && in_array($relative, $forceContentFiles, true)) {
            $needCopy = md5_file($from) !== md5_file($to);
        }

        if (!$needCopy) {
            $stats['skipped']++;
            continue;
        }

        $toDir = dirname($to);
        if (!is_dir($toDir)) {
            mkdir($toDir, 0755, true);
        }

        if (copy($from, $to)) {
            $stats['updated']++;
            if ($oldSize >= 0) {
                update_log_write('UPDATE FILE: ' . $relative . ' (' . $oldSize . ' -> ' . $sourceSize . ' bytes)');
            } else {
                update_log_write('ADD FILE: ' . $relative . ' (' . $sourceSize . ' bytes)');
            }
        } else {
            $stats['failed']++;
            update_log_write('COPY FAILED: ' . $relative . ' | ' . $from . ' -> ' . $to);
        }
    }

    return $stats;
}

update_log_write('Start update ' . JMWEB_NAME);
update_log_write('PHP binary: ' . (defined('PHP_BINARY') ? PHP_BINARY : 'unknown'));
update_log_write('Site dir: ' . JMWEB_SITE_DIR);
update_log_write('Work dir: ' . JMWEB_UPDATE_WORKDIR);
update_log_write('Repo: ' . JMWEB_UPDATE_REPO);

if (!function_exists('exec')) {
    update_log_write('ERROR: PHP exec is disabled.');
    exit(1);
}

$gitCheck = run_cmd('git --version');
if ($gitCheck !== 0) {
    update_log_write('ERROR: Git command not found. Please install Git on server or enable git command path.');
    exit(1);
}

$workdir = JMWEB_UPDATE_WORKDIR;
$repo = JMWEB_UPDATE_REPO;
$site = JMWEB_SITE_DIR;
$installBackupDir = backup_install_files($site);

if (!is_dir(dirname($workdir))) {
    mkdir(dirname($workdir), 0755, true);
}

if (!is_dir($workdir . DIRECTORY_SEPARATOR . '.git')) {
    if (is_dir($workdir)) {
        update_log_write('Remove invalid update source dir: ' . $workdir);
        if (!remove_dir_recursive($workdir)) {
            update_log_write('ERROR: Remove invalid update source dir failed.');
            exit(1);
        }
    }
    update_log_write('Clone repo: ' . $repo);
    $code = run_cmd('git clone --depth=1 ' . escapeshellarg($repo) . ' ' . escapeshellarg($workdir));
    if ($code !== 0) {
        update_log_write('Depth clone failed, try full clone.');
        if (is_dir($workdir)) {
            remove_dir_recursive($workdir);
        }
        $code = run_cmd('git clone ' . escapeshellarg($repo) . ' ' . escapeshellarg($workdir));
        if ($code !== 0) {
            update_log_write('ERROR: Clone failed. Check network, repo URL, and GitHub access.');
            exit($code);
        }
    }
} else {
    update_log_write('Fetch and reset update source.');
    $code = run_cmd('git fetch origin main', $workdir);
    if ($code !== 0) {
        update_log_write('ERROR: Fetch failed. Check network, branch, and repo permission.');
        exit($code);
    }
    $code = run_cmd('git reset --hard origin/main', $workdir);
    if ($code !== 0) {
        update_log_write('ERROR: Reset failed.');
        exit($code);
    }
    run_cmd('git clean -fd', $workdir);
}

update_log_write('Compare file size and copy changed files to site dir.');
$copyStats = array('updated' => 0, 'skipped' => 0, 'failed' => 0);
copy_update_files($workdir, $site, $workdir, $copyStats);
restore_install_files($site, $installBackupDir);
update_log_write('Copy summary: updated ' . $copyStats['updated'] . ' file(s), skipped ' . $copyStats['skipped'] . ' unchanged file(s), failed ' . $copyStats['failed'] . ' file(s).');
if ($copyStats['failed'] > 0) {
    update_log_write('ERROR: Some files failed to copy.');
    exit(1);
}
update_log_write('Update done.');
