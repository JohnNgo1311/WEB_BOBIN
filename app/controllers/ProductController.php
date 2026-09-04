<?php
require_once ROOT_PATH . '/app/core/Controller.php';
require_once ROOT_PATH . '/app/models/ProductModel.php';
class ProductController extends Controller
{
    private ProductModel $model;

    public function __construct()
    {
        date_default_timezone_set('Asia/Ho_Chi_Minh');
        // $this->model = new ProductModel();
    }

    // GET
    public function getListProducts()
    {
        try {
            $data = $this->model->getAllProducts();
            $this->json(['success' => true, 'data' => $data]);
        } catch (Exception $e) {
            $this->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // POST: Create
    public function registNewProduct()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return $this->json(['message' => 'Invalid Method'], 405);
        $input = $this->getInputData();

        try {
            // 1. Validate dữ liệu cơ bản
            if (empty($input['production_order_code'])) throw new Exception("Thiếu Mã chỉ thị sản xuất (PO).");
            if (empty($input['product_code'])) throw new Exception("Thiếu Mã sản phẩm.");

            // 2. Kiểm tra trùng lặp (Vì Table có ràng buộc UNIQUE)
            if ($this->model->checkExists('production_order_code', $input['production_order_code'])) {
                throw new Exception("Mã PO '{$input['production_order_code']}' đã tồn tại.");
            }
            if ($this->model->checkExists('product_code', $input['product_code'])) {
                throw new Exception("Mã sản phẩm '{$input['product_code']}' đã tồn tại.");
            }

            // 3. Tạo mới
            $id = $this->model->registProduct([
                'production_order_code' => trim($input['production_order_code']),
                'product_code'          => trim($input['product_code']),
                'description'           => trim($input['description'] ?? '')
            ]);

            $this->json(['success' => true, 'message' => 'Thêm sản phẩm thành công', 'id' => $id]);
        } catch (Exception $e) {
            $this->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    // POST: Update
    public function updateProduct()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return $this->json(['message' => 'Invalid Method'], 405);
        $input = $this->getInputData();

        try {
            if (empty($input['id'])) throw new Exception("Thiếu ID sản phẩm.");
            if (empty($input['production_order_code'])) throw new Exception("Thiếu Mã PO.");
            if (empty($input['product_code'])) throw new Exception("Thiếu Mã sản phẩm.");

            // Kiểm tra trùng lặp (Trừ chính nó ra)
            if ($this->model->checkExists('production_order_code', $input['production_order_code'], $input['id'])) {
                throw new Exception("Mã PO đã tồn tại ở sản phẩm khác.");
            }
            if ($this->model->checkExists('product_code', $input['product_code'], $input['id'])) {
                throw new Exception("Mã sản phẩm đã tồn tại ở sản phẩm khác.");
            }

            $this->model->updateProduct($input['id'], [
                'production_order_code' => trim($input['production_order_code']),
                'product_code'          => trim($input['product_code']),
                'description'           => trim($input['description'] ?? '')
            ]);

            $this->json(['success' => true, 'message' => 'Cập nhật thành công']);
        } catch (Exception $e) {
            $this->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    // POST: Delete
    public function deleteProduct()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return $this->json(['message' => 'Invalid Method'], 405);
        $input = $this->getInputData();

        try {
            if (empty($input['id'])) throw new Exception("Thiếu ID cần xóa.");
            $this->model->deleteProduct($input['id']);
            $this->json(['success' => true, 'message' => 'Xóa sản phẩm thành công']);
        } catch (Exception $e) {
            $this->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    private function getInputData()
    {
        $json = json_decode(file_get_contents('php://input'), true);
        return is_array($json) ? $json : $_POST;
    }
}
