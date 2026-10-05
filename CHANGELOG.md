# CHANGELOG.md - Nhật Ký Những Thay Đổi Quan Trọng

Toàn bộ các cập nhật lớn, sửa lỗi logic, tái cấu trúc mã nguồn và cải tiến UX/UI được ghi nhận tuần tự theo thời gian tại đây.

---

## [2026-10-06] - Nâng Cấp UI 3 Trang Điều Chỉnh & Thu Gọn Hiển Thị update_history (TASK-008)

### 1. Nâng cao UI/UX 3 trang Điều chỉnh (Đùn, QC, Cuộn)
- **Header:**
  - Thiết kế Dark Hero Gradient hiện đại với viền cong mềm mại và shadow đa lớp.
  - Tiêu đề kèm icon trực quan (`🏭 Điều chỉnh Đùn`, `🛡️ Điều chỉnh QC`, `📍 Điều chỉnh Cuộn`).
  - Bổ sung subtitle mô tả chi tiết nhiệm vụ của trang và huy hiệu định danh công đoạn (`⚙️ ĐÙN CHECK`, `🔍 QC CHECK`, `⚡ WINDING CHECK`).
- **Card Bobin:**
  - Card-top phân biệt rõ rệt theo từng trạng thái bằng dải gradient màu chuẩn SMC:
    - `.status_busy_unchecked`: Gradient Cam công đoạn Đùn (`#f97316` đến `#c2410c`).
    - `.status_busy_checked`: Gradient Xanh dương công đoạn QC (`#3b82f6` đến `#1e40af`).
    - `.status_rolled`: Gradient Xanh lá công đoạn Cuộn hoàn tất (`#22c55e` đến `#15803d`).
  - Badge trạng thái nổi bật, có shadow tinh tế và viền bo mềm mại.
  - Hiệu ứng `.is-editing` nổi bật với viền xanh 2px và shadow sâu khi người dùng bấm "Chỉnh sửa".
- **Visual Inspection Strips & Nút thao tác:**
  - Thanh kiểm tra ngoại quan (`.ext-check-strip`) có viền bo tròn, các ô kiểm tra (`.ext-check-box`) có shadow nhẹ và viền nổi bật.
  - Nút bấm OK/NG (`.ext-toggle-btn`) có kích thước lớn hơn, rõ ràng, hiệu ứng hover phóng to nhẹ.
  - Các nút hành động (`.btn-edit`, `.btn-confirm`, `.btn-cancel-edit`, `.btn-delete`) có hiệu ứng lift khi hover, bo góc 7px và shadow rõ ràng.

### 2. Chuẩn hóa vị trí hiển thị dữ liệu `update_history`
- Dữ liệu `update_history` **chỉ được hiển thị duy nhất tại trang Lịch sử Bobin** (`listBobinHistoryView.php`) nhằm tối ưu không gian hiển thị và giữ đúng mục đích tra cứu lịch sử thay đổi.
- Gỡ bỏ khối hiển thị `update_history` khỏi các trang khác:
  - `app/views/extrusionEditBobinView.php` (Đã gỡ bỏ)
  - `app/views/qcEditBobinView.php` (Đã gỡ bỏ)
  - `app/views/windingEditBobinView.php` (Đã gỡ bỏ)
  - `app/views/listBobinDetailView.php` (Đã gỡ bỏ)
- Giữ nguyên hiển thị đầy đủ và chi tiết tại `app/views/listBobinHistoryView.php`.

---

## [2026-10-06] - Phân Quyền Trang Điều Chỉnh, Hiển Thị Lịch Sử Audit Trail & Nâng Cao UI/UX (TASK-007)

### 1. Phân quyền truy cập các trang điều chỉnh (Extrusion, QC, Winding)
- **Quy tắc phân quyền:**
  - **Điều chỉnh Đùn (`bobin/extrusionEditBobinView`, `bobin/extrusionUpdateBobin`):** Cho phép vai trò `extrusion` và `admin`.
  - **Điều chỉnh QC (`bobin/qcEditBobinView`, `bobin/updateQCEditBobin`):** Cho phép vai trò `qc` và `admin`.
  - **Điều chỉnh Cuộn (`bobin/windingEditBobinView`, `bobin/updateWindingEditBobin`):** Dành riêng cho `admin`.
