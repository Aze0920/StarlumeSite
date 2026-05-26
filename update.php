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

function path_starts_with($path, $prefix)
{
    return substr($path, 0, strlen($prefix)) === $prefix;
}

function copy_update_files($source, $target)
{
    $skip = array('.git', 'data', 'logs');
    $sourceReal = realpath($source) ? realpath($source) : $source;
    $targetReal = realpath($target) ? realpath($target) : $target;
    $items = scandir($source) ? scandir($source) : array();
    foreach ($items as $item) {
        $from = $source . DIRECTORY_SEPARATOR . $item;
        $to = $target . DIRECTORY_SEPARATOR . $item;
        $fromReal = realpath($from) ? realpath($from) : $from;
        if ($item === '.' || $item === '..' || in_array($item, $skip, true) || $sourceReal === $targetReal || path_starts_with($fromReal, $targetReal . DIRECTORY_SEPARATOR)) {
            continue;
        }
        if (is_dir($from)) {
            if (!is_dir($to)) {
                mkdir($to, 0755, true);
            }
            copy_update_files($from, $to);
        } else {
            if (!copy($from, $to)) {
                update_log_write('COPY FAILED: ' . $from . ' -> ' . $to);
            }
        }
    }
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

if (!is_dir(dirname($workdir))) {
    mkdir(dirname($workdir), 0755, true);
}

if (!is_dir($workdir . DIRECTORY_SEPARATOR . '.git')) {
    update_log_write('Clone repo: ' . $repo);
    $code = run_cmd('git clone ' . escapeshellarg($repo) . ' ' . escapeshellarg($workdir));
    if ($code !== 0) {
        update_log_write('ERROR: Clone failed. Check network, repo URL, and GitHub access.');
        exit($code);
    }
} else {
    update_log_write('Pull latest code.');
    $code = run_cmd('git pull origin main', $workdir);
    if ($code !== 0) {
        update_log_write('ERROR: Pull failed. Check network, branch, and repo permission.');
        exit($code);
    }
}

update_log_write('Copy files to site dir.');
copy_update_files($workdir, $site);
update_log_write('Update done.');
