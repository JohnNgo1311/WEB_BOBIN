<?php
require_once ROOT_PATH . '/app/core/AuthHelper.php';

class Router
{
    public function run(): void
    {
        // ================= 1. PARSE URL =================
        $rawUrl = $_GET['url'] ?? 'auth/login';
        $url = trim($rawUrl, '/');
        $segments = explode('/', $url);

        $controllerSegment = preg_replace('/[^a-zA-Z0-9_-]/', '', $segments[0] ?? 'auth');
        $rawMethod         = preg_replace('/[^a-zA-Z0-9_]/', '', $segments[1] ?? 'index');
        $params            = array_slice($segments, 2);

        $controllerLower = strtolower($controllerSegment);
        $methodLower     = strtolower($rawMethod);

        // ================= 2. VÀO TRANG LOGIN -> RESET SESSION =================
        if ($controllerLower === 'auth' && in_array($methodLower, ['login', 'index', ''])) {
            header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
            header("Pragma: no-cache");

            if (session_status() === PHP_SESSION_ACTIVE) {
                $_SESSION = [];
                if (ini_get("session.use_cookies")) {
                    $cookieParams = session_get_cookie_params();
                    setcookie(session_name(), '', [
                        'expires'  => time() - 42000,
                        'path'     => $cookieParams['path'] ?? '/',
                        'domain'   => $cookieParams['domain'] ?? '',
                        'secure'   => (bool)($cookieParams['secure'] ?? false),
                        'httponly' => (bool)($cookieParams['httponly'] ?? true),
                        'samesite' => $cookieParams['samesite'] ?? 'Lax'
                    ]);
                }
                session_destroy();
            }
            session_start();
            session_regenerate_id(true);
        }

        // ================= 3. DANH MỤC CÔNG KHAI (KHÔNG CẦN LOGIN) =================
        $publicActions = [
            'auth' => ['login', 'validatelogin', 'logout', 'index', 'changepassword', 'postchangepassword', 'setlanguage'],
            'listdata' => ['getlistdata', 'index'], // API nạp danh mục gợi ý máy, nhân viên...
            'bobin' => [
                'listbobindetailview',         // Xem chi tiết danh sách
                'listbobinhistoryview',        // Xem lịch sử hoạt động
                'listpendingcancellationview', // Xem danh sách chờ hủy
                'exportdetailexcel',           // Xuất file Excel chi tiết
                'exporthistoryexcel',          // Xuất file Excel lịch sử
                'getspecificbobin'
            ]
        ];

        // Quyền hạn nghiệp vụ riêng theo từng Role
        $rolePermissions = [
            'extrusion' => [
                'index',
                'extrusion',
                'createbobinview',
                'extrusioneditbobinview',
                'createbobin',
                'extrusionupdatebobin',
                'extrusiondeletebobin'
            ],
            'qc' => [
                'index',
                'listbobinview_qc',
                'qceditbobinview',
                'updateqceditbobin',
                'updateqcbobin',
                'qccancelbobin',
                'changebobintype',
                'updateqcandchangetype'
            ],
            'winding' => [
                'index',
                'listbobinview_winding',
                'updatewindingbobin',
                'windingcancelbobin'
            ],
            'admin' => [
                // Admin toàn quyền mọi module, bao gồm xóa hoàn tất Bobin
                'deletebobin',
                'qceditbobinview',
                'updateqceditbobin',
                'windingeditbobinview',
                'updatewindingeditbobin'
            ]
        ];

        $isPublic = false;
        if (isset($publicActions[$controllerLower])) {
            $isPublic = in_array($methodLower, $publicActions[$controllerLower], true);
        }

        $isApi = $this->isApiRequest($controllerLower);

        // ================= 4. KIỂM TRA ĐĂNG NHẬP =================
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

        // ================= 4.1. KIỂM TRA BẮT BUỘC ĐỔI MẬT KHẨU LẦN ĐẦU =================
        if (isset($_SESSION['user']) && !empty($_SESSION['user']['is_first_login'])) {
            $isChangePwdAction = ($controllerLower === 'auth' && in_array($methodLower, ['changepassword', 'postchangepassword', 'logout', 'setlanguage'], true));
            if (!$isChangePwdAction) {
                if ($isApi) {
                    http_response_code(403);
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode([
                        'success' => false,
                        'error'   => 'Bạn bắt buộc phải đổi mật khẩu trong lần đăng nhập đầu tiên.',
                        'must_change_password' => true
                    ], JSON_UNESCAPED_UNICODE);
                    exit;
                }

                header('Location: ' . BASE_URL . '/index.php?url=auth/changePassword&first_login=1');
                exit;
            }
        }

        // ================= 5. KIỂM TRA PHÂN QUYỀN (PERMISSIONS & ROLE) =================
        if (isset($_SESSION['user']) && !$isPublic) {
            $userRole = strtolower($_SESSION['user']['role'] ?? '');

            // Kiểm tra phân quyền truy cập chi tiết thông qua AuthHelper (tương thích quyền tùy biến & quyền mặc định)
            $isAllowed = AuthHelper::isActionAllowed($controllerLower, $methodLower, $_SESSION['user']);

            if (!$isAllowed) {
                if ($isApi) {
                    http_response_code(403);
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode([
                        'success' => false,
                        'error'   => 'Bạn không có quyền thực hiện thao tác này.'
                    ], JSON_UNESCAPED_UNICODE);
                    exit;
                }

                // Chuyển hướng người dùng về trang mặc định phù hợp
                $redirectUrl = match ($userRole) {
                    'extrusion' => BASE_URL . '/index.php?url=bobin/index',
                    'qc'        => BASE_URL . '/index.php?url=bobin/listBobinView_QC',
                    'winding'   => BASE_URL . '/index.php?url=bobin/windingView',
                    default     => BASE_URL . '/index.php?url=bobin/listBobinDetailView',
                };

                header("Location: {$redirectUrl}");
                exit;
            }
        }

        // ================= 6. GỌI CONTROLLER & METHOD =================
        $formattedName = str_replace(' ', '', ucwords(str_replace('-', ' ', $controllerSegment)));
        if (strtolower($formattedName) === 'listdata') {
            $formattedName = 'ListData';
        }

        $controllerName = $formattedName . 'Controller';
        $controllerFile = ROOT_PATH . "/app/controllers/{$controllerName}.php";

        if (!file_exists($controllerFile)) {
            $this->sendError(404, "Không tìm thấy Controller: '{$controllerName}'");
            return;
        }

        require_once $controllerFile;

        if (!class_exists($controllerName)) {
            $this->sendError(500, "Lỗi hệ thống: Class '{$controllerName}' không tồn tại");
            return;
        }

        $instance = new $controllerName();

        if (str_starts_with($rawMethod, '__') || !is_callable([$instance, $rawMethod])) {
            $this->sendError(404, "Hành động '{$rawMethod}' không tồn tại trong {$controllerName}");
            return;
        }

        call_user_func_array([$instance, $rawMethod], $params);
    }

    private function isApiRequest(string $controller = ''): bool
    {
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            return true;
        }

        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (str_contains($accept, 'application/json') || str_contains($contentType, 'application/json')) {
            return true;
        }

        if ($controller === 'listdata') {
            return true;
        }

        return false;
    }

    private function sendError(int $code, string $message): void
    {
        http_response_code($code);

        if ($this->isApiRequest()) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'error' => $message], JSON_UNESCAPED_UNICODE);
            exit;
        }

        echo "<!DOCTYPE html>
        <html lang='vi'>
        <head><meta charset='UTF-8'><title>Lỗi {$code}</title></head>
        <body style='font-family:sans-serif; text-align:center; padding-top:50px;'>
            <h1>Lỗi {$code}</h1>
            <p>" . htmlspecialchars($message) . "</p>
            <a href='" . BASE_URL . "/index.php'>Quay về trang chính</a>
        </body>
        </html>";
        exit;
    }
}
