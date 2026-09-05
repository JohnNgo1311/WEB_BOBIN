<?php
require_once ROOT_PATH . '/app/repositories/BobinRepository.php';
require_once ROOT_PATH . '/app/repositories/ProductRepository.php';     // <--- Load Repo Mới
require_once ROOT_PATH . '/app/repositories/MaterialLotRepository.php'; // <--- Load Repo Mới
require_once ROOT_PATH . '/app/repositories/EmployeeRepository.php';    // <--- Load Repo Mới
require_once ROOT_PATH . '/app/repositories/ListDataRepository.php';    // <--- Load Repo Mới


class BobinServices
{
    private BobinRepository $bobinRepo;
    private ListDataRepository $listRepository;
    private ProductRepository $productRepo;
    private MaterialLotRepository $materialRepo;
    private EmployeeRepository $employeeRepo;

    public function __construct()
    {
        $this->bobinRepo = new BobinRepository();
        $this->listRepository = new ListDataRepository();
        $this->productRepo = new ProductRepository();
        $this->materialRepo = new MaterialLotRepository();
        $this->employeeRepo = new EmployeeRepository();
    }


    #region EXTRUSION
    public function createBobin(BobinCreateDTO $dto): BobinEntity
    {
        $this->listRepository->getListData(isFull: true);

        if (empty($dto->bobin_identification_code)) {
            throw new Exception("Vui lòng nhập Mã định danh Bobin.");
        }

        $entity = $this->buildBaseEntity($dto, 'extCreate');

        // Visual Inspection (chỉ có ở create)
        $entity->visualInspection = new VisualInspectionEntity(
            inspector_code: $dto->inspector_code,
            inspector_name: $dto->inspector_name,
            inspection_time: new DateTime(),
            defects: new DefectEntity(
                gel: false,
                foreign_object: false,
                color_issue: false,
                print_quality: false,
                note: 'Chưa cập nhật'
            )
        );

        $entity->flow_test_result = "Thất bại";
        $entity->winding_employee = null;
        $entity->winding_machine = "Chưa cập nhật";
        $entity->winding_note = "Chưa cập nhật";

        $this->resolveStatus($entity, $dto->bobin_identification_code);

        $this->bobinRepo->createNewBobin($entity);

        return $entity;
    }
    public function extUpdateBobin(BobinExtUpdateDTO $dto): BobinEntity
    {
        $this->listRepository->getListData(isFull: true);

        if (empty($dto->bobin_identification_code)) {
            throw new Exception("Vui lòng nhập Mã định danh Bobin.");
        }
        $entity = $this->buildBaseEntity($dto, 'extUpdate');
        $this->bobinRepo->extrusionUpdateBobin($entity);
        return $entity;
    }
    public function extDeleteBobin(BobinExtDeleteDTO $dto): BobinEntity
    {
        if (empty($dto->bobin_identification_code)) {
            throw new Exception("Vui lòng nhập Mã định danh Bobin.");
        }

        $entity = new BobinEntity();

        $this->listRepository->getListData(true);

        $entity->identificationCode = $dto->bobin_identification_code;

        $entity->bobinKeyCode = $this->resolveKeyCode($dto->bobin_identification_code);

        $entity->extrusion_employee = $this->resolveEmployee(
            $dto->extrusion_employee_code
        );
        $entity->currentStatus = "Pending_Cancellation";
        $this->bobinRepo->extrusionDeleteBobin($entity);
        return $entity;
    }
    private function buildBaseEntity($dto, string $function): BobinEntity
    {
        $entity = new BobinEntity();

        if ($function === 'extUpdate') {
            $entity->bobinKeyCode = $this->resolveKeyCode($dto->bobin_identification_code);
        }
        if ($function === 'extCreate') {
            $entity->bobinKeyCode = $dto->bobin_identification_code . "_" . date('Y_m_d_H_i_s');
        }
        $entity->identificationCode = $dto->bobin_identification_code;
        $entity->size = $this->resolveSize($dto->bobin_identification_code);
        $entity->type = $dto->bobin_type;
        $entity->printLot = $dto->print_lot;
        $entity->length = $dto->length_m;
        $entity->shift = $dto->shift;
        $entity->extrusionDate = $this->parseDate($dto->extrusion_date);
        $entity->finishTime = $this->parseDate($dto->finish_time);
        $entity->product = $this->resolveProduct($dto->production_order_code, $dto->product_code);
        $entity->materialLot = $this->resolveMaterial($dto->material_lot);
        $entity->extrusion_employee = $this->resolveEmployee(
            $dto->extrusion_employee_code
        );
        return $entity;
    }
    private function parseDate($date): DateTime
    {
        try {
            if ($date instanceof DateTime) {
                return $date;
            }

            if (!empty($date) && is_string($date)) {
                return new DateTime($date);
            }
        } catch (Exception $e) {
        }

        return new DateTime();
    }
    private function resolveProduct($productionOrderCode, $productCode)
    {
        $foundProduct = $this->productRepo->findByCodes($productionOrderCode, $productCode);

        if ($foundProduct === null) {
            throw new Exception('Mã sản phẩm không tồn tại trong hệ thống');
        }

        return $foundProduct;
    }


