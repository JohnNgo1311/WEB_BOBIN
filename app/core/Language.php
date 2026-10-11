<?php

/**
 * Class Language - Hệ thống Đa Ngôn Ngữ Tập Trung (Single Source of Truth)
 * - Nguồn từ điển mặc định duy nhất: config/default_translations.json
 * - Nguồn từ điển tùy chỉnh động:   config/custom_translations.json
 * - Tự động đồng bộ 100% giữa PHP Backend (__) và JS Frontend (window.t)
 */
class Language
{
    public const SUPPORTED = [
        'vi' => [
            'code'  => 'vi',
            'name'  => 'Tiếng Việt',
            'flag'  => '🇻🇳',
            'short' => 'VI'
        ],
        'en' => [
            'code'  => 'en',
            'name'  => 'English',
            'flag'  => '🇬🇧',
            'short' => 'EN'
        ],
        'ja' => [
            'code'  => 'ja',
            'name'  => '日本語',
            'flag'  => '🇯🇵',
            'short' => 'JA'
        ]
    ];

    private static ?string $currentLang = null;
    private static ?array $defaultDictionary = null;
    private static ?string $rawDefaultJson = null;
    private static ?array $dictionary = null;
    private static bool $jsBootstrapped = false;
    private static bool $deferBootstrapToHead = false;

    public static function setDeferBootstrapToHead(bool $defer): void
    {
        self::$deferBootstrapToHead = $defer;
    }

