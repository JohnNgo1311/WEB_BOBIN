<?php
// File: app/dtos/Bobin/BobinGetSpecificDTO.php

class BobinGetSpecificDTO
{
    // Thông tin cơ bản
    public string $bobin_Identification_Code;

    // Hàm static để map dữ liệu từ Request (Form) sang DTO
    public static function fromRequest(array $request): self
    {
        $dto = new self();
        $dto->bobin_Identification_Code = trim($request['bobin_identification_code'] ?? '');
        return $dto;
    }
}
