<?php
require_once __DIR__ . '/../core/Database.php';

class ExtrusionMachineEntity implements JsonSerializable
{

    public function __construct(
        public ?int $id = null,
        public ?int $machine_number = null,
        public ?int $machine_code = null,
        public string $machine_name = '',
    ) {}
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'machine_number' => $this->machine_number,
            'machine_name' => $this->machine_name,
            'machine_code' => $this->machine_code,
        ];
    }
    public static function fromJson(string $json): self
    {
        $data = json_decode($json, true);
        return new self(
            id: $data['id'] ?? null,
            machine_number: $data['machine_number'] ?? null,
            machine_name: $data['machine_name'] ?? '',
            machine_code: $data['machine_code'] ?? null,
        );
    }
}
