<?php
// File: app/dtos/Bobin/BobinWindingCancelDTO.php

class BobinWindingCancelDTO
{
    // Thông tin Winding (Visual Inspection)
    public string $bobin_identification_code; // Mã định danh để xác định Bobin cần cập nhật
    public string $bobin_key_code;
    public string $winding_machine;
    public string $winding_employee_code;
    public string $winding_employee_name;
    public string $flow_test_result; // 'Thành công' hoặc 'Thất bại'
    public string $winding_note;
    public array $cancel_errors = [];
    public ?string $source = null;
    public ?string $finished_product_qty = null;
    public ?string $cancel_reason = null;
    public ?string $winding_chart_image = null;

    // Hàm static để map dữ liệu từ Request (Form) sang DTO
    public static function fromRequest(array $request): self
    {
        $dto = new self();
        $dto->bobin_identification_code = $request["bobin_identification_code"] ?? '';
        $dto->bobin_key_code = $request["bobin_key_code"] ?? '';
        $dto->winding_machine = $request["winding_machine"] ?? '';
        $dto->winding_employee_code = $request["winding_employee_code"] ?? '';
        $dto->winding_employee_name = $request["winding_employee_name"] ?? '';
        $dto->flow_test_result = $request['flow_test_result'] ?? 'Thất bại';
        $dto->winding_note = $request["winding_note"] ?? '';
        $dto->cancel_errors = is_array($request['cancel_errors'] ?? null) ? $request['cancel_errors'] : [];
        $dto->source = isset($request['source']) ? (string)$request['source'] : null;
        $dto->finished_product_qty = isset($request['finished_product_qty']) ? (string)$request['finished_product_qty'] : null;
        $dto->cancel_reason = isset($request['cancel_reason']) ? (string)$request['cancel_reason'] : null;
        return $dto;
    }
}
