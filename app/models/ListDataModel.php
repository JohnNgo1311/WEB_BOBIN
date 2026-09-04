<?php
require_once __DIR__ . '/../core/Database.php';
            // ✅ THÊM DÒNG NÀY (Để nạp class lấy dữ liệu)
require_once ROOT_PATH . '/app/core/GlobalData.php'; 
class ListDataModel
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }
    /**
     * Fetches list data from multiple tables.
     *
     * @return array An associative array containing the lists.
     * @throws Exception if a database error occurs.
     */
    public function getListData(): array
    {
        // It's a best practice to select only the columns you need instead of using '*'.
        // Replace 'col1, col2' with your actual column names for each table.
        
        //! Cấu hình câu truy vấn cho từng bảng cũng như dạng dữ liệu trả về
        $queries = [
            'list_bobin' => "SELECT * FROM bobin_list_general",
            'list_employee' => "SELECT * FROM employee_list",
            'list_material_lot' => "SELECT * FROM material_lot_list",
            'list_product' => "SELECT * FROM product_list",
            'list_extrusion_machine' => "SELECT * FROM extrusion_machine_list",
            'list_material' => "SELECT * FROM material_list",
            'list_year' => "SELECT * FROM year_list",
            'list_month' => "SELECT * FROM month_list",
            'list_day' => "SELECT * FROM day_list",
        ];

        $response = [];
        $pdo = $this->db->pdo();

        try {
            foreach ($queries as $key => $sql) {
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $response[$key] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            GlobalData::$listData = $response; // Lưu dữ liệu vào biến tĩnh 
          
            
            return $response;
        } catch (PDOException $e) {
            // Re-throw the exception to be handled by a controller or a global error handler.
            // This keeps the model focused on data access, not HTTP responses.
            throw new Exception("Database query failed: " . $e->getMessage());
        }
    }
    private function json_utf8($data): string
    {
        return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

    }
}