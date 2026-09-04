<?php
declare(strict_types=1);

require_once __DIR__ . '/../../app/core/Database.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$type  = $_GET['type']  ?? '';
$query = trim($_GET['query'] ?? '');

$result = [];

/* ================= VALIDATION ================= */
$allowedTypes = ['bobin', 'employee', 'product'];

if (!in_array($type, $allowedTypes, true)) {
    echo json_encode([]);
    exit;
}

if (mb_strlen($query) < 2) {
    echo json_encode([]);
    exit;
}

/* ================= DB ================= */
try {
    $pdo = Database::getInstance()->pdo();
    $q = "%{$query}%";

    switch ($type) {

        case 'bobin':
            $stmt = $pdo->prepare(
                "SELECT bobin_identification_code 
                 FROM bobin_list_general 
                 WHERE bobin_identification_code LIKE :q 
                 ORDER BY bobin_identification_code DESC
                 LIMIT 10"
            );
            $stmt->execute(['q' => $q]);

            $result = array_map(
                fn($r) => ['value' => $r['bobin_identification_code']],
                $stmt->fetchAll()
            );
            break;

        case 'employee':
            $stmt = $pdo->prepare(
                "SELECT employee_code, employee_name
                 FROM employees
                 WHERE employee_code LIKE :q OR employee_name LIKE :q
                 LIMIT 10"
            );
            $stmt->execute(['q' => $q]);

            $result = array_map(
                fn($r) => [
                    'value' => $r['employee_code'],
                    'label' => "{$r['employee_code']} - {$r['employee_name']}",
                    'name'  => $r['employee_name']
                ],
                $stmt->fetchAll()
            );
            break;

        case 'product':
            $stmt = $pdo->prepare(
                "SELECT product_code 
                 FROM products
                 WHERE product_code LIKE :q
                 LIMIT 10"
            );
            $stmt->execute(['q' => $q]);

            $result = array_map(
                fn($r) => ['value' => $r['product_code']],
                $stmt->fetchAll()
            );
            break;
    }

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'DB_ERROR',
        'message' => $e->getMessage()
    ]);
    exit;
}
//! Result trả về Json
echo json_encode($result);
