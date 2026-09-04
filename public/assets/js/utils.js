/*MODULE TOAST: Hiển thị thông báo*/
const Toast = {
    show: function (message, type = 'success') {
        const toast = document.createElement('div');
        toast.className = `toast-message ${type === 'error' ? 'toast-error' : ''}`;
        toast.innerHTML = message;

        document.body.appendChild(toast);

        // Hiệu ứng
        setTimeout(() => toast.classList.add('show'), 10);

        // Tự động tắt
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => document.body.removeChild(toast), 500);
        }, 3000);
    }
};

/**
 * MODULE FORM: Xử lý gửi Form bằng AJAX
 * @param {string} formSelector - ID hoặc class của form (vd: '#myForm')
 * @param {string} apiUrl - Đường dẫn API
 * @param {function} onSuccessCallback - Hàm chạy khi thành công (tùy chọn)
 */
function registerAjaxForm(formSelector, apiUrl, onSuccessCallback = null) {
    const form = document.querySelector(formSelector);
    if (!form) return;

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        console.log(`Thực hiện tác vụ: ${formSelector} -> ${apiUrl}`);
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn ? submitBtn.innerText : '';
        if (submitBtn) {
            submitBtn.innerText = 'Đang lưu...';
            submitBtn.disabled = true;
        }

        const fd = new FormData(form);
        console.log("Dữ liệu FormData:", [...fd.entries()]);
        fetch(apiUrl, {
            method: 'POST',
            body: fd
        })
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    Toast.show(res.message, 'success');
                    form.reset();
                    // Gọi hàm xử lý riêng (nếu có)
                    if (typeof onSuccessCallback === 'function') {
                        onSuccessCallback(res, form);
                    }
                } else {
                    Toast.show("❌ Lỗi: " + res.message, 'error');
                }
            })
            .catch(err => {
                console.error(err);
                Toast.show("❌ Đã có lỗi xảy ra khi gửi dữ liệu. " + err.message, 'error');
            })
            .finally(() => {
                // Trả lại nút bấm
                if (submitBtn) {
                    submitBtn.innerText = originalText;
                    submitBtn.disabled = false;
                }
            });
    });
}

// MODULE CLICK OUTSIDE: Đóng hộp khi click ngoài
function registerOutsideClickCleaner(pairs) {
    document.addEventListener('click', (e) => {
        pairs.forEach(pair => {
            const inputEl = document.getElementById(pair.input);
            const boxEl = document.getElementById(pair.box);
            if (boxEl && inputEl && e.target !== inputEl && !boxEl.contains(e.target)) {
                boxEl.style.display = 'none';
            }
        });
    });
}