    private function resolveKeyCode($boinIdentificationCode)
    {
        $bobinKeyCode = $this->bobinRepo->findBobinKeyCode($boinIdentificationCode);

        if ($bobinKeyCode === null) {
            throw new Exception('Mã key Bobin không tồn tại trong hệ thống');
        }

        return $bobinKeyCode;
    }

    private function resolveSize($boinIdentificationCode)
    {
        $bobinSize = $this->bobinRepo->findBobinSize($boinIdentificationCode);

        if ($bobinSize === null) {
            throw new Exception('Kích thước Bobin không tồn tại trong hệ thống');
        }

        return $bobinSize;
    }
    private function resolveMaterial($lot)
    {
        $foundMaterial = $this->materialRepo->findByLot($lot);

        if ($foundMaterial === null) {
            throw new Exception('Lot vật liệu không tồn tại trong hệ thống');
        }

        return $foundMaterial;
    }
    private function resolveEmployee($employeeCode)
    {
        $found = $this->employeeRepo->findByCode($employeeCode);

        if ($found === null) {
            throw new Exception('Mã nhân viên không tồn tại trong hệ thống');
        }

        return $found;
    }
    private function resolveStatus(BobinEntity $entity, string $identificationCode): void
    {
        $this->bobinRepo->getSpecificBobin($identificationCode);

        if (GlobalData::$bobinEntity === null) {
            return;
        }

        $status = GlobalData::$bobinEntity->currentStatus;

        switch ($status) {
            case 'Rolled':
                $entity->currentStatus = "Busy_Unchecked";
                break;

            case 'Pending_Cancellation':
                $entity->currentStatus = "Cancelled";
                break;
        }
    }
    #endregion




    #region QC
    public function updateQCBobin(BobinQCUpdateDTO $dto): BobinEntity
    {
        if (empty($dto->bobin_identification_code)) {
            throw new Exception("Mã định danh Bobin không tồn tại.");
        }

        $entity = new BobinEntity();
        $entity->identificationCode = $dto->bobin_identification_code;
        $entity->bobinKeyCode = $dto->bobin_key_code;
        $entity->currentStatus = "Busy_Checked";
        $entity->visualInspection = $this->createVisualInspection(
            $dto->inspector_code,
            $dto->inspector_name,
            $dto->defect_gel,
            $dto->defect_foreign_object,
            $dto->defect_color_issue,
            $dto->defect_print_quality,
            $dto->defect_note
        );

        $this->bobinRepo->updateQCBobinInfor($entity);
        return $entity;
    }
    public function qcCancelBobin(BobinQCCancelDTO $dto): BobinEntity
    {
        if (empty($dto->bobin_identification_code)) {
            throw new Exception("Mã định danh Bobin không tồn tại.");
        }

        $entity = new BobinEntity();
        $entity->identificationCode = $dto->bobin_identification_code;
        $entity->bobinKeyCode = $dto->bobin_key_code;
        $entity->currentStatus = "Pending_Cancellation";
        $entity->visualInspection = $this->createVisualInspection(
            $dto->inspector_code,
            $dto->inspector_name,
            $dto->defect_gel,
            $dto->defect_foreign_object,
            $dto->defect_color_issue,
            $dto->defect_print_quality,
            "Yêu cầu hủy QC: {$dto->defect_note}"
        );

        $this->bobinRepo->qcCancelBobin($entity);
        return $entity;
    }

