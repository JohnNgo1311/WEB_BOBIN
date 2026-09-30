<?php
// ==========================================
// 1. CẤU HÌNH MÔI TRƯỜNG & HIỂN THỊ LỖI
// ==========================================
define('ENVIRONMENT', 'development'); // Đổi thành 'production' khi đưa lên server thực tế

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

// Tự động nhận diện BASE_URL linh hoạt (không sợ đổi tên thư mục hay đổi domain)
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
define('BASE_URL', rtrim($protocol . $_SERVER['HTTP_HOST'] . $scriptDir, '/'));

// ==========================================
// 3. THIẾT LẬP SESSION AN TOÀN
// ==========================================
if (session_status() === PHP_SESSION_NONE) {
    // Ngăn chặn Javascript truy cập vào Session Cookie (chống XSS)
    ini_set('session.cookie_httponly', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_samesite', 'Lax');

    session_start();
}

// ==========================================
// 4. NẠP CÁC FILE CỐT LÕI
// ==========================================
require_once ROOT_PATH . '/config/database.php';
require_once ROOT_PATH . '/app/core/GlobalData.php';
require_once ROOT_PATH . '/app/core/Router.php';

// ==========================================
// 5. KHỞI CHẠY BỘ ĐỊNH TUYẾN
// ==========================================
$router = new Router();
$router->run();
