<?php
require_once ROOT_PATH . '/app/entities/EmployeeEntity.php';

class EmployeeRepository
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function getListEmployee(): array
    {
        $sql = "SELECT id, employee_code, employee_name, role, username, is_active, updated_time 
                FROM employee_list 
                WHERE is_active = 1 
                ORDER BY employee_code ASC";

        try {
            $pdo = $this->db->pdo();
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $response = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $this->populateGlobalData($response);
            return $response;
        } catch (PDOException $e) {
            error_log("DB Error in getListEmployee: " . $e->getMessage());
            throw new Exception("Lỗi truy vấn danh sách nhân viên: " . $e->getMessage());
        }
    }

    private function populateGlobalData(array $list_employee): void
    {
        GlobalData::$listEmployeeEntity = $list_employee ?? [];
    }

    public function findByCode(string $code): ?EmployeeEntity
    {
        $code = trim($code);
        if ($code === '') return null;

        $list = GlobalData::$listEmployeeEntity;
        if (empty($list)) {
            $list = $this->getListEmployee();
        }

        // 1. Tìm trong bộ nhớ đệm
        foreach ($list as $emp) {
            if (!empty($emp['employee_code']) && strcasecmp($emp['employee_code'], $code) === 0) {
                return new EmployeeEntity(
                    id: (int)($emp['id'] ?? 0),
                    employee_code: $emp['employee_code'],
                    employee_name: $emp['employee_name'],
                    updated_time: !empty($emp['updated_time']) ? new DateTime($emp['updated_time']) : new DateTime()
                );
            }
        }

        // 2. Dự phòng: Truy vấn trực tiếp CSDL nếu bộ nhớ đệm chưa kịp nạp
        try {
            $pdo = $this->db->pdo();
            $stmt = $pdo->prepare("SELECT id, employee_code, employee_name, updated_time 
                                   FROM employee_list 
                                   WHERE employee_code = :code AND is_active = 1 
                                   LIMIT 1");
            $stmt->execute([':code' => $code]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($row) {
                return new EmployeeEntity(
                    id: (int)$row['id'],
                    employee_code: $row['employee_code'],
                    employee_name: $row['employee_name'],
                    updated_time: !empty($row['updated_time']) ? new DateTime($row['updated_time']) : new DateTime()
                );
            }
        } catch (Throwable $e) {
            error_log("Error in findByCode: " . $e->getMessage());
        }

        return null;
    }
}