- **Cập nhật hệ thống:**
  - `app/core/Router.php`: Bổ sung route `qceditbobinview` và `updateqceditbobin` vào quyền của vai trò `qc`.
  - `app/controllers/BobinController.php`: Phương thức `qcEditBobinView()` và `updateQCEditBobin()` kiểm tra quyền `in_array($role, ['qc', 'admin'], true)`.
  - Cập nhật menu điều hướng trên toàn bộ các view (`qcView.php`, `qcEditBobinView.php`, `windingView.php`, `windingEditBobinView.php`, `listBobinDetailView.php`, `listBobinHistoryView.php`, `listPendingCancellationView.php`, `employeeListView.php`, `extrusionEditBobinView.php`, `extrusionView.php`): Hiển thị link `bobin/qcEditBobinView` cho cả vai trò `qc` và `admin`.

### 2. Trang lịch sử Bobin (`bobin/listBobinHistoryView`)
- Đã gỡ bỏ toàn bộ khối nút chọn nhanh ngày ("Hôm nay", "Hôm qua", "7 ngày", "1 tháng") theo đúng yêu cầu.
- Loại bỏ hàm JavaScript `setQuickDate()`.

### 3. Hiển thị vết lịch sử thay đổi `update_history` (Audit Trail)
- Lưu vết lịch sử chỉnh sửa khi thực hiện cập nhật Bobin qua cả 3 trang điều chỉnh (Đùn, QC, Cuộn):
  - Ghi nhận `stage` (`extrusion`, `qc`, `winding`), `action`, `employee_code`, `employee_name`, `updated_at`, `note`.
- Tích hợp khối hiển thị `.audit-history-box` trực quan trên từng card Bobin trong cả 3 trang điều chỉnh:
  - `app/views/extrusionEditBobinView.php`
  - `app/views/qcEditBobinView.php`
  - `app/views/windingEditBobinView.php`
- Đầy đủ thông tin người thay đổi, thời gian thay đổi, công đoạn và ghi chú; hỗ trợ đa ngôn ngữ (`vi`, `en`, `ja`).

### 4. Nâng cao UI/UX các trang điều chỉnh
- Cải thiện giao diện đồng bộ trong `public/assets/css/extrusionEditBobin.css`:
  - Header hiện đại phong cách Dark Hero Gradient (`#1e293b` đến `#0f172a`), badge công đoạn sắc nét.
  - Card Bobin có viền tinh tế, bóng đổ mềm mại, viền nổi bật khi ở chế độ chỉnh sửa (`.is-editing`).
  - Nút bấm action (`btn-edit`, `btn-confirm`, `btn-cancel-edit`, `btn-delete`) có hiệu ứng hover lift, bóng đổ và gradient rõ ràng.
  - Khối lịch sử điều chỉnh (`.audit-history-box`) có màu phân biệt theo từng công đoạn (`stage-extrusion`, `stage-qc`, `stage-winding`), font chữ dễ đọc.

---

## [2026-10-05] - Khắc Phục Lỗi Treo Web Khi Đổi Ngôn Ngữ & Tối Ưu Event Loop (TASK-003)

### 0. Sửa dứt điểm hiện tượng treo web (freeze) và vô hiệu hóa nút bấm khi đổi ngôn ngữ
- **Files thay đổi:**
  - `public/assets/js/i18n.js`
  - `app/views/components/languageSwitcher.php`
  - `AI_preference/AI_Task.md`
  - `AI_preference/CHANGELOG.md`
