document.addEventListener('DOMContentLoaded', () => {
    // Tìm nút Logout
    const logoutBtn = document.querySelector('.logout-btn');

    if (logoutBtn) {
        logoutBtn.addEventListener('click', function (e) {
            e.preventDefault();

            const logoutUrl = this.getAttribute('href');

            ConfirmDialog.show(
                'Xác nhận đăng xuất',
                `
                <div style="background-color: #f8d7da; color: #721c24; padding: 10px 12px; border-radius: 4px; font-weight: bold; margin-bottom: 15px; border-left: 4px solid #f5c6cb; text-align: left;">
                ⚠️ Bạn có chắc chắn muốn đăng xuất khỏi hệ thống không?
                </div>
                `,
                () => {
                    window.location.href = logoutUrl;
                }
            );
        });
    }
});