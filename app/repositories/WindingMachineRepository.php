<?php
require_once ROOT_PATH . '/app/entities/WindingMachineEntity.php';

class WindingMachineRepository
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }
    public function getListWindingMachine(): array
    {
        $sql = "SELECT * FROM winding_machine_list";
        $pdo = null;

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
            if ($pdo !== null && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw new Exception("Database query failed: " . $e->getMessage());
        }
    }

    private function populateGlobalData(array $list_winding_machine): void
    {
        GlobalData::$listWindingMachineEntity = $list_winding_machine ?? [];
    }


    public function findByCode(string $code): ?WindingMachineEntity
    {
        $list = GlobalData::$listWindingMachineEntity;
        foreach ($list as $machine) {
            if (!empty($machine['machine_name']) && $machine['machine_name'] === $code) {
                return new WindingMachineEntity(
                    $machine['id'],
                    $machine['machine_name']
                );
            }
        }
        return null;
    }
}
