<?php
require_once ROOT_PATH . '/app/core/Controller.php';
require_once ROOT_PATH . '/app/repositories/ListDataRepository.php';
require_once ROOT_PATH . '/app/core/AuthHelper.php';

class EmployeeController extends Controller
{
    public function __construct()
    {
        $hasEmpManage = AuthHelper::hasPermission('employee_manage');
        $hasPermManage = AuthHelper::hasPermission('permission_manage');

        if (!isset($_SESSION['user']) || (!$hasEmpManage && !$hasPermManage)) {
            if ($this->isAjax()) {
                $this->json([
                    'success' => false,
                    'error'   => 'Quyền truy cập bị từ chối. Bạn không có quyền sử dụng chức năng này.'
                ], 403);
            }
            header('Location: ' . BASE_URL . '/index.php?url=bobin/listBobinDetailView&error=' . urlencode('Bạn không có quyền truy cập trang Quản trị.'));
            exit;
        }
    }

    public function index(): void
    {
        try {
            $listRepo = new ListDataRepository();
            $listRepo->getListData();
        } catch (Throwable $e) {}

        $pdo = Database::getInstance()->pdo();

        $filters = [
            'keyword' => trim($_GET['keyword'] ?? ''),
            'role'    => trim($_GET['role'] ?? 'all'),
            'status'  => trim($_GET['status'] ?? 'all'),
        ];

        // Query employees
        $sql = "SELECT * FROM employee_list WHERE 1=1";
        $params = [];

        if ($filters['keyword'] !== '') {
            $sql .= " AND (employee_code LIKE ? OR employee_name LIKE ?)";
            $params[] = '%' . $filters['keyword'] . '%';
            $params[] = '%' . $filters['keyword'] . '%';
        }
        if ($filters['role'] !== 'all') {
            $sql .= " AND role = ?";
            $params[] = $filters['role'];
        }
        if ($filters['status'] !== 'all') {
            $statusVal = match($filters['status']) {
                'active' => 1,
                'inactive' => 0,
                default => null
            };
            if ($statusVal !== null) {
                $sql .= " AND is_active = ?";
                $params[] = $statusVal;
            }
        }
        $sql .= " ORDER BY id DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Stats
        $stats = ['total' => 0, 'extrusion' => 0, 'qc' => 0, 'winding' => 0, 'admin' => 0, 'active' => 0, 'inactive' => 0];
        
        $stmtTotal = $pdo->query("SELECT COUNT(*) FROM employee_list");
        $stats['total'] = (int)$stmtTotal->fetchColumn();

        $stmtActive = $pdo->query("SELECT COUNT(*) FROM employee_list WHERE is_active = 1");
        $stats['active'] = (int)$stmtActive->fetchColumn();
        $stats['inactive'] = $stats['total'] - $stats['active'];

        $stmtRole = $pdo->query("SELECT role, COUNT(*) as cnt FROM employee_list GROUP BY role");
        $roleCounts = $stmtRole->fetchAll(PDO::FETCH_ASSOC);
        foreach ($roleCounts as $row) {
            $role = strtolower($row['role']);
            if (isset($stats[$role])) {
                $stats[$role] = (int)$row['cnt'];
            }
        }

        $pendingCount = 0;
        try {
            $bobinRepo = new BobinRepository();
            $pendingCount = $bobinRepo->countPendingCancellation();
        } catch (Throwable $e) {
            $pendingCount = GlobalData::$pendingBobinCount ?? 0;
        }

        $this->view('employeeListView', [
            'employees'    => $employees,
            'stats'        => $stats,
            'filters'      => $filters,
            'pendingCount' => $pendingCount,
            'userRole'     => $_SESSION['user']['role'] ?? '',
            'userName'     => $_SESSION['user']['employee_name'] ?? ''
        ]);
    }

    public function create(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirectIndex('Phương thức không hợp lệ.');
            return;
        }

