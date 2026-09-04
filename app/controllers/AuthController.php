<?php

require_once ROOT_PATH . '/app/core/Controller.php';
require_once ROOT_PATH . '/app/core/GlobalData.php';


class AuthController extends Controller
{
    public function login()
    {
        $this->view('loginView');
    }
    public function validateLogin()
    {
        // 1. Lấy dữ liệu và CHUẨN HÓA ngay lập tức
        // - trim: Xóa khoảng trắng thừa (tránh lỗi copy paste)
        // - strtolower: Chuyển hết thành chữ thường để so sánh
        $usernameInput = $_POST['username'] ?? '';
        $username = strtolower(trim($usernameInput));

        $password = $_POST['password'] ?? '';

        //? giả lập dữ liệu người dùng (Key phải viết thường)
        $users = [
            'ext' => ['pass' => '123', 'role' => 'extrusion'],
            'qc'  => ['pass' => '123', 'role' => 'qc'],
            'win' => ['pass' => '123', 'role' => 'winding'],
            'mgr' => ['pass' => '123', 'role' => 'manager'],
            'admin' => ['pass' => '123', 'role' => 'admin']
        ];

        //? kiểm tra
        // Lúc này $username đã là chữ thường, nên sẽ khớp với key trong mảng $users
        if (!isset($users[$username]) || $users[$username]['pass'] !== $password) {
            // Lưu ý: Password vẫn phân biệt hoa thường (bảo mật)
            return $this->view('loginView', ['error' => 'Sai tài khoản hoặc mật khẩu']);
        }

        //? lưu session (người dùng)
        $_SESSION['user'] = [
            'username' => $username, // Lưu username đã chuẩn hóa (ext, qc...)
            'role' => $users[$username]['role']
        ];

        // Cập nhật biến Global (nếu logic của bạn cần)
        GlobalData::$userRole = $_SESSION['user']['role'];
        GlobalData::$userName = $_SESSION['user']['username'];

        //? chuyển hướng
        // Chuyển hướng về Controller, Controller sẽ tự định tuyến dựa trên Role như bạn đã viết ở hàm index()
        header('Location: index.php?url=bobin/index', true, 302);
        exit;
    }
    //? đăng xuất
    public function logout()
    {
        session_destroy();
        header('Location: index.php?url=auth/login');
        exit;
    }
}