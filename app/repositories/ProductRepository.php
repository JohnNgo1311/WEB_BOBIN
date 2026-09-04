<?php
require_once ROOT_PATH . '/app/entities/ProductEntity.php';
require_once ROOT_PATH . '/app/core/GlobalData.php';
require_once ROOT_PATH . '/app/models/ListDataModel.php';

// Giả định class GlobalData đã được load ở index.php hoặc config.php

class ProductRepository
{
    /**
     * Tìm sản phẩm trong GlobalData dựa trên OrderCode và ProductCode
     */
    public function findByCodes(string $productionOrderCode, string $productCode): ?ProductEntity
    {
        // 1. Chuẩn hóa Input
        $targetPO = trim((string)$productionOrderCode);
        $targetPC = trim((string)$productCode);
        $temp = [];
        // if (empty(GlobalData::$listProductEntity)) {
        //     try {
        //         $model = new ListDataModel(); 
        //         $allData = $model->getListData(); 
        //        if (!empty($allData['list_product'])) {
        //             GlobalData::$listProductEntity = $allData['list_product'];
        //             GlobalData::$listMaterialLotEntity = $allData['list_material_lot'];
        //             GlobalData::$listMaterialEntity = $allData['list_material'];
        //             GlobalData::$listEmployeeEntity = $allData['list_employee'];

        //             $temp = $allData['list_product'];
        //         }else{
        //             echo 'No Product Data in ListData';
        //         }
        //     } catch (Throwable $e) {
        //         error_log("FindByCodes Error: " . $e->getMessage());
        //     }
        // }
        // =================================================================

        // 2. Kiểm tra lại dữ liệu
        $productList = !empty($temp) ? $temp : GlobalData::$listProductEntity;

        // Nếu vẫn rỗng sau khi cố gắng nạp => Trả về null (Không tìm thấy)
        if (empty($productList)) {
            return null;
        }
        // 3. Vòng lặp tìm kiếm (Dùng cú pháp Array an toàn)
        foreach ($productList as $item) {


            // Ép kiểu mảng để tránh lỗi nếu item là object
            $itemArr = (array)$item;

            $itemPO = trim((string)($itemArr['production_order_code'] ?? ''));
            $itemPC = trim((string)($itemArr['product_code'] ?? ''));
            if ($itemPO === $targetPO && $itemPC === $targetPC) {
                // 1. Xử lý DateTime an toàn (Vì class yêu cầu ?DateTime)
                $finalTime = null;
                if (!empty($itemArr['updated_time'])) {
                    try {
                        // Nếu là chuỗi thì convert, nếu đã là object thì giữ nguyên
                        $finalTime = is_string($itemArr['updated_time'])
                            ? new DateTime($itemArr['updated_time'])
                            : $itemArr['updated_time'];
                        // echo "<h1>---final---</h1>";

                    } catch (Exception $e) {
                        $finalTime = new DateTime(); // Fallback nếu ngày tháng sai định dạng
                    }
                }

                // 2. Chuẩn bị các biến Primitive (Ép kiểu cứng để tránh lỗi NULL)
                // Class yêu cầu 'string', nên ta ép (string) để biến null thành ''
                $finalId   = isset($itemArr['id']) ? (int)$itemArr['id'] : null;
                $finalPO   = (string)($itemPO ?? '');
                $finalPC   = (string)($itemPC ?? '');
                $finalDesc = (string)($itemArr['description'] ?? '');
                // if($finalId!== null && $finalId!== null && $finalPC!== null && $finalDesc!==null){  

                // echo "<h1>---Tìm thấy Product trong GlobalData---</h1>";}

                // 3. Khởi tạo Object
                // Dùng tham số theo vị trí (Positional) để đảm bảo an toàn nhất
                return new ProductEntity(
                    $finalId,   // id
                    $finalPO,   // production_order_code
                    $finalPC,   // product_code
                    $finalDesc, // description
                    $finalTime  // updated_time
                );
            }
        }
        return null;
    }
    //     else {
    //         return null;
    //    }
    // }
    //     private function findProductModel(string $productionOrderCode, string $productCode): array
    // {
    //     $productList = GlobalData::$listData['product_list'] ?? [];
    //     foreach ($productList as $prod) {
    //         if ((!empty($prod['production_order_code']) && $prod['production_order_code'] === $productionOrderCode)
    //             && (!empty($prod['product_code']) && $prod['product_code'] === $productCode)) {
    //             return $prod;
    //         }
    //     }

    //     return [
    //         'id' => null,
    //         'production_order_code' => $productionOrderCode ?: 'Chưa cập nhật',
    //         'product_code' => $productCode ?: 'Chưa cập nhật',
    //         'description' => 'Chưa cập nhật',
    //         'updated_time' => (new DateTime())->format('Y-m-d H:i:s')
    //     ];
    // }
}
