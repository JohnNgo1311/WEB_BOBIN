/**
 * ==============================================================================
 * EXTRUSION / EDIT - SCAN QR CODE (WEB_BOBIN)
 * ==============================================================================
 */

document.addEventListener("DOMContentLoaded", function () {
  const btnScanQR = document.getElementById("btnScanQR");
  const qrReaderDiv = document.getElementById("qr-reader");
  if (!btnScanQR || !qrReaderDiv) return;

  function tr(key, fallback) {
    if (typeof window.t === "function") {
      const val = window.t(key);
      if (val && val !== key) return val;
    }
    return fallback;
  }

  // Lưu lại HTML ban đầu của nút
  const originalBtnHTML = btnScanQR.innerHTML;

  function updateButtonState(isScanning) {
    if (isScanning) {
      btnScanQR.classList.add("is-scanning");
      btnScanQR.innerHTML = `
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <line x1="18" y1="6" x2="6" y2="18"></line>
            <line x1="6" y1="6" x2="18" y2="18"></line>
        </svg>
        <span>${tr("btn_close_camera", "Đóng Camera")}</span>
      `;
    } else {
      btnScanQR.classList.remove("is-scanning");
      btnScanQR.innerHTML = originalBtnHTML;
    }
  }

  btnScanQR.addEventListener("click", function () {
    if (typeof QRScannerHelper === "undefined") {
      console.error("QRScannerHelper is not loaded!");
      return;
    }

    QRScannerHelper.toggle("qr-reader", {
      onOpen: function () {
        updateButtonState(true);
      },
      onClose: function () {
        updateButtonState(false);
      },
      onSuccess: function (decodedText) {
        updateButtonState(false);

        const bobinInput = document.getElementById("bobin_identification_code");
        const searchKeyword = document.getElementById("searchKeyword") || document.getElementById("keyword");
        const filterForm = document.getElementById("filterForm");

        if (bobinInput) {
          bobinInput.value = decodedText;
          bobinInput.dispatchEvent(new Event("input", { bubbles: true }));
          bobinInput.dispatchEvent(new Event("change", { bubbles: true }));
          bobinInput.focus();
        } else if (searchKeyword) {
          searchKeyword.value = decodedText;
          searchKeyword.dispatchEvent(new Event("input", { bubbles: true }));
          searchKeyword.dispatchEvent(new Event("change", { bubbles: true }));
          if (filterForm) {
            setTimeout(() => {
              filterForm.submit();
            }, 300);
          }
        }
      }
    });
  });
});
