<?php

/**
 * Security bootstrap for WAF engines.
 *
 * Priority:
 * 1) NinjaFirewall WP Edition (only if WordPress context exists).
 * 2) BitFire startup (plain PHP fallback).
 */
if (!defined('APP_SECURITY_BOOTSTRAPPED')) {
    define('APP_SECURITY_BOOTSTRAPPED', true);

    $projectRoot = dirname(__DIR__);
    $configFile = $projectRoot . '/config/security.php';
    $securityConfig = file_exists($configFile) ? require $configFile : [];
    $engine = isset($securityConfig['engine']) ? strtolower((string) $securityConfig['engine']) : 'auto';

    $ninjaWpEntry = $projectRoot . '/ninjafirewall/ninjafirewall.php';
    $hasWordPressContext = defined('ABSPATH') && function_exists('add_action');
    $bitfireStartup = $projectRoot . '/bitfire-1.8.1/startup.php';

    if ($engine === 'off') {
        return;
    }

    if ($engine === 'bitfire') {
        if (file_exists($bitfireStartup)) {
            require_once $bitfireStartup;
        }
        return;
    }

    if ($engine === 'phpids') {
        if (isset($securityConfig['phpids_impact_threshold'])) {
            define('APP_PHPIDS_IMPACT_THRESHOLD', (int) $securityConfig['phpids_impact_threshold']);
        }
        require_once __DIR__ . '/phpids_bootstrap.php';
        return;
    }

    if ($engine === 'ninja') {
        if ($hasWordPressContext && file_exists($ninjaWpEntry)) {
            require_once $ninjaWpEntry;
        } else {
            error_log('SECURITY_BOOTSTRAP: Ninja mode selected but WordPress context is missing.');
        }
        return;
    }

    // auto mode: Ninja only in WP context, otherwise BitFire fallback.
    if ($hasWordPressContext && file_exists($ninjaWpEntry)) {
        require_once $ninjaWpEntry;
        return;
    }

    if (file_exists($bitfireStartup)) {
        require_once $bitfireStartup;
    }
}
