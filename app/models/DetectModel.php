<?php
require_once __DIR__ . '/../core/Database.php';

class DefectModel
{
    private $db; 

    public bool $gel;
    public bool $foreign_object;
    public bool $color_issue;
    public bool $print_quality;
    public string $note;
    public function __construct(bool $gel = false, bool $foreign_object = false, bool $color_issue = false, bool $print_quality = false, string $note = '')
    {
        $this->gel = $gel;
        $this->foreign_object = $foreign_object;
        $this->color_issue = $color_issue;
        $this->print_quality = $print_quality;
        $this->note = $note;
        
        $this->db = Database::getInstance();
    }

    private function json_utf8($data): string
    {
        return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

    }
}