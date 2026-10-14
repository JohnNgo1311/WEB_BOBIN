<?php
// File: app/dtos/Bobin/BobinQCCancelDTO.php

class BobinQCCancelDTO
{
    // Thông tin QC (Visual Inspection)
    public string $bobin_identification_code; // Mã định danh để xác định Bobin cần cập nhật
    public string $bobin_key_code;
    public string $inspector_code;
    public string $inspector_name;
    public bool $defect_gel;
    public bool $defect_foreign_object;
    public bool $defect_color_issue;
    public bool $defect_print_quality;
    public bool $defect_clump;
    public bool $defect_scratch;
    public bool $defect_streak;
    public bool $defect_deformation;
    public string $defect_note;

    // Hàm static để map dữ liệu từ Request (Form) sang DTO
    public static function fromRequest(array $request): self
    {
        $dto = new self();
        $dto->bobin_identification_code = $request["bobin_identification_code"] ?? '';
        $dto->bobin_key_code = $request["bobin_key_code"] ?? '';
        $dto->inspector_code = $request["inspector_code"] ?? '';
        $dto->inspector_name = $request["inspector_name"] ?? '';
        $dto->defect_gel = filter_var($request["defect_gel"] ?? false, FILTER_VALIDATE_BOOLEAN);
        $dto->defect_foreign_object = filter_var($request["defect_foreign_object"] ?? false, FILTER_VALIDATE_BOOLEAN);
        $dto->defect_color_issue = filter_var($request["defect_color_issue"] ?? false, FILTER_VALIDATE_BOOLEAN);
        $dto->defect_print_quality = filter_var($request["defect_print_quality"] ?? false, FILTER_VALIDATE_BOOLEAN);
        $dto->defect_clump = filter_var($request["defect_clump"] ?? $request["defect_lump"] ?? false, FILTER_VALIDATE_BOOLEAN);
        $dto->defect_scratch = filter_var($request["defect_scratch"] ?? false, FILTER_VALIDATE_BOOLEAN);
        $dto->defect_streak = filter_var($request["defect_streak"] ?? $request["defect_scuff"] ?? false, FILTER_VALIDATE_BOOLEAN);
        $dto->defect_deformation = filter_var($request["defect_deformation"] ?? $request["defect_deformed"] ?? false, FILTER_VALIDATE_BOOLEAN);
        $dto->defect_note = $request["defect_note"] ?? '';
        return $dto;
    }
}
