<?php
declare(strict_types=1);

$config = require __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

require_https();
ensure_session($config);
logout();

header('Location: /control-room/login.php');
exit;
