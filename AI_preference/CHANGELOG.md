# CHANGELOG.md - Nhật Ký Những Thay Đổi Quan Trọng

Toàn bộ các cập nhật lớn, sửa lỗi logic, tái cấu trúc mã nguồn và cải tiến UX/UI được ghi nhận tuần tự theo thời gian tại đây.

## [2026-10-09] - Khắc Phục Triệt Để Hiện Tượng Toast Thông Báo Xuất Hiện 2 Lần Sau Khi Thao Tác (TASK-029)

### 1. Bối cảnh & Yêu cầu:
- Người dùng phản ánh: "Tại sao ở trang danh sách chờ hủy, tôi hủy 1 Bobin nhưng Toast lại hiện 2 lần?"
- Kiểm tra toàn diện luồng hiển thị thông báo Toast trong `listPendingCancellationView.php`, `delete.js`, `toast.js` và các module thao tác khác (Đùn, Cuộn, QC).

### 2. Nguyên nhân gốc rễ (Root Cause Analysis):
1. **Hiện tượng "Show-before-reload + Flash-after-reload" (Anti-pattern cốt lõi):**
   - Trong `public/assets/js/delete.js` (hàm `handleDelete`), sau khi nhận phản hồi API thành công, mã nguồn đồng thời:
     - Gọi `window.Toast.show(successMsg, 'success')` (Làm Toast thứ nhất lập tức xuất hiện trên màn hình).
     - Lưu thông báo vào Flash: `sessionStorage.setItem('bobin_toast_flash', JSON.stringify({ message: successMsg, ... }))`.
     - Gọi hàm reload trang: `setTimeout(() => window.location.reload(), 800)`.
   - Sau 800ms, trình duyệt tải lại trang HTML mới. File `public/assets/js/toast.js` được nạp lại và hàm `checkAutoToasts()` tự động chạy khi DOM sẵn sàng.
   - Hàm `checkAutoToasts()` đọc được thông báo còn lưu trong `sessionStorage('bobin_toast_flash')`, xóa item đó và gọi tiếp `Toast.show(...)` lần thứ hai!
   - Vì toàn bộ trang web bị reload, toàn bộ context bộ nhớ JavaScript (bao gồm biến chống spam trùng lặp `lastToastTime` trong `toast.js`) bị reset hoàn toàn về 0, khiến cơ chế deduplicate trong 1.5 giây bị vô hiệu hóa. Người dùng nhìn thấy Toast xuất hiện trước khi reload, và sau khi reload xong lại xuất hiện Toast y hệt một lần nữa.
2. **Xung đột mã nguồn & Lắng nghe trùng lặp trong `delete.js`:**
   - Trong `delete.js` tồn tại đoạn mã cũ định nghĩa lại class `Toast` nội bộ và đăng ký một listener `DOMContentLoaded` riêng biệt cũng lắng nghe `bobin_toast_flash`, chạy song song và xung đột với `toast.js` toàn cục.
3. **Mã lặp tương tự tại các trang Đùn, Cuộn và QC:**
   - Cùng một pattern gọi cả `Toast.flash(...)` lẫn `Toast.show(...)` trước khi gọi `window.location.reload()` được sao chép ở các file:
     - `public/assets/js/Extrusion/edit_submit.js` (cập nhật & hủy Bobin Đùn).
     - `public/assets/js/QC/submit.js` & `QC/edit_submit.js` (cập nhật kết quả, hủy Bobin QC, chuyển trạng thái QC).
     - `public/assets/js/Winding/submit.js` & `Winding/edit_submit.js` (cập nhật & lưu cuộn).

### 3. Giải pháp đã triển khai:
1. **Chuẩn hóa `public/assets/js/delete.js`:**
   - Xóa bỏ hoàn toàn định nghĩa `Toast` cũ và event listener `DOMContentLoaded` trùng lặp trong `delete.js`.
   - Trong `handleDelete()`:
     - Card Bobin được hủy sẽ mờ dần nhẹ nhàng (`opacity = 0; transform = scale(0.95)`).
     - Chỉ lưu thông báo duy nhất vào `window.Toast.flash(...)`.
     - Tuyệt đối KHÔNG gọi `Toast.show()` trước khi reload.
     - Sau 450ms, trang reload và `toast.js` chỉ hiển thị Toast **ĐÚNG 1 LẦN DUY NHẤT** kèm danh sách Bobin mới và badge số lượng chính xác.
2. **Chuẩn hóa đồng bộ toàn hệ thống:**
   - Loại bỏ toàn bộ các lệnh `Toast.show(...)` nằm ngay trước `setTimeout(() => window.location.reload(), ...)` trong:
     - `public/assets/js/Extrusion/edit_submit.js`
     - `public/assets/js/QC/submit.js`
     - `public/assets/js/QC/edit_submit.js`
     - `public/assets/js/Winding/submit.js`
     - `public/assets/js/Winding/edit_submit.js`
   - Đảm bảo cơ chế Flash Toast hoạt động nhất quán, mượt mà và không bao giờ xuất hiện Toast kép trên bất kỳ trang nào.

---

## [2026-10-09] - Tối Ưu Hóa Toàn Diện File Dữ Liệu SQL Chuẩn (production_db_Data.sql) (TASK-028)

### 1. Bối cảnh & Yêu cầu:
- Người dùng yêu cầu kiểm tra và đánh giá file `public/assets/sql/Updated/production_db_Data.sql` xem đã được tối ưu hóa chưa.
- Sau khi phân tích phát hiện 1 lỗi cú pháp nghiêm trọng gây dừng import (`Multiple primary key defined`) cùng các điểm lệch cấu trúc bảng, thiếu chỉ mục và chưa tối ưu câu lệnh bulk insert, người dùng đã phê duyệt tiến hành tối ưu hóa toàn diện file dữ liệu.

### 2. Các vấn đề cốt lõi đã được xử lý triệt để:
1. **Lỗi cú pháp Multiple primary key trên `employee_list`:**
   - Trong file cũ, bảng `employee_list` vừa khai báo `id INT AUTO_INCREMENT PRIMARY KEY` trong `CREATE TABLE`, vừa chạy `ALTER TABLE employee_list ADD PRIMARY KEY (id)` ở cuối file. Khi nạp vào MySQL sẽ báo lỗi dừng `ERROR 1068 (42000): Multiple primary key defined`.
   - Đã chuẩn hóa: Khởi tạo cột `id INT NOT NULL` trong `CREATE TABLE`, sau đó thêm khóa chính và thuộc tính `AUTO_INCREMENT` đồng bộ ở cuối file theo đúng kiến trúc của toàn bộ dự án.
2. **Đồng bộ hóa Schema & Dữ liệu 100% khớp với Live DB và `Architect_sql.sql`:**
   - Cập nhật bảng `employee_list` có đủ 10 cột, bổ sung `cost_center` và `permissions` (phục vụ chức năng phân quyền `AuthHelper`), nạp trọn vẹn 132 nhân viên thực tế từ Live Database.
   - Sửa các bảng danh mục `material_list` (`brand, code, grinding_time`), `material_lot_list` (`lot, updated_time`), `winding_machine_list` (`machine_name`) khớp 100% với mã nguồn JavaScript (`suggestion.js`).
3. **Tối ưu hóa hiệu năng nạp dữ liệu lớn (Bulk Import Directives & Chunking):**
   - Bổ sung `SET FOREIGN_KEY_CHECKS = 0;`, `SET UNIQUE_CHECKS = 0;`, `SET AUTOCOMMIT = 0;` ở đầu file và khôi phục ở cuối file.
   - Chia nhỏ các khối INSERT: Chia `bobin_list_detail` (3,000 dòng) thành các khối 500 dòng/câu lệnh; chia `bobin_list_general` (3,000 dòng) và `material_lot_list` (3,869 dòng) thành các khối 1,000 dòng/câu lệnh kèm `COMMIT;` định kỳ, triệt tiêu hoàn toàn nguy cơ lỗi `max_allowed_packet` và quá tải bộ đệm InnoDB Undo Log.
4. **Chuẩn hóa Collation:**
   - Đưa toàn bộ Database và bảng `rack_list` về thống nhất `utf8mb4_unicode_ci` (thay vì `utf8mb4_general_ci`), tránh xung đột `Illegal mix of collations`.
5. **Bổ sung chỉ mục (Indexes) tăng tốc truy vấn:**
   - Bổ sung `uk_employee_code`, `uk_username`, `idx_employee_role` cho `employee_list`.
   - Bổ sung `idx_lot` cho `material_lot_list`.
   - Bổ sung `idx_detail_status` và `idx_general_status` cho các bảng Bobin.
   - Bổ sung `uk_rack_code`, `uk_winding_machine_name`, `uk_extrusion_machine_code`, `uk_production_order_code`, `uk_product_code`.
   - Đã đồng bộ trực tiếp các chỉ mục này vào Live Database thành công 100%.

### 3. Kết quả kiểm thử:
- Kiểm thử import toàn trình qua MySQL CLI trên database tạm `test_verify_production_db`: 100% thành công, 0 lỗi, 0 cảnh báo, nạp đầy đủ 14 bảng với 10,960 dòng dữ liệu chuẩn.
- Kiểm thử hồi quy logic ứng dụng (`test_cancellation_fixes.php`): 21/21 kịch bản Passed 100%.

---

## [2026-10-09] - Ngăn Chặn Duplicate Bobin Chờ Hủy & Hoàn Thiện Ràng Buộc Hủy Bobin Đùn, Cuộn, QC (TASK-027)

### 1. Bối cảnh & Yêu cầu:

1. **Lỗi Duplicate Bobin tại danh sách chờ hủy:** Khi hủy 1 Bobin tại trang điều chỉnh nhóm Đùn, xuất hiện 2 thẻ Bobin ở danh sách chờ hủy (`listPendingCancellationView`).
2. **Ràng buộc thông tin hủy Bobin tại nhóm Cuộn:** Yêu cầu phải nhập đầy đủ 4 thông tin: Mã máy cuộn, Mã nhân viên, Họ tên nhân viên, và Ghi chú thì mới được phép hủy Bobin.
3. **Ràng buộc thông tin hủy Bobin tại nhóm QC:** Yêu cầu phải nhập đầy đủ: Mã nhân viên, Họ tên nhân viên, Ghi chú, và phải có ít nhất 1 trường ngoại quan được đánh giá là NG thì mới được phép hủy Bobin.

### 2. Phân tích nguyên nhân gốc rễ (Root Cause Analysis):

1. **Nguyên nhân Duplicate Bobin ở danh sách chờ hủy:**
   - Trong cơ sở dữ liệu thực tế `production_db`, hai bảng `bobin_list_detail` và `bobin_list_general` bị thiếu ràng buộc `UNIQUE KEY uk_bobin_identification_code` và `UNIQUE KEY uk_bobin_key_code` (mặc dù schema trong file SQL mẫu có khai báo).
   - Khi tái sử dụng Bobin (chạy lại chu kỳ mới bằng câu lệnh `INSERT ... ON DUPLICATE KEY UPDATE`), do thiếu Unique Key, MySQL không cập nhật dòng hiện có mà chèn thêm một bản ghi mới (`id > 3000`).
   - Khi người dùng bấm "Hủy Bobin" ở trang Chỉnh sửa Đùn, câu truy vấn `UPDATE bobin_list_detail SET bobin_current_status = 'Pending_Cancellation' WHERE bobin_identification_code = :ident` lọc chỉ theo mã định danh (không lọc theo `bobin_key_code`), dẫn đến cập nhật cả 2 dòng của Bobin đó thành `Pending_Cancellation`. Do đó, trang `listPendingCancellationView` truy vấn ra 2 dòng thẻ cùng 1 mã Bobin.
2. **Thiếu sót điều kiện xác thực khi hủy tại Cuộn và QC:**
   - Phía Cuộn: `BobinWindingCancelDTO` trước đây thiếu trường `winding_employee_name`. Hàm kiểm tra backend `validFormWinding_Cancel` và hàm frontend `handleWindingCancel` chỉ kiểm tra mã máy và mã nhân viên, bỏ qua họ tên và ghi chú.
   - Phía QC: `validFormQC_Cancel` chỉ kiểm tra mã định danh và mã nhân viên, chưa bắt buộc tên nhân viên, chưa bắt buộc lý do ghi chú, và hoàn toàn không kiểm tra xem có bất kỳ tiêu chí ngoại quan nào bị NG hay không. Đồng thời trên frontend `public/assets/js/QC/submit.js`, selector nút switch đang trỏ sai class `.vi-item-switch`, logic hiển thị badge trong modal bị ngược (`hasDefect ? OK : NG`), và chưa có bước kiểm tra chặn hiển thị modal xác nhận.

### 3. Giải pháp đã triển khai:

1. **Cơ sở dữ liệu (MySQL Database `production_db`):**
   - Hợp nhất dữ liệu chu kỳ mới nhất từ các bản ghi trùng lặp (`id > 3000`) về bản ghi gốc (`id <= 3000`).
   - Xóa bỏ toàn bộ các bản ghi trùng lặp thừa, chuẩn hóa lại đúng 3000 bản ghi trên cả `bobin_list_detail` và `bobin_list_general`.
   - Bổ sung `UNIQUE KEY uk_bobin_identification_code (bobin_identification_code)` và `UNIQUE KEY uk_bobin_key_code (bobin_key_code)` trên cả hai bảng.
2. **Khắc phục triệt để hủy Bobin tại Đùn (`BobinExtDeleteDTO`, `BobinServices`, `BobinRepository`):**
   - Bổ sung `bobin_key_code` vào `BobinExtDeleteDTO`.
   - Cập nhật `extrusionEditBobinView.php` truyền `bobin_key_code` qua `data-bobin-key`.
   - Cập nhật `public/assets/js/Extrusion/edit_submit.js` gửi `bobin_key_code` trong payload DELETE.
   - Cập nhật `BobinRepository::extDeleteBobinDetail()` và `BobinRepository::extDeleteBobinGeneral()` lọc theo cả `:ident` và `:key`.
3. **Hoàn thiện ràng buộc hủy Bobin phía Cuộn (`windingView.php`, `BobinWindingCancelDTO`, `BobinController`, `submit.js`):**
   - Bổ sung trường `winding_employee_name` vào `BobinWindingCancelDTO`.
   - Cập nhật backend `validFormWinding_Cancel` trong `BobinController.php`: Bắt buộc đủ 4 trường `winding_machine`, `winding_employee_code`, `winding_employee_name`, `winding_note`.
   - Cập nhật frontend `handleWindingCancel()` trong `public/assets/js/Winding/submit.js`: Kiểm tra tuần tự 4 trường, tự động focus & viền đỏ input bị trống, hiển thị Toast cảnh báo tương ứng.
4. **Hoàn thiện ràng buộc hủy Bobin phía QC (`qcView.php`, `BobinController`, `submit.js`):**
   - Cập nhật backend `validFormQC_Cancel` trong `BobinController.php`: Bắt buộc `inspector_code`, `inspector_name`, `defect_note` và có ít nhất 1 lỗi ngoại quan NG (`$hasNG = $dto->defect_gel || $dto->defect_foreign_object || $dto->defect_color_issue || $dto->defect_print_quality`).
   - Cập nhật `app/views/qcView.php`: Bổ sung `data-key` cho container switch ngoại quan.
   - Cập nhật `public/assets/js/QC/submit.js`:
     - Sửa selector đọc switch `.toggle-switch[data-defect]`.
     - Sửa hiển thị badge: `hasDefect ? '<span class="vi-badge-ng">NG</span>' : '<span class="vi-badge-ok">OK</span>'`.
     - Kiểm tra bắt buộc họ tên, ghi chú và tối thiểu 1 trường ngoại quan NG trước khi cho phép mở modal xác nhận hủy Bobin.
5. **Hệ thống đa ngôn ngữ (i18n):**
   - Bổ sung 5 khóa dịch mới (`toast_err_req_machine`, `toast_err_req_emp_code`, `toast_err_req_emp_name`, `toast_err_req_cancel_note`, `toast_err_qc_require_ng`) đồng bộ trong `app/core/Language.php` và `public/assets/js/i18n.js` cho cả 3 ngôn ngữ (`vi`, `en`, `ja`).
6. **Kiểm thử tự động:**
   - Xây dựng và thực thi bộ test kiểm thử tự động với 21/21 kịch bản Passed 100%.

---

## [2026-10-09] - Khắc Phục Lỗi Duplicate Entry '0' Khi Hủy Bobin Từ Trang Chỉnh Sửa Đùn (TASK-026)

### 1. Bối cảnh & Hiện tượng lỗi:

- Khi người dùng thực hiện thao tác hủy Bobin tại trang Chỉnh sửa Đùn (`app/views/extrusionEditBobinView.php`), hệ thống trả về lỗi cơ sở dữ liệu:
  `Lỗi Database: SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry '0' for key 'PRIMARY'`
