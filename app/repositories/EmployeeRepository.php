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
        $sql = "SELECT * FROM employee_list";

        try {
            $pdo = $this->db->pdo();
            $pdo->beginTransaction();

            $stmt = $pdo->prepare($sql);
            $stmt->execute();

            $response = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $this->populateGlobalData($response);

            $pdo->commit();

            return $response;
        } catch (PDOException $e) {
            $pdo->rollBack();
            throw new Exception("Database query failed: " . $e->getMessage());
        }
    }

    private function populateGlobalData(array $list_employee): void
    {
        GlobalData::$listEmployeeEntity = $list_employee ?? [];
    }


    public function findByCode(string $code): ?EmployeeEntity
    {
        $list = GlobalData::$listEmployeeEntity;
        foreach ($list as $emp) {
            if (!empty($emp['employee_code']) && $emp['employee_code'] === $code) {
                return new EmployeeEntity(
                    $emp['id'],
                    $emp['employee_code'],
                    $emp['employee_name'],
                    new DateTime($emp['updated_time'])
                );
            }
        }
        return null;
    }
}
