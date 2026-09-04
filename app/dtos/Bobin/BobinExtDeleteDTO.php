<?php
// File: app/dtos/Bobin/BobinExtDeleteDTO.php

class BobinExtDeleteDTO
{
    public string $bobin_identification_code; // Mã định danh để xác định Bobin cần cập nhật
    public string $extrusion_employee_code;
    public string $extrusion_employee_name;

    public static function fromRequest(array $request): self
    {
        $dto = new self();
        $dto->bobin_identification_code = $request['bobin_identification_code'] ?? '';
        $dto->extrusion_employee_code = $request['extrusion_employee_code'] ?? '';
        $dto->extrusion_employee_name = $request['extrusion_employee_name'] ?? '';
        return $dto;
    }
}
