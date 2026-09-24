<?php
// File: app/dtos/Bobin/BobinGetListDTO.php
class BobinGetListDTO
{
    public ?string $keyword;
    public ?string $fromDate;
    public ?string $toDate;
    public ?string $status;
    public ?string $bobinSize;
    public ?string $bobinType;
    public ?string $rack;       // <-- Thêm lọc Rack
    public ?string $sort;       // <-- Thêm sắp xếp mới nhất
    public int $page;
    public int $limit;

    public function __construct(
        ?string $keyword = '',
        ?string $fromDate = '',
        ?string $toDate = '',
        ?string $status = 'all',
        ?string $bobinSize = 'all',
        ?string $bobinType = 'all',
        ?string $rack = 'all',
        ?string $sort = 'default',
        int $page = 1,
        int $limit = 50
    ) {
        $this->keyword = $keyword;
        $this->fromDate = $fromDate;
        $this->toDate = $toDate;
        $this->status = $status;
        $this->bobinSize = $bobinSize;
        $this->bobinType = $bobinType;
        $this->rack = $rack;
        $this->sort = $sort;
        $this->page = max(1, $page);
        $this->limit = max(1, min(100, $limit));
    }

    public static function fromRequest(array $request): self
    {
        return new self(
            keyword: $request['keyword'] ?? '',
            fromDate: $request['from_date'] ?? '',
            toDate: $request['to_date'] ?? '',
            status: $request['status'] ?? 'all',
            bobinSize: $request['bobin_size'] ?? 'all',
            bobinType: $request['bobin_type'] ?? 'all',
            rack: $request['rack'] ?? 'all',
            sort: $request['sort'] ?? 'default',
            page: isset($request['page']) ? (int)$request['page'] : 1,
            limit: isset($request['limit']) ? (int)$request['limit'] : 50
        );
    }
}