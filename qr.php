<?php
require 'config/database.php';
require_once 'libs/phpqrcode/qrlib.php';
// The QRcode class is included directly, no need for 'use' statement

$bobin_id = $_GET['bobin_id'] ?? null;
if(!$bobin_id) { http_response_code(400); echo 'Missing bobin_id'; exit; }

$stmt = $pdo->prepare("SELECT * FROM bobin_list_general WHERE id = ?");
$stmt->execute([$bobin_id]);
$bobin = $stmt->fetch();
if(!$bobin){ http_response_code(404); echo 'Not found'; exit; }

// nội dung QR: có thể là URL chi tiết hoặc JSON rút gọn
$payload = json_encode([
  'bobin_identification_code' => $bobin['bobin_identification_code'],
  'id' => $bobin['id'],
  'product' => $bobin['product_code']
], JSON_UNESCAPED_UNICODE);

// generate to output (PNG)
header('Content-Type: image/png');
//! QRcode::png($payload, null, QR_ECLEVEL_L, 3);