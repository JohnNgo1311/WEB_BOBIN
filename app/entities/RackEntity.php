<?php
// File: app/entities/RackEntity.php

class RackEntity
{
    public int $id;
    public string $code;

    public function __construct(int $id, string $code)
    {
        $this->id = $id;
        $this->code = $code;
    }

    public static function fromArray(array $data): ?self
    {
        if (empty($data) || !isset($data['id'], $data['code'])) {
            return null;
        }
        return new self(
            id: (int)$data['id'],
            code: trim($data['code'])
        );
    }
}