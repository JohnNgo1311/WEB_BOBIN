<?php
// File: app/dtos/Bobin/BobinQCUpdateDTO.php

class BobinQCUpdateDTO
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
    public string $confirm_password;
    public ?string $qc_visual_image = null;
    public string $qc_image_action = 'keep';


    // Hàm static để map dữ liệu từ Request (Form) sang DTO
    public static function fromRequest(array $request): self
    {
        $dto = new self();
        $dto->bobin_identification_code = $request["bobin_identification_code"] ?? '';
        $dto->bobin_key_code = $request["bobin_key_code"] ?? '';

        // QC
        $dto->inspector_code = $request['inspector_code'] ?? 'Chưa cập nhật';
        $dto->inspector_name = $request['inspector_name'] ?? 'Chưa cập nhật';

        // Hỗ trợ cả cấu trúc phẳng (defect_gel) và cấu trúc lồng nhau (defects.gel từ trang Điều chỉnh QC)
        $defectsNested = (isset($request['defects']) && is_array($request['defects'])) ? $request['defects'] : [];

        // Filter boolean (checkbox HTML gửi 'on' hoặc không gửi gì, hoặc string 'true'/'false')
        $dto->defect_gel = filter_var($request['defect_gel'] ?? $defectsNested['gel'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $dto->defect_foreign_object = filter_var($request['defect_foreign_object'] ?? $defectsNested['foreign_object'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $dto->defect_color_issue = filter_var($request['defect_color_issue'] ?? $defectsNested['color_issue'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $dto->defect_print_quality = filter_var($request['defect_print_quality'] ?? $defectsNested['print_quality'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $dto->defect_clump = filter_var($request['defect_clump'] ?? $request['defect_lump'] ?? $defectsNested['clump'] ?? $defectsNested['lump'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $dto->defect_scratch = filter_var($request['defect_scratch'] ?? $defectsNested['scratch'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $dto->defect_streak = filter_var($request['defect_streak'] ?? $request['defect_scuff'] ?? $defectsNested['streak'] ?? $defectsNested['scuff'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $dto->defect_deformation = filter_var($request['defect_deformation'] ?? $request['defect_deformed'] ?? $defectsNested['deformation'] ?? $defectsNested['deformed'] ?? false, FILTER_VALIDATE_BOOLEAN);

        $rawNote = $request['defect_note'] ?? ($defectsNested['note'] ?? '');
        if (is_string($rawNote) && !empty(trim($rawNote))) {
            $dto->defect_note = trim($rawNote);
        } else {
            $dto->defect_note = 'Chưa cập nhật';
        }
        $dto->confirm_password = trim($request['confirm_password'] ?? '');
        $dto->qc_image_action = strtolower(trim((string)($request['qc_image_action'] ?? 'keep')));
        return $dto;
    }
}
