<?php

// Shared Rhymix session bridge
$authBridge = '/www/fun/common/rhymix_bridge.php';
if (file_exists($authBridge)) {
    require_once $authBridge;
}

require_once __DIR__ . '/session.php';
require_once __DIR__ . '/consent.php';
require_once __DIR__ . '/routes.php';

$serviceConfig = require __DIR__ . '/../config/service.php';
$session = FunSession::resolve();
$cookieConsent = FunConsent::resolve($session);

