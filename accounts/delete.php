<?php
require_once __DIR__ . '/endpoint.php';
accounts_require_method(['POST']);

$_GET['action'] = 'delete';
define('ACCOUNTS_API_ENTRY', true);
require __DIR__ . '/service.php';
