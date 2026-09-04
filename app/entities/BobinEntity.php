<?php
// File: app/entities/BobinEntity.php
require_once ROOT_PATH . '/app/entities/VisualInspectionEntity.php';
require_once ROOT_PATH . '/app/entities/EmployeeEntity.php';
require_once ROOT_PATH . '/app/entities/ProductEntity.php';
require_once ROOT_PATH . '/app/entities/MaterialLotEntity.php';
require_once ROOT_PATH . '/app/entities/DeFectEntity.php';
require_once ROOT_PATH . '/app/entities/VisualInspectionEntity.php';

class BobinEntity
{
    public ?int $id = 1;
    public string $bobinKeyCode;         // Unique Key (Mã + Time)
    public string $identificationCode;   // Mã định danh (BBA01...)

    public string $size;
    public string $type;

    public EmployeeEntity $extrusion_employee;
    public ProductEntity $product;
    public MaterialLotEntity $materialLot;

    public string $printLot;
    public float $length;
    public string $shift;

    public DateTime $extrusionDate;
    public DateTime $finishTime;
    public string $currentStatus;

    // Field này trong DB là JSON
    public VisualInspectionEntity $visualInspection;
    public string $winding_machine;

    public ?EmployeeEntity $winding_employee;

    public string $flow_test_result;

    public string $winding_note;

    public DateTime $updatedTime;

    public function __construct(
        ?int $id = null,
        string $bobinKeyCode = '',
        string $identificationCode = '',
        string $size = 'Chưa cập nhật',
        string $type = 'Chưa cập nhật',
        ?EmployeeEntity $extrusion_employee = null,
        ?ProductEntity $product = null,
        ?MaterialLotEntity $materialLot = null,
        string $printLot = 'Chưa cập nhật',
        float $length = 0,
        string $shift = 'Chưa cập nhật',
        ?DateTime $extrusionDate = null,
        ?DateTime $finishTime = null,
        string $currentStatus = 'Busy_Unchecked',
        ?VisualInspectionEntity $visualInspection = null,
        string $winding_machine = 'Chưa cập nhật',
        ?EmployeeEntity $winding_employee = null,
        string $flow_test_result = 'Thất bại',
        string $winding_note = 'Chưa cập nhật',
        ?DateTime $updatedTime = null
    ) {
        $this->id = $id ?? 1;
        $this->bobinKeyCode = $bobinKeyCode;
        $this->identificationCode = $identificationCode;
        $this->size = $size;
        $this->type = $type;
        $this->extrusion_employee = $extrusion_employee ?? new EmployeeEntity();
        $this->product = $product ?? new ProductEntity();
        $this->materialLot = $materialLot ?? new MaterialLotEntity();
        $this->printLot = $printLot;
        $this->length = $length;
        $this->shift = $shift;
        $this->extrusionDate = $extrusionDate ?? new DateTime();
        $this->finishTime = $finishTime ?? new DateTime();
        $this->currentStatus = $currentStatus;
        $this->visualInspection = $visualInspection ?? new VisualInspectionEntity(new DeFectEntity());
        $this->winding_machine = $winding_machine;
        $this->winding_employee = $winding_employee;
        $this->flow_test_result = $flow_test_result;
        $this->winding_note = $winding_note;
        $this->updatedTime = $updatedTime ?? new DateTime();
    }
    public static function fromJson(string $json): self
    {
        $data = json_decode($json, true);
        return new self(
            id: $data['id'] ?? 1,
            bobinKeyCode: $data['bobin_key_code'] ?? '',
            identificationCode: $data['bobin_identification_code'] ?? '',
            size: $data['bobin_size'] ?? 'Chưa cập nhật',
            type: $data['bobin_type'] ?? 'Chưa cập nhật',
            extrusion_employee: isset($data['extrusion_employee']) ? EmployeeEntity::fromJson($data['extrusion_employee']) : new EmployeeEntity(),
            product: isset($data['products']) ? ProductEntity::fromJson($data['products']) : new ProductEntity(),
            materialLot: isset($data['material_lot']) ? MaterialLotEntity::fromJson($data['material_lot']) : new MaterialLotEntity(),
            printLot: $data['print_lot'] ?? 'Chưa cập nhật',
            length: (float)($data['length_m'] ?? 0),
            shift: $data['shift'] ?? 'Chưa cập nhật',
            extrusionDate: !empty($data['extrusionDate']) ? new DateTime($data['extrusionDate']) : new DateTime(),
            finishTime: !empty($data['finishTime']) ? new DateTime($data['finishTime']) : new DateTime(),
            currentStatus: $data['bobin_current_status'] ?? '',
            visualInspection: isset($data['visual_inspection']) ? VisualInspectionEntity::fromJson($data['visual_inspection']) : new VisualInspectionEntity(new DeFectEntity()),
            winding_machine: $data['winding_machine'] ?? 'Chưa cập nhật',
            winding_employee: isset($data['winding_employee']) ? EmployeeEntity::fromJson($data['winding_employee']) : null,
            flow_test_result: $data['flow_test_result'] ?? 'Thất bại',
            winding_note: $data['winding_note'] ?? 'Chưa cập nhật',
            updatedTime: !empty($data['updatedTime']) ? new DateTime($data['updatedTime']) : new DateTime()
        );
    }
}
