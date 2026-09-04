<?php
// File: app/repositories/BobinRepository.php
require_once ROOT_PATH . '/app/core/Database.php';
require_once ROOT_PATH . '/app/entities/BobinEntity.php';

class BobinRepository
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    //! GET
    #region GET LIST

    //? GET Bobin History with Filter (OPTIONAL)
    public function getBobinsHistory(array $filters = [])
    {
        $pdo = $this->db->pdo();

        try {
            $sql = "SELECT * FROM bobin_history
                    WHERE updated_time BETWEEN :from_date AND :to_date";
            $params = [
                ':from_date' => $filters['from_date'],
                ':to_date' => $filters['to_date']
            ];
            if (!empty($filters['keyword'])) {
                $searchStr = '%' . trim($filters['keyword']) . '%';
                $sql .= " AND (
                bobin_identification_code LIKE :kw1 OR  
                JSON_EXTRACT(extrusion_employee, '$.employee_code') LIKE :kw2 OR 
                JSON_EXTRACT(extrusion_employee, '$.employee_name') LIKE :kw3 OR 
                JSON_EXTRACT(products, '$.product_code') LIKE :kw4 OR 
                JSON_EXTRACT(products, '$.production_order_code') LIKE :kw5 OR 
                print_lot LIKE :kw6 OR
                winding_machine LIKE :kw7 OR
                bobin_type LIKE :kw8
            )";

                $params[':kw1'] = $searchStr;
                $params[':kw2'] = $searchStr;
                $params[':kw3'] = $searchStr;
                $params[':kw4'] = $searchStr;
                $params[':kw5'] = $searchStr;
                $params[':kw6'] = $searchStr;
                $params[':kw7'] = $searchStr;
                $params[':kw8'] = $searchStr;
            }
            if (!empty($filters['status'])) {
                if ($filters['status'] === 'Line') {
                    $sql .= " AND bobin_current_status IN ('Busy_Unchecked', 'Busy_Checked')";
                } elseif ($filters['status'] !== 'all') {
                    $sql .= " AND bobin_current_status = :status";
                    $params[':status'] = $filters['status'];
                }
            }
            $sql .= " ORDER BY updated_time DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("DB Error: " . $e->getMessage());
            throw new Exception("Database error: " . $e->getMessage());
        }
    }
    //? GET LIST Detail 
    public function getBobinListDetail(array $filters = [])
    {
        $pdo = $this->db->pdo();

        try {
            $sql = "SELECT * FROM bobin_list_detail" . " WHERE 1=1"; // Câu SQL khởi tạo với điều kiện luôn đúng để dễ dàng thêm các điều kiện khác sau này
            $params = [];

            if (!empty($filters['keyword'])) {
                $searchStr = '%' . trim($filters['keyword']) . '%';
                $sql .= " AND (
                bobin_identification_code LIKE :kw1 OR  
                JSON_EXTRACT(extrusion_employee, '$.employee_code') LIKE :kw2 OR 
                JSON_EXTRACT(extrusion_employee, '$.employee_name') LIKE :kw3 OR 
                JSON_EXTRACT(products, '$.product_code') LIKE :kw4 OR 
                JSON_EXTRACT(products, '$.production_order_code') LIKE :kw5 OR 
                print_lot LIKE :kw6 OR
                winding_machine LIKE :kw7 OR
                bobin_type LIKE :kw8

            )";

                $params[':kw1'] = $searchStr;
                $params[':kw2'] = $searchStr;
                $params[':kw3'] = $searchStr;
                $params[':kw4'] = $searchStr;
                $params[':kw5'] = $searchStr;
                $params[':kw6'] = $searchStr;
                $params[':kw7'] = $searchStr;
                $params[':kw8'] = $searchStr;
            }

            if (!empty($filters['status'])) {
                if ($filters['status'] === 'Line') {
                    $sql .= " AND bobin_current_status IN ('Busy_Unchecked', 'Busy_Checked')";
                } elseif ($filters['status'] !== 'all') {
                    $sql .= " AND bobin_current_status = :status";
                    $params[':status'] = $filters['status'];
                }
            }


            $sql .= " ORDER BY bobin_identification_code ";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("DB Error: " . $e->getMessage());
            throw new Exception("Database error: " . $e->getMessage());
        }
    }

    //? GET LIST Detail 
    public function getListDetailBobinsForEditting(array $filters = [])
    {
        $pdo = $this->db->pdo();

        try {
            $sql = "SELECT * FROM bobin_list_detail" . " WHERE bobin_current_status IN ('Busy_UnChecked')";

            $params = [];

            if (!empty($filters['keyword'])) {
                $searchStr = '%' . trim($filters['keyword']) . '%';
                $sql .= " AND (
                bobin_identification_code LIKE :kw1 OR  
                JSON_EXTRACT(extrusion_employee, '$.employee_code') LIKE :kw2 OR 
                JSON_EXTRACT(extrusion_employee, '$.employee_name') LIKE :kw3 OR 
                JSON_EXTRACT(products, '$.product_code') LIKE :kw4 OR 
                JSON_EXTRACT(products, '$.production_order_code') LIKE :kw5 OR 
                print_lot LIKE :kw6 OR
                winding_machine LIKE :kw7 OR
                bobin_type LIKE :kw8

            )";

                $params[':kw1'] = $searchStr;
                $params[':kw2'] = $searchStr;
                $params[':kw3'] = $searchStr;
                $params[':kw4'] = $searchStr;
                $params[':kw5'] = $searchStr;
                $params[':kw6'] = $searchStr;
                $params[':kw7'] = $searchStr;
                $params[':kw8'] = $searchStr;
            }
            $sql .= " ORDER BY bobin_identification_code ";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("DB Error: " . $e->getMessage());
            throw new Exception("Database error: " . $e->getMessage());
        }
    }
    //? GET LIST Detail FOR QC (Only Busy_Unchecked & No Date Filter)
    public function getDetailBobinsForQC(array $filters = [])
    {
        $pdo = $this->db->pdo();

        try {
            //TODO 1. Câu SQL khởi tạo: Chỉ lấy những bobin có trạng thái là 'Busy_Unchecked'
            // Bỏ qua điều kiện ngày tháng như yêu cầu.
            $sql = "SELECT * FROM bobin_list_detail 
                WHERE bobin_current_status = 'Busy_Unchecked'";

            $params = [];

            //TODO 2. Xử lý Keyword (Giữ nguyên logic tìm kiếm nếu người dùng nhập)
            if (!empty($filters['keyword'])) {
                $searchStr = '%' . trim($filters['keyword']) . '%';
                // Nối thêm điều kiện AND (...)
                $sql .= " AND (
                bobin_identification_code LIKE :kw1 OR  
                JSON_EXTRACT(extrusion_employee, '$.employee_code') LIKE :kw2 OR 
                JSON_EXTRACT(extrusion_employee, '$.employee_name') LIKE :kw3 OR 
                JSON_EXTRACT(products, '$.product_code') LIKE :kw4 OR 
                JSON_EXTRACT(products, '$.production_order_code') LIKE :kw5 OR 
                print_lot LIKE :kw6 OR
                bobin_type LIKE :kw7
            )";

                $params[':kw1'] = $searchStr;
                $params[':kw2'] = $searchStr;
                $params[':kw3'] = $searchStr;
                $params[':kw4'] = $searchStr;
                $params[':kw5'] = $searchStr;
                $params[':kw6'] = $searchStr;
                $params[':kw7'] = $searchStr;
            }
            // Note: Removed the if (!empty($filters['status'])) part because it is required to only get Busy_Unchecked above.

            //TODO 3. Sắp xếp

            $sql .= " ORDER BY updated_time DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("DB Error: " . $e->getMessage());
            // Tùy chọn: return [] hoặc throw Exception
            throw new Exception("Database error: " . $e->getMessage());
        }
    }
    public function getDetailBobinsForPendingCancellation(array $filters = [])
    {
        $pdo = $this->db->pdo();

        try {
            //TODO 1. Câu SQL khởi tạo: Chỉ lấy những bobin có trạng thái là 'Pending_Cancellation'
            // Bỏ qua điều kiện ngày tháng như yêu cầu.
            $sql = "SELECT * FROM bobin_list_detail 
                WHERE bobin_current_status = 'Pending_Cancellation'";

            $params = [];

            //TODO 2. Xử lý Keyword (Giữ nguyên logic tìm kiếm nếu người dùng nhập)
            if (!empty($filters['keyword'])) {
                $searchStr = '%' . trim($filters['keyword']) . '%';
                // Nối thêm điều kiện AND (...)
                $sql .= " AND (
                bobin_identification_code LIKE :kw1 OR  
                JSON_EXTRACT(extrusion_employee, '$.employee_code') LIKE :kw2 OR 
                JSON_EXTRACT(extrusion_employee, '$.employee_name') LIKE :kw3 OR 
                JSON_EXTRACT(products, '$.product_code') LIKE :kw4 OR 
                JSON_EXTRACT(products, '$.production_order_code') LIKE :kw5 OR 
                print_lot LIKE :kw6 OR
                bobin_type LIKE :kw7
            )";

                $params[':kw1'] = $searchStr;
                $params[':kw2'] = $searchStr;
                $params[':kw3'] = $searchStr;
                $params[':kw4'] = $searchStr;
                $params[':kw5'] = $searchStr;
                $params[':kw6'] = $searchStr;
                $params[':kw7'] = $searchStr;
            }
            // Note: Removed the if (!empty($filters['status'])) part because it is required to only get Busy_Unchecked above.

            //TODO 3. Sắp xếp

            $sql .= " ORDER BY updated_time DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("DB Error: " . $e->getMessage());
            // Tùy chọn: return [] hoặc throw Exception
            throw new Exception("Database error: " . $e->getMessage());
        }
    }

    public function getDetailBobinsForWinding(array $filters = [])
    {
        $pdo = $this->db->pdo();

        try {
            //TODO 1. Câu SQL khởi tạo: Chỉ lấy những bobin có trạng thái là 'Busy_Checked' và 'Rolled'

            $sql = "SELECT * FROM bobin_list_detail 
                WHERE bobin_current_status IN ('Busy_Checked', 'Rolled')";
            $params = [];

            //TODO 2. Xử lý Keyword (Giữ nguyên logic tìm kiếm nếu người dùng nhập)
            if (!empty($filters['keyword'])) {
                $searchStr = '%' . trim($filters['keyword']) . '%';

                // Nối thêm điều kiện AND (...)
                $sql .= " AND (
                bobin_identification_code LIKE :kw1 OR  
                JSON_EXTRACT(extrusion_employee, '$.employee_code') LIKE :kw2 OR 
                JSON_EXTRACT(extrusion_employee, '$.employee_name') LIKE :kw3 OR 
                JSON_EXTRACT(products, '$.product_code') LIKE :kw4 OR 
                JSON_EXTRACT(products, '$.production_order_code') LIKE :kw5 OR 
                print_lot LIKE :kw6 OR
                bobin_type LIKE :kw7
            )";

                $params[':kw1'] = $searchStr;
                $params[':kw2'] = $searchStr;
                $params[':kw3'] = $searchStr;
                $params[':kw4'] = $searchStr;
                $params[':kw5'] = $searchStr;
                $params[':kw6'] = $searchStr;
                $params[':kw7'] = $searchStr;
            }

            // Lưu ý: Đã xóa phần if (!empty($filters['status'])) vì yêu cầu bắt buộc chỉ lấy Busy_Checked ở trên rồi.

            //TODO 3. Sắp xếp
            // Ưu tiên 1: Busy_Checked (1) xếp trước Rolled (2)
            // Ưu tiên 2: Trong cùng trạng thái thì thằng nào mới cập nhật sẽ lên trên (updated_time DESC)
            $sql .= " ORDER BY 
            CASE 
                WHEN bobin_current_status = 'Busy_Checked' THEN 1 
                WHEN bobin_current_status = 'Rolled' THEN 2 
                ELSE 3 
            END ASC, 
            updated_time DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("DB Error: " . $e->getMessage());
            // Tùy chọn: return [] hoặc throw Exception
            throw new Exception("Database error: " . $e->getMessage());
        }
    }
    public function getBobinListGeneral(): array
    {
        $pdo = $this->db->pdo();
        try {
            $sql = "SELECT * FROM bobin_list_general";
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $response = $stmt->fetchAll(PDO::FETCH_ASSOC);
            GlobalData::$listBobinEntity = $response;
            return $response;
        } catch (PDOException $e) {
            error_log("DB Error: " . $e->getMessage());
            throw new Exception("Database error: " . $e->getMessage());
        }
    }


    #endregion
    #region GET SPECIFIC
    //? Get Specific Bobin Detail by Identification Code
    public function getSpecificBobin(string $bobinIdentificationCode): ?array
    {
        $pdo = $this->db->pdo();

        try {
            $sql = "SELECT * FROM bobin_list_detail WHERE bobin_identification_code = :ident LIMIT 1";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':ident' => $bobinIdentificationCode]);
            $response = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($response !== null) {
                GlobalData::$bobinEntity = $this->mapArrayToBobinEntity($response);
            }
            return $response;
        } catch (PDOException $e) {
            error_log("DB Error: " . $e->getMessage());
            throw new Exception("Database error: " . $e->getMessage());
        }
    }
    #endregion

    #region Ext POST

    public function createNewBobin(BobinEntity $entity): bool
    {
        $pdo = $this->db->pdo();
        try {
            $pdo->beginTransaction();

            if ($entity->currentStatus === 'Cancelled') {
                if (GlobalData::$bobinEntity !== null) {
                    $oldEntity = GlobalData::$bobinEntity;
                    $this->insertBobinHistoryRecord($pdo,  $oldEntity, 'Cancelled');
                }
            }

            $entity->currentStatus = 'Busy_Unchecked';

            $this->insertBobinHistoryRecord($pdo, $entity, 'Busy_Unchecked');
            $this->insertBobinDetailRecord($pdo, $entity);
            $this->insertBobinGeneralRecord($pdo, $entity);

            $pdo->commit();
            return true;
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("DB Error: " . $e->getMessage());
            throw new Exception("Lỗi Database: " . $e->getMessage());
        }
    }

    private function insertBobinHistoryRecord(PDO $pdo, BobinEntity $entity, string $nextstatus): void
    {

        $timeExpression = ($nextstatus === "Busy_Unchecked")
            ? "DATE_ADD(NOW(), INTERVAL 1 SECOND)"
            : "NOW()";

        $sql = "INSERT INTO bobin_history (
                bobin_key_code, bobin_identification_code, bobin_size, bobin_type,
                extrusion_employee, products, material_lot, print_lot, length_m,
                shift, extrusion_date, finish_time, visual_inspection, winding_machine,
                winding_employee, flow_test_result, bobin_current_status, winding_note, updated_time
            ) VALUES (
                :key, :ident, :size, :type, :extrusionemp, :prod, :mat, :plot, :len,
                :shift, :edate, :ftime, :visual, :winding_machine, :winding_employee,
                :flow_test_result, :status, :note, {$timeExpression}
            )";

        $stmt = $pdo->prepare($sql);
        $fixedEntity = new BobinEntity();
        $fixedEntity = $entity;
        if ($nextstatus === "Cancelled") {
            $fixedEntity->currentStatus = 'Cancelled';
        }
        if ($nextstatus === 'Busy_Unchecked') {
            $fixedEntity->currentStatus = 'Busy_Unchecked';
        }
        $stmt->execute($this->getBobinInsertParams($fixedEntity));
    }
    private function insertBobinDetailRecord(PDO $pdo, BobinEntity $entity): void
    {
        $sql = "INSERT INTO bobin_list_detail (
                    bobin_key_code, bobin_identification_code, bobin_size, bobin_type,
                    extrusion_employee, products, material_lot, print_lot, length_m,
                    shift, extrusion_date, finish_time, visual_inspection, winding_machine,
                    winding_employee, flow_test_result, bobin_current_status, winding_note, updated_time
                ) VALUES (
                    :key, :ident, :size, :type, :extrusionemp, :prod, :mat, :plot, :len,
                    :shift, :edate, :ftime, :visual, :winding_machine, :winding_employee,
                    :flow_test_result, :status, :note, :updated
                ) ON DUPLICATE KEY UPDATE
                    bobin_key_code = VALUES(bobin_key_code),
                    bobin_size = VALUES(bobin_size),
                    bobin_type = VALUES(bobin_type),
                    extrusion_employee = VALUES(extrusion_employee),
                    products = VALUES(products),
                    material_lot = VALUES(material_lot),
                    print_lot = VALUES(print_lot),
                    length_m = VALUES(length_m),
                    shift = VALUES(shift),
                    extrusion_date = VALUES(extrusion_date),
                    finish_time = VALUES(finish_time),
                    bobin_current_status = VALUES(bobin_current_status),
                    visual_inspection = VALUES(visual_inspection),
                    winding_machine = VALUES(winding_machine),
                    winding_employee = VALUES(winding_employee),
                    flow_test_result = VALUES(flow_test_result),
                    winding_note = VALUES(winding_note),
                    updated_time = VALUES(updated_time)";

        $stmt = $pdo->prepare($sql);
        $params = $this->getBobinInsertParams($entity);
        $params[':status'] = 'Busy_Unchecked';
        $params[':updated'] = $entity->updatedTime->format('Y-m-d H:i:s');
        $stmt->execute($params);
    }
    private function insertBobinGeneralRecord(PDO $pdo, BobinEntity $entity): void
    {
        $sql = "INSERT INTO bobin_list_general (
                    bobin_key_code, bobin_identification_code, bobin_size, bobin_type,
                    bobin_current_status, updated_time
                ) VALUES (
                    :key, :ident, :size, :type, :status, NOW()
                ) ON DUPLICATE KEY UPDATE
                    bobin_key_code = VALUES(bobin_key_code),
                    bobin_size = VALUES(bobin_size),
                    bobin_type = VALUES(bobin_type),
                    bobin_current_status = VALUES(bobin_current_status),
                    updated_time = NOW()";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':key' => $entity->bobinKeyCode,
            ':ident' => $entity->identificationCode,
            ':size' => $entity->size,
            ':type' => $entity->type,
            ':status' => 'Busy_Unchecked'
        ]);
    }

    private function getBobinInsertParams(BobinEntity $entity): array
    {
        return [
            ':key' => $entity->bobinKeyCode,
            ':ident' => $entity->identificationCode,
            ':size' => $entity->size,
            ':type' => $entity->type,
            ':extrusionemp' => $this->json_utf8($entity->extrusion_employee),
            ':prod' => $this->json_utf8($entity->product),
            ':mat' => $this->json_utf8($entity->materialLot),
            ':plot' => $entity->printLot,
            ':len' => $entity->length,
            ':shift' => $entity->shift,
            ':edate' => $entity->extrusionDate->format('Y-m-d'),
            ':ftime' => $entity->finishTime->format('Y-m-d H:i:s'),
            ':status' => $entity->currentStatus,
            ':visual' => $this->json_utf8($entity->visualInspection),
            ':winding_machine' => $entity->winding_machine,
            ':winding_employee' => $entity->winding_employee ? $this->json_utf8($entity->winding_employee) : null,
            ':flow_test_result' => $entity->flow_test_result,
            ':note' => $entity->winding_note
        ];
    }

    #region Ext PUT
    public function extrusionUpdateBobin(BobinEntity $entity): bool
    {
        $pdo = $this->db->pdo();
        try {
            $pdo->beginTransaction();

            $entity->currentStatus = 'Busy_Unchecked';

            $this->extUpdateBobinDetail($pdo, $entity);
            $this->extUpdateBobinGeneral($pdo, $entity);
            $this->extInsertBobinHistoryAfterUpdate($pdo, $entity);

            $pdo->commit();
            return true;
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("DB Error: " . $e->getMessage());
            throw new Exception("Lỗi Database: " . $e->getMessage());
        }
    }

    private function extUpdateBobinDetail(PDO $pdo, BobinEntity $entity): void
    {
        $sql = "UPDATE bobin_list_detail SET
                    bobin_type = :type,
                    extrusion_employee = :extrusionemp,
                    products = :prod,
                    material_lot = :mat,
                    print_lot = :plot,
                    length_m = :len,
                    shift = :shift,
                    extrusion_date = :edate,
                    finish_time = :ftime,
                    bobin_current_status = :status,
                    updated_time = :updated
                WHERE bobin_identification_code = :ident";

        $stmt = $pdo->prepare($sql);
        $params = $this->getBobinExtUpdateParams($entity);
        $params[':status'] = 'Busy_Unchecked';
        $params[':updated'] = $entity->updatedTime->format('Y-m-d H:i:s');
        $stmt->execute($params);
    }

    private function extUpdateBobinGeneral(PDO $pdo, BobinEntity $entity): void
    {
        $sql = "UPDATE bobin_list_general SET
                    bobin_type = :type,
                    bobin_current_status = :status,
                    updated_time = NOW()
                WHERE bobin_identification_code = :ident";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':ident' => $entity->identificationCode,
            ':type' => $entity->type,
            ':status' => 'Busy_Unchecked'
        ]);
    }

    private function extInsertBobinHistoryAfterUpdate(PDO $pdo, BobinEntity $entity): void
    {
        $sql = "INSERT INTO bobin_history (
                    bobin_key_code, bobin_identification_code, bobin_size, bobin_type,
                    extrusion_employee, products, material_lot, print_lot, length_m,
                    shift, extrusion_date, finish_time, visual_inspection, winding_machine,
                    winding_employee, flow_test_result, bobin_current_status, winding_note, updated_time
                ) SELECT 
                    bobin_key_code, bobin_identification_code, bobin_size, bobin_type, 
                    extrusion_employee, products, material_lot, print_lot, length_m, 
                    shift, extrusion_date, finish_time, visual_inspection, 
                    winding_machine, winding_employee, flow_test_result, bobin_current_status, winding_note, updated_time
                FROM bobin_list_detail
                WHERE bobin_key_code = :key 
                AND bobin_identification_code = :ident";

        $stmtHistory = $pdo->prepare($sql);
        $stmtHistory->execute([
            ':key'   => $entity->bobinKeyCode,
            ':ident' => $entity->identificationCode
        ]);
    }

    private function getBobinExtUpdateParams(BobinEntity $entity): array
    {
        return [
            ':ident' => $entity->identificationCode,
            ':type' => $entity->type,
            ':extrusionemp' => $this->json_utf8($entity->extrusion_employee),
            ':prod' => $this->json_utf8($entity->product),
            ':mat' => $this->json_utf8($entity->materialLot),
            ':plot' => $entity->printLot,
            ':len' => $entity->length,
            ':shift' => $entity->shift,
            ':edate' => $entity->extrusionDate->format('Y-m-d'),
            ':ftime' => $entity->finishTime->format('Y-m-d H:i:s'),
        ];
    }
    #endregion

    //! Cancel Winding
    public function deleteBobin(BobinEntity $entity)
    {
        $pdo = $this->db->pdo();

        try {
            $pdo->beginTransaction();

            $sql = "UPDATE bobin_list_detail SET
                bobin_current_status = :status,
                updated_time = :updated
            WHERE bobin_key_code = :key
                AND bobin_identification_code = :ident";

            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                ':status'   => $entity->currentStatus,
                ':updated'  => $entity->updatedTime->format('Y-m-d H:i:s'),
                ':key'      => $entity->bobinKeyCode,
                ':ident'    => $entity->identificationCode,
            ]);

            $sqlGeneral = "UPDATE bobin_list_general SET
                            bobin_current_status = :status,
                            updated_time = NOW()
                       WHERE bobin_key_code = :key
                            AND bobin_identification_code = :ident";

            $stmtGen = $pdo->prepare($sqlGeneral);

            $stmtGen->execute([
                ':status'   => $entity->currentStatus,
                ':key'      => $entity->bobinKeyCode,
                ':ident'    => $entity->identificationCode
            ]);
            $sqlHistory = "INSERT INTO bobin_history (
                                bobin_key_code, bobin_identification_code, bobin_size, bobin_type, 
                                extrusion_employee, products, material_lot, print_lot, length_m, 
                                shift, extrusion_date, finish_time, visual_inspection, 
                                winding_machine, winding_employee, bobin_current_status, winding_note, flow_test_result, updated_time
                            )
                            SELECT 
                                bobin_key_code, bobin_identification_code, bobin_size, bobin_type, 
                                extrusion_employee, products, material_lot, print_lot, length_m, 
                                shift, extrusion_date, finish_time, visual_inspection, 
                                winding_machine, winding_employee, bobin_current_status, winding_note, flow_test_result, updated_time
                            FROM bobin_list_detail
                            WHERE bobin_key_code = :key 
                                AND bobin_identification_code = :ident";


            $stmtHistory = $pdo->prepare($sqlHistory);
            $stmtHistory->execute([
                ':key'      => $entity->bobinKeyCode,
                ':ident'    => $entity->identificationCode,
            ]);
            if ($stmt->rowCount() === 0 && $stmtGen->rowCount() === 0 && $stmtHistory->rowCount() === 0) {
                throw new Exception("Không tìm thấy dữ liệu khớp hoặc dữ liệu chưa được thay đổi.");
            }

            $pdo->commit();
            return true;
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("DB Error: " . $e->getMessage());
            throw new Exception("Lỗi Database: " . $e->getMessage());
        }
    }



    #region Ext DELETE
    public function extrusionDeleteBobin(BobinEntity $entity): bool
    {
        $pdo = $this->db->pdo();
        try {
            $pdo->beginTransaction();

            $entity->currentStatus = 'Pending_Cancellation';

            $this->extDeleteBobinDetail($pdo, $entity);
            $this->extDeleteBobinGeneral($pdo, $entity);
            $this->extInsertBobinHistoryAfterDelete($pdo, $entity);

            $pdo->commit();
            return true;
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("DB Error: " . $e->getMessage());
            throw new Exception("Lỗi Database: " . $e->getMessage());
        }
    }

    private function extDeleteBobinDetail(PDO $pdo, BobinEntity $entity): void
    {
        $sql = "UPDATE bobin_list_detail SET
                    extrusion_employee = :extrusionemp,
                    bobin_current_status = :status,
                    updated_time = :updated
                WHERE bobin_identification_code = :ident";

        $stmt = $pdo->prepare($sql);
        $params = $this->getBobinExtDeleteParams($entity);
        $params[':status'] = 'Pending_Cancellation';
        $params[':updated'] = $entity->updatedTime->format('Y-m-d H:i:s');
        $stmt->execute($params);
    }

    private function extDeleteBobinGeneral(PDO $pdo, BobinEntity $entity): void
    {
        $sql = "UPDATE bobin_list_general SET
                    bobin_current_status = :status,
                    updated_time = NOW()
                WHERE bobin_identification_code = :ident";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':ident' => $entity->identificationCode,
            ':status' => 'Pending_Cancellation'
        ]);
    }

    private function extInsertBobinHistoryAfterDelete(PDO $pdo, BobinEntity $entity): void
    {
        $sql = "INSERT INTO bobin_history (
                    bobin_key_code, bobin_identification_code, bobin_size, bobin_type,
                    extrusion_employee, products, material_lot, print_lot, length_m,
                    shift, extrusion_date, finish_time, visual_inspection, winding_machine,
                    winding_employee, flow_test_result, bobin_current_status, winding_note, updated_time
                ) SELECT 
                    bobin_key_code, bobin_identification_code, bobin_size, bobin_type, 
                    extrusion_employee, products, material_lot, print_lot, length_m, 
                    shift, extrusion_date, finish_time, visual_inspection, 
                    winding_machine, winding_employee, flow_test_result, bobin_current_status, winding_note, updated_time
                FROM bobin_list_detail
                WHERE bobin_key_code = :key 
                AND bobin_identification_code = :ident";

        $stmtHistory = $pdo->prepare($sql);
        $stmtHistory->execute([
            ':key'   => $entity->bobinKeyCode,
            ':ident' => $entity->identificationCode
        ]);
    }

    private function getBobinExtDeleteParams(BobinEntity $entity): array
    {
        return [
            ':ident' => $entity->identificationCode,
            ':extrusionemp' => $this->json_utf8($entity->extrusion_employee),
        ];
    }
    #endregion


    #region PUT
    //! PUT QC
    public function updateQCBobinInfor(BobinEntity $entity): bool
    {
        $pdo = $this->db->pdo();
        try {
            $pdo->beginTransaction();

            $sqlDetail = "UPDATE bobin_list_detail SET
                    bobin_current_status = :status,
                    visual_inspection = :visual,
                    updated_time = :updated
                WHERE bobin_key_code = :key
                    AND bobin_identification_code = :ident";

            $stmt = $pdo->prepare($sqlDetail);

            $stmt->execute([
                ':status'   => $entity->currentStatus,
                ':visual'   => $this->json_utf8($entity->visualInspection),
                ':updated'  => $entity->updatedTime->format('Y-m-d H:i:s'),
                ':key'      => $entity->bobinKeyCode,
                ':ident'    => $entity->identificationCode
            ]);

            $sqlGeneral = "UPDATE bobin_list_general SET
                            bobin_current_status = :status,
                            updated_time = NOW()
                       WHERE bobin_key_code = :key
                            AND bobin_identification_code = :ident";

            $stmtGen = $pdo->prepare($sqlGeneral);
            $stmtGen->execute([
                ':status'   => $entity->currentStatus,
                ':key'      => $entity->bobinKeyCode,
                ':ident'    => $entity->identificationCode
            ]);

            $sqlHistory = "INSERT INTO bobin_history (
                                 bobin_key_code, bobin_identification_code, bobin_size, bobin_type, 
                            extrusion_employee, products, material_lot, print_lot, length_m, 
                            shift, extrusion_date, finish_time,  
                            visual_inspection, winding_machine, winding_employee, bobin_current_status, winding_note, flow_test_result, updated_time
                            )
                            SELECT 
                                bobin_key_code, bobin_identification_code, bobin_size, bobin_type, 
                                extrusion_employee, products, material_lot, print_lot, length_m, 
                                shift, extrusion_date, finish_time, visual_inspection, 
                                winding_machine, winding_employee, bobin_current_status, winding_note, flow_test_result, updated_time
                            FROM bobin_list_detail
                            WHERE bobin_key_code = :key 
                                AND bobin_identification_code = :ident";

            $stmtHistory = $pdo->prepare($sqlHistory);
            $stmtHistory->execute([
                ':key'      => $entity->bobinKeyCode,
                ':ident'    => $entity->identificationCode
            ]);

            if (
                $stmt->rowCount() === 0 && $stmtGen->rowCount() === 0
                && $stmtHistory->rowCount() === 0
            ) {
                throw new Exception("Không tìm thấy dữ liệu khớp hoặc dữ liệu chưa được thay đổi.");
            }
            $pdo->commit();
            return true;
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("DB Error: " . $e->getMessage());
            throw new Exception("Lỗi Database: " . $e->getMessage());
        }
    }

    public function qcCancelBobin(BobinEntity $entity): bool
    {
        $pdo = $this->db->pdo();

        try {
            $pdo->beginTransaction();

            $sql = "UPDATE bobin_list_detail SET
                bobin_current_status = :status,
                visual_inspection = :visual,
                updated_time = :updated
            WHERE bobin_key_code = :key
                AND bobin_identification_code = :ident";

            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                ':status'   => $entity->currentStatus,
                ':updated'  => $entity->updatedTime->format('Y-m-d H:i:s'),
                ':key'      => $entity->bobinKeyCode,
                ':visual'   => $this->json_utf8($entity->visualInspection),
                ':ident'    => $entity->identificationCode
            ]);

            $sqlGeneral = "UPDATE bobin_list_general SET
                            bobin_current_status = :status,
                            updated_time = NOW()
                       WHERE bobin_key_code = :key
                            AND bobin_identification_code = :ident";

            $stmtGen = $pdo->prepare($sqlGeneral);

            $stmtGen->execute([
                ':status'   => $entity->currentStatus,
                ':key'      => $entity->bobinKeyCode,
                ':ident'    => $entity->identificationCode
            ]);
            $sqlHistory = "INSERT INTO bobin_history (
                                bobin_key_code, bobin_identification_code, bobin_size, bobin_type, 
                                extrusion_employee, products, material_lot, print_lot, length_m, 
                                shift, extrusion_date, finish_time, visual_inspection, 
                                winding_machine, winding_employee, bobin_current_status, winding_note, flow_test_result, updated_time
                            )
                            SELECT 
                                bobin_key_code, bobin_identification_code, bobin_size, bobin_type, 
                                extrusion_employee, products, material_lot, print_lot, length_m, 
                                shift, extrusion_date, finish_time, visual_inspection, 
                                winding_machine, winding_employee, bobin_current_status, winding_note, flow_test_result, updated_time
                            FROM bobin_list_detail
                            WHERE bobin_key_code = :key 
                                AND bobin_identification_code = :ident
                                AND visual_inspection = :visual";

            $stmtHistory = $pdo->prepare($sqlHistory);
            $stmtHistory->execute([
                ':key'      => $entity->bobinKeyCode,
                ':ident'    => $entity->identificationCode,
                ':visual'   => $this->json_utf8($entity->visualInspection)
            ]);
            if ($stmt->rowCount() === 0 && $stmtGen->rowCount() === 0 && $stmtHistory->rowCount() === 0) {
                throw new Exception("Không tìm thấy dữ liệu khớp hoặc dữ liệu chưa được thay đổi.");
            }

            $pdo->commit();
            return true;
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("DB Error: " . $e->getMessage());
            throw new Exception("Lỗi Database: " . $e->getMessage());
        }
    }

    //! PUT Winding
    public function updateWindingBobinInfor(BobinEntity $entity): bool
    {
        $pdo = $this->db->pdo();

        try {
            $pdo->beginTransaction();

            $sql = "UPDATE bobin_list_detail SET
                    bobin_current_status = :status,
                    winding_machine = :winding_machine,
                    winding_employee = :winding_employee,
                    winding_note = :winding_note,
                    flow_test_result = :flow_test_result,
                    updated_time = :updated
                WHERE bobin_key_code = :key
                    AND bobin_identification_code = :ident";

            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                ':status'   => $entity->currentStatus,
                ':winding_machine' => $entity->winding_machine,
                ':winding_employee' => $this->json_utf8($entity->winding_employee),
                ':winding_note' => $entity->winding_note,
                ':flow_test_result' => $entity->flow_test_result,
                ':updated'  => $entity->updatedTime->format('Y-m-d H:i:s'),
                ':key'      => $entity->bobinKeyCode,
                ':ident'    => $entity->identificationCode
            ]);

            $sqlGeneral = "UPDATE bobin_list_general SET
                            bobin_current_status = :status,
                            updated_time = NOW()
                       WHERE bobin_key_code = :key
                            AND bobin_identification_code = :ident";

            $stmtGen = $pdo->prepare($sqlGeneral);
            $stmtGen->execute([
                ':status'   => $entity->currentStatus,
                ':key'      => $entity->bobinKeyCode,
                ':ident'    => $entity->identificationCode
            ]);

            $sqlHistory = "INSERT INTO bobin_history (
                                bobin_key_code, bobin_identification_code, bobin_size, bobin_type, 
                                extrusion_employee, products, material_lot, print_lot, length_m, 
                                shift, extrusion_date, finish_time, visual_inspection, 
                                winding_machine, winding_employee, bobin_current_status, winding_note, flow_test_result, updated_time
                            )
                            SELECT 
                                bobin_key_code, bobin_identification_code, bobin_size, bobin_type, 
                                extrusion_employee, products, material_lot, print_lot, length_m, 
                                shift, extrusion_date, finish_time, visual_inspection, 
                                winding_machine, winding_employee, bobin_current_status, winding_note, flow_test_result, updated_time
                            FROM bobin_list_detail
                            WHERE bobin_key_code = :key 
                                AND bobin_identification_code = :ident
                                AND winding_machine = :winding_machine
                                AND winding_employee = :winding_employee
                                AND flow_test_result = :flow_test_result
                                AND winding_note = :winding_note";

            $stmtHistory = $pdo->prepare($sqlHistory);
            $stmtHistory->execute([
                ':key'      => $entity->bobinKeyCode,
                ':ident'    => $entity->identificationCode,
                ':winding_machine' => $entity->winding_machine,
                ':winding_employee' => $this->json_utf8($entity->winding_employee),
                ':winding_note' => $entity->winding_note ?? "Không có ghi chú",
                ':flow_test_result' => $entity->flow_test_result
            ]);

            if (
                $stmt->rowCount() === 0 && $stmtGen->rowCount() === 0
                && $stmtHistory->rowCount() === 0
            ) {
                throw new Exception("Không tìm thấy dữ liệu khớp hoặc dữ liệu chưa được thay đổi.");
            }

            $pdo->commit();
            return true;
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("DB Error: " . $e->getMessage());
            throw new Exception("Lỗi Database: " . $e->getMessage());
        }
    }


    //! Cancel Winding
    public function windingCancelBobin(BobinEntity $entity): bool
    {
        $pdo = $this->db->pdo();

        try {
            $pdo->beginTransaction();

            $sql = "UPDATE bobin_list_detail SET
                bobin_current_status = :status,
                updated_time = :updated,
                winding_machine = :winding_machine,
                winding_employee = :winding_employee,
                flow_test_result = :flow_test_result,
                winding_note = :winding_note
            WHERE bobin_key_code = :key
                AND bobin_identification_code = :ident";

            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                ':status'   => $entity->currentStatus,
                ':updated'  => $entity->updatedTime->format('Y-m-d H:i:s'),
                ':key'      => $entity->bobinKeyCode,
                ':ident'    => $entity->identificationCode,
                ':winding_machine' => $entity->winding_machine,
                ':winding_employee' => $this->json_utf8($entity->winding_employee),
                ':flow_test_result' => $entity->flow_test_result,
                ':winding_note' => $entity->winding_note ?? "Không có ghi chú (Yêu cầu hủy phía cuộn)"
            ]);

            $sqlGeneral = "UPDATE bobin_list_general SET
                            bobin_current_status = :status,
                            updated_time = NOW()
                       WHERE bobin_key_code = :key
                            AND bobin_identification_code = :ident";

            $stmtGen = $pdo->prepare($sqlGeneral);

            $stmtGen->execute([
                ':status'   => $entity->currentStatus,
                ':key'      => $entity->bobinKeyCode,
                ':ident'    => $entity->identificationCode
            ]);
            $sqlHistory = "INSERT INTO bobin_history (
                                bobin_key_code, bobin_identification_code, bobin_size, bobin_type, 
                                extrusion_employee, products, material_lot, print_lot, length_m, 
                                shift, extrusion_date, finish_time, visual_inspection, 
                                winding_machine, winding_employee, bobin_current_status, winding_note, flow_test_result, updated_time
                            )
                            SELECT 
                                bobin_key_code, bobin_identification_code, bobin_size, bobin_type, 
                                extrusion_employee, products, material_lot, print_lot, length_m, 
                                shift, extrusion_date, finish_time, visual_inspection, 
                                winding_machine, winding_employee, bobin_current_status, winding_note, flow_test_result, updated_time
                            FROM bobin_list_detail
                            WHERE bobin_key_code = :key 
                                AND bobin_identification_code = :ident
                                AND winding_machine = :winding_machine
                                AND winding_employee = :winding_employee
                                AND winding_note = :winding_note
                                AND flow_test_result = :flow_test_result";


            $stmtHistory = $pdo->prepare($sqlHistory);
            $stmtHistory->execute([
                ':key'      => $entity->bobinKeyCode,
                ':ident'    => $entity->identificationCode,
                ':winding_machine' => $entity->winding_machine,
                ':winding_employee' => $this->json_utf8($entity->winding_employee),
                ':winding_note' => $entity->winding_note ?? "Không có ghi chú (Yêu cầu hủy phía cuộn)",
                ':flow_test_result' => $entity->flow_test_result
            ]);
            if ($stmt->rowCount() === 0 && $stmtGen->rowCount() === 0 && $stmtHistory->rowCount() === 0) {
                throw new Exception("Không tìm thấy dữ liệu khớp hoặc dữ liệu chưa được thay đổi.");
            }

            $pdo->commit();
            return true;
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("DB Error: " . $e->getMessage());
            throw new Exception("Lỗi Database: " . $e->getMessage());
        }
    }

    #region JSON
    private function json_utf8($data): string
    {
        return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    }
    #endregion

    private function mapArrayToBobinEntity(array $data): BobinEntity
    {
        $entity = new BobinEntity();
        $entity = BobinEntity::fromJson($this->json_utf8($data));
        return $entity;
    }

    #region FIND KEY CODE
    public function findBobinKeyCode(string $identificationCode): ?string
    {
        $list = GlobalData::$listBobinEntity;
        foreach ($list as $bobin) {
            if (!empty($bobin['bobin_identification_code']) && $bobin['bobin_identification_code'] === $identificationCode) {
                return $bobin['bobin_key_code'];
            }
        }
        return null;
    }
    #endregion

    #region FIND SIZE
    public function findBobinSize(string $identificationCode): ?string
    {
        $list = GlobalData::$listBobinEntity;
        foreach ($list as $bobin) {
            if (!empty($bobin['bobin_identification_code']) && $bobin['bobin_identification_code'] === $identificationCode) {
                return $bobin['bobin_size'];
            }
        }
        return null;
    }
    #endregion
}