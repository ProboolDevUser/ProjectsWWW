<?php
declare(strict_types=1);

$cfg = require __DIR__ . '/config.php';
date_default_timezone_set($cfg['app']['tz']);

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
