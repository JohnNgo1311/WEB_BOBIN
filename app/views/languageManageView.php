<?php
// File: app/views/languageManageView.php
// Trang Quản trị Cấu hình Từ điển Đa Ngôn ngữ Động - SMC Extrusion (VN) (TASK-012)

$keysData     = $data['keysData'] ?? [];
$customDict   = $data['customDict'] ?? [];
$pendingCount = $data['pendingCount'] ?? 0;
$userRole     = $data['userRole'] ?? ($_SESSION['user']['role'] ?? '');
$userName     = $data['userName'] ?? ($_SESSION['user']['employee_name'] ?? '');

$totalKeys = count($keysData);
$customKeysCount = count($customDict['vi'] ?? []);
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= __('lang_page_title') ?> | SMC WEB_BOBIN</title>

    <!-- CSS Offline Local -->
    <link rel="stylesheet" href="/WEB_BOBIN/public/assets/css/i18n.css?v=<?= time() ?>">
    <link rel="stylesheet" href="/WEB_BOBIN/public/assets/css/sidebar.css?v=<?= time() ?>">
    <link rel="stylesheet" href="/WEB_BOBIN/public/assets/css/languageManage.css?v=<?= time() ?>">
    <link rel="icon" href="data:,">

    <script src="/WEB_BOBIN/public/assets/js/i18n.js?v=<?= time() ?>"></script>
</head>

