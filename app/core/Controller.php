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
        if (class_exists('Language')) {
            Language::setDeferBootstrapToHead(true);
        }
        ob_start();
        require ROOT_PATH . '/app/views/' . $view . '.php';
        $html = ob_get_clean();

        if (class_exists('Language')) {
            Language::setDeferBootstrapToHead(false);
            if (stripos($html, 'i18n.js') !== false && strpos($html, 'window.__DEFAULT_I18N__') === false) {
                $bootstrap = Language::renderJsBootstrap(true);
                $html = preg_replace_callback(
                    '/(<script[^>]+src=["\'][^"\']*i18n\.js[^"\']*["\'][^>]*>\s*<\/script>)/i',
                    fn($m) => $bootstrap . "\n    " . $m[1],
                    $html,
                    1
                );
            }
        }

        if (!defined('WEB_BOBIN_PERIODIC_LOGOUT_RENDERED') && !empty($_SESSION['user']['id']) && $view !== 'loginView') {
            $modalFile = ROOT_PATH . '/app/views/components/periodicLogoutModal.php';
            if (file_exists($modalFile)) {
                ob_start();
                require $modalFile;
                $modalHtml = ob_get_clean();
                if ($modalHtml !== '') {
                    if (stripos($html, '</body>') !== false) {
                        $html = str_ireplace('</body>', $modalHtml . "\n</body>", $html);
                    } else {
                        $html .= $modalHtml;
                    }
                }
            }
        }

        echo $html;
    }

    protected function json($data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    
}