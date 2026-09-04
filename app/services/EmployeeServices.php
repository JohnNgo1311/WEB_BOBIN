<?php
require_once ROOT_PATH . '/app/repositories/EmployeeRepository.php';

class EmployeeServices
{
    private EmployeeRepository $employeeRepo;

    public function __construct()
    {
        $this->employeeRepo = new EmployeeRepository();
    }

    public function getListEmployee(): array
    {
        try {
            $response = $this->employeeRepo->getListEmployee();
            if (empty($response)) {
                throw new Exception('No data found');
            }
            return $response;
        } catch (Exception $e) {
            error_log('Error fetching list data: ' . $e->getMessage());
            throw $e;
        }
    }
}
