<?php
// File: app/dtos/Bobin/BobinGetListDTO.php

class BobinGetListDTO
{
    // Thông tin cơ bản
    public string $keyword;
    public string $fromDate; // YYYY-MM-DD
    public string $toDate;    // YYYY-MM-DD HH:mm:ss
    public string $status;


    // Hàm static để map dữ liệu từ Request (Form) sang DTO
    public static function fromRequest(array $request): self
    {
        $dto = new self();
        // Trim và gán giá trị mặc định nếu rỗng
        $dto->keyword = trim($request['keyword'] ?? '');
        $dto->fromDate = trim($request['from_date'] ?? '');
        $dto->toDate = trim($request['to_date'] ?? '');
        $dto->status = trim($request['status'] ?? '');

        return $dto;
    }
}
