<?php
require_once __DIR__ . '/../core/Database.php';

require_once ROOT_PATH . '/app/models/DefectModel.php';
class VisualInspectionModel
{
    private $db;
    
    public string $inspector_code;
    public string $inspector_name;
    public DateTime $inspection_time;
    public DefectModel $defects;

    public function __construct(string $inspector_code, string $inspector_name, DateTime $inspection_time, DefectModel $defects)
    {   $this->inspector_code = $inspector_code;
        $this->inspector_name = $inspector_name;
        $this->inspection_time = $inspection_time;
        $this->defects = $defects;
        $db = Database::getInstance();
    }

    private function json_utf8($data): string
    {
        return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

    }
}