# CHANGELOG.md - Nhật Ký Những Thay Đổi Quan Trọng

Toàn bộ các cập nhật lớn, sửa lỗi logic, tái cấu trúc mã nguồn và cải tiến UX/UI được ghi nhận tuần tự theo thời gian tại đây.

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