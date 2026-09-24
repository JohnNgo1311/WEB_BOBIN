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
                $this->manageCapacityView();
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
        // Gọi nạp dữ liệu CSDL để gán giá trị cho GlobalData::$pendingBobinCount
        $listDataRepo = new ListdataRepository();
        $listDataRepo->getListData(false);

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
        header('Location: /WEB_BOBIN/public/index.php?url=bobin/windingView');
        exit; // Bắt buộc phải có exit để dừng script ngay lập tức
    }

    private function manager()
    {
        require_once "../app/views/manageCapacityView.php";
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
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
        }

        $errorMsg = null;
        $bobins = [];
        $statusCounts = [];
        $capacityMap = []; // Thêm biến lưu trữ dung lượng
        try {
            $dto = BobinGetListDTO::fromRequest($_GET);
            $bobins = $this->bobinService->getBobinsHistory($dto) ?? [];
            $statusCounts = $this->bobinService->getBobinHistoryStats($dto);
            $capacityMap = $this->bobinService->getBobinCapacities();
            $racks = $this->bobinService->getAllRacks(); // <-- Thêm lấy danh sách Rack
        } catch (Throwable $e) {
            $errorMsg = $e->getMessage();
        }

        $this->view('listBobinHistoryView', data: [
            'bobins'       => $bobins,
            'statusCounts' => $statusCounts,
            'capacityMap'  => $capacityMap,
            'racks'        => $racks ?? [], // <-- Truyền xuống View
            'error'        => $errorMsg,
            'success'      => empty($errorMsg),
        ]);
    }

    public function listBobinView_Winding()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
        }

        $capacityMap = [];
        try {
            $dto = BobinGetListDTO::fromRequest($_GET);
            $bobins = $this->bobinService->getDetailBobinsForWindingPaginated($dto) ?? [];

            $totalRecords = $this->bobinService->countDetailBobinsForWinding($dto);
            $totalPages = (int)ceil($totalRecords / $dto->limit);

            // Lấy dung lượng động từ cơ sở dữ liệu
            $capacityMap = $this->bobinService->getBobinCapacities();
        } catch (Throwable $e) {
            throw new Exception($e->getMessage(), $e->getCode(), $e);
        }

        $this->view('windingView', data: [
            'bobins'     => $bobins,
            'capacityMap' => $capacityMap, // <-- Truyền xuống View
            'pagination' => [
                'currentPage'  => $dto->page,
                'totalPages'   => $totalPages,
                'totalRecords' => $totalRecords,
                'limit'        => $dto->limit
            ]
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

            $statusCounts = $this->bobinService->getBobinStatusStats($dto);
            $capacityMap = $this->bobinService->getBobinCapacities();

            // Lấy danh sách Rack truyền xuống View
            $racks = $this->bobinService->getAllRacks();
        } catch (Throwable $e) {
            throw new Exception($e->getMessage(), $e->getCode(), $e);
        }

        $this->view('listBobinDetailView', data: [
            'bobins'       => $bobins,
            'statusCounts' => $statusCounts,
            'capacityMap'  => $capacityMap,
            'racks'        => $racks, // <-- Thêm danh sách Racks
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
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
        }

        try {
            $dto = BobinGetListDTO::fromRequest($_GET);
            $bobins = $this->bobinService->getDetailBobinsForQC($dto) ?? [];

            // Lấy số lượng Bobin đang chờ hủy
            $pendingCount = $this->bobinService->countPendingBobins();
        } catch (Throwable $e) {
            throw new Exception($e->getMessage(), $e->getCode(), $e);
        }

        $this->view('qcView', data: [
            'bobins'       => $bobins,
            'pendingCount' => $pendingCount ?? 0,
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
    public function windingView()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
        }

        try {
            $dto = BobinGetListDTO::fromRequest($_GET);
            $bobins = $this->bobinService->getDetailBobinsForWindingPaginated($dto) ?? [];
            $totalRecords = $this->bobinService->countDetailBobinsForWinding($dto);
            $totalPages = (int)ceil($totalRecords / $dto->limit);
            // Lấy số lượng Bobin đang chờ hủy
            $pendingCount = $this->bobinService->countPendingBobins();
        } catch (Throwable $e) {
            throw new Exception($e->getMessage(), $e->getCode(), $e);
        }

        $this->view('windingView', data: [
            'pendingCount' => $pendingCount,
            'bobins'     => $bobins,
            'pagination' => [
                'currentPage'  => $dto->page,
                'totalPages'   => $totalPages,
                'totalRecords' => $totalRecords,
                'limit'        => $dto->limit
            ]
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
    public function updateQCAndChangeType(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
            $this->jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
        }
        try {
            header('Content-Type: application/json; charset=utf-8');
            $input = json_decode(file_get_contents('php://input'), true) ?? [];

            // Gọi Service xử lý
            $entity = $this->bobinService->updateQCAndChangeTypeBobin($input);

            $this->json([
                'success' => true,
                'message' => "Kiểm tra QC và đổi loại thành công cho Bobin {$entity->identificationCode}!"
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
        try {
            $dto = BobinGetListDTO::fromRequest($_REQUEST);

            // Tiếp nhận danh sách bản ghi chọn qua checkbox nếu có
            $selectedIds = [];
            if (!empty($_REQUEST['selected_ids'])) {
                if (is_array($_REQUEST['selected_ids'])) {
                    $selectedIds = $_REQUEST['selected_ids'];
                } else {
                    $selectedIds = explode(',', $_REQUEST['selected_ids']);
                }
                $selectedIds = array_filter(array_map('trim', $selectedIds));
            }

            $bobins = $this->bobinService->getBobinsHistory($dto, $selectedIds) ?? [];

            $filename = "Lich_Su_Bobin_" . date('Y-m-d_H_i') . ".csv";
            $this->outputEnhancedCsvHistory($filename, $bobins);
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            exit;
        }
    }

    private function outputEnhancedCsvHistory(string $filename, array $bobins): void
    {
        if (ob_get_level()) {
            ob_end_clean();
        }

        header('Content-Type: text/csv; charset=utf-8');
        header("Content-Disposition: attachment; filename=\"{$filename}\"");
        header('Pragma: public');
        header('Expires: 0');

        $output = fopen('php://output', 'w');
        fputs($output, "\xEF\xBB\xBF"); // UTF-8 BOM hiển thị tiếng Việt có dấu chuẩn trên Excel

        $headers = [
            'Bobin Key ID',
            'Mã định danh Bobin',
            'Kích thước Bobin',
            'Loại Bobin',
            'Vị trí Rack',
            'Mã Chỉ thị sản xuất',
            'Mã Sản phẩm',
            'Mã NV Đùn',
            'Họ tên NV Đùn',
            'Ca sản xuất',
            'Lot vật liệu',
            'Lot in (Print Lot)',
            'Chiều dài (m)',
            'Ngày đùn',
            'Thời gian hoàn thành cuộn',
            'Đùn - Đường kính',
            'Đùn - Gel',
            'Đùn - Dị vật',
            'Đùn - Màu sắc',
            'Đùn - Chữ in',
            'Mã NV QC',
            'Họ tên NV QC',
            'Thời gian QC',
            'QC - Gel',
            'QC - Dị vật',
            'QC - Màu sắc',
            'QC - Chữ in',
            'Ghi chú QC',
            'Mã máy cuộn',
            'Mã NV cuộn',
            'Họ tên NV cuộn',
            'Kết quả thông khí',
            'Ghi chú cuộn',
            'Thời điểm ghi nhận lịch sử',
            'Trạng thái tại thời điểm ghi nhận'
        ];
        fputcsv($output, $headers);

        $totalLength = 0;
        $statusStats = [
            'Rolled'               => 0,
            'Busy_Unchecked'       => 0,
            'Busy_Checked'         => 0,
            'Pending_Cancellation' => 0,
            'Cancelled'            => 0
        ];

        foreach ($bobins as $row) {
            $totalLength += floatval($row['length_m'] ?? 0);
            $st = $row['bobin_current_status'] ?? 'Unknown';
            if (isset($statusStats[$st])) {
                $statusStats[$st]++;
            }

            $rowData = $this->prepareHistoryRowData($row);
            fputcsv($output, $rowData);
        }

        // BẢNG TỔNG KẾT Ở CUỐI FILE
        fputcsv($output, []);
        fputcsv($output, ['=== BẢNG THỐNG KÊ LỊCH SỬ TỔNG HỢP ===']);
        fputcsv($output, ['Tổng số lượt cập nhật:', count($bobins) . ' lượt']);
        fputcsv($output, ['Tổng chiều dài sản xuất:', number_format($totalLength, 1) . ' mét']);
        fputcsv($output, [
            'Chi tiết trạng thái:',
            "Đã cuộn: {$statusStats['Rolled']} | Đã đùn (Chưa QC): {$statusStats['Busy_Unchecked']} | Đã QC: {$statusStats['Busy_Checked']} | Đã hủy: {$statusStats['Cancelled']}"
        ]);

        fclose($output);
        exit;
    }

    private function prepareHistoryRowData(array $row): array
    {
        $products = $this->jsonDecode($row['products'] ?? '{}');
        $extEmp   = $this->jsonDecode($row['extrusion_employee'] ?? '{}');
        $matLot   = $this->jsonDecode($row['material_lot'] ?? '{}');
        $extCheck = $this->jsonDecode($row['extrusion_check'] ?? '{}');
        $rack     = $this->jsonDecode($row['rack'] ?? '{}');
        $vi       = $this->jsonDecode($row['visual_inspection'] ?? '{}');
        $defects  = $vi['defects'] ?? [];
        $windEmp  = $this->jsonDecode($row['winding_employee'] ?? '{}');

        $statusName = match ($row['bobin_current_status'] ?? '') {
            'Rolled'               => 'Đã cuộn',
            'Busy_Unchecked'       => 'Đã đùn (Chưa QC)',
            'Busy_Checked'         => 'Đã QC',
            'Pending_Cancellation' => 'Chờ hủy',
            'Cancelled'            => 'Đã hủy',
            default                => $row['bobin_current_status'] ?? 'Chưa cập nhật'
        };

        return [
            $row['bobin_key_code'] ?? '',
            $row['bobin_identification_code'] ?? '',
            $row['bobin_size'] ?? 'Chưa cập nhật',
            $row['bobin_type'] ?? 'Chưa cập nhật',
            $rack['code'] ?? 'Chưa cập nhật',
            $products['production_order_code'] ?? 'Chưa cập nhật',
            $products['product_code'] ?? 'Chưa cập nhật',
            $extEmp['employee_code'] ?? 'Chưa cập nhật',
            $extEmp['employee_name'] ?? 'Chưa cập nhật',
            $row['shift'] ?? 'Chưa cập nhật',
            $matLot['lot'] ?? 'Chưa cập nhật',
            $row['print_lot'] ?? 'Chưa cập nhật',
            $row['length_m'] ?? '0',
            $row['extrusion_date'] ?? '',
            $row['finish_time'] ?? '',
            // 5 tiêu chí Đùn check
            ($extCheck['diameter'] ?? true) ? 'OK' : 'NG',
            ($extCheck['gel'] ?? true) ? 'OK' : 'NG',
            ($extCheck['foreign_object'] ?? true) ? 'OK' : 'NG',
            ($extCheck['color'] ?? true) ? 'OK' : 'NG',
            ($extCheck['print'] ?? true) ? 'OK' : 'NG',
            // QC Check
            $vi['inspector_code'] ?? 'Chưa cập nhật',
            $vi['inspector_name'] ?? 'Chưa cập nhật',
            $vi['inspection_time'] ?? 'Chưa cập nhật',
            ($defects['gel'] ?? false) ? 'OK' : 'NG',
            ($defects['foreign_object'] ?? false) ? 'OK' : 'NG',
            ($defects['color_issue'] ?? false) ? 'OK' : 'NG',
            ($defects['print_quality'] ?? false) ? 'OK' : 'NG',
            $defects['note'] ?? 'Chưa cập nhật',
            // Cuộn
            $row['winding_machine'] ?? 'Chưa cập nhật',
            $windEmp['employee_code'] ?? 'Chưa cập nhật',
            $windEmp['employee_name'] ?? 'Chưa cập nhật',
            $row['flow_test_result'] ?? 'Chưa cập nhật',
            $row['winding_note'] ?? 'Chưa cập nhật',
            $row['updated_time'] ?? '',
            $statusName
        ];
    }

    private function prepareRowData(array $row): array
    {
        $products = $this->jsonDecode($row['products'] ?? '{}');
        $extEmp = $this->jsonDecode($row['extrusion_employee'] ?? '{}');
        $matLot = $this->jsonDecode($row['material_lot'] ?? '{}');
        $visual = $this->jsonDecode($row['visual_inspection'] ?? '{}');
        $defects = $visual['defects'] ?? [];
        $windEmp = $this->jsonDecode($row['winding_employee'] ?? '{}');

        $status = match ($row['bobin_current_status'] ?? '') {
            'Rolled' => 'Đã cuộn',
            'Busy_Unchecked' => 'Chưa QC',
            'Busy_Checked' => 'Đã QC',
            'Pending_Cancellation' => 'Chờ hủy',
            'Cancelled' => 'Đã hủy',
            default => $row['bobin_current_status'] ?? 'Chưa cập nhật'
        };

        return [
            $row['bobin_key_code'] ?? '',
            $row['bobin_identification_code'] ?? ($row['bobin_code'] ?? ''),
            $row['bobin_size'] ?? 'Chưa cập nhật',
            $row['bobin_type'] ?? 'Chưa cập nhật',
            $products['production_order_code'] ?? 'Chưa cập nhật',
            $products['product_code'] ?? 'Chưa cập nhật',
            $extEmp['employee_code'] ?? 'Chưa cập nhật',
            $extEmp['employee_name'] ?? 'Chưa cập nhật',
            $row['shift'] ?? 'Chưa cập nhật',
            $matLot['lot'] ?? 'Chưa cập nhật',
            $row['print_lot'] ?? 'Chưa cập nhật',
            $row['length_m'] ?? '0',
            $row['extrusion_date'] ?? '',
            $row['finish_time'] ?? '',
            $visual['inspector_code'] ?? 'Chưa cập nhật',
            $visual['inspector_name'] ?? 'Chưa cập nhật',
            ($defects['gel'] ?? false) ? 'OK' : 'NG',
            ($defects['foreign_object'] ?? false) ? 'OK' : 'NG',
            ($defects['color_issue'] ?? false) ? 'OK' : 'NG',
            ($defects['print_quality'] ?? false) ? 'OK' : 'NG',
            $defects['note'] ?? 'Chưa cập nhật',
            $row['winding_machine'] ?? 'Chưa cập nhật',
            $windEmp['employee_code'] ?? 'Chưa cập nhật',
            $windEmp['employee_name'] ?? 'Chưa cập nhật',
            $row['winding_note'] ?? 'Chưa cập nhật',
            $row['updated_time'] ?? '',
            $status
        ];
    }

    //? Xuất Excel danh sách Bobin ngoài line hiện tại
    public function exportDetailExcel()
    {
        try {
            $dto = BobinGetListDTO::fromRequest($_REQUEST);

            // Kiểm tra xem người dùng có chọn cụ thể danh sách Bobin nào không
            $selectedCodes = [];
            if (!empty($_REQUEST['selected_codes'])) {
                if (is_array($_REQUEST['selected_codes'])) {
                    $selectedCodes = $_REQUEST['selected_codes'];
                } else {
                    $selectedCodes = explode(',', $_REQUEST['selected_codes']);
                }
                $selectedCodes = array_filter(array_map('trim', $selectedCodes));
            }

            // Lấy toàn bộ Bobin theo yêu cầu (không phân trang 50 dòng)
            $bobins = $this->bobinService->getDetailBobinsForExport($dto, $selectedCodes);

            $filename = "Danh_Sach_Bobin_" . date('Y-m-d_H_i') . ".csv";
            $this->outputEnhancedCsvDetail($filename, $bobins);
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            exit;
        }
    }

    private function outputEnhancedCsvDetail(string $filename, array $bobins): void
    {
        if (ob_get_level()) {
            ob_end_clean();
        }

        header('Content-Type: text/csv; charset=utf-8');
        header("Content-Disposition: attachment; filename=\"{$filename}\"");
        header('Pragma: public');
        header('Expires: 0');

        $output = fopen('php://output', 'w');
        fputs($output, "\xEF\xBB\xBF"); // UTF-8 BOM để Excel hiển thị đúng tiếng Việt

        // TIÊU ĐỀ 35 CỘT DỮ LIỆU ĐẦY ĐỦ
        $headers = [
            'Bobin Key ID',
            'Mã định danh Bobin',
            'Kích thước Bobin',
            'Loại Bobin',
            'Vị trí Rack',
            'Mã Chỉ thị sản xuất',
            'Mã Sản phẩm',
            'Mã NV Đùn',
            'Họ tên NV Đùn',
            'Ca sản xuất',
            'Lot vật liệu',
            'Lot in (Print Lot)',
            'Chiều dài (m)',
            'Ngày đùn',
            'Thời gian hoàn thành cuộn',
            'Đùn - Đường kính',
            'Đùn - Gel',
            'Đùn - Dị vật',
            'Đùn - Màu sắc',
            'Đùn - Chữ in',
            'Mã NV QC',
            'Họ tên NV QC',
            'Thời gian QC',
            'QC - Gel',
            'QC - Dị vật',
            'QC - Màu sắc',
            'QC - Chữ in',
            'Ghi chú QC',
            'Mã máy cuộn',
            'Mã NV cuộn',
            'Họ tên NV cuộn',
            'Kết quả thông khí',
            'Ghi chú cuộn',
            'Thời gian cập nhật gần nhất',
            'Trạng thái hiện tại'
        ];
        fputcsv($output, $headers);

        // BIẾN THỐNG KÊ TỔNG KẾT
        $totalLength = 0;
        $statusStats = [
            'Rolled' => 0,
            'Busy_Unchecked' => 0,
            'Busy_Checked' => 0,
            'Pending_Cancellation' => 0,
            'Cancelled' => 0
        ];

        foreach ($bobins as $row) {
            $length = floatval($row['length_m'] ?? 0);
            $totalLength += $length;

            $status = $row['bobin_current_status'] ?? 'Unknown';
            if (isset($statusStats[$status])) {
                $statusStats[$status]++;
            }

            $rowData = $this->prepareDetailRowData($row);
            fputcsv($output, $rowData);
        }

        // // THÊM CÁC DÒNG TỔNG KẾT Ở CUỐI BẢNG
        // fputcsv($output, []); // Dòng trống ngăn cách
        // fputcsv($output, ['=== BẢNG THỐNG KÊ TỔNG HỢP ===']);
        // fputcsv($output, ['Tổng số lượng Bobin:', count($bobins) . ' cuộn']);
        // fputcsv($output, ['Tổng chiều dài sản xuất:', number_format($totalLength, 1) . ' mét']);
        // fputcsv($output, [
        //     'Chi tiết trạng thái:',
        //     "Đã cuộn: {$statusStats['Rolled']} | Chưa QC: {$statusStats['Busy_Unchecked']} | Đã QC: {$statusStats['Busy_Checked']} | Chờ hủy: {$statusStats['Pending_Cancellation']}"
        // ]);

        fclose($output);
        exit;
    }

    private function prepareDetailRowData(array $row): array
    {
        $products = $this->jsonDecode($row['products'] ?? '{}');
        $extEmp   = $this->jsonDecode($row['extrusion_employee'] ?? '{}');
        $matLot   = $this->jsonDecode($row['material_lot'] ?? '{}');
        $extCheck = $this->jsonDecode($row['extrusion_check'] ?? '{}');
        $rack     = $this->jsonDecode($row['rack'] ?? '{}');
        $vi       = $this->jsonDecode($row['visual_inspection'] ?? '{}');
        $defects  = $vi['defects'] ?? [];
        $windEmp  = $this->jsonDecode($row['winding_employee'] ?? '{}');

        $statusName = match ($row['bobin_current_status'] ?? '') {
            'Rolled'               => 'Đã cuộn',
            'Busy_Unchecked'       => 'Chưa QC',
            'Busy_Checked'         => 'Đã QC',
            'Pending_Cancellation' => 'Chờ hủy',
            'Cancelled'            => 'Đã hủy',
            default                => $row['bobin_current_status'] ?? 'Chưa cập nhật'
        };

        return [
            $row['bobin_key_code'] ?? '',
            $row['bobin_identification_code'] ?? '',
            $row['bobin_size'] ?? 'Chưa cập nhật',
            $row['bobin_type'] ?? 'Chưa cập nhật',
            $rack['code'] ?? 'Chưa cập nhật',
            $products['production_order_code'] ?? 'Chưa cập nhật',
            $products['product_code'] ?? 'Chưa cập nhật',
            $extEmp['employee_code'] ?? 'Chưa cập nhật',
            $extEmp['employee_name'] ?? 'Chưa cập nhật',
            $row['shift'] ?? 'Chưa cập nhật',
            $matLot['lot'] ?? 'Chưa cập nhật',
            $row['print_lot'] ?? 'Chưa cập nhật',
            $row['length_m'] ?? '0',
            $row['extrusion_date'] ?? '',
            $row['finish_time'] ?? '',
            // 5 tiêu chí Đùn check
            ($extCheck['diameter'] ?? true) ? 'OK' : 'NG',
            ($extCheck['gel'] ?? true) ? 'OK' : 'NG',
            ($extCheck['foreign_object'] ?? true) ? 'OK' : 'NG',
            ($extCheck['color'] ?? true) ? 'OK' : 'NG',
            ($extCheck['print'] ?? true) ? 'OK' : 'NG',
            // QC Check
            $vi['inspector_code'] ?? 'Chưa cập nhật',
            $vi['inspector_name'] ?? 'Chưa cập nhật',
            $vi['inspection_time'] ?? 'Chưa cập nhật',
            ($defects['gel'] ?? false) ? 'OK' : 'NG',
            ($defects['foreign_object'] ?? false) ? 'OK' : 'NG',
            ($defects['color_issue'] ?? false) ? 'OK' : 'NG',
            ($defects['print_quality'] ?? false) ? 'OK' : 'NG',
            $defects['note'] ?? 'Chưa cập nhật',
            // Cuộn
            $row['winding_machine'] ?? 'Chưa cập nhật',
            $windEmp['employee_code'] ?? 'Chưa cập nhật',
            $windEmp['employee_name'] ?? 'Chưa cập nhật',
            $row['flow_test_result'] ?? 'Chưa cập nhật',
            $row['winding_note'] ?? 'Chưa cập nhật',
            $row['updated_time'] ?? '',
            $statusName
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
        // if (empty($dto->bobin_size)) {
        //     throw new Exception('Kích thước Bobin không được để trống');
        // }
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
            throw new Exception('Vui lòng nhập mã máy cuộn');
        }
        if (strlen($dto->winding_machine) < 4) {
            throw new Exception('Mã máy cuộn không tồn tại trong hệ thống');
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
    // Hiển thị trang quản lý dung lượng Bobin cho Quản lý/Admin
    public function manageCapacityView()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
        }

        try {
            // Lấy danh sách cấu hình dung lượng hiện tại từ DB
            $capacities = $this->bobinService->getBobinCapacities();
        } catch (Throwable $e) {
            $capacities = [];
        }

        $this->view('manageCapacityView', data: [
            'capacities' => $capacities
        ]);
    }

    // API nhận request cập nhật dung lượng từ giao diện
    public function updateCapacityAction(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
        }

        try {
            header('Content-Type: application/json; charset=utf-8');
            $input = json_decode(file_get_contents('php://input'), true) ?? [];

            $sizeName = trim($input['size_name'] ?? '');
            $capacity = (int)($input['capacity'] ?? 0);

            // Gọi Service cập nhật vào database
            $this->bobinService->updateBobinCapacity($sizeName, $capacity);

            $this->json([
                'success' => true,
                'message' => "Cập nhật số lượng cho kích thước [{$sizeName}] thành công!"
            ]);
        } catch (Throwable $e) {
            $this->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
        exit;
    }
}