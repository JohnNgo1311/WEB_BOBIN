<?php
// File: app/core/GlobalData.php
require_once __DIR__ . '/../models/ListDataModel.php'; 
require_once __DIR__ . '/../entities/BobinEntity.php'; 

class GlobalData {
    public static array $listData = [];
    public static string $userRole = '';
    public static string $userName = '';

    public static int $bobin_id = 0;
    public static int $employee_id = 0;
    public static int $material_lot_id = 0;
    public static int $material_lot = 0;
    public static int $product_id = 0;
    public static array $listBobinModel = [];
    public static array $listEmployeeModel = [];
    public static array $listMaterialLotModel = [];
    public static array $listProductModel = [];
    public static array $listDefectModel = [];
    public static array $listVisualInspectionModel = [];


    public static array $listBobinEntity = [];
    public static array $listEmployeeEntity = [];
    public static array $listMaterialLotEntity = [];
    public static array $listMaterialEntity = [];
    public static array $listProductEntity = [];
    public static array $listDeFectEntity = [];
    public static array $listVisualInspectionEntity = [];
    public static array $listExtrusionMachineEntity = [];
    public static array $listWindingMachineEntity = [];

    public static array $listYearEntity = [];
    public static array $listMonthEntity = [];
    public static array $listDayEntity = [];

    public static BobinEntity $bobinEntity;



    // public static function get() {
    //     // Chỉ gọi Database 1 lần duy nhất trong đời request
    //     if (self::$data === null) {
    //         $model = new ListDataModel(); 
    //         self::$data = $model->getListData();
    //     }
    //     return self::$data;
    // }
}