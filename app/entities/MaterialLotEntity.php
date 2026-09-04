<?php
require_once __DIR__ . '/../core/Database.php';

class MaterialLotEntity implements JsonSerializable
{
public function __construct(
    public ?int $id = null, 
    public string $lot = '',
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
            'lot' => $this->lot,
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
        lot: $data['lot'] ?? '',
        updated_time: isset($data['updated_time']) 
            ? new DateTime($data['updated_time']) 
            : null
    );
    }

}