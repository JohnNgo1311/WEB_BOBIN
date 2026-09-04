<?php
require_once __DIR__ . '/../core/Database.php';

class ProductEntity implements JsonSerializable
{  
    public function __construct(
    public ?int $id = null, 
    public string $production_order_code = '', 
    public string $product_code = '', 
    public string $description = '', 
    public ?DateTime $updated_time = null)
        {
        if ($this->updated_time === null) {
            $this->updated_time = new DateTime();
        }
        }
    
    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'] ?? null,
            production_order_code: $data['production_order_code'] ?? '',
            product_code: $data['product_code'] ?? '',
            description: $data['description'] ?? '',
            updated_time: isset($data['updated_time']) 
                ? new DateTime($data['updated_time']) 
                : null
        );
    }
    
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'production_order_code' => $this->production_order_code,
            'product_code' => $this->product_code,
            'description' => $this->description,
            'updated_time' => $this->updated_time?->format('Y-m-d H:i:s'),
        ];
    }
    public static function fromJson(string $json): self
    {
        $data = json_decode($json, true);
        return new self(
            id: $data['id'] ?? null,
            production_order_code: $data['production_order_code'] ?? '',
            product_code: $data['product_code'] ?? '',
            description: $data['description'] ?? '',
            updated_time: isset($data['updated_time']) 
                ? new DateTime($data['updated_time']) 
                : null
        );
    }

}