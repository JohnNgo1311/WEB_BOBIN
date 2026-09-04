<?php
require_once __DIR__ . '/../core/Database.php';

class MaterialLotModel
{
    private $db;
    public ?int $id;
    public string $lot;
    public DateTime $updated_time;

    public function __construct(?int $id = null, string $lot, DateTime $updated_time)
    {   $this->id = $id;
        $this->lot = $lot;
        $this->updated_time = $updated_time;
        $this->db = Database::getInstance();
    }

    // 1. Lấy tất cả
    public function getAllMaterialLot() {
        $stmt = $this->db->pdo()->prepare("SELECT * FROM material_lot ORDER BY id DESC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // 2. Lấy theo ID
    public function getMaterialLotById($id) {
        $stmt = $this->db->pdo()->prepare("SELECT * FROM material_lot WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // 3. Kiểm tra trùng Lot
    public function checkLotExists($lot, $excludeId = null) {
        $sql = "SELECT COUNT(*) FROM material_lot WHERE lot = :lot";
        $params = ['lot' => $lot];

        if ($excludeId) {
            $sql .= " AND id != :id";
            $params['id'] = $excludeId;
        }

        $stmt = $this->db->pdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn() > 0;
    }

    // 4. Tạo mới
    public function registMaterialLot($lot) {
        $sql = "INSERT INTO material_lot (lot) VALUES (:lot)";
        $stmt = $this->db->pdo()->prepare($sql);
        $stmt->execute(['lot' => $lot]);
        return $this->db->pdo()->lastInsertId();
    }

    // 5. Cập nhật
    public function updateMaterialLot($id, $lot) {
        $sql = "UPDATE material_lot SET lot = :lot, updated_time = NOW() WHERE id = :id";
        $stmt = $this->db->pdo()->prepare($sql);
        return $stmt->execute(['lot' => $lot, 'id' => $id]);
    }

    // 6. Xóa
    public function deleteMaterialLot($id) {
        $stmt = $this->db->pdo()->prepare("DELETE FROM material_lot WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }
}