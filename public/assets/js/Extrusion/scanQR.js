/**
 * ==============================================================================
 * EXTRUSION - SCAN QR CODE (WEB_BOBIN)
 * ==============================================================================
 */

document.addEventListener("DOMContentLoaded", function () {
  const btnScanQR = document.getElementById("btnScanQR");
  const qrReaderDiv = document.getElementById("qr-reader");
  const btnScanRackQR = document.getElementById("btnScanRackQR");
  const qrReaderRackDiv = document.getElementById("qr-reader-rack");

  function tr(key, fallback) {
    if (typeof window.t === "function") {
      const val = window.t(key);
      if (val && val !== key) return val;
    }
    return fallback;
  }

  function createButtonStateUpdater(buttonEl) {
    if (!buttonEl) return function () {};
    const originalHTML = buttonEl.innerHTML;
    return function (isScanning) {
      if (isScanning) {
        buttonEl.classList.add("is-scanning");
        buttonEl.innerHTML = `
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
              <line x1="18" y1="6" x2="6" y2="18"></line>
              <line x1="6" y1="6" x2="18" y2="18"></line>
          </svg>
          <span>${tr("btn_close_camera", "Đóng Camera")}</span>
        `;
      } else {
        buttonEl.classList.remove("is-scanning");
        buttonEl.innerHTML = originalHTML;
      }
    };
  }

  const updateBobinBtnState = createButtonStateUpdater(btnScanQR);
  const updateRackBtnState = createButtonStateUpdater(btnScanRackQR);

  // 1. QUÉT MÃ QR ĐỊNH DANH BOBIN
  if (btnScanQR && qrReaderDiv) {
    btnScanQR.addEventListener("click", function () {
      if (typeof QRScannerHelper === "undefined") {
        console.error("QRScannerHelper is not loaded!");
        return;
      }

      // Đảm bảo tắt bàn phím ảo trước khi mở camera
      if (document.activeElement && typeof document.activeElement.blur === "function") {
        document.activeElement.blur();
      }

      QRScannerHelper.toggle("qr-reader", {
        onOpen: function () {
          updateBobinBtnState(true);
        },
        onClose: function () {
          updateBobinBtnState(false);
        },
        onSuccess: function (decodedText) {
          updateBobinBtnState(false);
          const cleanCode = (decodedText || "").trim();

          // 1. Nhập mã vào ô định danh Bobin trên trang Đùn
          const bobinInput = document.getElementById("bobin_identification_code");
          const bobinSuggestions = document.getElementById("bobin_suggestions");
          // 2. Dự phòng nếu trang có ô tìm kiếm
          const searchKeyword = document.getElementById("searchKeyword") || document.getElementById("keyword");
          const filterForm = document.getElementById("filterForm");

          if (bobinInput) {
            bobinInput.value = cleanCode;
            bobinInput.dispatchEvent(new Event("input", { bubbles: true }));
            bobinInput.dispatchEvent(new Event("change", { bubbles: true }));
            if (bobinSuggestions) {
              bobinSuggestions.style.display = "none";
            }
            // Không gọi focus() để tránh bàn phím ảo bật lên sau khi quét xong
            bobinInput.blur();
            if (document.activeElement && typeof document.activeElement.blur === "function") {
              document.activeElement.blur();
            }
          } else if (searchKeyword) {
            searchKeyword.value = cleanCode;
            searchKeyword.dispatchEvent(new Event("input", { bubbles: true }));
            searchKeyword.dispatchEvent(new Event("change", { bubbles: true }));
            searchKeyword.blur();
            if (filterForm) {
              setTimeout(() => {
                filterForm.submit();
              }, 300);
            }
          }
        }
      });
    });
  }

  // 2. QUÉT MÃ QR VỊ TRÍ RACK
  if (btnScanRackQR && qrReaderRackDiv) {
    btnScanRackQR.addEventListener("click", function () {
      if (typeof QRScannerHelper === "undefined") {
        console.error("QRScannerHelper is not loaded!");
        return;
      }

      // Đảm bảo tắt bàn phím ảo trước khi mở camera
      if (document.activeElement && typeof document.activeElement.blur === "function") {
        document.activeElement.blur();
      }

      QRScannerHelper.toggle("qr-reader-rack", {
        title: tr("qr_scanner_rack_title", "Quét mã QR Vị trí Rack"),
        searchingText: tr("qr_scanner_rack_searching", "Đang quét mã QR Vị trí Rack..."),
        onOpen: function () {
          updateRackBtnState(true);
          qrReaderRackDiv.scrollIntoView({ behavior: "smooth", block: "nearest" });
        },
        onClose: function () {
          updateRackBtnState(false);
        },
        onSuccess: function (decodedText) {
          updateRackBtnState(false);
          const cleanRack = (decodedText || "").trim();

          const rackInput = document.getElementById("rack_code");
          const rackSuggestions = document.getElementById("rack_suggestions");

          if (rackInput) {
            rackInput.value = cleanRack;
            if (rackSuggestions) {
              rackSuggestions.innerHTML = "";
              rackSuggestions.style.display = "none";
            }
            rackInput.dispatchEvent(new Event("change", { bubbles: true }));
            // Tuyệt đối không gọi focus() để bàn phím ảo không hiện lên sau khi quét xong
            rackInput.blur();
            if (document.activeElement && typeof document.activeElement.blur === "function") {
              document.activeElement.blur();
            }
          }
        }
      });
    });
  }
});
