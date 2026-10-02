<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
$_SESSION = [];
session_destroy();
header('Location: ' . url('index.php'));
exit;
