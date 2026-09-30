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

            // SỬA TẠI ĐÂY: Dùng 2 placeholder riêng (:emp_code và :uname)
            $stmt = $pdo->prepare("SELECT id, employee_code, employee_name, role, username, password, is_active 
                                   FROM employee_list 
                                   WHERE (employee_code = :emp_code OR username = :uname) AND is_active = 1 
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

            // Lưu phiên làm việc vào Session
            $_SESSION['user'] = [
                'id'            => (int)$user['id'],
                'employee_code' => $user['employee_code'],
                'employee_name' => $user['employee_name'],
                'username'      => $user['username'],
                'role'          => strtolower($user['role']),
                'logged_at'     => date('Y-m-d H:i:s')
            ];

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
