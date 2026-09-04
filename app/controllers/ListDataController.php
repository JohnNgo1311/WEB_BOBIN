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
                throw new UnexpectedValueException('Insufficient data returned from service.');
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
        GlobalData::$listBobinEntity = $data->list_bobin ?? [];
        GlobalData::$listEmployeeEntity = $data->list_employee ?? [];
        GlobalData::$listMaterialLotEntity = $data->list_material_lot ?? [];
        GlobalData::$listProductEntity = $data->list_product ?? [];
        GlobalData::$listMaterialEntity = $data->list_material ?? [];
        GlobalData::$listExtrusionMachineEntity = $data->list_extrusion_machine ?? [];
        GlobalData::$listWindingMachineEntity = $data->list_winding_machine ?? [];
        GlobalData::$listYearEntity = $data->list_year ?? [];
        GlobalData::$listMonthEntity = $data->list_month ?? [];
        GlobalData::$listDayEntity = $data->list_day ?? [];
    }

    private function validateDataLoaded(): bool
    {
        return !empty(GlobalData::$listBobinEntity) &&
            !empty(GlobalData::$listEmployeeEntity) &&
            !empty(GlobalData::$listMaterialLotEntity) &&
            !empty(GlobalData::$listProductEntity) &&
            !empty(GlobalData::$listMaterialEntity) &&
            !empty(GlobalData::$listExtrusionMachineEntity) &&
            !empty(GlobalData::$listWindingMachineEntity) &&
            !empty(GlobalData::$listYearEntity) &&
            !empty(GlobalData::$listMonthEntity) &&
            !empty(GlobalData::$listDayEntity);
        // return true;
    }
}