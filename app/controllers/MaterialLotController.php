<?php
require_once ROOT_PATH . '/app/core/Controller.php';
require_once ROOT_PATH . '/app/models/MaterialLotModel.php';
class MaterialLotController extends Controller
{
    private MaterialLotModel $model;

    public function __construct()
    {
        date_default_timezone_set('Asia/Ho_Chi_Minh');
        // $this->model = new MaterialLotModel();
    }

    // GET: Lấy danh sách
    public function getListMaterialLot()
    {
        try {
            $data = $this->model->getAllMaterialLot();
            $this->json(['success' => true, 'data' => $data]);
        } catch (Exception $e) {
            $this->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // POST: Tạo mới
    public function registNewMaterialLot()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return $this->json(['message' => 'Method not allowed'], 405);
        $input = $this->getInputData();

        try {
            if (empty($input['lot'])) throw new Exception("Mã Lot không được để trống.");

            if ($this->model->checkLotExists($input['lot'])) {
                throw new Exception("Lot '{$input['lot']}' đã tồn tại.");
            }

            $id = $this->model->registMaterialLot(trim($input['lot']));
            $this->json(['success' => true, 'message' => 'Tạo Lot thành công', 'id' => $id]);
        } catch (Exception $e) {
            $this->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    // PUT/POST: Cập nhật
    public function updateMaterialLot()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return $this->json(['message' => 'Method not allowed'], 405);
        $input = $this->getInputData();

        try {
            if (empty($input['id'])) throw new Exception("Thiếu ID.");
            if (empty($input['lot'])) throw new Exception("Mã Lot không được để trống.");

            if ($this->model->checkLotExists($input['lot'], $input['id'])) {
                throw new Exception("Lot '{$input['lot']}' đã tồn tại ở bản ghi khác.");
            }

            $this->model->updateMaterialLot($input['id'], trim($input['lot']));
            $this->json(['success' => true, 'message' => 'Cập nhật thành công']);
        } catch (Exception $e) {
            $this->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    // DELETE: Xóa
    public function deleteMaterialLot()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return $this->json(['message' => 'Method not allowed'], 405);
        $input = $this->getInputData();

        try {
            if (empty($input['id'])) throw new Exception("Thiếu ID cần xóa.");
            $this->model->deleteMaterialLot($input['id']);
            $this->json(['success' => true, 'message' => 'Đã xóa Lot.']);
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
