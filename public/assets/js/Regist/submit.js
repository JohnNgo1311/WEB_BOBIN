
registerAjaxForm(
    '#registBobinForm', // ID của form
    API_BASE_URL + 'bobin/registNewBobin', // API Endpoint
    // Callback sau khi thành công
    (res, form) => {
        // Tùy chọn: Sau khi đăng ký xong thì chuyển hướng về danh sách
        // Hoặc chỉ cần reset form (hàm registerAjaxForm đã tự reset rồi)
        setTimeout(() => {
            window.location.href = 'WEB_BOBIN/public/index.php?url=bobin/createBobinView';
        }, 1500); // Đợi 1.5s để người dùng đọc thông báo Toast rồi mới chuyển
    }
);