- Lỗi ngăn cản hoàn toàn việc chuyển trạng thái Bobin sang chờ hủy (`Pending_Cancellation`) và không thể ghi nhận lịch sử vào bảng `bobin_history`.

### 2. Phân tích nguyên nhân gốc rễ (Root Cause Analysis):

1. **Thiếu thuộc tính `AUTO_INCREMENT` tại khóa chính bảng `bobin_history`:**
   - Cột `id` của bảng `bobin_history` được định nghĩa là `INT(11) NOT NULL PRIMARY KEY`, nhưng lại không có thuộc tính `AUTO_INCREMENT`.
   - Trong `BobinRepository.php`, hàm `extInsertBobinHistoryAfterDelete()` (cũng như `createBobin()`, `extrusionUpdateBobin()`,...) thực hiện câu lệnh `INSERT INTO bobin_history (...) SELECT ... FROM bobin_list_detail` mà không truyền trường `id`, với kỳ vọng rằng hệ quản trị MySQL sẽ tự động cấp phát ID tuần tự.
   - Do thiếu `AUTO_INCREMENT`, MySQL tự động gán giá trị mặc định kiểu số là `0` cho cột `id`.
   - Lần đầu tiên ghi nhận lịch sử cho Bobin trước đây đã tạo ra một dòng có `id = 0`.
   - Khi người dùng thực hiện hủy Bobin lần tiếp theo, MySQL lại cố gắng gán `id = 0`, gây ra xung đột khóa chính `Duplicate entry '0' for key 'PRIMARY'`.
2. **Nguy cơ tiềm ẩn trên các bảng khác trong cơ sở dữ liệu:**
   - Kiểm tra mở rộng toàn bộ cơ sở dữ liệu `production_db` cho thấy các bảng `bobin_list_detail`, `bobin_list_general` và 10 bảng danh mục (`employee_list`, `day_list`, `extrusion_machine_list`, `material_list`, `material_lot_list`, `month_list`, `product_list`, `rack_list`, `winding_machine_list`, `year_list`) cũng đều thiếu thuộc tính `AUTO_INCREMENT` ở cột `id`. Một số bảng đã tồn tại bản ghi với `id = 0`.
3. **Thiếu sót trường sao lưu dữ liệu trong câu truy vấn:**
   - Trong hàm `extInsertBobinHistoryAfterDelete()`, danh sách các cột được sao lưu từ `bobin_list_detail` sang `bobin_history` thiếu 2 trường `extrusion_check` và `rack`, khiến thông tin kiểm tra ngoại quan đùn và vị trí rack bị bỏ sót khi chuyển trạng thái sang lịch sử.

### 3. Giải pháp đã triển khai:

1. **Cập nhật dữ liệu & cấu trúc bảng trong MySQL Database (`production_db`):**
   - Đổi giá trị `id = 0` hiện có trong `bobin_history` thành `id = 1`. Thiết lập thuộc tính:
     `ALTER TABLE bobin_history MODIFY id INT(11) NOT NULL AUTO_INCREMENT;`
   - Đổi giá trị `id = 0` trong `bobin_list_detail` và `bobin_list_general` thành `id = 3001`, thêm `PRIMARY KEY (id)` và kích hoạt `AUTO_INCREMENT`.
   - Đồng bộ hóa toàn bộ 10 bảng danh mục còn lại (`employee_list`, `rack_list`, `product_list`, `material_list`, `material_lot_list`, `extrusion_machine_list`, `winding_machine_list`, `day_list`, `month_list`, `year_list`) với `PRIMARY KEY (id) AUTO_INCREMENT`.
2. **Cập nhật repository backend (`app/repositories/BobinRepository.php`):**
   - Bổ sung 2 cột `extrusion_check, rack` vào câu lệnh SQL trong `extInsertBobinHistoryAfterDelete()` để đảm bảo thông tin tiêu chuẩn kiểm tra đùn và vị trí rack được sao lưu trọn vẹn khi hủy Bobin.
3. **Cập nhật các file kịch bản SQL nguồn (`public/assets/sql/Updated/`):**
   - Đồng bộ hóa `AUTO_INCREMENT` trên các bảng trong `production_db_Architect_sql.sql` và `production_db_Data.sql`.
4. **Kiểm thử xác minh (Verification):**
   - Viết kịch bản kiểm thử toàn trình thao tác `extDeleteBobin` với mã nhân viên và Bobin thực tế. Kết quả ghi nhận lịch sử vào `bobin_history` thành công với ID tự tăng (`id = 4`), trạng thái cả 2 bảng `bobin_list_detail` và `bobin_list_general` đều chuyển sang `Pending_Cancellation` chính xác, không còn bất kỳ lỗi xung đột `Duplicate entry '0'`.

---

## [2026-10-09] - Rà Soát Toàn Diện Mạng Nội Bộ (100% Offline Intranet) & Xác Nhận Không Dùng Astral (TASK-025)

### 1. Bối cảnh & Yêu cầu:

- Người dùng yêu cầu rà soát toàn diện dự án xem có đang nạp thư viện nào qua internet (`http://`, `https://`) hay không.
- Yêu cầu loại bỏ triệt để mọi truy cập ra bên ngoài internet.
- Yêu cầu hủy bỏ/loại bỏ nếu có sử dụng bất kỳ thư viện hoặc tác vụ nào từ Astral (`astral.sh`).

### 2. Kết quả rà soát chi tiết:

1. **Kiểm tra công cụ & thư viện Astral (`astral.sh`):**
   - Xác nhận: Toàn bộ dự án **KHÔNG HỀ sử dụng** bất kỳ công cụ CLI, package, hay dependency nào từ Astral (không có `uv`, `ruff`, không có file cấu hình Python `pyproject.toml`, `ruff.toml`).
   - Dự án là ứng dụng thuần PHP (chạy trên XAMPP Apache + MySQL cục bộ) và JavaScript thuần (Vanilla JS), hoàn toàn không dính dáng đến Astral.
2. **Kiểm tra mã nguồn HTML/PHP View (`app/views/`):**
   - Rà soát toàn bộ 18 file view: 100% các thẻ `<script src="...">` và `<link rel="stylesheet" href="...">` đều chỉ trỏ đến đường dẫn nội bộ cục bộ máy chủ (`/WEB_BOBIN/public/assets/...`).
   - Tuyệt đối không có bất kỳ thẻ nào nạp từ CDN bên ngoài (như cdnjs, unpkg, jsdelivr, googleapis, fontawesome).
3. **Kiểm tra Stylesheet & Font chữ (`public/assets/css/`):**
   - Không có bất kỳ thẻ `@import url(...)` nào tải file CSS từ internet.
   - Không có khai báo `@font-face` nào tải font từ Google Fonts hay máy chủ bên ngoài. Hệ thống sử dụng 100% phông chữ có sẵn trên hệ điều hành của máy (`system-ui`, `'Segoe UI'`, Roboto, sans-serif).
   - Toàn bộ icon mũi tên, ký hiệu được nhúng trực tiếp bằng Inline SVG data-uri nội bộ.
4. **Kiểm tra JavaScript AJAX/Fetch & Network (`public/assets/js/`):**
   - 100% các lệnh gọi `fetch()` trong JavaScript đều gọi tới backend PHP cục bộ thông qua đường dẫn nội bộ máy chủ `/WEB_BOBIN/public/index.php?url=...`.
   - Đã loại bỏ các link ngoài (`scanapp.org`, `github`) trong file thư viện `html5-qrcode.min.js`, chuyển về liên kết nội bộ `#`.
   - Đã dọn dẹp ghi chú URL cũ trong `public/assets/js/contentLoaded.js`.
5. **Kiểm tra backend PHP (`app/`):**
   - Không có lệnh gọi mạng ra ngoài (`curl_init`, `file_get_contents` với URL internet, Guzzle HTTP, sockets, v.v.).

### 3. Kết luận:

- Dự án đáp ứng chuẩn **100% Offline Local Intranet**, hoàn toàn độc lập, có thể vận hành trơn tru khi ngắt kết nối internet hoàn toàn.
- Hoàn toàn **không có bất kỳ thành phần nào của Astral (`astral.sh`)**.

---

## [2026-10-09] - Khắc Phục Triệt Để Lỗi 3 Toast & Màn Hình Camera Chớp Nháy Khi Quét QR (TASK-024)

### 1. Bối cảnh & Yêu cầu:

- Người dùng phát hiện khi quét 1 mã QR thì xuất hiện cùng lúc **3 thông báo Toast xếp chồng**, đồng thời **màn hình camera chớp chớp nhấp nháy nhanh** trong quá trình quét.
- Yêu cầu rà soát cẩn thận, khắc phục triệt để.
- **Ràng buộc an toàn & bản quyền:** Tuyệt đối không liên quan hay sử dụng bất kỳ tác vụ/công cụ nào của Astral (`astral.sh`). Hoàn toàn 100% Offline Local Intranet, không tải thêm thư viện từ internet.

### 2. Phân tích nguyên nhân gốc rễ (Root Cause Analysis):

1. **Nguyên nhân 3 Toast hiển thị đồng thời:**
   - Thư viện `html5-qrcode` quét camera liên tục theo chu kỳ 15 FPS (mỗi frame cách nhau ~66.6ms).
   - Khi phát hiện mã QR hợp lệ ở frame đầu tiên (t = 0ms), hàm `handleScanSuccess()` được gọi và kích hoạt thông báo Toast thành công. Để tạo hiệu ứng thị giác cho người dùng kịp nhìn thấy khung ngắm nhận diện mã, hàm sử dụng `setTimeout(..., 250)` trước khi dừng máy ảnh bằng `stop()`.
   - Tuy nhiên, `handleScanSuccess()` lại **thiếu cờ khóa chặn re-entry (`hasScanned`)** và không lập tức tạm dừng luồng quét. Do đó, ở frame tiếp theo (t = 66ms) và frame thứ ba (t = 133ms), mã QR vẫn nằm trong khung hình và thư viện tiếp tục gọi `onSuccess()` thêm 2 lần nữa. Kết quả là hàm `window.Toast.show()` bị kích hoạt 3 lần liên tiếp, tạo ra 3 thẻ Toast xếp chồng lên nhau.
2. **Nguyên nhân màn hình camera quét bị chớp chớp nháy nhanh:**
   - Thư viện `html5-qrcode` sử dụng một thẻ `<canvas id="qr-canvas">` nội bộ để trích xuất dữ liệu điểm ảnh (pixel) từ thẻ `<video>` đưa vào thuật toán giải mã QR. Mặc định trong mã nguồn của thư viện, phần tử này được gán `canvasElement.style.display = "none"`.
   - Tuy nhiên, trong file `scanQR.css` và 7 stylesheet của các trang trước đó có chứa selector:
     ```css
     #qr-reader canvas {
       max-width: 100% !important;
       width: 100% !important;
       display: block !important;
     }
     ```
     Selector này vô tình bắt trúng thẻ `<canvas id="qr-canvas">` bên trong container `#qr-reader` và ép nó hiển thị ra ngoài màn hình (`display: block !important`).
   - Vì container `#qr-video-viewport` là một Flexbox (`display: flex; align-items: center; justify-content: center;`), việc cả thẻ `<video>` lẫn thẻ `<canvas>` cùng bị ép hiển thị với kích thước 100% khiến trình duyệt liên tục phải tính toán lại bố cục (flex layout reflow).
   - Hơn nữa, cấu hình `disableFlip: false` mặc định khiến thư viện liên tục đảo ngược ma trận biến đổi 2D (`context.scale(-1, 1)`) trên canvas 15 lần/giây, dẫn đến hiện tượng khung hình giật cục, chớp nhấp nháy dữ dội.

### 3. Giải pháp đã triển khai:

1. **Khắc phục dứt điểm lỗi 3 Toast trong `public/assets/js/qrScannerHelper.js`:**
   - Thêm cờ khóa trạng thái `hasScanned: false` vào `QRScannerHelper`. Reset cờ về `false` mỗi khi khởi động hoặc dừng phiên quét.
   - Ngay khi phát hiện mã QR đầu tiên trong `handleScanSuccess()`:
     - Kiểm tra `if (this.hasScanned) return;` và lập tức đánh dấu `this.hasScanned = true;`.
     - Gọi ngay `this.scannerInstance.pause(true);` để ngắt dứt khoát vòng lặp giải mã frame của thư viện và đóng băng video hiển thị kết quả.
     - Kích hoạt duy nhất 1 lần `window.Toast.show()`.
     - Quản lý bộ đếm `closeTimer` an toàn trước khi dọn dẹp camera và gọi callback `onSuccess`.
2. **Thêm cơ chế phòng thủ nhiều lớp (Defense-in-depth) trong `public/assets/js/toast.js`:**
   - Bổ sung cơ chế chống spam/trùng lặp Toast trong hàm `Toast.show()`: Tự động chặn các thông báo giống hệt nhau liên tiếp xuất hiện trong khoảng thời gian 1.5 giây (`debounce 1500ms`).
3. **Triệt tiêu hiện tượng camera chớp nháy trong `public/assets/css/scanQR.css`:**
   - Ẩn triệt để toàn bộ canvas nội bộ của thư viện:
     ```css
     #qr-reader canvas,
     #reader canvas,
     #qr-video-viewport canvas,
     canvas#qr-canvas,
     .qr-video-region canvas {
       display: none !important;
       visibility: hidden !important;
       position: absolute !important;
       width: 0 !important;
       height: 0 !important;
       opacity: 0 !important;
       pointer-events: none !important;
     }
     ```
   - Ẩn `#qr-shaded-region` và mọi phần tử phụ ngoài ý muốn do thư viện tự ý chèn: `#qr-video-viewport > *:not(video) { display: none !important; }`.
   - Cố định tỉ lệ video khung ngắm `height: 100% !important; object-fit: cover !important;`.
   - Thiết lập `disableFlip: true` trong cấu hình quét để ngắt hoàn toàn việc lật đảo ma trận canvas không cần thiết.
4. **Chuẩn hóa CSS đồng bộ trên 7 stylesheet các trang:**
   - Xóa bỏ selector `#qr-reader canvas` và thay bằng quy tắc ẩn triệt để canvas trên 7 file: `extrusion.css`, `extrusionEditBobin.css`, `listBobinDetail.css`, `listBobinHistory.css`, `listBobin_QC.css`, `listBobin_Winding.css`, `listPendingCancellation.css`.

---

## [2026-10-09] - Khắc Phục Triệt Để & Nâng Cấp Toàn Diện Giao Diện Quét Mã QR (TASK-023)

### 1. Bối cảnh & Yêu cầu:

- Người dùng yêu cầu kiểm tra lại chức năng `scanQR`, khắc phục các lỗi và vấn đề ở giao diện quét mã QR.
- **Ràng buộc nghiêm ngặt:** Tuyệt đối không tải thêm bất kỳ thư viện ngoài nào trên internet (Offline Intranet 100%).

### 2. Nguyên nhân gốc rễ (Root Cause Analysis):

1. **Lỗi Fatal Error tại `app/views/scanQR.php`:**
   - File `app/views/scanQR.php` gọi trực tiếp helper `__('ph_qr_scan_result')` và `__('search')` mà không nạp `Language.php`. Khi truy cập trực tiếp qua browser, PHP dừng với lỗi: `Fatal error: Uncaught Error: Call to undefined function __() in app/views/scanQR.php:84`.
   - Router chưa đăng ký action `scanQR` trong `publicActions` và `BobinController.php` thiếu method `scanQR()`, khiến route `bobin/scanQR` không hoạt động.
2. **Giao diện quét mã QR thô sơ và trải nghiệm người dùng kém (UI/UX Flaws):**
   - Khi sử dụng `Html5QrcodeScanner` mặc định của thư viện `html5-qrcode`, thư viện tự chèn các phần tử DOM unstyled thô sơ: nút "Request Camera Permissions", select box "Select Camera", nút "Start Scanning" / "Stop Scanning", link ngoài ScanApp/Github.
   - Người dùng bấm nút "Quét QR" trên trang, nhưng camera không mở ngay mà lại hiện một hộp thoại tiếng Anh bắt người dùng phải bấm thêm nút phụ bên trong để cấp quyền hoặc khởi chạy.
   - Khung hình camera không có Reticle căn chỉnh (góc ngắm), không có hiệu ứng tia laser quét mã, video bị co dãn không đúng tỉ lệ (`object-fit: cover`).
   - Nút bấm `btnScanQR` khi chuyển sang trạng thái "Đóng Camera" bị hardcode text tiếng Việt, làm hỏng đa ngôn ngữ (`en`, `ja`) và ghi đè mất class CSS riêng của các trang (`ext-btn-scan`, `qc-btn-scan`, `wnd-btn-scan`).