    private function createVisualInspection(
        string $inspectorCode,
        string $inspectorName,
        bool $gel,
        bool $foreignObject,
        bool $colorIssue,
        bool $printQuality,
        string $note
    ): VisualInspectionEntity {
        return new VisualInspectionEntity(
            inspector_code: $inspectorCode,
            inspector_name: $inspectorName,
            inspection_time: new DateTime(),
            defects: new DefectEntity(
                gel: $gel,
                foreign_object: $foreignObject,
                color_issue: $colorIssue,
                print_quality: $printQuality,
                note: $note
            )
        );
    }
    #endregion
    #region WINDING
    public function updateWindingBobin(BobinUpdateWindingDTO $dto): BobinEntity
    {
        if (empty($dto->bobin_identification_code)) {
            throw new Exception(message: "Mã định danh Bobin không tồn tại.");
        }
        $this->employeeRepo->getListEmployee();
        $entity = new BobinEntity();
        $entity->identificationCode = $dto->bobin_identification_code;
        $entity->bobinKeyCode = $dto->bobin_key_code;

        $entity->currentStatus = "Rolled";

        $entity->winding_machine = $dto->winding_machine;



        $foundwindingEmp = $this->employeeRepo->findByCode($dto->winding_employee_code);
        if ($foundwindingEmp !== null) {
            $entity->winding_employee = $foundwindingEmp;
        } else {
            throw new Exception('Mã nhân viên không tồn tại trong hệ thống');
        }
        $entity->flow_test_result = 'Thành công';

        $entity->winding_note = $dto->winding_note ?? "Không có ghi chú";

        $this->bobinRepo->updateWindingBobinInfor($entity);

        return $entity;
    }

    public function windingCancelBobin(BobinWindingCancelDTO $dto): BobinEntity
    {
        if (empty($dto->bobin_identification_code)) {
            throw new Exception("Mã định danh Bobin không tồn tại.");
        }
        $this->employeeRepo->getListEmployee();

        $entity = new BobinEntity();

        $entity->identificationCode = $dto->bobin_identification_code;
        $entity->bobinKeyCode = $dto->bobin_key_code;

        $entity->currentStatus = "Pending_Cancellation";

        $entity->winding_machine = $dto->winding_machine;


        $foundwindingEmp = $this->employeeRepo->findByCode($dto->winding_employee_code);
        if ($foundwindingEmp !== null) {
            $entity->winding_employee = $foundwindingEmp;
        } else {
            throw new Exception('Mã nhân viên không tồn tại trong hệ thống');
        }
        $entity->winding_note = $dto->winding_note . " (Yêu cầu hủy phía cuộn)";

        if (strpos($dto->winding_note, 'Thông khí') !== false) {
            $entity->flow_test_result = "Thất bại";
        } else {
            $entity->flow_test_result = "Thành công";
        }
        $this->bobinRepo->windingCancelBobin($entity);
        return $entity;
    }
    #endregion

    public function deleteBobin(BobinDeleteDTO $dto): BobinEntity
    {
        if (empty($dto->bobin_identification_code)) {
            throw new Exception("Mã định danh Bobin không tồn tại.");
        }

        $entity = new BobinEntity();

        $entity->identificationCode = $dto->bobin_identification_code;
        $entity->bobinKeyCode = $dto->bobin_key_code;
        $entity->currentStatus = "Cancelled";

        $this->bobinRepo->deleteBobin($entity);
        return $entity;
    }

    #region GET LIST & GET SPECIFIC
    public function getBobinsHistory(BobinGetListDTO $dto): array
    {
        $keywordRaw = $dto->keyword;
        $fromRaw    = $dto->fromDate;
        $toRaw      = $dto->toDate;
        $statusRaw  = $dto->status;

        $isValidDate = function ($d) {
            if (!is_string($d) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $d)) return false;
            [$y, $m, $day] = explode('-', $d);
            return checkdate((int)$m, (int)$day, (int)$y);
        };

        $from    = trim($fromRaw);
        $to      = trim($toRaw);
        $keyword = trim($keywordRaw);
        $status  = trim($statusRaw);

        if ($from !== '' && !$isValidDate($from)) $from = '';
        if ($to !== '' && !$isValidDate($to)) $to = '';

        $filters = [
            'keyword'    => mb_substr($keyword, 0, 255),
            'from_date'  => $from,
            'to_date'    => $to,
            'status'     => mb_substr($status, 0, 50),
            'bobin_size' => trim($dto->bobinSize ?? 'all'), // Đã thêm
            'bobin_type' => trim($dto->bobinType ?? 'all'), // Đã thêm
        ];