    public static function init(): string
    {
        if (self::$currentLang !== null) {
            return self::$currentLang;
        }

        if (!empty($_GET['lang']) && array_key_exists(strtolower($_GET['lang']), self::SUPPORTED)) {
            $lang = strtolower($_GET['lang']);
            self::set($lang);
            return $lang;
        }

        if (session_status() === PHP_SESSION_ACTIVE && !empty($_SESSION['lang']) && array_key_exists($_SESSION['lang'], self::SUPPORTED)) {
            self::$currentLang = $_SESSION['lang'];
            return self::$currentLang;
        }

        if (!empty($_COOKIE['app_lang']) && array_key_exists(strtolower($_COOKIE['app_lang']), self::SUPPORTED)) {
            $lang = strtolower($_COOKIE['app_lang']);
            if (session_status() === PHP_SESSION_ACTIVE) {
                $_SESSION['lang'] = $lang;
            }
            self::$currentLang = $lang;
            return $lang;
        }

        self::$currentLang = 'vi';
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION['lang'] = 'vi';
        }
        return self::$currentLang;
    }

    public static function set(string $lang): void
    {
        $lang = strtolower(trim($lang));
        if (!array_key_exists($lang, self::SUPPORTED)) {
            $lang = 'vi';
        }

        self::$currentLang = $lang;

        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION['lang'] = $lang;
        }

        if (!headers_sent()) {
            setcookie('app_lang', $lang, [
                'expires'  => time() + 31536000,
                'path'     => '/',
                'httponly' => false,
                'samesite' => 'Lax'
            ]);
        }
    }

    public static function getCurrent(): string
    {
        return self::$currentLang ?? self::init();
    }

    public static function getCurrentLanguage(): string
    {
        return self::getCurrent();
    }

    public static function getCurrentInfo(): array
    {
        $code = self::getCurrent();
        return self::SUPPORTED[$code] ?? self::SUPPORTED['vi'];
    }

    public static function getDefaultFilePath(): string
    {
        $base = defined('ROOT_PATH') ? ROOT_PATH : dirname(__DIR__, 2);
        return $base . '/config/default_translations.json';
    }

    public static function getCustomFilePath(): string
    {
        $base = defined('ROOT_PATH') ? ROOT_PATH : dirname(__DIR__, 2);
        return $base . '/config/custom_translations.json';
    }

    /**
     * Đọc từ điển gốc chuẩn từ config/default_translations.json (Single Source of Truth)
     */
    public static function getDefaultDictionary(): array
    {
        if (self::$defaultDictionary !== null) {
            return self::$defaultDictionary;
        }

        $filePath = self::getDefaultFilePath();
        if (!file_exists($filePath)) {
            self::$defaultDictionary = ['vi' => [], 'en' => [], 'ja' => []];
            return self::$defaultDictionary;
        }

        $content = file_get_contents($filePath);
        if ($content === false || trim($content) === '') {
            self::$defaultDictionary = ['vi' => [], 'en' => [], 'ja' => []];
            return self::$defaultDictionary;
        }

        self::$rawDefaultJson = $content;
        $decoded = json_decode($content, true);
        self::$defaultDictionary = is_array($decoded) ? $decoded : ['vi' => [], 'en' => [], 'ja' => []];
        return self::$defaultDictionary;
    }

    /**
     * Trả về chuỗi JSON của từ điển mặc định (tối ưu tốc độ, không cần json_encode lại nếu đã đọc từ file)
     */
    public static function getJsonDefaultDictionary(): string
    {
        if (self::$rawDefaultJson !== null) {
            return self::$rawDefaultJson;
        }
        $filePath = self::getDefaultFilePath();
        if (file_exists($filePath)) {
            $content = file_get_contents($filePath);
            if ($content !== false && trim($content) !== '') {
                self::$rawDefaultJson = $content;
                return $content;
            }
        }
        return json_encode(self::getDefaultDictionary(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}';
    }

    /**
     * Đọc từ điển tùy chỉnh động từ config/custom_translations.json
     */
    public static function getCustomDictionary(): array
    {
        $filePath = self::getCustomFilePath();
        if (!file_exists($filePath)) {
            return [];
        }
        $content = file_get_contents($filePath);
        if ($content === false || trim($content) === '') {
            return [];
        }
        $decoded = json_decode($content, true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Lưu từ điển tùy chỉnh vào config/custom_translations.json
     */
    public static function saveCustomDictionary(array $data): bool
    {
        $dir = dirname(self::getCustomFilePath());
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $cleanData = [
            'vi' => [],
            'en' => [],
            'ja' => []
        ];

        foreach (['vi', 'en', 'ja'] as $lang) {
            if (isset($data[$lang]) && is_array($data[$lang])) {
                foreach ($data[$lang] as $k => $v) {
                    $cleanKey = trim((string)$k);
                    $cleanVal = trim((string)$v);
                    if ($cleanKey !== '' && $cleanVal !== '') {
                        $cleanData[$lang][$cleanKey] = $cleanVal;
                    }
                }
            }
        }

        $json = json_encode($cleanData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $result = file_put_contents(self::getCustomFilePath(), $json, LOCK_EX);

        if ($result !== false) {
            self::$dictionary = null; // Reset cache để nạp lại bản dịch mới
            clearstatcache(true, self::getCustomFilePath());
            return true;
        }

        return false;
    }

    /**
     * Mã băm phiên bản từ điển (tự động thay đổi khi default_translations.json hoặc custom_translations.json cập nhật)
     */
    public static function getDictionaryHash(): string
    {
        $defFile = self::getDefaultFilePath();
        $cusFile = self::getCustomFilePath();

        $defStat = file_exists($defFile) ? (filemtime($defFile) . ':' . filesize($defFile)) : '0:0';
        $cusStat = file_exists($cusFile) ? (filemtime($cusFile) . ':' . filesize($cusFile)) : '0:0';

        return substr(md5($defStat . '|' . $cusStat), 0, 16);
    }

    /**
     * Tra cứu chuỗi dịch theo key và ngôn ngữ hiện hành
     */
    public static function get(string $key, ?string $default = null): string
    {
        if (self::$dictionary === null) {
            self::loadDictionary();
        }

        $lang = self::getCurrent();
        if (isset(self::$dictionary[$lang][$key])) {
            return self::$dictionary[$lang][$key];
        }

        // Kiểm tra alias: btn_confirm <-> confirm, btn_cancel <-> cancel
        $aliasKey = match ($key) {
            'btn_confirm' => 'confirm',
            'confirm'     => 'btn_confirm',
            'btn_cancel'  => 'cancel',
            'cancel'      => 'btn_cancel',
            default       => null
        };
        if ($aliasKey !== null && isset(self::$dictionary[$lang][$aliasKey])) {
            return self::$dictionary[$lang][$aliasKey];
        }

        if (isset(self::$dictionary['vi'][$key])) {
            return self::$dictionary['vi'][$key];
        }

        if ($aliasKey !== null && isset(self::$dictionary['vi'][$aliasKey])) {
            return self::$dictionary['vi'][$aliasKey];
        }

        return $default ?? $key;
    }

    /**
     * Trả về mảng từ điển đã gộp (Default + Custom)
     */
    public static function getMergedDictionary(): array
    {
        if (self::$dictionary === null) {
            self::loadDictionary();
        }
        return self::$dictionary;
    }

    /**
     * Trả về chuỗi JSON của toàn bộ từ điển đã gộp (Default + Custom)
     */
    public static function getJsonDictionary(): string
    {
        if (self::$dictionary === null) {
            self::loadDictionary();
        }
        return json_encode(self::$dictionary, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}';
    }

    /**
     * Xuất khối <script> khởi tạo biến toàn cục window.__DEFAULT_I18N__, window.__CUSTOM_I18N__ và window.__I18N_HASH__
     * Đảm bảo chỉ xuất tối đa 1 lần trên mỗi HTTP request để tránh trùng lặp dữ liệu trong HTML.
     */
    public static function renderJsBootstrap(bool $force = false): string
    {
        if (!$force && (self::$jsBootstrapped || self::$deferBootstrapToHead)) {
            return '';
        }
        self::$jsBootstrapped = true;

        $hashJson    = json_encode(self::getDictionaryHash());
        $defaultJson = self::getJsonDefaultDictionary();
        $customJson  = json_encode(self::getCustomDictionary(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}';

        return "<script>\n"
            . "    window.__I18N_HASH__ = {$hashJson};\n"
            . "    window.__DEFAULT_I18N__ = {$defaultJson};\n"
            . "    window.__CUSTOM_I18N__ = {$customJson};\n"
            . "    if (typeof window.loadAndMergeCustomTranslations === 'function') {\n"
            . "        window.loadAndMergeCustomTranslations(window.__CUSTOM_I18N__, window.__DEFAULT_I18N__, window.__I18N_HASH__);\n"
            . "    }\n"
            . "</script>";
    }

    /**
     * Nạp và hợp nhất từ điển mặc định (config/default_translations.json) + tùy biến (config/custom_translations.json)
     */
    private static function loadDictionary(): void
    {
        self::$dictionary = self::getDefaultDictionary();

        $custom = self::getCustomDictionary();
        if (!empty($custom)) {
            foreach (['vi', 'en', 'ja'] as $lang) {
                if (!empty($custom[$lang]) && is_array($custom[$lang])) {
                    foreach ($custom[$lang] as $k => $v) {
                        self::$dictionary[$lang][$k] = (string)$v;
                        if ($k === 'btn_cancel' && !isset($custom[$lang]['cancel'])) {
                            self::$dictionary[$lang]['cancel'] = (string)$v;
                        }
                        if ($k === 'btn_confirm' && !isset($custom[$lang]['confirm'])) {
                            self::$dictionary[$lang]['confirm'] = (string)$v;
                        }
                        if ($k === 'cancel' && !isset($custom[$lang]['btn_cancel'])) {
                            self::$dictionary[$lang]['btn_cancel'] = (string)$v;
                        }
                        if ($k === 'confirm' && !isset($custom[$lang]['btn_confirm'])) {
                            self::$dictionary[$lang]['btn_confirm'] = (string)$v;
                        }
                    }
                }
            }
        }
    }
}

if (!function_exists('__')) {
    function __(string $key, ?string $default = null): string
    {
        return Language::get($key, $default);
    }
}
