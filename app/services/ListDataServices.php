<?php
require_once ROOT_PATH . '/app/repositories/ListDataRepository.php';

class ListDataServices
{
    private ListDataRepository $listDataRepo;

    public function __construct()
    {
        $this->listDataRepo = new ListDataRepository();
    }

    public function getListData(bool $isFull): ListDataEntity
    {
        try {
            $entity = $this->listDataRepo->getListData($isFull);

            if (empty($entity)) {
                throw new Exception('No data found');
            }

            return $entity;
        } catch (Exception $e) {
            error_log('Error fetching list data: ' . $e->getMessage());
            throw $e;
        }
    }
}