        $listBobin = [];
        try {
            if ($filters['from_date'] !== '' && $filters['to_date'] !== '') {
                if ($filters['from_date'] > $filters['to_date']) {
                    throw new Exception('Ngày bắt đầu không được sau ngày kết thúc');
                } else {
                    $filters['from_date'] = "{$filters['from_date']} 00:00:00";
                    $filters['to_date']   = "{$filters['to_date']} 23:59:59";
                    $listBobin = $this->bobinRepo->getBobinsHistory($filters);
                }
            } else {
                $fromDefault = date('Y-m-d', strtotime('-7 days'));
                $toDefault = date('Y-m-d');

                $filters['from_date'] = "{$fromDefault} 00:00:00";
                $filters['to_date']   = "{$toDefault} 23:59:59";
                $listBobin =  $this->bobinRepo->getBobinsHistory($filters);
            }
        } catch (Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode(), $e);
        }
        return $listBobin;
    }

    public function getBobinHistoryStats(BobinGetListDTO $dto): array
    {
        try {
            $fromRaw = $dto->fromDate ?? '';
            $toRaw   = $dto->toDate ?? '';
            $keyword = trim($dto->keyword ?? '');

            $isValidDate = function ($d) {
                if (!is_string($d) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $d)) return false;
                [$y, $m, $day] = explode('-', $d);
                return checkdate((int)$m, (int)$day, (int)$y);
            };

            $from = trim($fromRaw);
            $to   = trim($toRaw);
            if ($from !== '' && !$isValidDate($from)) $from = '';
            if ($to !== '' && !$isValidDate($to)) $to = '';

            $filters = [
                'keyword'    => mb_substr($keyword, 0, 255),
                'from_date'  => $from,
                'to_date'    => $to,
                'bobin_size' => trim($dto->bobinSize ?? 'all'), // Đã thêm
                'bobin_type' => trim($dto->bobinType ?? 'all'), // Đã thêm
            ];

            if ($filters['from_date'] !== '' && $filters['to_date'] !== '') {
                $filters['from_date'] = "{$filters['from_date']} 00:00:00";
                $filters['to_date']   = "{$filters['to_date']} 23:59:59";
            } else {
                $fromDefault = date('Y-m-d', strtotime('-7 days'));
                $toDefault = date('Y-m-d');
                $filters['from_date'] = "{$fromDefault} 00:00:00";
                $filters['to_date']   = "{$toDefault} 23:59:59";
            }

            return $this->bobinRepo->getBobinsHistoryStats($filters);
        } catch (Exception $e) {
            throw new Exception($e->getMessage(), (int)$e->getCode(), $e);
        }
    }
    public function getListDetailBobinsForEditting(BobinGetListDTO $dto): array
    {
        try {
            // 1. Chỉ lấy và xử lý Keyword và Status 
            $keywordRaw = $dto->keyword ?? '';
            $statusRaw  = $dto->status;
            $keyword    = trim($keywordRaw);
            $status     = trim($statusRaw);

            // 2. Tạo mảng filters chỉ chứa keyword và status
            $filters = [
                'keyword' => mb_substr($keyword, 0, 255),
                'status'  => $status,
            ];

            return $this->bobinRepo->getListDetailBobinsForEditting($filters);
        } catch (Exception $e) {
            // Log lỗi và ném ra ngoại lệ để Controller xử lý
            throw new Exception($e->getMessage(), (int)$e->getCode(), $e);
        }
    }
    public function getBobinStatusStats(BobinGetListDTO $dto): array
    {
        try {
            $filters = [
                'bobin_size' => trim($dto->bobinSize ?? 'all'),
                'bobin_type' => trim($dto->bobinType ?? 'all'),
            ];
            return $this->bobinRepo->getBobinStatusStats($filters);
        } catch (Exception $e) {
            throw new Exception($e->getMessage(), (int)$e->getCode(), $e);
        }
    }

    public function getDetailBobins(BobinGetListDTO $dto): array
    {
        try {
            $filters = [
                'keyword'    => mb_substr(trim($dto->keyword ?? ''), 0, 255),
                'status'     => trim($dto->status ?? 'all'),
                'bobin_size' => trim($dto->bobinSize ?? 'all'),
                'bobin_type' => trim($dto->bobinType ?? 'all'),
            ];
            return $this->bobinRepo->getBobinListDetail($filters, $dto->page, $dto->limit);
        } catch (Exception $e) {
            throw new Exception($e->getMessage(), (int)$e->getCode(), $e);
        }
    }

    public function countDetailBobins(BobinGetListDTO $dto): int
    {
        try {
            $filters = [
                'keyword'    => mb_substr(trim($dto->keyword ?? ''), 0, 255),
                'status'     => trim($dto->status ?? 'all'),
                'bobin_size' => trim($dto->bobinSize ?? 'all'),
                'bobin_type' => trim($dto->bobinType ?? 'all'),
            ];
            return $this->bobinRepo->countBobinListDetail($filters);
        } catch (Exception $e) {
            throw new Exception($e->getMessage(), (int)$e->getCode(), $e);
        }
    }

    public function getDetailBobinsForQC(BobinGetListDTO $dto): array
    {
        try {
            // 1. Chỉ lấy và xử lý Keyword (Vì Date và Status đã bị loại bỏ/cố định trong Repo)
            $keywordRaw = $dto->keyword ?? '';
            $keyword    = trim($keywordRaw);

            // 2. Tạo mảng filters chỉ chứa keyword
            $filters = [
                'keyword' => mb_substr($keyword, 0, 255),
            ];

            // 3. Gọi hàm Repository mới (getDetailBobinsFORQC)
            // Hàm này đã mặc định lấy status = 'Busy_Unchecked' và bỏ qua ngày tháng
            return $this->bobinRepo->getDetailBobinsForQC($filters);
        } catch (Exception $e) {
            // Log lỗi và ném ra ngoại lệ để Controller xử lý
            throw new Exception($e->getMessage(), (int)$e->getCode(), $e);
        }
    }

    public function getDetailBobinsForPendingCancellation(BobinGetListDTO $dto): array
    {
        try {
            // 1. Chỉ lấy và xử lý Keyword (Vì Date và Status đã bị loại bỏ/cố định trong Repo)
            $keywordRaw = $dto->keyword ?? '';
            $keyword    = trim($keywordRaw);

            // 2. Tạo mảng filters chỉ chứa keyword
            $filters = [
                'keyword' => mb_substr($keyword, 0, 255),
            ];

            // 3. Gọi hàm Repository mới (getDetailBobinsFORPendingCancellation)
            // Hàm này đã mặc định lấy status = 'Busy_Unchecked' và bỏ qua ngày tháng
            return $this->bobinRepo->getDetailBobinsForPendingCancellation($filters);
        } catch (Exception $e) {
            // Log lỗi và ném ra ngoại lệ để Controller xử lý
            throw new Exception($e->getMessage(), (int)$e->getCode(), $e);
        }
    }
    public function getDetailBobinsForWinding(BobinGetListDTO $dto): array
    {
        try {
            // 1. Chỉ lấy và xử lý Keyword
            $keywordRaw = $dto->keyword ?? '';
            $keyword    = trim($keywordRaw);

            // 2. Tạo mảng filters chỉ chứa keyword
            $filters = [
                'keyword' => mb_substr($keyword, 0, 255),
            ];

            // 3. Gọi hàm Repository mới (getDetailBobinsFORWinding)
            // Hàm này đã mặc định lấy status = 'Busy_Unchecked' và "Rolled" và bỏ qua ngày tháng
            return $this->bobinRepo->getDetailBobinsForWinding($filters);
        } catch (Exception $e) {
            // Log lỗi và ném ra ngoại lệ để Controller xử lý
            throw new Exception($e->getMessage(), (int)$e->getCode(), $e);
        }
    }

    public function getSpecificBobin(BobinGetSpecificDTO $dto): ?BobinEntity
    {
        try {
            $entity = new BobinEntity();
            $result  = $this->bobinRepo->getSpecificBobin($dto->bobin_Identification_Code);
            if ($result !== null) {
                $entity = GlobalData::$bobinEntity;
                return $entity;
            } else {
                return null; // Hoặc có thể ném ra Exception nếu muốn
            }
        } catch (Exception $e) {
            // Log lỗi và ném ra ngoại lệ để Controller xử lý
            throw new Exception($e->getMessage(), (int)$e->getCode(), $e);
        }
    }

    #endregion
    #region MAPPING

    #endregion
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
}
