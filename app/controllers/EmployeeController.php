<?php

require_once ROOT_PATH . '/app/core/Controller.php';
require_once ROOT_PATH . '/app/services/EmployeeServices.php';


class EmployeeController extends Controller
{
    private EmployeeServices $EmployeeService;

    public function __construct()
    {
        date_default_timezone_set('Asia/Ho_Chi_Minh');
        $this->EmployeeService = new EmployeeServices();
    }

    public function getListEmployee(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->json(['success' => false, 'message' => 'Method not allowed'], 405);
            return;
        }

        try {

            $data = $this->EmployeeService->getListEmployee();

            $this->populateGlobalData($data);

            if ($this->validateDataLoaded()) {
                $data['success'] = true;
                $this->json($data);
            } else {
                throw new Exception('Insufficient data returned from service.');
            }
        } catch (Throwable $e) {
            $this->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    private function populateGlobalData(array $data): void
    {
        GlobalData::$listEmployeeEntity = $data ?? [];
    }

    private function validateDataLoaded(): bool
    {
        return !empty(GlobalData::$listEmployeeEntity);
    }
}
