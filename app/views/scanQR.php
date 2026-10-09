<?php
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__, 2));
}
if (!defined('BASE_URL')) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
    define('BASE_URL', rtrim($protocol . $_SERVER['HTTP_HOST'] . $scriptDir, '/'));
}
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once ROOT_PATH . '/app/core/Language.php';
Language::init();
require_once ROOT_PATH . '/app/core/AuthHelper.php';

$backUrl = BASE_URL . '/index.php?url=bobin/listBobinView_QC';
if (!empty($_SERVER['HTTP_REFERER']) && str_contains($_SERVER['HTTP_REFERER'], 'WEB_BOBIN')) {
    $backUrl = $_SERVER['HTTP_REFERER'];
}
?>
<!DOCTYPE html>
<html lang="<?= Language::getCurrentLanguage() ?>">

<head>
    <meta charset="UTF-8">
    <title><?= __('qr_scanner_title') ?> - WEB_BOBIN</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Phông chữ hệ thống Local Offline -->
    <link rel="stylesheet" href="/WEB_BOBIN/public/assets/css/i18n.css?v=<?= time() ?>">
    <link rel="stylesheet" href="/WEB_BOBIN/public/assets/css/scanQR.css?v=<?= time() ?>">
    <script src="/WEB_BOBIN/public/assets/js/i18n.js?v=<?= time() ?>"></script>
    <script src="/WEB_BOBIN/public/assets/js/toast.js?v=<?= time() ?>"></script>
    <script src="/WEB_BOBIN/public/assets/js/html5-qrcode.min.js"></script>
    <script src="/WEB_BOBIN/public/assets/js/qrScannerHelper.js?v=<?= time() ?>"></script>
    <style>
        body, button, input, select, textarea {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        }

        body {
            background-color: #f1f5f9;
            margin: 0;
            padding: 24px 16px;
            color: #1e293b;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-start;
        }

        .scan-page-wrap {
            width: 100%;
            max-width: 520px;
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .scan-page-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #ffffff;
            padding: 14px 18px;
            border-radius: 14px;
            box-shadow: 0 4px 14px rgba(15, 23, 42, 0.05);
            border: 1px solid #e2e8f0;
        }

        .scan-title-group {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .scan-title-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            box-shadow: 0 4px 10px rgba(2, 132, 199, 0.25);
        }

        .scan-title-text h1 {
            font-size: 16px;
            font-weight: 700;
            margin: 0;
            color: #0f172a;
        }

        .scan-title-text p {
            font-size: 12px;
            color: #64748b;
            margin: 2px 0 0;
        }

        .scan-btn-back {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 14px;
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            color: #334155;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            transition: all 0.15s ease;
        }

        .scan-btn-back:hover {
            background: #e2e8f0;
            color: #0f172a;
        }

        .scan-form-card {
            background: #ffffff;
            border-radius: 14px;
            padding: 18px 20px;
            box-shadow: 0 4px 14px rgba(15, 23, 42, 0.05);
            border: 1px solid #e2e8f0;
            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        .scan-target-select-wrap {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .scan-target-label {
            font-size: 12px;
            font-weight: 600;
            color: #475569;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }

        .scan-target-select {
            padding: 9px 12px;
            border-radius: 8px;
            border: 1.5px solid #cbd5e1;
            font-size: 13px;
            color: #1e293b;
            background: #f8fafc;
            outline: none;
            transition: border-color 0.15s ease;
        }

        .scan-target-select:focus {
            border-color: #0284c7;
            background: #ffffff;
        }

        .scan-input-group {
            display: flex;
            gap: 8px;
        }

        .scan-input-wrap {
            position: relative;
            flex: 1;
        }

        .scan-input-wrap input {
            width: 100%;
            padding: 11px 14px;
            border-radius: 8px;
            border: 1.5px solid #cbd5e1;
            font-size: 14px;
            font-weight: 600;
            color: #0f172a;
            box-sizing: border-box;
            outline: none;
            transition: border-color 0.15s ease, box-shadow 0.15s ease;
        }

        .scan-input-wrap input:focus {
            border-color: #0284c7;
            box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.15);
        }

        .scan-btn-submit {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 11px 20px;
            background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);
            color: #ffffff;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 4px 10px rgba(2, 132, 199, 0.25);
            transition: all 0.15s ease;
            white-space: nowrap;
        }

        .scan-btn-submit:hover {
            background: linear-gradient(135deg, #0369a1 0%, #075985 100%);
            transform: translateY(-1px);
        }

        .scan-trigger-bar {
            display: flex;
            justify-content: center;
        }

        .scan-btn-toggle {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 22px;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: #ffffff;
            border: none;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.25);
            transition: all 0.15s ease;
        }

        .scan-btn-toggle:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(16, 185, 129, 0.35);
        }
    </style>
