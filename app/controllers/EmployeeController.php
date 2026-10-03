<?php
require_once ROOT_PATH . '/app/core/Controller.php';
require_once ROOT_PATH . '/app/models/EmployeeModel.php';
require_once ROOT_PATH . '/app/repositories/ListDataRepository.php';

class EmployeeController extends Controller
{
    private EmployeeModel $model;

    public function __construct()
    {
        // ================= KIỂM TRA QUYỀN ADMIN =================
        if (!isset($_SESSION['user']) || strtolower($_SESSION['user']['role'] ?? '') !== 'admin') {
            if ($this->isAjax()) {
                $this->json([
                    'success' => false,
                    'error'   => 'Quyền truy cập bị từ chối. Chỉ Quản trị viên (Admin) mới có quyền sử dụng chức năng này.'
                ], 403);
            }

            header('Location: ' . BASE_URL . '/index.php?url=bobin/listBobinDetailView&error=' . urlencode('Bạn không có quyền truy cập trang Quản lý nhân viên.'));
            exit;
        }

        $this->model = new EmployeeModel();
    }

    /**
     * 1. Tra cứu danh sách nhân viên
     */
    public function index(): void
    {
        try {
            $listRepo = new ListDataRepository();
            $listRepo->getListData(); // Nạp danh mục phụ (nếu có)
        } catch (Throwable $e) {
            // Không chặn trang nếu có lỗi nạp danh mục phụ
        }

        $filters = [
            'keyword' => trim($_GET['keyword'] ?? ''),
            'role'    => trim($_GET['role'] ?? 'all'),
            'status'  => trim($_GET['status'] ?? 'all'),
        ];

        $employees = $this->model->getAll($filters);
        $stats     = $this->model->getStats();

        $this->view('employeeListView', [
            'employees' => $employees,
            'stats'     => $stats,
            'filters'   => $filters,
            'userRole'  => $_SESSION['user']['role'] ?? '',
            'userName'  => $_SESSION['user']['employee_name'] ?? ''
        ]);
    }

    /**
     * 2. Thêm mới nhân viên
     */
    public function create(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirectIndex('Phương thức không hợp lệ.');
            return;
        }

