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

    public function getPdo(): PDO
    {
        return $this->db->pdo();
    }

    public function getBobinCurrentImages(string $identCode, ?string $keyCode = null): ?array
    {
        $pdo = $this->db->pdo();
        try {
            $sql = "SELECT bobin_key_code, bobin_current_status, extrusion_chart_image, qc_visual_image, winding_chart_image
                    FROM bobin_list_detail
                    WHERE bobin_identification_code = :ident";
            $params = [':ident' => $identCode];
            if (!empty($keyCode)) {
                $sql .= " AND bobin_key_code = :key";
                $params[':key'] = $keyCode;
            }
            $sql .= " LIMIT 1";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ?: null;
        } catch (PDOException $e) {
            error_log("DB getBobinCurrentImages Error: " . $e->getMessage());
            return null;
        }
    }
    private ?array $cachedRacks = null;
    private ?array $cachedCapacities = null;

    // Bổ sung hàm lấy toàn bộ Rack để hiển thị lên Dropdown
    public function getAllRacks(bool $forceRefresh = false): array
    {
        if (!$forceRefresh && $this->cachedRacks !== null) {
            return $this->cachedRacks;
        }

        $pdo = $this->db->pdo();
        try {
            $sql = "SELECT id, rack_code FROM rack_list ORDER BY rack_code ASC";
            $stmt = $pdo->query($sql);
            $this->cachedRacks = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $this->cachedRacks;
        } catch (PDOException $e) {
            error_log("DB Error: " . $e->getMessage());
            return [];
        }
    }
    public function getBobinCapacities(bool $forceRefresh = false): array
    {
        if (!$forceRefresh && $this->cachedCapacities !== null) {
            return $this->cachedCapacities;
        }

        $pdo = $this->db->pdo();
        try {
            $sql = "SELECT size_name, capacity FROM bobin_capacity";
            $stmt = $pdo->query($sql);
            $this->cachedCapacities = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
            return $this->cachedCapacities;
        } catch (PDOException $e) {
            error_log("DB Error: " . $e->getMessage());
            return [];
        }
    }

    public function updateBobinCapacity(string $sizeName, int $newCapacity): bool
    {
        $this->cachedCapacities = null;
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
                        ) latest_key ON h.id = latest_key.max_id
                        WHERE 1=1";

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
                        ) latest_key ON h.id = latest_key.max_id
                        WHERE 1=1";

                if ($statusFilter === 'Busy_Checked_InPeriod') {
                    $sql .= " AND $keyTimeExpr BETWEEN :from_key AND :to_key";
                    $params[':from_key'] = $fromDateStr;
                    $params[':to_key']   = $toDateStr;
                } elseif ($statusFilter === 'Busy_Checked_Before') {
                    $sql .= " AND $keyTimeExpr < :from_key";
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
                        ) latest_key ON h.id = latest_key.max_id
                        WHERE 1=1";

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
                        ) latest_key ON h.id = latest_key.max_id
                        WHERE 1=1";

                if ($statusFilter === 'Cancelled_InPeriod') {
                    $sql .= " AND $keyTimeExpr BETWEEN :from_key AND :to_key";
                    $params[':from_key'] = $fromDateStr;
                    $params[':to_key']   = $toDateStr;
                } elseif ($statusFilter === 'Cancelled_Before') {
                    $sql .= " AND $keyTimeExpr < :from_key";
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
                        ) latest_key ON h.id = latest_key.max_id
                        WHERE 1=1";

                if ($statusFilter === 'Pending_Cancellation_InPeriod') {
                    $sql .= " AND $keyTimeExpr BETWEEN :from_key AND :to_key";
                    $params[':from_key'] = $fromDateStr;
                    $params[':to_key']   = $toDateStr;
                } elseif ($statusFilter === 'Pending_Cancellation_Before') {
                    $sql .= " AND $keyTimeExpr < :from_key";
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

            // Đảm bảo chỉ lọc các bản ghi có đầy đủ thông tin từ nhóm Đùn
            $sql .= " AND " . $this->getExtrusionCompleteCondition('h');

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

            // 5. Lọc nâng cao: Mã NV (Đùn/Cuộn/QC), Mã Sản phẩm, Tình trạng ngoại quan QC
            $this->appendAdvancedManagementFilters($sql, $params, $filters, 'h');

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

            // Đoạn phụ lọc thêm (bobin_size, bobin_type, rack, keyword) + đầy đủ thông tin từ nhóm Đùn
            $extraWhere = " AND " . $this->getExtrusionCompleteCondition('h');
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

            // Lọc nâng cao: Mã NV (Đùn/Cuộn/QC), Mã Sản phẩm, Tình trạng ngoại quan QC
            $this->appendAdvancedManagementFilters($extraWhere, $extraParams, $filters, 'h');

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

    /**
     * Điều kiện SQL xác định Bobin đã có đầy đủ thông tin nhập từ nhóm Đùn
     */
    private function getExtrusionCompleteCondition(string $prefix = ''): string
    {
        $p = $prefix !== '' ? rtrim($prefix, '.') . '.' : '';
        return "(
            {$p}products IS NOT NULL AND TRIM({$p}products) NOT IN ('', 'null', '{}')
            AND JSON_UNQUOTE(JSON_EXTRACT({$p}products, '$.product_code')) IS NOT NULL
            AND TRIM(JSON_UNQUOTE(JSON_EXTRACT({$p}products, '$.product_code'))) NOT IN ('', 'Chưa cập nhật')
            AND {$p}extrusion_employee IS NOT NULL AND TRIM({$p}extrusion_employee) NOT IN ('', 'null', '{}')
            AND JSON_UNQUOTE(JSON_EXTRACT({$p}extrusion_employee, '$.employee_code')) IS NOT NULL
            AND TRIM(JSON_UNQUOTE(JSON_EXTRACT({$p}extrusion_employee, '$.employee_code'))) NOT IN ('', 'Chưa cập nhật')
            AND {$p}material_lot IS NOT NULL AND TRIM({$p}material_lot) NOT IN ('', 'null', '{}')
            AND JSON_UNQUOTE(JSON_EXTRACT({$p}material_lot, '$.lot')) IS NOT NULL
            AND TRIM(JSON_UNQUOTE(JSON_EXTRACT({$p}material_lot, '$.lot'))) NOT IN ('', 'Chưa cập nhật')
            AND {$p}rack IS NOT NULL AND TRIM({$p}rack) NOT IN ('', 'null', '{}')
            AND JSON_UNQUOTE(JSON_EXTRACT({$p}rack, '$.code')) IS NOT NULL
            AND TRIM(JSON_UNQUOTE(JSON_EXTRACT({$p}rack, '$.code'))) NOT IN ('', 'Chưa cập nhật')
            AND {$p}extrusion_check IS NOT NULL AND TRIM({$p}extrusion_check) NOT IN ('', 'null', '{}')
            AND {$p}print_lot IS NOT NULL AND TRIM({$p}print_lot) NOT IN ('', 'Chưa cập nhật')
            AND {$p}length_m > 0
            AND {$p}shift IS NOT NULL AND TRIM({$p}shift) NOT IN ('', 'Chưa cập nhật')
            AND {$p}extrusion_date IS NOT NULL
            AND {$p}finish_time IS NOT NULL
        )";
    }

    /**
     * Chuẩn hóa giá trị lọc Mã nhân viên (hỗ trợ cả khi người dùng chọn dạng "02224428 - Lê Thị Lụa")
     */
    public function normalizeEmployeeCodeFilter(?string $raw): string
    {
        $val = trim((string)($raw ?? ''));
        if ($val === '' || strtolower($val) === 'all') {
            return '';
        }
        if (strpos($val, ' - ') !== false) {
            $val = trim(explode(' - ', $val, 2)[0]);
        }
        return $val;
    }

    /**
     * Chuẩn hóa giá trị lọc Mã sản phẩm (hỗ trợ cả khi người dùng chọn dạng "TIUB11BU-1MZ2 (25 Bobin)" hoặc "TIUB11BU-1MZ2 - ...")
     */
    public function normalizeProductCodeFilter(?string $raw): string
    {
        $val = trim((string)($raw ?? ''));
        if ($val === '' || strtolower($val) === 'all') {
            return '';
        }
        if (strpos($val, ' - ') !== false) {
            $val = trim(explode(' - ', $val, 2)[0]);
        }
        if (preg_match('/^(.+?)\s*\(\d+\s*Bobin\)$/iu', $val, $m)) {
            $val = trim($m[1]);
        }
        return $val;
    }

    /**
     * Điều kiện SQL xác định Bobin đã được QC kiểm tra ngoại quan
     */
    private function getQcInspectedCondition(string $prefix = ''): string
    {
        $p = $prefix !== '' ? rtrim($prefix, '.') . '.' : '';
        return "(
            {$p}visual_inspection IS NOT NULL
            AND TRIM({$p}visual_inspection) NOT IN ('', 'null', '{}')
            AND COALESCE(TRIM(JSON_UNQUOTE(JSON_EXTRACT({$p}visual_inspection, '$.inspector_code'))), '') NOT IN ('', 'null', 'Chưa cập nhật')
        )";
    }

    /**
     * Biểu thức SQL kiểm tra 1 tiêu chí ngoại quan QC có bị NG (true) hay không (NULL-safe với COALESCE)
     */
    private function getSingleQcDefectNgExpr(string $field, string $prefix = ''): string
    {
        $p = $prefix !== '' ? rtrim($prefix, '.') . '.' : '';
        return match ($field) {
            'gel'            => "(COALESCE(JSON_UNQUOTE(JSON_EXTRACT({$p}visual_inspection, '$.defects.gel')), 'false') IN ('true', '1') OR COALESCE(JSON_UNQUOTE(JSON_EXTRACT({$p}visual_inspection, '$.defect_gel')), 'false') IN ('true', '1'))",
            'foreign_object' => "(COALESCE(JSON_UNQUOTE(JSON_EXTRACT({$p}visual_inspection, '$.defects.foreign_object')), 'false') IN ('true', '1') OR COALESCE(JSON_UNQUOTE(JSON_EXTRACT({$p}visual_inspection, '$.defect_foreign_object')), 'false') IN ('true', '1'))",
            'color_issue'    => "(COALESCE(JSON_UNQUOTE(JSON_EXTRACT({$p}visual_inspection, '$.defects.color_issue')), 'false') IN ('true', '1') OR COALESCE(JSON_UNQUOTE(JSON_EXTRACT({$p}visual_inspection, '$.defect_color_issue')), 'false') IN ('true', '1'))",
            'print_quality'  => "(COALESCE(JSON_UNQUOTE(JSON_EXTRACT({$p}visual_inspection, '$.defects.print_quality')), 'false') IN ('true', '1') OR COALESCE(JSON_UNQUOTE(JSON_EXTRACT({$p}visual_inspection, '$.defect_print_quality')), 'false') IN ('true', '1'))",
            'clump'          => "(COALESCE(JSON_UNQUOTE(JSON_EXTRACT({$p}visual_inspection, '$.defects.clump')), 'false') IN ('true', '1') OR COALESCE(JSON_UNQUOTE(JSON_EXTRACT({$p}visual_inspection, '$.defects.lump')), 'false') IN ('true', '1') OR COALESCE(JSON_UNQUOTE(JSON_EXTRACT({$p}visual_inspection, '$.defect_clump')), 'false') IN ('true', '1'))",
            'scratch'        => "(COALESCE(JSON_UNQUOTE(JSON_EXTRACT({$p}visual_inspection, '$.defects.scratch')), 'false') IN ('true', '1') OR COALESCE(JSON_UNQUOTE(JSON_EXTRACT({$p}visual_inspection, '$.defect_scratch')), 'false') IN ('true', '1'))",
            'streak'         => "(COALESCE(JSON_UNQUOTE(JSON_EXTRACT({$p}visual_inspection, '$.defects.streak')), 'false') IN ('true', '1') OR COALESCE(JSON_UNQUOTE(JSON_EXTRACT({$p}visual_inspection, '$.defects.scuff')), 'false') IN ('true', '1') OR COALESCE(JSON_UNQUOTE(JSON_EXTRACT({$p}visual_inspection, '$.defect_streak')), 'false') IN ('true', '1'))",
            'deformation'    => "(COALESCE(JSON_UNQUOTE(JSON_EXTRACT({$p}visual_inspection, '$.defects.deformation')), 'false') IN ('true', '1') OR COALESCE(JSON_UNQUOTE(JSON_EXTRACT({$p}visual_inspection, '$.defects.deformed')), 'false') IN ('true', '1') OR COALESCE(JSON_UNQUOTE(JSON_EXTRACT({$p}visual_inspection, '$.defect_deformation')), 'false') IN ('true', '1'))",
            default          => "0=1"
        };
    }

    /**
     * Điều kiện SQL lọc theo tình trạng ngoại quan của nhóm QC
     */
    private function getQcDefectFilterCondition(string $qcDefect, string $prefix = ''): string
    {
        $qcDefect = trim($qcDefect);
        if ($qcDefect === '' || $qcDefect === 'all') {
            return '1=1';
        }

        $inspectedCond = $this->getQcInspectedCondition($prefix);
        $defectFields  = ['gel', 'foreign_object', 'color_issue', 'print_quality', 'clump', 'scratch', 'streak', 'deformation'];

        if (in_array($qcDefect, $defectFields, true)) {
            return "(" . $inspectedCond . " AND " . $this->getSingleQcDefectNgExpr($qcDefect, $prefix) . ")";
        }

        $anyNgExpr = "(" . implode(' OR ', array_map(fn($f) => $this->getSingleQcDefectNgExpr($f, $prefix), $defectFields)) . ")";

        if ($qcDefect === 'ng_any') {
            return "(" . $inspectedCond . " AND " . $anyNgExpr . ")";
        }

        if ($qcDefect === 'ok_all') {
            return "(" . $inspectedCond . " AND NOT " . $anyNgExpr . ")";
        }

        if ($qcDefect === 'uninspected') {
            return "(NOT " . $inspectedCond . ")";
        }

        return '1=1';
    }

    /**
     * Gắn đồng bộ 3 bộ lọc nâng cao:
     * - 2a: employee_code (tra cứu cả 3 nhóm: Đùn / Cuộn / QC)
     * - 2b: product_code (tra cứu theo mã sản phẩm)
     * - 2c: qc_defect (tra cứu theo tình trạng ngoại quan QC)
     */
    private function appendAdvancedManagementFilters(
        string &$sql,
        array &$params,
        array $filters,
        string $prefix = '',
        bool $skipQcDefect = false,
        bool $skipProductCode = false
    ): void {
        $p = $prefix !== '' ? rtrim($prefix, '.') . '.' : '';

        // 2a. Lọc theo mã số nhân viên trên cả 3 nhóm (Đùn OR Cuộn OR QC)
        $empCode = $this->normalizeEmployeeCodeFilter($filters['employee_code'] ?? '');
        if ($empCode !== '') {
            $sql .= " AND (
                TRIM(JSON_UNQUOTE(JSON_EXTRACT({$p}extrusion_employee, '$.employee_code'))) = :f_emp_ext
                OR TRIM(JSON_UNQUOTE(JSON_EXTRACT({$p}winding_employee, '$.employee_code'))) = :f_emp_wnd
                OR TRIM(JSON_UNQUOTE(JSON_EXTRACT({$p}visual_inspection, '$.inspector_code'))) = :f_emp_qc
            )";
            $params[':f_emp_ext'] = $empCode;
            $params[':f_emp_wnd'] = $empCode;
            $params[':f_emp_qc']  = $empCode;
        }

        // 2b. Lọc theo mã sản phẩm
        if (!$skipProductCode) {
            $prodCode = $this->normalizeProductCodeFilter($filters['product_code'] ?? '');
            if ($prodCode !== '') {
                $sql .= " AND TRIM(JSON_UNQUOTE(JSON_EXTRACT({$p}products, '$.product_code'))) = :f_prod_code";
                $params[':f_prod_code'] = $prodCode;
            }
        }

        // 2c. Lọc theo tình trạng ngoại quan QC
        if (!$skipQcDefect) {
            $qcDefect = trim((string)($filters['qc_defect'] ?? 'all'));
            if ($qcDefect !== '' && $qcDefect !== 'all') {
                $sql .= " AND " . $this->getQcDefectFilterCondition($qcDefect, $prefix);
            }
        }

        // 2d. Lọc theo máy đùn (TASK-051)
        $extMachine = trim((string)($filters['machine'] ?? $filters['extrusion_machine'] ?? ''));
        if ($extMachine !== '' && $extMachine !== 'all') {
            $sql .= " AND {$p}extrusion_machine = :f_ext_machine";
            $params[':f_ext_machine'] = $extMachine;
        }

        // 2e. Lọc theo máy cuộn (TASK-051)
        $windMachine = trim((string)($filters['winding_machine'] ?? ''));
        if ($windMachine !== '' && $windMachine !== 'all') {
            $sql .= " AND {$p}winding_machine = :f_wind_machine";
            $params[':f_wind_machine'] = $windMachine;
        }
    }

    /**
     * Tính thống kê số lượng Bobin theo từng tình trạng ngoại quan QC từ tập bản ghi (dùng cho History, Chờ hủy, Đã hủy)
     */
    public function computeQcDefectStatsFromRows(array $rows): array
    {
        $stats = [
            'total_bobins'    => count($rows),
            'inspected_total' => 0,
            'uninspected'     => 0,
            'ok_all'          => 0,
            'ng_any'          => 0,
            'gel'             => 0,
            'foreign_object'  => 0,
            'color_issue'     => 0,
            'print_quality'   => 0,
            'clump'           => 0,
            'scratch'         => 0,
            'streak'          => 0,
            'deformation'     => 0,
        ];

        foreach ($rows as $row) {
            $viRaw = $row['visual_inspection'] ?? '';
            $vi = is_string($viRaw) && $viRaw !== '' ? (json_decode($viRaw, true) ?: []) : (is_array($viRaw) ? $viRaw : []);
            $inspCode = trim((string)($vi['inspector_code'] ?? ''));
            if ($inspCode === '' || $inspCode === 'Chưa cập nhật') {
                $stats['uninspected']++;
                continue;
            }

            $stats['inspected_total']++;
            $def = is_array($vi['defects'] ?? null) ? $vi['defects'] : [];

            $isGel     = !empty($def['gel']) || !empty($vi['defect_gel']);
            $isForeign = !empty($def['foreign_object']) || !empty($vi['defect_foreign_object']);
            $isColor   = !empty($def['color_issue']) || !empty($vi['defect_color_issue']);
            $isPrint   = !empty($def['print_quality']) || !empty($vi['defect_print_quality']);
            $isClump   = !empty($def['clump']) || !empty($def['lump']) || !empty($vi['defect_clump']);
            $isScratch = !empty($def['scratch']) || !empty($vi['defect_scratch']);
            $isStreak  = !empty($def['streak']) || !empty($def['scuff']) || !empty($vi['defect_streak']);
            $isDeform  = !empty($def['deformation']) || !empty($def['deformed']) || !empty($vi['defect_deformation']);

            if ($isGel)     $stats['gel']++;
            if ($isForeign) $stats['foreign_object']++;
            if ($isColor)   $stats['color_issue']++;
            if ($isPrint)   $stats['print_quality']++;
            if ($isClump)   $stats['clump']++;
            if ($isScratch) $stats['scratch']++;
            if ($isStreak)  $stats['streak']++;
            if ($isDeform)  $stats['deformation']++;

            if ($isGel || $isForeign || $isColor || $isPrint || $isClump || $isScratch || $isStreak || $isDeform) {
                $stats['ng_any']++;
            } else {
                $stats['ok_all']++;
            }
        }

        return $stats;
    }

    /**
     * Tính thống kê số lượng Bobin theo từng Mã sản phẩm từ tập bản ghi
     */
    public function computeProductCountsFromRows(array $rows): array
    {
        $counts = [];
        foreach ($rows as $row) {
            $pRaw = $row['products'] ?? '';
            $p = is_string($pRaw) && $pRaw !== '' ? (json_decode($pRaw, true) ?: []) : (is_array($pRaw) ? $pRaw : []);
            $code = trim((string)($p['product_code'] ?? ''));
            if ($code !== '' && $code !== 'Chưa cập nhật') {
                $counts[$code] = ($counts[$code] ?? 0) + 1;
            }
        }
        ksort($counts);
        return $counts;
    }

    /**
     * Thống kê số lượng Bobin của từng tình trạng ngoại quan QC trên trang Danh sách Bobin (toàn tập dữ liệu lọc, không giới hạn phân trang)
     */
    public function getDetailQcDefectStats(array $filters = []): array
    {
        $pdo = $this->db->pdo();
        try {
            $baseTable   = $this->getActiveBaseTable();
            $extComplete = $this->getExtrusionCompleteCondition();
            $inspCond    = $this->getQcInspectedCondition();

            $gelExpr     = $this->getSingleQcDefectNgExpr('gel');
            $foreignExpr = $this->getSingleQcDefectNgExpr('foreign_object');
            $colorExpr   = $this->getSingleQcDefectNgExpr('color_issue');
            $printExpr   = $this->getSingleQcDefectNgExpr('print_quality');
            $clumpExpr   = $this->getSingleQcDefectNgExpr('clump');
            $scratchExpr = $this->getSingleQcDefectNgExpr('scratch');
            $streakExpr  = $this->getSingleQcDefectNgExpr('streak');
            $deformExpr  = $this->getSingleQcDefectNgExpr('deformation');
            $anyNgExpr   = "($gelExpr OR $foreignExpr OR $colorExpr OR $printExpr OR $clumpExpr OR $scratchExpr OR $streakExpr OR $deformExpr)";

            $sql = "SELECT
                        COUNT(*) AS total_bobins,
                        COALESCE(SUM(CASE WHEN $inspCond THEN 1 ELSE 0 END), 0) AS inspected_total,
                        COALESCE(SUM(CASE WHEN NOT $inspCond THEN 1 ELSE 0 END), 0) AS uninspected,
                        COALESCE(SUM(CASE WHEN $inspCond AND NOT $anyNgExpr THEN 1 ELSE 0 END), 0) AS ok_all,
                        COALESCE(SUM(CASE WHEN $inspCond AND $anyNgExpr THEN 1 ELSE 0 END), 0) AS ng_any,
                        COALESCE(SUM(CASE WHEN $inspCond AND $gelExpr THEN 1 ELSE 0 END), 0) AS gel,
                        COALESCE(SUM(CASE WHEN $inspCond AND $foreignExpr THEN 1 ELSE 0 END), 0) AS foreign_object,
                        COALESCE(SUM(CASE WHEN $inspCond AND $colorExpr THEN 1 ELSE 0 END), 0) AS color_issue,
                        COALESCE(SUM(CASE WHEN $inspCond AND $printExpr THEN 1 ELSE 0 END), 0) AS print_quality,
                        COALESCE(SUM(CASE WHEN $inspCond AND $clumpExpr THEN 1 ELSE 0 END), 0) AS clump,
                        COALESCE(SUM(CASE WHEN $inspCond AND $scratchExpr THEN 1 ELSE 0 END), 0) AS scratch,
                        COALESCE(SUM(CASE WHEN $inspCond AND $streakExpr THEN 1 ELSE 0 END), 0) AS streak,
                        COALESCE(SUM(CASE WHEN $inspCond AND $deformExpr THEN 1 ELSE 0 END), 0) AS deformation
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

            $statusVal = !empty($filters['status']) ? $filters['status'] : 'all';
            if ($statusVal === 'Empty') {
                $sql .= " AND NOT (bobin_current_status IN ('Busy_Unchecked', 'Busy_Checked', 'Pending_Cancellation') AND {$extComplete})";
            } else {
                $sql .= " AND {$extComplete}";
                if ($statusVal === 'Line') {
                    $sql .= " AND bobin_current_status IN ('Busy_Unchecked', 'Busy_Checked')";
                } elseif ($statusVal !== 'all') {
                    $sql .= " AND bobin_current_status = :status";
                    $params[':status'] = $statusVal;
                }
            }

            // Áp dụng các bộ lọc nâng cao (employee_code, product_code, qc_defect)
            $this->appendAdvancedManagementFilters($sql, $params, $filters);

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

            return [
                'total_bobins'    => (int)($row['total_bobins'] ?? 0),
                'inspected_total' => (int)($row['inspected_total'] ?? 0),
                'uninspected'     => (int)($row['uninspected'] ?? 0),
                'ok_all'          => (int)($row['ok_all'] ?? 0),
                'ng_any'          => (int)($row['ng_any'] ?? 0),
                'gel'             => (int)($row['gel'] ?? 0),
                'foreign_object'  => (int)($row['foreign_object'] ?? 0),
                'color_issue'     => (int)($row['color_issue'] ?? 0),
                'print_quality'   => (int)($row['print_quality'] ?? 0),
                'clump'           => (int)($row['clump'] ?? 0),
                'scratch'         => (int)($row['scratch'] ?? 0),
                'streak'          => (int)($row['streak'] ?? 0),
                'deformation'     => (int)($row['deformation'] ?? 0),
            ];
        } catch (PDOException $e) {
            error_log("DB Error: " . $e->getMessage());
            return $this->computeQcDefectStatsFromRows([]);
        }
    }

    /**
     * Thống kê số lượng Bobin theo từng Mã sản phẩm trên trang Danh sách Bobin (phục vụ gợi ý và thống kê theo điều kiện lọc hiện tại)
     */
    public function getDetailProductCounts(array $filters = []): array
    {
        $pdo = $this->db->pdo();
        try {
            $baseTable   = $this->getActiveBaseTable();
            $extComplete = $this->getExtrusionCompleteCondition();
            $sql = "SELECT TRIM(JSON_UNQUOTE(JSON_EXTRACT(products, '$.product_code'))) AS pcode, COUNT(*) AS cnt
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

            $statusVal = !empty($filters['status']) ? $filters['status'] : 'all';
            if ($statusVal === 'Empty') {
                $sql .= " AND NOT (bobin_current_status IN ('Busy_Unchecked', 'Busy_Checked', 'Pending_Cancellation') AND {$extComplete})";
            } else {
                $sql .= " AND {$extComplete}";
                if ($statusVal === 'Line') {
                    $sql .= " AND bobin_current_status IN ('Busy_Unchecked', 'Busy_Checked')";
                } elseif ($statusVal !== 'all') {
                    $sql .= " AND bobin_current_status = :status";
                    $params[':status'] = $statusVal;
                }
            }

            // Áp dụng employee_code và qc_defect (bỏ qua product_code để lấy đủ số lượng cho từng mã SP trong gợi ý)
            $this->appendAdvancedManagementFilters($sql, $params, $filters, '', false, true);

            $sql .= " GROUP BY pcode ORDER BY pcode ASC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

            $result = [];
            foreach ($rows as $code => $cnt) {
                $clean = trim((string)$code);
                if ($clean !== '' && $clean !== 'Chưa cập nhật') {
                    $result[$clean] = (int)$cnt;
                }
            }
            return $result;
        } catch (PDOException $e) {
            error_log("DB Error: " . $e->getMessage());
            return [];
        }
    }

    public function countBobinListDetail(array $filters = []): int
    {
        $pdo = $this->db->pdo();
        try {
            $baseTable = $this->getActiveBaseTable();
            $extComplete = $this->getExtrusionCompleteCondition();
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

            $statusVal = !empty($filters['status']) ? $filters['status'] : 'all';
            if ($statusVal === 'Empty') {
                $sql .= " AND NOT (bobin_current_status IN ('Busy_Unchecked', 'Busy_Checked', 'Pending_Cancellation') AND {$extComplete})";
            } else {
                $sql .= " AND {$extComplete}";
                if ($statusVal === 'Line') {
                    $sql .= " AND bobin_current_status IN ('Busy_Unchecked', 'Busy_Checked')";
                } elseif ($statusVal !== 'all') {
                    $sql .= " AND bobin_current_status = :status";
                    $params[':status'] = $statusVal;
                }
            }

            // Lọc nâng cao: Mã NV (Đùn/Cuộn/QC), Mã Sản phẩm, Tình trạng ngoại quan QC
            $this->appendAdvancedManagementFilters($sql, $params, $filters);

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
            $extComplete = $this->getExtrusionCompleteCondition();
            $sql = "SELECT bobin_current_status, COUNT(*) AS total 
                    FROM $baseTable AS active_bobins WHERE {$extComplete}";
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

            // Lọc nâng cao: Mã NV (Đùn/Cuộn/QC), Mã Sản phẩm, Tình trạng ngoại quan QC
            $this->appendAdvancedManagementFilters($sql, $params, $filters);

            $sql .= " GROUP BY bobin_current_status";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

            $rolled        = (int)($rows['Rolled'] ?? 0);
            $busyUnchecked = (int)($rows['Busy_Unchecked'] ?? 0);
            $busyChecked   = (int)($rows['Busy_Checked'] ?? 0);
            $pendingCancel = (int)($rows['Pending_Cancellation'] ?? 0);
            $cancelled     = (int)($rows['Cancelled'] ?? 0);

            return [
                'Rolled'               => $rolled,
                'Busy_Unchecked'       => $busyUnchecked,
                'Busy_Checked'         => $busyChecked,
                'Line'                 => $busyUnchecked + $busyChecked,
                'Pending_Cancellation' => $pendingCancel,
                'Cancelled'            => $cancelled,
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
            $extComplete = $this->getExtrusionCompleteCondition();
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

            // 5. Lọc theo Trạng thái và điều kiện đầy đủ thông tin từ nhóm Đùn
            $statusVal = !empty($filters['status']) ? $filters['status'] : 'all';
            if ($statusVal === 'Empty') {
                $sql .= " AND NOT (bobin_current_status IN ('Busy_Unchecked', 'Busy_Checked', 'Pending_Cancellation') AND {$extComplete})";
            } else {
                $sql .= " AND {$extComplete}";
                if ($statusVal === 'Line') {
                    $sql .= " AND bobin_current_status IN ('Busy_Unchecked', 'Busy_Checked')";
                } elseif ($statusVal !== 'all') {
                    $sql .= " AND bobin_current_status = :status";
                    $params[':status'] = $statusVal;
                }
            }

            // 5b. Lọc nâng cao: Mã NV (Đùn/Cuộn/QC), Mã Sản phẩm, Tình trạng ngoại quan QC
            $this->appendAdvancedManagementFilters($sql, $params, $filters);

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

    private function buildExtrusionEditStatusCondition(string $statusFilter): string
    {
        $statusFilter = trim($statusFilter);
        return match ($statusFilter) {
            'Busy_Unchecked' => "bobin_current_status = 'Busy_Unchecked'",
            'Busy_Checked'   => "bobin_current_status = 'Busy_Checked'",
            'Rolled'         => "bobin_current_status = 'Rolled'",
            'post_qc'        => "bobin_current_status IN ('Busy_Checked', 'Rolled')",
            default          => "bobin_current_status IN ('Busy_Unchecked', 'Busy_Checked', 'Rolled')",
        };
    }

    public function getListDetailBobinsForEditting(array $filters = [], int $page = 1, int $limit = 50): array
    {
        $pdo = $this->db->pdo();

        try {
            $extComplete = $this->getExtrusionCompleteCondition();
            $statusCond  = $this->buildExtrusionEditStatusCondition((string)($filters['status'] ?? 'all'));
            $sql = "SELECT * FROM bobin_list_detail WHERE {$statusCond} AND {$extComplete}";

            $params = [];

            if (empty($filters['view_all'])) {
                $empCode = trim((string)($filters['extrusion_employee_code'] ?? ''));
                $userId  = (int)($filters['extrusion_user_id'] ?? 0);

                if ($empCode !== '' && $userId > 0) {
                    $sql .= " AND (
                        TRIM(COALESCE(JSON_UNQUOTE(JSON_EXTRACT(extrusion_employee, '$.employee_code')), '')) = :e_emp_code
                        OR CAST(COALESCE(JSON_UNQUOTE(JSON_EXTRACT(extrusion_employee, '$.id')), '0') AS UNSIGNED) = :e_user_id
                    )";
                    $params[':e_emp_code'] = $empCode;
                    $params[':e_user_id']  = $userId;
                } elseif ($empCode !== '') {
                    $sql .= " AND TRIM(COALESCE(JSON_UNQUOTE(JSON_EXTRACT(extrusion_employee, '$.employee_code')), '')) = :e_emp_code";
                    $params[':e_emp_code'] = $empCode;
                } elseif ($userId > 0) {
                    $sql .= " AND CAST(COALESCE(JSON_UNQUOTE(JSON_EXTRACT(extrusion_employee, '$.id')), '0') AS UNSIGNED) = :e_user_id";
                    $params[':e_user_id'] = $userId;
                } else {
                    return [];
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
            $sql .= " ORDER BY CASE bobin_current_status
                WHEN 'Busy_Unchecked' THEN 1
                WHEN 'Busy_Checked' THEN 2
                WHEN 'Rolled' THEN 3
                ELSE 4
            END ASC, updated_time DESC, bobin_identification_code ASC";

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

    public function countDetailBobinsForEditting(array $filters = []): int
    {
        $pdo = $this->db->pdo();

        try {
            $extComplete = $this->getExtrusionCompleteCondition();
            $statusCond  = $this->buildExtrusionEditStatusCondition((string)($filters['status'] ?? 'all'));
            $sql = "SELECT COUNT(*) FROM bobin_list_detail WHERE {$statusCond} AND {$extComplete}";
            $params = [];

            if (empty($filters['view_all'])) {
                $empCode = trim((string)($filters['extrusion_employee_code'] ?? ''));
                $userId  = (int)($filters['extrusion_user_id'] ?? 0);

                if ($empCode !== '' && $userId > 0) {
                    $sql .= " AND (
                        TRIM(COALESCE(JSON_UNQUOTE(JSON_EXTRACT(extrusion_employee, '$.employee_code')), '')) = :e_emp_code
                        OR CAST(COALESCE(JSON_UNQUOTE(JSON_EXTRACT(extrusion_employee, '$.id')), '0') AS UNSIGNED) = :e_user_id
                    )";
                    $params[':e_emp_code'] = $empCode;
                    $params[':e_user_id']  = $userId;
                } elseif ($empCode !== '') {
                    $sql .= " AND TRIM(COALESCE(JSON_UNQUOTE(JSON_EXTRACT(extrusion_employee, '$.employee_code')), '')) = :e_emp_code";
                    $params[':e_emp_code'] = $empCode;
                } elseif ($userId > 0) {
                    $sql .= " AND CAST(COALESCE(JSON_UNQUOTE(JSON_EXTRACT(extrusion_employee, '$.id')), '0') AS UNSIGNED) = :e_user_id";
                    $params[':e_user_id'] = $userId;
                } else {
                    return 0;
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

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            return (int)$stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("DB Error: " . $e->getMessage());
            throw new Exception("Database error: " . $e->getMessage());
        }
    }

    /**
     * Kiểm tra xem Bobin có được tạo thông tin đùn bởi đúng User hiện tại hay không
     */
    public function isBobinExtrusionCreatedByUser(
        string $identCode,
        string $empCode = '',
        int $userId = 0,
        ?array $allowedStatuses = null,
        ?string $keyCode = null
    ): bool {
        $identCode = trim($identCode);
        $empCode   = trim($empCode);
        if ($identCode === '' || ($empCode === '' && $userId <= 0)) {
            return false;
        }

        // Chỉ cho phép các trạng thái hợp lệ (không bao giờ cho phép Pending_Cancellation / Cancelled)
        $permittedPool = ['Busy_Unchecked', 'Busy_Checked', 'Rolled'];
        $statuses = !empty($allowedStatuses)
            ? array_values(array_intersect($allowedStatuses, $permittedPool))
            : ['Busy_Unchecked'];

        if (empty($statuses)) {
            return false;
        }

        $pdo = $this->db->pdo();
        try {
            $statusPlaceholders = [];
            $params = [':ident' => $identCode];
            foreach ($statuses as $idx => $st) {
                $ph = ":st_{$idx}";
                $statusPlaceholders[] = $ph;
                $params[$ph] = $st;
            }
            $inClause = implode(', ', $statusPlaceholders);
            $sql = "SELECT extrusion_employee FROM bobin_list_detail WHERE bobin_identification_code = :ident AND bobin_current_status IN ({$inClause})";
            if ($keyCode !== null && trim($keyCode) !== '') {
                $sql .= " AND bobin_key_code = :key";
                $params[':key'] = trim($keyCode);
            }
            $sql .= " LIMIT 1";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $rawExtEmp = $stmt->fetchColumn();
            if (empty($rawExtEmp)) {
                return false;
            }
            $eEmp = json_decode($rawExtEmp, true);
            if (!is_array($eEmp)) {
                return false;
            }
            $recordedCode = trim((string)($eEmp['employee_code'] ?? ''));
            $recordedId   = (int)($eEmp['id'] ?? 0);

            if ($empCode !== '' && $recordedCode !== '' && strcasecmp($empCode, $recordedCode) === 0) {
                return true;
            }
            if ($userId > 0 && $recordedId > 0 && $userId === $recordedId) {
                return true;
            }
            return false;
        } catch (PDOException $e) {
            error_log("DB isBobinExtrusionCreatedByUser Error: " . $e->getMessage());
            return false;
        }
    }

    public function getListDetailBobinsForQCEdit(array $filters = [], int $page = 1, int $limit = 50): array
    {
        $pdo = $this->db->pdo();
        try {
            // Task-005 & Task-047: Chỉ lấy những Bobin có status là 'Busy_Checked' và đầy đủ thông tin từ nhóm đùn
            $extComplete = $this->getExtrusionCompleteCondition();
            $sql = "SELECT * FROM bobin_list_detail WHERE bobin_current_status = 'Busy_Checked' AND {$extComplete}";
            $params = [];

            if (empty($filters['view_all'])) {
                $empCode = trim((string)($filters['qc_employee_code'] ?? ''));
                if ($empCode !== '') {
                    $sql .= " AND TRIM(COALESCE(JSON_UNQUOTE(JSON_EXTRACT(visual_inspection, '$.inspector_code')), '')) = :qc_emp_code";
                    $params[':qc_emp_code'] = $empCode;
                } else {
                    return [];
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
                    bobin_type LIKE :kw7 OR
                    JSON_EXTRACT(visual_inspection, '$.inspector_code') LIKE :kw8 OR
                    JSON_EXTRACT(visual_inspection, '$.inspector_name') LIKE :kw9
                )";
                for ($i = 1; $i <= 9; $i++) {
                    $params[":kw$i"] = $searchStr;
                }
            }

            $sql .= " ORDER BY updated_time DESC, bobin_identification_code ASC";
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

    public function countDetailBobinsForQCEdit(array $filters = []): int
    {
        $pdo = $this->db->pdo();
        try {
            $extComplete = $this->getExtrusionCompleteCondition();
            $sql = "SELECT COUNT(*) FROM bobin_list_detail WHERE bobin_current_status = 'Busy_Checked' AND {$extComplete}";
            $params = [];

            if (empty($filters['view_all'])) {
                $empCode = trim((string)($filters['qc_employee_code'] ?? ''));
                if ($empCode !== '') {
                    $sql .= " AND TRIM(COALESCE(JSON_UNQUOTE(JSON_EXTRACT(visual_inspection, '$.inspector_code')), '')) = :qc_emp_code";
                    $params[':qc_emp_code'] = $empCode;
                } else {
                    return 0;
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
                    bobin_type LIKE :kw7 OR
                    JSON_EXTRACT(visual_inspection, '$.inspector_code') LIKE :kw8 OR
                    JSON_EXTRACT(visual_inspection, '$.inspector_name') LIKE :kw9
                )";
                for ($i = 1; $i <= 9; $i++) {
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

    /**
     * Kiểm tra xem Bobin có được xác nhận kiểm tra ngoại quan QC bởi đúng User hiện tại hay không
     */
    public function isBobinQcInspectedByUser(string $identCode, string $empCode = ''): bool
    {
        $identCode = trim($identCode);
        $empCode   = trim($empCode);
        if ($identCode === '' || $empCode === '') {
            return false;
        }

        $pdo = $this->db->pdo();
        try {
            $sql = "SELECT visual_inspection FROM bobin_list_detail WHERE bobin_identification_code = :ident AND bobin_current_status = 'Busy_Checked' LIMIT 1";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':ident' => $identCode]);
            $rawVi = $stmt->fetchColumn();
            if (empty($rawVi)) {
                return false;
            }
            $vi = json_decode($rawVi, true);
            if (!is_array($vi)) {
                return false;
            }
            $recordedCode = trim((string)($vi['inspector_code'] ?? ''));
            return ($recordedCode !== '' && strcasecmp($empCode, $recordedCode) === 0);
        } catch (PDOException $e) {
            error_log("DB isBobinQcInspectedByUser Error: " . $e->getMessage());
            return false;
        }
    }

    public function getListDetailBobinsForWindingEdit(array $filters = [], int $page = 1, int $limit = 50): array
    {
        $pdo = $this->db->pdo();
        try {
            // Task-046 & Task-047: Chỉ hiển thị các Bobin có status là 'Rolled', đầy đủ thông tin từ nhóm đùn
            // Mặc định chỉ hiển thị Bobin do chính User hiện tại xác nhận cuộn, trừ khi có quyền winding_edit_view_all / admin
            $extComplete = $this->getExtrusionCompleteCondition();
            $sql = "SELECT * FROM bobin_list_detail WHERE bobin_current_status = 'Rolled' AND {$extComplete}";
            $params = [];

            if (empty($filters['view_all'])) {
                $empCode = trim((string)($filters['winding_employee_code'] ?? ''));
                $userId  = (int)($filters['winding_user_id'] ?? 0);

                if ($empCode !== '' && $userId > 0) {
                    $sql .= " AND (
                        TRIM(COALESCE(JSON_UNQUOTE(JSON_EXTRACT(winding_employee, '$.employee_code')), '')) = :w_emp_code
                        OR CAST(COALESCE(JSON_UNQUOTE(JSON_EXTRACT(winding_employee, '$.id')), '0') AS UNSIGNED) = :w_user_id
                    )";
                    $params[':w_emp_code'] = $empCode;
                    $params[':w_user_id']  = $userId;
                } elseif ($empCode !== '') {
                    $sql .= " AND TRIM(COALESCE(JSON_UNQUOTE(JSON_EXTRACT(winding_employee, '$.employee_code')), '')) = :w_emp_code";
                    $params[':w_emp_code'] = $empCode;
                } elseif ($userId > 0) {
                    $sql .= " AND CAST(COALESCE(JSON_UNQUOTE(JSON_EXTRACT(winding_employee, '$.id')), '0') AS UNSIGNED) = :w_user_id";
                    $params[':w_user_id'] = $userId;
                } else {
                    // Không có thông tin định danh nhân viên đang đăng nhập -> Không hiển thị Bobin nào
                    return [];
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

            $sql .= " ORDER BY updated_time DESC, bobin_identification_code ASC";
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

    public function countDetailBobinsForWindingEdit(array $filters = []): int
    {
        $pdo = $this->db->pdo();
        try {
            $extComplete = $this->getExtrusionCompleteCondition();
            $sql = "SELECT COUNT(*) FROM bobin_list_detail WHERE bobin_current_status = 'Rolled' AND {$extComplete}";
            $params = [];

            if (empty($filters['view_all'])) {
                $empCode = trim((string)($filters['winding_employee_code'] ?? ''));
                $userId  = (int)($filters['winding_user_id'] ?? 0);

                if ($empCode !== '' && $userId > 0) {
                    $sql .= " AND (
                        TRIM(COALESCE(JSON_UNQUOTE(JSON_EXTRACT(winding_employee, '$.employee_code')), '')) = :w_emp_code
                        OR CAST(COALESCE(JSON_UNQUOTE(JSON_EXTRACT(winding_employee, '$.id')), '0') AS UNSIGNED) = :w_user_id
                    )";
                    $params[':w_emp_code'] = $empCode;
                    $params[':w_user_id']  = $userId;
                } elseif ($empCode !== '') {
                    $sql .= " AND TRIM(COALESCE(JSON_UNQUOTE(JSON_EXTRACT(winding_employee, '$.employee_code')), '')) = :w_emp_code";
                    $params[':w_emp_code'] = $empCode;
                } elseif ($userId > 0) {
                    $sql .= " AND CAST(COALESCE(JSON_UNQUOTE(JSON_EXTRACT(winding_employee, '$.id')), '0') AS UNSIGNED) = :w_user_id";
                    $params[':w_user_id'] = $userId;
                } else {
                    return 0;
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

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            return (int)$stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("DB Error: " . $e->getMessage());
            throw new Exception("Database error: " . $e->getMessage());
        }
    }

    /**
     * Kiểm tra xem Bobin có được xác nhận thông tin cuộn bởi đúng User hiện tại hay không
     */
    public function isBobinWindingConfirmedByUser(string $identCode, string $empCode = '', int $userId = 0): bool
    {
        $identCode = trim($identCode);
        $empCode   = trim($empCode);
        if ($identCode === '' || ($empCode === '' && $userId <= 0)) {
            return false;
        }

        $pdo = $this->db->pdo();
        try {
            $sql = "SELECT winding_employee FROM bobin_list_detail WHERE bobin_identification_code = :ident AND bobin_current_status = 'Rolled' LIMIT 1";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':ident' => $identCode]);
            $rawWindingEmp = $stmt->fetchColumn();
            if (empty($rawWindingEmp)) {
                return false;
            }
            $wEmp = json_decode($rawWindingEmp, true);
            if (!is_array($wEmp)) {
                return false;
            }
            $recordedCode = trim((string)($wEmp['employee_code'] ?? ''));
            $recordedId   = (int)($wEmp['id'] ?? 0);

            if ($empCode !== '' && $recordedCode !== '' && strcasecmp($empCode, $recordedCode) === 0) {
                return true;
            }
            if ($userId > 0 && $recordedId > 0 && $userId === $recordedId) {
                return true;
            }
            return false;
        } catch (PDOException $e) {
            error_log("DB isBobinWindingConfirmedByUser Error: " . $e->getMessage());
            return false;
        }
    }

    public function adminUpdateQCBobin(
        string $identCode,
        string $keyCode,
        array $viData,
        ?string $newType,
        ?array $updateRecord,
        bool $updateQcImage = false,
        ?string $qcImagePath = null
    ): bool {
        $pdo = $this->db->pdo();
        try {
            $pdo->beginTransaction();

            $viJson = $this->json_utf8($viData);

            // Cập nhật bobin_list_detail
            $currStmt = $pdo->prepare("SELECT update_history FROM bobin_list_detail WHERE bobin_identification_code = :ident LIMIT 1");
            $currStmt->execute([':ident' => $identCode]);
            $rawHist = $currStmt->fetchColumn();
            $histArr = (!empty($rawHist)) ? (json_decode($rawHist, true) ?: []) : [];
            if ($updateRecord !== null) {
                $histArr[] = $updateRecord;
            }
            $histJson = !empty($histArr) ? $this->json_utf8($histArr) : null;

            $imgSqlPart = $updateQcImage ? ", qc_visual_image = :qc_img" : "";

            if (!empty($newType)) {
                $sqlDetail = "UPDATE bobin_list_detail SET visual_inspection = :visual, bobin_type = :type, update_history = :uhist{$imgSqlPart}, updated_time = NOW() WHERE bobin_identification_code = :ident";
                $stmtDetail = $pdo->prepare($sqlDetail);
                $detailParams = [':visual' => $viJson, ':type' => $newType, ':uhist' => $histJson, ':ident' => $identCode];
                if ($updateQcImage) {
                    $detailParams[':qc_img'] = $qcImagePath;
                }
                $stmtDetail->execute($detailParams);

                $sqlGen = "UPDATE bobin_list_general SET bobin_type = :type, updated_time = NOW() WHERE bobin_identification_code = :ident";
                $stmtGen = $pdo->prepare($sqlGen);
                $stmtGen->execute([':type' => $newType, ':ident' => $identCode]);
            } else {
                $sqlDetail = "UPDATE bobin_list_detail SET visual_inspection = :visual, update_history = :uhist{$imgSqlPart}, updated_time = NOW() WHERE bobin_identification_code = :ident";
                $stmtDetail = $pdo->prepare($sqlDetail);
                $detailParams = [':visual' => $viJson, ':uhist' => $histJson, ':ident' => $identCode];
                if ($updateQcImage) {
                    $detailParams[':qc_img'] = $qcImagePath;
                }
                $stmtDetail->execute($detailParams);
            }

            // Cập nhật bobin_history (chỉ cập nhật bản ghi QC mới nhất, không ghi đè visual_inspection lên bản ghi Busy_Unchecked)
            $currHistStmt = $pdo->prepare("SELECT id, update_history FROM bobin_history WHERE bobin_key_code = :key AND bobin_current_status = 'Busy_Checked' ORDER BY id DESC LIMIT 1");
            $currHistStmt->execute([':key' => $keyCode]);
            $histRow = $currHistStmt->fetch(PDO::FETCH_ASSOC);

            if (!$histRow) {
                $fallbackStmt = $pdo->prepare("SELECT id, update_history FROM bobin_history WHERE bobin_key_code = :key ORDER BY id DESC LIMIT 1");
                $fallbackStmt->execute([':key' => $keyCode]);
                $histRow = $fallbackStmt->fetch(PDO::FETCH_ASSOC);
            }

            if ($histRow) {
                $rawHistHist = $histRow['update_history'] ?? null;
                $histArrHist = (!empty($rawHistHist)) ? (json_decode($rawHistHist, true) ?: []) : [];
                if ($updateRecord !== null) {
                    $histArrHist[] = $updateRecord;
                }
                $histHistJson = !empty($histArrHist) ? $this->json_utf8($histArrHist) : null;

                $sqlHistory = "UPDATE bobin_history SET visual_inspection = :visual, update_history = :uhist{$imgSqlPart}, updated_time = NOW() WHERE id = :hist_id";
                $stmtHistory = $pdo->prepare($sqlHistory);
                $histParams = [':visual' => $viJson, ':uhist' => $histHistJson, ':hist_id' => $histRow['id']];
                if ($updateQcImage) {
                    $histParams[':qc_img'] = $qcImagePath;
                }
                $stmtHistory->execute($histParams);
            }

            // Đồng bộ visual_inspection (và qc_visual_image nếu đổi) cho các bản ghi sau QC của cùng chu kỳ (nếu có)
            $sqlSyncVi = "UPDATE bobin_history SET visual_inspection = :visual{$imgSqlPart}, updated_time = NOW() WHERE bobin_key_code = :key AND bobin_current_status != 'Busy_Unchecked'";
            $stmtSyncVi = $pdo->prepare($sqlSyncVi);
            $syncParams = [':visual' => $viJson, ':key' => $keyCode];
            if ($updateQcImage) {
                $syncParams[':qc_img'] = $qcImagePath;
            }
            $stmtSyncVi->execute($syncParams);

            if (!empty($newType)) {
                $sqlTypeHist = "UPDATE bobin_history SET bobin_type = :type WHERE bobin_key_code = :key";
                $stmtTypeHist = $pdo->prepare($sqlTypeHist);
                $stmtTypeHist->execute([':type' => $newType, ':key' => $keyCode]);
            }

            $pdo->commit();
            return true;
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("DB adminUpdateQCBobin Error: " . $e->getMessage());
            throw new Exception("Lỗi Database: " . $e->getMessage());
        }
    }

    public function adminUpdateWindingBobin(
        string $identCode,
        string $keyCode,
        string $machine,
        ?array $empData,
        string $flowResult,
        string $note,
        ?array $updateRecord,
        bool $updateWndImage = false,
        ?string $wndImagePath = null
    ): bool {
        $pdo = $this->db->pdo();
        try {
            $pdo->beginTransaction();

            $empJson = $empData ? $this->json_utf8($empData) : null;

            // Cập nhật bobin_list_detail
            $currStmt = $pdo->prepare("SELECT update_history FROM bobin_list_detail WHERE bobin_identification_code = :ident LIMIT 1");
            $currStmt->execute([':ident' => $identCode]);
            $rawHist = $currStmt->fetchColumn();
            $histArr = (!empty($rawHist)) ? (json_decode($rawHist, true) ?: []) : [];
            if ($updateRecord !== null) {
                $histArr[] = $updateRecord;
            }
            $histJson = !empty($histArr) ? $this->json_utf8($histArr) : null;

            $imgSqlPart = $updateWndImage ? ", winding_chart_image = :wnd_img" : "";

            $sqlDetail = "UPDATE bobin_list_detail SET 
                            winding_machine = :machine, 
                            winding_employee = :emp, 
                            flow_test_result = :flow, 
                            winding_note = :note, 
                            update_history = :uhist{$imgSqlPart},
                            updated_time = NOW() 
                          WHERE bobin_identification_code = :ident";
            $stmtDetail = $pdo->prepare($sqlDetail);
            $detailParams = [
                ':machine' => $machine,
                ':emp'     => $empJson,
                ':flow'    => $flowResult,
                ':note'    => $note,
                ':uhist'   => $histJson,
                ':ident'   => $identCode
            ];
            if ($updateWndImage) {
                $detailParams[':wnd_img'] = $wndImagePath;
            }
            $stmtDetail->execute($detailParams);

            // Cập nhật bobin_history (chỉ cập nhật bản ghi Rolled mới nhất của chu kỳ này)
            $currHistStmt = $pdo->prepare("SELECT id, update_history FROM bobin_history WHERE bobin_key_code = :key ORDER BY id DESC LIMIT 1");
            $currHistStmt->execute([':key' => $keyCode]);
            $histRow = $currHistStmt->fetch(PDO::FETCH_ASSOC);

            if ($histRow) {
                $rawHistHist = $histRow['update_history'] ?? null;
                $histArrHist = (!empty($rawHistHist)) ? (json_decode($rawHistHist, true) ?: []) : [];
                if ($updateRecord !== null) {
                    $histArrHist[] = $updateRecord;
                }
                $histHistJson = !empty($histArrHist) ? $this->json_utf8($histArrHist) : null;

                $sqlHistory = "UPDATE bobin_history SET 
                                winding_machine = :machine, 
                                winding_employee = :emp, 
                                flow_test_result = :flow, 
                                winding_note = :note, 
                                update_history = :uhist{$imgSqlPart},
                                updated_time = NOW() 
                              WHERE id = :hist_id";
                $stmtHistory = $pdo->prepare($sqlHistory);
                $histParams = [
                    ':machine' => $machine,
                    ':emp'     => $empJson,
                    ':flow'    => $flowResult,
                    ':note'    => $note,
                    ':uhist'   => $histHistJson,
                    ':hist_id' => $histRow['id']
                ];
                if ($updateWndImage) {
                    $histParams[':wnd_img'] = $wndImagePath;
                }
                $stmtHistory->execute($histParams);
            }

            $pdo->commit();
            return true;
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("DB adminUpdateWindingBobin Error: " . $e->getMessage());
            throw new Exception("Lỗi Database: " . $e->getMessage());
        }
    }

    public function getDetailBobinsForQC(array $filters = [], int $page = 1, int $limit = 50)
    {
        $pdo = $this->db->pdo();

        try {
            $extComplete = $this->getExtrusionCompleteCondition();
            $sql = "SELECT * FROM bobin_list_detail 
                WHERE bobin_current_status = 'Busy_Unchecked' AND {$extComplete}";

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
                bobin_type LIKE :kw7 OR
                extrusion_machine LIKE :kw8
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

            if (!empty($filters['machine']) && $filters['machine'] !== 'all') {
                $sql .= " AND extrusion_machine = :machine";
                $params[':machine'] = trim($filters['machine']);
            }

            if (!empty($filters['product_code']) && $filters['product_code'] !== 'all') {
                $pVal = trim($filters['product_code']);
                $sql .= " AND (
                    JSON_UNQUOTE(JSON_EXTRACT(products, '$.product_code')) = :pcode1
                    OR JSON_UNQUOTE(JSON_EXTRACT(products, '$.production_order_code')) = :pcode2
                )";
                $params[':pcode1'] = $pVal;
                $params[':pcode2'] = $pVal;
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

            $sql .= " ORDER BY updated_time DESC";
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

    public function countDetailBobinsForQC(array $filters = []): int
    {
        $pdo = $this->db->pdo();

        try {
            $extComplete = $this->getExtrusionCompleteCondition();
            $sql = "SELECT COUNT(*) FROM bobin_list_detail 
                WHERE bobin_current_status = 'Busy_Unchecked' AND {$extComplete}";

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
                bobin_type LIKE :kw7 OR
                extrusion_machine LIKE :kw8
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

            if (!empty($filters['machine']) && $filters['machine'] !== 'all') {
                $sql .= " AND extrusion_machine = :machine";
                $params[':machine'] = trim($filters['machine']);
            }

            if (!empty($filters['product_code']) && $filters['product_code'] !== 'all') {
                $pVal = trim($filters['product_code']);
                $sql .= " AND (
                    JSON_UNQUOTE(JSON_EXTRACT(products, '$.product_code')) = :pcode1
                    OR JSON_UNQUOTE(JSON_EXTRACT(products, '$.production_order_code')) = :pcode2
                )";
                $params[':pcode1'] = $pVal;
                $params[':pcode2'] = $pVal;
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
            $extComplete = $this->getExtrusionCompleteCondition();
            $sql = "SELECT * FROM bobin_list_detail 
                WHERE bobin_current_status = 'Pending_Cancellation' AND {$extComplete}";

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
                bobin_type LIKE :kw7 OR
                JSON_EXTRACT(winding_employee, '$.employee_code') LIKE :kw8 OR
                JSON_EXTRACT(winding_employee, '$.employee_name') LIKE :kw9 OR
                JSON_EXTRACT(visual_inspection, '$.inspector_code') LIKE :kw10 OR
                JSON_EXTRACT(visual_inspection, '$.inspector_name') LIKE :kw11
            )";

                for ($i = 1; $i <= 11; $i++) {
                    $params[":kw$i"] = $searchStr;
                }
            }

            // Task-046: Lọc mã nhân viên (Đùn/Cuộn/QC), mã sản phẩm, tình trạng ngoại quan QC
            $this->appendAdvancedManagementFilters($sql, $params, $filters, '');

            $sql .= " ORDER BY updated_time DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("DB Error: " . $e->getMessage());
            throw new Exception("Database error: " . $e->getMessage());
        }
    }

    public function getDetailBobinsForCancelled(array $filters = []): array
    {
        $pdo = $this->db->pdo();

        try {
            $extComplete = $this->getExtrusionCompleteCondition();
            $sql = "SELECT * FROM bobin_list_detail 
                WHERE bobin_current_status = 'Cancelled' AND {$extComplete}";

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

            if (!empty($filters['from_date'])) {
                $sql .= " AND DATE(updated_time) >= :from_date";
                $params[':from_date'] = $filters['from_date'];
            }
            if (!empty($filters['to_date'])) {
                $sql .= " AND DATE(updated_time) <= :to_date";
                $params[':to_date'] = $filters['to_date'];
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
                bobin_type LIKE :kw7 OR
                JSON_EXTRACT(winding_employee, '$.employee_code') LIKE :kw8 OR
                JSON_EXTRACT(winding_employee, '$.employee_name') LIKE :kw9 OR
                JSON_EXTRACT(visual_inspection, '$.inspector_code') LIKE :kw10 OR
                JSON_EXTRACT(visual_inspection, '$.inspector_name') LIKE :kw11 OR
                JSON_EXTRACT(cancellation_info, '$.cancelled_by_code') LIKE :kw12 OR
                JSON_EXTRACT(cancellation_info, '$.cancelled_by_name') LIKE :kw13
            )";

                for ($i = 1; $i <= 13; $i++) {
                    $params[":kw$i"] = $searchStr;
                }
            }

            // Task-046: Lọc mã nhân viên (Đùn/Cuộn/QC), mã sản phẩm, tình trạng ngoại quan QC
            $this->appendAdvancedManagementFilters($sql, $params, $filters, '');

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
            $extComplete = $this->getExtrusionCompleteCondition();
            // AI_CONTEXT & TASK-050: Busy_Checked hoặc Rolled trong vòng 7 ngày gần nhất (có đầy đủ thông tin từ nhóm đùn)
            $sql = "SELECT * FROM $baseTable AS active_bobins 
                    WHERE {$extComplete} AND (bobin_current_status = 'Busy_Checked' OR (bobin_current_status = 'Rolled' AND updated_time >= DATE_SUB(NOW(), INTERVAL 7 DAY)))";
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

            // TASK-051: Bộ lọc tương tự QC cho trang Cuộn (Máy đùn, Máy cuộn, Mã sản phẩm, Loại Bobin)
            if (!empty($filters['machine']) && $filters['machine'] !== 'all') {
                $sql .= " AND extrusion_machine = :machine";
                $params[':machine'] = $filters['machine'];
            }
            if (!empty($filters['winding_machine']) && $filters['winding_machine'] !== 'all') {
                $sql .= " AND winding_machine = :wind_machine";
                $params[':wind_machine'] = $filters['winding_machine'];
            }
            if (!empty($filters['product_code']) && $filters['product_code'] !== 'all') {
                $sql .= " AND (JSON_UNQUOTE(JSON_EXTRACT(products, '$.product_code')) = :pcode1 OR JSON_UNQUOTE(JSON_EXTRACT(products, '$.production_order_code')) = :pcode2)";
                $params[':pcode1'] = $filters['product_code'];
                $params[':pcode2'] = $filters['product_code'];
            }
            if (!empty($filters['bobin_type']) && $filters['bobin_type'] !== 'all') {
                $sql .= " AND bobin_type = :btype";
                $params[':btype'] = $filters['bobin_type'];
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
            $extComplete = $this->getExtrusionCompleteCondition();
            // AI_CONTEXT & TASK-050: Busy_Checked hoặc Rolled trong vòng 7 ngày gần nhất (có đầy đủ thông tin từ nhóm đùn)
            $sql = "SELECT COUNT(*) FROM $baseTable AS active_bobins 
                    WHERE {$extComplete} AND (bobin_current_status = 'Busy_Checked' OR (bobin_current_status = 'Rolled' AND updated_time >= DATE_SUB(NOW(), INTERVAL 7 DAY)))";
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

            // TASK-051: Bộ lọc tương tự QC cho trang Cuộn (Máy đùn, Máy cuộn, Mã sản phẩm, Loại Bobin)
            if (!empty($filters['machine']) && $filters['machine'] !== 'all') {
                $sql .= " AND extrusion_machine = :machine";
                $params[':machine'] = $filters['machine'];
            }
            if (!empty($filters['winding_machine']) && $filters['winding_machine'] !== 'all') {
                $sql .= " AND winding_machine = :wind_machine";
                $params[':wind_machine'] = $filters['winding_machine'];
            }
            if (!empty($filters['product_code']) && $filters['product_code'] !== 'all') {
                $sql .= " AND (JSON_UNQUOTE(JSON_EXTRACT(products, '$.product_code')) = :pcode1 OR JSON_UNQUOTE(JSON_EXTRACT(products, '$.production_order_code')) = :pcode2)";
                $params[':pcode1'] = $filters['product_code'];
                $params[':pcode2'] = $filters['product_code'];
            }
            if (!empty($filters['bobin_type']) && $filters['bobin_type'] !== 'all') {
                $sql .= " AND bobin_type = :btype";
                $params[':btype'] = $filters['bobin_type'];
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
            $sql = "SELECT * FROM bobin_list_detail WHERE bobin_identification_code = :ident ORDER BY id ASC LIMIT 1";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':ident' => trim($bobinIdentificationCode)]);
            $response = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!is_array($response)) {
                GlobalData::$bobinEntity = null;
                return null;
            }
            GlobalData::$bobinEntity = $this->mapArrayToBobinEntity($response);
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

            $identCode = strtoupper(trim($entity->identificationCode));
            $entity->identificationCode = $identCode;

            // 1. Khóa dòng cố định đã có sẵn trong bobin_list_detail theo bobin_identification_code
            // Tuyệt đối không INSERT thêm bản ghi mới nếu mã không tồn tại hoặc đã tồn tại.
            $lockStmt = $pdo->prepare(
                "SELECT * FROM bobin_list_detail
                 WHERE bobin_identification_code = :ident
                 ORDER BY id ASC
                 LIMIT 1
                 FOR UPDATE"
            );
            $lockStmt->execute([':ident' => $identCode]);
            $existingRow = $lockStmt->fetch(PDO::FETCH_ASSOC);

            if (!is_array($existingRow)) {
                throw new Exception("Mã định danh Bobin [{$identCode}] không tồn tại trong danh sách cố định của hệ thống.");
            }

            // Bảo toàn mã định danh gốc và kích thước cố định của Bobin trong CSDL
            $entity->identificationCode = (string)$existingRow['bobin_identification_code'];
            if (!empty($existingRow['bobin_size'])) {
                $entity->size = (string)$existingRow['bobin_size'];
            }

            $existingStatus = (string)($existingRow['bobin_current_status'] ?? '');
            if ($entity->currentStatus !== 'Cancelled') {
                if ($existingStatus === 'Busy_Unchecked') {
                    throw new Exception("Bobin {$identCode} đã được đùn. Đang đợi QC check. Không thể nhập mới!");
                }
                if ($existingStatus === 'Busy_Checked') {
                    throw new Exception("Bobin {$identCode} đã được QC check. Đang đợi cuộn. Không thể nhập mới!");
                }
                if ($existingStatus === 'Pending_Cancellation') {
                    throw new Exception("Bobin {$identCode} đang trong quá trình hủy. Không thể nhập mới!");
                }
            }

            $prevStatus = ($existingStatus !== '') ? $existingStatus : 'Rolled';

            if ($entity->currentStatus === 'Cancelled') {
                if (GlobalData::$bobinEntity !== null) {
                    $oldEntity = GlobalData::$bobinEntity;
                    $oldEntity->previousStatus = 'Pending_Cancellation';

                    $rawHist = $existingRow['update_history'] ?? null;
                    $histArr = (!empty($rawHist)) ? (json_decode($rawHist, true) ?: []) : [];
                    $histArr[] = [
                        'stage'         => 'extrusion',
                        'action'        => 'confirm_cancel',
                        'employee_code' => $entity->extrusion_employee->employee_code ?? ($_SESSION['user']['employee_code'] ?? 'Unknown'),
                        'employee_name' => $entity->extrusion_employee->employee_name ?? ($_SESSION['user']['employee_name'] ?? 'Unknown'),
                        'updated_at'    => date('Y-m-d H:i:s'),
                        'note'          => 'Xác nhận hủy'
                    ];
                    $histJson = $this->json_utf8($histArr);

                    $this->insertBobinHistoryRecord($pdo, $oldEntity, 'Cancelled', $histJson);
                    $prevStatus = 'Cancelled';
                }
            }

            $entity->previousStatus = $prevStatus;
            $entity->currentStatus = 'Busy_Unchecked';

            // 2. Snapshot vòng đời mới vào bobin_history, và CHỈ UPDATE bản ghi cố định sẵn có trên bobin_list_detail & bobin_list_general
            $this->insertBobinHistoryRecord($pdo, $entity, 'Busy_Unchecked');
            $this->updateBobinDetailRecord($pdo, $entity, $existingRow);
            $this->updateBobinGeneralRecord($pdo, $entity);

            $pdo->commit();
            return true;
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("DB Error: " . $e->getMessage());
            throw new Exception("Lỗi Database: " . $e->getMessage());
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    private function insertBobinHistoryRecord(PDO $pdo, BobinEntity $entity, string $nextstatus, ?string $updateHistoryJson = null): void
    {

        $timeExpression = ($nextstatus === "Busy_Unchecked")
            ? "DATE_ADD(NOW(), INTERVAL 1 SECOND)"
            : "NOW()";

        $sql = "INSERT INTO bobin_history (
                bobin_key_code, bobin_identification_code, bobin_size, bobin_type,
                extrusion_employee, extrusion_check, extrusion_machine, rack, products, material_lot, print_lot, length_m,
                shift, extrusion_date, finish_time, visual_inspection, winding_machine,
                winding_employee, flow_test_result, bobin_current_status, previous_status, winding_note,
                extrusion_chart_image, qc_visual_image, winding_chart_image,
                update_history, updated_time
            ) VALUES (
                :key, :ident, :size, :type, :extrusionemp, :ext_check, :extrusion_machine, :rack, :prod, :mat, :plot, :len,
                :shift, :edate, :ftime, :visual, :winding_machine, :winding_employee,
                :flow_test_result, :status, :prev_status, :note,
                :ext_img, :qc_img, :wnd_img,
                :uhist, {$timeExpression}
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
        $params = $this->getBobinInsertParams($fixedEntity);
        $params[':uhist'] = $updateHistoryJson;
        $stmt->execute($params);
    }

    /**
     * Chỉ UPDATE trên bản ghi Bobin cố định đã tồn tại trong bobin_list_detail (chỉ cập nhật các cột thực sự có thay đổi).
     * Tuyệt đối KHÔNG INSERT tạo dòng trùng bobin_identification_code.
     */
    private function updateBobinDetailRecord(PDO $pdo, BobinEntity $entity, array $existingRow): void
    {
        $desiredColumns = [
            'bobin_key_code'        => $entity->bobinKeyCode,
            'bobin_size'            => $entity->size,
            'bobin_type'            => $entity->type,
            'extrusion_employee'    => $this->json_utf8($entity->extrusion_employee),
            'extrusion_check'       => $entity->extrusion_check ? $this->json_utf8($entity->extrusion_check) : null,
            'extrusion_machine'     => $entity->extrusion_machine,
            'rack'                  => $entity->rack ? $this->json_utf8($entity->rack) : null,
            'products'              => $this->json_utf8($entity->product),
            'material_lot'          => $this->json_utf8($entity->materialLot),
            'print_lot'             => $entity->printLot,
            'length_m'              => number_format((float)$entity->length, 3, '.', ''),
            'shift'                 => $entity->shift,
            'extrusion_date'        => $entity->extrusionDate->format('Y-m-d'),
            'finish_time'           => $entity->finishTime->format('Y-m-d H:i:s'),
            'visual_inspection'     => $this->json_utf8($entity->visualInspection),
            'winding_machine'       => $entity->winding_machine,
            'winding_employee'      => $entity->winding_employee ? $this->json_utf8($entity->winding_employee) : null,
            'flow_test_result'      => $entity->flow_test_result,
            'bobin_current_status'  => 'Busy_Unchecked',
            'previous_status'       => $entity->previousStatus ?? 'Rolled',
            'winding_note'          => $entity->winding_note,
            'extrusion_chart_image' => $entity->extrusion_chart_image,
            'qc_visual_image'       => null,
            'winding_chart_image'   => null,
            'update_history'        => null,
            'updated_time'          => $entity->updatedTime->format('Y-m-d H:i:s'),
        ];

        $setClauses = [];
        $params = [];
        foreach ($desiredColumns as $col => $newVal) {
            $oldVal = $existingRow[$col] ?? null;
            $hasChanged = ($oldVal === null || $newVal === null)
                ? ($oldVal !== $newVal)
                : ((string)$oldVal !== (string)$newVal);

            if ($hasChanged) {
                $paramKey = ':upd_' . $col;
                $setClauses[] = "`{$col}` = {$paramKey}";
                $params[$paramKey] = $newVal;
            }
        }

        if (empty($setClauses)) {
            return;
        }

        $params[':target_ident'] = (string)$existingRow['bobin_identification_code'];
        if (isset($existingRow['id']) && $existingRow['id'] !== null && $existingRow['id'] !== '') {
            $params[':target_id'] = (int)$existingRow['id'];
            $sql = "UPDATE bobin_list_detail
                    SET " . implode(",\n                        ", $setClauses) . "
                    WHERE id = :target_id AND bobin_identification_code = :target_ident
                    LIMIT 1";
        } else {
            $sql = "UPDATE bobin_list_detail
                    SET " . implode(",\n                        ", $setClauses) . "
                    WHERE bobin_identification_code = :target_ident
                    LIMIT 1";
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
    }

    /**
     * Chỉ UPDATE trên bản ghi Bobin cố định đã tồn tại trong bobin_list_general (không INSERT tạo mới / không tăng AUTO_INCREMENT).
     */
    private function updateBobinGeneralRecord(PDO $pdo, BobinEntity $entity): void
    {
        $lockGen = $pdo->prepare(
            "SELECT * FROM bobin_list_general
             WHERE bobin_identification_code = :ident
             ORDER BY id ASC
             LIMIT 1
             FOR UPDATE"
        );
        $lockGen->execute([':ident' => $entity->identificationCode]);
        $existingGen = $lockGen->fetch(PDO::FETCH_ASSOC);

        if (!is_array($existingGen)) {
            return;
        }

        $desiredGen = [
            'bobin_key_code'       => $entity->bobinKeyCode,
            'bobin_size'           => $entity->size,
            'bobin_type'           => $entity->type,
            'bobin_current_status' => 'Busy_Unchecked',
            'previous_status'      => $entity->previousStatus ?? 'Rolled',
        ];

        $setClauses = [];
        $params = [];
        foreach ($desiredGen as $col => $newVal) {
            $oldVal = $existingGen[$col] ?? null;
            $hasChanged = ($oldVal === null || $newVal === null)
                ? ($oldVal !== $newVal)
                : ((string)$oldVal !== (string)$newVal);

            if ($hasChanged) {
                $paramKey = ':gen_' . $col;
                $setClauses[] = "`{$col}` = {$paramKey}";
                $params[$paramKey] = $newVal;
            }
        }

        if (empty($setClauses)) {
            return;
        }

        $setClauses[] = "`updated_time` = NOW()";
        $params[':gen_id'] = (int)$existingGen['id'];
        $params[':gen_ident'] = (string)$existingGen['bobin_identification_code'];

        $sql = "UPDATE bobin_list_general
                SET " . implode(", ", $setClauses) . "
                WHERE id = :gen_id AND bobin_identification_code = :gen_ident
                LIMIT 1";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
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
            ':extrusion_machine' => $entity->extrusion_machine,
            ':rack' => $entity->rack ? $this->json_utf8($entity->rack) : null, // Mới thêm
            ':prod' => $this->json_utf8($entity->product),
            ':mat' => $this->json_utf8($entity->materialLot),
            ':plot' => $entity->printLot,
            ':len' => $entity->length,
            ':shift' => $entity->shift,
            ':edate' => $entity->extrusionDate->format('Y-m-d'),
            ':ftime' => $entity->finishTime->format('Y-m-d H:i:s'),
            ':status' => $entity->currentStatus,
            ':prev_status' => $entity->previousStatus ?? 'Rolled',
            ':visual' => $this->json_utf8($entity->visualInspection),
            ':winding_machine' => $entity->winding_machine,
            ':winding_employee' => $entity->winding_employee ? $this->json_utf8($entity->winding_employee) : null,
            ':flow_test_result' => $entity->flow_test_result,
            ':note' => $entity->winding_note,
            ':ext_img' => $entity->extrusion_chart_image,
            ':qc_img' => $entity->qc_visual_image,
            ':wnd_img' => $entity->winding_chart_image,
        ];
    }
    #region Ext PUT
    public function extrusionUpdateBobin(BobinEntity $entity, ?array $updateRecord = null): bool
    {
        $pdo = $this->db->pdo();
        try {
            $pdo->beginTransaction();

            $entity->currentStatus = 'Busy_Unchecked';

            $this->extUpdateBobinDetail($pdo, $entity, $updateRecord);
            $this->extUpdateBobinGeneral($pdo, $entity);
            // Cập nhật lại bản ghi trong bobin_history theo bobin_key_code mà không POST dòng mới (Req VI & VII)
            $this->extUpdateBobinHistory($pdo, $entity, $updateRecord);

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

    private function extUpdateBobinDetail(PDO $pdo, BobinEntity $entity, ?array $updateRecord = null): void
    {
        // AI_CONTEXT: Cập nhật updated_time khi có bất kì thay đổi gì
        $sql = "UPDATE bobin_list_detail SET
                    bobin_type = :type,
                    extrusion_employee = :extrusionemp,
                    extrusion_check = :ext_check,
                    extrusion_machine = :extrusion_machine,
                    rack = :rack,
                    products = :prod,
                    material_lot = :mat,
                    print_lot = :plot,
                    length_m = :len,
                    shift = :shift,
                    extrusion_date = :edate,
                    finish_time = :ftime,
                    extrusion_chart_image = :ext_img,
                    bobin_current_status = :status,
                    update_history = :uhist,
                    updated_time = NOW()
                WHERE bobin_identification_code = :ident";

        // Lấy lịch sử cập nhật hiện tại và nối thêm bản ghi mới
        $currStmt = $pdo->prepare("SELECT update_history FROM bobin_list_detail WHERE bobin_identification_code = :ident LIMIT 1");
        $currStmt->execute([':ident' => $entity->identificationCode]);
        $rawHist = $currStmt->fetchColumn();
        $histArr = (!empty($rawHist)) ? (json_decode($rawHist, true) ?: []) : [];
        if ($updateRecord !== null) {
            $histArr[] = $updateRecord;
        }

        $stmt = $pdo->prepare($sql);
        $params = $this->getBobinExtUpdateParams($entity);
        $params[':status'] = 'Busy_Unchecked';
        $params[':uhist']  = !empty($histArr) ? $this->json_utf8($histArr) : null;
        $stmt->execute($params);
    }

    private function extUpdateBobinGeneral(PDO $pdo, BobinEntity $entity): void
    {
        // AI_CONTEXT: Cập nhật updated_time khi có bất kì thay đổi gì
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

    private function extUpdateBobinHistory(PDO $pdo, BobinEntity $entity, ?array $updateRecord = null): void
    {
        if (empty($entity->bobinKeyCode)) {
            return;
        }

        // Tìm đến bobin_key_code tương ứng để update dữ liệu, cập nhật updated_time (AI_CONTEXT)
        $sql = "UPDATE bobin_history SET
                    bobin_type = :type,
                    extrusion_employee = :extrusionemp,
                    extrusion_check = :ext_check,
                    extrusion_machine = :extrusion_machine,
                    rack = :rack,
                    products = :prod,
                    material_lot = :mat,
                    print_lot = :plot,
                    length_m = :len,
                    shift = :shift,
                    extrusion_date = :edate,
                    finish_time = :ftime,
                    extrusion_chart_image = :ext_img,
                    bobin_current_status = :status,
                    update_history = :uhist,
                    updated_time = NOW()
                WHERE bobin_key_code = :key";

        $currStmt = $pdo->prepare("SELECT update_history FROM bobin_history WHERE bobin_key_code = :key LIMIT 1");
        $currStmt->execute([':key' => $entity->bobinKeyCode]);
        $rawHist = $currStmt->fetchColumn();
        $histArr = (!empty($rawHist)) ? (json_decode($rawHist, true) ?: []) : [];
        if ($updateRecord !== null) {
            $histArr[] = $updateRecord;
        }

        $stmt = $pdo->prepare($sql);
        $params = $this->getBobinExtUpdateParams($entity);
        unset($params[':ident']);
        $params[':key'] = $entity->bobinKeyCode;
        $params[':status'] = 'Busy_Unchecked';
        $params[':uhist']  = !empty($histArr) ? $this->json_utf8($histArr) : null;
        $stmt->execute($params);
    }

    private function getBobinExtUpdateParams(BobinEntity $entity): array
    {
        return [
            ':ident' => $entity->identificationCode,
            ':type' => $entity->type,
            ':extrusionemp' => $this->json_utf8($entity->extrusion_employee),
            ':ext_check' => $entity->extrusion_check ? $this->json_utf8($entity->extrusion_check) : null, // Mới thêm
            ':extrusion_machine' => $entity->extrusion_machine,
            ':rack' => $entity->rack ? $this->json_utf8($entity->rack) : null, // Mới thêm
            ':prod' => $this->json_utf8($entity->product),
            ':mat' => $this->json_utf8($entity->materialLot),
            ':plot' => $entity->printLot,
            ':len' => $entity->length,
            ':shift' => $entity->shift,
            ':edate' => $entity->extrusionDate->format('Y-m-d'),
            ':ftime' => $entity->finishTime->format('Y-m-d H:i:s'),
            ':ext_img' => $entity->extrusion_chart_image,
        ];
    }

    /**
     * TASK-054: Chỉ cập nhật hình ảnh biểu đồ dữ liệu đo của nhóm Đùn (extrusion_chart_image)
     * mà KHÔNG làm thay đổi bất kỳ thông tin nghiệp vụ nào khác, KHÔNG đổi trạng thái Bobin,
     * KHÔNG chạm thông tin QC, thông tin Cuộn, bảng bobin_list_general hay cột updated_time.
     */
    public function updateExtrusionChartImageOnly(
        string $identCode,
        string $keyCode,
        ?string $extImagePath,
        ?array $updateRecord = null
    ): bool {
        $identCode = trim($identCode);
        $keyCode   = trim($keyCode);
        if ($identCode === '' || $keyCode === '') {
            throw new Exception(__('err_ext_img_cycle_not_found'));
        }

        $pdo = $this->db->pdo();
        try {
            $pdo->beginTransaction();

            $lockStmt = $pdo->prepare(
                "SELECT bobin_current_status, update_history
                 FROM bobin_list_detail
                 WHERE bobin_identification_code = :ident
                   AND bobin_key_code = :key
                 LIMIT 1
                 FOR UPDATE"
            );
            $lockStmt->execute([
                ':ident' => $identCode,
                ':key'   => $keyCode,
            ]);
            $detailRow = $lockStmt->fetch(PDO::FETCH_ASSOC);

            if (!$detailRow) {
                throw new Exception(__('err_ext_img_cycle_not_found'));
            }

            $currentStatus = trim((string)($detailRow['bobin_current_status'] ?? ''));
            $allowedStatuses = ['Busy_Unchecked', 'Busy_Checked', 'Rolled'];
            if (!in_array($currentStatus, $allowedStatuses, true)) {
                throw new Exception(__('err_ext_img_status_not_allowed'));
            }

            $rawHist = $detailRow['update_history'] ?? null;
            $histArr = (!empty($rawHist)) ? (json_decode($rawHist, true) ?: []) : [];
            if ($updateRecord !== null) {
                $histArr[] = $updateRecord;
            }
            $histJson = !empty($histArr) ? $this->json_utf8($histArr) : null;

            // 1. Chỉ cập nhật duy nhất extrusion_chart_image và vết kiểm toán update_history trên bobin_list_detail
            $stmtDetail = $pdo->prepare(
                "UPDATE bobin_list_detail
                 SET extrusion_chart_image = :ext_img,
                     update_history = :uhist
                 WHERE bobin_identification_code = :ident
                   AND bobin_key_code = :key"
            );
            $stmtDetail->execute([
                ':ext_img' => $extImagePath,
                ':uhist'   => $histJson,
                ':ident'   => $identCode,
                ':key'     => $keyCode,
            ]);

            // 2. Đồng bộ duy nhất cột extrusion_chart_image cho các bản ghi lịch sử của đúng chu kỳ bobin_key_code
            $stmtHistImg = $pdo->prepare(
                "UPDATE bobin_history
                 SET extrusion_chart_image = :ext_img
                 WHERE bobin_identification_code = :ident
                   AND bobin_key_code = :key"
            );
            $stmtHistImg->execute([
                ':ext_img' => $extImagePath,
                ':ident'   => $identCode,
                ':key'     => $keyCode,
            ]);

            // 3. Ghi vết kiểm toán update_history vào dòng lịch sử mới nhất của chu kỳ mà không chạm updated_time hay cột khác
            if ($updateRecord !== null) {
                $latestHistStmt = $pdo->prepare(
                    "SELECT id, update_history
                     FROM bobin_history
                     WHERE bobin_identification_code = :ident
                       AND bobin_key_code = :key
                     ORDER BY id DESC
                     LIMIT 1"
                );
                $latestHistStmt->execute([
                    ':ident' => $identCode,
                    ':key'   => $keyCode,
                ]);
                $latestHistRow = $latestHistStmt->fetch(PDO::FETCH_ASSOC);
                if ($latestHistRow) {
                    $rawLatestHist = $latestHistRow['update_history'] ?? null;
                    $latestHistArr = (!empty($rawLatestHist)) ? (json_decode($rawLatestHist, true) ?: []) : [];
                    $latestHistArr[] = $updateRecord;
                    $latestHistJson = $this->json_utf8($latestHistArr);

                    $updHistAuditStmt = $pdo->prepare(
                        "UPDATE bobin_history
                         SET update_history = :uhist
                         WHERE id = :hist_id"
                    );
                    $updHistAuditStmt->execute([
                        ':uhist'   => $latestHistJson,
                        ':hist_id' => $latestHistRow['id'],
                    ]);
                }
            }

            $pdo->commit();
            return true;
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("DB updateExtrusionChartImageOnly Error: " . $e->getMessage());
            throw new Exception("Lỗi Database: " . $e->getMessage());
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }
    #endregion

    //! Cancel Winding / Final Delete
    public function deleteBobin(BobinEntity $entity, ?array $updateRecord = null)
    {
        $pdo = $this->db->pdo();

        try {
            $pdo->beginTransaction();

            // Lấy lịch sử cập nhật hiện tại và nối thêm bản ghi 'Xác nhận hủy'
            $currStmt = $pdo->prepare("SELECT update_history FROM bobin_list_detail WHERE bobin_key_code = :key AND bobin_identification_code = :ident LIMIT 1");
            $currStmt->execute([
                ':key'   => $entity->bobinKeyCode,
                ':ident' => $entity->identificationCode
            ]);
            $rawHist = $currStmt->fetchColumn();
            $histArr = (!empty($rawHist)) ? (json_decode($rawHist, true) ?: []) : [];
            if ($updateRecord !== null) {
                $histArr[] = $updateRecord;
            }
            $histJson = !empty($histArr) ? $this->json_utf8($histArr) : null;

            $sql = "UPDATE bobin_list_detail SET
                previous_status = 'Pending_Cancellation',
                bobin_current_status = :status,
                update_history = :uhist,
                updated_time = :updated
            WHERE bobin_key_code = :key
                AND bobin_identification_code = :ident";

            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                ':status'   => $entity->currentStatus,
                ':uhist'    => $histJson,
                ':updated'  => $entity->updatedTime->format('Y-m-d H:i:s'),
                ':key'      => $entity->bobinKeyCode,
                ':ident'    => $entity->identificationCode,
            ]);

            $sqlGeneral = "UPDATE bobin_list_general SET
                            previous_status = 'Pending_Cancellation',
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
                                extrusion_employee, extrusion_check, extrusion_machine, rack, products, material_lot, print_lot, length_m, 
                                shift, extrusion_date, finish_time, visual_inspection, 
                                winding_machine, winding_employee, bobin_current_status, previous_status, winding_note, flow_test_result,
                                extrusion_chart_image, qc_visual_image, winding_chart_image,
                                update_history, updated_time
                            )
                            SELECT 
                                bobin_key_code, bobin_identification_code, bobin_size, bobin_type, 
                                extrusion_employee, extrusion_check, extrusion_machine, rack, products, material_lot, print_lot, length_m, 
                                shift, extrusion_date, finish_time, visual_inspection, 
                                winding_machine, winding_employee, bobin_current_status, previous_status, winding_note, flow_test_result,
                                extrusion_chart_image, qc_visual_image, winding_chart_image,
                                update_history, updated_time
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

    /**
     * Khôi phục (Restore) Bobin từ danh sách chờ hủy (Pending_Cancellation) về trạng thái trước đó
     * Quy tắc TASK-032:
     * - Nếu đưa vào chờ hủy bởi nhóm Đùn -> trả về Busy_Unchecked (Chưa kiểm tra QC)
     * - Nếu đưa vào chờ hủy bởi nhóm QC -> trả về Busy_Unchecked (Chưa kiểm tra QC)
     * - Nếu đưa vào chờ hủy bởi nhóm Cuộn, trước đó là Busy_Checked -> trả về Busy_Checked (Đã QC)
     * - Nếu đưa vào chờ hủy bởi nhóm Cuộn, trước đó là Rolled -> trả về Rolled (Đã Cuộn)
     */
    public function restoreBobin(string $identCode, string $keyCode, ?array $updateRecord = null): array
    {
        $pdo = $this->db->pdo();

        try {
            $pdo->beginTransaction();

            $sqlFind = "SELECT * FROM bobin_list_detail WHERE bobin_identification_code = :ident";
            $findParams = [':ident' => $identCode];
            if (!empty($keyCode)) {
                $sqlFind .= " AND bobin_key_code = :key";
                $findParams[':key'] = $keyCode;
            }
            $sqlFind .= " LIMIT 1";

            $stmtFind = $pdo->prepare($sqlFind);
            $stmtFind->execute($findParams);
            $row = $stmtFind->fetch(PDO::FETCH_ASSOC);

            if (!$row) {
                throw new Exception("Không tìm thấy thông tin Bobin [{$identCode}] cần khôi phục.");
            }

            if (($row['bobin_current_status'] ?? '') !== 'Pending_Cancellation') {
                throw new Exception("Bobin [{$identCode}] không nằm trong danh sách chờ hủy.");
            }

            $actualKeyCode = $row['bobin_key_code'];
            $prevStatus    = $row['previous_status'] ?? null;
            $windingNote   = $row['winding_note'] ?? '';
            $viData        = json_decode($row['visual_inspection'] ?? '{}', true) ?: [];
            $viNote        = $viData['defects']['note'] ?? ($viData['defect_note'] ?? '');

            // Xác định trạng thái đích khi Restore theo đúng 4 quy tắc TASK-032
            $isFromWinding = str_contains($windingNote, 'Yêu cầu hủy phía cuộn') || in_array($prevStatus, ['Busy_Checked', 'Rolled'], true);
            $isFromQC      = str_starts_with(trim($viNote), 'Yêu cầu hủy QC:');

            if ($isFromQC && !str_contains($windingNote, 'Yêu cầu hủy phía cuộn')) {
                // Đưa vào danh sách hủy bởi nhóm QC -> trả về trạng thái chưa QC
                $targetStatus = 'Busy_Unchecked';
            } elseif ($isFromWinding) {
                // Đưa vào danh sách hủy bởi nhóm Cuộn -> trả về trạng thái trước đó (Busy_Checked hoặc Rolled)
                if ($prevStatus === 'Rolled') {
                    $targetStatus = 'Rolled';
                } elseif ($prevStatus === 'Busy_Checked') {
                    $targetStatus = 'Busy_Checked';
                } else {
                    // Fallback kiểm tra lịch sử nếu previous_status bị NULL trên dữ liệu cũ
                    $stmtHistCheck = $pdo->prepare("SELECT bobin_current_status FROM bobin_history WHERE bobin_key_code = :key AND bobin_current_status IN ('Rolled', 'Busy_Checked') ORDER BY id DESC LIMIT 1");
                    $stmtHistCheck->execute([':key' => $actualKeyCode]);
                    $histStatus = $stmtHistCheck->fetchColumn();
                    $targetStatus = ($histStatus === 'Rolled') ? 'Rolled' : 'Busy_Checked';
                }
            } else {
                // Đưa vào danh sách hủy bởi nhóm Đùn (hoặc mặc định) -> trả về trạng thái chưa QC
                $targetStatus = 'Busy_Unchecked';
            }

            // Làm sạch hậu tố báo hủy để Bobin hoạt động bình thường ở công đoạn được khôi phục
            $newViJson       = $row['visual_inspection'];
            $newWMachine     = $row['winding_machine'];
            $newWEmp         = $row['winding_employee'];
            $newWNote        = $row['winding_note'];
            $newFlowResult   = $row['flow_test_result'];
            $newQcImg        = $row['qc_visual_image'] ?? null;
            $newWndImg       = $row['winding_chart_image'] ?? null;

            if ($targetStatus === 'Busy_Unchecked' && $isFromQC) {
                $newQcImg = null;
                if (isset($viData['defects']) && is_array($viData['defects'])) {
                    $cleanedNote = trim(preg_replace('/^Yêu cầu hủy QC:\s*/u', '', $viData['defects']['note'] ?? ''));
                    $viData['defects']['gel']            = false;
                    $viData['defects']['foreign_object'] = false;
                    $viData['defects']['color_issue']    = false;
                    $viData['defects']['print_quality']  = false;
                    $viData['defects']['note']           = $cleanedNote !== '' ? $cleanedNote : 'Chưa cập nhật';
                    $newViJson = $this->json_utf8($viData);
                }
            }

            if (in_array($targetStatus, ['Busy_Checked', 'Rolled'], true)) {
                $cleanedWNote = trim(str_replace('(Yêu cầu hủy phía cuộn)', '', $newWNote ?? ''));
                if ($targetStatus === 'Busy_Checked') {
                    $newWMachine   = 'Chưa cập nhật';
                    $newWEmp       = null;
                    $newWNote      = $cleanedWNote !== '' ? $cleanedWNote : 'Chưa cập nhật';
                    $newFlowResult = 'Thất bại';
                    $newWndImg     = null;
                } else {
                    $newWNote      = $cleanedWNote !== '' ? $cleanedWNote : 'Không có ghi chú';
                    $newFlowResult = 'Thành công';
                }
            }

            // Ghi vết kiểm toán vào update_history
            $histArr = (!empty($row['update_history'])) ? (json_decode($row['update_history'], true) ?: []) : [];
            if ($updateRecord !== null) {
                $updateRecord['restored_to'] = $targetStatus;
                $histArr[] = $updateRecord;
            }
            $histJson = !empty($histArr) ? $this->json_utf8($histArr) : null;

            // 1. Cập nhật bobin_list_detail
            $sqlUpdateDetail = "UPDATE bobin_list_detail SET
                                    previous_status = 'Pending_Cancellation',
                                    bobin_current_status = :target_status,
                                    visual_inspection = :visual,
                                    winding_machine = :wmachine,
                                    winding_employee = :wemp,
                                    winding_note = :wnote,
                                    flow_test_result = :flow,
                                    qc_visual_image = :qc_img,
                                    winding_chart_image = :wnd_img,
                                    update_history = :uhist,
                                    updated_time = NOW()
                                WHERE bobin_identification_code = :ident
                                  AND bobin_key_code = :key";
            $stmtDetail = $pdo->prepare($sqlUpdateDetail);
            $stmtDetail->execute([
                ':target_status' => $targetStatus,
                ':visual'        => $newViJson,
                ':wmachine'      => $newWMachine,
                ':wemp'          => $newWEmp,
                ':wnote'         => $newWNote,
                ':flow'          => $newFlowResult,
                ':qc_img'        => $newQcImg,
                ':wnd_img'       => $newWndImg,
                ':uhist'         => $histJson,
                ':ident'         => $identCode,
                ':key'           => $actualKeyCode
            ]);

            // 2. Cập nhật bobin_list_general
            $sqlUpdateGeneral = "UPDATE bobin_list_general SET
                                    previous_status = 'Pending_Cancellation',
                                    bobin_current_status = :target_status,
                                    updated_time = NOW()
                                 WHERE bobin_identification_code = :ident";
            $stmtGen = $pdo->prepare($sqlUpdateGeneral);
            $stmtGen->execute([
                ':target_status' => $targetStatus,
                ':ident'         => $identCode
            ]);

            // 3. Chèn bản ghi snapshot mới vào bobin_history
            $sqlInsertHistory = "INSERT INTO bobin_history (
                                    bobin_key_code, bobin_identification_code, bobin_size, bobin_type, 
                                    extrusion_employee, extrusion_check, extrusion_machine, rack, products, material_lot, print_lot, length_m, 
                                    shift, extrusion_date, finish_time, visual_inspection, 
                                    winding_machine, winding_employee, bobin_current_status, previous_status, winding_note, flow_test_result,
                                    extrusion_chart_image, qc_visual_image, winding_chart_image,
                                    update_history, updated_time
                                )
                                SELECT 
                                    bobin_key_code, bobin_identification_code, bobin_size, bobin_type, 
                                    extrusion_employee, extrusion_check, extrusion_machine, rack, products, material_lot, print_lot, length_m, 
                                    shift, extrusion_date, finish_time, visual_inspection, 
                                    winding_machine, winding_employee, bobin_current_status, previous_status, winding_note, flow_test_result,
                                    extrusion_chart_image, qc_visual_image, winding_chart_image,
                                    update_history, updated_time
                                FROM bobin_list_detail
                                WHERE bobin_key_code = :key 
                                  AND bobin_identification_code = :ident";
            $stmtHist = $pdo->prepare($sqlInsertHistory);
            $stmtHist->execute([
                ':key'   => $actualKeyCode,
                ':ident' => $identCode
            ]);

            $pdo->commit();

            return [
                'bobin_identification_code' => $identCode,
                'bobin_key_code'            => $actualKeyCode,
                'restored_status'           => $targetStatus,
                'previous_status'           => 'Pending_Cancellation'
            ];
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("DB restoreBobin Error: " . $e->getMessage());
            throw new Exception("Lỗi Database: " . $e->getMessage());
        }
    }



    #region Ext DELETE
    public function extrusionDeleteBobin(BobinEntity $entity, ?array $updateRecord = null): bool
    {
        $pdo = $this->db->pdo();
        try {
            $pdo->beginTransaction();

            $entity->previousStatus = 'Busy_Unchecked';
            $entity->currentStatus = 'Pending_Cancellation';

            $this->extDeleteBobinDetail($pdo, $entity, $updateRecord);
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

    private function extDeleteBobinDetail(PDO $pdo, BobinEntity $entity, ?array $updateRecord = null): void
    {
        $currStmt = $pdo->prepare("SELECT update_history FROM bobin_list_detail WHERE bobin_identification_code = :ident LIMIT 1");
        $currStmt->execute([':ident' => $entity->identificationCode]);
        $rawHist = $currStmt->fetchColumn();
        $histArr = (!empty($rawHist)) ? (json_decode($rawHist, true) ?: []) : [];
        if ($updateRecord !== null) {
            $histArr[] = $updateRecord;
        }

        $sql = "UPDATE bobin_list_detail SET
                    extrusion_employee = :extrusionemp,
                    previous_status = 'Busy_Unchecked',
                    bobin_current_status = :status,
                    update_history = :uhist,
                    updated_time = :updated
                WHERE bobin_identification_code = :ident";

        $params = $this->getBobinExtDeleteParams($entity);
        $params[':status'] = 'Pending_Cancellation';
        $params[':uhist']  = !empty($histArr) ? $this->json_utf8($histArr) : null;
        $params[':updated'] = $entity->updatedTime->format('Y-m-d H:i:s');

        if (!empty($entity->bobinKeyCode)) {
            $sql .= " AND bobin_key_code = :key";
            $params[':key'] = $entity->bobinKeyCode;
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
    }

    private function extDeleteBobinGeneral(PDO $pdo, BobinEntity $entity): void
    {
        $sql = "UPDATE bobin_list_general SET
                    previous_status = 'Busy_Unchecked',
                    bobin_current_status = :status,
                    updated_time = NOW()
                WHERE bobin_identification_code = :ident";

        $params = [
            ':ident' => $entity->identificationCode,
            ':status' => 'Pending_Cancellation'
        ];

        if (!empty($entity->bobinKeyCode)) {
            $sql .= " AND bobin_key_code = :key";
            $params[':key'] = $entity->bobinKeyCode;
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
    }

    private function extInsertBobinHistoryAfterDelete(PDO $pdo, BobinEntity $entity): void
    {
        $sql = "INSERT INTO bobin_history (
                    bobin_key_code, bobin_identification_code, bobin_size, bobin_type,
                    extrusion_employee, extrusion_check, extrusion_machine, rack, products, material_lot, print_lot, length_m,
                    shift, extrusion_date, finish_time, visual_inspection, winding_machine,
                    winding_employee, flow_test_result, bobin_current_status, previous_status, winding_note,
                    extrusion_chart_image, qc_visual_image, winding_chart_image,
                    update_history, updated_time
                ) SELECT 
                    bobin_key_code, bobin_identification_code, bobin_size, bobin_type, 
                    extrusion_employee, extrusion_check, extrusion_machine, rack, products, material_lot, print_lot, length_m, 
                    shift, extrusion_date, finish_time, visual_inspection, 
                    winding_machine, winding_employee, flow_test_result, bobin_current_status, previous_status, winding_note,
                    extrusion_chart_image, qc_visual_image, winding_chart_image,
                    update_history, updated_time
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
                    previous_status = 'Busy_Unchecked',
                    bobin_current_status = :status,
                    visual_inspection = :visual,
                    qc_visual_image = :qc_img,
                    updated_time = :updated
                WHERE bobin_key_code = :key
                    AND bobin_identification_code = :ident";

            $stmt = $pdo->prepare($sqlDetail);

            $stmt->execute([
                ':status'   => $entity->currentStatus,
                ':visual'   => $this->json_utf8($entity->visualInspection),
                ':qc_img'   => $entity->qc_visual_image,
                ':updated'  => $entity->updatedTime->format('Y-m-d H:i:s'),
                ':key'      => $entity->bobinKeyCode,
                ':ident'    => $entity->identificationCode
            ]);

            $sqlGeneral = "UPDATE bobin_list_general SET
                            previous_status = 'Busy_Unchecked',
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
                                extrusion_employee, extrusion_check, extrusion_machine, rack, products, material_lot, print_lot, length_m, 
                                shift, extrusion_date, finish_time, visual_inspection, 
                                winding_machine, winding_employee, bobin_current_status, previous_status, winding_note, flow_test_result,
                                extrusion_chart_image, qc_visual_image, winding_chart_image,
                                update_history, updated_time
                            )
                            SELECT 
                                bobin_key_code, bobin_identification_code, bobin_size, bobin_type, 
                                extrusion_employee, extrusion_check, extrusion_machine, rack, products, material_lot, print_lot, length_m, 
                                shift, extrusion_date, finish_time, visual_inspection, 
                                winding_machine, winding_employee, bobin_current_status, previous_status, winding_note, flow_test_result,
                                extrusion_chart_image, qc_visual_image, winding_chart_image,
                                update_history, updated_time
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
                    previous_status = 'Busy_Unchecked',
                    bobin_current_status = :status,
                    visual_inspection = :visual,
                    qc_visual_image = :qc_img,
                    updated_time = NOW()
                WHERE bobin_key_code = :key
                    AND bobin_identification_code = :ident";

            $stmt = $pdo->prepare($sqlDetail);
            $stmt->execute([
                ':type'     => $newType,
                ':status'   => $entity->currentStatus,
                ':visual'   => $this->json_utf8($entity->visualInspection),
                ':qc_img'   => $entity->qc_visual_image,
                ':key'      => $entity->bobinKeyCode,
                ':ident'    => $entity->identificationCode
            ]);

            // 2. Cập nhật bảng Tổng hợp (bobin_list_general)
            $sqlGeneral = "UPDATE bobin_list_general SET
                            bobin_type = :type,
                            previous_status = 'Busy_Unchecked',
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
                                extrusion_employee, extrusion_check, extrusion_machine, rack, products, material_lot, print_lot, length_m, 
                                shift, extrusion_date, finish_time, visual_inspection, 
                                winding_machine, winding_employee, bobin_current_status, previous_status, winding_note, flow_test_result,
                                extrusion_chart_image, qc_visual_image, winding_chart_image,
                                update_history, updated_time
                            )
                            SELECT 
                                bobin_key_code, bobin_identification_code, bobin_size, :type, 
                                extrusion_employee, extrusion_check, extrusion_machine, rack, products, material_lot, print_lot, length_m, 
                                shift, extrusion_date, finish_time, visual_inspection, 
                                winding_machine, winding_employee, bobin_current_status, previous_status, winding_note, flow_test_result,
                                extrusion_chart_image, qc_visual_image, winding_chart_image,
                                update_history, NOW()
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
    public function qcCancelBobin(BobinEntity $entity, ?array $updateRecord = null): bool
    {
        $pdo = $this->db->pdo();

        try {
            $pdo->beginTransaction();

            // Lấy lịch sử cập nhật hiện tại và nối thêm bản ghi 'Đưa vào danh sách chờ hủy'
            $currStmt = $pdo->prepare("SELECT update_history FROM bobin_list_detail WHERE bobin_key_code = :key AND bobin_identification_code = :ident LIMIT 1");
            $currStmt->execute([
                ':key'   => $entity->bobinKeyCode,
                ':ident' => $entity->identificationCode
            ]);
            $rawHist = $currStmt->fetchColumn();
            $histArr = (!empty($rawHist)) ? (json_decode($rawHist, true) ?: []) : [];
            if ($updateRecord !== null) {
                $histArr[] = $updateRecord;
            }
            $histJson = !empty($histArr) ? $this->json_utf8($histArr) : null;

            // TASK-032: Nếu được đưa vào danh sách hủy bởi nhóm QC, khi restore sẽ trả về trạng thái chưa QC (Busy_Unchecked)
            $sql = "UPDATE bobin_list_detail SET
                previous_status = 'Busy_Unchecked',
                bobin_current_status = :status,
                visual_inspection = :visual,
                qc_visual_image = COALESCE(:qc_img, qc_visual_image),
                update_history = :uhist,
                updated_time = :updated
            WHERE bobin_key_code = :key
                AND bobin_identification_code = :ident";

            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                ':status'   => $entity->currentStatus,
                ':uhist'    => $histJson,
                ':updated'  => $entity->updatedTime->format('Y-m-d H:i:s'),
                ':key'      => $entity->bobinKeyCode,
                ':visual'   => $this->json_utf8($entity->visualInspection),
                ':qc_img'   => $entity->qc_visual_image,
                ':ident'    => $entity->identificationCode
            ]);

            $sqlGeneral = "UPDATE bobin_list_general SET
                            previous_status = 'Busy_Unchecked',
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
                                extrusion_employee, extrusion_check, extrusion_machine, rack, products, material_lot, print_lot, length_m, 
                                shift, extrusion_date, finish_time, visual_inspection, 
                                winding_machine, winding_employee, bobin_current_status, previous_status, winding_note, flow_test_result,
                                extrusion_chart_image, qc_visual_image, winding_chart_image,
                                update_history, updated_time
                            )
                            SELECT 
                                bobin_key_code, bobin_identification_code, bobin_size, bobin_type, 
                                extrusion_employee, extrusion_check, extrusion_machine, rack, products, material_lot, print_lot, length_m, 
                                shift, extrusion_date, finish_time, visual_inspection, 
                                winding_machine, winding_employee, bobin_current_status, previous_status, winding_note, flow_test_result,
                                extrusion_chart_image, qc_visual_image, winding_chart_image,
                                update_history, updated_time
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

            // Lấy trạng thái trước khi cập nhật cuộn (Busy_Checked hoặc Rolled nếu cuộn lần 2+)
            $stmtPrev = $pdo->prepare("SELECT bobin_current_status FROM bobin_list_detail WHERE bobin_key_code = :key AND bobin_identification_code = :ident LIMIT 1");
            $stmtPrev->execute([
                ':key'   => $entity->bobinKeyCode,
                ':ident' => $entity->identificationCode
            ]);
            $prevStatus = $stmtPrev->fetchColumn();
            if (!in_array($prevStatus, ['Busy_Checked', 'Rolled', 'Busy_Unchecked'], true)) {
                $prevStatus = 'Busy_Checked';
            }

            $sql = "UPDATE bobin_list_detail SET
                    previous_status = :prev_status,
                    bobin_current_status = :status,
                    winding_machine = :winding_machine,
                    winding_employee = :winding_employee,
                    winding_note = :winding_note,
                    flow_test_result = :flow_test_result,
                    winding_chart_image = COALESCE(:wnd_img, winding_chart_image),
                    updated_time = :updated
                WHERE bobin_key_code = :key
                    AND bobin_identification_code = :ident";

            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                ':prev_status' => $prevStatus,
                ':status'   => $entity->currentStatus,
                ':winding_machine' => $entity->winding_machine,
                ':winding_employee' => $this->json_utf8($entity->winding_employee),
                ':winding_note' => $entity->winding_note,
                ':flow_test_result' => $entity->flow_test_result,
                ':wnd_img'  => $entity->winding_chart_image,
                ':updated'  => $entity->updatedTime->format('Y-m-d H:i:s'),
                ':key'      => $entity->bobinKeyCode,
                ':ident'    => $entity->identificationCode
            ]);

            $sqlGeneral = "UPDATE bobin_list_general SET
                            previous_status = :prev_status,
                            bobin_current_status = :status,
                            updated_time = NOW()
                       WHERE bobin_key_code = :key
                            AND bobin_identification_code = :ident";

            $stmtGen = $pdo->prepare($sqlGeneral);
            $stmtGen->execute([
                ':prev_status' => $prevStatus,
                ':status'   => $entity->currentStatus,
                ':key'      => $entity->bobinKeyCode,
                ':ident'    => $entity->identificationCode
            ]);

            $sqlHistory = "INSERT INTO bobin_history (
                                bobin_key_code, bobin_identification_code, bobin_size, bobin_type, 
                                extrusion_employee, extrusion_check, extrusion_machine, rack, products, material_lot, print_lot, length_m, 
                                shift, extrusion_date, finish_time, visual_inspection, 
                                winding_machine, winding_employee, bobin_current_status, previous_status, winding_note, flow_test_result,
                                extrusion_chart_image, qc_visual_image, winding_chart_image,
                                update_history, updated_time
                            )
                            SELECT 
                                bobin_key_code, bobin_identification_code, bobin_size, bobin_type, 
                                extrusion_employee, extrusion_check, extrusion_machine, rack, products, material_lot, print_lot, length_m, 
                                shift, extrusion_date, finish_time, visual_inspection, 
                                winding_machine, winding_employee, bobin_current_status, previous_status, winding_note, flow_test_result,
                                extrusion_chart_image, qc_visual_image, winding_chart_image,
                                update_history, updated_time
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
    public function windingCancelBobin(BobinEntity $entity, ?array $updateRecord = null): bool
    {
        $pdo = $this->db->pdo();

        try {
            $pdo->beginTransaction();

            // TASK-032: Lưu lại trạng thái trước khi nhóm Cuộn báo hủy (Busy_Checked hoặc Rolled) và update_history
            $stmtPrev = $pdo->prepare("SELECT bobin_current_status, update_history FROM bobin_list_detail WHERE bobin_key_code = :key AND bobin_identification_code = :ident LIMIT 1");
            $stmtPrev->execute([
                ':key'   => $entity->bobinKeyCode,
                ':ident' => $entity->identificationCode
            ]);
            $prevRow = $stmtPrev->fetch(PDO::FETCH_ASSOC);
            $prevStatus = $prevRow['bobin_current_status'] ?? 'Busy_Checked';
            if (!in_array($prevStatus, ['Busy_Checked', 'Rolled'], true)) {
                $prevStatus = 'Busy_Checked';
            }

            $rawHist = $prevRow['update_history'] ?? null;
            $histArr = (!empty($rawHist)) ? (json_decode($rawHist, true) ?: []) : [];
            if ($updateRecord !== null) {
                $histArr[] = $updateRecord;
            }
            $histJson = !empty($histArr) ? $this->json_utf8($histArr) : null;

            $sql = "UPDATE bobin_list_detail SET
                previous_status = :prev_status,
                bobin_current_status = :status,
                update_history = :uhist,
                updated_time = :updated,
                winding_machine = :winding_machine,
                winding_employee = :winding_employee,
                flow_test_result = :flow_test_result,
                winding_note = :winding_note,
                winding_chart_image = COALESCE(:wnd_img, winding_chart_image)
            WHERE bobin_key_code = :key
                AND bobin_identification_code = :ident";

            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                ':prev_status' => $prevStatus,
                ':status'   => $entity->currentStatus,
                ':uhist'    => $histJson,
                ':updated'  => $entity->updatedTime->format('Y-m-d H:i:s'),
                ':key'      => $entity->bobinKeyCode,
                ':ident'    => $entity->identificationCode,
                ':winding_machine' => $entity->winding_machine,
                ':winding_employee' => $this->json_utf8($entity->winding_employee),
                ':flow_test_result' => $entity->flow_test_result,
                ':winding_note' => $entity->winding_note ?? "Không có ghi chú (Yêu cầu hủy phía cuộn)",
                ':wnd_img'  => $entity->winding_chart_image
            ]);

            $sqlGeneral = "UPDATE bobin_list_general SET
                            previous_status = :prev_status,
                            bobin_current_status = :status,
                            updated_time = NOW()
                       WHERE bobin_key_code = :key
                            AND bobin_identification_code = :ident";

            $stmtGen = $pdo->prepare($sqlGeneral);

            $stmtGen->execute([
                ':prev_status' => $prevStatus,
                ':status'   => $entity->currentStatus,
                ':key'      => $entity->bobinKeyCode,
                ':ident'    => $entity->identificationCode
            ]);
            $sqlHistory = "INSERT INTO bobin_history (
                                bobin_key_code, bobin_identification_code, bobin_size, bobin_type, 
                                extrusion_employee, extrusion_check, extrusion_machine, rack, products, material_lot, print_lot, length_m, 
                                shift, extrusion_date, finish_time, visual_inspection, 
                                winding_machine, winding_employee, bobin_current_status, previous_status, winding_note, flow_test_result,
                                extrusion_chart_image, qc_visual_image, winding_chart_image,
                                update_history, updated_time
                            )
                            SELECT 
                                bobin_key_code, bobin_identification_code, bobin_size, bobin_type, 
                                extrusion_employee, extrusion_check, extrusion_machine, rack, products, material_lot, print_lot, length_m, 
                                shift, extrusion_date, finish_time, visual_inspection, 
                                winding_machine, winding_employee, bobin_current_status, previous_status, winding_note, flow_test_result,
                                extrusion_chart_image, qc_visual_image, winding_chart_image,
                                update_history, updated_time
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
            $extComplete = $this->getExtrusionCompleteCondition();
            $sql = "SELECT COUNT(*) FROM bobin_list_detail WHERE bobin_current_status = 'Pending_Cancellation' AND {$extComplete}";
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
                    bobin_type LIKE :kw8 OR
                    JSON_EXTRACT(winding_employee, '$.employee_code') LIKE :kw9 OR
                    JSON_EXTRACT(winding_employee, '$.employee_name') LIKE :kw10 OR
                    JSON_EXTRACT(visual_inspection, '$.inspector_code') LIKE :kw11 OR
                    JSON_EXTRACT(visual_inspection, '$.inspector_name') LIKE :kw12 OR
                    extrusion_machine LIKE :kw13
                )";
                for ($i = 1; $i <= 13; $i++) {
                    $params[":kw$i"] = $searchStr;
                }
            }

            // Lọc theo khoảng thời gian nếu có (hỗ trợ trang Đã hủy / Lọc thời gian)
            if (!empty($filters['from_date'])) {
                $sql .= " AND DATE(updated_time) >= :from_date";
                $params[':from_date'] = substr($filters['from_date'], 0, 10);
            }
            if (!empty($filters['to_date'])) {
                $sql .= " AND DATE(updated_time) <= :to_date";
                $params[':to_date'] = substr($filters['to_date'], 0, 10);
            }

            // Task-046: Lọc mã nhân viên (Đùn/Cuộn/QC), mã sản phẩm, tình trạng ngoại quan QC
            $this->appendAdvancedManagementFilters($sql, $params, $filters, '');

            // Lọc trạng thái (Chỉ lấy những Bobin có đầy đủ thông tin nhập từ nhóm đùn)
            $extComplete = $this->getExtrusionCompleteCondition();
            $sql .= " AND ($extComplete)";
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
            if (!empty($bobin['bobin_identification_code']) && strcasecmp((string)$bobin['bobin_identification_code'], $identificationCode) === 0) {
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
            if (!empty($bobin['bobin_identification_code']) && strcasecmp((string)$bobin['bobin_identification_code'], $identificationCode) === 0) {
                return $bobin['bobin_size'];
            }
        }
        return null;
    }
    #endregion
}
