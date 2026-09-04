<?php
// File: app/dtos/Bobin/BobinExtUpdateDTO.php

class BobinExtUpdateDTO
{
    public string $bobin_identification_code;
    public string $bobin_type;
    public string $extrusion_employee_code;
    public string $production_order_code;
    public string $product_code;
    public string $material_lot;
    public string $print_lot;
    public float $length_m;
    public string $shift;

    // Thời gian
    public string $extrusion_date; // YYYY-MM-DD
    public string $finish_time;    // YYYY-MM-DD HH:mm:ss


    // Hàm static để map dữ liệu từ Request (Form) sang DTO
    public static function fromRequest(array $request): self
    {
        $dto = new self();
        $dto->bobin_identification_code = trim($request['bobin_identification_code'] ?? '');

        $dto->bobin_type = $request['bobin_type'] ?? '';
        $dto->extrusion_employee_code = trim($request['extrusion_employee_code'] ?? '');

        $dto->production_order_code = trim($request['production_order_code'] ?? '');
        $dto->product_code = trim($request['product_code'] ?? '');
        $dto->material_lot = trim($request['material_lot'] ?? '');
        $dto->print_lot = trim($request['print_lot'] ?? '');
        $dto->length_m = floatval($request['length_m'] ?? 0);
        $dto->shift = $request['shift'] ?? '';
        // Xử lý ngày tháng
        $dto->extrusion_date = $request['extrusion_date'] ?? date('Y-m-d');
        $dto->finish_time = $request['finish_time'] ?? date('Y-m-d H:i:s');
        return $dto;
    }
}
