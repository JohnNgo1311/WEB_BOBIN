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
        } catch (Throwable $e) {
        }

        $pdo = Database::getInstance()->pdo();

        $filters = [
            'keyword'     => trim($_GET['keyword'] ?? ''),
            'cost_center' => trim($_GET['cost_center'] ?? 'all'),
        ];

        // Lấy danh sách các mã bộ phận (cost_center) duy nhất để hiển thị bộ lọc
        $stmtCc = $pdo->query("SELECT DISTINCT cost_center FROM employee_list WHERE cost_center IS NOT NULL AND cost_center != '' ORDER BY cost_center ASC");
        $costCenters = $stmtCc->fetchAll(PDO::FETCH_COLUMN);

        // Query employees
        $sql = "SELECT id, employee_code, employee_name, cost_center, role, username, is_first_login, permissions, updated_time FROM employee_list WHERE 1=1";
        $params = [];

        if ($filters['keyword'] !== '') {
            $sql .= " AND (employee_code LIKE ? OR employee_name LIKE ? OR cost_center LIKE ?)";
            $params[] = '%' . $filters['keyword'] . '%';
            $params[] = '%' . $filters['keyword'] . '%';
            $params[] = '%' . $filters['keyword'] . '%';
        }
        if ($filters['cost_center'] !== 'all') {
            $sql .= " AND cost_center = ?";
            $params[] = $filters['cost_center'];
        }
        $sql .= " ORDER BY id DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Stats
        $stats = ['total' => 0, 'extrusion' => 0, 'qc' => 0, 'winding' => 0, 'admin' => 0];

        $stmtTotal = $pdo->query("SELECT COUNT(*) FROM employee_list");
        $stats['total'] = (int)$stmtTotal->fetchColumn();

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
            'costCenters'  => $costCenters,
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
                'employee_code'  => strtoupper(trim($_POST['employee_code'] ?? '')),
                'employee_name'  => trim($_POST['employee_name'] ?? ''),
                'cost_center'    => trim($_POST['cost_center'] ?? ''),
                'role'           => trim($_POST['role'] ?? 'extrusion'),
                'username'       => '',
                'password'       => password_hash('123', PASSWORD_DEFAULT),
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

            $defaultPermsJson = json_encode(AuthHelper::getDefaultPermissionsForRole($data['role']), JSON_UNESCAPED_UNICODE);

            $sql = "INSERT INTO employee_list (employee_code, employee_name, cost_center, role, username, password, is_first_login, permissions, updated_time) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $data['employee_code'],
                $data['employee_name'],
                $data['cost_center'] !== '' ? $data['cost_center'] : null,
                $data['role'],
                $data['username'],
                $data['password'],
                $data['is_first_login'],
                $defaultPermsJson
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
                'cost_center'   => trim($_POST['cost_center'] ?? ''),
                'role'          => trim($_POST['role'] ?? 'extrusion'),
            ];

            if ($data['employee_name'] === '') throw new Exception('Họ tên không được để trống.');

            // Kiểm tra thông tin cũ để đồng bộ quyền nếu thay đổi Role
            $stmtOld = $pdo->prepare("SELECT employee_code, role, permissions FROM employee_list WHERE id = ? LIMIT 1");
            $stmtOld->execute([$id]);
            $oldEmp = $stmtOld->fetch(PDO::FETCH_ASSOC);

            $newPermsJson = $oldEmp['permissions'] ?? null;
            if ($oldEmp && strtolower($oldEmp['role']) !== strtolower($data['role'])) {
                $oldDefault = AuthHelper::getDefaultPermissionsForRole($oldEmp['role']);
                $oldCurrent = AuthHelper::getUserPermissions($oldEmp);
                $wasDefault = empty(array_diff($oldCurrent, $oldDefault)) && empty(array_diff($oldDefault, $oldCurrent));
                if ($wasDefault || empty($oldEmp['permissions']) || strtolower($data['role']) === 'admin') {
                    $newPermsJson = json_encode(AuthHelper::getDefaultPermissionsForRole($data['role']), JSON_UNESCAPED_UNICODE);
                }
            } elseif ($oldEmp && empty($oldEmp['permissions'])) {
                $newPermsJson = json_encode(AuthHelper::getDefaultPermissionsForRole($data['role']), JSON_UNESCAPED_UNICODE);
            }

            $sql = "UPDATE employee_list SET employee_name = ?, cost_center = ?, role = ?, permissions = ?, updated_time = NOW() WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $data['employee_name'],
                $data['cost_center'] !== '' ? $data['cost_center'] : null,
                $data['role'],
                $newPermsJson,
                $id
            ]);

            if ($oldEmp && !empty($oldEmp['employee_code'])) {
                AuthHelper::syncCurrentSessionIfMatch($oldEmp['employee_code']);
            }

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
            'keyword'     => trim($_GET['keyword'] ?? ''),
            'cost_center' => trim($_GET['cost_center'] ?? 'all'),
        ];

        $pdo = Database::getInstance()->pdo();
        $sql = "SELECT * FROM employee_list WHERE 1=1";
        $params = [];

        if ($filters['keyword'] !== '') {
            $sql .= " AND (employee_code LIKE ? OR employee_name LIKE ? OR cost_center LIKE ?)";
            $params[] = '%' . $filters['keyword'] . '%';
            $params[] = '%' . $filters['keyword'] . '%';
            $params[] = '%' . $filters['keyword'] . '%';
        }
        if ($filters['cost_center'] !== 'all') {
            $sql .= " AND cost_center = ?";
            $params[] = $filters['cost_center'];
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
        fputcsv($output, ['STT', 'Mã nhân viên', 'Họ và tên', 'Mã bộ phận', 'Vai trò / Nhóm', 'Tên đăng nhập', 'Mật khẩu lần đầu', 'Thời gian cập nhật']);

        $stt = 1;
        foreach ($employees as $emp) {
            $roleLabel = match ($emp['role']) {
                'extrusion' => 'Nhóm Đùn',
                'qc' => 'Nhóm QC',
                'winding' => 'Nhóm Cuộn',
                'admin' => 'Quản trị viên (Admin)',
                default => $emp['role']
            };
            $firstLoginLabel = ($emp['is_first_login'] == 1) ? 'Chưa đổi (123)' : 'Đã đổi riêng';

            fputcsv($output, [
                $stt++,
                $emp['employee_code'],
                $emp['employee_name'],
                $emp['cost_center'] ?? '',
                $roleLabel,
                $emp['username'],
                $firstLoginLabel,
                $emp['updated_time']
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
        fputcsv($output, ['Mã nhân viên', 'Họ và tên', 'Mã bộ phận', 'Vai trò (extrusion/qc/winding/admin)', 'Tài khoản (Tùy chọn)']);
        fputcsv($output, ['01910698', 'Nguyễn Thị Hiền', 'A00430', 'admin', '01910698']);
        fputcsv($output, ['02420111', 'Trần Văn A', 'A00330', 'extrusion', '02420111']);
        fputcsv($output, ['02619486', 'Lê Văn B', 'A00340', 'winding', '02619486']);
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
                    'employee_code' => strtoupper(trim($data[0] ?? '')),
                    'employee_name' => trim($data[1] ?? ''),
                    'cost_center'   => trim($data[2] ?? ''),
                    'role'          => strtolower(trim($data[3] ?? 'extrusion')),
                    'username'      => trim($data[4] ?? '')
                ];
            }
            fclose($handle);
            if (empty($rows)) throw new Exception('File tải lên không có dữ liệu nhân viên nào.');

            $pdo = Database::getInstance()->pdo();
            $updateIfExists = !empty($_POST['update_existing']);
            $inserted = 0;
            $updated = 0;
            $errors = [];

            $validRoles = ['extrusion', 'qc', 'winding', 'admin'];

            $stmtCheck = $pdo->prepare("SELECT id, role, permissions FROM employee_list WHERE employee_code = ?");
            $stmtInsert = $pdo->prepare("INSERT INTO employee_list (employee_code, employee_name, cost_center, role, username, password, is_first_login, permissions, updated_time) VALUES (?, ?, ?, ?, ?, ?, 1, ?, NOW())");
            $stmtUpdate = $pdo->prepare("UPDATE employee_list SET employee_name = ?, cost_center = ?, role = ?, permissions = ?, updated_time = NOW() WHERE employee_code = ?");

            $defaultHash = password_hash('123', PASSWORD_DEFAULT);

            foreach ($rows as $i => $r) {
                if (empty($r['employee_code'])) continue;
                $username = empty($r['username']) ? $r['employee_code'] : $r['username'];
                $costCenter = $r['cost_center'] !== '' ? $r['cost_center'] : null;
                $role = in_array($r['role'], $validRoles, true) ? $r['role'] : 'extrusion';
                $defaultPermsJson = json_encode(AuthHelper::getDefaultPermissionsForRole($role), JSON_UNESCAPED_UNICODE);

                $stmtCheck->execute([$r['employee_code']]);
                $existingRow = $stmtCheck->fetch(PDO::FETCH_ASSOC);

                try {
                    if ($existingRow) {
                        if ($updateIfExists) {
                            $permsToSave = $existingRow['permissions'] ?? null;
                            if (empty($permsToSave) || strtolower($existingRow['role']) !== $role) {
                                $permsToSave = $defaultPermsJson;
                            }
                            $stmtUpdate->execute([$r['employee_name'], $costCenter, $role, $permsToSave, $r['employee_code']]);
                            $updated++;
                        }
                    } else {
                        $stmtInsert->execute([$r['employee_code'], $r['employee_name'], $costCenter, $role, $username, $defaultHash, $defaultPermsJson]);
                        $inserted++;
                    }
                } catch (PDOException $e) {
                    $errors[] = "Dòng " . ($i + 2) . ": " . $e->getMessage();
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
        } catch (Throwable $e) {
        }

        $pdo = Database::getInstance()->pdo();

        // Lấy danh sách nhân viên để tra cứu / chọn nhanh
        $stmt = $pdo->query("SELECT id, employee_code, employee_name, cost_center, role, username, permissions, updated_time 
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
            $stmt = $pdo->prepare("SELECT id, employee_code, employee_name, cost_center, role, username, permissions, updated_time 
                                   FROM employee_list 
                                   WHERE employee_code = ? OR username = ? 
                                   LIMIT 1");
            $stmt->execute([$code, $code]);
            $emp = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$emp) {
                $this->json(['success' => false, 'error' => "Không tìm thấy nhân viên với mã '{$code}'."], 404);
                return;
            }

            $currentPerms = AuthHelper::getUserPermissions($emp);
            $defaultPerms = AuthHelper::getDefaultPermissionsForRole($emp['role']);
            $hasCustom = !empty($emp['permissions'])
                && (!empty(array_diff($currentPerms, $defaultPerms)) || !empty(array_diff($defaultPerms, $currentPerms)));

            $this->json([
                'success' => true,
                'employee' => [
                    'id'                     => (int)$emp['id'],
                    'employee_code'          => $emp['employee_code'],
                    'employee_name'          => $emp['employee_name'],
                    'cost_center'            => $emp['cost_center'] ?? '',
                    'username'               => $emp['username'],
                    'role'                   => $emp['role'],
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
                // Khôi phục và lưu đồng bộ quyền mặc định theo role vào DB
                $defaultPerms = AuthHelper::getDefaultPermissionsForRole($emp['role']);
                $defaultJson  = json_encode($defaultPerms, JSON_UNESCAPED_UNICODE);
                $up = $pdo->prepare("UPDATE employee_list SET permissions = ?, updated_time = NOW() WHERE employee_code = ?");
                $up->execute([$defaultJson, $code]);
                AuthHelper::syncCurrentSessionIfMatch($code);

                $this->json([
                    'success'     => true,
                    'message'     => "Đã khôi phục quyền mặc định theo vai trò cho nhân viên {$emp['employee_name']}.",
                    'permissions' => $defaultPerms,
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
                // Chuẩn hóa quyền cũ nếu có
                if ($p === 'extrusion_edit') {
                    $p = (strtolower($emp['role']) === 'admin') ? 'extrusion_edit_cancel' : 'extrusion_edit_only';
                } elseif ($p === 'pending_cancel') {
                    $p = (strtolower($emp['role']) === 'admin') ? 'pending_cancel_edit' : 'pending_cancel_view';
                }
                if (in_array($p, $allValidKeys, true)) {
                    $cleanPerms[] = $p;
                }
            }
            $cleanPerms = array_values(array_unique($cleanPerms));

            // Đảm bảo 2 cấp quyền Điều chỉnh Đùn loại trừ nhau (ưu tiên extrusion_edit_cancel nếu cả hai được gửi lên)
            if (in_array('extrusion_edit_cancel', $cleanPerms, true) && in_array('extrusion_edit_only', $cleanPerms, true)) {
                $cleanPerms = array_values(array_diff($cleanPerms, ['extrusion_edit_only']));
            }

            // Đảm bảo 2 quyền danh sách chờ hủy loại trừ nhau (ưu tiên pending_cancel_edit nếu cả hai được gửi lên)
            if (in_array('pending_cancel_edit', $cleanPerms, true) && in_array('pending_cancel_view', $cleanPerms, true)) {
                $cleanPerms = array_values(array_diff($cleanPerms, ['pending_cancel_view']));
            }

            // Admin luôn giữ quyền quản lý phân quyền và quyền xem hết cả 3 trang điều chỉnh theo mặc định
            $empRoleLower = strtolower(trim($emp['role'] ?? ''));
            if ($empRoleLower === 'admin') {
                foreach (['permission_manage', 'extrusion_edit_view_all', 'qc_edit_view_all', 'winding_edit_view_all'] as $adminPerm) {
                    if (!in_array($adminPerm, $cleanPerms, true)) {
                        $cleanPerms[] = $adminPerm;
                    }
                }
            } else {
                // Nhân viên không phải Admin chỉ được cấp quyền xem toàn bộ trang điều chỉnh thuộc đúng nhóm của mình (đùn / cuộn / QC)
                $allowedViewAll = match ($empRoleLower) {
                    'extrusion' => 'extrusion_edit_view_all',
                    'qc'        => 'qc_edit_view_all',
                    'winding'   => 'winding_edit_view_all',
                    default     => ''
                };
                foreach (['extrusion_edit_view_all', 'qc_edit_view_all', 'winding_edit_view_all'] as $vp) {
                    if ($vp !== $allowedViewAll && in_array($vp, $cleanPerms, true)) {
                        $cleanPerms = array_values(array_diff($cleanPerms, [$vp]));
                    }
                }
            }

            $permsJson = json_encode($cleanPerms, JSON_UNESCAPED_UNICODE);

            $up = $pdo->prepare("UPDATE employee_list SET permissions = ?, updated_time = NOW() WHERE employee_code = ?");
            $up->execute([$permsJson, $code]);

            AuthHelper::syncCurrentSessionIfMatch($code);

            $defaultPerms = AuthHelper::getDefaultPermissionsForRole($emp['role']);
            $hasCustom = !empty(array_diff($cleanPerms, $defaultPerms)) || !empty(array_diff($defaultPerms, $cleanPerms));

            $this->json([
                'success'     => true,
                'message'     => "Cập nhật phân quyền cho nhân viên {$emp['employee_name']} thành công!",
                'permissions' => $cleanPerms,
                'has_custom'  => $hasCustom
            ]);
        } catch (Throwable $e) {
            $this->json(['success' => false, 'error' => 'Lỗi máy chủ: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Trang cấu hình từ điển đa ngôn ngữ động (TASK-012)
     */
    public function translationsView(): void
    {
        try {
            $listRepo = new ListDataRepository();
            $listRepo->getListData();
        } catch (Throwable $e) {
        }

        $customDict = Language::getCustomDictionary();
        $fullDict = Language::getMergedDictionary();

        $allKeys = array_unique(array_merge(
            array_keys($fullDict['vi'] ?? []),
            array_keys($fullDict['en'] ?? []),
            array_keys($fullDict['ja'] ?? []),
            array_keys($customDict['vi'] ?? []),
            array_keys($customDict['en'] ?? []),
            array_keys($customDict['ja'] ?? [])
        ));
        sort($allKeys);

        $keysData = [];
        foreach ($allKeys as $key) {
            $keysData[$key] = [
                'vi' => $fullDict['vi'][$key] ?? '',
                'en' => $fullDict['en'][$key] ?? '',
                'ja' => $fullDict['ja'][$key] ?? '',
                'is_custom' => (isset($customDict['vi'][$key]) || isset($customDict['en'][$key]) || isset($customDict['ja'][$key]))
            ];
        }

        $this->view('languageManageView', [
            'keysData'     => $keysData,
            'customDict'   => $customDict,
            'pendingCount' => GlobalData::$pendingBobinCount ?? 0,
            'userRole'     => $_SESSION['user']['role'] ?? '',
            'userName'     => $_SESSION['user']['employee_name'] ?? ''
        ]);
    }

    /**
     * API Lấy từ điển ngôn ngữ hiện tại (Default + Custom + Hash)
     */
    public function getCustomTranslations(): void
    {
        $this->json([
            'success'      => true,
            'hash'         => Language::getDictionaryHash(),
            'default_dict' => Language::getDefaultDictionary(),
            'custom_dict'  => Language::getCustomDictionary()
        ]);
    }

    /**
     * API Lưu từ điển ngôn ngữ tùy chỉnh vào config/custom_translations.json
     */
    public function updateTranslations(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'error' => 'Phương thức không được hỗ trợ'], 405);
            return;
        }

        try {
            $input = json_decode(file_get_contents('php://input'), true);
            if (!is_array($input) && !empty($_POST)) {
                $input = $_POST;
            }

            $translations = $input['translations'] ?? [];
            if (!is_array($translations)) {
                $this->json(['success' => false, 'error' => 'Dữ liệu không hợp lệ'], 400);
                return;
            }

            $isReplaceAll = !empty($input['replace_all']);
            if ($isReplaceAll) {
                $customData = [
                    'vi' => [],
                    'en' => [],
                    'ja' => []
                ];
            } else {
                // Giữ lại các key tùy chỉnh cũ nếu chỉ cập nhật hoặc thêm mới 1 số key
                $customData = Language::getCustomDictionary();
                if (!isset($customData['vi']) || !is_array($customData['vi'])) $customData['vi'] = [];
                if (!isset($customData['en']) || !is_array($customData['en'])) $customData['en'] = [];
                if (!isset($customData['ja']) || !is_array($customData['ja'])) $customData['ja'] = [];
            }

            foreach ($translations as $row) {
                $key = trim($row['key'] ?? '');
                if ($key === '') continue;

                $vi = trim((string)($row['vi'] ?? ''));
                $en = trim((string)($row['en'] ?? ''));
                $ja = trim((string)($row['ja'] ?? ''));

                if ($vi !== '') $customData['vi'][$key] = $vi;
                if ($en !== '') $customData['en'][$key] = $en;
                if ($ja !== '') $customData['ja'][$key] = $ja;
            }

            $success = Language::saveCustomDictionary($customData);

            if ($success) {
                $this->json([
                    'success'      => true,
                    'message'      => 'Lưu từ điển dịch thuật thành công!',
                    'hash'         => Language::getDictionaryHash(),
                    'custom_dict'  => $customData,
                    'custom_count' => count($customData['vi'])
                ]);
            } else {
                $this->json([
                    'success' => false,
                    'error' => 'Không thể ghi file cấu hình custom_translations.json. Vui lòng kiểm tra quyền thư mục.'
                ], 500);
            }
        } catch (Throwable $e) {
            $this->json(['success' => false, 'error' => 'Lỗi máy chủ: ' . $e->getMessage()], 500);
        }
    }

    /**
     * API Khôi phục bản dịch mặc định của 1 key hoặc tất cả
     */
    public function resetTranslations(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'error' => 'Phương thức không được hỗ trợ'], 405);
            return;
        }

        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $keyToReset = trim($input['key'] ?? '');

            if ($keyToReset === '') {
                // Reset toàn bộ
                Language::saveCustomDictionary(['vi' => [], 'en' => [], 'ja' => []]);
                $this->json([
                    'success'     => true,
                    'message'     => 'Đã khôi phục toàn bộ từ điển gốc mặc định.',
                    'hash'        => Language::getDictionaryHash(),
                    'custom_dict' => ['vi' => [], 'en' => [], 'ja' => []]
                ]);
                return;
            }

            $custom = Language::getCustomDictionary();
            unset($custom['vi'][$keyToReset], $custom['en'][$keyToReset], $custom['ja'][$keyToReset]);
            Language::saveCustomDictionary($custom);

            $this->json([
                'success'     => true,
                'message'     => "Đã khôi phục key '{$keyToReset}' về từ điển mặc định.",
                'hash'        => Language::getDictionaryHash(),
                'custom_dict' => $custom
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