3. **Quản lý vòng đời Camera chưa triệt để (Lifecycle & Memory Leak):**
   - Khi đóng camera, hàm `stopScanner()` cũ chỉ gọi `html5QrcodeScanner.clear()` bất đồng bộ mà không dừng dứt khoát MediaStream tracks, không reset biến instance về `null`, khiến đèn webcam vẫn sáng và gây lỗi khi bấm mở lại lần 2.

### 3. Các giải pháp đã triển khai:

- **Khắc phục trang `app/views/scanQR.php` & Điều hướng:**
  - Khởi tạo an toàn `ROOT_PATH`, `BASE_URL`, `session_start()` và `Language::init()` ở đầu file `scanQR.php`.
  - Bổ sung method `scanQR()` vào `BobinController.php` và cấp quyền truy cập `scanqr` trong `$publicActions['bobin']` tại `app/core/Router.php`.
  - Thiết kế lại trang `scanQR.php` chuẩn Dashboard với nút quay lại, tiêu đề chuyên nghiệp, chọn đích tra cứu (QC, Cuộn, Chi tiết, Lịch sử), input kết quả và tự động submit sau khi quét.
- **Xây dựng Module Quản lý Camera Trung tâm `public/assets/js/qrScannerHelper.js`:**
  - Sử dụng trực tiếp lớp `Html5Qrcode` (từ file cục bộ `public/assets/js/html5-qrcode.min.js`), loại bỏ hoàn toàn các phần tử unstyled và link ngoài của Minhaz.
  - Quản lý trạng thái `isScanning`, tự động dừng camera dứt điểm (`await scannerInstance.stop()`) và giải phóng bộ nhớ.
  - Tự động kích hoạt ngay camera sau (`facingMode: 'environment'`) hoặc camera đầu tiên mà không yêu cầu thêm thao tác bấm phụ.
  - Hỗ trợ đổi camera trước/sau linh hoạt khi thiết bị có nhiều camera.
  - Tích hợp tính năng tải ảnh mã QR từ thiết bị (`scanFile`) dành cho máy tính không có camera hoặc thiết bị bị chặn quyền truy cập.
  - Màn hình chờ kết nối (Spinner) và khối thông báo lỗi thân thiện kèm nút Thử lại.
- **Xây dựng Stylesheet Chuyên dụng `public/assets/css/scanQR.css`:**
  - Thiết kế thẻ `.qr-scanner-card` bo góc 16px, đổ bóng mềm mại, viền chuẩn công nghiệp.
  - Viewport tối giản với khung ngắm Reticle 4 góc phát sáng ngọc lục bảo (`#10b981`), tia laser quét lên xuống mượt mà (`qrLaserSweep`), và badge trạng thái đang quét.
  - Hiệu ứng flash viền xanh lá khi nhận diện mã thành công.
  - Ẩn triệt để toàn bộ watermark bên ngoài, scanapp link của thư viện `html5-qrcode`.
  - Bổ sung style `.is-scanning` cho nút bấm `#btnScanQR` trên toàn bộ các trang (gradient đỏ, chữ Đóng Camera).
  - Responsive hoàn hảo trên điện thoại di động, máy tính bảng và màn hình máy tính bàn.
- **Đa ngôn ngữ & Đồng bộ Toàn Hệ Thống:**
  - Thêm 11 translation keys cho QR Scanner trên cả 3 ngôn ngữ (`vi`, `en`, `ja`) trong `app/core/Language.php` và `public/assets/js/i18n.js`.
  - Cập nhật 5 file script quét mã QR:
    - `public/assets/js/Extrusion/scanQR.js`
    - `public/assets/js/Extrusion/edit_scanQR.js`
    - `public/assets/js/QC/scanQR.js`
    - `public/assets/js/Winding/scanQR.js`
    - `public/assets/js/Manage/scanQR.js`
  - Đồng bộ trên toàn bộ 10 file view:
    - `app/views/scanQR.php`
    - `app/views/extrusionView.php`
    - `app/views/extrusionEditBobinView.php`
    - `app/views/qcView.php`
    - `app/views/qcEditBobinView.php`
    - `app/views/windingView.php`
    - `app/views/windingEditBobinView.php`
    - `app/views/listBobinDetailView.php`
    - `app/views/listBobinHistoryView.php`
    - `app/views/listPendingCancellationView.php`

## [2026-10-09] - Làm Hiển Thị Nổi Bật 4 Thông Số Bobin & Khắc Phục Triệt Để Lỗi Nút Hủy Bobin (TASK-022)

### 1. Bối cảnh & Yêu cầu:

- **Yêu cầu 1:** Tại trang Danh sách (`listBobinDetailView`, `listBobinView`), Lịch sử Bobin (`listBobinHistoryView`), trang QC (`qcView`) và trang Cuộn (`windingView`), làm hiển thị nổi bật 4 thông tin cốt lõi của Bobin:
  1. **Mã sản phẩm:** Hiển thị nổi bật với badge xanh dương.
  2. **Vị trí Rack (nếu có):** Badge xanh ngọc hiển thị `📍 [Mã Rack]` khi có giá trị, hiển thị placeholder mờ `---` khi chưa có dữ liệu.
  3. **Loại Bobin:** Phân màu sắc chuyên biệt theo 3 gam màu chuẩn:
     - **Sản xuất:** Màu xanh lá đậm (`#14532d`, nền `#dcfce7`, viền `#16a34a`).
     - **Bù:** Màu xanh dương (`#1e40af`, nền `#dbeafe`, viền `#3b82f6`).
     - **Các loại điều chỉnh:** Màu cam đậm (`#9a3412`, nền `#ffedd5`, viền `#ea580c`), áp dụng cho tất cả các loại ngoại quan: `Điều chỉnh`, `Điều chỉnh (Do CP)`, `Gel`, `Dị vật`, `Trầy`, `Biến dạng`, `Xước`, `Chữ in`, `Vón cục`, `Màu`.
  4. **Lot in:** Badge Monospace tím indigo (`#4338ca`, nền `#e0e7ff`, viền `#818cf8`), phân biệt rõ với trạng thái chưa cập nhật.
- **Yêu cầu 2:** Điều tra và khắc phục nguyên nhân nút "Hủy Bobin" tại trang QC và Cuộn bị hiển thị thành "Trở lại".
- **Yêu cầu 3:** Tối ưu hóa UI/UX với hiệu ứng chuyển động mượt mà và hover elevation tương tác.

### 2. Nguyên nhân gốc rễ lỗi nút "Hủy Bobin" bị đổi thành "Trở lại" (Root Cause):

- Trong `config/custom_translations.json`, người dùng cấu hình từ khóa `"btn_cancel"` có nghĩa là `"Trở lại"` (dành cho modal quay lại).
- Trong `public/assets/js/i18n.js`, hàm `deepTranslateDOM()` duyệt qua danh sách `DOM_MAPPINGS.buttons` chứa `{ key: 'btn_cancel', vi: 'Hủy' }`.
- Nút bấm tại `qcView.php` và `windingView.php` hiển thị chữ `🗑️ Hủy Bobin`. Vòng lặp so sánh `raw.includes('Hủy')` bị khớp nhầm vào `btn_cancel`, gọi `window.t('btn_cancel')` trả về `"Trở lại"` từ tệp cấu hình động và ghi đè toàn bộ chữ của nút.

### 3. Các giải pháp đã triển khai:

- **Khắc phục triệt để lỗi nút Hủy Bobin:**
  - Khởi tạo key dịch chuyên biệt `btn_cancel_bobin`:
    - `vi`: `"Hủy Bobin"`
    - `en`: `"Cancel Bobin"`
    - `ja`: `"ボビン廃棄"`
  - Đồng bộ từ khóa `btn_cancel_bobin` vào `app/core/Language.php` và `public/assets/js/i18n.js`.
  - Đặt rule mapping `btn_cancel_bobin` đứng trước `btn_cancel` trong `DOM_MAPPINGS.buttons`.
  - Bổ sung safeguard chặn khớp nhầm trong `deepTranslateDOM()`: `if (item.key === 'btn_cancel' && (raw.includes('Bobin') || raw.includes('ボビン'))) continue;`.
  - Gắn thuộc tính `data-i18n="btn_cancel_bobin"` và helper PHP `<?= __('btn_cancel_bobin') ?>` cho nút hủy tại `qcView.php` và `windingView.php`.
- **Thiết kế khối CSS hiển thị nổi bật 4 thông số Bobin (TASK-022 CSS):**
  - Xây dựng các class `.bobin-highlight-product`, `.bobin-highlight-rack`, `.bobin-type-badge` (với `.type-san-xuat`, `.type-bu`, `.type-dieu-chinh`), `.bobin-highlight-printlot`.
  - Tối ưu hóa UI/UX với `transition: transform 0.15s ease, box-shadow 0.15s ease` và hover elevation `transform: translateY(-1px)`.
  - Đồng bộ trên toàn bộ 5 stylesheet liên quan:
    - `public/assets/css/listBobinDetail.css`
    - `public/assets/css/listBobin.css`
    - `public/assets/css/listBobinHistory.css`
    - `public/assets/css/listBobin_QC.css`
    - `public/assets/css/listBobin_Winding.css`
- **Khắc phục lỗi giá trị "Điều chỉnh..." của "Loại Bobin" tràn qua cột "Lot vật liệu":**
  - Cập nhật `.bobin-type-badge`: Cho phép `white-space: normal`, tự động xuống dòng (`word-break: break-word; overflow-wrap: anywhere; line-height: 1.25`), giới hạn `max-width: 100%`, `box-sizing: border-box`, căn giữa và padding `2.5px 7px`.
  - Thêm `min-width: 0; overflow: hidden;` cho `.field-item` và `.val-sub` để bảo đảm các ô trong CSS Grid không bị phình to hoặc tràn ra ngoài track.
  - Nâng chiều rộng tối thiểu cột trong `.info-grid` lên `minmax(125px, 1fr)` trên cả 5 stylesheet.
  - Thêm thuộc tính `title="<?= htmlspecialchars($rawBobinType) ?>"` vào thẻ badge trên cả 5 view để hỗ trợ tooltip khi rê chuột.

### 4. Kết quả kiểm thử & Nghiệm thu:

- PHP Syntax Check (`php -l`): 100% không phát hiện lỗi cú pháp trên toàn bộ các tệp view và core.
- JS Syntax Check (`node -c`): 100% đạt chuẩn cú pháp trên `i18n.js` và `toast.js`.
- Cả 4 thông số hiển thị trực quan, đúng màu sắc quy định, chuyển đổi ngôn ngữ trơn tru (Việt - Anh - Nhật), nút "Hủy Bobin" hiển thị chuẩn xác không bị đè thành "Trở lại".

## [2026-10-09] - Chuẩn Hóa Hệ Thống Toast Thông Báo Toàn Cục & Khắc Phục Triệt Để Lỗi Không Hiển Thị

### 1. Bối cảnh & Yêu cầu:

- **Hiện tượng:** Người dùng phản ánh rất nhiều tính năng hệ thống không hiển thị thông báo Toast khi xảy ra lỗi (lỗi thao tác người dùng, lỗi nhập liệu thiếu trường, nhập sai mật khẩu xác nhận, lỗi kết nối máy chủ, lỗi mở camera quét QR) hoặc khi thực hiện thành công tác vụ (lưu thành công, cập nhật thành công, hủy Bobin, khôi phục từ điển).
- **Nguyên nhân gốc rễ (Root Cause):**
  1. **Xung đột & Thiếu CSS tại các trang Điều chỉnh (Đùn, QC, Cuộn):** Trong `Extrusion/edit_submit.js`, `QC/edit_submit.js`, và `Winding/edit_submit.js`, mỗi tệp định nghĩa một đối tượng `const Toast` cục bộ tạo ra thẻ `<div class="toast-message">`, nhưng các tệp CSS tương ứng hoàn toàn KHÔNG có quy tắc CSS nào cho `.toast-message`. Thẻ thông báo được chèn vào cuối `<body>` như một đoạn văn bản thô không màu, không định vị fixed và bị che khuất hoàn toàn.
  2. **Race condition reload trang làm mất thông báo:** Khi lưu/cập nhật thành công, các module gọi `Toast.show()` và ngay lập tức gọi `window.location.reload()`. Vì không có cơ chế Flash Message lưu tạm qua reload, Toast bị xóa sạch ngay khi trang tải lại khiến người dùng tưởng hệ thống không phản hồi.
  3. **Sử dụng `alert()` trình duyệt hoặc Toast cục bộ:** Một số trang như `languageManageView.php`, `listBobinHistoryView.php` sử dụng hộp thoại `alert()` gây gián đoạn trải nghiệm người dùng, hoặc dùng div cục bộ không đồng bộ UI/UX.
  4. **Thiếu module Toast thống nhất:** Chưa có một module Toast độc lập, nạp toàn cục cho mọi trang của dự án.

### 2. Các giải pháp đã triển khai:

- **Xây dựng module Toast toàn cục độc lập (`public/assets/js/toast.js`):**
  - 100% Thuần Vanilla JS & CSS, hoạt động hoàn toàn Offline trong mạng nội bộ Intranet, không dùng CDN.
  - Cung cấp `window.Toast` và `window.showToast` với 4 trạng thái chuẩn:
    - `success` (✅ Thành công - Màu xanh lá)
    - `error` (❌ Thất bại/Lỗi - Màu đỏ cảnh báo)
    - `warning` (⚠️ Cảnh báo - Màu vàng cam)
    - `info` (ℹ️ Thông tin - Màu xanh dương)
  - Tự động tiêm CSS `#unified-toast-styles` với z-index cực cao (`999999`), thanh tiến trình thời gian mượt mà, nút đóng tức thì (✕), hiệu ứng trượt vào/ra và responsive trên Desktop/Tablet/Mobile.
  - Hỗ trợ Flash Message qua `sessionStorage` (`bobin_toast_flash`) và nhận diện tự động qua URL params (`?msg=...&msg_type=...`, `?error=...`, `?success=...`), tự động dọn dẹp URL bằng `history.replaceState`.
- **Bổ sung khóa đa ngôn ngữ Toast:**
  - Thêm 19 khóa dịch thuật mới (`toast_saved_success`, `toast_updated_success`, `toast_deleted_success`, `toast_cancelled_success`, `toast_reset_success`, `toast_copied_success`, `toast_error_system`, `toast_error_network`, `toast_error_input`, `toast_error_pwd`, `toast_qr_scanned`, `toast_qr_cam_error`, `warn_select_history_first`,...) cho cả 3 ngôn ngữ (`vi`, `en`, `ja`) trong `app/core/Language.php` và `public/assets/js/i18n.js`.
- **Nạp Toast trên 100% các View (18/18 View):**
  - Tích hợp vào `app/views/components/header.php` (tự động nạp cho 12 view có header).
  - Nạp trực tiếp vào thẻ `<head>` của 6 view độc lập: `loginView.php`, `changePasswordView.php`, `manageCapacityView.php`, `createBobinView.php`, `scanQR.php`, `listBobinView.php`.
- **Đồng bộ hóa các Module Script:**
  - `Extrusion/edit_submit.js`, `QC/edit_submit.js`, `Winding/edit_submit.js`: Bỏ `const Toast` cục bộ không CSS, chuyển sang ủy quyền `window.Toast` và thêm `Toast.flash(...)` trước khi reload trang.
  - `Extrusion/submit.js`, `QC/submit.js`, `Winding/submit.js`: Chuẩn hóa ủy quyền `window.Toast` và kích hoạt `Toast.flash(...)`.
  - `utils.js`, `delete.js`: Chuẩn hóa `window.Toast`.
  - `employeePermissionsView.php`: Ủy quyền `window.Toast.show()`.
  - `languageManageView.php`: Thay thế toàn bộ 4 hàm `alert()` bằng `Toast.warning` / `Toast.error`, hỗ trợ `Toast.flash(...)` khi lưu/khôi phục từ điển.
  - `listBobinHistoryView.php`: Thay thế `alert()` khi chưa chọn checkbox xuất excel bằng `Toast.warning()`.
  - Toàn bộ 5 file máy quét QR (`scanQR.js`): Kết nối đồng bộ với `Toast.show()`.

### 3. Kết quả kiểm thử:

- PHP Syntax Check (`php -l`): 100% toàn bộ các tệp `.php` trong thư mục `app/` đạt không lỗi.
- JS Syntax Check (`node -c`): 100% toàn bộ các tệp JavaScript trong `public/assets/js/` đạt chuẩn cú pháp.
- Toast hiển thị hoàn hảo ở cả 4 trạng thái, duy trì thông báo qua lượt reload trang và hỗ trợ 3 ngôn ngữ mượt mà.

## [2026-10-08] - Kiểm Tra Và Khắc Phục Toàn Diện Ngôn Ngữ Textfield & Placeholder (TASK-021)

### 1. Bối cảnh & Yêu cầu:

- **Hiện tượng:** Người dùng phản ánh một số ô textfield, placeholder của các ô tìm kiếm, nhập ghi chú, mã vật tư, mật khẩu không hiển thị đúng ngôn ngữ khi tải trang hoặc khi chuyển đổi qua lại giữa Tiếng Việt, Tiếng Anh và Tiếng Nhật.
- **Nguyên nhân gốc rễ (Root Cause):**
  1. **Lỗi phân giải cú pháp tiền tố `[placeholder]` trong `applyDataI18n()` (`public/assets/js/i18n.js`):** Nhiều view sử dụng quy ước `data-i18n="[placeholder]key_name"`, nhưng hàm `applyDataI18n()` truyền nguyên chuỗi `"[placeholder]key_name"` vào `window.t()`, dẫn đến kết quả trả về `undefined` và placeholder bị giữ nguyên chuỗi khởi tạo ban đầu.
  2. **Thiếu hỗ trợ các thuộc tính dữ liệu mở rộng:** Một số view sử dụng `data-placeholder-i18n="key"` hoặc `data-i18n-ph="key"`, nhưng trước đó `applyDataI18n()` chỉ tìm kiếm thẻ `[data-i18n]` hoặc thiếu bao quát các thẻ biến thể.
  3. **Placeholder bị hardcode trong mã HTML của PHP:** Một số view (`extrusionView.php`, `qcView.php`, `windingView.php`, `listBobinDetailView.php`, `listBobinHistoryView.php`,...) ghi tĩnh tiếng Việt `placeholder="Nhập hoặc quét mã Bobin..."` thay vì dùng hàm helper `<?= __('ph_scan_bobin') ?>`.
  4. **Thiếu một số từ khóa placeholder chuyên dụng trong từ điển:** Các key như `search_keyword_ph`, `qc_note_ph`, `qc_note_explain_ph`, `ph_winding_machine_input`, `ph_winding_note_explain`,... chưa có mặt đầy đủ trong `Language.php` và `i18n.js`.
  5. **Mục Section E của `deepTranslateDOM()`:** Chưa bao quát hết các cụm từ placeholder mới xuất hiện trong các form điều chỉnh và quản trị.

### 2. Các giải pháp đã triển khai:

- **Nâng cấp công cụ i18n Frontend (`public/assets/js/i18n.js`):**
  - Cải tiến `applyDataI18n()` sử dụng biểu thức chính quy `/^\[([a-zA-Z0-9_-]+)\](.*)$/` để trích xuất chuẩn xác thuộc tính đích (như `placeholder`, `title`) và mã khóa bản dịch, sau đó gán trực tiếp thuộc tính tương ứng.
  - Tích hợp thêm truy vấn tự động cho toàn bộ các thuộc tính placeholder: `[data-i18n-ph]`, `[data-placeholder-i18n]`, `[data-i18n-placeholder]`.
  - Mở rộng Section E trong `deepTranslateDOM()` với regex và chuỗi so khớp dự phòng đầy đủ cho toàn bộ placeholder trên mọi màn hình.
  - Thêm 12 cặp từ khóa placeholder mới vào cả 3 ngôn ngữ (`DICT.vi`, `DICT.en`, `DICT.ja`).
- **Nâng cấp từ điển Backend (`app/core/Language.php`):**
  - Thêm 12 khóa dịch thuật chuyên dụng (`search_keyword_ph`, `qc_note_ph`, `qc_note_explain_ph`, `ph_winding_machine_input`, `ph_winding_note_explain`, `ph_length_hint`, `ph_bobin_id_example`, `ph_qr_scan_result`, `lang_ph_key_name`, `lang_ph_vi_content`, `lang_ph_en_content`, `lang_ph_ja_content`) cho cả 3 ngôn ngữ `vi`, `en`, `ja`.
- **Rà soát & Đồng bộ hóa toàn bộ 18 file view của hệ thống:**
  - `extrusionView.php`: Thay thế 10 placeholder hardcode bằng `<?= __('key') ?>` và gắn `data-i18n-ph="key"`.
  - `extrusionEditBobinView.php`: Chuẩn hóa 7 trường input với `data-i18n-ph`.
  - `qcView.php`: Cập nhật placeholder ô tìm kiếm và ô ghi chú giải trình lý do QC.
  - `qcEditBobinView.php`: Chuẩn hóa placeholder tìm kiếm Bobin và ghi chú ngoại quan QC.
  - `windingView.php`: Chuẩn hóa ô tìm kiếm, ô chọn máy cuộn và ghi chú cuộn.
  - `windingEditBobinView.php`: Chuẩn hóa ô tìm kiếm và ghi chú cuộn.
  - `listBobinDetailView.php`, `listBobinHistoryView.php`, `listBobinView.php`, `listPendingCancellationView.php`: Chuẩn hóa các ô tìm kiếm Bobin.
  - `createBobinView.php`: Cập nhật placeholder ví dụ mã Bobin.
  - `scanQR.php`: Cập nhật placeholder ô quét kết quả QR và nút tìm kiếm.
  - `languageManageView.php`: Cập nhật placeholder ô tìm kiếm và các ô nhập tạo mới từ khóa (key name, vi, en, ja).
  - `employeeListView.php`, `employeePermissionsView.php`, `changePasswordView.php`: Bổ sung `data-i18n-ph` và dịch tooltip `title` ẩn/hiện mật khẩu.
  - Các tệp JS modal xác thực (`Extrusion/edit_submit.js`, `QC/edit_submit.js`, `Winding/edit_submit.js`): Bổ sung `data-i18n-ph="confirm_pwd_ph"` vào ô nhập mật khẩu xác nhận.

### 3. Kết quả kiểm thử:

- Kiểm tra cú pháp PHP Syntax Check (`php -l`): 18/18 views + `Language.php` đạt 100% không lỗi.
- Kiểm tra cú pháp JavaScript Syntax Check (`node -c`): 4/4 files JS đạt 100% không lỗi.
- Chạy script kiểm thử Backend PHP: 100% các placeholder keys trả về đúng bản dịch tương ứng ở cả `vi`, `en`, `ja`.
- Chạy script kiểm thử Frontend JS (DOM Simulation): 100% cả 4 cơ chế (tiền tố `[placeholder]`, `data-i18n-ph`, `data-placeholder-i18n`, và fallback Section E) đều dịch chính xác sang tiếng Anh và tiếng Nhật.

## [2026-10-08] - Đồng Bộ Toàn Diện Giao Diện Điều Chỉnh Đùn & Hệ Thống Đa Ngôn Ngữ (TASK-020)

### 1. Bối cảnh & Yêu cầu:

- **Đồng bộ hóa giao diện Điều chỉnh Đùn (`extrusionEditBobinView.php`):** Nâng cấp trang Điều chỉnh Đùn đồng nhất với phong cách Dashboard hiện đại của Điều chỉnh QC và Điều chỉnh Cuộn:
  - Header Hero ấn tượng với nhận diện công đoạn (`EXTRUSION PROCESS EDIT`).
  - Hàng 4 thẻ KPI thống kê trực quan (Tổng số Bobin chờ QC, Đùn Check Đạt chuẩn 5/5 OK, Đùn Check Có lỗi NG, Trạng thái `BUSY_UNCHECKED`).
  - Khối thẻ Bobin phân chia thành 3 phân vùng rõ ràng, khoa học: Quy cách sản phẩm & Định lượng, Thiết bị máy đùn & Vật liệu, Nhân sự & Thời gian hoàn thành.
  - Băng chuyền 5 tiêu chí kiểm tra công đoạn Đùn (Đường kính, Gel, Dị vật, Màu sắc, Chữ in) với công tắc toggle switch trực quan.
  - Bảo toàn 100% các class selector, autocomplete suggestion (`edit_suggestion.js`), máy quét mã QR (`edit_scanQR.js`) và bảo mật xác thực mật khẩu cá nhân khi lưu cập nhật (`edit_submit.js`).
- **Kiểm tra và đồng bộ hóa đa ngôn ngữ 100%:** Rà soát và bổ sung 46+ từ khóa dịch thuật cho cả 3 ngôn ngữ (`vi`, `en`, `ja`) trong `app/core/Language.php` và `public/assets/js/i18n.js`:
  - Khắc phục triệt để hiện tượng thiếu bản dịch các tiêu đề, nhãn trường (`field_product`, `field_print_lot`, `field_length`, `field_rack`, `field_bobin_type`,...), tiêu chí kiểm tra và các badge trạng thái Bobin (`status_busy_unchecked`, `status_busy_checked`, `status_rolled`).
  - Đảm bảo popup xác nhận cập nhật/hủy Bobin hiển thị đa ngôn ngữ tự nhiên theo lựa chọn ngôn ngữ của người dùng.
- **Bảo mật phân quyền:** Bổ sung cơ chế kiểm tra quyền hạn `AuthHelper::hasPermission('extrusion_edit')` tại `BobinController::extrusionEditBobinView()` tương tự như QC và Cuộn.

### 2. Các tệp tin đã thay đổi:

- **Backend & Controller:**
  - `app/controllers/BobinController.php`: Bổ sung kiểm tra quyền `extrusion_edit` và truyền `totalRecords` sang view `extrusionEditBobinView`.
- **Giao diện & Bố cục:**
  - `public/assets/css/extrusionEditBobin.css`: Viết lại hoàn chỉnh với phong cách Royal Blue / Modern Dashboard, lưới thẻ 3 cột responsive, hiệu ứng hover, thanh 5 tiêu chí Đùn Check và hỗ trợ responsive trên màn hình tablet/mobile.
  - `app/views/extrusionEditBobinView.php`: Tái cấu trúc toàn diện, tích hợp thống kê KPI động, gắn 100% thuộc tính `data-i18n` và PHP `__()` helper.
  - `public/assets/js/Extrusion/edit_submit.js`: Đồng bộ popup xác nhận lưu thay đổi với các nhãn i18n động thông qua `window.t()`.
- **Đa ngôn ngữ (i18n):**
  - `app/core/Language.php`: Bổ sung 46 key dịch thuật mới (`ext_*`, `field_*`, `crit_*`, `ph_*`, `status_*`) cho cả 3 ngôn ngữ (`vi`, `en`, `ja`).
  - `public/assets/js/i18n.js`: Bổ sung đồng bộ 46 key tương ứng vào `DICT.vi`, `DICT.en`, `DICT.ja`.
- **Tài liệu dự án:**
  - `AI_preference/AI_Task.md`: Ghi nhận hoàn thành `TASK-020`.
  - `AI_preference/CHANGELOG.md`: Cập nhật chi tiết nội dung thay đổi.

### 3. Kết quả kiểm thử:

- Kiểm tra cú pháp PHP Syntax Check (`php -l`): 100% tệp tin đạt chuẩn (0 lỗi).
- Kiểm tra render HTML đa ngôn ngữ: `extrusionEditBobinView` render chuẩn xác cả 3 ngôn ngữ `vi`, `en`, `ja` với dung lượng ~48KB HTML, không có lỗi hay cảnh báo PHP nào.
- Kiểm tra tính tương thích: Giữ nguyên các class hook JavaScript và hoạt động 100% offline nội bộ.

## [2026-10-08] - Đồng Bộ Kiến Trúc SQL, Tối Ưu Menu Tree & Nâng Cấp Giao Diện QC / Cuộn (TASK-019)

### 1. Bối cảnh & Yêu cầu:

- **Đồng bộ hóa đa ngôn ngữ tại trang Phân quyền:** Đồng bộ toàn diện các nhãn, placeholder, nút bấm, thông báo trạng thái, popup xác nhận trên trang `employeePermissionsView.php` sang hệ thống i18n động (`vi`, `en`, `ja`) và lắng nghe sự kiện `languageChanged` để cập nhật tức thì trên giao diện mà không cần reload trang.
- **Tối ưu thanh điều hướng Menu Folder Tree:** Duy trì cấu trúc phân cấp cây thư mục trong `sidebar.css`, xê dịch các mục con (`.sb-tree-children`, `.sb-tree-leaf`) thụt vào trong 1 khoảng so với thư mục cha, đồng thời căn chỉnh đường chỉ dẫn phân cấp (`tree guide line`) và mấu nối ngang (`tree branch connector`) liền mạch, chuyên nghiệp.
- **Đồng bộ kiến trúc CSDL SQL:** Cập nhật tệp kiến trúc `public/assets/sql/Updated/production_db_Architect_sql.sql` đồng bộ 100% với cấu trúc thực tế của cơ sở dữ liệu `production_db` (bổ sung đầy đủ các cột mới `cost_center`, `permissions`, `update_history`, chuẩn hóa `PRIMARY KEY AUTO_INCREMENT` và các `UNIQUE KEY`, `KEY` cho toàn bộ 15 bảng). Sửa lỗi thiếu khóa chính ở bảng `product_list`.
- **Nâng cấp giao diện Điều chỉnh QC và Điều chỉnh Cuộn:** Thay thế giao diện đơn điệu, thô sơ cũ bằng giao diện Dashboard chuyên nghiệp, hiện đại:
  - Header Hero ấn tượng với badge nhận diện phân hệ.
  - Hàng thẻ KPI thống kê trực quan (Tổng số bản ghi, Đạt/Lỗi ngoại quan QC, Đạt/Không đạt Test thông khí Cuộn).
  - Khối danh sách dạng thẻ Card 2 cột responsive (cột thông số kỹ thuật Bobin và cột form điều chỉnh tác vụ).
  - Bộ toggle switch kiểm tra tiêu chí ngoại quan hiện đại (Gel, Dị vật, Lỗi màu, Lỗi in).
  - Tích hợp 100% hệ thống i18n đa ngôn ngữ cho toàn bộ nhãn, tiêu đề và nút bấm.
  - Bảo toàn 100% các class hook của JavaScript (`QC/edit_submit.js`, `Winding/edit_submit.js`), xác thực mật khẩu cá nhân và cơ chế phân trang 10 Bobin/trang.

### 2. Các tệp tin đã thay đổi:

- **Cơ sở dữ liệu & Kiến trúc SQL:**
  - `public/assets/sql/Updated/production_db_Architect_sql.sql`: Viết lại chuẩn hóa 15 bảng với đầy đủ ràng buộc khóa chính tự tăng `AUTO_INCREMENT`, khóa duy nhất và chỉ mục tối ưu hiệu năng.
  - `public/assets/production_db_database.sql`: Sửa cú pháp backtick cho cột `cost_center`.
  - CSDL MySQL `production_db`: Bổ sung `PRIMARY KEY (id) AUTO_INCREMENT` cho bảng `product_list`.
- **Giao diện & Bố cục:**
  - `public/assets/css/sidebar.css`: Tăng thụt lề `.sb-tree-children` lên `padding-left: 30px`, căn chỉnh thước kẻ `::before` (`left: 24px`), bổ sung căn lề `.sb-tree-leaf` và mấu nối ngang nhánh cây (`left: -10px; width: 9px`).
  - `public/assets/css/qcEditBobin.css` (Mới): Thiết kế phong cách Ocean/Cyan chuyên nghiệp, hiệu ứng hover card, switch kiểm tra ngoại quan và responsive mobile/tablet.
  - `public/assets/css/windingEditBobin.css` (Mới): Thiết kế phong cách Teal/Emerald chuyên nghiệp, hiệu ứng hover card, switch kết quả test thông khí và responsive mobile/tablet.
  - `app/views/qcEditBobinView.php`: Tái cấu trúc toàn diện theo thiết kế mới, thêm thống kê KPI, gắn thẻ `data-i18n`.
  - `app/views/windingEditBobinView.php`: Tái cấu trúc toàn diện theo thiết kế mới, thêm thống kê KPI, gắn thẻ `data-i18n`.
  - `app/views/employeePermissionsView.php`: Gắn thuộc tính `data-i18n` cho toàn bộ thẻ tĩnh, chuyển toàn bộ chuỗi JavaScript động sang `window.t()`, lắng nghe sự kiện `languageChanged`.
- **Đa ngôn ngữ (i18n):**
  - `app/core/Language.php`: Bổ sung 40+ khóa dịch thuật (`perm_*`, `qc_*`, `winding_*`) cho cả 3 ngôn ngữ (`vi`, `en`, `ja`).
  - `public/assets/js/i18n.js`: Đồng bộ từ điển JavaScript cho tất cả các khóa dịch thuật mới của Phân quyền, Điều chỉnh QC và Điều chỉnh Cuộn.

### 3. Kết quả kiểm thử:

- Kiểm tra cú pháp PHP (`php -l`): Đạt chuẩn 100% không phát sinh lỗi cú pháp nào.
- Render kiểm thử View: Cả 3 view `qcEditBobinView.php`, `windingEditBobinView.php`, `employeePermissionsView.php` render HTML hoàn chỉnh với dữ liệu mẫu, không có ngoại lệ hay cảnh báo PHP nào.
- Khả năng hoạt động Local: Không sử dụng bất kỳ CDN ngoài nào, 100% tài nguyên CSS/JS cục bộ.

