<?php
require_once __DIR__ . '/../core/Database.php';

class EmployeeModel
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::getInstance()->pdo();
    }

    /**
     * Lấy danh sách nhân viên có bộ lọc và tìm kiếm
     */
    public function getAll(array $filters = []): array
    {
        $sql = "SELECT id, employee_code, employee_name, role, username, password, is_active, is_first_login, updated_time 
                FROM employee_list 
                WHERE 1=1";
        $params = [];

        // Lọc từ khóa (Mã NV, Họ tên, Username)
        if (!empty($filters['keyword'])) {
            $kw = '%' . trim($filters['keyword']) . '%';
            $sql .= " AND (employee_code LIKE :kw1 OR employee_name LIKE :kw2 OR username LIKE :kw3)";
            $params[':kw1'] = $kw;
            $params[':kw2'] = $kw;
            $params[':kw3'] = $kw;
        }

        // Lọc theo vai trò (extrusion, qc, winding, admin)
        if (!empty($filters['role']) && $filters['role'] !== 'all') {
            $sql .= " AND role = :role";
            $params[':role'] = $filters['role'];
        }

        // Lọc theo trạng thái làm việc (1: đang làm, 0: nghỉ/khóa)
        if (isset($filters['status']) && $filters['status'] !== '' && $filters['status'] !== 'all') {
            $sql .= " AND is_active = :status";
            $params[':status'] = (int)$filters['status'];
        }

        $sql .= " ORDER BY id ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Lấy chi tiết nhân viên theo ID
     */
    public function getById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM employee_list WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Lấy chi tiết nhân viên theo Mã NV
     */
    public function getByCode(string $code): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM employee_list WHERE employee_code = :code LIMIT 1");
        $stmt->execute([':code' => trim($code)]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Thống kê số lượng nhân viên theo từng vai trò
     */
    public function getStats(): array
    {
        $stmt = $this->pdo->query("SELECT role, COUNT(*) as cnt FROM employee_list GROUP BY role");
        $roleCounts = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $roleCounts[$row['role']] = (int)$row['cnt'];
        }

        $total = $this->pdo->query("SELECT COUNT(*) FROM employee_list")->fetchColumn();
        $active = $this->pdo->query("SELECT COUNT(*) FROM employee_list WHERE is_active = 1")->fetchColumn();

        return [
            'total'     => (int)$total,
            'active'    => (int)$active,
            'inactive'  => (int)$total - (int)$active,
            'extrusion' => $roleCounts['extrusion'] ?? 0,
            'qc'        => $roleCounts['qc'] ?? 0,
            'winding'   => $roleCounts['winding'] ?? 0,
            'admin'     => $roleCounts['admin'] ?? 0
        ];
    }

    /**
     * Thêm nhân viên mới
     */
    public function create(array $data): int|bool
    {
        $code = trim($data['employee_code'] ?? '');
        $name = trim($data['employee_name'] ?? '');
        $role = trim($data['role'] ?? 'extrusion');
        $username = trim($data['username'] ?? $code);
        $rawPass = trim($data['password'] ?? '123');
        $isActive = isset($data['is_active']) ? (int)$data['is_active'] : 1;
        $isFirstLogin = isset($data['is_first_login']) ? (int)$data['is_first_login'] : 1;

        if ($code === '' || $name === '') {
            return false;
        }

        // Kiểm tra trùng mã NV
        if ($this->getByCode($code)) {
            throw new Exception("Mã nhân viên '{$code}' đã tồn tại trên hệ thống.");
        }

        $validRoles = ['extrusion', 'qc', 'winding', 'admin'];
        if (!in_array($role, $validRoles, true)) {
            $role = 'extrusion';
        }

        $passwordHash = password_hash($rawPass, PASSWORD_DEFAULT);

        $sql = "INSERT INTO employee_list (employee_code, employee_name, role, username, password, is_active, is_first_login, updated_time) 
                VALUES (:code, :name, :role, :username, :password, :is_active, :is_first_login, NOW())";
        $stmt = $this->pdo->prepare($sql);
        $res = $stmt->execute([
            ':code'           => $code,
            ':name'           => $name,
            ':role'           => $role,
            ':username'       => $username,
            ':password'       => $passwordHash,
            ':is_active'      => $isActive,
            ':is_first_login' => $isFirstLogin
        ]);

        return $res ? (int)$this->pdo->lastInsertId() : false;
    }

    /**
     * Cập nhật thông tin nhân viên (Họ tên, Vai trò, Username, Tình trạng)
     */
    public function update(int $id, array $data): bool
    {
        $name = trim($data['employee_name'] ?? '');
        $role = trim($data['role'] ?? 'extrusion');
        $username = trim($data['username'] ?? '');
        $isActive = isset($data['is_active']) ? (int)$data['is_active'] : 1;

        if ($name === '') {
            return false;
        }

        $validRoles = ['extrusion', 'qc', 'winding', 'admin'];
        if (!in_array($role, $validRoles, true)) {
            $role = 'extrusion';
        }

        $sql = "UPDATE employee_list 
                SET employee_name = :name, 
                    role = :role, 
                    username = :username, 
                    is_active = :is_active, 
                    updated_time = NOW() 
                WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':name'      => $name,
            ':role'      => $role,
            ':username'  => $username,
            ':is_active' => $isActive,
            ':id'        => $id
        ]);
    }

    /**
     * Thay đổi / Cấp lại mật khẩu nhân viên (Admin Reset Password)
     * Khi cấp lại mật khẩu, tự động đặt is_first_login = 1 để nhân viên phải đổi MK khi đăng nhập
     */
    public function resetPassword(int $id, ?string $newPassword = null): bool
    {
        $plainPass = ($newPassword !== null && trim($newPassword) !== '') ? trim($newPassword) : '123';
        $hash = password_hash($plainPass, PASSWORD_DEFAULT);

        $sql = "UPDATE employee_list 
                SET password = :pass, 
                    is_first_login = 1, 
                    updated_time = NOW() 
                WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':pass' => $hash,
            ':id'   => $id
        ]);
    }

    /**
     * Xóa nhân viên khỏi hệ thống
     */
    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM employee_list WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    /**
     * Nhập hàng loạt danh sách nhân viên từ mảng dữ liệu Excel / CSV
     */
    public function importBatch(array $rows, bool $updateIfExists = true): array
    {
        $inserted = 0;
        $updated  = 0;
        $errors   = [];

        $validRoles = ['extrusion', 'qc', 'winding', 'admin'];

        foreach ($rows as $index => $row) {
            $lineNum = $index + 2; // Dòng excel tính từ 2 (sau header)

            $code = trim($row['employee_code'] ?? ($row[0] ?? ''));
            $name = trim($row['employee_name'] ?? ($row[1] ?? ''));
            $role = strtolower(trim($row['role'] ?? ($row[2] ?? 'extrusion')));
            $username = trim($row['username'] ?? ($row[3] ?? $code));
            $isActive = isset($row['is_active']) ? (int)$row['is_active'] : 1;

            if ($code === '' || $name === '') {
                $errors[] = "Dòng {$lineNum}: Bỏ qua vì thiếu Mã nhân viên hoặc Họ tên.";
                continue;
            }

            // Chuẩn hóa role
            if (!in_array($role, $validRoles, true)) {
                $role = match ($role) {
                    'đùn', 'nhóm đùn', 'extrusion' => 'extrusion',
                    'qc', 'kcs', 'kiểm tra'         => 'qc',
                    'cuộn', 'nhóm cuộn', 'winding' => 'winding',
                    'admin', 'quản trị'             => 'admin',
                    default                         => 'extrusion'
                };
            }

            $existing = $this->getByCode($code);
            if ($existing) {
                if ($updateIfExists) {
                    $upStmt = $this->pdo->prepare("UPDATE employee_list 
                                                   SET employee_name = :name, role = :role, username = :username, is_active = :is_active, updated_time = NOW() 
                                                   WHERE id = :id");
                    $upStmt->execute([
                        ':name'      => $name,
                        ':role'      => $role,
                        ':username'  => $username !== '' ? $username : $code,
                        ':is_active' => $isActive,
                        ':id'        => $existing['id']
                    ]);
                    $updated++;
                } else {
                    $errors[] = "Dòng {$lineNum}: Mã NV {$code} đã tồn tại (bỏ qua).";
                }
            } else {
                $defaultPassHash = password_hash('123', PASSWORD_DEFAULT);
                $insStmt = $this->pdo->prepare("INSERT INTO employee_list (employee_code, employee_name, role, username, password, is_active, is_first_login, updated_time) 
                                                VALUES (:code, :name, :role, :username, :pass, :is_active, 1, NOW())");
                $insStmt->execute([
                    ':code'      => $code,
                    ':name'      => $name,
                    ':role'      => $role,
                    ':username'  => $username !== '' ? $username : $code,
                    ':pass'      => $defaultPassHash,
                    ':is_active' => $isActive
                ]);
                $inserted++;
            }
        }

        return [
            'inserted' => $inserted,
            'updated'  => $updated,
            'errors'   => $errors
        ];
    }
}