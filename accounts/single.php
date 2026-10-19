<?php
require_once __DIR__ . '/endpoint.php';
accounts_require_method(['GET']);

$_GET['action'] = 'single';
define('ACCOUNTS_API_ENTRY', true);
require __DIR__ . '/service.php';
