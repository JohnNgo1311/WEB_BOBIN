<?php
// File: app/dtos/Bobin/BobinUpdateWindingDTO.php

class BobinUpdateWindingDTO
{
    public string $bobin_identification_code; // Mã định danh để xác định Bobin cần cập nhật
    public string $bobin_key_code;
    public string $winding_machine;
    public ?string $winding_employee_code; // Có thể null nếu không có thông tin nhân viên cuốn
    public string $flow_test_result; // 'Thành công' hoặc 'Thất bại'
    public ?string $winding_note; // Ghi chú có thể null

    // Hàm static để map dữ liệu từ Request (Form) sang DTO
    public static function fromRequest(array $request): self
    {
        $dto = new self();
        $dto->bobin_identification_code = $request["bobin_identification_code"] ?? '';
        $dto->bobin_key_code = $request["bobin_key_code"] ?? '';
        $dto->winding_machine = $request["winding_machine"] ?? '';
        $dto->winding_employee_code = $request["winding_employee_code"] ?? null;
        $dto->flow_test_result = $request["flow_test_result"] ?? 'Thất bại';
        $dto->winding_note = $request["winding_note"] ?? '';
        return $dto;
    }
}
