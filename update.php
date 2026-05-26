<?php
require_once __DIR__ . '/config/app.php';

set_time_limit(300);

function line_out(string $message): void
{
    echo '[' . date('H:i:s') . '] ' . $message . PHP_EOL;
}

function run_cmd(string $cmd, ?string $cwd = null): int
{
    $prefix = '';
    if ($cwd) {
        $prefix = stripos(PHP_OS_FAMILY, 'Windows') !== false
            ? 'cd /d ' . escapeshellarg($cwd) . ' && '
            : 'cd ' . escapeshellarg($cwd) . ' && ';
    }
    passthru($prefix . $cmd, $code);
    return (int) $code;
}

function path_starts_with(string $path, string $prefix): bool
{
    return substr($path, 0, strlen($prefix)) === $prefix;
}

function copy_update_files(string $source, string $target): void
{
    $skip = ['.git', 'data', 'logs'];
    $sourceReal = realpath($source) ?: $source;
    $targetReal = realpath($target) ?: $target;
    $items = scandir($source) ?: [];
    foreach ($items as $item) {
        $from = $source . DIRECTORY_SEPARATOR . $item;
        $to = $target . DIRECTORY_SEPARATOR . $item;
        $fromReal = realpath($from) ?: $from;
        if ($item === '.' || $item === '..' || in_array($item, $skip, true) || $sourceReal === $targetReal || path_starts_with($fromReal, $targetReal . DIRECTORY_SEPARATOR)) {
            continue;
        }
        if (is_dir($from)) {
            if (!is_dir($to)) {
                mkdir($to, 0755, true);
            }
            copy_update_files($from, $to);
        } else {
            copy($from, $to);
        }
    }
}

line_out('开始更新 ' . JMWEB_NAME);

if (!function_exists('exec')) {
    line_out('服务器禁用了 exec，无法自动拉取 GitHub。');
    exit(1);
}

$workdir = JMWEB_UPDATE_WORKDIR;
$repo = JMWEB_UPDATE_REPO;
$site = JMWEB_SITE_DIR;

if (!is_dir(dirname($workdir))) {
    mkdir(dirname($workdir), 0755, true);
}

if (!is_dir($workdir . DIRECTORY_SEPARATOR . '.git')) {
    line_out('首次克隆仓库：' . $repo);
    $code = run_cmd('git clone ' . escapeshellarg($repo) . ' ' . escapeshellarg($workdir));
    if ($code !== 0) {
        line_out('克隆失败，请确认宝塔服务器已安装 Git，且仓库地址正确。');
        exit($code);
    }
} else {
    line_out('拉取最新代码');
    $code = run_cmd('git pull origin main', $workdir);
    if ($code !== 0) {
        line_out('拉取失败，请检查网络、分支和仓库权限。');
        exit($code);
    }
}

line_out('同步文件到网站目录');
copy_update_files($workdir, $site);
line_out('更新完成');