        try {
            $pdo = Database::getInstance()->pdo();
            $data = [
                'employee_code'  => trim($_POST['employee_code'] ?? ''),
                'employee_name'  => trim($_POST['employee_name'] ?? ''),
                'role'           => trim($_POST['role'] ?? 'extrusion'),
                'username'       => '',
                'password'       => password_hash('123', PASSWORD_DEFAULT),
                'is_active'      => 1,
                'is_first_login' => 1
            ];

            if ($data['employee_code'] === '' || $data['employee_name'] === '') {
                throw new Exception('Vui lòng nhập đầy đủ Mã nhân viên và Họ tên.');
            }
            $data['username'] = $data['employee_code'];

            // Check duplicate
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM employee_list WHERE employee_code = ?");
            $stmt->execute([$data['employee_code']]);
            if ($stmt->fetchColumn() > 0) {
                throw new Exception('Mã nhân viên đã tồn tại.');
            }

            $sql = "INSERT INTO employee_list (employee_code, employee_name, role, username, password, is_active, is_first_login, updated_time) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $data['employee_code'],
                $data['employee_name'],
                $data['role'],
                $data['username'],
                $data['password'],
                $data['is_active'],
                $data['is_first_login']
            ]);
            $newId = $pdo->lastInsertId();

            if ($this->isAjax()) {
                $this->json(['success' => true, 'message' => 'Thêm nhân viên mới thành công!', 'id' => $newId]);
            }
            $this->redirectIndex('Thêm nhân viên mới thành công!', 'success');
        } catch (Throwable $e) {
            if ($this->isAjax()) $this->json(['success' => false, 'error' => $e->getMessage()]);
            $this->redirectIndex($e->getMessage(), 'error');
        }
    }

    public function update(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirectIndex('Phương thức không hợp lệ.');
            return;
        }

        try {
            $pdo = Database::getInstance()->pdo();
            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) throw new Exception('ID nhân viên không hợp lệ.');

            $data = [
                'employee_name' => trim($_POST['employee_name'] ?? ''),
                'role'          => trim($_POST['role'] ?? 'extrusion'),
                'is_active'     => isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1,
            ];

            if ($data['employee_name'] === '') throw new Exception('Họ tên không được để trống.');

            $sql = "UPDATE employee_list SET employee_name = ?, role = ?, is_active = ?, updated_time = NOW() WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$data['employee_name'], $data['role'], $data['is_active'], $id]);

            if ($this->isAjax()) $this->json(['success' => true, 'message' => 'Cập nhật thông tin nhân viên thành công!']);
            $this->redirectIndex('Cập nhật thông tin nhân viên thành công!', 'success');
        } catch (Throwable $e) {
            if ($this->isAjax()) $this->json(['success' => false, 'error' => $e->getMessage()]);
            $this->redirectIndex($e->getMessage(), 'error');
        }
    }

    public function delete(): void
    {
        $id = (int)($_POST['id'] ?? ($_GET['id'] ?? 0));
        try {
            if ($id <= 0) throw new Exception('ID nhân viên không hợp lệ.');
            if ($id === (int)($_SESSION['user']['id'] ?? 0)) {
                throw new Exception('Bạn không thể tự xóa tài khoản đang đăng nhập của chính mình.');
            }

            $pdo = Database::getInstance()->pdo();
            $stmt = $pdo->prepare("DELETE FROM employee_list WHERE id = ?");
            $stmt->execute([$id]);

            if ($this->isAjax()) $this->json(['success' => true, 'message' => 'Đã xóa nhân viên thành công!']);
            $this->redirectIndex('Đã xóa nhân viên thành công!', 'success');
        } catch (Throwable $e) {
            if ($this->isAjax()) $this->json(['success' => false, 'error' => $e->getMessage()]);
            $this->redirectIndex($e->getMessage(), 'error');
        }
    }

    public function resetPassword(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirectIndex('Phương thức không hợp lệ.');
            return;
        }

        try {
            $id = (int)($_POST['id'] ?? 0);
            $newPassword = trim($_POST['new_password'] ?? '');
            if ($id <= 0) throw new Exception('ID nhân viên không hợp lệ.');

            $passToSet = ($newPassword !== '') ? $newPassword : '123';
            $hashed = password_hash($passToSet, PASSWORD_DEFAULT);

            $pdo = Database::getInstance()->pdo();
            $stmt = $pdo->prepare("UPDATE employee_list SET password = ?, is_first_login = 1, updated_time = NOW() WHERE id = ?");
            $stmt->execute([$hashed, $id]);

            if ($this->isAjax()) {
                $this->json(['success' => true, 'message' => "Đã cấp lại mật khẩu thành công! Mật khẩu hiện tại là '{$passToSet}'"]);
            }
            $this->redirectIndex("Đã cấp lại mật khẩu thành công (Mật khẩu: {$passToSet})!", 'success');
        } catch (Throwable $e) {
            if ($this->isAjax()) $this->json(['success' => false, 'error' => $e->getMessage()]);
            $this->redirectIndex($e->getMessage(), 'error');
        }
    }

    public function exportExcel(): void
    {
        $filters = [
            'keyword' => trim($_GET['keyword'] ?? ''),
            'role'    => trim($_GET['role'] ?? 'all'),
            'status'  => trim($_GET['status'] ?? 'all'),
        ];

        $pdo = Database::getInstance()->pdo();
        $sql = "SELECT * FROM employee_list WHERE 1=1";
        $params = [];

        if ($filters['keyword'] !== '') {
            $sql .= " AND (employee_code LIKE ? OR employee_name LIKE ?)";
            $params[] = '%' . $filters['keyword'] . '%';
            $params[] = '%' . $filters['keyword'] . '%';
        }
        if ($filters['role'] !== 'all') {
            $sql .= " AND role = ?";
            $params[] = $filters['role'];
        }
        if ($filters['status'] !== 'all') {
            $statusVal = match($filters['status']) { 'active' => 1, 'inactive' => 0, default => null };
            if ($statusVal !== null) {
                $sql .= " AND is_active = ?";
                $params[] = $statusVal;
            }
        }
        $sql .= " ORDER BY id DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $filename = 'Danh_sach_nhan_vien_SMC_' . date('Ymd_His') . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        
        $output = fopen('php://output', 'w');
        fputs($output, "\xEF\xBB\xBF");
        fputcsv($output, ['STT', 'Mã nhân viên', 'Họ và tên', 'Tên đăng nhập', 'Vai trò / Nhóm', 'Tình trạng làm việc', 'Mật khẩu lần đầu', 'Thời gian cập nhật']);

        $stt = 1;
        foreach ($employees as $emp) {
            $roleLabel = match ($emp['role']) {
                'extrusion' => 'Nhóm Đùn', 'qc' => 'Nhóm QC', 'winding' => 'Nhóm Cuộn', 'admin' => 'Quản trị viên (Admin)', default => $emp['role']
            };
            $statusLabel = ($emp['is_active'] == 1) ? 'Đang làm việc' : 'Đã nghỉ/Khóa';
            $firstLoginLabel = ($emp['is_first_login'] == 1) ? 'Chưa đổi (123)' : 'Đã đổi riêng';

            fputcsv($output, [
                $stt++, $emp['employee_code'], $emp['employee_name'], $emp['username'], $roleLabel, $statusLabel, $firstLoginLabel, $emp['updated_time']
            ]);
        }
        fclose($output);
        exit;
    }

    public function downloadTemplate(): void
    {
        $filename = 'Mau_nhap_nhan_vien_SMC.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        $output = fopen('php://output', 'w');
        fputs($output, "\xEF\xBB\xBF");
        fputcsv($output, ['Mã nhân viên', 'Họ và tên', 'Vai trò (extrusion/qc/winding/admin)', 'Tài khoản (Tùy chọn)', 'Trạng thái (1: Làm việc, 0: Khóa)']);
        fputcsv($output, ['01910698', 'Nguyễn Thị Hiền', 'admin', '01910698', '1']);
        fputcsv($output, ['02420111', 'Trần Văn A', 'extrusion', '02420111', '1']);
        fclose($output);
        exit;
    }

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
            $this->redirectIndex('Hệ thống hiện tại hỗ trợ file định dạng .csv hoặc .txt có cấu trúc theo file mẫu.', 'error');
            return;
        }

        try {
            $handle = fopen($file['tmp_name'], 'r');
            if (!$handle) throw new Exception('Không thể mở file tải lên.');

            $bom = fread($handle, 3);
            if ($bom !== "\xEF\xBB\xBF") rewind($handle);

            fgetcsv($handle); // skip header
            $rows = [];
            while (($data = fgetcsv($handle)) !== false) {
                if (empty($data) || (count($data) === 1 && trim($data[0]) === '')) continue;
                $rows[] = [
                    'employee_code' => $data[0] ?? '',
                    'employee_name' => $data[1] ?? '',
                    'role'          => $data[2] ?? 'extrusion',
                    'username'      => $data[3] ?? '',
                    'is_active'     => $data[4] ?? 1
                ];
            }
            fclose($handle);
            if (empty($rows)) throw new Exception('File tải lên không có dữ liệu nhân viên nào.');

            $pdo = Database::getInstance()->pdo();
            $updateIfExists = !empty($_POST['update_existing']);
            $inserted = 0;
            $updated = 0;
            $errors = [];

            $stmtCheck = $pdo->prepare("SELECT id FROM employee_list WHERE employee_code = ?");
            $stmtInsert = $pdo->prepare("INSERT INTO employee_list (employee_code, employee_name, role, username, password, is_active, is_first_login, updated_time) VALUES (?, ?, ?, ?, ?, ?, 1, NOW())");
            $stmtUpdate = $pdo->prepare("UPDATE employee_list SET employee_name = ?, role = ?, is_active = ?, updated_time = NOW() WHERE employee_code = ?");

            $defaultHash = password_hash('123', PASSWORD_DEFAULT);

            foreach ($rows as $i => $r) {
                if (empty($r['employee_code'])) continue;
                $username = empty($r['username']) ? $r['employee_code'] : $r['username'];
                
                $stmtCheck->execute([$r['employee_code']]);
                $existsId = $stmtCheck->fetchColumn();

                try {
                    if ($existsId) {
                        if ($updateIfExists) {
                            $stmtUpdate->execute([$r['employee_name'], $r['role'], $r['is_active'], $r['employee_code']]);
                            $updated++;
                        }
                    } else {
                        $stmtInsert->execute([$r['employee_code'], $r['employee_name'], $r['role'], $username, $defaultHash, $r['is_active']]);
                        $inserted++;
                    }
                } catch (PDOException $e) {
                    $errors[] = "Dòng " . ($i+2) . ": " . $e->getMessage();
                }
            }

            $msg = "Nhập hoàn tất! Thêm mới: {$inserted}, Cập nhật: {$updated}.";
            if (!empty($errors)) $msg .= ' Lưu ý: ' . implode(' ', array_slice($errors, 0, 3));
            
            if ($this->isAjax()) $this->json(['success' => true, 'message' => $msg, 'inserted' => $inserted, 'updated' => $updated, 'errors' => $errors]);
            $this->redirectIndex($msg, 'success');
        } catch (Throwable $e) {
            if ($this->isAjax()) $this->json(['success' => false, 'error' => $e->getMessage()]);
            $this->redirectIndex($e->getMessage(), 'error');
        }
    }

    public function permissionsView(): void
    {
        if (!AuthHelper::hasPermission('permission_manage')) {
            header('Location: ' . BASE_URL . '/index.php?url=bobin/listBobinDetailView&error=' . urlencode('Bạn không có quyền phân quyền tài khoản.'));
            exit;
        }

        try {
            $listRepo = new ListDataRepository();
            $listRepo->getListData();
        } catch (Throwable $e) {}

        $pdo = Database::getInstance()->pdo();

        // Lấy danh sách nhân viên để tra cứu / chọn nhanh
        $stmt = $pdo->query("SELECT id, employee_code, employee_name, role, username, is_active, permissions, updated_time 
                             FROM employee_list 
                             ORDER BY employee_code ASC");
        $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $selectedCode = trim($_GET['employee_code'] ?? '');
        $selectedEmployee = null;

        if ($selectedCode !== '') {
            foreach ($employees as $emp) {
                if ($emp['employee_code'] === $selectedCode) {
                    $selectedEmployee = $emp;
                    break;
                }
            }
        }

        $allPermissions = AuthHelper::getAllPermissions();
        $pendingCount   = GlobalData::$pendingBobinCount ?? 0;

        $this->view('employeePermissionsView', [
            'employees'        => $employees,
            'selectedCode'     => $selectedCode,
            'selectedEmployee' => $selectedEmployee,
            'allPermissions'   => $allPermissions,
            'pendingCount'     => $pendingCount,
            'userRole'         => $_SESSION['user']['role'] ?? '',
            'userName'         => $_SESSION['user']['employee_name'] ?? ''
        ]);
    }

    public function getEmployeePermissions(): void
    {
        if (!AuthHelper::hasPermission('permission_manage')) {
            $this->json(['success' => false, 'error' => 'Quyền truy cập bị từ chối.'], 403);
            return;
        }

        $code = trim($_GET['employee_code'] ?? '');
        if ($code === '') {
            $this->json(['success' => false, 'error' => 'Vui lòng cung cấp mã nhân viên.'], 400);
            return;
        }

        try {
            $pdo = Database::getInstance()->pdo();
            $stmt = $pdo->prepare("SELECT id, employee_code, employee_name, role, username, is_active, permissions, updated_time 
                                   FROM employee_list 
                                   WHERE employee_code = ? OR username = ? 
                                   LIMIT 1");
            $stmt->execute([$code, $code]);
            $emp = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$emp) {
                $this->json(['success' => false, 'error' => "Không tìm thấy nhân viên với mã '{$code}'."], 404);
                return;
            }

            $hasCustom = !empty($emp['permissions']);
            $currentPerms = AuthHelper::getUserPermissions($emp);
            $defaultPerms = AuthHelper::getDefaultPermissionsForRole($emp['role']);

            $this->json([
                'success' => true,
                'employee' => [
                    'id'                     => (int)$emp['id'],
                    'employee_code'          => $emp['employee_code'],
                    'employee_name'          => $emp['employee_name'],
                    'username'               => $emp['username'],
                    'role'                   => $emp['role'],
                    'is_active'              => (int)$emp['is_active'],
                    'has_custom_permissions' => $hasCustom,
                    'permissions'            => $currentPerms,
                    'default_permissions'    => $defaultPerms,
                    'updated_time'           => $emp['updated_time']
                ]
            ]);
        } catch (Throwable $e) {
            $this->json(['success' => false, 'error' => 'Lỗi máy chủ: ' . $e->getMessage()], 500);
        }
    }

    public function updatePermissions(): void
    {
        if (!AuthHelper::hasPermission('permission_manage')) {
            $this->json(['success' => false, 'error' => 'Quyền truy cập bị từ chối.'], 403);
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'error' => 'Phương thức không hợp lệ.'], 405);
            return;
        }

        $code = trim($_POST['employee_code'] ?? '');
        if ($code === '') {
            $this->json(['success' => false, 'error' => 'Mã nhân viên không hợp lệ.'], 400);
            return;
        }

        $resetDefault = !empty($_POST['reset_default']);
        $permsInput   = $_POST['permissions'] ?? [];

        try {
            $pdo = Database::getInstance()->pdo();
            $stmt = $pdo->prepare("SELECT id, employee_code, employee_name, role FROM employee_list WHERE employee_code = ? LIMIT 1");
            $stmt->execute([$code]);
            $emp = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$emp) {
                $this->json(['success' => false, 'error' => "Không tìm thấy nhân viên với mã '{$code}'."], 404);
                return;
            }

            if ($resetDefault) {
                // Khôi phục về quyền mặc định theo role
                $up = $pdo->prepare("UPDATE employee_list SET permissions = NULL, updated_time = NOW() WHERE employee_code = ?");
                $up->execute([$code]);
                AuthHelper::syncCurrentSessionIfMatch($code);

                $this->json([
                    'success'     => true,
                    'message'     => "Đã khôi phục quyền mặc định theo vai trò cho nhân viên {$emp['employee_name']}.",
                    'permissions' => AuthHelper::getDefaultPermissionsForRole($emp['role']),
                    'has_custom'  => false
                ]);
                return;
            }

            // Xử lý mảng permissions
            if (is_string($permsInput)) {
                $decoded = json_decode($permsInput, true);
                if (is_array($decoded)) {
                    $permsInput = $decoded;
                } else {
                    $permsInput = explode(',', $permsInput);
                }
            }

            $allValidKeys = AuthHelper::getAllPermissionKeys();
            $cleanPerms = [];
            foreach ($permsInput as $p) {
                $p = trim((string)$p);
                if (in_array($p, $allValidKeys, true)) {
                    $cleanPerms[] = $p;
                }
            }
            $cleanPerms = array_values(array_unique($cleanPerms));

            // Admin luôn giữ quyền quản lý phân quyền để tránh tự khóa mình
            if (strtolower($emp['role']) === 'admin' && !in_array('permission_manage', $cleanPerms, true)) {
                $cleanPerms[] = 'permission_manage';
            }

            $permsJson = json_encode($cleanPerms, JSON_UNESCAPED_UNICODE);

            $up = $pdo->prepare("UPDATE employee_list SET permissions = ?, updated_time = NOW() WHERE employee_code = ?");
            $up->execute([$permsJson, $code]);

            AuthHelper::syncCurrentSessionIfMatch($code);

            $this->json([
                'success'     => true,
                'message'     => "Cập nhật phân quyền cho nhân viên {$emp['employee_name']} thành công!",
                'permissions' => $cleanPerms,
                'has_custom'  => true
            ]);
        } catch (Throwable $e) {
            $this->json(['success' => false, 'error' => 'Lỗi máy chủ: ' . $e->getMessage()], 500);
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