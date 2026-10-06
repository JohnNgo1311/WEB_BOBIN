<?php
// File: app/dtos/Bobin/BobinCreateDTO.php

class BobinCreateDTO
{

    //! Update thêm field 22/09/2026:
    public string $rack_code;
    //! Extrusion Visual inspection
    public bool $ext_check_diameter;
    public bool $ext_check_gel;
    public bool $ext_check_foreign_object;
    public bool $ext_check_color;
    public bool $ext_check_print;
    //!
    // Thông tin cơ bản
    public string $bobin_identification_code;
    public string $bobin_size;
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

    // Thông tin QC (Visual Inspection)
    public string $inspector_code;
    public string $inspector_name;
    public bool $defect_gel;
    public bool $defect_foreign_object;
    public bool $defect_color_issue;
    public bool $defect_print_quality;
    public string $defect_note;

    public string $winding_employee_code;
    public string $winding_machine;
    public string $flow_test_result;


    public string $winding_note; // Ghi chú thêm cho phía cuộn


    // Hàm static để map dữ liệu từ Request (Form) sang DTO
    public static function fromRequest(array $request): self
    {
        $dto = new self();

        // Trim và gán giá trị mặc định nếu rỗng

        $dto->bobin_identification_code = trim($request['bobin_identification_code'] ?? '');
        // if (!str_starts_with($dto->bobin_identification_code, 'BB')) {
        //     $dto->bobin_identification_code = 'BB' . $dto->bobin_identification_code;
        // }


        // Thêm vào trong hàm fromRequest():
        $dto->rack_code = trim($request['rack_code'] ?? '');
        $dto->ext_check_diameter = filter_var($request['ext_check_diameter'] ?? true, FILTER_VALIDATE_BOOLEAN);
        $dto->ext_check_gel      = filter_var($request['ext_check_gel'] ?? true, FILTER_VALIDATE_BOOLEAN);
        $dto->ext_check_foreign_object = filter_var($request['ext_check_foreign_object'] ?? true, FILTER_VALIDATE_BOOLEAN);
        $dto->ext_check_color    = filter_var($request['ext_check_color'] ?? true, FILTER_VALIDATE_BOOLEAN);
        $dto->ext_check_print    = filter_var($request['ext_check_print'] ?? true, FILTER_VALIDATE_BOOLEAN);

        $dto->bobin_size = trim($request['bobin_size'] ?? '');
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

        // QC
        $dto->inspector_code = $request['inspector_code'] ?? 'Chưa cập nhật';
        $dto->inspector_name = $request['inspector_name'] ?? 'Chưa cập nhật';

        // Filter boolean (checkbox HTML gửi 'on' hoặc không gửi gì, hoặc string 'true'/'false')
        $dto->defect_gel = filter_var($request['defect_gel'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $dto->defect_foreign_object = filter_var($request['defect_foreign_object'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $dto->defect_color_issue = filter_var($request['defect_color_issue'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $dto->defect_print_quality = filter_var($request['defect_print_quality'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $dto->defect_note = $request['defect_note'] ?? '';

        $dto->winding_employee_code = trim($request['winding_employee_code'] ?? '');
        $dto->winding_machine = trim($request['winding_machine'] ?? '');

        $dto->winding_note = trim($request['winding_note'] ?? 'Chưa cập nhật');
        $dto->flow_test_result = trim($request['flow_test_result'] ?? 'Thất bại');

        return $dto;
    }
}
