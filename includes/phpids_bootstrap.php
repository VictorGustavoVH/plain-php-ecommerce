<?php

if (defined('APP_PHPIDS_BOOTSTRAPPED')) {
    return;
}
define('APP_PHPIDS_BOOTSTRAPPED', true);

if (PHP_SAPI === 'cli') {
    return;
}

$projectRoot = dirname(__DIR__);
$phpidsLibDir = $projectRoot . '/phpids/lib';
$phpidsConfigFile = $phpidsLibDir . '/IDS/Config/Config.ini.php';
$tmpDir = $projectRoot . '/tmp/phpids';
$logDir = $projectRoot . '/tmp/security';
$logFile = $logDir . '/phpids.log';
$threshold = defined('APP_PHPIDS_IMPACT_THRESHOLD') ? (int) APP_PHPIDS_IMPACT_THRESHOLD : 10;

if (!is_dir($tmpDir)) {
    @mkdir($tmpDir, 0777, true);
}
if (!is_dir($logDir)) {
    @mkdir($logDir, 0777, true);
}

if (!is_file($phpidsConfigFile)) {
    error_log('PHPIDS bootstrap skipped: missing config file.');
    return;
}

spl_autoload_register(static function ($class) use ($phpidsLibDir) {
    $prefix = 'IDS\\';
    if (strpos($class, $prefix) !== 0) {
        return;
    }

    $relative = substr($class, strlen($prefix));
    $path = $phpidsLibDir . '/IDS/' . str_replace('\\', '/', $relative) . '.php';
    if (is_file($path)) {
        require_once $path;
    }
});

try {
    $init = \IDS\Init::init($phpidsConfigFile);
    $init->setConfig([
        'General' => [
            'use_base_path' => false,
            'filter_path' => $phpidsLibDir . '/IDS/default_filter.xml',
            'tmp_path' => $tmpDir,
        ],
        'Caching' => [
            'caching' => 'file',
            'path' => $tmpDir . '/default_filter.cache',
        ],
    ], true);

    $requestData = array_merge($_GET, $_POST, $_COOKIE);
    $monitor = new \IDS\Monitor($init);
    $result = $monitor->run($requestData);

    if (!$result->isEmpty()) {
        $impact = (int) $result->getImpact();
        $tags = implode(',', $result->getTags());
        $requestUri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
        $remoteAddr = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
        $line = sprintf(
            "[%s] ip=%s impact=%d tags=%s uri=%s\n",
            date('Y-m-d H:i:s'),
            $remoteAddr,
            $impact,
            $tags,
            $requestUri
        );
        @file_put_contents($logFile, $line, FILE_APPEND);

        if ($impact >= $threshold) {
            header('HTTP/1.1 403 Forbidden');
            exit('Request blocked by PHPIDS');
        }
    }
} catch (\Throwable $e) {
    error_log('PHPIDS bootstrap error: ' . $e->getMessage());
}

