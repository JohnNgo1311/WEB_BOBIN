<?php
// File: index.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

define('ROOT_PATH', dirname(__DIR__));

require_once ROOT_PATH . '/config/database.php';
require_once ROOT_PATH . '/app/core/Router.php';

// ✅ THÊM DÒNG NÀY (Để nạp class lấy dữ liệu)
require_once ROOT_PATH . '/app/core/GlobalData.php';

session_start();

$router = new Router();
$router->run();
