if (typeof window.Toast === "undefined") {
  window.Toast = {
    initStyle() {
      if (!document.getElementById("toast-style-css")) {
        const style = document.createElement("style");
        style.id = "toast-style-css";
        style.innerHTML = `
          .toast-message {
            position: fixed; top: 24px; right: 24px;
            padding: 12px 20px; border-radius: 8px;
            box-shadow: 0 10px 25px -5px rgba(0,0,0,0.2), 0 8px 10px -6px rgba(0,0,0,0.2);
            font-family: var(--font-family-base, 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif);
            font-size: 13.5px; font-weight: 700;
            color: #fff; z-index: 99999; opacity: 0;
            transform: translateY(-20px); transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex; align-items: center; gap: 8px;
            pointer-events: none;
            max-width: calc(100vw - 48px);
          }
          .toast-message.show { opacity: 1; transform: translateY(0); }
          .toast-success { background-color: #16a34a !important; }
          .toast-error { background-color: #dc2626 !important; }
          .toast-warning { background-color: #f59e0b !important; }
          .toast-info { background-color: #2563eb !important; }
          @media (max-width: 640px) {
            .toast-message {
              top: 16px; right: 16px; left: 16px;
              justify-content: center; text-align: center; max-width: none;
            }
          }
        `;
        document.head.appendChild(style);
      }
    },
    show(message, type = "success") {
      this.initStyle();
      document.querySelectorAll(".toast-message").forEach((el) => el.remove());

      const toast = document.createElement("div");
      toast.className = `toast-message toast-${type}`;
      const icon = type === "error" ? "❌" : (type === "warning" ? "⚠️" : "✅");
      toast.innerHTML = `<span>${icon}</span> <span>${message}</span>`;
      document.body.appendChild(toast);

      requestAnimationFrame(() => {
        requestAnimationFrame(() => toast.classList.add("show"));
      });

      setTimeout(() => {
        toast.classList.remove("show");
        setTimeout(() => toast.remove(), 400);
      }, 3000);
    },
  };
}
var Toast = window.Toast;


function copyToClipboard(button) {
  const textToCopy = button.getAttribute("data-copy");

  if (textToCopy == "Chưa cập nhật đầy đủ thông tin cần thiết") {
    Toast.show("Không có nội dung để copy!", "error");
    return;
  }

  const originalHTML = button.innerHTML;

  const handleSuccess = () => {
    button.innerHTML = "✅ Đã copy";
    button.style.backgroundColor = "#d4edda";
    Toast.show("Đã copy nội dung!", "success");

    setTimeout(() => {
      button.innerHTML = originalHTML;
      button.style.backgroundColor = "";
    }, 2000);
  };

  const handleError = (err) => {
    console.error(err);
    Toast.show("Không thể copy nội dung!", "error");
  };

  if (
    typeof navigator !== "undefined" &&
    navigator.clipboard &&
    typeof navigator.clipboard.writeText === "function"
  ) {
    navigator.clipboard
      .writeText(textToCopy)
      .then(handleSuccess)
      .catch(handleError);
  } else {
    const textArea = document.createElement("textarea");
    textArea.value = textToCopy;
    textArea.style.position = "fixed";
    textArea.style.top = "0";
    textArea.style.left = "-999999px";

    document.body.appendChild(textArea);
    textArea.focus();
    textArea.select();

    try {
      const successful = document.execCommand("copy");
      if (successful) {
        handleSuccess();
      } else {
        handleError(new Error("execCommand false"));
      }
    } catch (err) {
      handleError(err);
    }

    document.body.removeChild(textArea);
  }
}
