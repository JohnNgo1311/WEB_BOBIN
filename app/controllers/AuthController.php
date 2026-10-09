<?php

require_once ROOT_PATH . '/app/core/Controller.php';

class AuthController extends Controller
{
    public function login(): void
    {
        $this->view('loginView');
    }

    public function index(): void
    {
        $this->login();
    }

    public function validateLogin(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '/index.php?url=auth/login');
            exit;
        }

        $loginInput = trim($_POST['username'] ?? ''); // Cho phép nhập mã nhân viên hoặc username
        $password   = trim($_POST['password'] ?? '');

        if ($loginInput === '' || $password === '') {
            $this->redirectWithError('Vui lòng nhập đầy đủ mã nhân viên/tài khoản và mật khẩu.');
            return;
        }

        try {
            $pdo = Database::getInstance()->pdo();

            $stmt = $pdo->prepare("SELECT id, employee_code, employee_name, cost_center, role, username, password, is_first_login, permissions 
                                   FROM employee_list 
                                   WHERE (employee_code = :emp_code OR username = :uname) 
                                   LIMIT 1");
            $stmt->execute([
                ':emp_code' => $loginInput,
                ':uname'    => $loginInput
            ]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            $isValid = false;
            if ($user) {
                if (password_verify($password, $user['password'])) {
                    $isValid = true;
                } elseif ($password === $user['password']) {
                    // Nếu là mật khẩu thuần, tự động nâng cấp sang bcrypt
                    $newHash = password_hash($password, PASSWORD_DEFAULT);
                    $up = $pdo->prepare("UPDATE employee_list SET password = :hash WHERE id = :id");
                    $up->execute([':hash' => $newHash, ':id' => $user['id']]);
                    $isValid = true;
                }
            }

            if (!$user || !$isValid) {
                $this->redirectWithError('Mã nhân viên / Tên đăng nhập hoặc mật khẩu không chính xác.');
                return;
            }

            $isFirstLogin = (int)($user['is_first_login'] ?? 1);
            $perms = !empty($user['permissions']) ? json_decode($user['permissions'], true) : null;

            // Lưu phiên làm việc vào Session
            $_SESSION['user'] = [
                'id'            => (int)$user['id'],
                'employee_code' => $user['employee_code'],
                'employee_name' => $user['employee_name'],
                'username'      => $user['username'],
                'role'          => strtolower($user['role']),
                'permissions'   => is_array($perms) ? $perms : null,
                'is_first_login'=> $isFirstLogin,
                'logged_at'     => date('Y-m-d H:i:s')
            ];

            // Nếu là lần đầu đăng nhập -> Bắt buộc chuyển hướng đến trang đổi mật khẩu
            if ($isFirstLogin === 1) {
                header('Location: ' . BASE_URL . '/index.php?url=auth/changePassword&first_login=1');
                exit;
            }

            // Tự động phân luồng điều hướng theo vai trò
            switch ($_SESSION['user']['role']) {
                case 'extrusion':
                    header('Location: ' . BASE_URL . '/index.php?url=bobin/index');
                    break;
                case 'qc':
                    header('Location: ' . BASE_URL . '/index.php?url=bobin/listBobinView_QC');
                    break;
                case 'winding':
                    header('Location: ' . BASE_URL . '/index.php?url=bobin/listBobinView_Winding');
                    break;
                case 'admin':
                default:
                    header('Location: ' . BASE_URL . '/index.php?url=bobin/listBobinDetailView');
                    break;
            }
            exit;
        } catch (Throwable $e) {
            error_log("Login Error: " . $e->getMessage());
            $this->redirectWithError('Lỗi kết nối cơ sở dữ liệu. Vui lòng thử lại sau. ' . $e->getMessage());
        }
    }

    public function changePassword(): void
    {
        if (!isset($_SESSION['user'])) {
            header('Location: ' . BASE_URL . '/index.php?url=auth/login');
            exit;
        }

        $user = $_SESSION['user'];
        $isFirstLogin = !empty($user['is_first_login']);
        $error = $_GET['error'] ?? null;
        $success = $_GET['success'] ?? null;

        $this->view('changePasswordView', [
            'user'         => $user,
            'isFirstLogin' => $isFirstLogin,
            'error'        => $error,
            'success'      => $success
        ]);
    }

    public function postChangePassword(): void
    {
        if (!isset($_SESSION['user'])) {
            if ($this->isAjax()) {
                $this->json(['success' => false, 'message' => 'Phiên đăng nhập đã hết hạn. Vui lòng đăng nhập lại.']);
            }
            header('Location: ' . BASE_URL . '/index.php?url=auth/login');
            exit;
        }

        $userId = (int)$_SESSION['user']['id'];
        $currentPassword = trim($_POST['current_password'] ?? '');
        $newPassword     = trim($_POST['new_password'] ?? '');
        $confirmPassword = trim($_POST['confirm_password'] ?? '');

        // 1. Kiểm tra không được để trống
        if ($currentPassword === '' || $newPassword === '' || $confirmPassword === '') {
            $msg = 'Vui lòng điền đầy đủ tất cả các trường mật khẩu.';
            if ($this->isAjax()) {
                $this->json(['success' => false, 'message' => $msg]);
            }
            $this->redirectChangePasswordWithError($msg);
            return;
        }

        // 2. Kiểm tra độ dài tối thiểu
        if (strlen($newPassword) < 6) {
            $msg = 'Mật khẩu mới phải có độ dài tối thiểu từ 6 ký tự trở lên.';
            if ($this->isAjax()) {
                $this->json(['success' => false, 'message' => $msg]);
            }
            $this->redirectChangePasswordWithError($msg);
            return;
        }

        // 3. Kiểm tra khớp mật khẩu xác nhận
        if ($newPassword !== $confirmPassword) {
            $msg = 'Mật khẩu xác nhận không trùng khớp với mật khẩu mới.';
            if ($this->isAjax()) {
                $this->json(['success' => false, 'message' => $msg]);
            }
            $this->redirectChangePasswordWithError($msg);
            return;
        }

        // 4. Kiểm tra mật khẩu mới không được trùng mật khẩu cũ
        if ($newPassword === $currentPassword) {
            $msg = 'Mật khẩu mới không được trùng với mật khẩu hiện tại.';
            if ($this->isAjax()) {
                $this->json(['success' => false, 'message' => $msg]);
            }
            $this->redirectChangePasswordWithError($msg);
            return;
        }

        // 5. Cấm các mật khẩu quá đơn giản / mặc định
        $forbiddenPasswords = [
            '123', '123456', 'password', 'admin', '12345678',
            strtolower($_SESSION['user']['employee_code'] ?? ''),
            strtolower($_SESSION['user']['username'] ?? '')
        ];
        if (in_array(strtolower($newPassword), $forbiddenPasswords, true)) {
            $msg = 'Mật khẩu mới quá đơn giản hoặc trùng với mã nhân viên/tài khoản. Vui lòng chọn mật khẩu an toàn hơn.';
            if ($this->isAjax()) {
                $this->json(['success' => false, 'message' => $msg]);
            }
            $this->redirectChangePasswordWithError($msg);
            return;
        }

        try {
            $pdo = Database::getInstance()->pdo();
            $stmt = $pdo->prepare("SELECT id, password, is_first_login FROM employee_list WHERE id = :id LIMIT 1");
            $stmt->execute([':id' => $userId]);
            $dbUser = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$dbUser) {
                $msg = 'Không tìm thấy thông tin tài khoản hoặc tài khoản đã bị khóa.';
                if ($this->isAjax()) {
                    $this->json(['success' => false, 'message' => $msg]);
                }
                $this->redirectChangePasswordWithError($msg);
                return;
            }

            // 6. Kiểm tra mật khẩu hiện tại
            $isCurrentValid = false;
            if (password_verify($currentPassword, $dbUser['password'])) {
                $isCurrentValid = true;
            } elseif ($currentPassword === $dbUser['password']) {
                $isCurrentValid = true;
            }

            if (!$isCurrentValid) {
                $msg = 'Mật khẩu hiện tại không chính xác. Vui lòng kiểm tra lại.';
                if ($this->isAjax()) {
                    $this->json(['success' => false, 'message' => $msg]);
                }
                $this->redirectChangePasswordWithError($msg);
                return;
            }

            // 7. Hash mật khẩu mới và cập nhật database
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $updateStmt = $pdo->prepare("UPDATE employee_list SET password = :pwd, is_first_login = 0, updated_time = NOW() WHERE id = :id");
            $updateStmt->execute([
                ':pwd' => $hashedPassword,
                ':id'  => $userId
            ]);

            // 8. Cập nhật Session
            $_SESSION['user']['is_first_login'] = 0;

            // Xác định trang đích điều hướng theo vai trò
            $redirectUrl = match ($_SESSION['user']['role']) {
                'extrusion' => BASE_URL . '/index.php?url=bobin/index',
                'qc'        => BASE_URL . '/index.php?url=bobin/listBobinView_QC',
                'winding'   => BASE_URL . '/index.php?url=bobin/listBobinView_Winding',
                default     => BASE_URL . '/index.php?url=bobin/listBobinDetailView',
            };

            if ($this->isAjax()) {
                $this->json([
                    'success'  => true,
                    'message'  => 'Đổi mật khẩu thành công! Đang chuyển hướng...',
                    'redirect' => $redirectUrl
                ]);
            }

            header('Location: ' . $redirectUrl);
            exit;
        } catch (Throwable $e) {
            error_log("Change Password Error: " . $e->getMessage());
            $msg = 'Lỗi hệ thống khi cập nhật mật khẩu: ' . $e->getMessage();
            if ($this->isAjax()) {
                $this->json(['success' => false, 'message' => $msg]);
            }
            $this->redirectChangePasswordWithError($msg);
        }
    }

    private function isAjax(): bool
    {
        return (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
            || (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false);
    }

    private function redirectChangePasswordWithError(string $msg): void
    {
        header('Location: ' . BASE_URL . '/index.php?url=auth/changePassword&error=' . urlencode($msg));
        exit;
    }

    public function setLanguage(): void
    {
        $lang = $_GET['lang'] ?? ($_POST['lang'] ?? 'vi');
        Language::set($lang);

        if ($this->isAjax()) {
            $this->json([
                'success' => true,
                'lang'    => Language::getCurrent(),
                'info'    => Language::getCurrentInfo()
            ]);
        }

        $referer = $_SERVER['HTTP_REFERER'] ?? (BASE_URL . '/index.php');
        header('Location: ' . $referer);
        exit;
    }

    public function logout(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = [];
            session_destroy();
        }
        header('Location: ' . BASE_URL . '/index.php?url=auth/login');
        exit;
    }

    private function redirectWithError(string $msg): void
    {
        header('Location: ' . BASE_URL . '/index.php?url=auth/login&error=' . urlencode($msg));
        exit;
    }
}
