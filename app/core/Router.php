<?php

class Router
{
    public function run(): void
    {
        // ================= 1. PARSE & SANITIZE URL =================
        $rawUrl = $_GET['url'] ?? 'auth/login';
        $url = trim($rawUrl, '/');
        $segments = explode('/', $url);

        // Lọc ký tự lạ, ngăn ngừa lỗi Directory Traversal
        $controllerSegment = preg_replace('/[^a-zA-Z0-9_-]/', '', $segments[0] ?? 'auth');
        $rawMethod         = preg_replace('/[^a-zA-Z0-9_]/', '', $segments[1] ?? 'index');
        $params            = array_slice($segments, 2);

        $controllerLower = strtolower($controllerSegment);
        $methodLower     = strtolower($rawMethod);

        // ================= 2. ĐÃ ĐĂNG NHẬP MÀ VÀO LOGIN -> REDIRECT VÀO APP =================
        if (isset($_SESSION['user']) && $controllerLower === 'auth' && in_array($methodLower, ['login', 'index', ''])) {
            header('Location: ' . BASE_URL . '/index.php?url=bobin/listBobinDetailView');
            exit;
        }

        // ================= 3. AUTH GUARD (BẢO MẬT & WHITELIST CÔNG KHAI) =================
        $isApi = $this->isApiRequest();

        // Danh sách các action cho phép truy cập công khai không cần đăng nhập
        $publicActions = [
            'bobin' => [
                'listBobinDetailView',         // Xem danh sách chi tiết
                'listBobinHistoryView',        // Xem lịch sử hoạt động
                'listPendingCancellationView', // Xem danh sách chờ hủy
                'exportDetailExcel',           // Xuất file Excel chi tiết
                'exportHistoryExcel'           // Xuất file Excel lịch sử
            ]
        ];

        // Kiểm tra xem URL hiện tại có thuộc diện được mở công khai hay không
        $isPublic = ($controllerLower === 'auth');
        if (isset($publicActions[$controllerLower])) {
            $allowedMethods = array_map('strtolower', $publicActions[$controllerLower]);
            if (in_array($methodLower, $allowedMethods, true)) {
                $isPublic = true;
            }
        }

        // Nếu chưa đăng nhập và không thuộc diện công khai -> Chặn lại
        if (!isset($_SESSION['user']) && !$isPublic) {
            if ($isApi) {
                http_response_code(401);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'success'  => false,
                    'error'    => 'Unauthorized',
                    'redirect' => BASE_URL . '/index.php?url=auth/login'
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }

            header('Location: ' . BASE_URL . '/index.php?url=auth/login');
            exit;
        }

        // ================= 4. CONTROLLER NAMING =================
        // Chuyển "list-data" hoặc "listdata" -> "ListDataController"
        $formattedName = str_replace(' ', '', ucwords(str_replace('-', ' ', $controllerSegment)));
        $controllerName = $formattedName . 'Controller';
        $controllerFile = ROOT_PATH . "/app/controllers/{$controllerName}.php";

        // ================= 5. KIỂM TRA FILE CONTROLLER =================
        if (!file_exists($controllerFile)) {
            $this->sendError(404, "Không tìm thấy Controller: '{$controllerName}'");
            return;
        }

        require_once $controllerFile;

        if (!class_exists($controllerName)) {
            $this->sendError(500, "Lỗi hệ thống: Class '{$controllerName}' không tồn tại trong file");
            return;
        }

        $instance = new $controllerName();

        // ================= 6. KIỂM TRA METHOD =================
        // Chặn gọi các magic method (bắt đầu bằng __) và phương thức private/protected
        if (str_starts_with($rawMethod, '__') || !is_callable([$instance, $rawMethod])) {
            $this->sendError(404, "Hành động (Method) '{$rawMethod}' không tồn tại hoặc không được cấp quyền trong {$controllerName}");
            return;
        }

        // ================= 7. THỰC THI PHƯƠNG THỨC =================
        call_user_func_array([$instance, $rawMethod], $params);
    }

    /**
     * Nhận diện request API từ AJAX, Fetch API hoặc Axios
     */
    private function isApiRequest(): bool
    {
        // 1. Kiểm tra header XMLHttpRequest truyền thống
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            return true;
        }

        // 2. Kiểm tra header Accept mong muốn nhận JSON (chuẩn của fetch)
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        if (str_contains($accept, 'application/json')) {
            return true;
        }

        // 3. Kiểm tra Content-Type gửi lên là JSON
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (str_contains($contentType, 'application/json')) {
            return true;
        }

        return false;
    }

    /**
     * Trả về lỗi tương ứng theo định dạng JSON (với API) hoặc HTML (với trình duyệt)
     */
    private function sendError(int $code, string $message): void
    {
        http_response_code($code);

        if ($this->isApiRequest()) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'code'    => $code,
                'error'   => $message
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Trả về giao diện lỗi HTML gọn gàng
        echo "<!DOCTYPE html>
        <html lang='vi'>
        <head>
            <meta charset='UTF-8'>
            <title>Lỗi {$code}</title>
            <style>
                body { font-family: system-ui, -apple-system, sans-serif; background: #f8fafc; color: #334155; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; }
                .box { background: #fff; padding: 36px 48px; border-radius: 14px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); text-align: center; max-width: 480px; }
                h1 { font-size: 48px; color: #ef4444; margin: 0 0 12px; }
                p { font-size: 15px; line-height: 1.5; color: #64748b; margin-bottom: 24px; }
                a { display: inline-block; background: #2563eb; color: #fff; text-decoration: none; padding: 10px 22px; border-radius: 8px; font-weight: 600; }
                a:hover { background: #1d4ed8; }
            </style>
        </head>
        <body>
            <div class='box'>
                <h1>{$code}</h1>
                <p>" . htmlspecialchars($message) . "</p>
                <a href='" . BASE_URL . "/index.php'>Quay về trang chính</a>
            </div>
        </body>
        </html>";
        exit;
    }
}
