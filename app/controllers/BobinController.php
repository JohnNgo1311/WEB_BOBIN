<?php
//! Controller
require_once ROOT_PATH . '/app/core/Controller.php';
//! DTOs
require_once ROOT_PATH . '/app/dtos/Bobin/BobinCreateDTO.php';
require_once ROOT_PATH . '/app/dtos/Bobin/BobinExtUpdateDTO.php';
require_once ROOT_PATH . '/app/dtos/Bobin/BobinExtDeleteDTO.php';
require_once ROOT_PATH . '/app/dtos/Bobin/BobinGetListDTO.php';
require_once ROOT_PATH . '/app/dtos/Bobin/BobinQCUpdateDTO.php';
require_once ROOT_PATH . '/app/dtos/Bobin/BobinUpdateWindingDTO.php';
require_once ROOT_PATH . '/app/dtos/Bobin/BobinWindingCancelDTO.php';
require_once ROOT_PATH . '/app/dtos/Bobin/BobinQCCancelDTO.php';
require_once ROOT_PATH . '/app/dtos/Bobin/BobinDeleteDTO.php';
require_once ROOT_PATH . '/app/dtos/Bobin/BobinGetSpecificDTO.php';
//! Services
require_once ROOT_PATH . '/app/services/BobinServices.php';

/*==========================================*/
//! BobinController – CHỈ LÀM 3 VIỆC
//? JS → Controller → Repository → trả JSON
//? ✔ Nhận $_POST / $_GET
//? ✔ Validate tối thiểu
//? ✔ Tạo BobinDTO
//? ✔ Gọi Repository
// ❌ KHÔNG xử lý DateTime
// ❌ KHÔNG chạm SQL
// ❌ KHÔNG new Entity phức tạp
/*==========================================*/

class BobinController extends Controller
{
    private BobinServices $bobinService;
    public function __construct()
    {
        // Cài đặt múi giờ (nếu chưa có trong config chung)
        date_default_timezone_set('Asia/Ho_Chi_Minh');
        $this->bobinService = new BobinServices();
    }
    // private BobinModel $model;

    #region VIEW RENDERING
    public function index()
    {
        $role = $_SESSION['user']['role'] ?? null;
        switch ($role) {
            case 'extrusion':
                $this->extrusion();
                break;
            case 'qc':
                $this->qc();
                break;
            case 'winding':
                $this->winding();
                break;
            case 'manager':
                $this->manager();
                break;
            case 'admin':
                $this->admin();
                break;
            default:
                $this->view('403View');
        }
    }

    private function extrusion()
    {
        require_once "../app/views/extrusionView.php";
    }

    private function qc()
    {
        // Sử dụng header Location để chuyển hướng
        // Lưu ý: Nếu dự án của bạn nằm trong thư mục WEB_BOBIN, hãy thêm nó vào đường dẫn
        header('Location: /WEB_BOBIN/public/index.php?url=bobin/listBobinView_QC');
        exit; // Bắt buộc phải có exit để dừng script ngay lập tức
    }

    private function winding()
    {
        // Sử dụng header Location để chuyển hướng
        // Lưu ý: Nếu dự án của bạn nằm trong thư mục WEB_BOBIN, hãy thêm nó vào đường dẫn
        header('Location: /WEB_BOBIN/public/index.php?url=bobin/listBobinView_Winding');
        exit; // Bắt buộc phải có exit để dừng script ngay lập tức
    }

    private function manager()
    {
        require_once "../app/views/managerView.php";
    }
    private function admin()
    {
        require_once "../app/views/adminView.php";
    }
    public function createBobinView()
    {
        $this->view("createBobinView");
    }
    #endregion


    /** ================= API ================= */

    #region LIST
    //! GET
    public function listBobinHistoryView()
    {
        // 1. Chỉ chấp nhận GET
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
        }

        try {
            $dto = BobinGetListDTO::fromRequest($_GET);

            // Lấy danh sách Bobin đã filter theo Status
            $bobins = $this->bobinService->getBobinsHistory($dto) ?? [];
            if (empty($bobins)) {
                $bobins = [];
            }

            // Lấy số liệu thống kê độc lập (Không bị ảnh hưởng bởi Status)
            $statusCounts = $this->bobinService->getBobinHistoryStats($dto);
        } catch (Throwable $e) {
            throw new Exception($e->getMessage(), $e->getCode(), $e);
        }

