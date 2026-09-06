<?php
require_once __DIR__ . '/../core/Database.php';

class WindingMachineEntity implements JsonSerializable
{

    public function __construct(
        public ?int $id = null,
        public string $WindingMachineEntity_code = '',
    ) {}
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'machine_name' => $this->WindingMachineEntity_code,
        ];
    }
    public static function fromJson(string $json): self
    {
        $data = json_decode($json, true);
        return new self(
            id: $data['id'] ?? null,
            WindingMachineEntity_code: $data['machine_name'] ?? '',
        );
    }
}
