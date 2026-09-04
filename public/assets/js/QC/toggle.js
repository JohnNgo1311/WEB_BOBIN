function toggleStopInputs(checkbox) {
  // Lấy 2 ô input dựa vào ID
  const dateInput = document.getElementById("stopDateInput");
  const timeInput = document.getElementById("stopTimeInput");

  if (checkbox.checked) {
    // Nếu được tick: Mở khóa cho phép nhập
    dateInput.disabled = false;
    timeInput.disabled = false;

    // (Tùy chọn) Tự động focus con trỏ vào ô Ngày để nhập luôn cho tiện
    dateInput.focus();
  } else {
    // Nếu bỏ tick: Khóa lại
    dateInput.disabled = true;
    timeInput.disabled = true;

    // (Quan trọng) Xóa luôn dữ liệu lỡ nhập trước đó để tránh đẩy rác lên Database
    dateInput.value = "";
    timeInput.value = "";
  }
}
