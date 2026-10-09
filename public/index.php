<?php
// ==========================================
// 1. CẤU HÌNH MÔI TRƯỜNG & HIỂN THỊ LỖI
// ==========================================
define('ENVIRONMENT', 'development'); // Đổi thành 'production' khi triển khai chính thức

if (ENVIRONMENT === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
}

// ==========================================
// 2. KHỞI TẠO ĐƯỜNG DẪN HỆ THỐNG
// ==========================================
define('ROOT_PATH', dirname(__DIR__));

// Tự động nhận diện BASE_URL linh hoạt theo thư mục cài đặt thực tế
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
define('BASE_URL', rtrim($protocol . $_SERVER['HTTP_HOST'] . $scriptDir, '/'));

// ==========================================
// 3. THIẾT LẬP SESSION BẢO MẬT
// ==========================================
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_samesite', 'Lax');
    session_start();
}

// ==========================================
// 4. NẠP CORE & DATABASE & I18N
// ==========================================
require_once ROOT_PATH . '/config/database.php';
require_once ROOT_PATH . '/app/core/GlobalData.php';
require_once ROOT_PATH . '/app/core/Language.php';
Language::init();
require_once ROOT_PATH . '/app/core/Router.php';

// ==========================================
// 5. KHỞI CHẠY BỘ ĐỊNH TUYẾN
// ==========================================
$router = new Router();
$router->run();
