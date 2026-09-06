<?php
require_once ROOT_PATH . '/app/entities/ExtrusionMachineEntity.php';

class ExtrusionMachineRepository
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }
    public function getListExtrusionMachine(): array
    {
        $sql = "SELECT * FROM extrusion_machine_list";
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

    private function populateGlobalData(array $list_Extrusion_machine): void
    {
        GlobalData::$listExtrusionMachineEntity = $list_Extrusion_machine ?? [];
    }


    public function findByName(string $code): ?ExtrusionMachineEntity
    {
        $list = GlobalData::$listExtrusionMachineEntity;
        foreach ($list as $machine) {
            if (!empty($machine['machine_code']) && $machine['machine_code'] === $code) {
                return new ExtrusionMachineEntity(
                    $machine['id'],
                    $machine['machine_number'],
                    $machine['machine_code'],
                    $machine['machine_name']
                );
            }
        }
        return null;
    }
}
