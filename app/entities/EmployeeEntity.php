<?php
require_once __DIR__ . '/../core/Database.php';

class EmployeeEntity implements JsonSerializable
{

    public function __construct(
    public ?int $id = null, 
    public string $employee_code='', 
    public string $employee_name='', 
    public ?DateTime $updated_time = null)
    { 
    if ($this->updated_time === null) 
    {
    $this->updated_time = new DateTime();
    }
    }
     public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'employee_code' => $this->employee_code,
            'employee_name' => $this->employee_name,
            'updated_time' => $this->updated_time
                ? $this->updated_time->format('Y-m-d H:i:s')
                : null,
        ];
    }
public static function fromJson(string $json): self
{
    $data = json_decode($json, true);
    return new self(
        id: $data['id'] ?? null,
        employee_code: $data['employee_code'] ?? '',
        employee_name: $data['employee_name'] ?? '',
        updated_time: isset($data['updated_time']) 
            ? new DateTime($data['updated_time']) 
            : null
    );  
}
}