<?php
// File: app/entities/ListDataEntity.php

class ListDataEntity
{

    public array $list_bobin = [];
    public array $list_employee = [];
    public array $list_material_lot = [];
    public array $list_material = [];
    public array $list_extrusion_machine = [];
    public array $list_winding_machine = [];
    public array $list_product = [];
    public array $list_year = [];
    public array $list_month = [];
    public array $list_day = [];
    public bool $success = false;

    public function __construct()
    {
        $this->list_bobin = [];
        $this->list_employee = [];
        $this->list_material_lot = [];
        $this->list_material = [];
        $this->list_extrusion_machine = [];
        $this->list_winding_machine = [];
        $this->list_product = [];
        $this->list_year = [];
        $this->list_month = [];
        $this->list_day = [];
        $this->success = false;
    }
}