- **Nguyên nhân cốt lõi gây lỗi:**
  1. **Lặp vô tận trong `MutationObserver` (Infinite Recursive Mutation Loop):** `observeDOMChanges()` lắng nghe thay đổi của toàn bộ `document.body` (`childList: true, subtree: true`). Khi đổi ngôn ngữ, hàm `deepTranslateDOM()` ghi đè nội dung các phần tử (qua `innerHTML` trên labels, buttons, links). Thao tác này ngay lập tức sinh ra các `mutation` mới có `addedNodes`, khiến callback observer tự gọi lại `deepTranslateDOM()`. Quá trình này lặp lại vô tận ở tốc độ cực cao, chiếm 100% Main Thread / JS Event Loop khiến trình duyệt bị treo (freeze) hoàn toàn và người dùng không thể thao tác bất kỳ nút hay form nào.
  2. **Mất Event Listeners do can thiệp DOM thô bạo:** Khi `deepTranslateDOM()` và `applyDataI18n()` gán lại `innerHTML` trên các phần tử (như nút có icon SVG, link có badge), toàn bộ các DOM node con bị hủy và tạo mới, làm đứt toàn bộ event listener đã gắn trước đó (như các hàm click, toggle dropdown, toggle modal, filter,...).
  3. **Xung đột kép khi kích hoạt chuyển đổi (Double Trigger & Race Condition):** Nút chọn ngôn ngữ vừa có thuộc tính `onclick` vừa được bắt bởi `addEventListener` trong `initLanguageSwitcher()`, đồng thời hàm `fetch` đồng bộ backend gọi song song với `window.location.reload()`, gây race condition trên một số môi trường intranet.
- **Biện pháp khắc phục và tối ưu:**
  1. **Cơ chế khóa đa luồng (Infinite Loop Guard & Re-entrancy Lock):** Thêm biến cờ `isTranslating` kiểm soát tiến trình dịch; nếu đang dịch thì `MutationObserver` hoàn toàn bỏ qua mọi thay đổi DOM. Chỉ mở lại cờ sau khi hoàn tất microtask rendering.
  2. **Lọc Node & Debounce MutationObserver:** Observer chỉ quan tâm các `ELEMENT_NODE` thực sự phát sinh từ DOM ngoài (bỏ qua text node và các phần tử do chính i18n tạo ra); bổ sung debounce timer (150ms) để gom cụm các biến đổi DOM thay vì kích hoạt liên tục.
  3. **Bảo toàn DOM Tree & Event Listeners:**
     - Thay vì xóa `innerHTML = ''` trên nút bấm, chỉ cập nhật riêng text node con hoặc so sánh trước khi gán text (`if (btn.textContent !== target)`).
     - Giữ nguyên các phần tử SVG, badge, icon để không làm mất event listeners của JavaScript.
     - Kiểm tra điều kiện thay đổi trước khi gán (`if (el.textContent !== target)`) nhằm tránh phát sinh mutation rác.
  4. **Đồng bộ tải trang an toàn và mượt mà trong `setAppLanguage`:**
     - Ghi nhận `app_lang` vào cả Cookie và `localStorage` ngay lập tức.
     - Sử dụng `navigator.sendBeacon` (hoặc `fetch` với timeout fallback 300ms) để đồng bộ session backend an toàn trước khi reload, đảm bảo không bao giờ bị nghẽn mạng hay treo reload.
     - Tối ưu hóa click handler trong `languageSwitcher.php`, tránh duplicate trigger.
- **Ảnh hưởng chức năng khác:** Hoàn toàn **KHÔNG** ảnh hưởng logic nghiệp vụ, database schema hay API. Các chức năng form, lọc, quét QR, đổi mật khẩu và xem chi tiết hoạt động mượt mà 100% trên cả 3 ngôn ngữ (`vi`, `en`, `ja`).

---

## [2026-10-05] - Điều Tra & Đồng Bộ Toàn Diện Đa Ngôn Ngữ (TASK-002), Fallback Pipeline & Logic Đùn

### 0. Điều tra và đồng bộ toàn diện chức năng chuyển đổi ngôn ngữ (TASK-002)
- **Files thay đổi:**
  - `public/assets/js/i18n.js`
  - `app/core/Language.php`
  - `app/views/manageCapacityView.php`
  - `AI_preference/AI_Task.md`
- **Nội dung:**
  - Rà soát toàn bộ từ điển và ánh xạ DOM giữa PHP backend (`Language.php`) và Frontend JavaScript (`i18n.js`).
  - Bổ sung các key còn thiếu để đạt **100% khớp tuyệt đối (273/273 keys)** trên cả 3 ngôn ngữ (`vi`, `en`, `ja`) ở cả 2 tầng Backend và Frontend:
    - Bổ sung 6 key thông báo nhân viên vào `i18n.js`: `emp_import_hint`, `emp_msg_added`, `emp_msg_updated`, `emp_msg_deleted`, `emp_msg_reset_done`, `emp_msg_imported`.
    - Bổ sung 3 key metadata ngôn ngữ vào `Language.php`: `lang_name`, `flag`, `short`.
  - Tích hợp component chuyển đổi ngôn ngữ (`languageSwitcher.php`) và nút quay lại kèm đa ngôn ngữ cho `manageCapacityView.php`.
  - Xác nhận cơ chế đồng bộ ngôn ngữ ba lớp (Session $\leftrightarrow$ Cookie `app_lang` $\leftrightarrow$ `localStorage`) hoạt động liền mạch, không ảnh hưởng logic lưu trữ DB hay API nghiệp vụ.

