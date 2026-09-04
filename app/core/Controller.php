<?php
// 1 kết nối duy nhất
// PDO
// Dùng lại cho mọi Model
//! Controller có 2 chức năng là render view và trả về json
class Controller
{
    protected function view(string $view, array $data = []): void
    {
        extract($data);
        require ROOT_PATH . '/app/views/' . $view . '.php';
    }

    protected function json($data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    
}