<?php
require 'config/database.php';

$type = $_GET['type'] ?? '';
$query = $_GET['query'] ?? '';

$result = [];

if ($type && $query) {
    $queryParam = "%$query%";

    try {
        if ($type == 'bobin') {
            $stmt = $pdo->prepare("SELECT code, type FROM bobin_list WHERE code LIKE :q LIMIT 10");
            $stmt->execute(['q' => $queryParam]);
            $result = $stmt->fetchAll();

        } elseif ($type == 'employee') {
            $stmt = $pdo->prepare("SELECT employee_code, name FROM employees WHERE employee_code LIKE :q OR name LIKE :q LIMIT 10");
            $stmt->execute(['q' => $queryParam]);
            $result = $stmt->fetchAll();

        } elseif ($type == 'product') {
            $stmt = $pdo->prepare("SELECT product_code FROM products WHERE product_code LIKE :q LIMIT 10");
            $stmt->execute(['q' => $queryParam]);
            $rows = $stmt->fetchAll();
            // chuyển mảng associative thành array string
            $result = array_column($rows, 'product_code');
        }

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
        exit;
    }
}

header('Content-Type: application/json');
echo json_encode($result);
