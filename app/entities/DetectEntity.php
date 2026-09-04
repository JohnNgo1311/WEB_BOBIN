<?php
class DetectEntity
{
    public function __construct(
    public bool $gel = false, 
    public bool $foreign_object = false, 
    public bool $color_issue = false, 
    public bool $print_quality = false, 
    public string $note = '')
    {}
}