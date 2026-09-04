<?php
require_once ROOT_PATH . '/app/entities/MaterialLotEntity.php';

class MaterialLotRepository
{
    public function findByLot(string $lot): ?MaterialLotEntity
    {
        $list = GlobalData::$listMaterialLotEntity;
        foreach ($list as $materialLot) {
            if (!empty($materialLot['lot']) && $materialLot['lot'] === $lot) {
                return new MaterialLotEntity(
                    id: $materialLot['id'],
                    lot: $materialLot['lot'],
                    updated_time: new DateTime($materialLot['updated_time'])
                );
            }
        }
        return null;
    }
}