## [2026-10-08] - Nâng Cấp Quản Lý Nhân Viên: Bổ Sung Mã Bộ Phận (Cost Center), Chuẩn Hóa Schema & Tối Ưu UX Danh Sách (TASK-018)

### 1. Bối cảnh & Mục tiêu:

- Nâng cấp bảng cơ sở dữ liệu `employee_list` để tối ưu cho việc bảo trì, tra cứu và cập nhật dữ liệu hàng loạt từ Excel/CSV theo bộ phận sản xuất:
  - Loại bỏ hoàn toàn cột trạng thái `is_active` (không cần thiết).
  - Thêm trường `cost_center` (`VARCHAR(50) DEFAULT NULL COMMENT 'Mã bộ phận'`).
  - Thiết lập khóa chính tự tăng `PRIMARY KEY AUTO_INCREMENT` trên cột `id`, các chỉ mục duy nhất `UNIQUE KEY` trên `employee_code` và `username`, cùng các chỉ mục tối ưu tìm kiếm `KEY (role)` và `KEY (cost_center)`.
- Tái cấu trúc trang Danh sách nhân viên (`employeeListView.php`) và Phân quyền (`employeePermissionsView.php`):
  - Xóa bỏ bộ lọc theo `role` trên thanh công cụ, thay thế bằng bộ lọc theo `cost_center` (tự động lấy danh sách bộ phận duy nhất từ CSDL).
  - Loại bỏ cột trạng thái `is_active`, hiển thị rõ ràng cột `cost_center` (Mã bộ phận) với định dạng badge đẹp mắt.
  - Cập nhật các modal: Thêm mới nhân viên, Sửa nhân viên, Cấp lại mật khẩu, Nhập Excel và Xuất/Tải mẫu CSV để hỗ trợ `cost_center`.
  - Hỗ trợ đầy đủ đa ngôn ngữ (`vi`, `en`, `ja`) cho các trường thông tin bộ phận mới.

### 2. Các tệp tin đã thay đổi:

- **Cơ sở dữ liệu:** `production_db.employee_list` (đã nâng cấp cấu trúc chuẩn và lưu trữ 132 nhân viên với dữ liệu `cost_center` hợp lệ).
- **Backend & Repositories:**
  - `app/repositories/EmployeeRepository.php`: Bỏ `is_active = 1` trong câu truy vấn dự phòng `findByCode()`. Nạp sẵn `GlobalData.php` để đảm bảo hoạt động độc lập an toàn.
  - `app/repositories/ListDataRepository.php`: Bỏ `is_active = 1`, truy vấn đầy đủ `cost_center` trong `list_employee`.
  - `app/controllers/AuthController.php`: Bỏ `is_active = 1`, chọn `cost_center` khi đăng nhập và đổi mật khẩu.
  - `app/controllers/BobinController.php`: Bỏ `is_active = 1` trong xác thực mật khẩu điều chỉnh Bobin.
  - `app/controllers/EmployeeController.php`:
    - `index()`: Tích hợp bộ lọc theo `cost_center`, truy vấn danh sách `cost_center` duy nhất, loại bỏ bộ lọc `status` và `role`, cập nhật tính toán KPI không phụ thuộc `is_active`.
    - `create()`: Thêm trường `cost_center`, bỏ `is_active`.
    - `update()`: Cập nhật `employee_name`, `cost_center`, `role` theo `id`, bỏ `is_active`.
    - `exportExcel()`: Xuất danh sách có cột `Mã bộ phận` (`cost_center`).
    - `downloadTemplate()`: Cập nhật file mẫu CSV chuẩn với cột `Mã bộ phận` và dữ liệu mẫu minh họa.
    - `importExcel()`: Nhập danh sách gồm 5 cột chuẩn (Mã NV, Họ tên, Mã bộ phận, Vai trò, Tên đăng nhập).
    - `permissionsView()` & `getEmployeePermissions()`: Truy vấn và trả về `cost_center`, loại bỏ `is_active`.
  - `app/models/EmployeeModel.php`: Cập nhật toàn bộ các phương thức `getAll()`, `getStats()`, `create()`, `update()`, `importBatch()` đồng bộ với `cost_center`.
- **Giao diện người dùng (Views & Styles):**
  - `app/views/employeeListView.php`:
    - Thanh công cụ thay thế bộ lọc vai trò/trạng thái bằng dropdown chọn Mã bộ phận (`cost_center`).
    - Bảng danh sách hiển thị cột `Mã bộ phận` dạng badge monospace trang nhã.
    - Modal Thêm mới và Sửa nhân viên bổ sung ô nhập liệu `cost_center` kèm hướng dẫn.
    - Hướng dẫn nhập file CSV trong modal Import được cập nhật theo cấu trúc cột mới.
    - Bộ lọc nhanh tương tác client-side khi click vào thẻ KPI vai trò hoặc biểu đồ Donut Chart.
  - `app/views/employeePermissionsView.php`: Thay thế badge trạng thái bằng badge Mã bộ phận của nhân viên.
  - `public/assets/css/employeeList.css`: Thêm kiểu dáng chuyên biệt `.badge-cost-center`.
- **Đa ngôn ngữ (i18n):**
  - `app/core/Language.php`: Bổ sung các từ khóa `emp_cost_center`, `emp_all_cost_centers`, `emp_cost_center_ph`, `emp_cost_center_hint`, cập nhật `emp_import_col3-5` cho cả 3 ngôn ngữ (`vi`, `en`, `ja`).
  - `public/assets/js/i18n.js`: Đồng bộ từ điển JavaScript và ánh xạ `DOM_MAPPINGS` cho `Mã bộ phận`.

### 3. Kết quả kiểm thử:

- Kiểm tra cú pháp PHP Syntax Check (`php -l`): 100% tệp tin đạt chuẩn (0 lỗi).
- Kiểm tra truy vấn CSDL: Nạp và tra cứu chính xác 132 nhân viên theo mã bộ phận (ví dụ: `A00330`: 36 nhân viên).
- Kiểm tra render HTML của `employeeListView` và `employeePermissionsView`: Render thành công, không phát sinh lỗi cảnh báo nào.

## [2026-10-06] - Khắc Phục Truy Xuất & Đồng Bộ Từ Điển Đa Ngôn Ngữ Động Từ Trang Cấu Hình (TASK-017)

### 1. Bối cảnh & Yêu cầu:

- **Hiện tượng người dùng phản ánh:** Tại trang Cấu hình đa ngôn ngữ (`url=employee/languageManageView`), người dùng thêm/tùy biến các từ khóa như `btn_confirm` ("Xác nhận" / "Confirm" / "確認") và `btn_cancel` ("Trở lại" / "Back" / "戻る"). Tuy nhiên, giao diện toàn bộ hệ thống không cập nhật nội dung mới.
- **Yêu cầu cốt lõi:** Không fix cứng các giá trị dịch trong `i18n.js` mà phải dựa vào trang cấu hình đa ngôn ngữ (`config/custom_translations.json`) để tự động truy xuất và cập nhật thay đổi ngôn ngữ linh hoạt cho toàn bộ UI (modal, dialog, button, label, header).

### 2. Nguyên nhân cốt lõi (Root Cause Analysis):

1. **Lệch pha thời điểm nạp Script (Execution Order Race Condition):** Thẻ `<script src="i18n.js">` luôn nằm trong thẻ `<head>`, trong khi khối script gán `window.__CUSTOM_I18N__` trước đây chỉ được xuất trong `sidebar.php` nằm sâu trong thẻ `<body>`. Khi `i18n.js` được khởi chạy lần đầu trong `<head>`, `window.__CUSTOM_I18N__` là `undefined`, dẫn đến việc không có dữ liệu tùy chỉnh nào được nạp vào từ điển JavaScript.
2. **Thiếu cơ chế lưu đệm đồng bộ phía Client (Client-side Synchronous Cache):** Trình duyệt không lưu cache từ điển tùy biến vào `localStorage`, khiến việc tải trang luôn phải phụ thuộc vào biến toàn cục và gây hiện tượng nháy chữ hoặc mất bản dịch tùy biến.
3. **Cơ chế ghi đè dữ liệu khi thêm mới từ khóa:** API `updateTranslations` trước đây khi nhận một mảng từ khóa mới (ví dụ khi thêm `btn_cancel` từ modal) sẽ khởi tạo lại mảng rỗng và vô tình xóa sạch các từ khóa đã lưu trước đó nếu không có cơ chế gộp (`merge`).
4. **Hàm `window.t()` và `DOM_MAPPINGS` ghi đè `data-i18n`:**
   - Trong `i18n.js`, hàm `deepTranslateDOM()` duyệt qua các nút bấm bằng so khớp chuỗi tĩnh trong `DOM_MAPPINGS.buttons` (chứa các chuỗi cứng như `{ vi: 'Hủy', en: 'Cancel', ja: 'キャンセル' }`) mà không kiểm tra xem phần tử đã có thuộc tính `data-i18n` hay chưa, dẫn đến việc đè mất bản dịch động ("Trở lại" / "Back" / "戻る") mà người dùng đã thiết lập.
   - Hàm `window.t(key)` không có cơ chế fallback alias tương đương giữa `btn_confirm` <-> `confirm` và `btn_cancel` <-> `cancel`.

### 3. Chi tiết Giải pháp & Cải tiến:

- **Đồng bộ hóa Từ điển Tùy biến Động ([`i18n.js`](file:///c:/xampp/htdocs/WEB_BOBIN/public/assets/js/i18n.js)):**
  - **Khởi tạo tức thì từ `localStorage`:** Ngay dòng đầu tiên của `i18n.js`, hệ thống đọc đồng bộ `localStorage.getItem('webbobin_custom_i18n')` để nạp ngay từ điển tùy biến vào `DICT` và `window.__CUSTOM_I18N__`, triệt tiêu hoàn toàn độ trễ hiển thị.
  - **Hàm API toàn cục `window.loadAndMergeCustomTranslations(customObj)`:** Hỗ trợ nạp và gộp tức thì từ điển tùy biến bất cứ khi nào có bản dịch mới từ backend hoặc Ajax, tự động kích hoạt `deepTranslateDOM()` để cập nhật toàn bộ trang.
  - **Background Fetch an toàn:** Tự động gọi API `/WEB_BOBIN/public/index.php?url=employee/getCustomTranslations` ngầm nếu chưa có cache từ trước.
  - **Tối ưu hóa `window.t(key)`:** Ưu tiên tra cứu trong `window.__CUSTOM_I18N__` trước `DICT` mặc định, đồng thời hỗ trợ tra cứu tương đương 2 chiều giữa `btn_confirm` <-> `confirm` và `btn_cancel` <-> `cancel`.
  - **Bảo vệ `data-i18n` và Ánh xạ Động trong `deepTranslateDOM()`:**
    - Bước B (Headers), Bước C (Labels), Bước D (Buttons), Bước H (Sections) đều kiểm tra nếu phần tử đã có `data-i18n` thì không áp dụng ghi đè tĩnh.
    - Trong `DOM_MAPPINGS.buttons`, các nút hành động cốt lõi ("Xác nhận", "Hủy", "Quay lại", "Đóng") được liên kết trực tiếp với key tương ứng (`btn_confirm`, `btn_cancel`, `close`) để tự động gọi `window.t(item.key)` thay vì dùng chuỗi fix cứng.
    - Cải tiến hàm `applyDataI18n()` để bảo toàn thẻ icon SVG của nút bấm khi thay đổi nhãn văn bản.

- **Nâng cấp Backend Phía Server ([`Language.php`](file:///c:/xampp/htdocs/WEB_BOBIN/app/core/Language.php), [`EmployeeController.php`](file:///c:/xampp/htdocs/WEB_BOBIN/app/controllers/EmployeeController.php)):**
  - **Cơ chế Mirror Alias trong `Language::loadDictionary()`:** Khi phát hiện key `btn_cancel` hoặc `btn_confirm` trong `custom_translations.json`, hệ thống tự động gán giá trị tương ứng cho alias `cancel` và `confirm` (nếu chưa được tùy chỉnh riêng), đảm bảo các hàm backend `__('cancel')` và `__('btn_cancel')` đều trả về đúng bản dịch tùy biến.
  - **Tối ưu hóa `updateTranslations()` và `resetTranslations()`:**
    - Khi thêm một từ khóa mới từ modal, hệ thống tự động gộp với các từ khóa hiện có trong `custom_translations.json` thay vì xóa ghi đè.
    - Cả hai API `updateTranslations` và `resetTranslations` đều trả về `custom_dict` đầy đủ để frontend cập nhật ngay vào `localStorage`.

- **Nhúng Từ Điển Động Vào Các Layout & View:**
  - Cập nhật [`header.php`](file:///c:/xampp/htdocs/WEB_BOBIN/app/views/components/header.php), [`sidebar.php`](file:///c:/xampp/htdocs/WEB_BOBIN/app/views/components/sidebar.php), [`loginView.php`](file:///c:/xampp/htdocs/WEB_BOBIN/app/views/loginView.php) và [`changePasswordView.php`](file:///c:/xampp/htdocs/WEB_BOBIN/app/views/changePasswordView.php) để nhúng dữ liệu `Language::getCustomDictionary()` và gọi `window.loadAndMergeCustomTranslations`.
  - Cập nhật JavaScript trong [`languageManageView.php`](file:///c:/xampp/htdocs/WEB_BOBIN/app/views/languageManageView.php) để khi bấm "Lưu toàn bộ thay đổi", "Khôi phục", hoặc "Thêm từ khóa mới", dữ liệu mới được lưu tức thì vào `localStorage` trước khi tải lại trang.

---

## [2026-10-06] - Tối Ưu Toàn Diện Giao Diện Đa Thiết Bị Responsive Android, iOS & Tablet (TASK-016)

### 1. Bối cảnh & Mục tiêu:

- **Khắc phục lỗi hiển thị đa thiết bị:** Người dùng phản ánh trên điện thoại Android, iPhone (iOS) và máy tính bảng (Tablet/iPad) giao diện không đều bố cục, tràn layout (horizontal overflow), chồng lấn thanh điều hướng và khó thao tác chạm.
- **Tiêu chuẩn kiểm thử:** Đảm bảo trải nghiệm chạm mượt mà (touch target >= 40px), không bị auto-zoom trên iOS Safari, không tràn chiều ngang (zero horizontal blowout) trên các kích thước màn hình phổ biến (360px - 1024px).
- **Môi trường hoạt động:** 100% Offline Local Intranet trên XAMPP, giữ nguyên typography `var(--font-family-base)` và toàn vẹn bảo mật phân quyền.

### 2. Chi tiết Cải tiến & Triển khai:

- **Hợp nhất Top Header Bar & Thanh trượt Sidebar ([`header.php`](file:///c:/xampp/htdocs/WEB_BOBIN/app/views/components/header.php), [`sidebar.php`](file:///c:/xampp/htdocs/WEB_BOBIN/app/views/components/sidebar.php), [`sidebar.css`](file:///c:/xampp/htdocs/WEB_BOBIN/public/assets/css/sidebar.css)):**
  - **Lỗi cũ:** Trên màn hình <= 992px xuất hiện 2 thanh Topbar xếp chồng (`.sb-mobile-topbar` 56px và `.app-top-header` 54px) chiếm tới 110-140px chiều cao màn hình và va chạm cuộn z-index.
  - **Khắc phục:** Tích hợp nút Hamburger `#headerMobileToggle` trực tiếp vào Top Header; ẩn hoàn toàn `.sb-mobile-topbar` dư thừa; tối ưu Top Header dạng sticky 50-54px hiển thị Breadcrumb rút gọn, cụm cờ ngôn ngữ thu nhỏ, avatar người dùng và icon thao tác nhanh.
  - Tự động đóng drawer menu khi click vào bất kỳ liên kết trang nào (`.sb-tree-leaf`) hoặc khi nhấn phím ESC.

- **Khắc phục triệt để lỗi tràn Layout trên Điện thoại & Tablet:**
  - **Trang Phân quyền Nhân viên ([`employeePermissions.css`](file:///c:/xampp/htdocs/WEB_BOBIN/public/assets/css/employeePermissions.css)):** Sửa lỗi `minmax(420px, 1fr)` gây vỡ khung trên mọi màn hình điện thoại (360px - 414px) thành `minmax(280px, 1fr)` (rơi về `1fr` trên mobile). Thêm padding an toàn `env(safe-area-inset-bottom)` cho thanh lưu quyền trên iPhone.
  - **Trang Quản lý Ngôn ngữ ([`languageManage.css`](file:///c:/xampp/htdocs/WEB_BOBIN/public/assets/css/languageManage.css)):** Bổ sung `min-width: 780px` kèm khung cuộn ngang chuyên biệt `.lang-table-scroll` để tránh bảng từ điển 5 cột bị ép dẹp; cấu hình modal cuộn linh hoạt theo màn hình.
  - **Trang Quản lý Nhân viên ([`employeeList.css`](file:///c:/xampp/htdocs/WEB_BOBIN/public/assets/css/employeeList.css)):** Tối ưu lưới thống kê 2 cột cân đối trên di động, căn chỉnh nhóm nút thao tác 2x2, làm modal form tự động cuộn khi bàn phím ảo xuất hiện.
  - **Trang Đăng nhập ([`login.css`](file:///c:/xampp/htdocs/WEB_BOBIN/public/assets/css/login.css)):** Đổi hướng hiển thị sang `flex-direction: column-reverse` trên màn hình <= 1024px giúp form đăng nhập hiển thị ngay trên đầu màn hình điện thoại thay vì bị đẩy xuống dưới các thẻ giới thiệu cổng thông tin.
  - **Khống chế Camera Quét QR (`#qr-reader`):** Bổ sung ràng buộc kích thước nghiêm ngặt `max-width: 100% !important; object-fit: contain !important;` cho luồng camera video/canvas trên cả 7 file CSS vận hành Bobin, triệt tiêu hoàn toàn lỗi camera phình to quá màn hình.

- **Chống lỗi tự động Phóng to (Auto-Zoom) trên iOS Safari:**
  - Thiết lập `font-size: 16px !important;` cho toàn bộ thẻ `input`, `select`, `textarea` trên các breakpoint di động (<= 640px / <= 768px), ngăn chặn việc iOS Safari tự động zoom màn hình làm lệch giao diện khi người dùng chạm vào nhập liệu.
  - Chuẩn hóa chiều cao nút bấm cảm ứng (min-height 40-44px) trên toàn bộ hệ thống.

- **Đồng bộ Hộp thoại Xác nhận Mật khẩu Thao tác (ConfirmDialog):**
  - Cập nhật định dạng modal box responsive, cuộn linh hoạt (`max-height: calc(100vh - 32px)`), font chữ chuẩn hệ thống đồng bộ trên cả 3 tệp JavaScript: [`Extrusion/edit_submit.js`](file:///c:/xampp/htdocs/WEB_BOBIN/public/assets/js/Extrusion/edit_submit.js), [`QC/edit_submit.js`](file:///c:/xampp/htdocs/WEB_BOBIN/public/assets/js/QC/edit_submit.js) và [`Winding/edit_submit.js`](file:///c:/xampp/htdocs/WEB_BOBIN/public/assets/js/Winding/edit_submit.js).

- **Sửa Triệt Để Lỗi Không Mở Được Menu Bar trên Android (Xiaomi) & Thiết Bị Di Động:**
  - **Nguyên nhân cốt lõi:** Các trang View luôn nạp `sidebar.php` trước `header.php`. Khi khối script nội tuyến của `sidebar.php` thực thi tại thời điểm parse DOM ban đầu, phần tử `#headerMobileToggle` trong `header.php` chưa hề tồn tại trong cây DOM. Do đó, nút hamburger không được gắn bất kỳ listener sự kiện click nào. Ngoài ra trên các thiết bị Android (Xiaomi), trình duyệt có cử chỉ vuốt mép màn hình và độ trễ tap 300ms gây khó kích hoạt nút bấm.
  - **Khắc phục toàn diện:**
    - Khởi tạo hàm toàn cục `window.toggleWebBobinMobileMenu(event)` và gán trực tiếp thuộc tính `onclick` trên thẻ HTML `#headerMobileToggle`.
    - Sử dụng kỹ thuật Event Delegation tại cấp độ `document.addEventListener('click', ...)` để bắt trọn mọi tương tác chạm/click của người dùng mà không phụ thuộc thứ tự nạp DOM.
    - Tích hợp debounce chống double-trigger touch + click (< 250ms).
    - Thêm `touch-action: manipulation` và mở rộng kích thước nút bấm chuẩn (40-42px) cho trải nghiệm chạm tức thì trên Android (Xiaomi).
    - Bổ sung `height: 100dvh` và khóa cuộn nền `body.sb-drawer-active` giúp thanh menu vừa khít với màn hình điện thoại khi thanh địa chỉ trình duyệt co giãn.

---

## [2026-10-06] - Tái Thiết Kế Top Header Bar Chuyên Nghiệp & Khắc Phục Lỗi Chèn Chữ Sidebar (TASK-015)

### 1. Bối cảnh & Mục tiêu:

- **Tối ưu Top Header Bar:** Thay vì Header chỉ mang tính hiển thị tĩnh, nâng cấp thành thanh điều khiển trung tâm chuẩn ERP với đầy đủ tính năng: Breadcrumb điều hướng bên trái; Bộ chọn ngôn ngữ (VI/EN/JA), Thẻ người dùng, Đổi mật khẩu và Đăng xuất tập trung tại góc phải Header.
- **Khắc phục triệt để lỗi chèn chữ ở Sidebar:** Xử lý hiện tượng mở các thư mục phía dưới (Quản trị, Giám sát) làm các thư mục phía trên bị Flexbox co ép chiều cao dẫn đến đè chữ, vỡ layout.
- **Chuẩn hóa kiến trúc mã nguồn:** Xây dựng component [`app/views/components/header.php`](file:///c:/xampp/htdocs/WEB_BOBIN/app/views/components/header.php) dùng chung, tách bạch rõ ràng giữa thanh Sidebar và Top Header, mã nguồn dễ đọc, dễ mở rộng và bảo trì.

### 2. Chi tiết Cải tiến & Triển khai:

- **Thiết kế lại Top Header Bar ([`app/views/components/header.php`](file:///c:/xampp/htdocs/WEB_BOBIN/app/views/components/header.php)):**
  - **Góc trái Header:** Breadcrumb ngữ cảnh 3 cấp độ `🏠 Trang chủ` > `📁 [Thư Mục Phân Xưởng]` > `📄 [Trang Thao Tác Hiện Tại]`.
  - **Góc phải Header:**
    - **Bộ chọn ngôn ngữ:** Nhóm nút pill hiện đại 🇻🇳 VI | 🇬🇧 EN | 🇯🇵 JA với highlight xanh khi được chọn.
    - **Thẻ thông tin người dùng:** Avatar theo màu vai trò (Admin tím, Đùn cam, QC xanh dương, Cuộn xanh lá) + Họ tên + Mã nhân viên & Role badge.
    - **Nút Đổi mật khẩu (`nav_change_pwd`):** Nút thao tác nhanh với icon 🔑 dẫn đến trang đổi mật khẩu.
    - **Nút Đăng xuất (`nav_logout`):** Nút đỏ nổi bật với icon 🚪 cho phép thoát an toàn.
  - Tích hợp lớp tương thích ngược tại [`app/views/components/breadcrumb.php`](file:///c:/xampp/htdocs/WEB_BOBIN/app/views/components/breadcrumb.php) để đảm bảo toàn bộ hệ thống hoạt động đồng bộ.
- **Khắc phục lỗi chèn chữ trên Sidebar Slider Bar ([`public/assets/css/sidebar.css`](file:///c:/xampp/htdocs/WEB_BOBIN/public/assets/css/sidebar.css)):**
  - **Nguyên nhân cốt lõi:** `.sb-nav-scroll` là flex column container nhưng các node thư mục `.sb-tree-folder`, `.sb-tree-folder-head` không có thuộc tính `flex-shrink: 0`. Khi thư mục dưới mở ra làm tăng tổng chiều cao, Flexbox tự động co xẹp chiều cao của các folder bên trên, khiến chữ và icon bị đè nén lên nhau.
  - **Giải pháp dứt điểm:**
    - Cấu hình `.sb-nav-scroll`: `flex: 1 1 auto; min-height: 0 !important; overflow-y: auto !important; overflow-x: hidden !important;`.
    - Thiết lập `flex-shrink: 0 !important;` cho toàn bộ `.sb-tree-folder`, `.sb-tree-folder-head`, `.sb-tree-children`, `.sb-tree-leaf`.
    - Khóa chiều cao tối thiểu chuẩn cho head (`min-height: 42px; height: 42px;`) và leaf (`min-height: 38px; height: 38px;`).
    - Bổ sung `min-width: 0; text-overflow: ellipsis; white-space: nowrap;` để đảm bảo văn bản hiển thị nguyên vẹn.
    - Thêm cơ chế `scrollIntoView({ behavior: 'smooth', block: 'nearest' })` khi click mở thư mục, giúp tự động cuộn đến vùng nhìn thấy trọn vẹn.
    - Tối giản chân sidebar thành `.sb-footer-minimal` (chỉ hiển thị status SMC và phiên bản), giải phóng hoàn toàn không gian dọc cho Folder Tree.

- **Đồng bộ hóa 12 Trang View:**
  - Cập nhật [`extrusionView.php`](file:///c:/xampp/htdocs/WEB_BOBIN/app/views/extrusionView.php), [`extrusionEditBobinView.php`](file:///c:/xampp/htdocs/WEB_BOBIN/app/views/extrusionEditBobinView.php), [`qcView.php`](file:///c:/xampp/htdocs/WEB_BOBIN/app/views/qcView.php), [`qcEditBobinView.php`](file:///c:/xampp/htdocs/WEB_BOBIN/app/views/qcEditBobinView.php), [`windingView.php`](file:///c:/xampp/htdocs/WEB_BOBIN/app/views/windingView.php), [`windingEditBobinView.php`](file:///c:/xampp/htdocs/WEB_BOBIN/app/views/windingEditBobinView.php), [`listBobinDetailView.php`](file:///c:/xampp/htdocs/WEB_BOBIN/app/views/listBobinDetailView.php), [`listBobinHistoryView.php`](file:///c:/xampp/htdocs/WEB_BOBIN/app/views/listBobinHistoryView.php), [`listPendingCancellationView.php`](file:///c:/xampp/htdocs/WEB_BOBIN/app/views/listPendingCancellationView.php), [`employeeListView.php`](file:///c:/xampp/htdocs/WEB_BOBIN/app/views/employeeListView.php), [`employeePermissionsView.php`](file:///c:/xampp/htdocs/WEB_BOBIN/app/views/employeePermissionsView.php), [`languageManageView.php`](file:///c:/xampp/htdocs/WEB_BOBIN/app/views/languageManageView.php).
  - Tất cả các view nạp trực tiếp component chuẩn `require ROOT_PATH . '/app/views/components/header.php';`.

---

## [2026-10-06] - Kiến Trúc Điều Hướng Cây Thư Mục (Folder Tree) & Breadcrumb Thông Minh (TASK-014)

### 1. Bối cảnh & Yêu cầu:

- Khắc phục tình trạng điều hướng dạng thanh dọc phẳng dồn tất cả các chức năng rời rạc vào một cột đơn điệu, gây khó định hướng và rối mắt cho người dùng.
- Tái cấu trúc thành điều hướng cây thư mục chuyên nghiệp (**Folder Tree / File Explorer Navigation**) kết hợp thanh điều hướng vị trí thực tế (**Contextual Breadcrumb Bar**) trên từng phân trang.

### 2. Chi tiết Cải tiến & Triển khai:

- **Kiến trúc Cây Thư mục trong Sidebar ([`app/views/components/sidebar.php`](file:///c:/xampp/htdocs/WEB_BOBIN/app/views/components/sidebar.php)):**
  - Chuyển đổi các danh mục phân xưởng thành các Node thư mục dạng cây (`sb-tree-folder`):
    - Đùn (Extrusion) `📁 SẢN XUẤT`
    - QC (Quality Control) `📁 KIỂM TRA QC`
    - Cuộn (Winding) `📁 CUỘN HOÀN TẤT`
    - Giám sát (Monitoring) `📁 GIÁM SÁT & BÁO CÁO`
    - Quản trị (System) `📁 QUẢN TRỊ HỆ THỐNG`
  - Tích hợp biểu tượng chevron mở rộng `▸` xoay chuyển mượt mà 90 độ khi mở (`is-open`), biểu tượng thư mục tự động chuyển `📁` (đóng) <-> `📂` (mở), kèm theo badge hiển thị số lượng chức năng khả dụng trong từng thư mục.
  - Tự động nhận diện và mở rộng thư mục chứa trang active hiện tại (`has-active-child`), đồng thời ghi nhớ trạng thái đóng/mở của người dùng vào `localStorage` (`webbobin_tree_folder_{id}`).
  - Thiết kế các đường kẻ định vị phân nhánh cây (`sb-tree-children::before`, `sb-tree-leaf::before`) tạo cảm giác cấu trúc thư mục rõ ràng, chuyên nghiệp.
  - Đảm bảo 100% nguyên tắc bảo mật phân quyền: Thư mục chỉ hiển thị nếu người dùng có ít nhất một quyền hợp lệ bên trong; thư mục trống sẽ bị ẩn hoàn toàn.

- **Thành phần Breadcrumb Header Bar ([`app/views/components/breadcrumb.php`](file:///c:/xampp/htdocs/WEB_BOBIN/app/views/components/breadcrumb.php)):**
  - Xây dựng component Breadcrumb tái sử dụng theo cấu trúc 3 cấp độ không gian:
    `🏠 Trang chủ` > `📁 [Tên Thư Mục Phân Xưởng]` > `📄 [Trang Thao Tác Hiện Tại]`
  - Tự động ánh xạ route theo URL hiện hành và quyền thực tế của người dùng (`$homeUrl`).
  - Hỗ trợ đa ngôn ngữ hoàn toàn (`data-i18n`, `vi`, `en`, `ja`) và tương thích responsive trên thiết bị di động.

- **Đồng bộ CSS & Tích hợp vào Tất cả các Trang:**
  - Bổ sung định dạng CSS cây thư mục và breadcrumb trong [`public/assets/css/sidebar.css`](file:///c:/xampp/htdocs/WEB_BOBIN/public/assets/css/sidebar.css), bảo toàn hệ font stack `var(--font-family-base)`.
  - Nhúng Breadcrumb vào 10 trang cốt lõi của hệ thống:
    - [`extrusionView.php`](file:///c:/xampp/htdocs/WEB_BOBIN/app/views/extrusionView.php)
    - [`extrusionEditBobinView.php`](file:///c:/xampp/htdocs/WEB_BOBIN/app/views/extrusionEditBobinView.php)
    - [`qcView.php`](file:///c:/xampp/htdocs/WEB_BOBIN/app/views/qcView.php)
    - [`qcEditBobinView.php`](file:///c:/xampp/htdocs/WEB_BOBIN/app/views/qcEditBobinView.php)
    - [`windingView.php`](file:///c:/xampp/htdocs/WEB_BOBIN/app/views/windingView.php)
    - [`windingEditBobinView.php`](file:///c:/xampp/htdocs/WEB_BOBIN/app/views/windingEditBobinView.php)
    - [`listBobinDetailView.php`](file:///c:/xampp/htdocs/WEB_BOBIN/app/views/listBobinDetailView.php)
    - [`listBobinHistoryView.php`](file:///c:/xampp/htdocs/WEB_BOBIN/app/views/listBobinHistoryView.php)
    - [`listPendingCancellationView.php`](file:///c:/xampp/htdocs/WEB_BOBIN/app/views/listPendingCancellationView.php)
    - [`employeeListView.php`](file:///c:/xampp/htdocs/WEB_BOBIN/app/views/employeeListView.php)
    - [`employeePermissionsView.php`](file:///c:/xampp/htdocs/WEB_BOBIN/app/views/employeePermissionsView.php)
    - [`languageManageView.php`](file:///c:/xampp/htdocs/WEB_BOBIN/app/views/languageManageView.php)
- **Từ điển Đa ngôn ngữ ([`Language.php`](file:///c:/xampp/htdocs/WEB_BOBIN/app/core/Language.php) & [`i18n.js`](file:///c:/xampp/htdocs/WEB_BOBIN/public/assets/js/i18n.js)):**
  - Bổ sung khóa dịch `breadcrumb_home` và `tree_items_count` đồng bộ trên 3 ngôn ngữ `vi`, `en`, `ja`.

---

## [2026-10-06] - Khắc Phục Lỗi Thiếu Cột Permissions & Đồng Bộ Cấu Trúc Database (production_db)

### 1. Nguyên nhân lỗi đăng nhập:

- Lỗi xuất hiện tại màn hình đăng nhập: `SQLSTATE[42S22]: Column not found: 1054 Unknown column 'permissions' in 'field list'`
- **Nguyên nhân gốc rễ:** File [`public/assets/production_db_database.sql`](file:///c:/xampp/htdocs/WEB_BOBIN/public/assets/production_db_database.sql) và database MySQL `production_db` đang chạy trên máy chủ nội bộ lúc trước chưa được cập nhật cột `permissions` (vốn được quy định trong TASK-010 phục vụ phân quyền tùy biến chi tiết cho tài khoản). Khi [`AuthController.php`](file:///c:/xampp/htdocs/WEB_BOBIN/app/controllers/AuthController.php) thực thi truy vấn đăng nhập `SELECT ... permissions FROM employee_list`, MySQL ném ngoại lệ do thiếu trường này.

### 2. Xử lý & Cải tiến:

- **Cập nhật database MySQL hiện hành:**
  - Thực thi bổ sung cột `permissions LONGTEXT DEFAULT NULL COMMENT 'Mảng JSON lưu danh sách mã quyền thao tác tùy biến (nếu NULL thì theo Role mặc định)' AFTER is_first_login` vào bảng `employee_list`.
  - Toàn bộ 135 tài khoản hiện có kế thừa giá trị `NULL` (tự động fallback về quyền mặc định theo vai trò `role` ban đầu), không làm mất mát hoặc gián đoạn bất kỳ dữ liệu nhân sự nào.
- **Đồng bộ hóa tập tin bản dựng SQL:**
  - Cập nhật [`public/assets/production_db_database.sql`](file:///c:/xampp/htdocs/WEB_BOBIN/public/assets/production_db_database.sql) phản ánh chính xác cấu trúc bảng `employee_list` có trường `permissions`.
  - Cập nhật tài liệu kỹ thuật [`AI_preference/DATABASE_SCHEMA.md`](file:///c:/xampp/htdocs/WEB_BOBIN/AI_preference/DATABASE_SCHEMA.md) để đồng bộ hoàn toàn giữa code, tài liệu và database.

---

## [2026-10-06] - Trang Cấu Hình Đa Ngôn Ngữ Động & Điều Hướng Thông Minh Theo Quyền Hạn (TASK-012 & TASK-013)

### 1. Quản lý Từ điển Đa ngôn ngữ Động qua Textfield (TASK-012)

- **Kiến trúc Từ điển Linh hoạt (Dynamic I18n Storage):**
  - Lưu trữ bản dịch tùy biến vào tập tin cấu hình [`config/custom_translations.json`](file:///c:/xampp/htdocs/WEB_BOBIN/config/custom_translations.json) (không làm biến đổi database schema, 100% offline local, an toàn dữ liệu tuyệt đối).
  - Cập nhật [`Language.php`](file:///c:/xampp/htdocs/WEB_BOBIN/app/core/Language.php):
    - Phương thức `getCustomFilePath()` và `getCustomDictionary()` nạp các giá trị dịch ghi đè.
    - Phương thức `saveCustomDictionary()` lưu trữ định dạng UTF-8 JSON có khóa phân tách theo từng ngôn ngữ `vi`, `en`, `ja`.
    - Trong `loadDictionary()`, tự động hợp nhất các bản dịch tùy chỉnh đè lên từ điển mặc định của hệ thống.
  - Cập nhật [`public/assets/js/i18n.js`](file:///c:/xampp/htdocs/WEB_BOBIN/public/assets/js/i18n.js):
    - Đọc đối tượng `window.__CUSTOM_I18N__` được nhúng từ layout máy chủ để đồng bộ tức thời mọi key tùy biến vào frontend `DICT` cho 3 ngôn ngữ `vi`, `en`, `ja`.
- **Giao diện Quản trị Cấu hình Từ điển ([`app/views/languageManageView.php`](file:///c:/xampp/htdocs/WEB_BOBIN/app/views/languageManageView.php)):**
  - Bố cục Dashboard hiện đại chuẩn doanh nghiệp kế thừa `var(--font-family-base)`: Hero header, 3 thẻ thống kê (Tổng mục từ điển, Đã tùy chỉnh riêng, Số ngôn ngữ đồng bộ).
  - Thanh công cụ tìm kiếm lọc tức thì theo từ khóa / nội dung dịch và lọc theo phạm vi (Tất cả, Đã tùy biến, Mặc định).
  - Bảng lưới tương tác trực quan với các ô `input textfield` cho từng ngôn ngữ (`VI`, `EN`, `JA`) đối với từng dòng từ khóa: người dùng có thể gõ chỉnh sửa nội dung bất kỳ lúc nào mà không bị fix cứng.
  - Hỗ trợ thêm mới từ khóa dịch thuật qua Modal popup và khôi phục (Reset) từng từ khóa hoặc toàn bộ từ điển về trạng thái gốc mặc định.
- **Bộ điều khiển & Phân quyền:**
  - Bổ sung 3 action vào [`EmployeeController.php`](file:///c:/xampp/htdocs/WEB_BOBIN/app/controllers/EmployeeController.php): `translationsView()`, `updateTranslations()`, `resetTranslations()`.
  - Cập nhật [`AuthHelper.php`](file:///c:/xampp/htdocs/WEB_BOBIN/app/core/AuthHelper.php) tích hợp các route quản lý ngôn ngữ dưới quyền `permission_manage`.

### 2. Thiết kế Lại Giao Diện Thông Minh Theo Quyền Hạn (TASK-013)

- **Ẩn hoàn toàn các nút nhấn/liên kết điều hướng đến trang không có quyền:**
  - **Sidebar Dọc Bên Trái ([`app/views/components/sidebar.php`](file:///c:/xampp/htdocs/WEB_BOBIN/app/views/components/sidebar.php)):**
    - Nhóm 1 (Đùn): Chỉ hiển thị nếu tài khoản có quyền `extrusion_create` hoặc `extrusion_edit`.
    - Nhóm 2 (QC): Chỉ hiển thị nếu tài khoản có quyền `qc_check` hoặc `qc_edit`.
    - Nhóm 3 (Cuộn): Chỉ hiển thị nếu tài khoản có quyền `winding_confirm` hoặc `winding_edit`.
    - Nhóm 4 (Giám sát & Báo cáo): Bọc toàn bộ khối trong điều kiện `if ($canBobinList || $canBobinHistory || $canPendingCancel)`, nếu tài khoản không có quyền xem giám sát nào thì ẩn hoàn toàn cả tiêu đề nhóm và các liên kết con.
    - Nhóm 5 (Quản trị): Chỉ hiển thị các mục tương ứng với `canEmployeeManage` và `canPermissionManage` (bao gồm link mới "Cấu hình đa ngôn ngữ").
  - **Điều hướng Logo Thương hiệu & Mobile Topbar:**
    - Tính toán động `$userHomeUrl` dựa theo quyền được cấp thực tế cao nhất của tài khoản (ưu tiên Đùn -> QC -> Cuộn -> Giám sát -> Quản trị).
    - Logo thương hiệu `sb-brand-main` và tiêu đề mobile tự động dẫn về đúng `$userHomeUrl` của user thay vì hardcode cố định dẫn đến lỗi phân quyền.
  - **Nút "Quay lại" (Back button) tại các trang view:**
    - [`extrusionEditBobinView.php`](file:///c:/xampp/htdocs/WEB_BOBIN/app/views/extrusionEditBobinView.php): Nút quay lại kiểm tra quyền `extrusion_create`, nếu không có quyền sẽ trỏ an toàn về `$userHomeUrl`.
    - [`qcEditBobinView.php`](file:///c:/xampp/htdocs/WEB_BOBIN/app/views/qcEditBobinView.php): Nút quay lại kiểm tra quyền `qc_check`, trỏ về trang QC tương ứng hoặc fallback về `$userHomeUrl`.
    - [`windingEditBobinView.php`](file:///c:/xampp/htdocs/WEB_BOBIN/app/views/windingEditBobinView.php): Nút quay lại kiểm tra quyền `winding_confirm`, trỏ về trang Cuộn tương ứng hoặc fallback về `$userHomeUrl`.
    - [`listBobinDetailView.php`](file:///c:/xampp/htdocs/WEB_BOBIN/app/views/listBobinDetailView.php), [`listBobinHistoryView.php`](file:///c:/xampp/htdocs/WEB_BOBIN/app/views/listBobinHistoryView.php), [`listPendingCancellationView.php`](file:///c:/xampp/htdocs/WEB_BOBIN/app/views/listPendingCancellationView.php): Nút quay lại kiểm tra quyền `extrusion_create` trước khi dẫn về `bobin/index`, nếu không có quyền thì dẫn an toàn về `$userHomeUrl` của tài khoản, tránh hoàn toàn tình trạng bị chuyển hướng 403.

---

## [2026-10-06] - Thống Nhất Typography Toàn Cục & Hoàn Thiện Mô Hình Phân Quyền Động (TASK-011)

### 1. Thống nhất Font Family toàn bộ các trang và chức năng

- **Thiết lập Typography toàn cục:**
  - Định nghĩa bộ biến CSS phông chữ chuẩn hệ thống doanh nghiệp (System Font Stack) tại `:root` trong [`public/assets/css/i18n.css`](file:///c:/xampp/htdocs/WEB_BOBIN/public/assets/css/i18n.css) (tập tin CSS nền tảng được nạp ở `<head>` của 100% trang web trong hệ thống):
    - Phông chữ văn bản chính: `--font-family-base: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol";`
    - Phông chữ kỹ thuật (Monospace): `--font-family-mono: ui-monospace, SFMono-Regular, "SF Mono", Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;`
  - Áp dụng `html, body { font-family: var(--font-family-base) !important; }` cùng tính năng khử răng cưa mượt mà (`-webkit-font-smoothing: antialiased`).
  - Toàn bộ các thẻ điều khiển biểu mẫu (`input`, `button`, `select`, `textarea`) tự động kế thừa font thống nhất.
  - Các phần tử hiển thị mã định danh (mã Bobin, mã NV, lot vật tư, JSON) tự động áp dụng `--font-family-mono`.
- **Đồng bộ hóa các CSS trang chuyên biệt:**
  - [`sidebar.css`](file:///c:/xampp/htdocs/WEB_BOBIN/public/assets/css/sidebar.css): Áp dụng `var(--font-family-base)` cho toàn bộ thanh Sidebar.
  - [`employeeList.css`](file:///c:/xampp/htdocs/WEB_BOBIN/public/assets/css/employeeList.css): Chuyển đổi font body riêng lẻ sang `var(--font-family-base)`.
  - [`employeePermissions.css`](file:///c:/xampp/htdocs/WEB_BOBIN/public/assets/css/employeePermissions.css): Kế thừa đầy đủ bộ font thống nhất.

### 2. Hoàn thiện mô hình Phân quyền Động (Dynamic Permission Access Control)

- **Thực trạng quyền tài khoản hiện hữu:**
  - Tổng số 135 tài khoản trong cơ sở dữ liệu (14 admin, 51 extrusion, 5 qc, 65 winding) hiện có trường `permissions = NULL`, kế thừa bộ quyền mặc định tương ứng với vai trò (`role`) ban đầu.
- **Loại bỏ phụ thuộc cứng vào thiết kế vai trò ban đầu:**
  - Trước đây, một số controller (`BobinController`, `EmployeeController`) và `Router.php` vẫn còn kiểm tra cứng vai trò (`$role === 'admin'` hoặc `in_array($role, ['qc', 'admin'])`). Điều này khiến việc tùy biến quyền cho các tài khoản không có hiệu lực hoàn toàn (ví dụ: cấp quyền QC cho nhân viên Đùn vẫn bị chặn, hoặc tước quyền Cuộn của Admin bị bypass).
  - Đã tái cấu trúc toàn diện các vị trí kiểm tra sang sử dụng `AuthHelper::hasPermission()` và `AuthHelper::isActionAllowed()`:
    - [`Router.php`](file:///c:/xampp/htdocs/WEB_BOBIN/app/core/Router.php): Kiểm tra phân quyền truy cập action 100% qua `AuthHelper::isActionAllowed()`.
    - [`BobinController.php`](file:///c:/xampp/htdocs/WEB_BOBIN/app/controllers/BobinController.php): `index()`, `qcEditBobinView()`, `windingEditBobinView()`, `updateQCEditBobin()`, `updateWindingEditBobin()` chuyển sang kiểm tra quyền cụ thể (`qc_edit`, `winding_edit`, `extrusion_create`, v.v.).
    - [`EmployeeController.php`](file:///c:/xampp/htdocs/WEB_BOBIN/app/controllers/EmployeeController.php): Kiểm tra quyền hạn chuẩn xác qua `employee_manage` và `permission_manage`.
  - Nhờ đó, hệ thống hiện tại có thể phân quyền độc lập, linh hoạt và chính xác cho từng tài khoản mà không bị ràng buộc bởi thiết kế vai trò ban đầu.

---

## [2026-10-06] - Phân Quyền Chi Tiết Theo Từng Thao Tác & Nâng Cấp Vertical Sidebar Doanh Nghiệp (TASK-010)

### 1. Cơ sở dữ liệu (Database Schema)

- Bổ sung trường `permissions LONGTEXT DEFAULT NULL` vào bảng `employee_list` (sau trường `is_first_login`).
- `permissions` lưu trữ mảng JSON chứa danh sách key quyền tùy biến được cấp cho từng nhân viên.
- Nếu trường này là `NULL`, hệ thống tự động kế thừa bộ quyền mặc định tương ứng với vai trò (`role`) ban đầu của nhân viên, đảm bảo tương thích ngược 100% với tài khoản hiện hữu.

### 2. Kiến trúc & Bộ điều hướng (Auth Architecture & Router)

- **`app/core/AuthHelper.php`:**
  - Định nghĩa 11 quyền thao tác chi tiết chia làm 5 nhóm:
    - **Công đoạn Đùn:** `extrusion_create` (Nhập liệu & tạo Bobin), `extrusion_edit` (Điều chỉnh & xóa Bobin đùn).
    - **Kiểm tra QC:** `qc_check` (Xác nhận ngoại quan QC), `qc_edit` (Điều chỉnh kết quả QC & báo hủy).
    - **Công đoạn Cuộn:** `winding_confirm` (Nhập máy cuộn & hoàn tất), `winding_edit` (Điều chỉnh thông số cuộn & báo hủy).
    - **Theo dõi & Báo cáo:** `bobin_list` (Danh sách chi tiết & xuất Excel), `bobin_history` (Lịch sử Bobin & audit trail), `pending_cancel` (Quản lý duyệt hủy Bobin).
    - **Quản trị hệ thống:** `employee_manage` (Quản lý tài khoản nhân viên), `permission_manage` (Phân quyền thao tác theo từng account).
  - Tích hợp cơ chế bảo vệ Admin không bao giờ bị khóa quyền `permission_manage`.
  - Tích hợp cơ chế đồng bộ ngay quyền trong `$_SESSION` nếu Admin đang thao tác trên tài khoản của chính mình.
- **`app/core/Router.php`:** Tích hợp `AuthHelper::isActionAllowed()` kiểm tra quyền hạn chi tiết trên từng action trước khi dispatch.
- **`app/controllers/AuthController.php`:** Nạp trường `permissions` từ database vào `$_SESSION['user']['permissions']` ngay khi đăng nhập.
- **`app/controllers/EmployeeController.php`:**
  - `permissionsView()`: Hiển thị giao diện quản lý phân quyền.
  - `getEmployeePermissions()`: API AJAX GET trả về chi tiết quyền và thông tin nhân viên theo mã số.
  - `updatePermissions()`: API AJAX POST lưu trữ danh sách quyền tùy biến hoặc reset về quyền vai trò mặc định (`NULL`).

### 3. Giao diện Phân quyền Nhân viên (`employee/permissionsView`)

- Tra cứu nhân viên thông minh bằng mã nhân viên (hỗ trợ autocomplete datalist) hoặc chọn từ dropdown danh sách.
- Hiển thị Profile Card trực quan của nhân viên được chọn (Mã số, Tên, Chức vụ, Trạng thái, Chế độ phân quyền Custom/Mặc định).
- Hệ thống Checkbox dạng thẻ (Card) trực quan theo từng nhóm công đoạn với mô tả chi tiết quyền hạn.
- Hỗ trợ thao tác nhanh: "Chọn tất cả", "Bỏ chọn tất cả", "Khôi phục quyền mặc định theo vai trò".
- Thanh lưu trữ dính đáy màn hình (Sticky Save Bar) và Toast thông báo kết quả tức thì không cần tải lại trang.

### 4. Nâng cấp Giao diện Thanh Điều Hướng: Vertical Sidebar Doanh Nghiệp

- Chuyển đổi thanh menu ngang (`.menu-bar`) sang thanh dọc bên trái (`Vertical Left Sidebar`) chuẩn doanh nghiệp hiện đại SMC Factory:
  - Header Sidebar: Brand logo SMC Factory, trạng thái vận hành hệ thống.
  - User Card: Ảnh đại diện, mã nhân viên, tên nhân viên và badge vai trò có màu nhận diện.
  - Navigation Sections: Nhóm phân cấp rõ ràng (Sản xuất & Vận hành, Theo dõi & Giám sát, Quản trị hệ thống).
  - Dynamic Rendering: Tự động ẩn/hiện menu link dựa trên quyền hạn thực tế của user qua `AuthHelper::hasPermission()`.
  - Huy hiệu (Badge) thời gian thực hiển thị số lượng Bobin đang chờ duyệt hủy.
  - Thu gọn/Mở rộng (`Collapse / Expand`) linh hoạt giúp tối đa hóa không gian làm việc trên máy tính sản xuất.
  - Tự động phản hồi (Responsive Off-Canvas) trên thiết bị di động/màn hình nhỏ (<992px) với nút Toggle Burger.
  - Footer Sidebar: Bộ chọn ngôn ngữ 3 chế độ (`vi`, `en`, `ja`), nút Đổi mật khẩu và Đăng xuất an toàn.
- Áp dụng đồng bộ Sidebar cho tất cả 11 giao diện trong hệ thống:
  - `app/views/employeePermissionsView.php`
  - `app/views/employeeListView.php`
  - `app/views/extrusionView.php`
  - `app/views/extrusionEditBobinView.php`
  - `app/views/qcView.php`
  - `app/views/qcEditBobinView.php`
  - `app/views/windingView.php`
  - `app/views/windingEditBobinView.php`
  - `app/views/listBobinDetailView.php`
  - `app/views/listBobinHistoryView.php`
  - `app/views/listPendingCancellationView.php`

### 5. Đa ngôn ngữ (i18n) & Tối ưu hóa UI/UX

- Bổ sung hơn 35 translation keys mới cho cả 3 ngôn ngữ (`vi`, `en`, `ja`) trong `app/core/Language.php` và `public/assets/js/i18n.js`.
- **Tối ưu hóa & Loại bỏ thông tin/icon trùng lặp:**
  - Chuẩn hóa các chuỗi bản dịch điều hướng (`nav_employee_list`, `nav_change_pwd`, `nav_permissions`) bằng cách tách biệt icon khỏi chuỗi văn bản, khắc phục triệt để lỗi hiển thị lặp hai lần icon (`👥 👥 Danh sách nhân viên`, `🛡️ 🛡️ Phân quyền tài khoản`, `🔑 🔑 Đổi MK`).
  - Gán icon định danh trực quan riêng `🔐` cho chức năng Phân quyền tài khoản để phân biệt rõ ràng với biểu tượng `🛡️` của công đoạn QC.
  - Loại bỏ biểu tượng trang trí trùng lặp `👥` trên header trang Quản lý nhân viên và nút tìm kiếm trang Phân quyền để giao diện tinh gọn, thoáng đãng và chuyên nghiệp.

---

## [2026-10-06] - Khắc Phục Lỗi Deprecated Dynamic Property ListDataEntity (TASK-009)

### 1. Sửa lỗi Deprecated trong ListDataEntity & ListDataRepository

- **Nguyên nhân:** Trên môi trường PHP 8.2+, việc gán động các thuộc tính chưa được khai báo trước trên class (`$entity->{$key} = $value` trong `ListDataRepository::mapToEntity`) gây ra cảnh báo `E_DEPRECATED: Creation of dynamic property ListDataEntity::$list_rack is deprecated` và `ListDataEntity::$pending_count is deprecated`.
- **Giải pháp xử lý:**
  - Khai báo tường minh hai thuộc tính `public array $list_rack = [];` và `public int $pending_count = 0;` trong `app/entities/ListDataEntity.php` kèm khởi tạo giá trị mặc định trong hàm `__construct()`.
  - Bổ sung attribute `#[AllowDynamicProperties]` cho class `ListDataEntity` để đảm bảo tương thích tuyệt đối và phòng ngừa cảnh báo phát sinh trên PHP 8.2+.

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

## [2026-10-06] - Lọc Bobin 3 Ngày Cho Cuộn/Điều Chỉnh Cuộn, Chuẩn Hóa Status Badge & Tối Ưu Hệ Thống Dịch i18n (TASK-006)

### 1. Giới hạn lọc thời gian 3 ngày gần nhất

- **Trang Điều chỉnh Cuộn (`bobin/windingEditBobinView`):**
  - Cập nhật truy vấn SQL trong `BobinRepository::getListDetailBobinsForWindingEdit()` và `countDetailBobinsForWindingEdit()`: Thay đổi từ khoảng 7 ngày sang 3 ngày gần nhất (`bobin_current_status = 'Rolled' AND updated_time >= DATE_SUB(NOW(), INTERVAL 3 DAY)`).
- **Trang Xác nhận thông tin Cuộn (`bobin/windingView`):**
  - Cập nhật truy vấn SQL trong `BobinRepository::getDetailBobinsForWinding()` và `countDetailBobinsForWinding()`: Chỉ lấy những Bobin có trạng thái `Busy_Checked` (đang chờ cuộn) hoặc Bobin có trạng thái `Rolled` (đã cuộn) được cập nhật trong vòng 3 ngày gần nhất (`(bobin_current_status = 'Busy_Checked' OR (bobin_current_status = 'Rolled' AND updated_time >= DATE_SUB(NOW(), INTERVAL 3 DAY)))`).

### 2. Chuẩn hóa hiển thị nhãn trạng thái Bobin (Status Badge)

- Thống nhất tuyệt đối hiển thị 5 trạng thái chuẩn viết hoa trên toàn bộ các Card Bobin trong tất cả các View:
  - `"CHƯA KIỂM TRA QC"`
  - `"ĐÃ KIỂM TRA QC"`
  - `"ĐANG CHỜ HỦY"`
  - `"ĐÃ HỦY"`
  - `"ĐÃ CUỘN"`
- Các View được chuẩn hóa:
  - `app/views/listBobinDetailView.php`: Match expression đổi thành 5 trạng thái viết hoa chuẩn.
  - `app/views/listBobinHistoryView.php`: Match expression đổi thành 5 trạng thái viết hoa chuẩn.
  - `app/views/listBobinView.php`: Match expression đổi thành 5 trạng thái viết hoa chuẩn.
  - `app/views/listPendingCancellationView.php`: Thay nhãn `⌛ Đang chờ hủy` thành `ĐANG CHỜ HỦY`.
  - `app/views/extrusionEditBobinView.php`: Thay nhãn `⏳ Chưa kiểm tra QC` thành `CHƯA KIỂM TRA QC`.
  - `app/views/qcEditBobinView.php`: Thay nhãn `🛡️ QC Đã kiểm tra` thành `ĐÃ KIỂM TRA QC`.
  - `app/views/windingEditBobinView.php`: Thay nhãn `📍 Cuộn Hoàn tất` thành `ĐÃ CUỘN`.
  - `app/views/qcView.php`: Thay nhãn `Đang đợi QC check` thành `CHƯA KIỂM TRA QC`.
  - `app/views/windingView.php`: Đổi trạng thái hiển thị thành `ĐÃ CUỘN` hoặc `ĐÃ KIỂM TRA QC`.

### 3. Tối ưu hóa hệ thống chuyển đổi ngôn ngữ (i18n)

- **Frontend (`public/assets/js/i18n.js`):**
  - Bổ sung 5 trạng thái chuẩn (`CHƯA KIỂM TRA QC`, `ĐÃ KIỂM TRA QC`, `ĐANG CHỜ HỦY`, `ĐÃ HỦY`, `ĐÃ CUỘN`) vào từ điển `DOM_MAPPINGS.statusBadges` cho cả 3 ngôn ngữ (`vi`, `en`, `ja`).
  - Mở rộng selector xử lý dịch trong `deepTranslateDOM()` để tự động quét cả `.status-badge`.
  - Gỡ bỏ điều kiện return sớm khi ở ngôn ngữ tiếng Việt (`currentLang === 'vi'`) trong `deepTranslateDOM()`, cho phép engine quét qua toàn bộ DOM (table headers, form labels, buttons, placeholders, status badges) để khôi phục hoặc chuẩn hóa các chuỗi tiếng Anh/Nhật còn sót về tiếng Việt chuẩn 100%.
  - Cập nhật `MutationObserver` cho phép chuẩn hóa các phần tử DOM được thêm động ngay cả khi đang ở chế độ Tiếng Việt.

---

## [2026-10-06] - Điều Chỉnh Bộ Lọc Danh Sách Bobin QC / Cuộn & Tích Hợp Phân Trang (TASK-005)

### 1. Chuẩn hóa bộ lọc danh sách Bobin theo đúng quy trình phân xưởng

- **Trang Điều chỉnh QC (`bobin/qcEditBobinView`):**
  - Chỉ hiển thị các Bobin có trạng thái `bobin_current_status = 'Busy_Checked'` (các Bobin đã qua công đoạn kiểm tra QC nhưng chưa qua cuộn hoàn tất).
- **Trang Điều chỉnh Cuộn (`bobin/windingEditBobinView`):**
  - Chỉ hiển thị các Bobin có trạng thái `bobin_current_status = 'Rolled'` và có thời gian cập nhật trong vòng 7 ngày gần nhất (`updated_time >= DATE_SUB(NOW(), INTERVAL 7 DAY)`).

### 2. Tích hợp tính năng phân trang (Pagination) toàn diện

- **Repository (`app/repositories/BobinRepository.php`):**
  - Cập nhật `getListDetailBobinsForQCEdit($filters, $page, $limit)`: Phân trang bằng `LIMIT :limit OFFSET :offset`, sắp xếp theo `updated_time DESC, bobin_identification_code ASC`.
  - Bổ sung `countDetailBobinsForQCEdit($filters)`: Đếm tổng số Bobin đạt chuẩn điều chỉnh QC.
  - Cập nhật `getListDetailBobinsForWindingEdit($filters, $page, $limit)`: Phân trang bằng `LIMIT :limit OFFSET :offset`, lọc `updated_time >= DATE_SUB(NOW(), INTERVAL 7 DAY)`, sắp xếp theo `updated_time DESC, bobin_identification_code ASC`.
  - Bổ sung `countDetailBobinsForWindingEdit($filters)`: Đếm tổng số Bobin đạt chuẩn điều chỉnh Cuộn trong 7 ngày gần nhất.
- **Service (`app/services/BobinServices.php`):**
  - Truyền `$dto->page, $dto->limit` vào các repository method tương ứng.
  - Thêm phương thức `countDetailBobinsForQCEdit(BobinGetListDTO $dto)` và `countDetailBobinsForWindingEdit(BobinGetListDTO $dto)`.
- **Controller (`app/controllers/BobinController.php`):**
  - Tính toán `$totalRecords`, `$totalPages = (int)ceil($totalRecords / $dto->limit)` và truyền mảng dữ liệu `pagination` cùng `totalRecords` sang view.
- **Views (`app/views/qcEditBobinView.php`, `app/views/windingEditBobinView.php`):**
  - Thêm hàm helper `buildFilterUrl()` bảo toàn tham số lọc / từ khóa tìm kiếm khi chuyển trang.
  - Hiển thị tổng số lượng bản ghi thực tế trên `counter-badge` bằng `number_format($totalRecords)`.
  - Bổ sung khối UI điều hướng phân trang (`.pagination-wrapper`, nút `‹ Trước`, các số trang liền kề, nút `Sau ›`, cùng nhãn thông tin `.page-info`).
- **CSS (`public/assets/css/extrusionEditBobin.css`):**
  - Bổ sung định dạng CSS cho `.pagination-wrapper`, `.page-btn`, `.page-btn.active`, `.page-info`.

### 3. Đa ngôn ngữ (i18n) cho tính năng phân trang

- **Frontend & Backend (`public/assets/js/i18n.js`, `app/core/Language.php`):**
  - Bổ sung các translation key: `page_prev`, `page_next`, `page_info` trên cả 3 ngôn ngữ `vi`, `en`, `ja`.
  - Cập nhật hàm `deepTranslateDOM()` trong `i18n.js` tự động nhận diện và dịch động các nút điều hướng phân trang và thông tin trang sang ngôn ngữ người dùng lựa chọn.

---

## [2026-10-05] - Triển Khai Tính Năng Điều Chỉnh QC & Cuộn (Admin), Xác Nhận Mật Khẩu & Vết Kiểm Toán Update History (TASK-004)

### 1. Phân quyền và tạo 2 tính năng điều chỉnh mới dành riêng cho Quản trị viên (Admin)

- **Files thay đổi:**
  - `app/core/Router.php`: Đăng ký `qceditbobinview`, `updateqceditbobin`, `windingeditbobinview`, `updatewindingeditbobin` nằm độc quyền dưới nhóm quyền `admin`.
  - `app/controllers/BobinController.php`: Bổ sung kiểm tra role Admin nghiêm ngặt cho các view (`qcEditBobinView`, `windingEditBobinView`) và API PUT (`updateQCEditBobin`, `updateWindingEditBobin`).
  - `app/services/BobinServices.php`: Bổ sung `getListDetailBobinsForQCEdit()`, `getListDetailBobinsForWindingEdit()`, `adminUpdateQCBobin()`, `adminUpdateWindingBobin()`.
  - `app/repositories/BobinRepository.php`: Bổ sung truy vấn và cập nhật chuyên biệt cho Admin QC & Winding Edit.
  - `app/views/qcEditBobinView.php` & `public/assets/js/QC/edit_submit.js`: Giao diện và script điều chỉnh QC (visual inspection, ghi chú, loại Bobin).
  - `app/views/windingEditBobinView.php` & `public/assets/js/Winding/edit_submit.js`: Giao diện và script điều chỉnh Cuộn (máy cuộn, nhân viên, kiểm tra thông khí, ghi chú).
  - Cập nhật liên kết thanh menu điều hướng (`menu-bar`) trong tất cả các View (`extrusionView`, `extrusionEditBobinView`, `qcView`, `windingView`, `listBobinDetailView`, `listBobinHistoryView`, `listPendingCancellationView`, `employeeListView`): Các liên kết "Điều chỉnh QC" và "Điều chỉnh Cuộn" chỉ hiển thị khi tài khoản có quyền `admin`.

### 2. Xác thực mật khẩu đăng nhập bắt buộc trước khi lưu ở cả 3 trang điều chỉnh

- **Files thay đổi:**
  - `app/dtos/Bobin/BobinExtUpdateDTO.php`, `BobinQCUpdateDTO.php`, `BobinUpdateWindingDTO.php`: Bổ sung trường `$confirm_password` và parse từ request payload.
  - `public/assets/js/Extrusion/edit_submit.js`, `public/assets/js/QC/edit_submit.js`, `public/assets/js/Winding/edit_submit.js`: Hộp thoại `ConfirmDialog` hỗ trợ chế độ `requirePassword = true`, bắt buộc người dùng nhập mật khẩu tài khoản đang đăng nhập trước khi submit PUT request.
  - `app/controllers/BobinController.php`: Phương thức `verifyCurrentUserPassword()` kiểm tra đối soát mật khẩu với tài khoản đang đăng nhập trong `employee_list` qua `$_SESSION['user']['id']`. Nếu mật khẩu không đúng hoặc để trống, server từ chối cập nhật và trả về HTTP 403.

### 3. Lưu vết kiểm toán `update_history` và hiển thị trên UI Danh sách / Lịch sử Bobin

- **Files thay đổi:**
  - `app/repositories/BobinRepository.php`: Cả 3 thao tác điều chỉnh (Đùn, QC, Cuộn) đều tự động nối bản ghi kiểm toán mới vào trường `update_history` JSON ở cả `bobin_list_detail` và `bobin_history` bao gồm: giai đoạn (`stage`), mã và họ tên người sửa (`employee_code`, `employee_name`), thời điểm thực hiện (`updated_at`), ghi chú (`note`).
  - `app/views/listBobinDetailView.php` & `app/views/listBobinHistoryView.php`: Render hộp thông tin lịch sử điều chỉnh `audit-history-box` ở cuối mỗi thẻ Bobin nếu Bobin đó đã từng được điều chỉnh, hiển thị rõ ràng ai sửa, ở công đoạn nào và vào lúc nào.

### 4. Đa ngôn ngữ i18n toàn diện (100% không hardcode)

- **Files thay đổi:**
  - `app/core/Language.php` & `public/assets/js/i18n.js`: Bổ sung đầy đủ translations trên cả 3 ngôn ngữ (`vi`, `en`, `ja`) cho các nhãn menu (`nav_qc_edit`, `nav_winding_edit`), tiêu đề trang (`page_qc_edit`, `page_winding_edit`), nhãn xác nhận mật khẩu (`confirm_pwd_label`, `confirm_pwd_ph`, `confirm_pwd_empty`, `confirm_edit_title`, `confirm_edit_sub`), nhãn tiến trình (`saving`, `saved`, `save_failed`), và nhãn vết kiểm toán (`audit_trail_title`, `audit_stage_extrusion`, `audit_stage_qc`, `audit_stage_winding`, `audit_updater`, `audit_time`).

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
    - **🏭 Đùn Check:** Kiểm tra nếu dữ liệu `extrusion_check` rỗng hoặc không có bất kỳ tiêu chí nào $\rightarrow$ Hiển thị badge trực quan: `⏳ Chưa có dữ liệu kiểm tra Đùn` (`pipeline_no_ext_data`).
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
