const Toast = {
  show(message, type = "success") {
    const toast = document.createElement("div");
    toast.className = `toast-message${type === "error" ? " toast-error" : ""}`;
    toast.textContent = message;
    document.body.appendChild(toast);

    requestAnimationFrame(() => {
      requestAnimationFrame(() => toast.classList.add("show"));
    });

    setTimeout(() => {
      toast.classList.remove("show");
      setTimeout(() => toast.remove(), 500);
    }, 3000);
  },
};

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
