<?php
require_once __DIR__ . '/../core/Database.php';


class VisualInspectionEntity implements JsonSerializable
{
    public function __construct(
    public DeFectEntity $defects,
    public string $inspector_code = '', 
    public string $inspector_name = '', 
    public ?DateTime $inspection_time = null)
    {
        if ($this->inspection_time === null) {
            $this->inspection_time = new DateTime();
        }
    }
     public function jsonSerialize(): array
    {
        return [
            'defects' => $this->defects,
            'inspector_code' => $this->inspector_code,
            'inspector_name' => $this->inspector_name,
            'inspection_time' => $this->inspection_time?->format('Y-m-d H:i:s'),
        ];
    }
    public static function fromJson(string $json): self
    { 
        $data = json_decode($json, true);
        return new self(
            defects: DefectEntity::fromArray($data['defects'] ?? []),
            inspector_code: $data['inspector_code'] ?? '',
            inspector_name: $data['inspector_name'] ?? '',
            inspection_time: isset($data['inspection_time']) 
                ? new DateTime($data['inspection_time'])
                : null
        );
    }


}