        $this->view('listBobinHistoryView', data: [
            'bobins'       => $bobins,
            'statusCounts' => $statusCounts, // <--- Bổ sung truyền data xuống view
            'success'      => true,
        ]);
    }
    public function listBobinDetailView()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
        }

        try {
            $dto = BobinGetListDTO::fromRequest($_GET);
            $bobins = $this->bobinService->getDetailBobins($dto) ?? [];
            $totalRecords = $this->bobinService->countDetailBobins($dto);
            $totalPages = (int)ceil($totalRecords / $dto->limit);

            // Thống kê theo bộ lọc Size và Type đã chọn
            $statusCounts = $this->bobinService->getBobinStatusStats($dto);
        } catch (Throwable $e) {
            throw new Exception($e->getMessage(), $e->getCode(), $e);
        }

        $this->view('listBobinDetailView', data: [
            'bobins'       => $bobins,
            'statusCounts' => $statusCounts,
            'pagination'   => [
                'currentPage'  => $dto->page,
                'totalPages'   => $totalPages,
                'totalRecords' => $totalRecords,
                'limit'        => $dto->limit
            ]
        ]);
    }
    public function listBobinView_QC()
    {
        //TODO 1. Chỉ chấp nhận GET
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
        }

        try {
            $dto = BobinGetListDTO::fromRequest($_GET);
            $bobins = $this->bobinService->getDetailBobinsForQC($dto) ?? [];
            if (empty($bobins)) {
                $bobins = [];
            } else {
            }
        } catch (Throwable $e) {
            throw new Exception($e->getMessage(), $e->getCode(), $e);
        }
        $this->view('qcView', data: [
            'bobins'  => $bobins,
        ]);
    }
    public function listPendingCancellationView()
    {
        //TODO 1. Chỉ chấp nhận GET
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
        }

        try {
            $dto = BobinGetListDTO::fromRequest($_GET);
            $bobins = $this->bobinService->getDetailBobinsForPendingCancellation($dto) ?? [];
            if (empty($bobins)) {
                $bobins = [];
            } else {
            }
        } catch (Throwable $e) {
            throw new Exception($e->getMessage(), $e->getCode(), $e);
        }
        $this->view('listPendingCancellationView', data: [
            'bobins'  => $bobins,
        ]);
    }
    public function listBobinView_Winding()
    {
        //TODO 1. Chỉ chấp nhận GET
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
        }

        try {

            $dto = BobinGetListDTO::fromRequest($_GET);

            $bobins = $this->bobinService->getDetailBobinsForWinding($dto) ?? [];

            if (empty($bobins)) {
                $bobins = [];
            } else {
            }
        } catch (Throwable $e) {
            throw new Exception($e->getMessage(), $e->getCode(), $e);
        }
        $this->view('windingView', data: [
            'bobins'  => $bobins,
        ]);
    }

    public function extrusionEditBobinView()
    {
        //TODO 1. Chỉ chấp nhận GET
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
        }

        try {

            $dto = BobinGetListDTO::fromRequest($_GET);
            $bobins = $this->bobinService->getListDetailBobinsForEditting($dto) ?? [];

            if (empty($bobins)) {
                $bobins = [];
            } else {
            }
        } catch (Throwable $e) {
            throw new Exception($e->getMessage(), $e->getCode(), $e);
        }
        $this->view('extrusionEditBobinView', data: [
            'bobins'  => $bobins,
        ]);
    }
    #endregion

    public function getSpecificBobin()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
            return;
        }

        try {
            $dto = BobinGetSpecificDTO::fromRequest($_GET);
            $this->validBobin($dto);
            $bobins = $this->bobinService->getSpecificBobin($dto);
            $this->jsonResponse([
                'success' => true,
                'data' => $bobins
            ], 200);
        } catch (Throwable $e) {
            $this->jsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    #region EXTRUSION

    public function createBobin(): void
    {
        //TODO 1. Chỉ chấp nhận POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
        }
        try {
            header('Content-Type: application/json; charset=utf-8');
            //TODO 2. Chuyển đổi dữ liệu thô thành DTO
            $dto = BobinCreateDTO::fromRequest($_POST);
            $this->validFormExtrusion($dto);
            //TODO 3. Gọi Service xử lý
            $entity = $this->bobinService->createBobin($dto);

            //TODO 4. Trả về kết quả thành công
            $this->json([
                'success' => true,
                'message' =>  "Tạo mới chu kỳ - Cập nhật thành công Bobin {$entity->identificationCode}!",
                'bobin_identification_code' => $entity->identificationCode,
                'bobin_key_code' => $entity->bobinKeyCode,
            ]);
        } catch (Throwable $e) {
            $this->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
        exit;
    }

    public function extrusionUpdateBobin(): void
    {
        //TODO 1. Chỉ chấp nhận POST
        if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
            $this->jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
        }
        try {
            header('Content-Type: application/json; charset=utf-8');
            //TODO 2. Chuyển đổi dữ liệu thô thành DTO
            $input = json_decode(file_get_contents('php://input'), true) ?? [];

            $dto = BobinExtUpdateDTO::fromRequest($input);
            $this->validFormExtrusionEdit($dto);
            //TODO 3. Gọi Service xử lý
            $entity = $this->bobinService->extUpdateBobin($dto);

            //TODO 4. Trả về kết quả thành công
            $this->json([
                'success' => true,
                'message' =>  "Cập nhật thành công Bobin {$entity->identificationCode}!",
                'bobin_identification_code' => $entity->identificationCode,
            ]);
        } catch (Throwable $e) {
            $this->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
        exit;
    }


    public function extrusionDeleteBobin(): void
    {
        //TODO 1. Chỉ chấp nhận POST
        if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
            $this->jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
        }
        try {
            header('Content-Type: application/json; charset=utf-8');
            //TODO 2. Chuyển đổi dữ liệu thô thành DTO
            $input = json_decode(file_get_contents('php://input'), true) ?? [];

            $dto = BobinExtDeleteDTO::fromRequest($input);
            $this->validFormExtrusionDelete($dto);
            //TODO 3. Gọi Service xử lý
            $entity = $this->bobinService->extDeleteBobin($dto);

            //TODO 4. Trả về kết quả thành công
            $this->json([
                'success' => true,
                'message' =>  "Hủy thành công Bobin {$entity->identificationCode}!",
                'bobin_identification_code' => $entity->identificationCode,
            ]);
        } catch (Throwable $e) {
            $this->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
        exit;
    }


    //! PUT
    #endregion
    #region QC
    public function updateQCBobin(): void
    {
        //TODO 1. Chỉ chấp nhận PUT
        if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
            $this->jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
        }
        try {
            header('Content-Type: application/json; charset=utf-8');
            //TODO 2. Chuyển đổi dữ liệu thô thành DTO
            $input = json_decode(file_get_contents('php://input'), true) ?? [];
            $dto = BobinQCUpdateDTO::fromRequest($input);
            $this->validFormQC($dto);
            //TODO 3. Gọi Service xử lý
            $entity = $this->bobinService->updateQCBobin($dto);
            //TODO 4. Trả về kết quả thành công
            $this->json([
                'success' => true,
                'message' =>  "Kiểm tra QC - Cập nhật thành công Bobin {$entity->identificationCode}!",
                'bobin_identification_code' => $entity->identificationCode,
            ]);
        } catch (Throwable $e) {
            $this->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
        exit;
    }

    public function qcCancelBobin(): void
    {
        //TODO 1. Chỉ chấp nhận PUT
        if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
            $this->jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
        }
        try {
            header('Content-Type: application/json; charset=utf-8');
            //TODO 2. Chuyển đổi dữ liệu thô thành DTO
            $input = json_decode(file_get_contents('php://input'), true) ?? [];
            $dto = BobinQCCancelDTO::fromRequest($input);
            $this->validFormQC_Cancel($dto);
            //TODO 3. Gọi Service xử lý
            $entity = $this->bobinService->qcCancelBobin($dto);

            //TODO 4. Trả về kết quả thành công
            $this->json([
                'success' => true,
                'message' =>  "Kiểm tra QC - Hủy thành công Bobin {$entity->identificationCode}!",
                'bobin_identification_code' => $entity->identificationCode,
            ]);
        } catch (Throwable $e) {
            $this->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
        exit;
    }
    #endregion
    #region WINDING
    public function updateWindingBobin(): void
    {
        //TODO 1. Chỉ chấp nhận PUT
        if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
            $this->jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
        }
        try {
            header('Content-Type: application/json; charset=utf-8');
            //TODO 2. Chuyển đổi dữ liệu thô thành DTO
            $input = json_decode(file_get_contents('php://input'), true) ?? [];
            $dto = BobinUpdateWindingDTO::fromRequest($input);
            $this->validFormWinding($dto);
            //TODO 3. Gọi Service xử lý
            $entity = $this->bobinService->updateWindingBobin($dto);

            //TODO 4. Trả về kết quả thành công
            $this->json([
                'success' => true,
                'message' =>  "Hoàn thành chu kỳ - Cập nhật thành công Bobin {$entity->identificationCode}!",
                'bobin_identification_code' => $entity->identificationCode,
                'bobin_key_code' => $entity->bobinKeyCode,
            ]);
        } catch (Throwable $e) {
            $this->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
        exit;
    }
    public function windingCancelBobin(): void
    {
        //TODO 1. Chỉ chấp nhận PUT
        if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
            $this->jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
        }
        try {
            header('Content-Type: application/json; charset=utf-8');
            //TODO 2. Chuyển đổi dữ liệu thô thành DTO
            $input = json_decode(file_get_contents('php://input'), true) ?? [];
            $dto = BobinWindingCancelDTO::fromRequest($input);
            $this->validFormWinding_Cancel($dto);
            //TODO 3. Gọi Service xử lý
            $entity = $this->bobinService->windingCancelBobin($dto);

            //TODO 4. Trả về kết quả thành công
            $this->json([
                'success' => true,
                'message' =>  "Hủy thành công Bobin {$entity->identificationCode}!",
                'bobin_identification_code' => $entity->identificationCode,
                'bobin_key_code' => $entity->bobinKeyCode,
            ]);
        } catch (Throwable $e) {
            $this->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
        exit;
    }
    #endregion

    //! Đưa BOBIN về trạng thái đã hủy (Canceled) - Xóa mềm
    public function deleteBobin()
    {   //TODO 1. Chỉ chấp nhận PUT
        if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
            $this->jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
        }
        try {
            header('Content-Type: application/json; charset=utf-8');
            //TODO 2. Chuyển đổi dữ liệu thô thành DTO
            $input = json_decode(file_get_contents('php://input'), true) ?? [];
            $dto = BobinDeleteDTO::fromRequest($input);
            $this->validFormDelete($dto);
            //TODO 3. Gọi Service xử lý
            $entity = $this->bobinService->deleteBobin($dto);

            //TODO 4. Trả về kết quả thành công
            $this->json([
                'success' => true,
                'message' =>  "Hoàn tất hủy thành công Bobin {$entity->identificationCode}!",
                'bobin_identification_code' => $entity->identificationCode,
            ]);
        } catch (Throwable $e) {
            $this->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
        exit;
    }

    #region EXPORT EXCEL
    /* ================= EXCEL ================= */
    //! Xuất file Excel (CSV)
    //? Xuất Excel danh sách lịch sử Bobin
    public function exportHistoryExcel()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
        }

        try {
            $dto = BobinGetListDTO::fromRequest($_GET);
            $bobins = $this->bobinService->getBobinsHistory($dto) ?? [];

            if (empty($bobins)) {
                $bobins = [];
            } else {
                $filename = "Bobin_History_List_" . date('Y-m-d_H_i') . ".csv";
                $this->outputCsvFile($filename, $bobins);
            }
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            exit;
        }
    }

    //? Xuất Excel danh sách Bobin ngoài line hiện tại
    public function exportDetailExcel()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
        }

        try {
            $dto = BobinGetListDTO::fromRequest($_GET);
            $bobins = $this->bobinService->getDetailBobins($dto) ?? [];

            if (empty($bobins)) {
                $bobins = [];
            } else {
                $filename = "Bobin_Detail_List_" . date('Y-m-d_H_i') . ".csv";
                $this->outputCsvFile($filename, $bobins);
            }
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            exit;
        }
    }

    private function outputCsvFile(string $filename, array $bobins): void
    {
        if (ob_get_level()) {
            ob_end_clean();
        }

        header('Content-Type: text/csv; charset=utf-8');
        header("Content-Disposition: attachment; filename=\"{$filename}\"");
        header('Pragma: public');
        header('Expires: 0');

        $output = fopen('php://output', 'w');
        fputs($output, "\xEF\xBB\xBF");

        $headers = [
            'Bobin Key ID',
            'Bobin ID',
            'Kích thước Bobin',
            'Loại Bobin',
            'Mã Chỉ thị sản xuất',
            'Mã Sản phẩm',
            'Mã Nhân viên',
            'Tên Nhân viên',
            'Ca làm việc',
            'Lot vật liệu',
            'Lot in',
            'Chiều dài Bobin (m)',
            'Ngày đùn',
            'Thời gian hoàn thành cuộn',
            'Mã nhân viên QC',
            'Tên nhân viên QC',
            'Gel',
            'Dị vật',
            'Chất lượng màu',
            'Chất lượng mực in',
            'Ghi chú QC',
            'Mã máy cuộn',
            'Mã nhân viên cuộn',
            'Tên nhân viên cuộn',
            'Ghi chú cuộn',
            'Thời gian cập nhật gần nhất',
            'Trạng thái hiện tại'
        ];
        fputcsv($output, $headers);

        foreach ($bobins as $row) {
            $rowData = $this->prepareRowData($row);
            fputcsv($output, $rowData);
        }

        fclose($output);
        exit;
    }

    private function prepareRowData(array $row): array
    {
        $viRaw = $row['visual_inspection'] ?? '{}';
        $productsRaw = $row['products'] ?? '{}';
        $extrusionEmployeeRaw = $row['extrusion_employee'] ?? '{}';
        $materialLotRaw = $row['material_lot'] ?? '{}';
        $windingEmployeeRaw = $row['winding_employee'] ?? '{}';


        $vi = $this->jsonDecode($viRaw);
        $products = $this->jsonDecode($productsRaw);
        $employee = $this->jsonDecode($extrusionEmployeeRaw);
        $materialLot = $this->jsonDecode($materialLotRaw);
        $windingEmployee = $this->jsonDecode($windingEmployeeRaw);

        $defects = $vi['defects'] ?? [];
        $gel = $defects['gel'] ?? false;
        $colorIssue = $defects['color_issue'] ?? false;
        $foreignObject = $defects['foreign_object'] ?? false;
        $printQuality = $defects['print_quality'] ?? false;

        return [
            $row['bobin_key_code'] ?? '',
            $row['bobin_identification_code'] ?? '',
            $row['bobin_size'] ?? 'Chưa cập nhật',
            $row['bobin_type'] ?? 'Chưa cập nhật',
            $products['production_order_code'] ?? 'Chưa cập nhật',
            $products['product_code'] ?? 'Chưa cập nhật',
            $employee['employee_code'] ?? 'Chưa cập nhật',
            $employee['employee_name'] ?? 'Chưa cập nhật',
            $row['shift'] ?? 'Hành chính',
            $materialLot['lot'] ?? 'Chưa cập nhật',
            $row['print_lot'] ?? 'Chưa cập nhật',

            $row['length_m'] ?? '0',
            $row['extrusion_date'] ?? '',
            $row['finish_time'] ?? '',
            $vi['inspector_code'] ?? 'Chưa cập nhật ',
            $vi['inspector_name'] ?? 'Chưa cập nhật',
            $gel ? 'NG' : 'OK',
            $foreignObject ? 'NG' : 'OK',
            $colorIssue ? 'NG' : 'OK',
            $printQuality ? 'NG' : 'OK',
            $defects['note'] ?? 'Chưa cập nhật',
            $row['winding_machine'] ?? 'Chưa cập nhật',
            $windingEmployee['employee_code'] ?? 'Chưa cập nhật',
            $windingEmployee['employee_name'] ?? 'Chưa cập nhật',
            $row['winding_note'] ?? 'Chưa cập nhật',
            $row['updated_time'] ?? '',
            $row['bobin_current_status'] ?? ''
        ];
    }
    #endregion

    #region JSON 
    private function jsonDecode(string $json): array
    {
        if (empty($json) || !is_string($json)) {
            return [];
        }
        $decoded = json_decode($json, true);
        return json_last_error() === JSON_ERROR_NONE && is_array($decoded) ? $decoded : [];
    }
    private function jsonResponse(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    #region VALIDATION
    private function validFilter(BobinGetListDTO $dto): void
    {

        if ($dto->toDate < $dto->fromDate) {
            throw new Exception('Ngày kết thúc phải lớn sau hoặc bằng ngày bắt đầu');
        }
    }
    private function validFormExtrusionDelete(BobinExtDeleteDTO $dto): void
    {
        if (empty($dto->bobin_identification_code)) {
            throw new Exception('Mã định danh Bobin không được để trống');
        }

        if (empty($dto->extrusion_employee_code) || $dto->extrusion_employee_code === "Chưa cập nhật") {
            throw new Exception('Vui lòng nhập mã nhân viên đùn');
        }
        if (empty($dto->extrusion_employee_name) || $dto->extrusion_employee_name === "Chưa cập nhật") {
            throw new Exception('Vui lòng nhập tên nhân viên đùn');
        }
    }
    private function validFormExtrusionEdit(BobinExtUpdateDTO $dto): void
    {
        if (empty($dto->bobin_identification_code)) {
            throw new Exception('Mã định danh Bobin không được để trống');
        }

        if (empty($dto->extrusion_employee_code) || $dto->extrusion_employee_code === "Chưa cập nhật") {
            throw new Exception('Vui lòng nhập mã nhân viên đùn');
        }
    }

    private function validFormExtrusion(BobinCreateDTO $dto): void
    {
        if (empty($dto->bobin_identification_code)) {
            throw new Exception('Mã định danh Bobin không được để trống');
        }
    }
    private function validBobin(BobinGetSpecificDTO $dto): void
    {
        if (empty($dto->bobin_Identification_Code)) {
            throw new Exception('Mã định danh Bobin không được để trống');
        }
    }
    private function validFormQC(BobinQCUpdateDTO $dto): void
    {
        if (empty($dto->bobin_identification_code)) {
            throw new Exception('Mã định danh Bobin không được để trống');
        }

        if (empty($dto->inspector_code) || $dto->inspector_code === "Chưa cập nhật") {
            throw new Exception('Vui lòng nhập mã nhân viên QC');
        }
        if (empty($dto->inspector_name) || $dto->inspector_name === "Chưa cập nhật") {
            throw new Exception('Vui lòng nhập tên nhân viên QC');
        }
    }
    private function validFormWinding(BobinUpdateWindingDTO $dto): void
    {
        if (empty($dto->bobin_identification_code)) {
            throw new Exception('Mã định danh Bobin không được để trống');
        }

        if (empty($dto->winding_machine)) {
            throw new Exception('Vui lòng nhập tên máy cuộn');
        }
        if (empty($dto->winding_employee_code) || $dto->winding_employee_code === "Chưa cập nhật") {
            throw new Exception('Vui lòng nhập mã nhân viên cuộn');
        }
    }
    private function validFormDelete(BobinDeleteDTO $dto): void
    {
        if (empty($dto->bobin_identification_code)) {
            throw new Exception('Mã định danh Bobin không được để trống');
        }
    }
    private function validFormQC_Cancel(BobinQCCancelDTO $dto): void
    {
        if (empty($dto->bobin_identification_code)) {
            throw new Exception('Mã định danh Bobin không được để trống');
        }

        if (empty($dto->inspector_code) || $dto->inspector_code === "Chưa cập nhật") {
            throw new Exception('Vui lòng nhập mã nhân viên QC');
        }
        if (empty($dto->inspector_name) || $dto->inspector_name === "Chưa cập nhật") {
            throw new Exception('Vui lòng nhập tên nhân viên QC');
        }
        if (empty($dto->defect_note) || $dto->defect_note === "Chưa cập nhật") {
            throw new Exception('Vui lòng nhập lý do hủy Bobin trong phần ghi chú');
        }
    }
    private function validFormWinding_Cancel(BobinWindingCancelDTO $dto): void
    {
        if (empty($dto->bobin_identification_code)) {
            throw new Exception('Mã định danh Bobin không được để trống');
        }

        if (empty($dto->winding_employee_code) || $dto->winding_employee_code === "Chưa cập nhật") {
            throw new Exception('Vui lòng nhập mã nhân viên cuộn');
        }
        if (empty($dto->winding_note) || $dto->winding_note === "Không có ghi chú") {
            throw new Exception('Vui lòng nhập lý do hủy Bobin trong phần ghi chú');
        }
    }
}
