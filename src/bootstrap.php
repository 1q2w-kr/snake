<?php

// Shared Rhymix session bridge
$authBridge = '/www/fun/common/rhymix_bridge.php';
if (file_exists($authBridge)) {
    require_once $authBridge;
}

// Fun service guard
$guardPaths = [
    '/www/fun/common/service/guard.php',
    __DIR__ . '/../../common/service/guard.php',
];
foreach ($guardPaths as $path) {
    if (file_exists($path)) {
        require_once $path;
        break;
    }
}
if (function_exists('fun_service_require_enabled')) {
    fun_service_require_enabled('snake');
}

// DB connection (shared helper)
$FUN_DB_ENV_PREFIX = 'SNAKE';
$dbConfigPaths = [
    '/www/fun/common/db.php',
    __DIR__ . '/../../common/db.php',
];
foreach ($dbConfigPaths as $path) {
    if (file_exists($path)) {
        require_once $path;
        break;
    }
}

require_once __DIR__ . '/session.php';
require_once __DIR__ . '/consent.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/routes.php';

$serviceConfig = require __DIR__ . '/../config/service.php';
$session = FunSession::resolve();
$cookieConsent = FunConsent::resolve($session);

// Optional: auto-init schema if DB is reachable and table missing
if (isset($conn) && $conn instanceof mysqli) {
    snakeEnsureSchema($conn);
}
