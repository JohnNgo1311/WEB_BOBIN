<?php
// app/models/DTO/BobinDTO.php
/* ===================================*/
//BobinDTO – DỮ LIỆU THÔ (FORM SHAPE)
//! 👉 DTO LUÔN LUÔN
//? chỉ string / number / array
//❌ không DateTime
//❌ không PDO
//❌ không logic
//! 📍 DTO = thứ JS gửi lên
/* ===================================*/
class BobinDTO
{
    public string $bobin_key_code;
    public string $bobin_identification_code;
    public string $bobin_type;
    public array  $employee;        // ['employee_code'=>..., 'employee_name'=>...]
    public array  $product;         // ['production_order_code'=>..., 'product_code'=>...]
    public string $material_lot;    // string (lot)
    public string $print_lot;
    public float  $length_m;
    public string $shift;
    public ?string $extrusion_date; // 'Y-m-d' or null
    public ?string $finish_time;    // 'Y-m-d H:i:s' or null
    public string $bobin_current_status;
    public array  $visual_inspection; // array (will be JSON-encoded)
    public string $updated_time;     // 'Y-m-d H:i:s'
}
