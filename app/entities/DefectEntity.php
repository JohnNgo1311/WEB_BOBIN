<?php
class DefectEntity implements JsonSerializable
{
    public bool $gel;
    public bool $foreign_object;
    public bool $color_issue;
    public bool $print_quality;
    public bool $clump;
    public bool $scratch;
    public bool $streak;
    public bool $deformation;
    public string $note;

    public function __construct(
        bool $gel = false,
        bool $foreign_object = false,
        bool $color_issue = false,
        bool $print_quality = false,
        bool $clump = false,
        bool $scratch = false,
        bool $streak = false,
        bool $deformation = false,
        string $note = ''
    ) {
        $this->gel = $gel;
        $this->foreign_object = $foreign_object;
        $this->color_issue = $color_issue;
        $this->print_quality = $print_quality;
        $this->clump = $clump;
        $this->scratch = $scratch;
        $this->streak = $streak;
        $this->deformation = $deformation;
        $this->note = $note;
    }
    
    public function jsonSerialize(): array
    {
        return [
            'gel' => $this->gel,
            'foreign_object' => $this->foreign_object,
            'color_issue' => $this->color_issue,
            'print_quality' => $this->print_quality,
            'clump' => $this->clump,
            'scratch' => $this->scratch,
            'streak' => $this->streak,
            'deformation' => $this->deformation,
            'note' => $this->note
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            gel: filter_var($data['gel'] ?? false, FILTER_VALIDATE_BOOLEAN),
            foreign_object: filter_var($data['foreign_object'] ?? false, FILTER_VALIDATE_BOOLEAN),
            color_issue: filter_var($data['color_issue'] ?? false, FILTER_VALIDATE_BOOLEAN),
            print_quality: filter_var($data['print_quality'] ?? false, FILTER_VALIDATE_BOOLEAN),
            clump: filter_var($data['clump'] ?? $data['lump'] ?? false, FILTER_VALIDATE_BOOLEAN),
            scratch: filter_var($data['scratch'] ?? false, FILTER_VALIDATE_BOOLEAN),
            streak: filter_var($data['streak'] ?? $data['scuff'] ?? false, FILTER_VALIDATE_BOOLEAN),
            deformation: filter_var($data['deformation'] ?? $data['deformed'] ?? false, FILTER_VALIDATE_BOOLEAN),
            note: (string)($data['note'] ?? '')
        );
    }
}