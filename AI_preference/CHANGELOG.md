# CHANGELOG.md - Nhật Ký Những Thay Đổi Quan Trọng

Toàn bộ các cập nhật lớn, sửa lỗi logic, tái cấu trúc mã nguồn và cải tiến UX/UI được ghi nhận tuần tự theo thời gian tại đây.

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