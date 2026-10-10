/**
 * ==============================================================================
 * QR SCANNER HELPER (WEB_BOBIN)
 * 100% Offline Local - Html5Qrcode Wrapper - Modern UI/UX - Robust Lifecycle
 * ==============================================================================
 */

(function (window) {
  'use strict';

  // Helper dịch ngôn ngữ đồng bộ hệ thống i18n
  function tr(key, defaultVal) {
    if (typeof window.t === 'function') {
      const translated = window.t(key);
      if (translated && translated !== key) return translated;
    }
    return defaultVal;
  }

  const QRScannerHelper = {
    scannerInstance: null,
    isScanning: false,
    hasScanned: false,
    closeTimer: null,
    activeContainer: null,
    currentCameraId: null,
    camerasList: [],
    currentCameraIndex: 0,
    callbacks: {},

    /**
     * Bật hoặc tắt máy quét QR
     * @param {string|HTMLElement} containerIdOrEl - ID hoặc element container
     * @param {Object} options - Các tùy chọn và callback
     */
    toggle: function (containerIdOrEl, options) {
      const targetContainer = typeof containerIdOrEl === 'string'
        ? document.getElementById(containerIdOrEl)
        : containerIdOrEl;
      if (this.isScanning && this.activeContainer === targetContainer) {
        return this.stop();
      } else {
        return this.start(containerIdOrEl, options);
      }
    },

    /**
     * Khởi động máy quét QR
     */
    start: async function (containerIdOrEl, options) {
      // Đóng bàn phím ảo nếu đang mở trên thiết bị cảm ứng
      if (document.activeElement && typeof document.activeElement.blur === 'function') {
        document.activeElement.blur();
      }

      const container = typeof containerIdOrEl === 'string' 
        ? document.getElementById(containerIdOrEl) 
        : containerIdOrEl;

      if (!container) {
        console.error('QRScanner: Container element not found', containerIdOrEl);
        return;
      }

      // Đảm bảo dừng phiên quét cũ nếu đang chạy
      if (this.isScanning) {
        await this.stop();
      }

      // Reset cờ quét và bộ đếm thời gian
      this.hasScanned = false;
      if (this.closeTimer) {
        clearTimeout(this.closeTimer);
        this.closeTimer = null;
      }

      this.activeContainer = container;
      this.callbacks = options || {};

      // Tạo cấu trúc giao diện Modern Scanner Card
      this.renderUI(container);

      // Kiểm tra thư viện Html5Qrcode
      if (typeof window.Html5Qrcode === 'undefined') {
        this.renderError('Thư viện máy quét chưa được nạp. Vui lòng tải lại trang!');
        return;
      }

      try {
        const videoRegionId = 'qr-video-viewport';
        this.scannerInstance = new window.Html5Qrcode(videoRegionId);

        // Lấy danh sách máy ảnh
        let cameras = [];
        try {
          cameras = await window.Html5Qrcode.getCameras();
          this.camerasList = cameras || [];
        } catch (camErr) {
          console.warn('Không thể liệt kê danh sách camera:', camErr);
          this.camerasList = [];
        }

        // Hiển thị nút đổi camera nếu có > 1 camera
        const btnSwitchCam = container.querySelector('.qr-btn-switch-cam');
        if (btnSwitchCam) {
          btnSwitchCam.style.display = (this.camerasList.length > 1) ? 'inline-flex' : 'none';
        }

        // Cấu hình quét: disableFlip: true ngăn ma trận canvas bị lật đảo liên tục gây nhấp nháy
        const config = {
          fps: 15,
          qrbox: (viewfinderWidth, viewfinderHeight) => {
            const minEdge = Math.min(viewfinderWidth, viewfinderHeight);
            const edge = Math.floor(minEdge * 0.72);
            return { width: Math.max(edge, 180), height: Math.max(edge, 180) };
          },
          disableFlip: true
        };

        const onSuccess = (decodedText, decodedResult) => {
          this.handleScanSuccess(decodedText, decodedResult);
        };

        const onFailure = (error) => {
          // Bỏ qua lỗi rác do frame chưa bắt được mã QR
        };

        // Ưu tiên camera sau (environment) trên mobile hoặc camera đầu tiên
        let cameraConfig = { facingMode: 'environment' };
        if (this.currentCameraId) {
          cameraConfig = this.currentCameraId;
        }

        // Bắt đầu luồng camera
        await this.scannerInstance.start(cameraConfig, config, onSuccess, onFailure);

        this.isScanning = true;

        // Ẩn màn hình loading khi camera đã mở
        const loadingOverlay = container.querySelector('.qr-loading-overlay');
        if (loadingOverlay) {
          loadingOverlay.style.display = 'none';
        }

        if (typeof this.callbacks.onOpen === 'function') {
          this.callbacks.onOpen();
        }

      } catch (err) {
        console.error('Lỗi khi mở camera QR:', err);
        // Thử lại với camera đầu tiên nếu facingMode: environment thất bại
        if (this.camerasList.length > 0 && !this.currentCameraId) {
          try {
            this.currentCameraId = this.camerasList[0].id;
            return await this.start(container, options);
          } catch (retryErr) {
            console.error('Thử lại với camera đầu tiên thất bại:', retryErr);
          }
        }

        this.renderError(
          tr('qr_scanner_camera_error', 'Không thể mở máy ảnh. Vui lòng cấp quyền truy cập Camera trong trình duyệt!')
        );
      }
    },

    /**
     * Dừng camera và dọn dẹp sạch sẽ tài nguyên
     */
    stop: async function () {
      if (this.closeTimer) {
        clearTimeout(this.closeTimer);
        this.closeTimer = null;
      }
      this.hasScanned = false;

      if (this.scannerInstance && this.isScanning) {
        try {
          await this.scannerInstance.stop();
        } catch (e) {
          console.warn('Lỗi khi dừng luồng scanner:', e);
        }
        try {
          this.scannerInstance.clear();
        } catch (e) {}
      }

      this.isScanning = false;
      this.scannerInstance = null;

      if (this.activeContainer) {
        this.activeContainer.innerHTML = '';
        this.activeContainer.style.display = 'none';
      }

      if (typeof this.callbacks.onClose === 'function') {
        this.callbacks.onClose();
      }
    },

    /**
     * Chuyển đổi giữa các camera (Trước / Sau hoặc các Webcam khác nhau)
     */
    switchCamera: async function () {
      if (this.camerasList.length <= 1) return;

      this.currentCameraIndex = (this.currentCameraIndex + 1) % this.camerasList.length;
      this.currentCameraId = this.camerasList[this.currentCameraIndex].id;

      if (this.activeContainer) {
        await this.start(this.activeContainer, this.callbacks);
      }
    },

    /**
     * Xử lý khi nhận diện mã thành công (Ngăn kích hoạt nhiều lần và hiển thị 1 Toast duy nhất)
     */
    handleScanSuccess: function (decodedText, decodedResult) {
      // 1. Chặn re-entry: Chỉ cho phép xử lý duy nhất 1 lần cho 1 mã vừa quét
      if (this.hasScanned) {
        return;
      }
      this.hasScanned = true;

      // 2. Tạm dừng scanner ngay lập tức để ngắt việc quét các frame tiếp theo
      if (this.scannerInstance) {
        try {
          this.scannerInstance.pause(true);
        } catch (pauseErr) {
          // Scanner có thể đã dừng hoặc không ở trạng thái scanning
        }
      }

      // 3. Hiệu ứng Flash thành công trên khung ngắm
      if (this.activeContainer) {
        const reticle = this.activeContainer.querySelector('.qr-reticle-overlay');
        if (reticle) reticle.classList.add('qr-success');

        const statusText = this.activeContainer.querySelector('.qr-status-pill span:last-child');
        if (statusText) statusText.textContent = tr('qr_scanner_success', 'Đã nhận diện thành công!');
      }

      // 4. Thông báo Toast thành công duy nhất 1 lần
      if (typeof window.Toast !== 'undefined') {
        const prefix = tr('toast_qr_scanned', 'Đã quét mã: ');
        window.Toast.show(`${prefix}${decodedText}`, 'success');
      }

      const cb = this.callbacks.onSuccess;

      // 5. Dừng máy quét và gọi callback sau 280ms để người dùng kịp quan sát hiệu ứng nhận diện
      if (this.closeTimer) {
        clearTimeout(this.closeTimer);
      }
      this.closeTimer = setTimeout(async () => {
        await this.stop();
        if (typeof cb === 'function') {
          cb(decodedText, decodedResult);
        }
        // Đảm bảo bàn phím không hiện lên sau khi quét xong
        if (document.activeElement && typeof document.activeElement.blur === 'function') {
          document.activeElement.blur();
        }
      }, 280);
    },

    /**
     * Quét mã QR từ file hình ảnh (Fallback cho máy không có webcam)
     */
    scanFile: async function (file) {
      if (!file) return;

      this.hasScanned = false;

      if (!this.scannerInstance) {
        const videoRegionId = 'qr-video-viewport';
        this.scannerInstance = new window.Html5Qrcode(videoRegionId);
      }

      try {
        const result = await this.scannerInstance.scanFile(file, false);
        this.handleScanSuccess(result);
      } catch (err) {
        console.warn('Không thể đọc mã QR từ file ảnh:', err);
        if (typeof window.Toast !== 'undefined') {
          window.Toast.show(
            tr('qr_scanner_no_qr_found', 'Không tìm thấy mã QR trong ảnh đã chọn!'),
            'warning'
          );
        }
      }
    },

    /**
     * Render cấu trúc UI chuyên nghiệp vào container
     */
    renderUI: function (container) {
      container.style.display = 'block';

      const titleText = (this.callbacks && this.callbacks.title) || tr('qr_scanner_title', 'Quét mã QR Bobin');
      const switchCamText = tr('qr_scanner_switch_cam', 'Đổi camera');
      const closeText = tr('close', 'Đóng');
      const connectingText = tr('qr_scanner_connecting', 'Đang kết nối camera...');
      const searchingText = (this.callbacks && this.callbacks.searchingText) || tr('qr_scanner_searching', 'Đang quét mã QR Bobin...');
      const hintText = tr('qr_scanner_hint', 'Căn chỉnh mã QR vào giữa khung quét');
      const uploadBtnText = tr('qr_scanner_upload_btn', 'Tải ảnh mã QR');

      container.innerHTML = `
        <div class="qr-scanner-card">
          <!-- HEADER -->
          <div class="qr-scanner-header">
            <div class="qr-scanner-header-title">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="3" width="7" height="7"></rect>
                <rect x="14" y="3" width="7" height="7"></rect>
                <rect x="14" y="14" width="7" height="7"></rect>
                <path d="M3 14h7v7H3z"></path>
              </svg>
              <span>${titleText}</span>
            </div>
            <div class="qr-scanner-actions">
              <button type="button" class="qr-btn-icon qr-btn-switch-cam" title="${switchCamText}" style="display:none;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"></path>
                </svg>
              </button>
              <button type="button" class="qr-btn-icon qr-btn-close" title="${closeText}">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                  <line x1="18" y1="6" x2="6" y2="18"></line>
                  <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
              </button>
            </div>
          </div>

          <!-- VIEWPORT -->
          <div class="qr-scanner-viewport-wrap">
            <div id="qr-video-viewport" class="qr-video-region"></div>

            <!-- KHUNG NGẮM & TIA LASER -->
            <div class="qr-reticle-overlay">
              <span class="qr-corner qr-corner-tl"></span>
              <span class="qr-corner qr-corner-tr"></span>
              <span class="qr-corner qr-corner-bl"></span>
              <span class="qr-corner qr-corner-br"></span>
              <div class="qr-laser-line"></div>
            </div>

            <!-- BADGE TRẠNG THÁI -->
            <div class="qr-status-pill">
              <span class="qr-status-dot"></span>
              <span>${searchingText}</span>
            </div>

            <!-- MÀN HÌNH CHỜ LOADING -->
            <div class="qr-loading-overlay">
              <div class="qr-spinner"></div>
              <span>${connectingText}</span>
            </div>
          </div>

          <!-- FOOTER -->
          <div class="qr-scanner-footer">
            <div class="qr-hint-text">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="12" y1="16" x2="12" y2="12"></line>
                <line x1="12" y1="8" x2="12.01" y2="8"></line>
              </svg>
              <span>${hintText}</span>
            </div>
            <div class="qr-upload-btn-wrap">
              <label class="qr-upload-label">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                  <polyline points="17 8 12 3 7 8"></polyline>
                  <line x1="12" y1="3" x2="12" y2="15"></line>
                </svg>
                <span>${uploadBtnText}</span>
                <input type="file" class="qr-file-input" accept="image/*">
              </label>
            </div>
          </div>
        </div>
      `;

      // Gắn sự kiện nút đóng
      const btnClose = container.querySelector('.qr-btn-close');
      if (btnClose) {
        btnClose.addEventListener('click', () => {
          this.stop();
        });
      }

      // Gắn sự kiện nút đổi camera
      const btnSwitchCam = container.querySelector('.qr-btn-switch-cam');
      if (btnSwitchCam) {
        btnSwitchCam.addEventListener('click', () => {
          this.switchCamera();
        });
      }

      // Gắn sự kiện tải ảnh mã QR
      const fileInput = container.querySelector('.qr-file-input');
      if (fileInput) {
        fileInput.addEventListener('change', (e) => {
          const file = e.target.files && e.target.files[0];
          if (file) {
            this.scanFile(file);
          }
        });
      }
    },

    /**
     * Render thông báo lỗi thân thiện khi không mở được camera
     */
    renderError: function (message) {
      if (!this.activeContainer) return;

      const titleText = tr('error', 'Lỗi truy cập camera');
      const retryText = tr('qr_scanner_retry', 'Thử lại');
      const uploadBtnText = tr('qr_scanner_upload_btn', 'Tải ảnh mã QR');
      const closeText = tr('close', 'Đóng');

      this.activeContainer.innerHTML = `
        <div class="qr-scanner-card">
          <div class="qr-scanner-header">
            <div class="qr-scanner-header-title">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="12" y1="8" x2="12" y2="12"></line>
                <line x1="12" y1="16" x2="12.01" y2="16"></line>
              </svg>
              <span>${titleText}</span>
            </div>
            <button type="button" class="qr-btn-icon qr-btn-close" title="${closeText}">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
              </svg>
            </button>
          </div>
          <div class="qr-error-card">
            <div class="qr-error-icon">⚠️</div>
            <h4 class="qr-error-title">${titleText}</h4>
            <p class="qr-error-desc">${message}</p>
            <div class="qr-error-actions">
              <button type="button" class="qr-btn-retry">${retryText}</button>
              <label class="qr-upload-label">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                  <polyline points="17 8 12 3 7 8"></polyline>
                  <line x1="12" y1="3" x2="12" y2="15"></line>
                </svg>
                <span>${uploadBtnText}</span>
                <input type="file" class="qr-file-input" accept="image/*">
              </label>
            </div>
          </div>
        </div>
      `;

      // Gắn sự kiện nút đóng
      const btnClose = this.activeContainer.querySelector('.qr-btn-close');
      if (btnClose) {
        btnClose.addEventListener('click', () => {
          this.stop();
        });
      }

      // Gắn sự kiện nút thử lại
      const btnRetry = this.activeContainer.querySelector('.qr-btn-retry');
      if (btnRetry) {
        btnRetry.addEventListener('click', () => {
          this.start(this.activeContainer, this.callbacks);
        });
      }

      // Gắn sự kiện tải ảnh mã QR
      const fileInput = this.activeContainer.querySelector('.qr-file-input');
      if (fileInput) {
        fileInput.addEventListener('change', (e) => {
          const file = e.target.files && e.target.files[0];
          if (file) {
            this.scanFile(file);
          }
        });
      }
    }
  };

  window.QRScannerHelper = QRScannerHelper;
})(window);