        try {
            $data = [
                'employee_code'  => trim($_POST['employee_code'] ?? ''),
                'employee_name'  => trim($_POST['employee_name'] ?? ''),
                'role'           => trim($_POST['role'] ?? 'extrusion'),
                'username'       => trim($_POST['username'] ?? ''),
                'password'       => trim($_POST['password'] ?? '123'),
                'is_active'      => isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1,
                'is_first_login' => 1 // Luôn bắt buộc đổi MK ở lần đầu đăng nhập
            ];

            if ($data['employee_code'] === '' || $data['employee_name'] === '') {
                throw new Exception('Vui lòng nhập đầy đủ Mã nhân viên và Họ tên.');
            }

            if ($data['username'] === '') {
                $data['username'] = $data['employee_code'];
            }

            $newId = $this->model->create($data);

            if ($this->isAjax()) {
                $this->json([
                    'success' => true,
                    'message' => 'Thêm nhân viên mới thành công!',
                    'id'      => $newId
                ]);
            }

            $this->redirectIndex('Thêm nhân viên mới thành công!', 'success');
        } catch (Throwable $e) {
            if ($this->isAjax()) {
                $this->json(['success' => false, 'error' => $e->getMessage()]);
            }
            $this->redirectIndex($e->getMessage(), 'error');
        }
    }

    /**
     * 3. Cập nhật thông tin nhân viên
     */
    public function update(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirectIndex('Phương thức không hợp lệ.');
            return;
        }

        try {
            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) {
                throw new Exception('ID nhân viên không hợp lệ.');
            }

            $data = [
                'employee_name' => trim($_POST['employee_name'] ?? ''),
                'role'          => trim($_POST['role'] ?? 'extrusion'),
                'username'      => trim($_POST['username'] ?? ''),
                'is_active'     => isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1,
            ];

            if ($data['employee_name'] === '') {
                throw new Exception('Họ tên không được để trống.');
            }

            $this->model->update($id, $data);

            if ($this->isAjax()) {
                $this->json([
                    'success' => true,
                    'message' => 'Cập nhật thông tin nhân viên thành công!'
                ]);
            }

            $this->redirectIndex('Cập nhật thông tin nhân viên thành công!', 'success');
        } catch (Throwable $e) {
            if ($this->isAjax()) {
                $this->json(['success' => false, 'error' => $e->getMessage()]);
            }
            $this->redirectIndex($e->getMessage(), 'error');
        }
    }

    /**
     * 4. Xóa nhân viên
     */
    public function delete(): void
    {
        $id = (int)($_POST['id'] ?? ($_GET['id'] ?? 0));

        try {
            if ($id <= 0) {
                throw new Exception('ID nhân viên không hợp lệ.');
            }

            // Không cho phép tự xóa tài khoản của chính mình đang đăng nhập
            if ($id === (int)($_SESSION['user']['id'] ?? 0)) {
                throw new Exception('Bạn không thể tự xóa tài khoản đang đăng nhập của chính mình.');
            }

            $this->model->delete($id);

            if ($this->isAjax()) {
                $this->json([
                    'success' => true,
                    'message' => 'Đã xóa nhân viên thành công!'
                ]);
            }

            $this->redirectIndex('Đã xóa nhân viên thành công!', 'success');
        } catch (Throwable $e) {
            if ($this->isAjax()) {
                $this->json(['success' => false, 'error' => $e->getMessage()]);
            }
            $this->redirectIndex($e->getMessage(), 'error');
        }
    }

    /**
     * 5. Cấp lại / Thay đổi mật khẩu nhân viên (Trường hợp quên mật khẩu)
     */
    public function resetPassword(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirectIndex('Phương thức không hợp lệ.');
            return;
        }

        try {
            $id = (int)($_POST['id'] ?? 0);
            $newPassword = trim($_POST['new_password'] ?? '');

            if ($id <= 0) {
                throw new Exception('ID nhân viên không hợp lệ.');
            }

            // Nếu để trống mật khẩu mới, mặc định reset về '123'
            $passToSet = ($newPassword !== '') ? $newPassword : '123';

            $this->model->resetPassword($id, $passToSet);

            if ($this->isAjax()) {
                $this->json([
                    'success' => true,
                    'message' => "Đã cấp lại mật khẩu thành công! Mật khẩu hiện tại là '{$passToSet}' (Bắt buộc nhân viên đổi MK khi đăng nhập)."
                ]);
            }

            $this->redirectIndex("Đã cấp lại mật khẩu thành công (Mật khẩu: {$passToSet})!", 'success');
        } catch (Throwable $e) {
            if ($this->isAjax()) {
                $this->json(['success' => false, 'error' => $e->getMessage()]);
            }
            $this->redirectIndex($e->getMessage(), 'error');
        }
    }

    /**
     * 6. Xuất danh sách nhân viên ra Excel (CSV chuẩn UTF-8 with BOM mở trực tiếp trên Excel)
     */
    public function exportExcel(): void
    {
        $filters = [
            'keyword' => trim($_GET['keyword'] ?? ''),
            'role'    => trim($_GET['role'] ?? 'all'),
            'status'  => trim($_GET['status'] ?? 'all'),
        ];

        $employees = $this->model->getAll($filters);

        $filename = 'Danh_sach_nhan_vien_SMC_' . date('Ymd_His') . '.csv';

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $output = fopen('php://output', 'w');

        // Ghi UTF-8 BOM để Excel hiển thị tiếng Việt & tiếng Nhật hoàn hảo không lỗi font
        fputs($output, "\xEF\xBB\xBF");

        // Header dòng 1
        fputcsv($output, [
            'STT',
            'Mã nhân viên',
            'Họ và tên',
            'Tên đăng nhập',
            'Vai trò / Nhóm',
            'Tình trạng làm việc',
            'Mật khẩu lần đầu',
            'Thời gian cập nhật'
        ]);

        $stt = 1;
        foreach ($employees as $emp) {
            $roleLabel = match ($emp['role']) {
                'extrusion' => 'Nhóm Đùn',
                'qc'        => 'Nhóm QC',
                'winding'   => 'Nhóm Cuộn',
                'admin'     => 'Quản trị viên (Admin)',
                default     => $emp['role']
            };

            $statusLabel = ($emp['is_active'] == 1) ? 'Đang làm việc' : 'Đã nghỉ/Khóa';
            $firstLoginLabel = ($emp['is_first_login'] == 1) ? 'Chưa đổi (123)' : 'Đã đổi riêng';

            fputcsv($output, [
                $stt++,
                $emp['employee_code'],
                $emp['employee_name'],
                $emp['username'],
                $roleLabel,
                $statusLabel,
                $firstLoginLabel,
                $emp['updated_time']
            ]);
        }

        fclose($output);
        exit;
    }

    /**
     * 7. Tải file mẫu Excel (CSV)
     */
    public function downloadTemplate(): void
    {
        $filename = 'Mau_nhap_nhan_vien_SMC.csv';

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $output = fopen('php://output', 'w');
        fputs($output, "\xEF\xBB\xBF"); // UTF-8 BOM

        // Header mẫu
        fputcsv($output, ['Mã nhân viên', 'Họ và tên', 'Vai trò (extrusion/qc/winding/admin)', 'Tài khoản (Tùy chọn)', 'Trạng thái (1: Làm việc, 0: Khóa)']);

        // Dữ liệu mẫu minh họa
        fputcsv($output, ['01910698', 'Nguyễn Thị Hiền', 'admin', '01910698', '1']);
        fputcsv($output, ['02420111', 'Trần Văn A', 'extrusion', '02420111', '1']);
        fputcsv($output, ['02420112', 'Lê Thị B', 'qc', '02420112', '1']);
        fputcsv($output, ['02420113', 'Phạm Văn C', 'winding', '02420113', '1']);

        fclose($output);
        exit;
    }

    /**
     * 8. Nhập danh sách nhân viên từ file Excel / CSV tải lên
     */
    public function importExcel(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_FILES['excel_file'])) {
            $this->redirectIndex('Vui lòng chọn file Excel / CSV để tải lên.', 'error');
            return;
        }

        $file = $_FILES['excel_file'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $this->redirectIndex('Lỗi khi tải file lên máy chủ.', 'error');
            return;
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['csv', 'txt'])) {
            $this->redirectIndex('Hệ thống hiện tại hỗ trợ file định dạng .csv hoặc .txt có cấu trúc theo file mẫu. Vui lòng tải file mẫu để kiểm tra.', 'error');
            return;
        }

        try {
            $handle = fopen($file['tmp_name'], 'r');
            if (!$handle) {
                throw new Exception('Không thể mở file tải lên.');
            }

            // Bỏ qua BOM nếu có
            $bom = fread($handle, 3);
            if ($bom !== "\xEF\xBB\xBF") {
                rewind($handle);
            }

            // Đọc dòng tiêu đề
            $header = fgetcsv($handle);

            $rows = [];
            while (($data = fgetcsv($handle)) !== false) {
                if (empty($data) || (count($data) === 1 && trim($data[0]) === '')) {
                    continue;
                }
                $rows[] = [
                    'employee_code' => $data[0] ?? '',
                    'employee_name' => $data[1] ?? '',
                    'role'          => $data[2] ?? 'extrusion',
                    'username'      => $data[3] ?? '',
                    'is_active'     => $data[4] ?? 1
                ];
            }
            fclose($handle);

            if (empty($rows)) {
                throw new Exception('File tải lên không có dữ liệu nhân viên nào.');
            }

            $updateIfExists = !empty($_POST['update_existing']);
            $result = $this->model->importBatch($rows, $updateIfExists);

            $msg = "Nhập hoàn tất! Thêm mới: {$result['inserted']}, Cập nhật: {$result['updated']}.";
            if (!empty($result['errors'])) {
                $msg .= ' Lưu ý: ' . implode(' ', array_slice($result['errors'], 0, 3));
            }

            if ($this->isAjax()) {
                $this->json([
                    'success'  => true,
                    'message'  => $msg,
                    'inserted' => $result['inserted'],
                    'updated'  => $result['updated'],
                    'errors'   => $result['errors']
                ]);
            }

            $this->redirectIndex($msg, 'success');
        } catch (Throwable $e) {
            if ($this->isAjax()) {
                $this->json(['success' => false, 'error' => $e->getMessage()]);
            }
            $this->redirectIndex($e->getMessage(), 'error');
        }
    }

    private function redirectIndex(string $message, string $type = 'info'): void
    {
        header('Location: ' . BASE_URL . '/index.php?url=employee/index&msg=' . urlencode($message) . '&msg_type=' . $type);
        exit;
    }

    private function isAjax(): bool
    {
        return (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
            || (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false);
    }
}
