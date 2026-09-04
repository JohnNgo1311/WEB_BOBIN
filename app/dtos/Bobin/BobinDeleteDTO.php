<?php
// File: app/dtos/Bobin/BobinDeleteDTO.php

class BobinDeleteDTO
{
    public string $bobin_identification_code; // Mã định danh để xác định Bobin cần cập nhật
    public string $bobin_key_code;

    // Hàm static để map dữ liệu từ Request (Form) sang DTO
    public static function fromRequest(array $request): self
    {
        $dto = new self();
        $dto->bobin_identification_code = $request["bobin_identification_code"] ?? '';
        $dto->bobin_key_code = $request["bobin_key_code"] ?? '';
        return $dto;
    }
}
