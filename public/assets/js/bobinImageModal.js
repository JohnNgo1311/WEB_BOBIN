/**
 * File: public/assets/js/bobinImageModal.js
 * Quản lý Modal Dialog hiển thị hình ảnh Bobin (Deferred Loading)
 * và bộ chọn hình ảnh (Máy tính / Thư viện / Chụp ảnh Camera trên điện thoại) cho 3 nhóm Đùn / QC / Cuộn.
 */
(function () {
    'use strict';

    const MAX_IMAGE_BYTES = 15 * 1024 * 1024; // 15 MB
    const ALLOWED_IMAGE_MIMES = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];

    function tr(key, fallback) {
        if (typeof window.t === 'function') {
            return window.t(key, fallback);
        }
        return fallback;
    }

    function getStageModalTitle(stage) {
        switch ((stage || '').toLowerCase()) {
            case 'extrusion':
                return tr('modal_title_ext_image', 'Hình ảnh biểu đồ dữ liệu đo (Nhóm Đùn)');
            case 'qc':
                return tr('modal_title_qc_image', 'Hình ảnh kiểm tra ngoại quan (Nhóm QC)');
            case 'winding':
                return tr('modal_title_wnd_image', 'Hình ảnh biểu đồ dữ liệu đo (Nhóm Cuộn)');
            default:
                return tr('btn_view_image', 'Xem hình ảnh');
        }
    }

    /* ==========================================================================
       1. MODAL DIALOG GIỮA MÀN HÌNH (CHỈ TẢI ẢNH KHI NHẤN "XEM HÌNH ẢNH")
       ========================================================================== */
    let modalBackdrop = null;
    let currentStage = '';
    let isZoomed = false;

    function ensureModalCreated() {
        if (modalBackdrop) return modalBackdrop;

        const el = document.createElement('div');
        el.className = 'bobin-image-modal-backdrop';
        el.id = 'bobinImageModalBackdrop';
        el.setAttribute('role', 'dialog');
        el.setAttribute('aria-modal', 'true');

        el.innerHTML = `
            <div class="bobin-image-modal-dialog">
                <div class="bim-header">
                    <div class="bim-header-left">
                        <h3 class="bim-title" id="bimModalTitle"></h3>
                        <span class="bim-bobin-badge" id="bimBobinBadge" style="display:none;"></span>
                    </div>
                    <div class="bim-header-actions">
                        <button type="button" class="bim-zoom-btn" id="bimZoomBtn" style="display:none;">
                            <span id="bimZoomBtnText"></span>
                        </button>
                        <button type="button" class="bim-close-btn" id="bimCloseBtn" aria-label="Close">&times;</button>
                    </div>
                </div>
                <div class="bim-body" id="bimModalBody"></div>
            </div>
        `;

        document.body.appendChild(el);

        el.addEventListener('click', function (e) {
            if (e.target === el) {
                closeModal();
            }
        });

        const closeBtn = el.querySelector('#bimCloseBtn');
        if (closeBtn) {
            closeBtn.addEventListener('click', closeModal);
        }

        const zoomBtn = el.querySelector('#bimZoomBtn');
        if (zoomBtn) {
            zoomBtn.addEventListener('click', toggleZoom);
        }

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && el.classList.contains('is-open')) {
                closeModal();
            }
        });

        modalBackdrop = el;
        return el;
    }

    function updateZoomButtonLabel() {
        if (!modalBackdrop) return;
        const zoomTextEl = modalBackdrop.querySelector('#bimZoomBtnText');
        if (!zoomTextEl) return;
        zoomTextEl.textContent = isZoomed
            ? tr('img_zoom_fit', 'Thu vừa khung')
            : tr('img_zoom_in', 'Phóng to 100%');
    }

    function toggleZoom() {
        if (!modalBackdrop) return;
        const body = modalBackdrop.querySelector('#bimModalBody');
        if (!body) return;
        isZoomed = !isZoomed;
        body.classList.toggle('is-zoomed', isZoomed);
        updateZoomButtonLabel();
    }

    function renderEmptyState(bodyEl) {
        bodyEl.innerHTML = `
            <div class="bim-empty-state">
                <div class="bim-empty-icon">
                    <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                        <circle cx="8.5" cy="8.5" r="1.5"></circle>
                        <polyline points="21 15 16 10 5 21"></polyline>
                    </svg>
                </div>
                <p class="bim-empty-text" data-i18n="no_image_attached">${tr('no_image_attached', 'Không có hình ảnh đi kèm')}</p>
            </div>
        `;
    }

    function openModal(options) {
        const opts = options || {};
        const url = (opts.url || '').trim();
        const stage = (opts.stage || '').trim();
        const bobinCode = (opts.bobinCode || '').trim();

        const el = ensureModalCreated();
        currentStage = stage;
        isZoomed = false;

        const titleEl = el.querySelector('#bimModalTitle');
        const badgeEl = el.querySelector('#bimBobinBadge');
        const zoomBtn = el.querySelector('#bimZoomBtn');
        const bodyEl = el.querySelector('#bimModalBody');

        bodyEl.classList.remove('is-zoomed');
        titleEl.textContent = opts.title || getStageModalTitle(stage);

        if (bobinCode) {
            badgeEl.textContent = bobinCode.startsWith('#') ? bobinCode : ('#' + bobinCode);
            badgeEl.style.display = 'inline-flex';
        } else {
            badgeEl.style.display = 'none';
        }

        if (!url) {
            zoomBtn.style.display = 'none';
            renderEmptyState(bodyEl);
        } else {
            bodyEl.innerHTML = '';
            const img = document.createElement('img');
            img.className = 'bim-image';
            img.alt = titleEl.textContent;
            img.addEventListener('click', toggleZoom);
            img.addEventListener('load', function () {
                zoomBtn.style.display = 'inline-flex';
                updateZoomButtonLabel();
            });
            img.addEventListener('error', function () {
                zoomBtn.style.display = 'none';
                renderEmptyState(bodyEl);
            });
            img.src = url;
            bodyEl.appendChild(img);
        }

        el.classList.add('is-open');
    }

    function closeModal() {
        if (!modalBackdrop) return;
        modalBackdrop.classList.remove('is-open');
        const bodyEl = modalBackdrop.querySelector('#bimModalBody');
        if (bodyEl) {
            bodyEl.innerHTML = '';
            bodyEl.classList.remove('is-zoomed');
        }
    }

    /* ==========================================================================
       2. QUẢN LÝ WIDGET PICKER (CHỌN ẢNH / CHỤP CAMERA / ĐỔI / XÓA)
       ========================================================================== */
    const pickerStore = new WeakMap();

    function getOrInitPickerData(container) {
        let state = pickerStore.get(container);
        if (!state) {
            const initialUrl = (container.dataset.initialUrl || '').trim();
            state = {
                selectedFile: null,
                objectUrl: '',
                currentServerUrl: initialUrl,
                backupServerUrl: initialUrl,
                action: 'keep'
            };
            pickerStore.set(container, state);
        }
        return state;
    }

    function revokeObjectUrlIfAny(state) {
        if (state && state.objectUrl) {
            try {
                URL.revokeObjectURL(state.objectUrl);
            } catch (_) {}
            state.objectUrl = '';
        }
    }

    function syncPickerUI(container) {
        if (!container) return;
        const state = getOrInitPickerData(container);

        const actionInput = container.querySelector('.bip-action-input');
        const statusBadge = container.querySelector('.bip-status-badge');
        const statusText = container.querySelector('.bip-status-text');
        const fileNameRow = container.querySelector('.bip-file-name-row');
        const fileNameText = container.querySelector('.bip-file-name-text');
        const chooseText = container.querySelector('.bip-choose-text');
        const previewBtn = container.querySelector('.bip-btn-preview');
        const removeBtn = container.querySelector('.bip-btn-remove');

        if (actionInput) {
            actionInput.value = state.action;
        }

        const effectivePreviewUrl = state.selectedFile
            ? state.objectUrl
            : (state.action === 'delete' ? '' : state.currentServerUrl);

        container.dataset.currentUrl = effectivePreviewUrl || '';

        if (statusBadge && statusText) {
            statusBadge.classList.remove('is-empty', 'is-attached', 'is-marked-delete');
            if (state.selectedFile || (state.currentServerUrl && state.action !== 'delete')) {
                statusBadge.classList.add('is-attached');
                statusText.setAttribute('data-i18n', 'img_status_attached');
                statusText.textContent = tr('img_status_attached', 'Đã đính kèm ảnh');
            } else if (state.action === 'delete' && state.currentServerUrl) {
                statusBadge.classList.add('is-marked-delete');
                statusText.setAttribute('data-i18n', 'img_status_marked_delete');
                statusText.textContent = tr('img_status_marked_delete', 'Sẽ xóa ảnh khi lưu');
            } else {
                statusBadge.classList.add('is-empty');
                statusText.setAttribute('data-i18n', 'img_status_none');
                statusText.textContent = tr('img_status_none', 'Chưa có ảnh');
            }
        }

        if (fileNameRow && fileNameText) {
            if (state.selectedFile) {
                fileNameText.textContent = state.selectedFile.name;
                fileNameRow.style.display = '';
            } else if (state.currentServerUrl && state.action !== 'delete') {
                const parts = state.currentServerUrl.split('/');
                fileNameText.textContent = parts[parts.length - 1] || '';
                fileNameRow.style.display = '';
            } else {
                fileNameText.textContent = '';
                fileNameRow.style.display = 'none';
            }
        }

        const hasActiveImage = Boolean(effectivePreviewUrl);

        if (chooseText) {
            const key = hasActiveImage ? 'btn_change_image' : 'btn_choose_image';
            chooseText.setAttribute('data-i18n', key);
            chooseText.textContent = hasActiveImage
                ? tr('btn_change_image', 'Đổi hình ảnh')
                : tr('btn_choose_image', 'Chọn hình ảnh');
        }

        if (previewBtn) {
            previewBtn.classList.toggle('has-image', hasActiveImage);
            previewBtn.classList.toggle('no-image', !hasActiveImage);
        }

        if (removeBtn) {
            removeBtn.style.display = hasActiveImage ? '' : 'none';
        }
    }

    function validateSelectedFile(file) {
        if (!file) return false;
        const mime = (file.type || '').toLowerCase();
        const validExt = /\.(jpe?g|png|webp|gif)$/i.test(file.name || '');
        if ((mime && !ALLOWED_IMAGE_MIMES.includes(mime) && !mime.startsWith('image/')) || (!mime && !validExt)) {
            const msg = tr('err_img_invalid_type', 'Định dạng file không hợp lệ. Vui lòng chọn ảnh JPG, PNG, WEBP hoặc GIF.');
            if (window.Toast && typeof window.Toast.show === 'function') {
                window.Toast.show(msg, 'error');
            } else {
                alert(msg);
            }
            return false;
        }
        if (file.size > MAX_IMAGE_BYTES) {
            const msg = tr('err_img_too_large', 'Dung lượng ảnh vượt quá giới hạn cho phép (tối đa 15MB).');
            if (window.Toast && typeof window.Toast.show === 'function') {
                window.Toast.show(msg, 'error');
            } else {
                alert(msg);
            }
            return false;
        }
        return true;
    }

    function assignFileToInput(fileInput, file) {
        if (!fileInput || typeof DataTransfer === 'undefined') return;
        try {
            const dt = new DataTransfer();
            if (file) {
                dt.items.add(file);
            }
            fileInput.files = dt.files;
        } catch (_) {}
    }

    function handleFilePicked(container, file, sourceType) {
        if (!file) return;
        const fileInput = container.querySelector('.bip-file-input');
        const cameraInput = container.querySelector('.bip-camera-input');

        if (!validateSelectedFile(file)) {
            if (sourceType === 'camera' && cameraInput) cameraInput.value = '';
            if (sourceType === 'gallery' && fileInput) fileInput.value = '';
            return;
        }

        const state = getOrInitPickerData(container);
        revokeObjectUrlIfAny(state);
        state.selectedFile = file;
        state.objectUrl = URL.createObjectURL(file);
        state.action = 'replace';

        // Đồng bộ file chụp từ camera sang input chính để new FormData(form) luôn tự động nhận được
        if (sourceType === 'camera') {
            assignFileToInput(fileInput, file);
            if (cameraInput) cameraInput.value = '';
        }

        syncPickerUI(container);
    }

    function bindPickerContainer(container) {
        if (!container || container.dataset.bipBound === '1') return;
        container.dataset.bipBound = '1';
        getOrInitPickerData(container);

        const fileInput = container.querySelector('.bip-file-input');
        const cameraInput = container.querySelector('.bip-camera-input');
        const chooseBtn = container.querySelector('.bip-btn-choose');
        const cameraBtn = container.querySelector('.bip-btn-camera');
        const previewBtn = container.querySelector('.bip-btn-preview');
        const removeBtn = container.querySelector('.bip-btn-remove');

        if (chooseBtn && fileInput) {
            chooseBtn.addEventListener('click', function () {
                if (container.classList.contains('is-locked') || chooseBtn.disabled) return;
                fileInput.click();
            });
        }

        if (cameraBtn && cameraInput) {
            cameraBtn.addEventListener('click', function () {
                if (container.classList.contains('is-locked') || cameraBtn.disabled) return;
                cameraInput.click();
            });
        }

        if (fileInput) {
            fileInput.addEventListener('change', function () {
                const file = fileInput.files && fileInput.files[0] ? fileInput.files[0] : null;
                if (file) {
                    handleFilePicked(container, file, 'gallery');
                }
            });
        }

        if (cameraInput) {
            cameraInput.addEventListener('change', function () {
                const file = cameraInput.files && cameraInput.files[0] ? cameraInput.files[0] : null;
                if (file) {
                    handleFilePicked(container, file, 'camera');
                }
            });
        }

        if (previewBtn) {
            previewBtn.addEventListener('click', function () {
                const stage = container.dataset.stage || '';
                let bobinCode = container.dataset.bobinCode || '';
                if (!bobinCode) {
                    const form = container.closest('form');
                    if (form) {
                        const codeInput = form.querySelector('#bobin_code, [name="bobin_identification_code"]');
                        if (codeInput && codeInput.value) {
                            bobinCode = codeInput.value.trim();
                        }
                    }
                }
                openModal({
                    url: container.dataset.currentUrl || '',
                    stage: stage,
                    bobinCode: bobinCode
                });
            });
        }

        if (removeBtn) {
            removeBtn.addEventListener('click', function () {
                if (container.classList.contains('is-locked') || removeBtn.disabled) return;
                const state = getOrInitPickerData(container);
                revokeObjectUrlIfAny(state);
                state.selectedFile = null;
                if (fileInput) fileInput.value = '';
                if (cameraInput) cameraInput.value = '';
                state.action = state.currentServerUrl ? 'delete' : 'keep';
                syncPickerUI(container);
            });
        }

        syncPickerUI(container);
    }

    function initAllPickers(root) {
        const scope = root || document;
        scope.querySelectorAll('[data-bobin-image-picker="1"]').forEach(bindPickerContainer);
    }

    function getPickerInScope(scopeEl, stage) {
        if (!scopeEl) return null;
        if (scopeEl.matches && scopeEl.matches('[data-bobin-image-picker="1"]')) {
            return scopeEl;
        }
        const selector = stage
            ? `[data-bobin-image-picker="1"][data-stage="${stage}"]`
            : '[data-bobin-image-picker="1"]';
        return scopeEl.querySelector(selector);
    }

    function setEditable(scopeEl, editable, stage) {
        const container = getPickerInScope(scopeEl, stage);
        if (!container) return;
        bindPickerContainer(container);
        container.classList.toggle('is-locked', !editable);
        container.dataset.locked = editable ? '0' : '1';
        container.querySelectorAll('.bip-file-input, .bip-camera-input, .bip-btn-choose, .bip-btn-camera, .bip-btn-remove')
            .forEach(function (el) {
                el.disabled = !editable;
            });
    }

    function backupState(scopeEl, stage) {
        const container = getPickerInScope(scopeEl, stage);
        if (!container) return;
        bindPickerContainer(container);
        const state = getOrInitPickerData(container);
        state.backupServerUrl = state.currentServerUrl;
    }

    function restoreState(scopeEl, stage) {
        const container = getPickerInScope(scopeEl, stage);
        if (!container) return;
        bindPickerContainer(container);
        const state = getOrInitPickerData(container);
        revokeObjectUrlIfAny(state);
        state.selectedFile = null;
        state.currentServerUrl = state.backupServerUrl;
        state.action = 'keep';
        const fileInput = container.querySelector('.bip-file-input');
        const cameraInput = container.querySelector('.bip-camera-input');
        if (fileInput) fileInput.value = '';
        if (cameraInput) cameraInput.value = '';
        syncPickerUI(container);
    }

    function commitServerUrl(scopeEl, newUrl, stage) {
        const container = getPickerInScope(scopeEl, stage);
        if (!container) return;
        bindPickerContainer(container);
        const state = getOrInitPickerData(container);
        revokeObjectUrlIfAny(state);
        state.selectedFile = null;
        state.currentServerUrl = (newUrl || '').trim();
        state.backupServerUrl = state.currentServerUrl;
        state.action = 'keep';
        container.dataset.initialUrl = state.currentServerUrl;
        const fileInput = container.querySelector('.bip-file-input');
        const cameraInput = container.querySelector('.bip-camera-input');
        if (fileInput) fileInput.value = '';
        if (cameraInput) cameraInput.value = '';
        syncPickerUI(container);
    }

    function reset(scopeEl, stage) {
        commitServerUrl(scopeEl, '', stage);
    }

    function getState(scopeEl, stage) {
        const container = getPickerInScope(scopeEl, stage);
        if (!container) {
            return {
                file: null,
                action: 'keep',
                hasImage: false,
                fileName: '',
                inputName: '',
                actionName: ''
            };
        }
        bindPickerContainer(container);
        const state = getOrInitPickerData(container);
        const fileInput = container.querySelector('.bip-file-input');
        const fallbackFile = (!state.selectedFile && fileInput && fileInput.files && fileInput.files[0])
            ? fileInput.files[0]
            : null;
        const activeFile = state.selectedFile || fallbackFile;
        const effectiveUrl = activeFile
            ? state.objectUrl
            : (state.action === 'delete' ? '' : state.currentServerUrl);

        return {
            file: activeFile,
            action: state.action || 'keep',
            hasImage: Boolean(activeFile || effectiveUrl),
            fileName: activeFile ? activeFile.name : (effectiveUrl ? effectiveUrl.split('/').pop() : ''),
            inputName: container.dataset.inputName || '',
            actionName: container.dataset.actionName || ''
        };
    }

    /**
     * Xây dựng gói gửi API tương thích cho cả trường hợp có file (POST FormData) và không file (POST FormData kèm payload_json)
     */
    function buildRequestInit(payloadObj, scopeEl, stage) {
        const pState = getState(scopeEl, stage);
        const payloadCopy = Object.assign({}, payloadObj || {});
        if (pState.actionName) {
            payloadCopy[pState.actionName] = pState.action;
        }

        const fd = new FormData();
        fd.append('payload_json', JSON.stringify(payloadCopy));
        if (pState.actionName) {
            fd.append(pState.actionName, pState.action);
        }
        if (pState.file && pState.inputName) {
            fd.append(pState.inputName, pState.file);
        }

        return {
            method: 'POST',
            body: fd
        };
    }

    function getStatusSummaryText(scopeEl, stage) {
        const pState = getState(scopeEl, stage);
        if (pState.file) {
            return tr('img_status_attached', 'Đã đính kèm ảnh') + ' (' + pState.file.name + ')';
        }
        if (pState.action === 'delete') {
            return tr('img_status_marked_delete', 'Sẽ xóa ảnh khi lưu');
        }
        if (pState.hasImage) {
            return tr('img_status_attached', 'Đã đính kèm ảnh');
        }
        return tr('no_image_attached', 'Không có hình ảnh đi kèm');
    }

    /* ==========================================================================
       3. EVENT DELEGATION CHO TOÀN BỘ NÚT [data-bobin-view-image="1"] & ĐỔI NGÔN NGỮ
       ========================================================================== */
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('[data-bobin-view-image="1"]');
        if (!btn) return;
        e.preventDefault();
        openModal({
            url: btn.getAttribute('data-image-url') || '',
            stage: btn.getAttribute('data-image-stage') || '',
            bobinCode: btn.getAttribute('data-bobin-code') || ''
        });
    });

    document.addEventListener('DOMContentLoaded', function () {
        initAllPickers(document);
    });

    window.addEventListener('languageChanged', function () {
        document.querySelectorAll('[data-bobin-image-picker="1"]').forEach(syncPickerUI);
        if (modalBackdrop && modalBackdrop.classList.contains('is-open')) {
            const titleEl = modalBackdrop.querySelector('#bimModalTitle');
            if (titleEl && currentStage) {
                titleEl.textContent = getStageModalTitle(currentStage);
            }
            updateZoomButtonLabel();
            const emptyTextEl = modalBackdrop.querySelector('.bim-empty-text');
            if (emptyTextEl) {
                emptyTextEl.textContent = tr('no_image_attached', 'Không có hình ảnh đi kèm');
            }
        }
    });

    window.BobinImageModal = {
        open: openModal,
        close: closeModal
    };

    window.BobinImagePicker = {
        initAll: initAllPickers,
        getState: getState,
        setEditable: setEditable,
        backupState: backupState,
        restoreState: restoreState,
        commitServerUrl: commitServerUrl,
        reset: reset,
        buildRequestInit: buildRequestInit,
        getStatusSummaryText: getStatusSummaryText
    };
})();

