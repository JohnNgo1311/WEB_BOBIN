<?php
// File: app/entities/ExtrusionCheckEntity.php

class ExtrusionCheckEntity
{
    public bool $diameter;
    public bool $gel;
    public bool $foreign_object;
    public bool $color;
    public bool $print;
    public bool $clump;
    public bool $scratch;
    public bool $streak;
    public bool $deformation;

    public function __construct(
        bool $diameter = true,
        bool $gel = true,
        bool $foreign_object = true,
        bool $color = true,
        bool $print = true,
        bool $clump = true,
        bool $scratch = true,
        bool $streak = true,
        bool $deformation = true
    ) {
        $this->diameter = $diameter;
        $this->gel = $gel;
        $this->foreign_object = $foreign_object;
        $this->color = $color;
        $this->print = $print;
        $this->clump = $clump;
        $this->scratch = $scratch;
        $this->streak = $streak;
        $this->deformation = $deformation;
    }

    public static function fromArray(array $data): self
    {
        return new self(
            diameter: filter_var($data['diameter'] ?? true, FILTER_VALIDATE_BOOLEAN),
            gel: filter_var($data['gel'] ?? true, FILTER_VALIDATE_BOOLEAN),
            foreign_object: filter_var($data['foreign_object'] ?? true, FILTER_VALIDATE_BOOLEAN),
            color: filter_var($data['color'] ?? true, FILTER_VALIDATE_BOOLEAN),
            print: filter_var($data['print'] ?? true, FILTER_VALIDATE_BOOLEAN),
            clump: filter_var($data['clump'] ?? $data['lump'] ?? true, FILTER_VALIDATE_BOOLEAN),
            scratch: filter_var($data['scratch'] ?? true, FILTER_VALIDATE_BOOLEAN),
            streak: filter_var($data['streak'] ?? $data['scuff'] ?? true, FILTER_VALIDATE_BOOLEAN),
            deformation: filter_var($data['deformation'] ?? $data['deformed'] ?? true, FILTER_VALIDATE_BOOLEAN)
        );
    }
}
