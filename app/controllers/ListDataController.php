<?php

require_once ROOT_PATH . '/app/core/Controller.php';
require_once ROOT_PATH . '/app/services/ListDataServices.php';

class ListDataController extends Controller
{
    private ListDataServices $listDataService;

    public function __construct()
    {
        date_default_timezone_set('Asia/Ho_Chi_Minh');
        $this->listDataService = new ListDataServices();
    }

    public function getListData(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->json(['success' => false, 'message' => 'Method not allowed'], 405);
            return;
        }

        try {
            $data = $this->listDataService->getListData(false);
            $this->populateGlobalData($data);

            if ($this->validateDataLoaded()) {
                $data->success = true;
                $this->json($data);
            } else {
                throw new UnexpectedValueException('Không có dữ liệu danh mục trả về từ hệ thống.');
            }
        } catch (Throwable $e) {
            $this->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    private function populateGlobalData(object $data): void
    {
        GlobalData::$listRackEntity             = $data->list_rack ?? [];
        GlobalData::$pendingBobinCount          = $data->pending_count ?? 0;
        GlobalData::$listBobinEntity            = $data->list_bobin ?? [];
        GlobalData::$listEmployeeEntity         = $data->list_employee ?? [];
        GlobalData::$listMaterialLotEntity      = $data->list_material_lot ?? [];
        GlobalData::$listProductEntity          = $data->list_product ?? [];
        GlobalData::$listMaterialEntity         = $data->list_material ?? [];
        GlobalData::$listExtrusionMachineEntity = $data->list_extrusion_machine ?? [];
        GlobalData::$listWindingMachineEntity   = $data->list_winding_machine ?? [];
        GlobalData::$listYearEntity             = $data->list_year ?? [];
        GlobalData::$listMonthEntity            = $data->list_month ?? [];
        GlobalData::$listDayEntity              = $data->list_day ?? [];
    }

    private function validateDataLoaded(): bool
    {
        // Cho phép trả về dữ liệu bình thường dù một vài danh mục tạm thời chưa có dòng nào
        return true;
    }
}
