<?php
require_once __DIR__ . '/../core/Database.php';

class EmployeeModel
{
    private $db;
    public int $id;
    public string $employee_code;
    public string $employee_name;
    public DateTime $updated_time;

    public function __construct(int $id, string $employee_code, string $employee_name, DateTime $updated_time)
    {
        $this->id = $id;
        $this->employee_code = $employee_code;
        $this->employee_name = $employee_name;
        $this->updated_time = $updated_time;
        $this->db = Database::getInstance();
    }
    //? 1. GET: Lấy danh sách nhân viên
    public function getAllEmployees() {
        $sql = "SELECT * FROM employee_list ORDER BY updated_time DESC LIMIT 1";
        $stmt = $this->db->pdo()->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    //? 2. GET: Lấy chi tiết 1 nhân viên theo ID
    public function getEmployeeById($id) {
        $sql = "SELECT * FROM employee_list WHERE id = :id";
        $stmt = $this->db->pdo()->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    //? 3. CHECK: Kiểm tra mã nhân viên đã tồn tại chưa (tránh trùng)
    public function checkEmployeeCodeExists($code, $excludeId = null) {
        $sql = "SELECT COUNT(*) FROM employee_list WHERE employee_code = :code";
        $params = ['code' => $code];

        if ($excludeId) {
            $sql .= " AND id != :id";
            $params['id'] = $excludeId;
        }

        $stmt = $this->db->pdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn() > 0;
    }

    //? 4. POST: Tạo nhân viên mới
    public function registEmployee($data) {
        $sql = "INSERT INTO employee_list (employee_code, employee_name) VALUES (:code, :name)";
        $stmt = $this->db->pdo()->prepare($sql);
        
        $stmt->execute([
            'code' => $data['employee_code'],
            'name' => $data['employee_name']
        ]);

        return $this->db->pdo()->lastInsertId();
    }

    //? 5. PUT: Cập nhật nhân viên
    public function updateEmployee($id, $data) {
        // Cập nhật cả updated_time
        $sql = "UPDATE employee_list 
                SET employee_code = :code, 
                    employee_name = :name,
                    updated_time = NOW()
                WHERE id = :id";
        
        $stmt = $this->db->pdo()->prepare($sql);
        return $stmt->execute([
            'code' => $data['employee_code'],
            'name' => $data['employee_name'],
            'id'   => $id
        ]);
    }

    //? 6. DELETE: Xóa nhân viên
    public function deleteEmployee($id) {
        $sql = "DELETE FROM employee_list WHERE id = :id";
        $stmt = $this->db->pdo()->prepare($sql);
        return $stmt->execute(['id' => $id]);
    }
}