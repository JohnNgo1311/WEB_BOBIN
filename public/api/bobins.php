<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Nạp core
require_once __DIR__ . '/../app/core/Router.php';

// Khởi động router
$router = new Router();