<?php
// File: app/repositories/ListdataRepository.php
require_once ROOT_PATH . '/app/core/Database.php';
require_once ROOT_PATH . '/app/entities/ListdataEntity.php';

class ListdataRepository
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function getListData(bool $isFull): ListDataEntity
    {
        $queries = [
            'list_bobin' => $isFull ? "SELECT * FROM bobin_list_general" : "SELECT * FROM bobin_list_general WHERE bobin_current_status = 'Rolled' OR bobin_current_status = 'Cancelled'",
            'list_employee' => "SELECT * FROM employee_list",
            'list_material_lot' => "SELECT * FROM material_lot_list",
            'list_product' => "SELECT * FROM product_list",
            'list_extrusion_machine' => "SELECT * FROM extrusion_machine_list",
            'list_material' => "SELECT * FROM material_list",
            'list_year' => "SELECT * FROM year_list",
            'list_month' => "SELECT * FROM month_list",
            'list_day' => "SELECT * FROM day_list",
            'list_winding_machine' => "SELECT * FROM winding_machine_list",
            'pending_count' => "SELECT COUNT(*) FROM bobin_list_detail WHERE bobin_current_status = 'Pending_Cancellation'",
        ];

        $pdo = null;

        try {
            $pdo = $this->db->pdo();
            $pdo->beginTransaction();

            $response = $this->fetchAllListData($queries, $pdo);
            $pdo->commit();

            return $this->mapToEntity($response);
        } catch (PDOException $e) {
            if ($pdo !== null && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw new Exception("Database query failed: " . $e->getMessage());
        }
    }
    // public function countPendingCancellation(): int
    // {
    //     $pdo = $this->db->pdo();
    //     try {
    //         $sql = "SELECT COUNT(*) FROM bobin_list_detail WHERE bobin_current_status = 'Pending_Cancellation'";
    //         $stmt = $pdo->query($sql);
    //         $pendingCount = (int)$stmt->fetchColumn();

    //         return $pendingCount;
    //     } catch (PDOException $e) {
    //         error_log("DB Error: " . $e->getMessage());
    //         return 0;
    //     }
    // }

    private function fetchAllListData(array $queries, PDO $pdo): array
    {
        $response = [];
        foreach ($queries as $key => $sql) {
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $response[$key] = $key === 'pending_count'
                ? (int) $stmt->fetchColumn()
                : $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        return $response;
    }

    private function mapToEntity(array $response): ListDataEntity
    {
        $entity = new ListDataEntity();
        foreach ($response as $key => $value) {
            $entity->{$key} = $value;
        }
        $this->populateGlobalData(data: $entity);
        return $entity;
    }
    private function populateGlobalData(ListDataEntity $data): void
    {

        GlobalData::$pendingBobinCount = $data->pending_count ?? 0;
        GlobalData::$listBobinEntity = $data->list_bobin ?? [];
        GlobalData::$listEmployeeEntity = $data->list_employee ?? [];
        GlobalData::$listMaterialLotEntity = $data->list_material_lot ?? [];
        GlobalData::$listProductEntity = $data->list_product ?? [];
        GlobalData::$listMaterialEntity = $data->list_material ?? [];
        GlobalData::$listExtrusionMachineEntity = $data->list_extrusion_machine ?? [];
        GlobalData::$listYearEntity = $data->list_year ?? [];
        GlobalData::$listMonthEntity = $data->list_month ?? [];
        GlobalData::$listDayEntity = $data->list_day ?? [];
    }
}