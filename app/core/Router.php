<?php

class Router
{
    public function run(): void
    {
        // ================= 1. SESSION START =================
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // ================= 2. PARSE URL =================
        $url = $_GET['url'] ?? 'auth/login';
        $url = trim($url, '/');
        $segments = explode('/', $url);

        // Lấy tên controller (Mặc định là auth)
        $controllerSegment = $segments[0] ?? 'auth';
        $method = $segments[1] ?? 'index';
        $params = array_slice($segments, 2);

        // ================= 3. AUTH GUARD (BẢO MẬT) =================
        // Logic: Chỉ cho phép 'auth' controller là công khai.
        // Tất cả controller khác (bao gồm cả API bobin, listdata) BẮT BUỘC phải có Session.
        // Vì Browser tự gửi Cookie khi fetch, nên API vẫn hoạt động bình thường với user đã đăng nhập.

        if (!isset($_SESSION['user']) && strtolower($controllerSegment) !== 'auth') {
            // Nếu là gọi API (AJAX) mà chưa login -> Trả về lỗi 401 thay vì redirect HTML
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                http_response_code(401);
                echo json_encode(['error' => 'Unauthorized', 'redirect' => '/WEB_BOBIN/public/index.php?url=auth/login']);
                exit;
            }

            // Nếu truy cập thường -> Chuyển về trang login
            header('Location: /WEB_BOBIN/public/index.php?url=auth/login');
            exit;
        }

        // ================= 4. CONTROLLER NAMING FIX =================
        // Xử lý tên: "list-data" hoặc "listdata" -> thành "ListDataController"
        // Logic: Chuyển dấu gạch ngang thành khoảng trắng, viết hoa chữ cái đầu mỗi từ, rồi xóa khoảng trắng.
        // Ví dụ: "list-data" -> "List Data" -> "ListData"
        // Ví dụ: "bobin" -> "Bobin"

        $formattedName = str_replace(' ', '', ucwords(str_replace('-', ' ', $controllerSegment)));

        // Fix cứng cho trường hợp đặc biệt nếu lỡ đặt tên không chuẩn (Optional)
        // if (strtolower($formattedName) === 'listdata') {
        //     $formattedName = 'ListData'; // Ép buộc chữ D hoa để khớp với file Model/Controller của bạn
        // }

        $controllerName = $formattedName . 'Controller';
        $controllerFile = ROOT_PATH . "/app/controllers/$controllerName.php";

        // ================= 5. CHECK FILE & INSTANTIATE =================
        if (!file_exists($controllerFile)) {
            $this->sendError(404, "Controller '$controllerName' not found");
            return;
        }

        require_once $controllerFile;

        if (!class_exists($controllerName)) {
            $this->sendError(500, "Class '$controllerName' does not exist in file");
            return;
        }

        $instance = new $controllerName;

        // ================= 6. CHECK METHOD =================
        if (!method_exists($instance, $method)) {
            $this->sendError(404, "Method '$method' not found in $controllerName");
            return;
        }

        // ================= 7. CALL METHOD =================
        // Dùng call_user_func_array để truyền tham số
        call_user_func_array([$instance, $method], $params);
    }

    // Hàm helper để trả về lỗi JSON chuẩn
    private function sendError($code, $message)
    {
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => $message]);
        exit;
    }
}