<body>
    <!-- Vertical Left Sidebar -->
    <?php require ROOT_PATH . '/app/views/components/sidebar.php'; ?>
    <?php require ROOT_PATH . '/app/views/components/header.php'; ?>

    <!-- Main Content Wrapper -->
    <main class="lang-page-wrapper">
        <!-- Hero Header -->
        <header class="lang-hero">
            <div class="lang-hero-content">
                <h1>
                    <span>🌐</span>
                    <span data-i18n="lang_page_title"><?= __('lang_page_title') ?></span>
                </h1>
                <p data-i18n="lang_page_subtitle"><?= __('lang_page_subtitle') ?></p>
            </div>
            <div class="lang-hero-badge">
                <span>⚙️</span>
                <span data-i18n="lang_badge_custom">Dynamic I18n Engine</span>
            </div>
        </header>

        <!-- Stats Strip -->
        <section class="lang-stats-strip">
            <div class="lang-stat-card">
                <div class="lang-stat-icon">📚</div>
                <div class="lang-stat-content">
                    <span class="lang-stat-val" id="statTotalKeys"><?= $totalKeys ?></span>
                    <span class="lang-stat-label" data-i18n="lang_stat_total">Tổng số mục dịch thuật</span>
                </div>
            </div>
            <div class="lang-stat-card">
                <div class="lang-stat-icon" style="background:#fef3c7; color:#b45309;">✏️</div>
                <div class="lang-stat-content">
                    <span class="lang-stat-val" id="statCustomKeys"><?= $customKeysCount ?></span>
                    <span class="lang-stat-label" data-i18n="lang_stat_custom">Đã tùy chỉnh riêng</span>
                </div>
            </div>
            <div class="lang-stat-card">
                <div class="lang-stat-icon" style="background:#ecfdf5; color:#059669;">🇻🇳 🇬🇧 🇯🇵</div>
                <div class="lang-stat-content">
                    <span class="lang-stat-val">3</span>
                    <span class="lang-stat-label" data-i18n="lang_stat_langs">Ngôn ngữ đồng bộ (VI/EN/JA)</span>
                </div>
            </div>
        </section>

        <!-- Toolbar & Filter -->
        <section class="lang-toolbar-card">
            <div class="lang-filter-group">
                <div class="lang-search-wrap">
                    <span class="lang-search-icon">🔍</span>
                    <input type="text" id="langSearchInput" class="lang-search-input"
                        placeholder="<?= __('lang_search_ph') ?>" data-i18n-ph="lang_search_ph" data-i18n="[placeholder]lang_search_ph">
                </div>

                <select id="langFilterScope" class="lang-select-filter">
                    <option value="all" data-i18n="lang_filter_all">-- Tất cả từ khóa --</option>
                    <option value="custom" data-i18n="lang_filter_custom">Chỉ xem mục đã tùy chỉnh</option>
                    <option value="default" data-i18n="lang_filter_default">Chỉ xem mục mặc định</option>
                </select>
            </div>

            <div class="lang-toolbar-actions">
                <button type="button" class="btn-lang-action btn-lang-add" id="btnOpenAddModal">
                    <span>➕</span>
                    <span data-i18n="lang_btn_add_key"><?= __('lang_btn_add_key') ?></span>
                </button>

                <button type="button" class="btn-lang-action btn-lang-save" id="btnSaveTranslations">
                    <span>💾</span>
                    <span data-i18n="lang_btn_save_all"><?= __('lang_btn_save_all') ?></span>
                </button>
            </div>
        </section>

        <!-- Translation Grid Table -->
        <section class="lang-table-card">
            <div class="lang-table-header-box">
                <div class="lang-table-title">
                    <span>📝</span>
                    <span data-i18n="lang_table_title"><?= __('lang_table_title') ?></span>
                    <span class="lang-badge-counter" id="visibleCounter"><?= $totalKeys ?></span>
                </div>
                <div>
                    <button type="button" class="btn-lang-action btn-lang-reset-all" id="btnResetAll"
                        title="Khôi phục toàn bộ từ điển về mặc định ban đầu">
                        <span>🔄</span>
                        <span data-i18n="lang_btn_reset_all"><?= __('lang_btn_reset_all') ?></span>
                    </button>
                </div>
            </div>

            <div class="lang-table-scroll">
                <table class="lang-table" id="langTable">
                    <thead>
                        <tr>
                            <th style="width: 240px;" data-i18n="lang_col_key"><?= __('lang_col_key') ?></th>
                            <th data-i18n="lang_col_vi">🇻🇳 Tiếng Việt (VI)</th>
                            <th data-i18n="lang_col_en">🇬🇧 English (EN)</th>
                            <th data-i18n="lang_col_ja">🇯🇵 日本語 (JA)</th>
                            <th style="width: 90px; text-align: center;" data-i18n="lang_col_action">
                                <?= __('lang_col_action') ?></th>
                        </tr>
                    </thead>
                    <tbody id="langTableBody">
                        <?php foreach ($keysData as $key => $vals): ?>
                            <tr class="lang-row" data-key="<?= htmlspecialchars($key) ?>"
                                data-custom="<?= $vals['is_custom'] ? '1' : '0' ?>">
                                <td>
                                    <span class="lang-key-col" title="<?= htmlspecialchars($key) ?>">
                                        <?= htmlspecialchars($key) ?>
                                    </span>
                                    <?php if ($vals['is_custom']): ?>
                                        <span class="lang-custom-tag"
                                            data-i18n="lang_tag_custom"><?= __('lang_tag_custom') ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <input type="text" class="lang-cell-input input-vi" data-lang="vi"
                                        data-key="<?= htmlspecialchars($key) ?>"
                                        data-orig="<?= htmlspecialchars($vals['vi']) ?>"
                                        value="<?= htmlspecialchars($vals['vi']) ?>">
                                </td>
                                <td>
                                    <input type="text" class="lang-cell-input input-en" data-lang="en"
                                        data-key="<?= htmlspecialchars($key) ?>"
                                        data-orig="<?= htmlspecialchars($vals['en']) ?>"
                                        value="<?= htmlspecialchars($vals['en']) ?>">
                                </td>
                                <td>
                                    <input type="text" class="lang-cell-input input-ja" data-lang="ja"
                                        data-key="<?= htmlspecialchars($key) ?>"
                                        data-orig="<?= htmlspecialchars($vals['ja']) ?>"
                                        value="<?= htmlspecialchars($vals['ja']) ?>">
                                </td>
                                <td style="text-align: center;">
                                    <button type="button" class="btn-key-reset" data-key="<?= htmlspecialchars($key) ?>"
                                        title="Khôi phục mặc định">
                                        ↺
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <!-- Modal Thêm Key mới -->
    <div class="lang-modal-overlay" id="addKeyModal">
        <div class="lang-modal">
            <div class="lang-modal-header">
                <h3>
                    <span>➕</span>
                    <span data-i18n="lang_modal_add_title"><?= __('lang_modal_add_title') ?></span>
                </h3>
                <button type="button" class="lang-modal-close" id="btnCloseModal">✕</button>
            </div>
            <div class="lang-modal-body">
                <div class="lang-form-group">
                    <label data-i18n="lang_modal_key_label"><?= __('lang_modal_key_label') ?> <span
                            style="color:#ef4444;">*</span></label>
                    <input type="text" id="newKeyName" placeholder="<?= __('lang_ph_key_name') ?>" data-i18n-ph="lang_ph_key_name" autocomplete="off">
                </div>
                <div class="lang-form-group">
                    <label data-i18n="lang_modal_vi_label">🇻🇳 Bản dịch Tiếng Việt: <span
                            style="color:#ef4444;">*</span></label>
                    <input type="text" id="newKeyVi" placeholder="<?= __('lang_ph_vi_content') ?>" data-i18n-ph="lang_ph_vi_content">
                </div>
                <div class="lang-form-group">
                    <label data-i18n="lang_modal_en_label">🇬🇧 Bản dịch English: <span
                            style="color:#ef4444;">*</span></label>
                    <input type="text" id="newKeyEn" placeholder="<?= __('lang_ph_en_content') ?>" data-i18n-ph="lang_ph_en_content">
                </div>
                <div class="lang-form-group">
                    <label data-i18n="lang_modal_ja_label">🇯🇵 Bản dịch 日本語: <span
                            style="color:#ef4444;">*</span></label>
                    <input type="text" id="newKeyJa" placeholder="<?= __('lang_ph_ja_content') ?>" data-i18n-ph="lang_ph_ja_content">
                </div>
            </div>
            <div class="lang-modal-footer">
                <button type="button" class="btn-lang-action btn-lang-add" id="btnCancelModal"
                    data-i18n="btn_cancel"><?= __('btn_cancel') ?></button>
                <button type="button" class="btn-lang-action btn-lang-save" id="btnSubmitNewKey">
                    <span>➕</span>
                    <span data-i18n="lang_btn_confirm_add"><?= __('lang_btn_confirm_add') ?></span>
                </button>
            </div>
        </div>
    </div>

    <!-- Floating Toast Notification -->
    <div class="lang-toast" id="langToast">
        <span id="toastIcon">✅</span>
        <span id="toastMsg">Thao tác thành công</span>
    </div>

    <script>
        (function() {
            const searchInput = document.getElementById('langSearchInput');
            const filterScope = document.getElementById('langFilterScope');
            const rows = document.querySelectorAll('.lang-row');
            const visibleCounter = document.getElementById('visibleCounter');
            const btnSave = document.getElementById('btnSaveTranslations');
            const btnResetAll = document.getElementById('btnResetAll');
            const toast = document.getElementById('langToast');
            const toastIcon = document.getElementById('toastIcon');
            const toastMsg = document.getElementById('toastMsg');

            // Modal elements
            const modal = document.getElementById('addKeyModal');
            const btnOpenModal = document.getElementById('btnOpenAddModal');
            const btnCloseModal = document.getElementById('btnCloseModal');
            const btnCancelModal = document.getElementById('btnCancelModal');
            const btnSubmitNew = document.getElementById('btnSubmitNewKey');
            const newKeyInput = document.getElementById('newKeyName');
            const newViInput = document.getElementById('newKeyVi');
            const newEnInput = document.getElementById('newKeyEn');
            const newJaInput = document.getElementById('newKeyJa');

            function showToast(message, isSuccess = true) {
                const type = isSuccess ? 'success' : 'error';
                if (window.Toast && window.Toast.show) {
                    window.Toast.show(message, type);
                }
                if (toastMsg && toast) {
                    toastMsg.textContent = message;
                    toastIcon.textContent = isSuccess ? '✅' : '❌';
                    toast.className = 'lang-toast show ' + (isSuccess ? 'toast-success' : 'toast-error');
                    setTimeout(() => {
                        toast.classList.remove('show');
                    }, 3500);
                }
            }

            // 1. Tìm kiếm và lọc
            function applyFilter() {
                const query = searchInput.value.trim().toLowerCase();
                const scope = filterScope.value;
                let count = 0;

                rows.forEach(r => {
                    const key = r.getAttribute('data-key').toLowerCase();
                    const isCustom = r.getAttribute('data-custom') === '1';

                    const viVal = (r.querySelector('.input-vi')?.value || '').toLowerCase();
                    const enVal = (r.querySelector('.input-en')?.value || '').toLowerCase();
                    const jaVal = (r.querySelector('.input-ja')?.value || '').toLowerCase();

                    let matchText = (key.includes(query) || viVal.includes(query) || enVal.includes(query) ||
                        jaVal.includes(query));
                    let matchScope = true;

                    if (scope === 'custom') matchScope = isCustom;
                    if (scope === 'default') matchScope = !isCustom;

                    if (matchText && matchScope) {
                        r.style.display = '';
                        count++;
                    } else {
                        r.style.display = 'none';
                    }
                });

                if (visibleCounter) visibleCounter.textContent = count;
            }

            searchInput.addEventListener('input', applyFilter);
            filterScope.addEventListener('change', applyFilter);

            // Đánh dấu input bị thay đổi
            document.querySelectorAll('.lang-cell-input').forEach(inp => {
                inp.addEventListener('input', function() {
                    const orig = this.getAttribute('data-orig');
                    if (this.value !== orig) {
                        this.classList.add('modified');
                    } else {
                        this.classList.remove('modified');
                    }
                });
            });

            // 2. Lưu toàn bộ cấu hình từ điển
            btnSave.addEventListener('click', async function() {
                btnSave.disabled = true;
                btnSave.style.opacity = '0.6';

                const payload = [];
                document.querySelectorAll('.lang-row').forEach(row => {
                    const key = row.getAttribute('data-key');
                    const isCustom = row.getAttribute('data-custom') === '1';
                    const viInput = row.querySelector('.input-vi');
                    const enInput = row.querySelector('.input-en');
                    const jaInput = row.querySelector('.input-ja');

                    const isModified = (viInput && (viInput.classList.contains('modified') || viInput.value !== viInput.getAttribute('data-orig')))
                                    || (enInput && (enInput.classList.contains('modified') || enInput.value !== enInput.getAttribute('data-orig')))
                                    || (jaInput && (jaInput.classList.contains('modified') || jaInput.value !== jaInput.getAttribute('data-orig')));

                    // Chỉ gửi lưu những key đã được tùy chỉnh hoặc có sự sửa đổi
                    if (isCustom || isModified) {
                        const vi = viInput ? viInput.value.trim() : '';
                        const en = enInput ? enInput.value.trim() : '';
                        const ja = jaInput ? jaInput.value.trim() : '';
                        payload.push({
                            key,
                            vi,
                            en,
                            ja
                        });
                    }
                });

                try {
                    const res = await fetch('/WEB_BOBIN/public/index.php?url=employee/updateTranslations', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify({
                            translations: payload,
                            replace_all: true
                        })
                    });

                    const data = await res.json();
                    if (data.success) {
                        if (data.custom_dict) {
                            try {
                                localStorage.setItem('webbobin_custom_i18n', JSON.stringify(data.custom_dict));
                            } catch (e) {}
                            if (typeof window.loadAndMergeCustomTranslations === 'function') {
                                window.loadAndMergeCustomTranslations(data.custom_dict);
                            }
                        }
                        if (window.Toast && window.Toast.flash) {
                            window.Toast.flash(data.message || (window.t ? window.t('toast_saved_success') : 'Lưu thành công!'), 'success');
                        }
                        showToast(data.message || 'Lưu thành công!', true);
                        setTimeout(() => {
                            window.location.reload();
                        }, 800);
                    } else {
                        showToast(data.error || 'Lỗi khi lưu từ điển', false);
                        btnSave.disabled = false;
                        btnSave.style.opacity = '1';
                    }
                } catch (err) {
                    showToast('Không thể kết nối đến máy chủ: ' + err.message, false);
                    btnSave.disabled = false;
                    btnSave.style.opacity = '1';
                }
            });

            // 3. Khôi phục 1 key về mặc định
            document.querySelectorAll('.btn-key-reset').forEach(btn => {
                btn.addEventListener('click', async function() {
                    const key = this.getAttribute('data-key');
                    if (!confirm(
                            `Bạn có chắc muốn khôi phục từ khóa '${key}' về bản dịch mặc định ban đầu không?`
                        )) {
                        return;
                    }

                    try {
                        const res = await fetch(
                            '/WEB_BOBIN/public/index.php?url=employee/resetTranslations', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest'
                                },
                                body: JSON.stringify({
                                    key: key
                                })
                            });
                        const data = await res.json();
                        if (data.success) {
                            if (data.custom_dict) {
                                try {
                                    localStorage.setItem('webbobin_custom_i18n', JSON.stringify(data.custom_dict));
                                } catch (e) {}
                                if (typeof window.loadAndMergeCustomTranslations === 'function') {
                                    window.loadAndMergeCustomTranslations(data.custom_dict);
                                }
                            }
                            if (window.Toast && window.Toast.flash) {
                                window.Toast.flash(data.message || (window.t ? window.t('toast_reset_success') : 'Đã khôi phục mặc định!'), 'success');
                            }
                            showToast(data.message, true);
                            setTimeout(() => window.location.reload(), 800);
                        } else {
                            showToast(data.error || 'Lỗi khi khôi phục', false);
                        }
                    } catch (e) {
                        showToast('Lỗi máy chủ', false);
                    }
                });
            });

            // 4. Khôi phục toàn bộ từ điển
            btnResetAll.addEventListener('click', async function() {
                if (!confirm(
                        'CẢNH BÁO: Thao tác này sẽ xóa toàn bộ các từ khóa tùy chỉnh và đưa hệ thống về từ điển mặc định của mã nguồn. Tiếp tục?'
                    )) {
                    return;
                }

                try {
                    const res = await fetch('/WEB_BOBIN/public/index.php?url=employee/resetTranslations', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify({
                            key: ''
                        })
                    });
                    const data = await res.json();
                    if (data.success) {
                        try {
                            localStorage.removeItem('webbobin_custom_i18n');
                        } catch (e) {}
                        if (typeof window.loadAndMergeCustomTranslations === 'function') {
                            window.loadAndMergeCustomTranslations({ vi: {}, en: {}, ja: {} });
                        }
                        if (window.Toast && window.Toast.flash) {
                            window.Toast.flash(data.message || (window.t ? window.t('toast_reset_success') : 'Đã khôi phục toàn bộ từ điển!'), 'success');
                        }
                        showToast(data.message, true);
                        setTimeout(() => window.location.reload(), 800);
                    } else {
                        showToast(data.error || 'Lỗi khi khôi phục', false);
                    }
                } catch (e) {
                    showToast('Lỗi máy chủ', false);
                }
            });

            // 5. Modal thêm Key mới
            btnOpenModal.addEventListener('click', () => {
                newKeyInput.value = '';
                newViInput.value = '';
                newEnInput.value = '';
                newJaInput.value = '';
                modal.classList.add('active');
                newKeyInput.focus();
            });

            const closeModal = () => modal.classList.remove('active');
            btnCloseModal.addEventListener('click', closeModal);
            btnCancelModal.addEventListener('click', closeModal);
            modal.addEventListener('click', (e) => {
                if (e.target === modal) closeModal();
            });

            btnSubmitNew.addEventListener('click', async () => {
                const k = newKeyInput.value.trim().toLowerCase().replace(/[^a-z0-9_]/g, '_');
                const vi = newViInput.value.trim();
                const en = newEnInput.value.trim();
                const ja = newJaInput.value.trim();

                if (!k || !vi) {
                    if (window.Toast && window.Toast.warning) {
                        window.Toast.warning('Vui lòng nhập Mã từ khóa (Key) và bản dịch Tiếng Việt!');
                    } else {
                        showToast('Vui lòng nhập Mã từ khóa (Key) và bản dịch Tiếng Việt!', false);
                    }
                    return;
                }

                const existingRow = document.querySelector(`.lang-row[data-key="${k}"]`);
                if (existingRow) {
                    const msg = `Từ khóa '${k}' đã tồn tại trong danh mục! Bạn có thể tìm kiếm và sửa trực tiếp trên bảng.`;
                    if (window.Toast && window.Toast.warning) {
                        window.Toast.warning(msg);
                    } else {
                        showToast(msg, false);
                    }
                    closeModal();
                    searchInput.value = k;
                    applyFilter();
                    return;
                }

                // Gửi lưu luôn lên server
                const payload = [{
                    key: k,
                    vi: vi,
                    en: en || vi,
                    ja: ja || vi
                }];
                try {
                    const res = await fetch('/WEB_BOBIN/public/index.php?url=employee/updateTranslations', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify({
                            translations: payload,
                            replace_all: false
                        })
                    });
                    const data = await res.json();
                    if (data.success) {
                        if (data.custom_dict) {
                            try {
                                localStorage.setItem('webbobin_custom_i18n', JSON.stringify(data.custom_dict));
                            } catch (e) {}
                            if (typeof window.loadAndMergeCustomTranslations === 'function') {
                                window.loadAndMergeCustomTranslations(data.custom_dict);
                            }
                        }
                        if (window.Toast && window.Toast.flash) {
                            window.Toast.flash(`Thêm từ khóa '${k}' thành công!`, 'success');
                        }
                        showToast(`Thêm từ khóa '${k}' thành công!`, true);
                        closeModal();
                        setTimeout(() => window.location.reload(), 800);
                    } else {
                        showToast(data.error || 'Lỗi khi thêm từ khóa', false);
                    }
                } catch (err) {
                    showToast('Lỗi kết nối máy chủ', false);
                }
            });
        })();
    </script>
</body>

</html>