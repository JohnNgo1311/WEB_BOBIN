/**
 * File: public/assets/js/autoSearchBobin.js
 * ==============================================================================
 * Tự động tìm kiếm & lọc Bobin tức thì khi nhập đủ mã Bobin (ví dụ: C0001)
 * Áp dụng cho: QC, Cuộn, Lịch sử, Chi tiết, Chờ hủy và các trang Danh sách Bobin.
 * ==============================================================================
 */

(function () {
  'use strict';

  function initAutoSearch() {
    // 1. Tìm ô tìm kiếm từ khóa
    const searchInput =
      document.getElementById('searchKeyword') ||
      document.getElementById('searchInput') ||
      document.querySelector('input[name="keyword"]');

    if (!searchInput) return;

    // 2. Tìm form lọc chứa ô tìm kiếm
    const filterForm =
      searchInput.closest('form') ||
      document.getElementById('filterForm') ||
      document.getElementById('searchForm');

    if (!filterForm) return;

    // 3. Khởi tạo trạng thái ban đầu
    const initialKeyword = (searchInput.value || '').trim();
    let isSubmitting = false;
    let debounceTimer = null;

    // Định dạng mã Bobin chuẩn trong hệ thống: 1 chữ cái + 4 chữ số (VD: A0001, B0050, C0001)
    const BOBIN_CODE_REGEX = /^[A-Za-z][0-9]{4}$/;
    // Hoặc mã quét dạng key code từ QR: C0001_2026_10_04_...
    const BOBIN_KEY_REGEX = /^[A-Za-z][0-9]{4}_/;

    // 4. Nhúng CSS bổ trợ nút Clear (✕) và hiệu ứng tương tác
    if (!document.getElementById('auto-search-bobin-style')) {
      const style = document.createElement('style');
      style.id = 'auto-search-bobin-style';
      style.textContent = `
        .search-box-modern, .qc-search-wrap, .wnd-search-wrap, .ext-search-wrap, .search-wrapper, .scan-input-wrap {
          position: relative !important;
        }
        .btn-clear-search {
          position: absolute;
          right: 10px;
          top: 50%;
          transform: translateY(-50%);
          background: rgba(148, 163, 184, 0.25);
          border: none;
          color: #64748b;
          font-size: 11px;
          font-weight: bold;
          cursor: pointer;
          width: 20px;
          height: 20px;
          border-radius: 50%;
          display: flex;
          align-items: center;
          justify-content: center;
          z-index: 5;
          transition: all 0.15s ease;
          line-height: 1;
          padding: 0;
        }
        .btn-clear-search:hover {
          background: #ef4444;
          color: #ffffff;
        }
        .btn-modern.is-searching, .qc-btn-filter.is-searching, .wnd-btn-filter.is-searching, .btn-filter.is-searching {
          opacity: 0.65;
          cursor: wait;
          pointer-events: none;
        }
      `;
      document.head.appendChild(style);
    }

    // 5. Thêm nút Xóa nhanh (Clear button ✕)
    const wrapper = searchInput.parentElement;
    let clearBtn = null;
    if (wrapper) {
      clearBtn = wrapper.querySelector('.btn-clear-search');
      if (!clearBtn) {
        clearBtn = document.createElement('button');
        clearBtn.type = 'button';
        clearBtn.className = 'btn-clear-search';
        clearBtn.innerHTML = '&#10005;'; // ✕
        clearBtn.title = 'Xóa tìm kiếm (Reset danh sách)';
        clearBtn.style.display = searchInput.value.trim() ? 'flex' : 'none';

        clearBtn.addEventListener('click', function (e) {
          e.preventDefault();
          e.stopPropagation();
          searchInput.value = '';
          clearBtn.style.display = 'none';

          // Nếu URL đang có tham số lọc keyword -> Tự động nộp form rỗng để quay lại danh sách gốc
          const urlParams = new URLSearchParams(window.location.search);
          if (urlParams.get('keyword')) {
            doSubmitForm();
          } else {
            searchInput.focus();
          }
        });

        wrapper.appendChild(clearBtn);
      }
    }

    function updateClearBtnVisibility() {
      if (clearBtn) {
        clearBtn.style.display = searchInput.value.trim() ? 'flex' : 'none';
      }
    }

    // 6. Hàm nộp form tự động và an toàn (chống double-submit)
    function doSubmitForm() {
      if (isSubmitting) return;
      isSubmitting = true;

      const submitBtn =
        filterForm.querySelector('button[type="submit"]') ||
        filterForm.querySelector('.btn-filter') ||
        filterForm.querySelector('.qc-btn-filter') ||
        filterForm.querySelector('.wnd-btn-filter') ||
        document.getElementById('btnFilter');

      if (submitBtn) {
        submitBtn.classList.add('is-searching');
      }

      filterForm.submit();
    }

    // 7. Xử lý khi người dùng nhập dữ liệu hoặc quét barcode / QR
    function handleKeywordInput() {
      updateClearBtnVisibility();

      let val = searchInput.value.trim();

      // Trường hợp quét mã đầy đủ có chứa timestamp (VD: C0001_2026_10_04_18_29_44)
      if (BOBIN_KEY_REGEX.test(val)) {
        val = val.substring(0, 5).toUpperCase();
        searchInput.value = val;
      }

      // Kiểm tra khi đã nhập đủ 5 ký tự mã nhận diện Bobin (VD: C0001, A0001, B0050)
      if (BOBIN_CODE_REGEX.test(val)) {
        val = val.toUpperCase();
        if (searchInput.value !== val) {
          searchInput.value = val;
        }

        // Chỉ lọc nếu mã này khác với từ khóa ban đầu khi load trang (tránh vòng lặp reload)
        if (val !== initialKeyword.toUpperCase()) {
          clearTimeout(debounceTimer);
          debounceTimer = setTimeout(() => {
            doSubmitForm();
          }, 150);
        }
      }
    }

    // Lắng nghe các sự kiện nhập liệu
    searchInput.addEventListener('input', handleKeywordInput);
    searchInput.addEventListener('change', handleKeywordInput);
    searchInput.addEventListener('paste', () => {
      setTimeout(handleKeywordInput, 30);
    });

    // Nhấn phím Enter: Tự động chuẩn hóa và nộp form ngay lập tức
    searchInput.addEventListener('keydown', function (e) {
      if (e.key === 'Enter') {
        clearTimeout(debounceTimer);
        let val = searchInput.value.trim();
        if (BOBIN_CODE_REGEX.test(val) || BOBIN_KEY_REGEX.test(val)) {
          searchInput.value = val.substring(0, 5).toUpperCase();
        }
        doSubmitForm();
      }
    });

    // Tự động bôi đen toàn bộ mã khi focus
    // Giúp công nhân quét Bobin tiếp theo là đè ngay mã mới mà không cần xóa tay
    searchInput.addEventListener('focus', function () {
      if (this.value) {
        this.select();
      }
    });

    // Nếu trang vừa tải xong và có sẵn kết quả tìm kiếm: tự động focus và bôi đen
    if (initialKeyword) {
      setTimeout(() => {
        if (document.activeElement !== searchInput) {
          searchInput.focus();
          searchInput.select();
        }
      }, 150);
    }
  }

  // Khởi chạy khi DOM sẵn sàng
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initAutoSearch);
  } else {
    initAutoSearch();
  }
})();