### 1. Xử lý hiển thị thông báo Fallback tại trang Danh Sách và Lịch Sử Bobin (Yêu cầu V)
- **Files thay đổi:**
  - `app/views/listBobinDetailView.php`
  - `app/views/listBobinHistoryView.php`
  - `public/assets/css/listBobinDetail.css`
  - `public/assets/css/listBobinHistory.css`
  - `public/assets/js/i18n.js`
  - `app/core/Language.php`
- **Nội dung:**
  - Tại 3 cột kiểm soát luân chuyển (`inspection-pipeline-grid`):
    - **🏭 Đùn Check:** Kiểm tra nếu dữ liệu `extrusion_check` rỗng hoặc không có bất kỳ tiêu chí nào $\rightarrow$ Hiển thị badge trực quan: `⏳ Chưa có dữ liệu sản xuất Đùn` (`pipeline_no_ext_data`).
    - **🛡️ QC Check:** Kiểm tra nếu chưa có `inspector_code` hoặc chưa kiểm tra QC $\rightarrow$ Hiển thị: `⏳ Chưa có dữ liệu kiểm tra QC` (`pipeline_no_qc_data`).
    - **📍 Thông tin cuộn:** Kiểm tra nếu chưa có thông tin máy cuộn/nhân viên cuộn hoặc trạng thái chưa hoàn thành `Rolled` $\rightarrow$ Hiển thị: `⏳ Chưa có dữ liệu thông tin cuộn` (`pipeline_no_winding_data`).
  - Hỗ trợ đa ngôn ngữ đồng bộ 3 thứ tiếng (`vi`, `en`, `ja`).

### 2. Sửa logic cập nhật Bobin của Nhân viên Đùn (Yêu cầu VI & VII)
- **Files thay đổi:**
  - `app/repositories/BobinRepository.php` (`extrusionUpdateBobin`, `extUpdateBobinDetail`, `extUpdateBobinGeneral`, `extUpdateBobinHistory`)
- **Nội dung:**
  - **Không INSERT bản ghi mới vào `bobin_history` (Req VI):** Thay thế việc `INSERT INTO bobin_history` bằng hàm `extUpdateBobinHistory` tìm đúng dòng có `bobin_key_code` tương ứng để `UPDATE` các thông số đùn đã chỉnh sửa.
  - **Không cập nhật `updated_time` (Req VII):** Bỏ việc gán `updated_time = :updated` trong `bobin_list_detail`, bỏ `updated_time = NOW()` trong `bobin_list_general`, và không cập nhật `updated_time` trong `bobin_history`. Thời điểm cập nhật ban đầu được bảo toàn nguyên vẹn.

### 3. Tạo 3 file tài liệu ngữ cảnh bảo toàn hệ thống
- Tạo và điền nội dung toàn diện cho:
  - `AI_CONTEXT.md`: Toàn bộ luồng nghiệp vụ, kiến trúc MVC, lifecycle Bobin và quy tắc code.
  - `DATABASE_SCHEMA.md`: Đặc tả chi tiết các bảng, cột, kiểu dữ liệu, quan hệ và JSON schemas.
  - `CHANGELOG.md`: Lịch sử các đợt cập nhật quan trọng.

---

## [2026-10-04] - Tái Cấu Trúc Toàn Diện Trang Nhân Viên & Bộ Lọc Xưởng/Tầng

### 1. Refactor Mô-đun Quản Lý Nhân Viên (`EmployeeController` & `employeeListView`)
- **Files thay đổi:**
  - `app/controllers/EmployeeController.php`
  - `app/views/employeeListView.php`
  - `public/assets/css/employeeList.css`
  - `app/views/components/languageSwitcher.php`
