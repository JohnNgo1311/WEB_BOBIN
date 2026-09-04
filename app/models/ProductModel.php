<?php
require_once __DIR__ . '/../core/Database.php';

class ProductModel
{
    private $db;
    public int $id;
    public string $production_order_code;
    public string $product_code;
    public string $description;
    public DateTime $updated_time;
    public function __construct(int $id , string $production_order_code, string $product_code, string $description, DateTime $updated_time)
    {
        $this->id = $id;
        $this->production_order_code = $production_order_code;
        $this->product_code = $product_code;
        $this->description = $description;
        $this->updated_time = $updated_time;
        $this->db = Database::getInstance();
    }
  public function getAllProducts() {
        $stmt = $this->db->pdo()->prepare("SELECT * FROM products ORDER BY id DESC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById($id) {
        $stmt = $this->db->pdo()->prepare("SELECT * FROM products WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Kiểm tra trùng lặp (Dùng chung cho cả PO Code và Product Code)
    public function checkExists($field, $value, $excludeId = null) {
        // Cho phép danh sách field an toàn để tránh SQL Injection
        $allowedFields = ['production_order_code', 'product_code'];
        if (!in_array($field, $allowedFields)) return false;

        $sql = "SELECT COUNT(*) FROM products WHERE $field = :value";
        $params = ['value' => $value];

        if ($excludeId) {
            $sql .= " AND id != :id";
            $params['id'] = $excludeId;
        }

        $stmt = $this->db->pdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn() > 0;
    }

    public function registProduct($data) {
        $sql = "INSERT INTO products (production_order_code, product_code, description) 
                VALUES (:po_code, :prod_code, :desc)";
        $stmt = $this->db->pdo()->prepare($sql);
        $stmt->execute([
            'po_code'   => $data['production_order_code'],
            'prod_code' => $data['product_code'],
            'desc'      => $data['description'] ?? null
        ]);
        return $this->db->pdo()->lastInsertId();
    }

    public function updateProduct($id, $data) {
        $sql = "UPDATE products 
                SET production_order_code = :po_code, 
                    product_code = :prod_code, 
                    description = :desc,
                    updated_time = NOW()
                WHERE id = :id";
        $stmt = $this->db->pdo()->prepare($sql);
        return $stmt->execute([
            'po_code'   => $data['production_order_code'],
            'prod_code' => $data['product_code'],
            'desc'      => $data['description'] ?? null,
            'id'        => $id
        ]);
    }

    public function deleteProduct($id) {
        $stmt = $this->db->pdo()->prepare("DELETE FROM products WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }
    private function json_utf8($data): string
    {
        return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

    }
}