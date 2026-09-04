<?php
require_once __DIR__ . '/../core/Database.php';
require_once ROOT_PATH . '/app/models/EmployeeModel.php';
require_once ROOT_PATH . '/app/models/MaterialLotModel.php';
require_once ROOT_PATH . '/app/models/ProductModel.php';
require_once ROOT_PATH . '/app/models/VisualInspectionModel.php';
    class BobinModel
    {
        private $db;
        public ?int $id;
        public string $bobin_key_code;
        public string $bobin_identification_code;
        public string $bobin_type;
        public EmployeeModel $employee;
        public ProductModel $product;
        public MaterialLotModel $material_lot;
        public string $print_lot;
        public float $length_m;
        public string $shift;
        public DateTime $extrusion_date;
        public DateTime $finish_time;
        public string $bobin_current_status;
        public VisualInspectionModel $visual_inspection;
        public DateTime $updated_time;

        public function __construct(
            string $bobin_key_code,
            string $bobin_identification_code,
            string $bobin_type,
            EmployeeModel $employee,
            ProductModel $product,
            MaterialLotModel $material_lot,
            string $print_lot,
            float $length_m,
            string $shift,
            DateTime $extrusion_date,
            DateTime $finish_time,
            string $bobin_current_status,
            VisualInspectionModel $visual_inspection,
            DateTime $updated_time,
            ?int $id = null   // 👈 id optional
        ) {
            $this->id = $id;
            $this->bobin_key_code = $bobin_key_code;
            $this->bobin_identification_code = $bobin_identification_code;
            $this->bobin_type = $bobin_type;
            $this->employee = $employee;
            $this->product = $product;
            $this->material_lot = $material_lot;
            $this->print_lot = $print_lot;
            $this->length_m = $length_m;
            $this->shift = $shift;
            $this->extrusion_date = $extrusion_date;
            $this->finish_time = $finish_time;
            $this->bobin_current_status = $bobin_current_status;
            $this->visual_inspection = $visual_inspection;
            $this->updated_time = $updated_time;
            $this->db = Database::getInstance();
        }
    /*===========================GET=============================*/
    //!GET
    //? GET LIST Detail with Filter (OPTIONAL)
    public function getBobinsHistory(array $filters = [])
    {
        echo "vào B1";
        //TODO B1: Chuẩn bị SQL cơ bản
        // bobin_key_code format: PREFIX_YYYY_MM_DD_HH_MI_SS (e.g. BB2001_2026_01_29_15_30_45)
        // use SUBSTRING_INDEX(..., '_', -6) to extract the last 6 underscore-separated parts and parse full datetime
        $sql = "SELECT * FROM bobin_list_detail 
            WHERE STR_TO_DATE(SUBSTRING_INDEX(bobin_key_code, '_', -6), '%Y_%m_%d_%H_%i_%s')
            BETWEEN :from_date AND :to_date";

        echo "hoàn thành B1";
    
        //TODO B2: Chuẩn bị mảng tham số ban đầu
        $params = [
            ':from_date' => $filters['from_date'],
            ':to_date' => $filters['to_date']
        ];

        //TODO B3.1: Xử lý Keyword (Nối vào WHERE)
        if (!empty($filters['keyword'])) {
            $searchStr = '%' . trim($filters['keyword']) . '%';

        $sql .= " AND (
        bobin_key_code LIKE :kw1 OR  
        employee LIKE :kw2 OR 
        product LIKE :kw3 OR 
        material_lot LIKE :kw4 OR 
        print_lot LIKE :kw5 OR 
      
    )";

            // Phải bind dữ liệu cho từng key riêng biệt
            $params[':kw1'] = $searchStr;
            $params[':kw2'] = $searchStr;
            $params[':kw3'] = $searchStr;
            $params[':kw4'] = $searchStr;
            $params[':kw5'] = $searchStr;
        }

        //TODO B3.2: Xử lý Status (Nối vào WHERE - Phải làm bước này TRƯỚC khi ORDER BY)
        // Lưu ý: Thường ta sẽ bỏ qua nếu status là 'all' hoặc rỗng
        if (!empty($filters['status']) && $filters['status'] !== 'all') {
            $sql .= " AND bobin_current_status = :status";
            $params[':status'] = $filters['status'];
        }

        //TODO B4: Sắp xếp theo thời gian cập nhật mới nhất
        $sql .= " ORDER BY updated_time DESC";

        // Debug: Bỏ comment dòng dưới để xem câu SQL đúng
        // echo "SQL: $sql <br>"; print_r($params); die();

        try {
            //TODO B5: Gọi Prepare
            $stmt = $this->db->pdo()->prepare($sql);

            //TODO B6: Thực thi
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
           
        } catch (PDOException $e) {
            error_log("DB Error: " . $e->getMessage());
            return [];
        }
    }

    //? GET LIST Detail last 7 days
    public function getDetailBobinsLast7Days()
    {
        $pdo = $this->db->pdo();
        try {
            $from = (new DateTime('now'))->modify('-7 days')->format('Y-m-d H:i:s');
            $sql = "
            SELECT *
            FROM bobin_list_detail
            WHERE updated_time >= :from
            ORDER BY updated_time DESC
        ";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':from' => $from]);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("DB Error: " . $e->getMessage());
            return [];
        }
    }
    //? GET specific bobin by code
    public function GetSpecificBobin(string $code)
    {
        $pdo = $this->db->pdo();
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("SELECT * FROM bobin_list_detail WHERE bobin_identification_code = ?");
            $stmt->execute([$code]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
    //? GET general bobins by date range
    public function getGeneralBobinsByDateRange(?string $fromDate = null, ?string $toDate = null)
    {
        $pdo = $this->db->pdo();

        try {
            $sql = "
            SELECT *
            FROM bobin_list_general
            WHERE 1=1
        ";

            $params = [];

            // 👇 lọc theo khoảng ngày từ bobin_key_code
            if ($fromDate && $toDate) {
                $sql .= "
                AND STR_TO_DATE(
                    RIGHT(bobin_key_code, 10),
                    '%Y_%m_%d'
                ) BETWEEN :fromDate AND :toDate
            ";

                $params['fromDate'] = $fromDate;
                $params['toDate'] = $toDate;
            }

            $sql .= " ORDER BY updated_time DESC";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            return false;
        }
    }


    //! POST
    public function registBobin(array $data)
    {
        $pdo = $this->db->pdo();
        try {
            $pdo->beginTransaction();

            //! 1️⃣ Insert vào bobin_list_general (LIST – STATUS)
            $sqlgeneral = "
                INSERT INTO bobin_list_general (
                    bobin_key_code,
                    bobin_identification_code,
                    bobin_current_status
                ) VALUES (?, ?, ?)
            ";
            $stmtList = $pdo->prepare($sqlgeneral);
            $stmtList->execute([
                $data['bobin_key_code'],
                $data['bobin_identification_code'],
                $data['bobin_current_status']
            ]);

            //! 2️⃣ Insert vào bobin_list_detail (DETAIL)
            $sqlInfor = "
                INSERT INTO bobin_list_detail (
                    bobin_key_code,
                    bobin_identification_code,
                    employee_code,
                    employee_name,
                    production_order_code,
                    product_code,
                    material_lot,
                    print_lot,
                    length_m,
                    shift,
                    extrusion_date,
                    finish_time,
                    bobin_current_status,
                    visual_inspection
                ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)
            ";

            $stmtInfor = $pdo->prepare($sqlInfor);
            $stmtInfor->execute([
                $data['bobin_key_code'],
                $data['bobin_identification_code'],
                $data['employee_code'],
                $data['employee_name'],
                $data['production_order_code'],
                $data['product_code'],
                $data['material_lot'],
                $data['print_lot'],
                $data['length_m'],
                $data['shift'],
                $data['extrusion_date'],
                $data['finish_time'],
                $data['bobin_current_status'],
                $this->json_utf8($data['visual_inspection'])
            ]);

            $pdo->commit();
            return true;

        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
    //? Cập nhật Bobin => TaoJ Bobin_key_code
    public function createBobinNewCycle(BobinModel $bobinModel): bool
    {
        $pdo = $this->db->pdo();
        try {
            $sql = "INSERT INTO bobin_list_detail (
                    bobin_key_code,
                    bobin_identification_code,
                    bobin_type,
                    employee,
                    product,
                    material_lot,
                    print_lot,
                    length_m,
                    shift,
                    extrusion_date,
                    finish_time,
                    bobin_current_status,
                    visual_inspection,
                    updated_time
                ) VALUES (
                    :bobin_key_code,
                    :bobin_identification_code,
                    :bobin_type,
                    :employee,
                    :product,
                    :material_lot,
                    :print_lot,
                    :length_m,
                    :shift,
                    :extrusion_date,
                    :finish_time,
                    :bobin_current_status,
                    :visual_inspection,
                    :updated_time
                )";

            $visual = $this->json_utf8($bobinModel->visual_inspection);

            // $employee = json_encode([
            //     'id'   => $bobinModel->employee->id,
            //     'employee_code' => $bobinModel->employee->employee_code,
            //     'employee_name' => $bobinModel->employee->employee_name,
            //     'updated_time'=> $bobinModel->employee->updated_time->format('Y-m-d H:i:s')
            // ], JSON_UNESCAPED_UNICODE);
            // $product = json_encode([
            //     'id'=> $bobinModel->product->id,
            //     'production_order_code'=> $bobinModel->product->production_order_code,
            //     'product_code'=> $bobinModel->product->product_code,
            //     'description'=> $bobinModel->product->description,
            //     'updated_time'=> $bobinModel->product->updated_time->format('Y-m-d H:i:s')
            // ], JSON_UNESCAPED_UNICODE);
            // $materialLot = json_encode([
            //     'id'=> $bobinModel->material_lot->id,
            //     'lot'=> $bobinModel->material_lot->lot,
            //     'updated_time'=> $bobinModel->material_lot->updated_time->format('Y-m-d H:i:s')
            // ], JSON_UNESCAPED_UNICODE);

           $employee = $this->json_utf8($bobinModel->employee);
           $product = $this->json_utf8($bobinModel->product);
           $materialLot = $this->json_utf8($bobinModel->material_lot);

            $params = [
                ':bobin_key_code' => $bobinModel->bobin_key_code,
                ':bobin_identification_code' => $bobinModel->bobin_identification_code ?? 'Chưa cập nhật',
                ':bobin_type' => $bobinModel->bobin_type ?? 'Chưa cập nhật',
                ':employee' => $employee,
                ':product' => $product,
                ':material_lot' => $materialLot,
                ':print_lot' => $bobinModel->print_lot ?? 'Chưa cập nhật',
                ':length_m' => $bobinModel->length_m ?? 0,
                ':shift' => $bobinModel->shift ?? 'Chưa cập nhật',
                ':extrusion_date' => $bobinModel->extrusion_date instanceof DateTime
                ? $bobinModel->extrusion_date->format('Y-m-d H:i:s'): null,
                ':finish_time' => $bobinModel->finish_time instanceof DateTime
                ? $bobinModel->finish_time->format('Y-m-d H:i:s'): null,
                ':bobin_current_status' => $bobinModel->bobin_current_status ?? 'Chưa cập nhật',
                ':visual_inspection' => $visual,
                ':updated_time' => $bobinModel->updated_time->format('Y-m-d H:i:s')
            ];
            try {
                $pdo->beginTransaction();
                $stmt = $pdo->prepare($sql);

                // bind with appropriate types for a bit more safety
                // foreach ($params as $key => $value) {
                //     if (is_int($value)) {
                //         $stmt->bindValue($key, $value, PDO::PARAM_INT);
                //     } else {
                //         $stmt->bindValue($key, $value, PDO::PARAM_STR);
                //     }
                // }

                $ok = $stmt->execute($params);
                $pdo->commit();

                return $ok && $stmt->rowCount() > 0;
            } catch (PDOException $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                throw new Exception('DB Error: ' . $e->getMessage(), (int) $e->getCode(), $e);
            }
        }
        catch (InvalidArgumentException $e) {
            throw $e;
        }
    
    }

    //! PUT
   //? UPDATE bobin => Chưa hoàn thành
    public function updateBobin(string $code, array $data)
{
    $pdo = $this->db->pdo();
    try {
        $pdo->beginTransaction();
        //? Update lên bobin_list_general
        $pdo->prepare("
                UPDATE bobin_list_general
                SET bobin_current_status = :status, updated_time = NOW()
                WHERE bobin_identification_code = :code
            ")->execute([
                'status' => $data['bobin_current_status'],
                'code'   => $code
            ]);
        //? Update lên bobin_list_detail
        $pdo->prepare("
            UPDATE bobin_list_detail
            SET
                employee_code = :employee_code,
                employee_name = :employee_name,
                length_m = :length_m,
                updated_time = NOW()
            WHERE bobin_identification_code = :code
        ")->execute([
            'employee_code' => $data['employee_code'],
            'employee_name' => $data['employee_name'],
            'length_m' => $data['length_m'],
            'code' => $code
        ]);

        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

    //! DELETE 
    public function DeleteBobin(string $code)
    {
        $pdo = $this->db->pdo();
        try {
            $pdo->beginTransaction();
            $pdo->prepare("DELETE FROM bobin_list_detail WHERE bobin_identification_code=?")
                ->execute([$code]);

            $pdo->prepare("DELETE FROM bobin_list_general WHERE bobin_identification_code=?")
                ->execute([$code]);

            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
    public function checkStatus()
    {
        $pdo = $this->db->pdo();
        try {
            $bobinId = $_GET['bobin_key_code'] ?? '';
            if (!$bobinId) throw new Exception('Thiếu mã Bobin Key ID');

            $sql = "SELECT * FROM bobin_list_detail 
                    WHERE bobin_key_code = :bid 
                    ORDER BY updated_time DESC LIMIT 1";
           
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':bid' => $bobinId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
            if ($row) {
                // Decode JSON nếu cần để JS sử dụng
                $row['visual_inspection'] = json_decode($row['visual_inspection'] ?? '{}', true);
                return $row;
            } else {
                return null;
            }
        } catch (Throwable $e) {
            throw $e;
        }
    }
    private function json_utf8($data): string
    {
        return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

    }
  

}