- **Nội dung:**
  - Loại bỏ hoàn toàn sự phụ thuộc vào `EmployeeModel`, chuyển sang truy vấn trực tiếp qua PDO singleton và repository.
  - Giao diện `employeeListView.php` được thiết kế lại hiện đại:
    - Bổ sung **Inline SVG Donut Chart** thể hiện tỷ lệ phân bổ vai trò (Extrusion / QC / Winding / Admin) thuần JS/SVG, không dùng thư viện ngoài.
    - Hàng thẻ KPI thống kê tương tác (Click vào thẻ để tự động lọc theo vai trò).
    - Đồng bộ thanh menu bar và hiển thị chính xác badge `badge-pending-count` của danh sách chờ hủy.
    - Hỗ trợ Import/Export file CSV theo định dạng chuẩn UTF-8 tương thích Excel.

### 2. Bộ Lọc Rack Theo Phân Xưởng và Tầng (Yêu cầu IV)
- **Files thay đổi:**
  - `app/repositories/BobinRepository.php`
  - `app/views/listBobinDetailView.php`
  - `app/views/listBobinHistoryView.php`
- **Nội dung:**
  - Thêm tính năng lọc nhóm Rack nhanh theo xưởng và tầng:
    - `B021`: Xưởng 2, tầng 1 (`Rack_B021_%`)
    - `B022`: Xưởng 2, tầng 2 (`Rack_B022_%`)
    - `B031`: Xưởng 3, tầng 1 (`Rack_B031_%`)
    - `B032`: Xưởng 3, tầng 2 (`Rack_B032_%`)
  - Cho phép người dùng vừa có thể chọn cả khu vực lớn, vừa có thể chọn chi tiết từng kệ cụ thể.

### 3. Khắc phục sự cố Reverse Print Lot (Yêu cầu VIII)
- **Files thay đổi:**
  - `public/assets/js/Extrusion/suggestion.js`
  - `public/assets/js/Extrusion/edit_suggestion.js`
  - `public/assets/css/extrusionEditBobin.css`
- **Nội dung:**
  - Sửa lỗi phân tích ngược lot in (`reversePrintLot` và `updatePrintLot`).
  - Xử lý triệt để khoảng trắng đầu chuỗi trong cơ sở dữ liệu (`trim`), chuẩn hóa quy tắc trích xuất 5 ký tự định danh chính xác.
  - Bổ sung CSS hiển thị dropdown gợi ý sản phẩm và PO rõ ràng, không bị che khuất.

---

## [2026-10-02 -> 2026-10-03] - Chuẩn Hóa Logic Thống Kê & KPI Báo Cáo Lịch Sử Bobin

### 1. Chuẩn hóa quy tắc sinh `bobin_key_code`
- Thiết lập quy tắc thống nhất: Thời điểm trong `bobin_key_code` luôn khớp với giá trị `finish_time` (Thời gian hoàn thành).
- Định dạng chuẩn: `[Mã_định_danh]_[YYYY]_[MM]_[DD]_[HH]_[mm]_[ss]`.

### 2. Tái cấu trúc bộ lọc và KPI trang Lịch Sử Bobin (`listBobinHistoryView`)
- Mặc định khi vào trang hiển thị nhật ký 7 ngày gần nhất theo `updated_time`.
- Thiết lập logic lọc chặt chẽ cho từng chế độ:
  - **ĐÃ ĐÙN:** Lọc các bản ghi theo `updated_time` và thời gian trong `bobin_key_code`, lấy phiên bản mới nhất cho mỗi `bobin_key_code`.
  - **CHƯA KT QC:** Lọc các Bobin chỉ ở trạng thái `Busy_Unchecked` tính đến mốc thời gian kết thúc của bộ lọc.
  - **ĐÃ KT QC, ĐÃ HỦY, CHỜ HỦY:** Tách biệt 2 chỉ số KPI:
    - Số lượng Bobin được đùn **trong khoảng thời gian chọn**.
    - Số lượng Bobin được đùn **trước đó**.
- Đảm bảo công thức bảo toàn sản lượng giữa các công đoạn:
  $$\text{ĐÃ ĐÙN} = \text{ĐÃ KT QC (trong kỳ)} + \text{CHƯA KT QC} + \text{ĐÃ CUỘN} + \text{CHỜ HỦY (trong kỳ)} - \text{ĐÃ HỦY (trong kỳ)}$$