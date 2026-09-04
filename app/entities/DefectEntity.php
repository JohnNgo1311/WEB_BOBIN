<?php
class DefectEntity implements JsonSerializable
{
    public bool $gel;
    public bool $foreign_object;
    public bool $color_issue;
    public bool $print_quality;
    public string $note;

    public function __construct(
        bool $gel = false,
        bool $foreign_object = false,
        bool $color_issue = false,
        bool $print_quality = false,
        string $note = ''
    ) {
        $this->gel = $gel;
        $this->foreign_object = $foreign_object;
        $this->color_issue = $color_issue;
        $this->print_quality = $print_quality;
        $this->note = $note;
    }
    
    public function jsonSerialize(): array
    {
        return [
            'gel' => $this->gel,
            'foreign_object' => $this->foreign_object,
            'color_issue' => $this->color_issue,
            'print_quality' => $this->print_quality,
            'note' => $this->note
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            gel: $data['gel'] ?? false,
            foreign_object: $data['foreign_object'] ?? false,
            color_issue: $data['color_issue'] ?? false,
            print_quality: $data['print_quality'] ?? false,
            note: $data['note'] ?? ''
        );
    }
}