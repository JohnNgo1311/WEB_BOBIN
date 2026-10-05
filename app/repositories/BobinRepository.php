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
    // Bổ sung hàm lấy toàn bộ Rack để hiển thị lên Dropdown
    public function getAllRacks(): array
    {
        $pdo = $this->db->pdo();
        try {
            $sql = "SELECT id, rack_code FROM rack_list ORDER BY rack_code ASC";
            $stmt = $pdo->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("DB Error: " . $e->getMessage());
            return [];
        }
    }
    public function getBobinCapacities(): array
    {
        $pdo = $this->db->pdo();
        try {
            $sql = "SELECT size_name, capacity FROM bobin_capacity";
            $stmt = $pdo->query($sql);
            return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        } catch (PDOException $e) {
            error_log("DB Error: " . $e->getMessage());
            return [];
        }
    }

    public function updateBobinCapacity(string $sizeName, int $newCapacity): bool
    {
        $pdo = $this->db->pdo();
        try {
            $sql = "UPDATE bobin_capacity SET capacity = :capacity WHERE size_name = :size_name";
            $stmt = $pdo->prepare($sql);
            return $stmt->execute([
                ':capacity'  => $newCapacity,
                ':size_name' => $sizeName
            ]);
        } catch (PDOException $e) {
            error_log("DB Error: " . $e->getMessage());
            throw new Exception("Lỗi cập nhật cấu hình: " . $e->getMessage());
        }
    }
    //! GET
    #region GET LIST

    public function getBobinsHistory(array $filters = [], array $selectedIds = [])
    {
        $pdo = $this->db->pdo();

        try {
            $statusFilter = !empty($filters['status']) ? trim($filters['status']) : 'all';
            $fromDateStr  = $filters['from_date'];
            $toDateStr    = $filters['to_date'];

            $params = [];

            // Biểu thức chuyển đổi thời gian từ bobin_key_code (VD: A0001_2026_10_04_18_29_44)
            $keyTimeExpr = "STR_TO_DATE(SUBSTRING_INDEX(h.bobin_key_code, '_', -6), '%Y_%m_%d_%H_%i_%s')";
            $keyTimeSubQueryExpr = "STR_TO_DATE(SUBSTRING_INDEX(bobin_key_code, '_', -6), '%Y_%m_%d_%H_%i_%s')";

            if ($statusFilter === 'Extruded') {
                // 1. Chức năng "ĐÃ ĐÙN":
                // Bước 1: Lọc danh sách các Bobin có updated_time VÀ giá trị thời gian trong bobin_key_code thuộc thời điểm đầu cuối đã chọn => Danh sách (1)
                // Bước 2: Nhóm theo bobin_key_code lấy version có updated_time mới nhất (MAX(id)).
                // Đảm bảo độc nhất bobin_key_code, bobin_identification_code có thể trùng nhau => Danh sách (2)
                $sql = "SELECT h.* FROM bobin_history h
                        INNER JOIN (
                            SELECT bobin_key_code, MAX(id) AS max_id
                            FROM bobin_history
                            WHERE updated_time BETWEEN :from_date AND :to_date
                              AND $keyTimeSubQueryExpr BETWEEN :from_date_key AND :to_date_key
                            GROUP BY bobin_key_code
                        ) latest_key ON h.id = latest_key.max_id";

                $params[':from_date']     = $fromDateStr;
                $params[':to_date']       = $toDateStr;
                $params[':from_date_key'] = $fromDateStr;
                $params[':to_date_key']   = $toDateStr;
            } elseif ($statusFilter === 'Busy_Unchecked') {
                // 3. Chức năng "CHƯA KT QC":
                // Tương ứng với số lượng Bobin "ĐÃ ĐÙN" ở trên (có updated_time & key_time trong [from_date, to_date]),
                // tính đến 23:59:59 của thời điểm cuối đã chọn (to_date), tập hợp những Bobin chỉ ở trạng thái Busy_Unchecked.
                $sql = "SELECT h.* FROM bobin_history h
                        INNER JOIN (
                            SELECT bobin_key_code, MAX(id) AS max_id
                            FROM bobin_history
                            WHERE updated_time <= :to_date_scope
                              AND bobin_key_code IN (
                                  SELECT DISTINCT bobin_key_code 
                                  FROM bobin_history 
                                  WHERE updated_time BETWEEN :from_date_ext AND :to_date_ext
                                    AND $keyTimeSubQueryExpr BETWEEN :from_date_key AND :to_date_key
                              )
                            GROUP BY bobin_key_code
                        ) latest_key ON h.id = latest_key.max_id
                        WHERE h.bobin_current_status = 'Busy_Unchecked'";

                $params[':to_date_scope'] = $toDateStr;
                $params[':from_date_ext'] = $fromDateStr;
                $params[':to_date_ext']   = $toDateStr;
                $params[':from_date_key'] = $fromDateStr;
                $params[':to_date_key']   = $toDateStr;
            } elseif (in_array($statusFilter, ['Busy_Checked', 'Busy_Checked_InPeriod', 'Busy_Checked_Before'])) {
                // 2. Chức năng "ĐÃ KT QC":
                // Có 2 trường hợp: Đùn trong khoảng thời gian hoặc đùn trước đó
                $sql = "SELECT h.* FROM bobin_history h
                        INNER JOIN (
                            SELECT bobin_key_code, MAX(id) AS max_id
                            FROM bobin_history
                            WHERE updated_time BETWEEN :from_date AND :to_date
                              AND bobin_current_status = 'Busy_Checked'
                            GROUP BY bobin_key_code
                        ) latest_key ON h.id = latest_key.max_id";

                if ($statusFilter === 'Busy_Checked_InPeriod') {
                    $sql .= " WHERE $keyTimeExpr BETWEEN :from_key AND :to_key";
                    $params[':from_key'] = $fromDateStr;
                    $params[':to_key']   = $toDateStr;
                } elseif ($statusFilter === 'Busy_Checked_Before') {
                    $sql .= " WHERE $keyTimeExpr < :from_key";
                    $params[':from_key'] = $fromDateStr;
                }

                $params[':from_date'] = $fromDateStr;
                $params[':to_date']   = $toDateStr;
            } elseif ($statusFilter === 'Rolled') {
                // 5. Chức năng "ĐÃ CUỘN":
                // Lọc ra các Bobin ở trạng thái Rolled trong khoảng thời gian [from, to], độc nhất bobin_key_code
                $sql = "SELECT h.* FROM bobin_history h
                        INNER JOIN (
                            SELECT bobin_key_code, MAX(id) AS max_id
                            FROM bobin_history
                            WHERE updated_time BETWEEN :from_date AND :to_date
                              AND bobin_current_status = 'Rolled'
                            GROUP BY bobin_key_code
                        ) latest_key ON h.id = latest_key.max_id";

                $params[':from_date'] = $fromDateStr;
                $params[':to_date']   = $toDateStr;
            } elseif (in_array($statusFilter, ['Cancelled', 'Cancelled_InPeriod', 'Cancelled_Before'])) {
                // 3 & 4. Chức năng "ĐÃ HỦY":
                $sql = "SELECT h.* FROM bobin_history h
                        INNER JOIN (
                            SELECT bobin_key_code, MAX(id) AS max_id
                            FROM bobin_history
                            WHERE updated_time BETWEEN :from_date AND :to_date
                              AND bobin_current_status = 'Cancelled'
                            GROUP BY bobin_key_code
                        ) latest_key ON h.id = latest_key.max_id";

                if ($statusFilter === 'Cancelled_InPeriod') {
                    $sql .= " WHERE $keyTimeExpr BETWEEN :from_key AND :to_key";
                    $params[':from_key'] = $fromDateStr;
                    $params[':to_key']   = $toDateStr;
                } elseif ($statusFilter === 'Cancelled_Before') {
                    $sql .= " WHERE $keyTimeExpr < :from_key";
                    $params[':from_key'] = $fromDateStr;
                }

                $params[':from_date'] = $fromDateStr;
                $params[':to_date']   = $toDateStr;
            } elseif (in_array($statusFilter, ['Pending_Cancellation', 'Pending_Cancellation_InPeriod', 'Pending_Cancellation_Before'])) {
                // 3 & 4. Chức năng "CHỜ HỦY":
                $sql = "SELECT h.* FROM bobin_history h
                        INNER JOIN (
                            SELECT bobin_key_code, MAX(id) AS max_id
                            FROM bobin_history
                            WHERE updated_time BETWEEN :from_date AND :to_date
                              AND bobin_current_status = 'Pending_Cancellation'
                            GROUP BY bobin_key_code
                        ) latest_key ON h.id = latest_key.max_id";

                if ($statusFilter === 'Pending_Cancellation_InPeriod') {
                    $sql .= " WHERE $keyTimeExpr BETWEEN :from_key AND :to_key";
                    $params[':from_key'] = $fromDateStr;
                    $params[':to_key']   = $toDateStr;
                } elseif ($statusFilter === 'Pending_Cancellation_Before') {
                    $sql .= " WHERE $keyTimeExpr < :from_key";
                    $params[':from_key'] = $fromDateStr;
                }

                $params[':from_date'] = $fromDateStr;
                $params[':to_date']   = $toDateStr;
            } else {
                // Mặc định ("all"):
                $sql = "SELECT h.* FROM bobin_history h
                        WHERE h.updated_time BETWEEN :from_date AND :to_date";

                $params[':from_date'] = $fromDateStr;
                $params[':to_date']   = $toDateStr;
            }

            // NẾU CÓ CHỌN CỤ THỂ CÁC BẢN GHI QUA CHECKBOX
            if (!empty($selectedIds)) {
                $inPlaceholders = [];
                foreach ($selectedIds as $index => $val) {
                    $paramKey = ":sel_id_" . $index;
                    $inPlaceholders[] = $paramKey;
                    $params[$paramKey] = $val;
                }
                $firstVal = reset($selectedIds);
                if (is_numeric($firstVal)) {
                    $sql .= " AND h.id IN (" . implode(',', $inPlaceholders) . ")";
                } else {
                    $sql .= " AND h.bobin_key_code IN (" . implode(',', $inPlaceholders) . ")";
                }
            }

            if (!empty($filters['bobin_size']) && $filters['bobin_size'] !== 'all') {
                $sql .= " AND h.bobin_size = :bsize";
                $params[':bsize'] = $filters['bobin_size'];
            }

            if (!empty($filters['bobin_type']) && $filters['bobin_type'] !== 'all') {
                $sql .= " AND h.bobin_type = :btype";
                $params[':btype'] = $filters['bobin_type'];
            }

            if (!empty($filters['rack']) && $filters['rack'] !== 'all') {
                $rackFilterVal = trim($filters['rack']);
                if (str_starts_with($rackFilterVal, 'Rack_')) {
                    $sql .= " AND JSON_UNQUOTE(JSON_EXTRACT(h.rack, '$.code')) = :rack";
                    $params[':rack'] = $rackFilterVal;
                } else {
                    $sql .= " AND (JSON_UNQUOTE(JSON_EXTRACT(h.rack, '$.code')) LIKE :rack_prefix OR JSON_UNQUOTE(JSON_EXTRACT(h.rack, '$.code')) = :rack_exact)";
                    $params[':rack_prefix'] = 'Rack_' . $rackFilterVal . '_%';
                    $params[':rack_exact']  = $rackFilterVal;
                }
            }

            if (!empty($filters['keyword'])) {
                $searchStr = '%' . trim($filters['keyword']) . '%';
                $sql .= " AND (
                    h.bobin_identification_code LIKE :kw1 OR  
                    JSON_EXTRACT(h.extrusion_employee, '$.employee_code') LIKE :kw2 OR 
                    JSON_EXTRACT(h.extrusion_employee, '$.employee_name') LIKE :kw3 OR 
                    JSON_EXTRACT(h.products, '$.product_code') LIKE :kw4 OR 
                    JSON_EXTRACT(h.products, '$.production_order_code') LIKE :kw5 OR 
                    h.print_lot LIKE :kw6 OR
                    h.winding_machine LIKE :kw7 OR
                    h.bobin_type LIKE :kw8
                )";
                for ($i = 1; $i <= 8; $i++) {
                    $params[":kw$i"] = $searchStr;
                }
            }

            $sql .= " ORDER BY h.updated_time DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("DB Error: " . $e->getMessage());
            throw new Exception("Database error: " . $e->getMessage());
        }
    }

    public function getBobinsHistoryStats(array $filters = []): array
    {
        $pdo = $this->db->pdo();
        try {
            $fromDateStr = $filters['from_date'];
            $toDateStr   = $filters['to_date'];

            // Đoạn phụ lọc thêm (bobin_size, bobin_type, rack, keyword)
            $extraWhere = "";
            $extraParams = [];

            if (!empty($filters['bobin_size']) && $filters['bobin_size'] !== 'all') {
                $extraWhere .= " AND h.bobin_size = :bsize";
                $extraParams[':bsize'] = $filters['bobin_size'];
            }
            if (!empty($filters['bobin_type']) && $filters['bobin_type'] !== 'all') {
                $extraWhere .= " AND h.bobin_type = :btype";
                $extraParams[':btype'] = $filters['bobin_type'];
            }
            if (!empty($filters['rack']) && $filters['rack'] !== 'all') {
                $rackFilterVal = trim($filters['rack']);
                if (str_starts_with($rackFilterVal, 'Rack_')) {
                    $extraWhere .= " AND JSON_UNQUOTE(JSON_EXTRACT(h.rack, '$.code')) = :rack";
                    $extraParams[':rack'] = $rackFilterVal;
                } else {
                    $extraWhere .= " AND (JSON_UNQUOTE(JSON_EXTRACT(h.rack, '$.code')) LIKE :rack_prefix OR JSON_UNQUOTE(JSON_EXTRACT(h.rack, '$.code')) = :rack_exact)";
                    $extraParams[':rack_prefix'] = 'Rack_' . $rackFilterVal . '_%';
                    $extraParams[':rack_exact']  = $rackFilterVal;
                }
            }
            if (!empty($filters['keyword'])) {
                $searchStr = '%' . trim($filters['keyword']) . '%';
                $extraWhere .= " AND (
                    h.bobin_identification_code LIKE :kw1 OR  
                    JSON_EXTRACT(h.extrusion_employee, '$.employee_code') LIKE :kw2 OR 
                    JSON_EXTRACT(h.extrusion_employee, '$.employee_name') LIKE :kw3 OR 
                    JSON_EXTRACT(h.products, '$.product_code') LIKE :kw4 OR 
                    JSON_EXTRACT(h.products, '$.production_order_code') LIKE :kw5 OR 
                    h.print_lot LIKE :kw6 OR
                    h.winding_machine LIKE :kw7 OR
                    h.bobin_type LIKE :kw8
                )";
                for ($i = 1; $i <= 8; $i++) {
                    $extraParams[":kw$i"] = $searchStr;
                }
            }

            $keyTimeExpr = "STR_TO_DATE(SUBSTRING_INDEX(h.bobin_key_code, '_', -6), '%Y_%m_%d_%H_%i_%s')";
            $keyTimeSubQueryExpr = "STR_TO_DATE(SUBSTRING_INDEX(bobin_key_code, '_', -6), '%Y_%m_%d_%H_%i_%s')";

            // 1. ĐÃ ĐÙN: Các Bobin có updated_time VÀ key_time trong [from, to], nhóm theo bobin_key_code lấy bản ghi mới nhất
            $sqlExt = "SELECT COUNT(*) FROM bobin_history h
                       INNER JOIN (
                           SELECT bobin_key_code, MAX(id) AS max_id
                           FROM bobin_history
                           WHERE updated_time BETWEEN :from_date AND :to_date
                             AND $keyTimeSubQueryExpr BETWEEN :from_date_key AND :to_date_key
                           GROUP BY bobin_key_code
                       ) latest_key ON h.id = latest_key.max_id
                       WHERE 1=1" . $extraWhere;
            $paramsExt = array_merge([
                ':from_date'     => $fromDateStr,
                ':to_date'       => $toDateStr,
                ':from_date_key' => $fromDateStr,
                ':to_date_key'   => $toDateStr
            ], $extraParams);
            $stmtExt = $pdo->prepare($sqlExt);
            $stmtExt->execute($paramsExt);
            $extruded = (int)$stmtExt->fetchColumn();

            // 2. CHƯA KT QC: Trong số các Bobin ĐÃ ĐÙN ở trên, tính đến to_date vẫn đang ở Busy_Unchecked
            $sqlUnchecked = "SELECT COUNT(*) FROM bobin_history h
                             INNER JOIN (
                                 SELECT bobin_key_code, MAX(id) AS max_id
                                 FROM bobin_history
                                 WHERE updated_time <= :to_date_scope
                                   AND bobin_key_code IN (
                                       SELECT DISTINCT bobin_key_code 
                                       FROM bobin_history 
                                       WHERE updated_time BETWEEN :from_date_ext AND :to_date_ext
                                         AND $keyTimeSubQueryExpr BETWEEN :from_date_key AND :to_date_key
                                   )
                                 GROUP BY bobin_key_code
                             ) latest_key ON h.id = latest_key.max_id
                             WHERE h.bobin_current_status = 'Busy_Unchecked'" . $extraWhere;
            $paramsUnchecked = array_merge([
                ':to_date_scope' => $toDateStr,
                ':from_date_ext' => $fromDateStr,
                ':to_date_ext'   => $toDateStr,
                ':from_date_key' => $fromDateStr,
                ':to_date_key'   => $toDateStr
            ], $extraParams);
            $stmtUnchecked = $pdo->prepare($sqlUnchecked);
            $stmtUnchecked->execute($paramsUnchecked);
            $busyUnchecked = (int)$stmtUnchecked->fetchColumn();

            // 3. ĐÃ KT QC: Busy_Checked trong [from, to], độc nhất theo bobin_key_code
            $sqlChecked = "SELECT 
                              COUNT(*) as total,
                              SUM(CASE WHEN $keyTimeExpr BETWEEN :from_key AND :to_key THEN 1 ELSE 0 END) as in_period,
                              SUM(CASE WHEN $keyTimeExpr < :from_key_before THEN 1 ELSE 0 END) as before_period
                           FROM bobin_history h
                           INNER JOIN (
                               SELECT bobin_key_code, MAX(id) AS max_id
                               FROM bobin_history
                               WHERE updated_time BETWEEN :from_date AND :to_date
                                 AND bobin_current_status = 'Busy_Checked'
                               GROUP BY bobin_key_code
                           ) latest_key ON h.id = latest_key.max_id
                           WHERE 1=1" . $extraWhere;
            $paramsChecked = array_merge([
                ':from_date'        => $fromDateStr,
                ':to_date'          => $toDateStr,
                ':from_key'         => $fromDateStr,
                ':to_key'           => $toDateStr,
                ':from_key_before'  => $fromDateStr
            ], $extraParams);
            $stmtChecked = $pdo->prepare($sqlChecked);
            $stmtChecked->execute($paramsChecked);
            $rowChecked = $stmtChecked->fetch(PDO::FETCH_ASSOC);
            $busyChecked = (int)($rowChecked['total'] ?? 0);
            $busyCheckedInPeriod = (int)($rowChecked['in_period'] ?? 0);
            $busyCheckedBefore = (int)($rowChecked['before_period'] ?? 0);

            // 4. ĐÃ CUỘN: Rolled trong [from, to], độc nhất theo bobin_key_code
            $sqlRolled = "SELECT COUNT(*) FROM bobin_history h
                          INNER JOIN (
                              SELECT bobin_key_code, MAX(id) AS max_id
                              FROM bobin_history
                              WHERE updated_time BETWEEN :from_date AND :to_date
                                AND bobin_current_status = 'Rolled'
                              GROUP BY bobin_key_code
                          ) latest_key ON h.id = latest_key.max_id
                          WHERE 1=1" . $extraWhere;
            $paramsRolled = array_merge([':from_date' => $fromDateStr, ':to_date' => $toDateStr], $extraParams);
            $stmtRolled = $pdo->prepare($sqlRolled);
            $stmtRolled->execute($paramsRolled);
            $rolled = (int)$stmtRolled->fetchColumn();

            // 5. ĐÃ HỦY: Cancelled trong [from, to], độc nhất theo bobin_key_code
            $sqlCancelled = "SELECT 
                               COUNT(*) as total,
                               SUM(CASE WHEN $keyTimeExpr BETWEEN :from_key AND :to_key THEN 1 ELSE 0 END) as in_period,
                               SUM(CASE WHEN $keyTimeExpr < :from_key_before THEN 1 ELSE 0 END) as before_period
                            FROM bobin_history h
                            INNER JOIN (
                                SELECT bobin_key_code, MAX(id) AS max_id
                                FROM bobin_history
                                WHERE updated_time BETWEEN :from_date AND :to_date
                                  AND bobin_current_status = 'Cancelled'
                                GROUP BY bobin_key_code
                            ) latest_key ON h.id = latest_key.max_id
                            WHERE 1=1" . $extraWhere;
            $paramsCancelled = array_merge([
                ':from_date'        => $fromDateStr,
                ':to_date'          => $toDateStr,
                ':from_key'         => $fromDateStr,
                ':to_key'           => $toDateStr,
                ':from_key_before'  => $fromDateStr
            ], $extraParams);
            $stmtCancelled = $pdo->prepare($sqlCancelled);
            $stmtCancelled->execute($paramsCancelled);
            $rowCancelled = $stmtCancelled->fetch(PDO::FETCH_ASSOC);
            $cancelled = (int)($rowCancelled['total'] ?? 0);
            $cancelledInPeriod = (int)($rowCancelled['in_period'] ?? 0);
            $cancelledBefore = (int)($rowCancelled['before_period'] ?? 0);

            // 6. CHỜ HỦY: Pending_Cancellation trong [from, to], độc nhất theo bobin_key_code
            $sqlPending = "SELECT 
                              COUNT(*) as total,
                              SUM(CASE WHEN $keyTimeExpr BETWEEN :from_key AND :to_key THEN 1 ELSE 0 END) as in_period,
                              SUM(CASE WHEN $keyTimeExpr < :from_key_before THEN 1 ELSE 0 END) as before_period
                           FROM bobin_history h
                           INNER JOIN (
                               SELECT bobin_key_code, MAX(id) AS max_id
                               FROM bobin_history
                               WHERE updated_time BETWEEN :from_date AND :to_date
                                 AND bobin_current_status = 'Pending_Cancellation'
                               GROUP BY bobin_key_code
                           ) latest_key ON h.id = latest_key.max_id
                           WHERE 1=1" . $extraWhere;
            $paramsPending = array_merge([
                ':from_date'        => $fromDateStr,
                ':to_date'          => $toDateStr,
                ':from_key'         => $fromDateStr,
                ':to_key'           => $toDateStr,
                ':from_key_before'  => $fromDateStr
            ], $extraParams);
            $stmtPending = $pdo->prepare($sqlPending);
            $stmtPending->execute($paramsPending);
            $rowPending = $stmtPending->fetch(PDO::FETCH_ASSOC);
            $pendingCancel = (int)($rowPending['total'] ?? 0);
            $pendingCancelInPeriod = (int)($rowPending['in_period'] ?? 0);
            $pendingCancelBefore = (int)($rowPending['before_period'] ?? 0);

            // 7. SỐ LIỆU BẢO TOÀN (Lấy trạng thái mới nhất tính đến to_date của các Bobin ĐÃ ĐÙN trong khoảng thời gian đã chọn)
            $sqlConserved = "SELECT h.bobin_current_status, COUNT(*) as cnt
                             FROM bobin_history h
                             INNER JOIN (
                                 SELECT bobin_key_code, MAX(id) AS max_id
                                 FROM bobin_history
                                 WHERE updated_time <= :to_date_scope
                                   AND bobin_key_code IN (
                                       SELECT DISTINCT bobin_key_code 
                                       FROM bobin_history 
                                       WHERE updated_time BETWEEN :from_date_ext AND :to_date_ext
                                         AND $keyTimeSubQueryExpr BETWEEN :from_date_key AND :to_date_key
                                   )
                                 GROUP BY bobin_key_code
                             ) latest_key ON h.id = latest_key.max_id
                             WHERE 1=1" . $extraWhere . "
                             GROUP BY h.bobin_current_status";
            $paramsConserved = array_merge([
                ':to_date_scope' => $toDateStr,
                ':from_date_ext' => $fromDateStr,
                ':to_date_ext'   => $toDateStr,
                ':from_date_key' => $fromDateStr,
                ':to_date_key'   => $toDateStr
            ], $extraParams);
            $stmtConserved = $pdo->prepare($sqlConserved);
            $stmtConserved->execute($paramsConserved);
            $conservedMap = $stmtConserved->fetchAll(PDO::FETCH_KEY_PAIR);

            return [
                'Extruded'                      => $extruded,
                'Rolled'                        => $rolled,
                'Busy_Unchecked'                => $busyUnchecked,
                'Busy_Checked'                  => $busyChecked,
                'Busy_Checked_InPeriod'         => $busyCheckedInPeriod,
                'Busy_Checked_Before'           => $busyCheckedBefore,
                'Pending_Cancellation'          => $pendingCancel,
                'Pending_Cancellation_InPeriod' => $pendingCancelInPeriod,
                'Pending_Cancellation_Before'   => $pendingCancelBefore,
                'Cancelled'                     => $cancelled,
                'Cancelled_InPeriod'            => $cancelledInPeriod,
                'Cancelled_Before'              => $cancelledBefore,
                // Số liệu trạng thái hiện thời của tập Bobin Đã đùn trong khoảng (Bảo toàn)
                'Conserved_Busy_Checked'         => (int)($conservedMap['Busy_Checked'] ?? 0),
                'Conserved_Busy_Unchecked'       => (int)($conservedMap['Busy_Unchecked'] ?? 0),
                'Conserved_Rolled'               => (int)($conservedMap['Rolled'] ?? 0),
                'Conserved_Pending_Cancellation' => (int)($conservedMap['Pending_Cancellation'] ?? 0),
                'Conserved_Cancelled'            => (int)($conservedMap['Cancelled'] ?? 0)
            ];
        } catch (PDOException $e) {
            error_log("DB Error: " . $e->getMessage());
            throw new Exception("Database error: " . $e->getMessage());
        }
    }

    // =========================================================================
    // HÀM TẠO BẢNG ẢO (DYNAMIC): Tự động lấy cấu hình từ Database
    // =========================================================================
    private function getActiveBaseTable(): string
    {
        // 1. Đọc linh hoạt dung lượng từ CSDL
        $capacities = $this->getBobinCapacities();

        // 2. Nếu CSDL bị trống hoặc lỗi kết nối, tự động trả về mức dự phòng
        if (empty($capacities)) {
            $capacities = [
                'PL4-7 (TU04.TU06)' => 420,
                'PL4-7 (TU08~)'     => 480,
                'PL7-3'             => 460
            ];
        }

        // 3. Tự động sinh vòng lặp điều kiện WHERE dựa trên số lượng thực tế
        $whereClauses = [];
        foreach ($capacities as $sizeName => $capacity) {
            $safeSize = addslashes($sizeName);
            $safeCap  = (int)$capacity;
            $whereClauses[] = "(bobin_size = '{$safeSize}' AND row_num <= {$safeCap})";
        }

        $whereSql = implode(' OR ', $whereClauses);

        return "(
            SELECT * FROM (
                SELECT *, ROW_NUMBER() OVER(PARTITION BY bobin_size ORDER BY bobin_identification_code ASC) as row_num 
                FROM bobin_list_detail
            ) AS numbered_bobins
            WHERE {$whereSql}
        )";
    }

    public function countBobinListDetail(array $filters = []): int
    {
        $pdo = $this->db->pdo();
        try {
            $baseTable = $this->getActiveBaseTable();
            $sql = "SELECT COUNT(*) FROM $baseTable AS active_bobins WHERE 1=1";
            $params = [];

            if (!empty($filters['bobin_size']) && $filters['bobin_size'] !== 'all') {
                $sql .= " AND bobin_size = :bsize";
                $params[':bsize'] = $filters['bobin_size'];
            }

            if (!empty($filters['bobin_type']) && $filters['bobin_type'] !== 'all') {
                $sql .= " AND bobin_type = :btype";
                $params[':btype'] = $filters['bobin_type'];
            }
            if (!empty($filters['rack']) && $filters['rack'] !== 'all') {
                $rackFilterVal = trim($filters['rack']);
                if (str_starts_with($rackFilterVal, 'Rack_')) {
                    $sql .= " AND JSON_UNQUOTE(JSON_EXTRACT(rack, '$.code')) = :rack";
                    $params[':rack'] = $rackFilterVal;
                } else {
                    $sql .= " AND (JSON_UNQUOTE(JSON_EXTRACT(rack, '$.code')) LIKE :rack_prefix OR JSON_UNQUOTE(JSON_EXTRACT(rack, '$.code')) = :rack_exact)";
                    $params[':rack_prefix'] = 'Rack_' . $rackFilterVal . '_%';
                    $params[':rack_exact']  = $rackFilterVal;
                }
            }
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
                for ($i = 1; $i <= 8; $i++) {
                    $params[":kw$i"] = $searchStr;
                }
            }

            if (!empty($filters['status'])) {
                if ($filters['status'] === 'Line') {
                    $sql .= " AND bobin_current_status IN ('Busy_Unchecked', 'Busy_Checked')";
                } elseif ($filters['status'] !== 'all') {
                    $sql .= " AND bobin_current_status = :status";
                    $params[':status'] = $filters['status'];
                }
            }

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            return (int)$stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("DB Error: " . $e->getMessage());
            throw new Exception("Database error: " . $e->getMessage());
        }
    }

    public function getBobinStatusStats(array $filters = []): array
    {
        $pdo = $this->db->pdo();
        try {
            $baseTable = $this->getActiveBaseTable();
            $sql = "SELECT bobin_current_status, COUNT(*) AS total 
                    FROM $baseTable AS active_bobins WHERE 1=1";
            $params = [];

            if (!empty($filters['bobin_size']) && $filters['bobin_size'] !== 'all') {
                $sql .= " AND bobin_size = :bsize";
                $params[':bsize'] = $filters['bobin_size'];
            }

            if (!empty($filters['bobin_type']) && $filters['bobin_type'] !== 'all') {
                $sql .= " AND bobin_type = :btype";
                $params[':btype'] = $filters['bobin_type'];
            }

            if (!empty($filters['rack']) && $filters['rack'] !== 'all') {
                $rackFilterVal = trim($filters['rack']);
                if (str_starts_with($rackFilterVal, 'Rack_')) {
                    $sql .= " AND JSON_UNQUOTE(JSON_EXTRACT(rack, '$.code')) = :rack";
                    $params[':rack'] = $rackFilterVal;
                } else {
                    $sql .= " AND (JSON_UNQUOTE(JSON_EXTRACT(rack, '$.code')) LIKE :rack_prefix OR JSON_UNQUOTE(JSON_EXTRACT(rack, '$.code')) = :rack_exact)";
                    $params[':rack_prefix'] = 'Rack_' . $rackFilterVal . '_%';
                    $params[':rack_exact']  = $rackFilterVal;
                }
            }

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
                for ($i = 1; $i <= 8; $i++) {
                    $params[":kw$i"] = $searchStr;
                }
            }

            $sql .= " GROUP BY bobin_current_status";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

            $rolled        = (int)($rows['Rolled'] ?? 0);
            $busyUnchecked = (int)($rows['Busy_Unchecked'] ?? 0);
            $busyChecked   = (int)($rows['Busy_Checked'] ?? 0);
            $pendingCancel = (int)($rows['Pending_Cancellation'] ?? 0);

            return [
                'Rolled'               => $rolled,
                'Busy_Unchecked'       => $busyUnchecked,
                'Busy_Checked'         => $busyChecked,
                'Line'                 => $busyUnchecked + $busyChecked,
                'Pending_Cancellation' => $pendingCancel,
            ];
        } catch (PDOException $e) {
            error_log("DB Error: " . $e->getMessage());
            throw new Exception("Database error: " . $e->getMessage());
        }
    }

    public function getBobinListDetail(array $filters = [], int $page = 1, int $limit = 50)
    {
        $pdo = $this->db->pdo();
        try {
            $baseTable = $this->getActiveBaseTable();
            $sql = "SELECT * FROM $baseTable AS active_bobins WHERE 1=1";
            $params = [];

            // 1. Lọc theo Kích thước
            if (!empty($filters['bobin_size']) && $filters['bobin_size'] !== 'all') {
                $sql .= " AND bobin_size = :bsize";
                $params[':bsize'] = $filters['bobin_size'];
            }

            // 2. Lọc theo Loại Bobin
            if (!empty($filters['bobin_type']) && $filters['bobin_type'] !== 'all') {
                $sql .= " AND bobin_type = :btype";
                $params[':btype'] = $filters['bobin_type'];
            }

            // 3. Lọc theo Vị trí Rack
            if (!empty($filters['rack']) && $filters['rack'] !== 'all') {
                $rackFilterVal = trim($filters['rack']);
                if (str_starts_with($rackFilterVal, 'Rack_')) {
                    $sql .= " AND JSON_UNQUOTE(JSON_EXTRACT(rack, '$.code')) = :rack";
                    $params[':rack'] = $rackFilterVal;
                } else {
                    $sql .= " AND (JSON_UNQUOTE(JSON_EXTRACT(rack, '$.code')) LIKE :rack_prefix OR JSON_UNQUOTE(JSON_EXTRACT(rack, '$.code')) = :rack_exact)";
                    $params[':rack_prefix'] = 'Rack_' . $rackFilterVal . '_%';
                    $params[':rack_exact']  = $rackFilterVal;
                }
            }

            // 4. Tìm kiếm từ khóa (Keyword)
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
                for ($i = 1; $i <= 8; $i++) {
                    $params[":kw$i"] = $searchStr;
                }
            }

            // 5. Lọc theo Trạng thái
            if (!empty($filters['status'])) {
                if ($filters['status'] === 'Line') {
                    $sql .= " AND bobin_current_status IN ('Busy_Unchecked', 'Busy_Checked')";
                } elseif ($filters['status'] !== 'all') {
                    $sql .= " AND bobin_current_status = :status";
                    $params[':status'] = $filters['status'];
                }
            }

            // 6. SẮP XẾP (Đặt sau tất cả điều kiện WHERE)
            $sort = $filters['sort'] ?? 'default';
            if ($sort === 'newest') {
                $sql .= " ORDER BY updated_time DESC, bobin_identification_code ASC";
            } else {
                $sql .= " ORDER BY bobin_identification_code ASC";
            }

            // 7. PHÂN TRANG (Đặt ở cuối cùng của truy vấn)
            $offset = ($page - 1) * $limit;
            $sql .= " LIMIT :limit OFFSET :offset";

            $stmt = $pdo->prepare($sql);
            foreach ($params as $k => $v) {
                $stmt->bindValue($k, $v);
            }
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("DB Error: " . $e->getMessage());
            throw new Exception("Database error: " . $e->getMessage());
        }
    }

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
    public function getDetailBobinsForQC(array $filters = [])
    {
        $pdo = $this->db->pdo();

        try {
            $sql = "SELECT * FROM bobin_list_detail 
                WHERE bobin_current_status = 'Busy_Unchecked'";

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

            $sql .= " ORDER BY updated_time DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("DB Error: " . $e->getMessage());
            throw new Exception("Database error: " . $e->getMessage());
        }
    }

    public function countDetailBobinsForQC(array $filters = []): int
    {
        $pdo = $this->db->pdo();

        try {
            $sql = "SELECT COUNT(*) FROM bobin_list_detail 
                WHERE bobin_current_status = 'Busy_Unchecked'";

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

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            return (int)$stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("DB Error: " . $e->getMessage());
            throw new Exception("Database error: " . $e->getMessage());
        }
    }
    public function getDetailBobinsForPendingCancellation(array $filters = [])
    {
        $pdo = $this->db->pdo();

        try {
            $sql = "SELECT * FROM bobin_list_detail 
                WHERE bobin_current_status = 'Pending_Cancellation'";

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

            $sql .= " ORDER BY updated_time DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("DB Error: " . $e->getMessage());
            throw new Exception("Database error: " . $e->getMessage());
        }
    }

    public function getDetailBobinsForWinding(array $filters = [], int $page = 1, int $limit = 50)
    {
        $pdo = $this->db->pdo();

        try {
            $baseTable = $this->getActiveBaseTable();
            $sql = "SELECT * FROM $baseTable AS active_bobins 
                    WHERE bobin_current_status IN ('Busy_Checked', 'Rolled')";
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
                    bobin_type LIKE :kw7
                )";
                for ($i = 1; $i <= 7; $i++) {
                    $params[":kw$i"] = $searchStr;
                }
            }

            $sql .= " ORDER BY 
            CASE 
                WHEN bobin_current_status = 'Busy_Checked' THEN 1 
                WHEN bobin_current_status = 'Rolled' THEN 2 
                ELSE 3 
            END ASC, 
            updated_time DESC";

            $offset = ($page - 1) * $limit;
            $sql .= " LIMIT :limit OFFSET :offset";

            $stmt = $pdo->prepare($sql);
            foreach ($params as $k => $v) {
                $stmt->bindValue($k, $v);
            }
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("DB Error: " . $e->getMessage());
            throw new Exception("Database error: " . $e->getMessage());
        }
    }

    public function countDetailBobinsForWinding(array $filters = []): int
    {
        $pdo = $this->db->pdo();
        try {
            $baseTable = $this->getActiveBaseTable();
            $sql = "SELECT COUNT(*) FROM $baseTable AS active_bobins WHERE bobin_current_status IN ('Busy_Checked', 'Rolled')";
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
                    bobin_type LIKE :kw7
                )";
                for ($i = 1; $i <= 7; $i++) {
                    $params[":kw$i"] = $searchStr;
                }
            }

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            return (int)$stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("DB Error: " . $e->getMessage());
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
                extrusion_employee, extrusion_check, rack, products, material_lot, print_lot, length_m,
                shift, extrusion_date, finish_time, visual_inspection, winding_machine,
                winding_employee, flow_test_result, bobin_current_status, winding_note, updated_time
            ) VALUES (
                :key, :ident, :size, :type, :extrusionemp, :ext_check, :rack, :prod, :mat, :plot, :len,
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
                    extrusion_employee, extrusion_check, rack, products, material_lot, print_lot, length_m,
                    shift, extrusion_date, finish_time, visual_inspection, winding_machine,
                    winding_employee, flow_test_result, bobin_current_status, winding_note, updated_time
                ) VALUES (
                    :key, :ident, :size, :type, :extrusionemp, :ext_check, :rack, :prod, :mat, :plot, :len,
                    :shift, :edate, :ftime, :visual, :winding_machine, :winding_employee,
                    :flow_test_result, :status, :note, :updated
                ) ON DUPLICATE KEY UPDATE
                    bobin_key_code = VALUES(bobin_key_code),
                    bobin_size = VALUES(bobin_size),
                    bobin_type = VALUES(bobin_type),
                    extrusion_check = VALUES(extrusion_check),
                    rack = VALUES(rack),
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
            ':ext_check' => $entity->extrusion_check ? $this->json_utf8($entity->extrusion_check) : null, // Mới thêm
            ':rack' => $entity->rack ? $this->json_utf8($entity->rack) : null, // Mới thêm
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
            // Cập nhật lại bản ghi trong bobin_history theo bobin_key_code mà không POST dòng mới (Req VI & VII)
            $this->extUpdateBobinHistory($pdo, $entity);

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
        // Khi nhân viên đùn update, không cập nhật updated_time (Req VII)
        $sql = "UPDATE bobin_list_detail SET
                    bobin_type = :type,
                    extrusion_employee = :extrusionemp,
                    extrusion_check = :ext_check,
                    rack = :rack,
                    products = :prod,
                    material_lot = :mat,
                    print_lot = :plot,
                    length_m = :len,
                    shift = :shift,
                    extrusion_date = :edate,
                    finish_time = :ftime,
                    bobin_current_status = :status
                WHERE bobin_identification_code = :ident";

        $stmt = $pdo->prepare($sql);
        $params = $this->getBobinExtUpdateParams($entity);
        $params[':status'] = 'Busy_Unchecked';
        $stmt->execute($params);
    }

    private function extUpdateBobinGeneral(PDO $pdo, BobinEntity $entity): void
    {
        // Khi nhân viên đùn update, không cập nhật updated_time (Req VII)
        $sql = "UPDATE bobin_list_general SET
                    bobin_type = :type,
                    bobin_current_status = :status
                WHERE bobin_identification_code = :ident";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':ident' => $entity->identificationCode,
            ':type' => $entity->type,
            ':status' => 'Busy_Unchecked'
        ]);
    }

    private function extUpdateBobinHistory(PDO $pdo, BobinEntity $entity): void
    {
        if (empty($entity->bobinKeyCode)) {
            return;
        }

        // Tìm đến bobin_key_code tương ứng để update dữ liệu, không cập nhật updated_time (Req VI & VII)
        $sql = "UPDATE bobin_history SET
                    bobin_type = :type,
                    extrusion_employee = :extrusionemp,
                    extrusion_check = :ext_check,
                    rack = :rack,
                    products = :prod,
                    material_lot = :mat,
                    print_lot = :plot,
                    length_m = :len,
                    shift = :shift,
                    extrusion_date = :edate,
                    finish_time = :ftime,
                    bobin_current_status = :status
                WHERE bobin_key_code = :key";

        $stmt = $pdo->prepare($sql);
        $params = $this->getBobinExtUpdateParams($entity);
        unset($params[':ident']);
        $params[':key'] = $entity->bobinKeyCode;
        $params[':status'] = 'Busy_Unchecked';
        $stmt->execute($params);
    }

    private function getBobinExtUpdateParams(BobinEntity $entity): array
    {
        return [
            ':ident' => $entity->identificationCode,
            ':type' => $entity->type,
            ':extrusionemp' => $this->json_utf8($entity->extrusion_employee),
            ':ext_check' => $entity->extrusion_check ? $this->json_utf8($entity->extrusion_check) : null, // Mới thêm
            ':rack' => $entity->rack ? $this->json_utf8($entity->rack) : null, // Mới thêm
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
    public function updateBobinTypeInfor(BobinEntity $entity, string $newType): bool
    {
        $pdo = $this->db->pdo();
        try {
            $pdo->beginTransaction();

            // 1. Cập nhật bảng Chi tiết (bobin_list_detail): Cập nhật trạng thái Busy_Checked, Visual Inspection và đổi Type mới
            $sqlDetail = "UPDATE bobin_list_detail SET
                    bobin_type = :type,
                    bobin_current_status = :status,
                    visual_inspection = :visual,
                    updated_time = NOW()
                WHERE bobin_key_code = :key
                    AND bobin_identification_code = :ident";

            $stmt = $pdo->prepare($sqlDetail);
            $stmt->execute([
                ':type'     => $newType,
                ':status'   => $entity->currentStatus,
                ':visual'   => $this->json_utf8($entity->visualInspection),
                ':key'      => $entity->bobinKeyCode,
                ':ident'    => $entity->identificationCode
            ]);

            // 2. Cập nhật bảng Tổng hợp (bobin_list_general)
            $sqlGeneral = "UPDATE bobin_list_general SET
                            bobin_type = :type,
                            bobin_current_status = :status,
                            updated_time = NOW()
                       WHERE bobin_key_code = :key
                            AND bobin_identification_code = :ident";

            $stmtGen = $pdo->prepare($sqlGeneral);
            $stmtGen->execute([
                ':type'     => $newType,
                ':status'   => $entity->currentStatus,
                ':key'      => $entity->bobinKeyCode,
                ':ident'    => $entity->identificationCode
            ]);

            // 3. Chèn một dòng lịch sử mới ghi nhận kết quả kiểm tra QC kèm loại Type mới
            $sqlHistory = "INSERT INTO bobin_history (
                                bobin_key_code, bobin_identification_code, bobin_size, bobin_type, 
                                extrusion_employee, products, material_lot, print_lot, length_m, 
                                shift, extrusion_date, finish_time, visual_inspection, 
                                winding_machine, winding_employee, bobin_current_status, winding_note, flow_test_result, updated_time
                            )
                            SELECT 
                                bobin_key_code, bobin_identification_code, bobin_size, :type, 
                                extrusion_employee, products, material_lot, print_lot, length_m, 
                                shift, extrusion_date, finish_time, visual_inspection, 
                                winding_machine, winding_employee, bobin_current_status, winding_note, flow_test_result, NOW()
                            FROM bobin_list_detail
                            WHERE bobin_key_code = :key 
                                AND bobin_identification_code = :ident";

            $stmtHistory = $pdo->prepare($sqlHistory);
            $stmtHistory->execute([
                ':type' => $newType,
                ':key'  => $entity->bobinKeyCode,
                ':ident' => $entity->identificationCode
            ]);

            // 4. [YÊU CẦU QUAN TRỌNG]: Tìm trong lịch sử các bản ghi có cùng bobin_key_code và cập nhật lại bobin_type thành loại mới
            $sqlHistoryUpdateType = "UPDATE bobin_history 
                                     SET bobin_type = :type 
                                     WHERE bobin_key_code = :key";
            $stmtHistoryType = $pdo->prepare($sqlHistoryUpdateType);
            $stmtHistoryType->execute([
                ':type' => $newType,
                ':key'  => $entity->bobinKeyCode
            ]);

            if ($stmt->rowCount() === 0 && $stmtGen->rowCount() === 0) {
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
    public function countPendingCancellation(): int
    {
        $pdo = $this->db->pdo();
        try {
            $sql = "SELECT COUNT(*) FROM bobin_list_detail WHERE bobin_current_status = 'Pending_Cancellation'";
            $stmt = $pdo->query($sql);
            return (int)$stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("DB Error: " . $e->getMessage());
            return 0;
        }
    }
    /**
     * Lấy toàn bộ danh sách Bobin để xuất file Excel (Bỏ qua Limit/Offset phân trang)
     */
    public function getBobinListDetailForExport(array $filters = [], array $selectedCodes = []): array
    {
        $pdo = $this->db->pdo();
        try {
            $baseTable = $this->getActiveBaseTable();
            $sql = "SELECT * FROM $baseTable AS active_bobins WHERE 1=1";
            $params = [];

            // Nếu người dùng chọn đích danh các Bobin qua Checkbox
            if (!empty($selectedCodes)) {
                $inPlaceholders = [];
                foreach ($selectedCodes as $index => $code) {
                    $paramKey = ":sel_code_" . $index;
                    $inPlaceholders[] = $paramKey;
                    $params[$paramKey] = $code;
                }
                $sql .= " AND bobin_identification_code IN (" . implode(',', $inPlaceholders) . ")";
            }

            // Lọc kích thước
            if (!empty($filters['bobin_size']) && $filters['bobin_size'] !== 'all') {
                $sql .= " AND bobin_size = :bsize";
                $params[':bsize'] = $filters['bobin_size'];
            }

            // Lọc loại bobin
            if (!empty($filters['bobin_type']) && $filters['bobin_type'] !== 'all') {
                $sql .= " AND bobin_type = :btype";
                $params[':btype'] = $filters['bobin_type'];
            }

            // Lọc vị trí Rack
            if (!empty($filters['rack']) && $filters['rack'] !== 'all') {
                $rackFilterVal = trim($filters['rack']);
                if (str_starts_with($rackFilterVal, 'Rack_')) {
                    $sql .= " AND JSON_UNQUOTE(JSON_EXTRACT(rack, '$.code')) = :rack";
                    $params[':rack'] = $rackFilterVal;
                } else {
                    $sql .= " AND (JSON_UNQUOTE(JSON_EXTRACT(rack, '$.code')) LIKE :rack_prefix OR JSON_UNQUOTE(JSON_EXTRACT(rack, '$.code')) = :rack_exact)";
                    $params[':rack_prefix'] = 'Rack_' . $rackFilterVal . '_%';
                    $params[':rack_exact']  = $rackFilterVal;
                }
            }

            // Lọc từ khóa
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
                for ($i = 1; $i <= 8; $i++) {
                    $params[":kw$i"] = $searchStr;
                }
            }

            // Lọc trạng thái
            if (!empty($filters['status'])) {
                if ($filters['status'] === 'Line') {
                    $sql .= " AND bobin_current_status IN ('Busy_Unchecked', 'Busy_Checked')";
                } elseif ($filters['status'] !== 'all') {
                    $sql .= " AND bobin_current_status = :status";
                    $params[':status'] = $filters['status'];
                }
            }

            // Sắp xếp
            $sort = $filters['sort'] ?? 'default';
            if ($sort === 'newest') {
                $sql .= " ORDER BY updated_time DESC, bobin_identification_code ASC";
            } else {
                $sql .= " ORDER BY bobin_identification_code ASC";
            }

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("DB Export Error: " . $e->getMessage());
            throw new Exception("Lỗi truy vấn xuất dữ liệu: " . $e->getMessage());
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