</head>

<body>

    <div class="scan-page-wrap">
        <!-- HEADER TRANG -->
        <div class="scan-page-header">
            <div class="scan-title-group">
                <div class="scan-title-icon">📷</div>
                <div class="scan-title-text">
                    <h1 data-i18n="qr_scanner_title"><?= __('qr_scanner_title') ?></h1>
                    <p data-i18n="qr_scanner_hint"><?= __('qr_scanner_hint') ?></p>
                </div>
            </div>
            <a href="<?= htmlspecialchars($backUrl) ?>" class="scan-btn-back" title="Quay lại">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <line x1="19" y1="12" x2="5" y2="12"></line>
                    <polyline points="12 19 5 12 12 5"></polyline>
                </svg>
                <span data-i18n="btn_back"><?= __('btn_back') ?></span>
            </a>
        </div>

        <!-- VÙNG HIỂN THỊ CAMERA QUÉT QR -->
        <div id="qr-reader"></div>

        <!-- FORM TÌM KIẾM THEO MÃ QR -->
        <div class="scan-form-card">
            <form action="/WEB_BOBIN/public/index.php" method="GET" id="searchForm">
                <div class="scan-target-select-wrap">
                    <label class="scan-target-label" data-i18n="search">Mục tiêu tra cứu:</label>
                    <select name="url" id="targetUrl" class="scan-target-select">
                        <option value="bobin/listBobinView_QC">Kiểm tra QC (QC Check)</option>
                        <option value="bobin/listBobinDetailView">Chi tiết danh sách Bobin</option>
                        <option value="bobin/listBobinHistoryView">Lịch sử Bobin</option>
                        <option value="bobin/windingView">Xác nhận Cuộn (Winding)</option>
                    </select>
                </div>

                <div class="scan-input-group" style="margin-top: 12px;">
                    <div class="scan-input-wrap">
                        <input type="text" id="searchInput" name="keyword"
                            placeholder="<?= __('ph_qr_scan_result') ?>"
                            data-i18n-ph="ph_qr_scan_result" required autocomplete="off">
                    </div>
                    <button type="submit" id="btnSubmit" class="scan-btn-submit">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <circle cx="11" cy="11" r="8"></circle>
                            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                        </svg>
                        <span data-i18n="search"><?= __('search') ?></span>
                    </button>
                </div>
            </form>

            <div class="scan-trigger-bar" id="reopenTrigger" style="display:none;">
                <button type="button" class="scan-btn-toggle" id="btnReopenScan">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="3" width="7" height="7"></rect>
                        <rect x="14" y="3" width="7" height="7"></rect>
                        <rect x="14" y="14" width="7" height="7"></rect>
                        <path d="M3 14h7v7H3z"></path>
                    </svg>
                    <span data-i18n="btn_scan_qr"><?= __('btn_scan_qr') ?></span>
                </button>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const searchInput = document.getElementById('searchInput');
            const searchForm = document.getElementById('searchForm');
            const reopenTrigger = document.getElementById('reopenTrigger');
            const btnReopenScan = document.getElementById('btnReopenScan');

            function startCamera() {
                if (reopenTrigger) reopenTrigger.style.display = 'none';

                QRScannerHelper.start('qr-reader', {
                    onSuccess: function (decodedText) {
                        if (searchInput) {
                            searchInput.value = decodedText;
                            searchInput.dispatchEvent(new Event('input', { bubbles: true }));
                            searchInput.dispatchEvent(new Event('change', { bubbles: true }));
                        }
                        if (reopenTrigger) reopenTrigger.style.display = 'flex';
                        if (searchForm) {
                            setTimeout(() => {
                                searchForm.submit();
                            }, 500);
                        }
                    },
                    onClose: function () {
                        if (reopenTrigger) reopenTrigger.style.display = 'flex';
                    }
                });
            }

            // Tự động mở camera khi vào trang
            startCamera();

            btnReopenScan?.addEventListener('click', function () {
                startCamera();
            });
        });
    </script>

</body>

</html>