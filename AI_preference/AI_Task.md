# AI TASK - WEB_BOBIN

> File này chứa các yêu cầu hiện tại cần Antigravity thực hiện.
> Luôn đọc file này trước khi bắt đầu một task mới.
> CURRENT TASK: TASK PHẢI THỰC HIỆN NGAY BÂY GIỜ.
> TODO: TASK PHẢI THỰC HIỆN, NẾU KHÔNG CÓ TODO THÌ KHÔNG LÀM
> DONE: TASK ĐÃ HOÀN THÀNH VÀ KHÔNG THỰC HIỆN NỮA
> Promt:Đọc và thực hiện AI_preference/prompts.md

---

## 🔴 CURRENT TASK

### TASK-001

**Status:** DONE

- Hãy kiểm tra lại toàn bộ project để đảm bảo các chức năng không cần internet vẫn sử dụng được. Lưu ý rằng tôi chỉ sử dụng mạng local, không truy cập được các trang web khác hay đường dẫn khác ngoài project này

### TASK-002

**Status:** DONE

- Hãy thực hiện điều tra toàn diện chức năng chuyển đổi ngôn ngữ và đồng bộ tất cả, đảm bảo các logic không ảnh hưởng.

### TASK-003

**Status:** DONE

- Trong quá trình sử dụng web, tôi đổi ngôn ngữ thì web bị đứng và không thể sử dụng được bất kì chức năng, thậm chí nhấn nút, hãy kiểm tra lại, báo cáo tôi nguyên nhân nêu lên cách khắc phục và tối ưu không bị lỗi đó nữa

---

### TASK-004

**Status:** DONE

- Tôi muốn thêm 2 chức năng Điều chỉnh QC và Điều chỉnh Cuộn, tuy nhiên các tính năng này chỉ có những người thuộc nhóm admin mới có thể thực hiện được.
- Ngoài ra, tôi muốn ở cả 3 trang ĐIỀU CHỈNH ĐÙN, ĐIỀU CHỈNH QC và ĐIỀU CHỈNH CUỘN, trước khi điều chỉnh thông tin Bobin, người dùng phải xác nhận lại lần cuối bằng mật khẩu đăng nhập của chính mình. Việc này sẽ tránh đi trường hợp người A cố tình thay đổi thông tin Bobin được nhập bởi người B mà không có sự đồng thuận từ người B.
- Để chặt chẽ hơn, tôi nghĩ ở database bobin_history nên có thêm trường dữ liệu: Danh sách người cập nhật thông tin Bobin cũng như thời điểm cập nhật thông tin đó để có thể theo dõi được rằng, ai là người đã thay đổi thông tin của Bobin đó theo từng giai đoạn

---

### TASK-005

**Status:** DONE

- Có sai lầm ở TASK bạn vừa hoàn thành trước đó, và giờ bạn phải fix như sau:

* Danh sách Bobin được hiển thị ở trang Điều chỉnh QC phải là những Bobin có status là "Busy_Checked"
* Danh sách Bobin được hiển thị ở trang điều chỉnh Cuộn phải là những Bobin có status là "Rolled", tuy nhiên chỉ hiển thị những Bobin có updated time trong 7 ngày gần nhất
  Cả 2 trang trên đều phải có thêm tính năng phân trang để tránh quá tải hiển thị và quá tải dữ liệu khi đặt vào cùng 1 trang.

### TASK-006

**Status:** DONE

- Danh sách Bobin được hiển thị ở trang điều chỉnh Cuộn phải là những Bobin có status là "Rolled", tuy nhiên chỉ hiển thị những Bobin có updated time trong 3 ngày gần nhất
- Danh sách Bobin được hiển thị ở trang Xác nhận thông tin cuộn phải là những Bobin có status là "Busy_Checked" và "Rolled", tuy nhiên đối với Bobin status là "Rolled" thì chỉ hiển thị những Bobin có updated time trong 3 ngày gần nhất
- Kiểm tra là chức năng translation, hiện tại tôi thấy khi đang chế độ tiếng Việt nhưng có rất nhiều từ đang để là tiếng Anh
- Các hiển thị trên từng card Bobin đều giữ nguyên là: "CHƯA KIỂM TRA QC", "ĐÃ KIỂM TRA QC", "ĐANG CHỜ HỦY", "ĐÃ HỦY", "ĐÃ CUỘN"

### TASK-007

**Status:** DONE

- Nâng cao UI/UX ở các trang điều chỉnh để nổi bật và chuyên nghiệp hơn
- Ở trang lịch sử, bỏ đi các nút nhấn "Hôm nay", "Hôm qua", "7 ngày", "1 tháng"
- Chỉ có admin mới có thể truy cập vào trang điều chỉnh Cuộn
- Nhân viên QC có thể truy cập vào trang điều chỉnh QC
- Nhân viên Đùn có thể truy cập vào trang điều chỉnh Đùn
- Tôi thấy bạn có thêm trường dữ liệu update_history. Lưu ý rằng, mục đích trường này được tạo ra là: Mỗi lần giá trị các trường dữ liệu Bobin (độc nhất theo bobin_key_code) thay đổi xuất phát từ các trang: Điều chỉnh đùn, điều chỉnh QC, điều chỉnh cuộn thì sẽ hiển thị người thay đổi dữ liệu cũng như thời gian thay đổi.

### TASK-008

**Status:** DONE

- Nâng cao UI cho trang Điều chỉnh đùn, điều chỉnh cuộn, điều chỉnh QC cho đẹp lên, nổi bật lên, chuyên nghiệp lên
- trường dữ liệu update_history chỉ cần hiển thị ra ở trang lịch sử bobin, các trang khác không cần hiển thị

### TASK-009

**Status:** DONE

Xuất hiện các lỗi dưới đây, hãy fix

- Deprecated: Creation of dynamic property ListDataEntity::$list_rack is deprecated in C:\xampp\htdocs\WEB_BOBIN\app\repositories\ListDataRepository.php on line 99
- Deprecated: Creation of dynamic property ListDataEntity::$pending_count is deprecated in C:\xampp\htdocs\WEB_BOBIN\app\repositories\ListDataRepository.php on line 99

### TASK-010

**Status:** DONE
Hãy nâng cấp dự án lên một tầm cao mới, chuyên nghiệp hơn

- Thêm 1 trang phân quyền, tại đây các admin sẽ có thể điều chỉnh các quyền thao tác đối với từng account user.
- Để triển khai ý trên, bạn có quyền update database cho trường dữ liệu employee_list
  Cách sử dụng trang này như sau:
  Admin tra cứu account của nhân viên bằng mã số nhân viên => Hiển thị ra thông tin cơ bản của nhân viên và các hạng mục mà nhân viên đó có quyền thao tác thuộc dạng checkbox, nếu có check thì là có quyền, nếu không thì là không có quyền.
- Hãy nâng cấp menubar, bạn có thể thay vì thanh ngang ở phía trên, hãy làm cột dọc bên trái
- Đảm bảo UI/UX phải thật sự đẹp và chuyên nghiệp

### TASK-011

**Status:** DONE

- Hãy thống nhất lại cùng 1 font family chữ cho toàn bộ các trang, các chức năng trong dự án.
- Bạn có nắm được quyền hiện tại của các account không thông qua thiết kế ban đầu trước đó không?

### TASK-012

**Status:** DONE

- Hãy tạo 1 trang, mà ở đó tôi có thể nhập textfield để quy định chuyển đổi giữa các ngôn ngữ mà không bị fix cứng như hiện tại

### TASK-013

**Status:** DONE

- Thiết kế lại giao diện thông minh với đẩy đủ SlideBar, Menubar và Header Bar sao cho hợp lý
- Nếu những người không có permission truy cập vào trang nào, thì tại giao diện màn hình của account đó sẽ không thấy các nút nhấn điều hướng đến trang không có permission.

### TASK-014

**Status:** DONE

- Thiết kế lại giao diện thông minh với đẩy đủ SlideBar, Menubar và Header Bar sao cho hợp lý và chuyên nghiệp, vì khi dồn tất cả các chức năng vào trung một bar dạng dọc thì không đúng, vì các chức năng này có vẻ như đang rời rạc
  => Ý tưởng: Thiết kế theo dạng Folder Tree / Breadcrumb

### TASK-015

**Status:** DONE

- Với thiết kế hiện tại, header thật không có ý nghĩa, chỉ mang tính hiển thị
  => THiết kế lại HEADER, phần ngôn ngữ, đổi mật khẩu và đăng xuất nên để ở góc phải của Header
- Thanh slider bar chưa được tối ưu, nếu mở folder dưới thì các folder trên bị chèn chữ gây hỏng giao diện => Chỉnh sửa ngay
- Cần phải tối ưu source code của dự án lại sao cho dễ dàng bảo trì và dễ hiểu, có cấu trúc rõ ràn

### TASK-016

**Status:** DONE

- Kiểm tra lại giao diện để WEB đảm bảo responsive trên cả android, ios, tablet,.. Vì hiện tại tôi thấy giao diện hiển thị trên các thiết bị trên không được đều bố cục, tràn layout và khó thao tác

### TASK-017

**Status:** DONE

- Khắc phục cơ chế truy xuất ngôn ngữ động từ trang cấu hình đa ngôn ngữ (custom_translations.json), tự động đồng bộ tức thì các từ khóa tùy biến (như btn_confirm, btn_cancel) lên toàn bộ UI/DOM (nút bấm, modal, form) mà không phụ thuộc vào việc fix cứng trong i18n.js hay load trễ script.
### TASK-018
**Status:** DONE
- Nâng cấp database `employee_list` của danh sách nhân viên để dễ bảo trì và cập nhật dữ liệu bằng excel:
  - Bỏ đi trường dữ liệu `is_active` vì không cần thiết.
  - Thêm trường `cost_center` (Mã bộ phận).
  - Cập nhật database schema chuẩn với PRIMARY KEY AUTO_INCREMENT và UNIQUE KEY.
- Trong trang danh sách nhân viên:
  - Xóa filter theo `role`, thay bằng filter theo `cost_center`.
  - Bỏ cột trạng thái `is_active`, hiển thị cột `cost_center` (Mã bộ phận) rõ ràng.
  - Cập nhật modal Thêm mới, modal Sửa nhân viên, mẫu Excel/CSV import và chức năng export/import Excel theo cấu trúc mới có `cost_center` và không có `is_active`.
  - Tối ưu đồng bộ trang Danh sách nhân viên (Stats, Biểu đồ phân bổ bộ phận/vai trò, tìm kiếm, i18n 3 ngôn ngữ).

Cấu trúc bảng `employee_list`:
```sql
CREATE TABLE `employee_list` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `employee_code` varchar(50) NOT NULL UNIQUE COMMENT 'Mã nhân viên',
  `employee_name` varchar(100) NOT NULL COMMENT 'Họ và tên nhân viên',
  `cost_center` varchar(50) DEFAULT NULL COMMENT 'Mã bộ phận',
  `role` enum('extrusion','qc','winding','admin') NOT NULL DEFAULT 'extrusion' COMMENT 'Vai trò phân quyền',
  `username` varchar(50) NOT NULL UNIQUE COMMENT 'Tên đăng nhập',
  `password` varchar(255) NOT NULL COMMENT 'Mật khẩu mã hóa password_hash',
  `is_first_login` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1: Chưa đổi mật khẩu lần đầu, 0: Đã đổi mật khẩu',
  `permissions` longtext DEFAULT NULL COMMENT 'Mảng JSON lưu danh sách mã quyền thao tác tùy biến (nếu NULL thì theo Role mặc định)',
  `updated_time` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  KEY `idx_role` (`role`),
  KEY `idx_cost_center` (`cost_center`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```
### TASK-019
**Status:** DONE
- Update đồng bộ ngôn ngữ lại ở trang phân quyền
- Đối với thanh Menu, vẫn duy trì bố cục folder tree, tuy nhiên những mục nhỏ cần xê dịch vào trong 1 chút so với từng thư mục lớn tương ứng.
- Truy cập C:\xampp\htdocs\WEB_BOBIN\public\assets\sql\Updated\production_db_Architect_sql.sql để tiến hành đồng bộ là kiến trúc SQL
- Nâng cấp giao diện ở trang điều chỉnh cuộn và điều chỉnh QC, hiện đang thiết kế rất tẻ nhạt

### TASK-020
**Status:** DONE
- Đồng bộ giao diện Điều chỉnh Đùn (extrusionEditBobinView.php) tương ứng với điều chỉnh QC và điều chỉnh Cuộn:
  - Thiết kế Hero Header, 4 thẻ thống kê KPI theo chuẩn Dashboard.
  - Phân vùng cấu trúc thẻ Bobin thành 3 khối chuyên biệt (Quy cách sản phẩm, Thiết bị & Vật liệu, Nhân sự & Thời gian) và thanh kiểm tra 5 tiêu chuẩn Đùn Check.
  - Bảo toàn 100% các class selector, autocomplete suggestion, quét mã QR và xác thực mật khẩu cá nhân khi cập nhật.
  - Kiểm tra và đồng bộ hóa đa ngôn ngữ 100% cho cả 3 ngôn ngữ (vi, en, ja) trong Language.php và i18n.js, khắc phục toàn bộ các text hardcode và thiếu key ở cả 3 trang điều chỉnh (Đùn, QC, Cuộn).

### TASK-021
**Status:** DONE
- Kiểm tra lại toàn diện các textfield, input placeholder đang không hiển thị đúng ngôn ngữ:
  - Phân tích và khắc phục triệt để lỗi phân giải tiền tố `[placeholder]` trong `applyDataI18n()` của `public/assets/js/i18n.js`.
  - Mở rộng cơ chế bắt thuộc tính đa dạng: `[data-i18n-ph]`, `[data-placeholder-i18n]`, `[data-i18n-placeholder]`, cú pháp tiền tố `[attribute]key` của `[data-i18n]`.
  - Nâng cấp Section E của `deepTranslateDOM()` trong `i18n.js` với đầy đủ pattern nhận diện và dịch dự phòng cho tất cả các input placeholder trên toàn hệ thống.
  - Bổ sung 12 translation keys chuyên dụng cho placeholder ở cả 3 ngôn ngữ (`vi`, `en`, `ja`) trong `app/core/Language.php` và `public/assets/js/i18n.js`.
  - Đồng bộ hóa 100% tất cả các placeholder và textfield tại 18 file view của dự án: chuyển các placeholder hardcode thành helper PHP `<?= __('key') ?>` và gắn `data-i18n-ph="key"`.

### TASK-TOAST
**Status:** DONE
- Kiểm tra toàn diện và khắc phục triệt để hệ thống thông báo Toast (Hiển thị khi lỗi thao tác người dùng hoặc do hệ thống, hiển thị khi thực hiện thành công tác vụ):
  - Xây dựng module Toast toàn cục độc lập `public/assets/js/toast.js` (100% Vanilla JS & CSS, hoàn toàn Offline Intranet, không dùng CDN).
  - Khắc phục lỗi thiếu CSS `.toast-message` ở các trang Điều chỉnh (Đùn, QC, Cuộn) khiến Toast trước đây bị ẩn/không hiển thị.
  - Tích hợp cơ chế Flash Toast qua `sessionStorage` và URL query params, giúp thông báo thành công hiển thị mượt mà xuyên suốt sau khi reload trang mà không bị mất.
  - Thay thế toàn bộ các hàm `alert()` trình duyệt bằng Toast cảnh báo và lỗi hiện đại, thân thiện.
  - Bổ sung 19 translation keys cho Toast ở cả 3 ngôn ngữ (`vi`, `en`, `ja`) trong `Language.php` và `i18n.js`.
  - Nạp Toast và đồng bộ hóa trên 100% các file view (18/18) và các module submit, edit, delete, utils, scan QR.

### TASK-022
**Status:** DONE
- Làm hiển thị nổi bật 4 thông tin cốt lõi của Bobin trên toàn bộ các trang Danh sách, Lịch sử Bobin, trang QC và trang Cuộn:
  1. **Mã sản phẩm:** Badge nổi bật xanh dương (`.bobin-highlight-product`), bo góc, viền xanh nhạt, bóng nhẹ, font đậm rõ nét.
  2. **Vị trí Rack (nếu có):** Badge vị trí xanh ngọc (`.bobin-highlight-rack`), hiển thị icon ghim `📍 [Mã Rack]` khi có dữ liệu, hiển thị placeholder mờ `---` (`.rack-empty`) khi chưa gán Rack.
  3. **Loại Bobin:** Phân loại màu sắc chuyên biệt theo 3 gam màu chuẩn:
     - **Sản xuất:** Màu xanh lá đậm (`.type-san-xuat`, text `#14532d`, bg `#dcfce7`, border `#16a34a`).
     - **Bù:** Màu xanh dương (`.type-bu`, text `#1e40af`, bg `#dbeafe`, border `#3b82f6`).
     - **Các loại điều chỉnh:** Màu cam đậm (`.type-dieu-chinh`, text `#9a3412`, bg `#ffedd5`, border `#ea580c`), áp dụng cho tất cả các loại ngoại quan: `Điều chỉnh`, `Điều chỉnh (Do CP)`, `Gel`, `Dị vật`, `Trầy`, `Biến dạng`, `Xước`, `Chữ in`, `Vón cục`, `Màu`.
  4. **Lot in:** Badge mã in nổi bật Monospace tím indigo (`.bobin-highlight-printlot`), phân biệt rõ với trạng thái chưa cập nhật (`.lot-empty`).
- Khắc phục lỗi giá trị "Điều chỉnh..." của "Loại Bobin" tràn qua cột "Lot vật liệu":
  - **Nguyên nhân:** Trước đây `.bobin-type-badge` thiết lập `white-space: nowrap` và thiếu `max-width`, trong khi cột `.info-grid` chỉ có chiều rộng tối thiểu 110px. Đối với các tên loại dài như "Điều chỉnh (Do CP)" hoặc "Điều chỉnh...", badge bị tràn ngang 50px đè trực tiếp lên ô "Lot vật liệu" kế bên.
  - **Giải pháp:**
    - Cập nhật `.bobin-type-badge`: Cho phép `white-space: normal`, tự động xuống dòng linh hoạt (`word-break: break-word; overflow-wrap: anywhere; line-height: 1.25`), giới hạn `max-width: 100%`, `box-sizing: border-box`, căn giữa và tinh chỉnh padding `2.5px 7px`.
    - Thêm `min-width: 0; overflow: hidden;` cho `.field-item` và `.val-sub` để ngăn chặn grid item tự ý phình to hoặc tràn ra ngoài track của grid.
    - Nâng nhẹ chiều rộng tối thiểu cột `.info-grid` lên `minmax(125px, 1fr)` trên cả 5 stylesheet để các thông số có không gian hiển thị rộng rãi, cân đối.
    - Thêm thuộc tính `title="<?= htmlspecialchars($rawBobinType) ?>"` vào thẻ badge trên cả 5 view để hỗ trợ tooltip khi rê chuột.
- Điều tra và khắc phục triệt để lỗi nút "Hủy Bobin" bị hiển thị thành "Trở lại" tại trang QC và Cuộn:
  - **Nguyên nhân gốc rễ:** Người dùng tùy biến từ khóa `btn_cancel` trong `custom_translations.json` thành `"Trở lại"` (cho modal). Trong `i18n.js`, hàm `deepTranslateDOM()` duyệt qua nút bấm có text "🗑️ Hủy Bobin" chứa chữ "Hủy", dẫn tới khớp nhầm vào `btn_cancel` và bị ghi đè thành `"Trở lại"`.
  - **Giải pháp triệt để:**
    - Khởi tạo key dịch riêng `btn_cancel_bobin` ("Hủy Bobin" / "Cancel Bobin" / "ボビン廃棄") trong `Language.php` và `i18n.js`.
    - Đặt rule mapping `btn_cancel_bobin` đứng trước `btn_cancel` trong `DOM_MAPPINGS.buttons`.
    - Thêm điều kiện chặn khớp nhầm: nếu nút chứa `Bobin` hoặc `ボビン` thì bỏ qua `btn_cancel`.
    - Gắn `data-i18n="btn_cancel_bobin"` và helper PHP `<?= __('btn_cancel_bobin') ?>` trực tiếp cho nút bấm tại `qcView.php` và `windingView.php`.
- Tối ưu hóa UI/UX:
  - Bổ sung hiệu ứng chuyển động mượt mà (`transition: transform 0.15s ease, box-shadow 0.15s ease`) và hover elevation (`transform: translateY(-1px)`) trên cả 5 stylesheet.
  - Đồng bộ trên 5 Views: `listBobinDetailView.php`, `listBobinView.php`, `listBobinHistoryView.php`, `qcView.php`, `windingView.php`.

### TASK-023
**Status:** DONE
- Kiểm tra lại toàn diện chức năng scanQR và khắc phục triệt để các vấn đề giao diện quét mã QR:
  - Khắc phục lỗi nghiêm trọng Fatal error `Call to undefined function __()` tại `app/views/scanQR.php` khi truy cập trực tiếp bằng cách khởi tạo `Language::init()` an toàn và bổ sung action `scanQR` vào `BobinController.php` & `Router.php` (hỗ trợ cả URL `bobin/scanQR`).
  - Thiết kế lại toàn bộ giao diện quét mã QR theo Design System hiện đại, 100% Offline Local, tuân thủ nghiêm ngặt không tải thêm bất kỳ thư viện ngoài nào (sử dụng thư viện `html5-qrcode.min.js` đã có sẵn trong dự án).
  - Xây dựng module điều phối máy ảnh trung tâm `public/assets/js/qrScannerHelper.js` quản lý vòng đời camera chặt chẽ:
    - Mở camera ngay lập tức khi bấm nút Quét QR (không bắt người dùng phải bấm thêm nút "Request Permissions" hay "Start scanning" thô sơ của thư viện).
    - Tạo hiệu ứng khung ngắm Reticle 4 góc phát sáng và tia laser xanh quét lên xuống mượt mà (`qrLaserSweep`).
    - Hỗ trợ đổi camera trước/sau linh hoạt khi thiết bị có nhiều camera.
    - Hỗ trợ tính năng tải ảnh mã QR từ thiết bị (`scanFile`) dành cho máy tính không có webcam.
    - Đóng camera sạch sẽ (dừng toàn bộ MediaStream tracks) khi bấm đóng, giúp tắt đèn webcam ngay lập tức và tránh xung đột khi mở lại.
  - Xây dựng stylesheet độc lập `public/assets/css/scanQR.css` chuẩn hóa khung hình, tỉ lệ video, ẩn toàn bộ watermark bên ngoài, hỗ trợ responsive trên mọi thiết bị di động, tablet và desktop.
  - Bổ sung 11 translation keys cho QR Scanner trên cả 3 ngôn ngữ (`vi`, `en`, `ja`) trong `app/core/Language.php` và `public/assets/js/i18n.js`.
### TASK-024
**Status:** DONE
- Kiểm tra toàn diện và khắc phục triệt để hiện tượng quét 1 mã nhưng hiển thị 3 thông báo Toast xếp chồng, đồng thời màn hình camera quét mã bị chớp chớp nhấp nháy nhanh:
  - **Khắc phục 3 Toast xếp chồng:**
    - Phân tích nguyên nhân: Thư viện `html5-qrcode` quét liên tục 15 frame/giây (mỗi 66.6ms). Hàm `handleScanSuccess()` trước đây sử dụng `setTimeout(..., 250)` để đóng máy ảnh nhưng thiếu cờ khóa (`hasScanned`), dẫn đến trong 250ms chờ đó, các frame tiếp theo ở 66ms và 132ms tiếp tục kích hoạt callback nhận diện thành công, làm gọi hàm `window.Toast.show()` 3 lần liên tiếp.
    - Giải pháp: Bổ sung cờ chặn re-entry `hasScanned` vào `QRScannerHelper`, gọi ngay `this.scannerInstance.pause(true)` ngay khi quét trúng mã đầu tiên để ngắt chu kỳ giải mã frame và đóng băng video; đồng thời bổ sung cơ chế chống spam Toast trùng lặp trong 1.5 giây tại `public/assets/js/toast.js`.
  - **Khắc phục màn hình quét chớp chớp nháy nhanh:**
    - Phân tích nguyên nhân: Trong `scanQR.css` và 7 file stylesheet của các trang, selector `#qr-reader canvas` bị gán `display: block !important; width: 100% !important;`. Phần tử `<canvas id="qr-canvas">` vốn là vùng nhớ đệm offscreen ẩn (`display: none`) của thư viện để trích xuất frame, nhưng việc ép hiển thị nó vào trong flex container `#qr-video-viewport` khiến canvas và thẻ video tranh chấp layout 15 lần/giây; đồng thời cấu hình `disableFlip: false` liên tục lật ma trận 2D trên canvas gây ra hiện tượng chớp nhấp nháy dữ dội.
    - Giải pháp: Ẩn triệt để toàn bộ canvas nội bộ (`#qr-reader canvas, #reader canvas, #qr-video-viewport canvas, canvas#qr-canvas { display: none !important; ... }`), ẩn `#qr-shaded-region`, thiết lập `disableFlip: true`, chuẩn hóa video `height: 100% !important; object-fit: cover !important;`, loại bỏ selector canvas thừa tại cả 7 file stylesheet của các trang.
  - **Ràng buộc an toàn:** Tuân thủ 100% không liên quan đến tác vụ Astral (`astral.sh`), 100% Offline Intranet cục bộ, không tải thêm bất kỳ thư viện ngoài nào.

### TASK-025
**Status:** DONE
- Rà soát toàn diện dự án để đảm bảo 100% không sử dụng thư viện trên internet và không phụ thuộc bất kỳ tác vụ nào từ Astral (`astral.sh`):
  - **Rà soát thư viện ngoài qua Internet:**
    - Quét toàn bộ 18 file View (`app/views/`): 100% thẻ `<script>` và `<link rel="stylesheet">` đều nạp từ thư mục tài nguyên cục bộ nội bộ `/WEB_BOBIN/public/assets/...`, tuyệt đối không có CDN ngoài (như cdnjs, unpkg, jsdelivr, googleapis).
    - Quét toàn bộ Stylesheet (`public/assets/css/`): 100% sử dụng font chữ hệ thống (`system-ui`, `Segoe UI`), không có `@font-face` hoặc `@import` tải từ internet, icon dạng SVG nội suy data-uri.
    - Quét toàn bộ JavaScript (`public/assets/js/`): Mọi hàm `fetch()` và AJAX đều chỉ gọi API nội bộ trên cùng máy chủ (`/WEB_BOBIN/public/index.php?url=...`), không có bất kỳ request ra internet.
    - Đã triệt tiêu các URL tham chiếu tĩnh bên ngoài trong file `html5-qrcode.min.js` (chuyển các link `scanapp.org` và `github` về `#`), dọn dẹp các ghi chú đường dẫn cũ trong `contentLoaded.js`.
  - **Kiểm tra công cụ & thư viện Astral (`astral.sh`):**
    - Xác nhận toàn bộ dự án không có file cấu hình, mã nguồn hay thư viện nào liên quan đến Astral (như `uv`, `ruff`, `pyproject.toml`, `.ruff.toml`).
    - Dự án thuần PHP (Apache XAMPP + MySQL) và Vanilla JavaScript, hoàn toàn độc lập và an toàn 100% trong môi trường mạng LAN nội bộ cô lập.

### TASK-026
**Status:** DONE
- Khắc phục triệt để lỗi database: `SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry '0' for key 'PRIMARY'` phát sinh khi hủy Bobin từ trang Chỉnh sửa Đùn:
  - **Nguyên nhân gốc rễ (Root Cause):**
    - Bảng `bobin_history` có định nghĩa khóa chính `id INT(11) PRIMARY KEY` nhưng bị thiếu thuộc tính `AUTO_INCREMENT`.
    - Khi các câu lệnh INSERT vào `bobin_history` (bao gồm `extInsertBobinHistoryAfterDelete()` khi hủy Bobin, `createBobin()`, `extrusionUpdateBobin()`,...) không truyền trường `id`, MySQL tự động gán giá trị mặc định là `0`.
    - Lần ghi lịch sử đầu tiên tạo ra bản ghi có `id = 0`. Các lần hủy hoặc ghi nhận lịch sử tiếp theo tiếp tục cố gắng ghi nhận `id = 0`, dẫn đến xung đột khóa chính `Duplicate entry '0' for key 'PRIMARY'`.
    - Tình trạng thiếu `AUTO_INCREMENT` tương tự cũng tồn tại ở các bảng danh mục và bảng dữ liệu khác trong schema.
  - **Giải pháp xử lý:**
    - Di chuyển/cập nhật bản ghi `id = 0` hiện có trong `bobin_history`, `bobin_list_detail`, `bobin_list_general` lên ID dương hợp lệ.
    - Cập nhật cấu trúc bảng `bobin_history` và toàn bộ 12 bảng danh mục khác trong cơ sở dữ liệu `production_db` (bao gồm `bobin_list_detail`, `bobin_list_general`, `employee_list`, `day_list`, `extrusion_machine_list`, `material_list`, `material_lot_list`, `month_list`, `product_list`, `rack_list`, `winding_machine_list`, `year_list`) bổ sung thuộc tính `AUTO_INCREMENT` cho cột `id`.
    - Cập nhật hàm `extInsertBobinHistoryAfterDelete()` tại `app/repositories/BobinRepository.php` để lưu trữ đồng bộ cả hai trường `extrusion_check, rack` vào `bobin_history` khi thực hiện thao tác hủy Bobin.
    - Đồng bộ hóa định nghĩa schema trong file SQL `public/assets/sql/Updated/production_db_Architect_sql.sql` và dữ liệu mẫu `public/assets/sql/Updated/production_db_Data.sql`.
### TASK-027
**Status:** DONE
- Khắc phục lỗi trùng lặp bản ghi Bobin ở danh sách chờ hủy và hoàn thiện điều kiện kiểm tra ràng buộc khi hủy Bobin tại các công đoạn Đùn, Cuộn và QC:
  - **Vấn đề 1: Hủy Bobin tại trang Chỉnh sửa Đùn không còn bị duplicate trong danh sách chờ hủy:**
    - Nguyên nhân gốc rễ: Database trực tiếp thiếu ràng buộc duy nhất `uk_bobin_identification_code` và `uk_bobin_key_code` trên hai bảng `bobin_list_detail` và `bobin_list_general` (dù schema mẫu có khai báo), dẫn đến câu lệnh `INSERT ... ON DUPLICATE KEY UPDATE` khi tạo lại chu kỳ Bobin đã chèn bản ghi mới (`id > 3000`) thay vì ghi đè lên dòng hiện tại. Khi thực hiện hủy Bobin, câu truy vấn chỉ lọc theo `bobin_identification_code` khiến cả 2 bản ghi đều chuyển thành `Pending_Cancellation` và xuất hiện 2 thẻ Bobin ở danh sách chờ hủy.
    - Xử lý cơ sở dữ liệu: Gộp dữ liệu chu kỳ mới nhất từ các dòng trùng (`id > 3000`) về bản ghi gốc (`id <= 3000`), xóa các dòng trùng lặp, đưa tổng số dòng về chính xác 3000 bản ghi trên cả hai bảng. Thiết lập `UNIQUE KEY uk_bobin_identification_code` và `UNIQUE KEY uk_bobin_key_code` trên cả `bobin_list_detail` và `bobin_list_general`.
    - Xử lý mã nguồn: Bổ sung `bobin_key_code` vào `BobinExtDeleteDTO`, truyền từ giao diện `extrusionEditBobinView.php` qua `public/assets/js/Extrusion/edit_submit.js` tới `BobinServices.php`, và cập nhật `extDeleteBobinDetail()` / `extDeleteBobinGeneral()` trong `BobinRepository.php` lọc chính xác theo cả mã định danh lẫn `bobin_key_code`.
  - **Vấn đề 2: Hủy Bobin tại trang Cuộn bắt buộc đầy đủ 4 trường thông tin:**
    - Bổ sung `winding_employee_name` vào `BobinWindingCancelDTO`.
    - Cập nhật hàm kiểm tra backend `validFormWinding_Cancel()` trong `BobinController.php` bắt buộc 4 trường: Mã máy cuộn (`winding_machine`), Mã nhân viên (`winding_employee_code`), Họ tên nhân viên (`winding_employee_name`), và Ghi chú lý do hủy (`winding_note`).
    - Nâng cấp hàm frontend `handleWindingCancel()` tại `public/assets/js/Winding/submit.js` kiểm tra tuần tự 4 trường trước khi hiển thị hộp thoại xác nhận, tự động viền đỏ trường bị thiếu và hiển thị thông báo Toast cảnh báo đa ngôn ngữ tương ứng.
  - **Vấn đề 3: Hủy Bobin tại trang QC bắt buộc đầy đủ thông tin và có ít nhất 1 trường ngoại quan NG:**
    - Cập nhật hàm kiểm tra backend `validFormQC_Cancel()` trong `BobinController.php` bắt buộc: Mã nhân viên QC (`inspector_code`), Họ tên nhân viên QC (`inspector_name`), Ghi chú lý do hủy (`defect_note`), và kiểm tra logic ngoại quan: `$hasNG = $dto->defect_gel || $dto->defect_foreign_object || $dto->defect_color_issue || $dto->defect_print_quality` (phải có ít nhất 1 trường NG).
    - Cập nhật `app/views/qcView.php` bổ sung `data-key` cho switch ngoại quan.
    - Nâng cấp `public/assets/js/QC/submit.js`: sửa selector `.toggle-switch[data-defect]`, sửa lỗi đảo ngược trạng thái badge trong hộp thoại xác nhận (`hasDefect ? NG : OK`), bổ sung xác thực hợp lệ phía client cho mã nhân viên, tên nhân viên, ghi chú và bắt buộc tối thiểu 1 trường NG trước khi mở dialog xác nhận.
  - **Đa ngôn ngữ & Kiểm thử:**
    - Bổ sung 5 translation keys: `toast_err_req_machine`, `toast_err_req_emp_code`, `toast_err_req_emp_name`, `toast_err_req_cancel_note`, `toast_err_qc_require_ng` đầy đủ cho cả 3 ngôn ngữ (`vi`, `en`, `ja`) trong `app/core/Language.php` và `public/assets/js/i18n.js`.
### TASK-028
**Status:** DONE
- Tối ưu hóa toàn diện cấu trúc, hiệu năng Bulk Import và đồng bộ dữ liệu cho file `public/assets/sql/Updated/production_db_Data.sql`:
  - **Khắc phục lỗi cú pháp nghiêm trọng:** Loại bỏ khai báo trùng lặp `PRIMARY KEY` trên bảng `employee_list`, xử lý triệt để lỗi `ERROR 1068 (42000): Multiple primary key defined` khi import.
  - **Đồng bộ hóa Schema & Dữ liệu chuẩn:**
    - Cập nhật bảng `employee_list` đầy đủ 10 cột theo `Architect_sql.sql` (bao gồm `cost_center` và `permissions`), nạp đủ 132 nhân viên thực tế có mã bộ phận và phân quyền từ Live DB.
    - Chuẩn hóa cấu trúc và dữ liệu các bảng danh mục: `material_list` (`brand, code, grinding_time`), `material_lot_list` (`lot, updated_time`), `winding_machine_list` (`machine_name`) khớp 100% với `Architect_sql.sql` và mã nguồn JavaScript (`suggestion.js`).
  - **Tối ưu hóa hiệu năng Bulk Import:**
    - Bổ sung các chỉ thị tăng tốc nạp dữ liệu: `SET FOREIGN_KEY_CHECKS = 0;`, `SET UNIQUE_CHECKS = 0;`, `SET AUTOCOMMIT = 0;` ở đầu file và khôi phục an toàn ở cuối file.
    - Phân đoạn các câu lệnh INSERT (Chunking): Chia bảng `bobin_list_detail` (3,000 dòng) thành các khối 500 dòng/lệnh kèm `COMMIT;` định kỳ; chia `bobin_list_general` (3,000 dòng) và `material_lot_list` (3,869 dòng) thành các khối 1,000 dòng/lệnh, ngăn chặn hoàn toàn nguy cơ tràn bộ đệm gói tin `max_allowed_packet` và quá tải Undo Log.
  - **Chuẩn hóa Collation:** Đưa toàn bộ cấu hình Database và bảng `rack_list` về thống nhất chuẩn `utf8mb4_unicode_ci`.
  - **Bổ sung chỉ mục (Indexes) tăng tốc truy vấn:** Bổ sung `uk_employee_code`, `uk_username`, `idx_employee_role` cho `employee_list`; `idx_lot` cho `material_lot_list`; `idx_detail_status` và `idx_general_status` cho `bobin_list_detail` & `bobin_list_general`; đồng bộ trực tiếp lên Live Database thành công 100%.
  - **Kiểm thử tự động:** Tạo database tạm `test_verify_production_db` và thực hiện import kiểm thử qua MySQL CLI: 100% thành công, 0 lỗi, nạp đủ 14 bảng với 10,960 dòng dữ liệu chuẩn.

### TASK-029
**Status:** DONE
- Khắc phục triệt để hiện tượng Toast thông báo xuất hiện 2 lần khi thực hiện hủy Bobin tại trang Danh sách chờ hủy (`listPendingCancellationView.php`) và khi cập nhật/hủy Bobin trên toàn hệ thống:
  - **Nguyên nhân cốt lõi (Root Cause):**
    1. **Hiện tượng Show trước reload + Flash sau reload (Show-before-reload Anti-pattern):** Trong `public/assets/js/delete.js`, sau khi API trả về kết quả xóa/hủy thành công, hàm `handleDelete()` gọi ngay lập tức `window.Toast.show(successMsg, 'success')` (khiến Toast #1 hiển thị ngay trên màn hình). Đồng thời, mã nguồn ghi `sessionStorage.setItem('bobin_toast_flash', ...)` và kích hoạt `setTimeout(() => window.location.reload(), 800)`. Khi trình duyệt tải lại trang, file `public/assets/js/toast.js` tự động khởi chạy `checkAutoToasts()`, đọc `bobin_toast_flash` từ `sessionStorage` và kích hoạt hàm `Toast.show()` một lần nữa (Toast #2 hiển thị sau khi reload). Do trang bị reload toàn bộ, biến bộ nhớ JavaScript (`lastToastTime`) bị xóa sạch về 0, vô hiệu hóa cơ chế deduplicate chống lặp trong 1.5s của `toast.js`.
    2. **Định nghĩa Toast và sự kiện trùng lặp trong `delete.js`:** Trong file `delete.js` trước đây chứa đoạn code cũ định nghĩa lại class `Toast` nội bộ và đăng ký một listener `DOMContentLoaded` riêng biệt để đọc `bobin_toast_flash`, xung đột và chạy song song với `toast.js` toàn cục.
    3. **Hiện tượng tương tự trên các trang Đùn, Cuộn và QC:** Pattern gọi `Toast.flash(...)` song song với `Toast.show(...)` trước khi reload cũng xuất hiện ở `Extrusion/edit_submit.js`, `QC/submit.js`, `QC/edit_submit.js`, `Winding/submit.js`, và `Winding/edit_submit.js`.
  - **Giải pháp xử lý:**
    - Chuẩn hóa `public/assets/js/delete.js`:
      - Xóa bỏ class `Toast` định nghĩa thừa và listener `DOMContentLoaded` cạnh tranh, sử dụng thống nhất `window.Toast` toàn cục từ `toast.js`.
      - Trong `handleDelete()`: Tạo hiệu ứng mờ dần (fade out) mượt mà cho card Bobin được hủy (`opacity = 0`, `transform = scale(0.95)`), lưu thông báo duy nhất vào `window.Toast.flash(...)` và gọi `window.location.reload()` sau 450ms. Tuyệt đối không gọi `Toast.show()` trước khi reload. Khi trang tải lại với danh sách mới và badge số lượng cập nhật, Toast chỉ hiển thị DUY NHẤT 1 LẦN.
    - Chuẩn hóa toàn bộ các file submit/edit trên toàn hệ thống:
      - `public/assets/js/Extrusion/edit_submit.js` (hủy & cập nhật).
      - `public/assets/js/QC/submit.js` (lưu kết quả QC, hủy Bobin QC, chuyển trạng thái QC) và `public/assets/js/QC/edit_submit.js`.
      - `public/assets/js/Winding/submit.js` (lưu cuộn, xác nhận cuộn) và `public/assets/js/Winding/edit_submit.js`.
      - Loại bỏ toàn bộ các lệnh `Toast.show(...)` dư thừa nằm ngay trước `window.location.reload()`, chuyển giao toàn bộ quyền hiển thị sau tải trang cho cơ chế Flash Toast duy nhất.

### TASK-030
**Status:** DONE
- Khắc phục triệt để lỗi cơ sở dữ liệu `SQLSTATE[42S22]: Column not found: 1054 Unknown column 'update_history' in 'field list'` khi xác nhận điều chỉnh thông tin Bobin tại trang Điều chỉnh Đùn, Điều chỉnh QC và Điều chỉnh Cuộn:
  - **Nguyên nhân cốt lõi (Root Cause):**
    - Trong thiết kế cơ sở dữ liệu (`DATABASE_SCHEMA.md`, TASK-007 & TASK-008) và trong `app/repositories/BobinRepository.php`, hệ thống sử dụng cột `update_history` (`LONGTEXT DEFAULT NULL`) trên hai bảng `bobin_list_detail` và `bobin_history` để lưu vết kiểm toán (audit trail) mỗi khi nhân viên chỉnh sửa thông tin Bobin tại 3 trang Điều chỉnh Đùn, Điều chỉnh QC, Điều chỉnh Cuộn và hiển thị tại trang Lịch sử (`listBobinHistoryView.php`).
    - Tuy nhiên, trong CSDL `production_db` thực tế và trong 2 file SQL (`public/assets/sql/production_db._Structure.sql`, `public/assets/sql/production_db_Data.sql`), cột `update_history` bị thiếu trên hai bảng `bobin_list_detail` và `bobin_history`.
    - Ngoài ra, trong `BobinRepository.php`, các câu lệnh tạo bản ghi snapshot lịch sử (`INSERT INTO bobin_history ... SELECT ... FROM bobin_list_detail`) khi kiểm tra QC, xác nhận Cuộn hoặc Hủy Bobin trước đó chưa sao chép theo cột `update_history` (cũng như `extrusion_check`, `rack`), và khi tái sử dụng Bobin cho chu kỳ mới (`insertBobinDetailRecord` - `ON DUPLICATE KEY UPDATE`) chưa reset `update_history = NULL`.
  - **Giải pháp xử lý:**
    - **Cập nhật trực tiếp Live Database (`production_db`):** Thực thi `ALTER TABLE` bổ sung cột `update_history LONGTEXT DEFAULT NULL COMMENT 'Mảng JSON lưu vết lịch sử điều chỉnh (Đùn, QC, Cuộn)'` vào cả hai bảng `bobin_list_detail` (sau cột `winding_note`) và `bobin_history` (sau cột `flow_test_result`).
    - **Đồng bộ hóa các tệp SQL chuẩn:** Bổ sung định nghĩa cột `update_history` vào `CREATE TABLE bobin_history` và `CREATE TABLE bobin_list_detail` trong cả 2 tệp `public/assets/sql/production_db._Structure.sql` và `public/assets/sql/production_db_Data.sql`.
    - **Hoàn thiện luồng bảo toàn lịch sử điều chỉnh trong `app/repositories/BobinRepository.php`:**
      - Cập nhật `insertBobinDetailRecord()` đặt `update_history = NULL` trong mệnh đề `ON DUPLICATE KEY UPDATE` để mỗi khi Bobin bắt đầu một chu kỳ `bobin_key_code` mới sẽ xóa sạch vết điều chỉnh của chu kỳ cũ.
      - Cập nhật toàn bộ 7 câu lệnh `INSERT INTO bobin_history (...) SELECT ... FROM bobin_list_detail` (`deleteBobin`, `extInsertBobinHistoryAfterDelete`, `updateQCBobinInfor`, `updateBobinTypeInfor`, `qcCancelBobin`, `updateWindingBobinInfor`, `windingCancelBobin`) sao chép đầy đủ `extrusion_check`, `rack`, và `update_history` sang dòng snapshot mới nhất trong `bobin_history`.

### TASK-031
**Status:** DONE
- Bổ sung chức năng quét mã QR cho trường **Vị trí Rack** (`#rack_code`) tại trang Nhóm Đùn (`app/views/extrusionView.php`) và ngăn chặn bàn phím ảo tự động bật lên sau khi quét QR:
  - **Giao diện (UI) & Bố cục:**
    - Bổ sung vùng hiển thị camera `<div id="qr-reader-rack"></div>` ngay đầu Khối 3 ("THÔNG SỐ CUỘN & VỊ TRÍ RACK") trong `app/views/extrusionView.php`.
    - Bọc ô nhập `#rack_code` và `#rack_suggestions` vào `.qr-input-group` kèm nút bấm `<button type="button" id="btnScanRackQR" class="btn-modern btn-scan">` đồng nhất với giao diện quét mã Bobin.
    - Cập nhật `public/assets/css/scanQR.css` và `public/assets/css/extrusion.css` hỗ trợ đầy đủ `#qr-reader-rack` và `#btnScanRackQR.is-scanning`.
  - **Điều phối Camera & Ngăn bàn phím ảo bật lên (`qrScannerHelper.js` & `scanQR.js`):**
    - Nâng cấp `QRScannerHelper.toggle()` trong `public/assets/js/qrScannerHelper.js`: kiểm tra `this.activeContainer === targetContainer` để khi người dùng chuyển đổi trực tiếp giữa nút quét Bobin (`#qr-reader`) và nút quét Rack (`#qr-reader-rack`), hệ thống tự động đóng khung quét cũ và mở khung quét mới mượt mà.
    - Hỗ trợ tùy biến tiêu đề khung quét (`options.title`, `options.searchingText`) với 2 key đa ngôn ngữ mới `qr_scanner_rack_title` và `qr_scanner_rack_searching` (`vi`, `en`, `ja`).
    - Ngăn bàn phím ảo hiện lên sau khi quét xong: loại bỏ hoàn toàn lệnh `.focus()` sau khi quét thành công và thay bằng `.blur()` + `document.activeElement.blur()` trong `qrScannerHelper.js`, `Extrusion/scanQR.js`, `Extrusion/edit_scanQR.js`, `QC/scanQR.js`, `Winding/scanQR.js`, và `Manage/scanQR.js`, đồng thời tự động ẩn danh sách gợi ý `#rack_suggestions` / `#bobin_suggestions`.
  - **Tương thích định dạng mã QR Rack trong Backend (`BobinServices.php`):**
    - Nâng cấp `BobinServices::resolveRack(string $rackCode)` hỗ trợ đối chiếu linh hoạt cả mã chính xác lẫn mã rút gọn số `0` ở đầu (ví dụ: mã QR `"Rack_B21_01"` khớp hợp lệ với `"Rack_B021_01"` trong bảng `rack_list` và giữ nguyên giá trị hiển thị theo mã đã quét).
### TASK-032
**Status:** DONE
- Thêm trường dữ liệu `previous_status` cho mỗi Bobin để lưu trữ trạng thái liền trước trạng thái hiện tại (`bobin_current_status`) trong `bobin_list_detail`, `bobin_list_general`, và `bobin_history`.
- Tại trang "Danh sách chờ hủy" (`app/views/listPendingCancellationView.php`), bổ sung ở mỗi phần tử Bobin nút **Khôi phục Bobin (Restore)** (`btn-restore-bobin`) và huy hiệu hiển thị trạng thái đích khi khôi phục (`prev-status-badge`):
  - Nếu được đưa vào danh sách hủy bởi **Nhóm Đùn** $\rightarrow$ khi Restore trả về trạng thái **Chưa kiểm tra QC** (`Busy_Unchecked`).
  - Nếu được đưa vào danh sách hủy bởi **Nhóm QC** $\rightarrow$ khi Restore trả về trạng thái **Chưa kiểm tra QC** (`Busy_Unchecked`).
  - Nếu được đưa vào danh sách hủy bởi **Nhóm Cuộn**, mà trước đó là trạng thái **Đã QC** $\rightarrow$ khi Restore trả về trạng thái **Đã QC** (`Busy_Checked`).
  - Nếu được đưa vào danh sách hủy bởi **Nhóm Cuộn**, mà trước đó là trạng thái **Đã Cuộn** $\rightarrow$ khi Restore trả về trạng thái **Đã Cuộn** (`Rolled`).
- Đề xuất chi tiết giải pháp kiến trúc & UX cho nghiệp vụ **"Bobin chạy dở dang"** (1 Bobin chạy nhiều lần tại nhóm Cuộn: `Busy_Checked` $\rightarrow$ `Rolled` $\rightarrow$ `Rolled`).

### TASK-033
**Status:** DONE
- Hãy kiểm tra lại trạng điều chỉnh QC, tôi đang thấy hiển thị sai dữ liệu
- Tại trang nhóm cuộn, user khi hủy phải chọn "Thông khí" hoặc "Ngoại quan" trên dialog review thì mới được hủy
- **Chi tiết thực hiện:**
  - **Khắc phục triệt để sai lệch hiển thị & lưu trữ dữ liệu tại trang Điều chỉnh QC (`qcEditBobinView.php`, `BobinController.php`, `BobinServices.php`, `BobinRepository.php`, `qcEditBobin.css`):**
    - Đồng bộ cấu trúc JSON `visual_inspection` theo chuẩn `VisualInspectionEntity` (`defects: { gel, foreign_object, color_issue, print_quality, note }`, `inspector_code`, `inspector_name`, `inspection_time`), kèm cơ chế fallback tương thích ngược với cấu trúc phẳng cũ (`defect_*`, `inspected_at`) trên `qcEditBobinView.php`, `windingView.php`, `listBobinDetailView.php`, `listBobinHistoryView.php` và các hàm xuất Excel (`prepareHistoryRowData`, `prepareRowData`, `prepareDetailRowData`).
    - Sửa lỗi đảo ngược trạng thái OK/NG khi xuất Excel cho 4 tiêu chí ngoại quan (`$defects['gel'] ? 'NG' : 'OK'`).
    - Bổ sung hiển thị đầy đủ thông số Bobin trên thẻ Điều chỉnh QC (`Kích thước`, `Lot vật liệu`, `Ca sản xuất`, `NV Đùn`, `Đùn tự kiểm tra` 5 tiêu chí, định dạng thời gian chuẩn cho `bobin_key_code`, thời gian kiểm tra QC thực tế `inspection_time`) và tích hợp gợi ý mã/tên nhân viên QC (`QC/suggestion.js`).
    - Sửa `BobinRepository::adminUpdateQCBobin()` và `adminUpdateWindingBobin()` chỉ cập nhật `update_history` trên đúng dòng lịch sử của công đoạn tương ứng (`WHERE id = :hist_id`), không ghi đè `visual_inspection` vào dòng lịch sử `Busy_Unchecked`.
  - **Bắt buộc chọn "Thông khí" hoặc "Ngoại quan" khi hủy Bobin tại trang Nhóm Cuộn (`Winding/submit.js`, `BobinWindingCancelDTO.php`, `BobinController.php`, `BobinServices.php`):**
    - Cập nhật `ConfirmDialog.show()` giữ nguyên hộp thoại khi callback `onConfirm(box)` trả về `false`.
    - Trong `handleWindingCancel()`, kiểm tra bắt buộc chọn ít nhất 1 nút `.error-toggle-btn.active` (`"Ngoại quan"` hoặc `"Thông khí"`). Nếu chưa chọn, hiển thị Toast cảnh báo + thông báo lỗi trực tiếp trên hộp thoại (`#winding-cancel-reason-error`) và không đóng dialog.
    - Bổ sung kiểm tra đồng bộ phía Backend tại `BobinController::validFormWinding_Cancel()` và `BobinServices::windingCancelBobin()`.

### TASK-034
**Status:** DONE
- Điều chỉnh lại thêm ở trang phân quyền, đối với danh sách chờ Hủy, phân quyền chia làm 2 dạng: Được xem và được chỉnh sửa thông tin, Chỉ được xem nhưng không được chỉnh sửa
- Tại trường "update_history" lịch sử cập nhật của Bobin, ngoài phần điều chỉnh thông tin Bobin, tôi cần để vào đó là "Đưa vào danh sách chờ hủy", "Xác nhận hủy"
- **Chi tiết thực hiện:**
  - **Tách phân quyền Danh sách chờ Hủy thành 2 mức loại trừ lẫn nhau (`AuthHelper.php`, `EmployeeController.php`, `employeePermissionsView.php`, `listPendingCancellationView.php`, `BobinController.php`, `Language.php`, `i18n.js`):**
    - Tách quyền `pending_cancel` trong nhóm `monitoring` thành 2 quyền độc lập và loại trừ lẫn nhau (mutually exclusive):
      1. `pending_cancel_edit`: **Danh sách chờ Hủy (Xem & Thao tác chỉnh sửa)** — Cho phép xem trang Danh sách chờ Hủy (`bobin/listpendingcancellationview`) đồng thời thao tác **Khôi phục Bobin** (`bobin/restorebobin`) và **Xác nhận Hủy** (`bobin/deletebobin`). Mặc định cấp cho vai trò `admin`.
      2. `pending_cancel_view`: **Danh sách chờ Hủy (Chỉ xem)** — Chỉ cho phép xem trang Danh sách chờ Hủy (`bobin/listpendingcancellationview`), ẩn các nút thao tác Khôi phục / Xác nhận Hủy và hiển thị nhãn thông báo chế độ chỉ xem (`pending_view_only_notice`). Mặc định cấp cho các vai trò `extrusion`, `qc`, `winding`.
    - Trên giao diện `employeePermissionsView.php`, khi tích chọn 1 trong 2 quyền (`pending_cancel_edit` hoặc `pending_cancel_view`), quyền còn lại tự động bỏ chọn; nút "Chọn tất cả" của nhóm Giám sát hoặc toàn trang sẽ ưu tiên chọn mức cao hơn (`pending_cancel_edit`).
    - Bổ sung kiểm tra bảo mật phía Backend tại `BobinController::deleteBobin()` và `BobinController::restoreBobin()` yêu cầu bắt buộc `AuthHelper::hasPermission('pending_cancel_edit')`, đồng thời đảm bảo tương thích ngược với dữ liệu quyền `pending_cancel` cũ trong CSDL.
  - **Ghi nhận và hiển thị `"Đưa vào danh sách chờ hủy"` & `"Xác nhận hủy"` trong `update_history` (`BobinServices.php`, `BobinRepository.php`, `listBobinHistoryView.php`, `listPendingCancellationView.php`, `Language.php`, `i18n.js`):**
    - Khi báo hủy Bobin từ bất kỳ công đoạn nào (`BobinServices::extDeleteBobin()`, `qcCancelBobin()`, `windingCancelBobin()`), hệ thống tự động nối thêm bản ghi kiểm toán vào mảng JSON `update_history` với `action: "pending_cancel"`, `note: "Đưa vào danh sách chờ hủy"`, thông tin công đoạn (`stage`), người thao tác (`operator`), thời gian (`timestamp`) và thay đổi trạng thái (`status: old -> Pending_Cancellation`) trên cả `bobin_list_detail` và `bobin_history`.
    - Khi xác nhận hủy hoàn tất (`BobinServices::deleteBobin()` / `BobinRepository::deleteBobin()` và trường hợp tự động hủy khi tạo đè Bobin tại `BobinRepository::createNewBobin()`), hệ thống nối thêm bản ghi vào `update_history` với `action: "confirm_cancel"`, `note: "Xác nhận hủy"`, người xác nhận (`operator`), thời gian (`timestamp`) và thay đổi trạng thái (`Pending_Cancellation -> Cancelled`).
    - Cập nhật giao diện hiển thị lịch sử cập nhật (`update_history`) trên cả 2 trang `listBobinHistoryView.php` và `listPendingCancellationView.php` với nhãn màu trực quan và hỗ trợ dịch đa ngôn ngữ (`vi`, `en`, `ja`).
### TASK-035: Phân quyền và đồng bộ quyền cho các nhóm người dùng
**Status:** DONE
- **Mục tiêu:** Rà soát và đồng bộ hệ thống phân quyền giữa Database, Backend/API và Frontend/UI cho các nhóm Admin, Winding, QC, Đùn (Extrusion); bổ sung 2 cấp quyền tại trang Điều chỉnh Đùn (`Edit Only` và `Edit + Cancel`); tách biệt và kiểm soát chặt chẽ quyền hủy Bobin hoàn toàn (`pending_cancel_edit`) tại cả Backend/API và giao diện.
- **Đã thực hiện:**
  - **Đồng bộ định nghĩa quyền & bộ kiểm tra trung tâm (`AuthHelper.php`):**
    - Tách quyền trang Điều chỉnh Đùn thành 2 cấp loại trừ lẫn nhau trong nhóm `'extrusion'`:
      - `extrusion_edit_only` (**Edit Only**): Cho phép truy cập `bobin/extrusioneditbobinview` và cập nhật Bobin qua `bobin/extrusionupdatebobin`, **không** được gọi `bobin/extrusiondeletebobin` hoặc hủy Bobin.
      - `extrusion_edit_cancel` (**Edit + Cancel**): Cho phép chỉnh sửa, đưa Bobin vào danh sách chờ hủy (`bobin/extrusiondeletebobin`) và hủy Bobin theo quy trình hiện tại.
    - Cập nhật đầy đủ route API cho từng quyền: bổ sung `bobin/qccancelbobin` vào `qc_check`, `bobin/windingcancelbobin` vào `winding_confirm`; khóa chặt `bobin/deletebobin` và `bobin/restorebobin` chỉ dành cho quyền `pending_cancel_edit`.
    - Chuẩn hóa `getDefaultPermissionsForRole()` cho 4 nhóm:
      - `admin`: Toàn bộ quyền hệ thống (`extrusion_create`, `extrusion_edit_cancel`, `qc_check`, `qc_edit`, `winding_confirm`, `winding_edit`, `bobin_list`, `bobin_history`, `pending_cancel_edit`, `employee_manage`, `permission_manage`).
      - `extrusion`: `extrusion_create`, `extrusion_edit_only`, `bobin_list`, `bobin_history`, `pending_cancel_view`.
      - `qc`: `qc_check`, `qc_edit`, `bobin_list`, `bobin_history`, `pending_cancel_view`.
      - `winding`: `winding_confirm`, `winding_edit`, `bobin_list`, `bobin_history`, `pending_cancel_view`.
    - Bổ sung `AuthHelper::ensureSessionPermissionsFresh()` tự động đồng bộ `role` và `permissions` mới nhất từ bảng `employee_list` vào `$_SESSION['user']` trong mỗi HTTP request.
  - **Kiểm soát chặt chẽ quyền tại Backend/API (`Router.php`, `BobinController.php`, `EmployeeController.php`):**
    - Tại `Router::dispatch()`: kiểm tra `AuthHelper::isActionAllowed($controllerKey, $methodName)` cho mọi request và trả về HTTP 403 JSON chuẩn (`{"success": false, "error": "...", "message": "..."}`) đối với các cuộc gọi API/POST/PUT vượt quyền.
    - Tại `BobinController.php`: bổ sung kiểm tra `AuthHelper::hasPermission(...)` trực tiếp tại đầu tất cả các hàm View và API (`createBobin`, `extrusionUpdateBobin`, `extrusionDeleteBobin`, `updateQCBobin`, `qcCancelBobin`, `updateQcEditBobin`, `updateWindingBobin`, `windingCancelBobin`, `updateWindingEditBobin`, `restoreBobin`, `deleteBobin`, `listBobinDetailView`, `listBobinHistoryView`, `listPendingCancellationView`), ngăn chặn triệt để việc gọi API trực tiếp để vượt quyền.
    - Tại `EmployeeController.php`: tự động khởi tạo và đồng bộ cột `permissions` theo role khi tạo mới (`create`), đổi vai trò (`update`), nhập CSV (`importExcel`) và lưu phân quyền (`updatePermissions`), đồng thời đảm bảo tính loại trừ lẫn nhau giữa `extrusion_edit_only` ↔ `extrusion_edit_cancel` và `pending_cancel_view` ↔ `pending_cancel_edit`.
  - **Cập nhật Giao diện & Đa ngôn ngữ (`extrusionEditBobinView.php`, `edit_submit.js`, `employeePermissionsView.php`, `sidebar.php`, `Language.php`, `i18n.js`):**
    - Trên `extrusionEditBobinView.php` & `public/assets/js/Extrusion/edit_submit.js`: hiển thị huy hiệu cấp quyền (`Edit Only` hoặc `Edit + Cancel`), ẩn nút `"Hủy Bobin"` (`.btn-delete-scrap`) và khóa hàm `handleDelete()` khi người dùng chỉ có quyền `extrusion_edit_only`.
    - Trên `employeePermissionsView.php`: hỗ trợ chọn loại trừ lẫn nhau (radio-like) cho 2 cặp quyền `extrusion_edit_cancel` ↔ `extrusion_edit_only` và `pending_cancel_edit` ↔ `pending_cancel_view`.
    - Bổ sung đầy đủ từ điển đa ngôn ngữ (`vi`, `en`, `ja`) cho các quyền và thông báo mới.
  - **Đồng bộ Database & SQL Dump (`production_db.employee_list` & `public/assets/sql/production_db_Data.sql`):**
    - Đồng bộ 100% (132/132 nhân viên) trong `employee_list` (không còn bản ghi `NULL` hoặc chứa key cũ): 12 Admin có toàn quyền; 13 nhân viên được chỉ định cụ thể giữ quyền `pending_cancel_edit` (và `extrusion_edit_cancel` đối với 6 nhân viên Đùn được chỉ định); 107 nhân viên tiêu chuẩn thuộc Đùn (46), QC (3), Winding (58) nhận đúng bộ quyền chuẩn theo nhóm.

### TASK-036: 
**Status:** DONE
- Chỉnh sửa phân quyền: Tất cả nhân viên Extrusion đều có thể đưa Bobin vào danh sách chờ hủy
- Tại trang Nhóm cuộn: Khi tôi nhấn nút hủy Bobin thì không nhấn được nút "ngoại quan" và "thông khí" trước khi xác nhận hủy. Hãy kiểm tra, đồng thời điều chỉnh UI/UX cho hợp lí
  - **1. Cập nhật Phân quyền nhóm Đùn (Extrusion):**
    - Cập nhật `AuthHelper::getDefaultPermissionsForRole('extrusion')` và logic tương thích ngược trong `AuthHelper::getUserPermissions()` từ `extrusion_edit_only` sang `extrusion_edit_cancel` để mặc định toàn bộ nhân viên nhóm Đùn đều được phép đưa Bobin vào danh sách chờ hủy (`bobin/extrusionDeleteBobin`).
    - Đồng bộ dữ liệu cho toàn bộ 46 nhân viên nhóm Đùn tiêu chuẩn trong bảng `production_db.employee_list` và file `public/assets/sql/production_db_Data.sql` sang `extrusion_edit_cancel` (100% nhân viên Đùn — 52/52 người — đều có quyền đưa Bobin vào danh sách chờ hủy; trong khi quyền duyệt hủy hoàn toàn `pending_cancel_edit` vẫn giữ nguyên chỉ dành cho Admin và các nhân viên được chỉ định).
  - **2. Khắc phục triệt để lỗi không nhấn được nút "Ngoại quan" & "Thông khí" và nâng cấp UI/UX Hủy Bobin tại trang Nhóm cuộn (`windingView.php`):**
    - **Nguyên nhân gốc rễ:** Trên `windingView.php`, file `public/assets/js/logout.js` được nạp trước `public/assets/js/Winding/submit.js`. File `logout.js` đã khởi tạo sẵn `window.ConfirmDialog` (phiên bản rút gọn chỉ có 3 tham số `show(title, contentHTML, onConfirm)`, không hỗ trợ tham số thứ 4 `onToggle`, không gắn sự kiện click cho `.error-toggle-btn`, và tự động đóng hộp thoại ngay cả khi `onConfirm()` trả về `false`). Khi `Winding/submit.js` chạy lệnh `if (typeof ConfirmDialog === "undefined")`, điều kiện trả về `false` khiến phiên bản `ConfirmDialog` đầy đủ trong `Winding/submit.js` bị bỏ qua hoàn toàn.
    - **Giải pháp khắc phục & Cải tiến UI/UX (`Winding/submit.js`, `logout.js`, `windingView.php`, `listBobin_Winding.css`, `Language.php`, `i18n.js`):**
      - Loại bỏ điều kiện chặn `if (typeof ConfirmDialog === "undefined")` trong `Winding/submit.js` để luôn đăng ký `window.ConfirmDialog` phiên bản đầy đủ (hỗ trợ `onToggle`, gắn trực tiếp sự kiện `click` với `e.preventDefault(); e.stopPropagation();` lên từng nút `.error-toggle-btn`, cập nhật trạng thái `.active` + huy hiệu `☑ NG` / `☐ OK`, và giữ hộp thoại mở nếu `onConfirm()` trả về `false`); đồng thời nâng cấp cả `window.ConfirmDialog` trong `logout.js` để tương thích đồng bộ.
      - Bổ sung thanh chọn nhanh hạng mục lỗi (**🔍 Ngoại quan** và **💨 Thông khí**) trực tiếp ngay trên từng thẻ Bobin tại `windingView.php` (`.winding-cancel-quick-group` / `.card-error-toggle-btn`), đồng bộ 2 chiều với hộp thoại xác nhận hủy.
      - Thiết kế lại hộp thoại xác nhận hủy Bobin phía Cuộn trực quan theo 2 bước: (1) Chọn hạng mục phát sinh lỗi NG (**Ngoại quan** / **Thông khí**) với thẻ nút bấm lớn dễ thao tác trên màn hình cảm ứng xưởng; (2) Ô nhập ghi chú chi tiết bổ sung ngay trong hộp thoại (không bắt buộc phải gõ ghi chú bên ngoài trước khi bấm nút Hủy Bobin) kèm khung xem trước nội dung lý do hủy tổng hợp theo thời gian thực.
      - Bổ sung đầy đủ 11 từ khóa đa ngôn ngữ (`vi`, `en`, `ja`) trong `app/core/Language.php` và `public/assets/js/i18n.js`.

### TASK-037: 
**Status:** DONE
- Kiểm tra lại sử dụng đa ngôn ngữ của dự án, xem có vị trí nào đang thiếu sót chuyển đổi không
- Kiểm tra và Đề xuất các hướng cải tiến cho dự án cho tôi xem xét (Nếu có)
  - **1. Kiểm tra & Đồng bộ toàn diện hệ thống Đa ngôn ngữ (`vi`, `en`, `ja`) trên toàn dự án:**
    - **Đồng bộ 100% từ điển Backend (`app/core/Language.php`) và Frontend (`public/assets/js/i18n.js`):**
      - Bổ sung **14 từ khóa** từng có trong `i18n.js` nhưng thiếu trong `Language.php` (`confirm_pwd_label`, `confirm_pwd_ph`, `confirm_pwd_empty`, `confirm_edit_title`, `confirm_edit_sub`, `saving`, `saved`, `save_failed`, `audit_trail_title`, `audit_stage_extrusion`, `audit_stage_qc`, `audit_stage_winding`, `audit_updater`, `audit_time` — khắc phục lỗi hiển thị raw key khi render PHP trên `listBobinHistoryView.php` và `listPendingCancellationView.php`).
      - Bổ sung **3 từ khóa** từng có trong `Language.php` nhưng thiếu trong `i18n.js` (`perm_active_status`, `perm_inactive_status`, `btn_delete`).
      - Bổ sung **7 từ khóa** được gọi trong các View/JS nhưng chưa được khai báo ở cả 2 từ điển (`no_data_found`, `showing`, `col_size`, `field_defect_note`, `btn_back`, `warning`, `info`).
      - Bổ sung **16 từ khóa mới** cho trang Đổi mật khẩu (`changePasswordView.php`), hộp thoại Đăng xuất (`logout.js`) và chức năng Sao chép dữ liệu (`copyText.js`): `cp_guide_title`, `cp_guide_1`, `cp_guide_2`, `cp_guide_3`, `cp_err_empty`, `cp_err_min_len`, `cp_err_mismatch`, `cp_err_same_old`, `cp_err_weak_forbidden`, `cp_err_failed`, `logout_confirm_title`, `logout_confirm_msg`, `copy_empty_err`, `copy_done_btn`, `copy_failed_err`.
      - Hiện tại cả `Language.php` và `i18n.js` đều đạt **581 từ khóa đồng nhất 100%** trên cả 3 ngôn ngữ (`vi`, `en`, `ja`), không còn bất kỳ key thiếu hoặc lệch nào.
    - **Khắc phục các vị trí văn bản tĩnh/động chưa chuyển đổi trên giao diện & JS:**
      - `app/views/components/header.php`: Thay thế tên nhóm quyền hardcode tiếng Việt (`Nhóm Đùn`, `Nhóm Cuộn`, `Nhóm QC`, `Admin`) trên `.header-user-role` bằng `data-i18n` và `__()` (`emp_role_extrusion`, `emp_role_winding`, `emp_role_qc`, `emp_role_admin`).
      - `app/views/changePasswordView.php`: Chuyển đổi toàn bộ văn bản HTML (tiêu đề, mô tả, cảnh báo đăng nhập lần đầu, nhãn mã nhân viên/tài khoản, khung hướng dẫn quy tắc bảo mật, bản quyền) và thông báo JS (thang đo độ mạnh mật khẩu, các thông báo kiểm tra hợp lệ) sang `data-i18n`, `__()`, và `window.t()`.
      - `app/views/employeeListView.php`: Tách `data-i18n` riêng biệt cho bộ đếm `#empCounter` (`emp_showing`, `emp_staff`) và tiêu đề cột `emp_code / emp_username` để không làm mất số đếm khi đổi ngôn ngữ.
      - `public/assets/js/i18n.js`: Mở rộng `DOM_MAPPINGS` (`labels`, `buttons`, `statusBadges`), bổ sung hỗ trợ `[data-i18n-title]`, hỗ trợ đồng thời placeholder `{current}` và `:current` cho phân trang `page_info`, bổ sung `window.translateDynamicText()` dịch tự động các thông báo Toast/API động, và bổ sung **Mục J trong `deepTranslateDOM()`** để tự động dịch các `<option>` trong `<select>`, các giá trị mặc định trên thẻ Bobin (`Chưa cập nhật`, `Chưa kiểm`, `Không có ghi chú`, Loại Bobin, Ca làm việc, Thông khí), nhãn lọc ngày, menu xuất CSV/Excel và huy hiệu lỗi ngoại quan.
      - `public/assets/js/toast.js`, `public/assets/js/logout.js`, `public/assets/js/copyText.js`: Tích hợp `window.t()` và `window.translateDynamicText()` cho toàn bộ thông báo Toast, hộp thoại xác nhận đăng xuất và nút sao chép dữ liệu.
  - **2. Đề xuất các hướng cải tiến toàn diện cho dự án & Triển khai Đề xuất 1 (Single Source of Truth cho i18n):**
    - Đã phân tích và lập danh mục 6 nhóm đề xuất cải tiến trọng tâm.
    - **Đã triển khai hoàn tất Đề xuất 1:** Hợp nhất toàn bộ 581 từ khóa × 3 ngôn ngữ (`vi`, `en`, `ja`) về nguồn duy nhất `config/default_translations.json` (kết hợp `config/custom_translations.json`); rút gọn `app/core/Language.php` từ 2.072 dòng xuống 367 dòng và `public/assets/js/i18n.js` từ 3.565 dòng xuống 1.701 dòng; tự động nhúng `window.__DEFAULT_I18N__`, `window.__CUSTOM_I18N__`, `window.__I18N_HASH__` ngay trước `i18n.js` trong `<head>` qua `Controller::view()` và `Language::renderJsBootstrap()`.

### TASK-038: 
**Status:** DONE
- Cập nhật thêm 4 trường tiêu chí kiểm tra ngoại quan QC: "Vón cục", "Trầy", "Xước", "Biến dạng" (nâng tổng số tiêu chí ngoại quan từ 4 lên 8 tiêu chí).
  - **1. Cập nhật Từ điển Đa ngôn ngữ (`config/default_translations.json` & `public/assets/js/i18n.js`):**
    - Bổ sung 8 từ khóa đa ngôn ngữ mới (`crit_clump`, `crit_scratch`, `crit_streak`, `crit_deformation`, `chk_clump`, `chk_scratch`, `chk_streak`, `chk_deformation`) đầy đủ cho cả 3 ngôn ngữ (`vi`, `en`, `ja`) tại `config/default_translations.json` (tổng cộng 589 từ khóa đồng bộ 100%).
    - Mở rộng `DOM_MAPPINGS.labels` và `DEFECT_NAMES` trong `public/assets/js/i18n.js` cho 4 tiêu chí mới.
  - **2. Cập nhật Model, Entity & DTO Backend:**
    - Cập nhật `app/entities/DefectEntity.php` và `app/models/DetectModel.php` bổ sung 4 thuộc tính boolean (`$clump`, `$scratch`, `$streak`, `$deformation`) và phương thức `jsonSerialize()`, `fromArray()` tương thích ngược với các alias (`lump`, `scuff`, `deformed`).
    - Cập nhật `BobinQCUpdateDTO.php`, `BobinQCCancelDTO.php`, `BobinUpdateQCDTO.php` tiếp nhận và chuyển đổi 4 trường lỗi mới từ request.
  - **3. Cập nhật Service, Controller & Xuất CSV:**
    - Cập nhật `BobinServices::createVisualInspection()` và các phương thức `updateQCBobin()`, `qcCancelBobin()`, `updateQCAndChangeTypeBobin()` để xử lý chuẩn hóa 8 tiêu chí ngoại quan.
    - Cập nhật `BobinController.php`: Xử lý 8 tiêu chí trong `updateQCEditBobin()`, `validFormQC_Cancel()`, và thêm 4 cột tiêu chí mới vào file xuất Excel/CSV lịch sử và chi tiết (`exportHistoryExcel`, `exportDetailExcel`).
  - **4. Cập nhật Giao diện (Views) & Script Frontend:**
    - `app/views/qcView.php`: Mở rộng lưới kiểm tra ngoại quan thành 8 công tắc toggle switch với `data-i18n`.
    - `public/assets/js/QC/submit.js`: Thu thập đủ 8 tiêu chí cho cả 3 luồng xác nhận QC, chuyển loại Bobin và hủy Bobin; tối ưu giao diện hộp thoại xác nhận dạng lưới 2 cột gọn gàng và hỗ trợ i18n.
    - `app/views/qcEditBobinView.php`: Cập nhật thống kê KPI và lưới nút bấm điều chỉnh kiểm tra ngoại quan sang 8 tiêu chí.
    - `public/assets/js/QC/edit_submit.js`: Thu thập đủ 8 tiêu chí khi điều chỉnh QC, hiển thị lưới 2 cột trong hộp thoại xác nhận và gửi payload cập nhật.
    - `app/views/windingView.php`, `app/views/listBobinDetailView.php`, `app/views/listBobinHistoryView.php`, `app/views/listPendingCancellationView.php`, `app/views/listBobinView.php`: Cập nhật hiển thị huy hiệu (badges) kết quả kiểm tra ngoại quan đầy đủ 8 tiêu chí.

### TASK-039:
**Status:** DONE
- Đồng bộ các trường dữ liệu ngoại quan mới ("Vón cục", "Trầy", "Xước", "Biến dạng") vào Nhóm Đùn Tự Kiểm Tra (nâng tổng số tiêu chí từ 5 lên 9).
- Khắc phục lỗi nút nhấn không hoạt động tại trang QC (`qcView.php`):
  - **1. Sửa lỗi nút nhấn tại trang QC (`qcView.php`):**
    - Tìm ra và xử lý nguyên nhân gốc rễ: Vòng lặp con duyệt `$qcItems` sử dụng biến `$item` làm che khuất (variable shadowing / collision) biến `$item` của vòng lặp duyệt `$bobins`, dẫn tới các thuộc tính `$item['bobin_identification_code']`, `$item['bobin_key_code']` bị trống (`""`). Toàn bộ nút `handleConfirm()`, `handleCancel()`, `handleChangeType()` khi gọi hàm đều truyền chuỗi rỗng và bị chặn lại.
    - Đổi tên biến lặp con thành `$qcDefect` và khôi phục 100% hoạt động cho các nút thao tác xác nhận QC, đổi loại, hủy Bobin.
    - Chuyển container `.qc-badges` thành `.qc-switches-grid` chuẩn 4 cột trên desktop (2 cột trên mobile/tablet) theo CSS.
    - Hỗ trợ click trực tiếp trên toàn bộ vùng `.vi-item-switch` để kích hoạt toggle switch thuận tiện.
    - Cho phép nhập mã NV QC nếu session chưa có mã nhân viên (không khóa cứng `readonly`), tích hợp autocomplete gợi ý mã nhân viên qua `suggestion.js`.
    - Xóa bỏ modal thừa `#modalChangeType` có các hàm JS chưa định nghĩa.
  - **2. Đồng bộ 9 tiêu chí vào Nhóm Đùn Tự Kiểm Tra (Extrusion Check):**
    - Cập nhật `ExtrusionCheckEntity.php`: Bổ sung 4 thuộc tính boolean (`$clump`, `$scratch`, `$streak`, `$deformation`), khởi tạo mặc định `true`, `fromArray()` tương thích alias.
    - Cập nhật DTO `BobinCreateDTO.php` và `BobinExtUpdateDTO.php`: Thêm 4 trường `ext_check_clump`, `ext_check_scratch`, `ext_check_streak`, `ext_check_deformation` và chuyển đổi request.
    - Cập nhật `BobinServices.php`: Khởi tạo `ExtrusionCheckEntity` đầy đủ 9 tham số khi tạo và cập nhật Bobin.
    - Cập nhật `extrusionView.php` & `public/assets/js/Extrusion/submit.js`: Hiển thị 9 nút toggle kiểm tra đùn có nhãn đa ngôn ngữ, thu thập và hiển thị đầy đủ trong popup xem trước.
    - Cập nhật `extrusionEditBobinView.php` & `public/assets/js/Extrusion/edit_submit.js`: Cập nhật thống kê KPI, mảng 9 tiêu chí đùn và thu thập đầy đủ khi điều chỉnh.
    - Cập nhật dải hiển thị tham khảo Đùn tự kiểm tra tại 5 màn hình: `qcView.php`, `qcEditBobinView.php`, `windingView.php`, `listBobinDetailView.php`, `listBobinHistoryView.php`, `listPendingCancellationView.php`.

### TASK-040:
**Status:** DONE
- Cải tiến như sau:
  + Trang "Nhóm Cuộn - Xác nhận hoàn thành Bobin" chỉ hiển thị danh sách các Bobin ở trạng thái "Busy_Checked" và "Rolled". Tuy nhiên đối với "Rolled" thì chỉ hiển thị những Bobin có updated_time trong vòng 24 tiếng so với thời điểm hiện tại.
  + Tại trang "Điều chỉnh cuộn": 
     1. Thêm nút hủy Bobin tương ứng với từng Bobin để đưa Bobin vào danh sách chờ hủy.
     2. Nếu người dùng nhấn nút "Hủy Bobin" thì sẽ hiện dialog, bao gồm: 1 textfield "ghi chú" lý do hủy Bobin, và 1 textfield nhập số thành phẩm đã chạy được từ Bobin đó trước khi hủy. Cả 2 dữ liệu này sẽ được gom lại và đặt vào trường dữ liệu "winding_note" sao cho hợp lí.
- **Chi tiết thực hiện:**
  - **Lọc Bobin Rolled trong vòng 24 giờ tại Nhóm Cuộn (`BobinRepository.php`):**
    - Cập nhật cả 2 hàm `getDetailBobinsForWinding()` và `countDetailBobinsForWinding()`: Thay đổi điều kiện từ `INTERVAL 3 DAY` thành `INTERVAL 24 HOUR` (`bobin_current_status = 'Busy_Checked' OR (bobin_current_status = 'Rolled' AND updated_time >= DATE_SUB(NOW(), INTERVAL 24 HOUR))`).
  - **Bổ sung nút và dialog Hủy Bobin tại trang Điều chỉnh Cuộn (`windingEditBobinView.php`, `windingEditBobin.css`, `Winding/edit_submit.js`):**
    - `windingEditBobinView.php`: Bổ sung nút `.wnd-btn-delete.btn-cancel-bobin` ("Hủy Bobin") trên mỗi thẻ Bobin (kèm biểu tượng 🗑️ và `data-i18n="btn_cancel_bobin"`). Bổ sung đối tượng ngữ cảnh tài khoản người dùng `window.CURRENT_USER` chứa `employee_code` và `employee_name`.
    - `windingEditBobin.css`: Thêm kiểu dáng `.wnd-btn-delete` với màu cảnh báo đỏ, viền mềm và hiệu ứng hover/shadow đồng bộ hệ thống.
    - `public/assets/js/Winding/edit_submit.js`:
      - Ẩn nút "Hủy Bobin" khi chuyển sang chế độ sửa và hiện lại khi hủy/hoàn tất sửa.
      - Xây dựng hàm `handleWindingEditCancel(btnElement, bobinCode, bobinKeyCode)` mở popup dialog xác nhận trực quan:
        - Hiển thị thông tin tóm tắt Bobin (Mã, Sản phẩm, Máy cuộn, Nhân viên).
        - Textfield 1: "Lý do hủy Bobin" (bắt buộc nhập, kiểm tra và báo lỗi nếu trống).
        - Textfield 2: "Số lượng thành phẩm đã chạy trước khi hủy" (type number, nhập 0 hoặc để trống nếu chưa chạy).
        - Khung xem trước (live preview) cập nhật thời gian thực chuỗi ghi chú sẽ lưu: `Số thành phẩm đã chạy: {qty} | Lý do hủy: {reason}`.
        - Gửi PUT request đến `bobin/windingCancelBobin`.
  - **Mở rộng DTO, Controller & Service tiếp nhận Hủy Bobin từ Điều chỉnh Cuộn (`BobinWindingCancelDTO.php`, `BobinController.php`, `BobinServices.php`):**
    - `BobinWindingCancelDTO.php`: Bổ sung các trường `$source`, `$finished_product_qty`, `$cancel_reason`.
    - `BobinController::validFormWinding_Cancel()`: Nếu `$dto->source === 'winding_edit'`, cho phép ghi chú lý do hủy tổng hợp tự do (không bắt buộc chỉ chọn riêng "Thông khí" hoặc "Ngoại quan"), đồng thời tự động gán fallback mã/tên nhân viên từ session nếu bản ghi ban đầu chưa cập nhật.
    - `BobinServices::windingCancelBobin()`: Tối ưu hóa cơ chế đối chiếu nhân viên với fallback session của người thực hiện thao tác, ghi nhận đúng lịch sử `pending_cancel` trong `update_history` và bảo toàn định dạng `winding_note`.
  - **Đồng bộ Đa ngôn ngữ (`config/default_translations.json`):**
    - Bổ sung 8 khóa dịch mới cho cả 3 ngôn ngữ (`vi`, `en`, `ja`): `winding_edit_cancel_title`, `winding_edit_cancel_desc`, `winding_cancel_reason_lbl`, `ph_winding_cancel_reason`, `winding_cancel_finished_qty_lbl`, `ph_winding_cancel_finished_qty`, `winding_cancel_preview_lbl`, `err_winding_cancel_reason_required`.

### TASK-041:
**Status:** DONE
- Điều chỉnh lại giao diện khi nhấn nút Hủy Bobin ở trang điều chỉnh cuộn (`windingEditBobinView.php`):
  + **Gợi ý nhanh lý do:** "Thông khí", "Ngoại quan", "A1", "A2", "A3", "A4", "A5"
  + **Đơn vị thành phẩm:** "Cuộn" (vi: Cuộn, en: Rolls, ja: 巻), không cần gợi ý nhanh số lượng.
  + **Đồng bộ đa ngôn ngữ:** Hoàn tất 100% trên cả 3 ngôn ngữ (`vi`, `en`, `ja`).
- **Chi tiết thực hiện:**
  - **1. Thiết kế Hệ thống CSS Modal chuyên nghiệp (`public/assets/css/windingEditBobin.css`):**
    - Khởi tạo đầy đủ các lớp CSS chuyên biệt: `.wnd-modal-overlay`, `.wnd-modal-box`, `.wnd-modal-header`, `.wnd-modal-close`, `.wnd-modal-body`, `.wnd-cancel-alert`, `.wnd-cancel-summary`, `.wnd-modal-field`, `.wnd-quick-chips`, `.wnd-chip-btn`, `.wnd-cancel-preview-box`, `.wnd-modal-footer`, `.wnd-btn-modal-cancel`, `.wnd-btn-modal-confirm`.
    - Điều chỉnh padding-right ô input (`.wnd-modal-input.has-unit { padding-right: 68px; }`) hiển thị vừa vặn huy hiệu đơn vị "Cuộn" / "Rolls" / "巻".
    - Loại bỏ hoàn toàn sự phụ thuộc vào inline styles thủ công trong JavaScript.
    - Đảm bảo tính tương thích và phản hồi cao (responsive) trên mọi thiết bị: tablet xưởng, mobile và desktop.
  - **2. Tối ưu UX & Thiết kế Hộp thoại Hủy Bobin (`public/assets/js/Winding/edit_submit.js`):**
    - Header: Nổi bật với tone màu đỏ cảnh báo, biểu tượng 🗑️, tiêu đề, mô tả và nút đóng tròn `✕`.
    - Thẻ tóm tắt Bobin: Hiển thị lưới thông tin 2 cột rõ ràng gồm Mã Bobin (dạng monospace `#...`), Sản phẩm (màu xanh dương đậm), Máy cuộn, Nhân viên cuộn.
    - Trường "Lý do hủy Bobin": Input hiện đại kèm **Quick Suggestion Chips** chuẩn: **Thông khí, Ngoại quan, A1, A2, A3, A4, A5**. Cơ chế toggle thông minh: nhấp chọn thêm vào, nhấp lại để gỡ bỏ hoặc kết hợp tự do bằng dấu phẩy; đồng bộ trạng thái `.active` khi gõ phím.
    - Trường "Số lượng thành phẩm đã chạy trước khi hủy": Input số lượng kèm huy hiệu đơn vị **"Cuộn"**, loại bỏ toàn bộ khối gợi ý nhanh số lượng theo yêu cầu.
    - Khung xem trước trực tiếp (Live Preview): Cập nhật tự động thời gian thực chuỗi ghi chú chuẩn hóa `Số thành phẩm đã chạy: {qty} Cuộn | Lý do hủy: {reason}`.
    - Tương tác công thái học: Tự động focus vào ô nhập lý do; hỗ trợ phím `Escape` để hủy hộp thoại; phím `Enter` để xác nhận nhanh; click ngoài backdrop để đóng modal; hiệu ứng nút `⏳ Đang xử lý...` chống bấm đúp khi gửi yêu cầu.
  - **3. Cập nhật Backend nhận diện thông khí đa ngôn ngữ (`app/services/BobinServices.php`):**
    - Nhận diện linh hoạt các biến thể "Thông khí", "Airflow", "通気" trong `winding_note` để tự động đánh dấu kết quả kiểm tra thông khí NG khi hủy Bobin.
  - **4. Đa ngôn ngữ (`config/default_translations.json`):**
    - Đồng bộ 100% các từ khóa đa ngôn ngữ mới cho cả 3 ngôn ngữ (`vi`, `en`, `ja`): `chip_reason_airflow`, `chip_reason_appearance`, `chip_reason_a1`, `chip_reason_a2`, `chip_reason_a3`, `chip_reason_a4`, `chip_reason_a5`, `qty_unit_roll`, `ph_winding_cancel_finished_qty`.

### TASK-042:
**Status:** DONE
- Kiểm tra lại source code của dự án, tối ưu về UI/UX, tối ưu tốc độ xử lý, đảm bảo không thay đổi các chức năng hiện có mang tính nghiệp vụ.
- **Chi tiết thực hiện:**
  - **1. Tối ưu hóa Tốc độ Xử lý Backend & Truy vấn CSDL (`ListDataRepository.php`, `BobinRepository.php`, `BobinServices.php`):**
    - `ListDataRepository.php`: Loại bỏ hoàn toàn khối `beginTransaction()` và `commit()` trên 12 truy vấn `SELECT` đọc danh mục, loại bỏ transaction lock và InnoDB read-view overhead. Thay thế `$pdo->prepare()` lặp lại bằng `$pdo->query()` trực tiếp, đưa thời gian thực thi `getListData()` xuống còn ~13ms.
    - `BobinRepository.php`: Bổ sung request-scoped caching với thuộc tính `$cachedCapacities` và `$cachedRacks`. Các hàm phụ trợ `getActiveBaseTable()` và render view không còn bị truy vấn CSDL trùng lặp nhiều lần trong cùng một chu kỳ request. Tự động xóa cache khi gọi `updateBobinCapacity()`.
    - `BobinServices.php`: Mở rộng tham số `$forceRefresh` cho `getBobinCapacities()` và `getAllRacks()`.
  - **2. Tối ưu Chỉ mục CSDL (Database Indexing - An toàn, không sửa schema):**
    - Thêm chỉ mục composite `idx_status_updated` trên `bobin_list_detail(bobin_current_status, updated_time)`, loại bỏ hoàn toàn hiện tượng `Using filesort` khi thực hiện truy vấn danh sách QC, Cuộn và Chờ hủy theo thời gian thực.
    - Thêm chỉ mục composite `idx_size_ident` trên `bobin_list_detail(bobin_size, bobin_identification_code)` hỗ trợ phân vùng tính toán row number cho bảng ảo active base table.
  - **3. Tối ưu Hiệu năng Render Frontend & Trải nghiệm UI/UX (`suggestion.js`, `edit_suggestion.js`):**
    - Đồng bộ áp dụng cơ chế Debounce (100ms) kết hợp `DocumentFragment` và giới hạn hiển thị tối đa 40 gợi ý (`MAX_SUGGESTIONS = 40`) trên toàn bộ các module: `QC/suggestion.js`, `Winding/suggestion.js`, `Extrusion/suggestion.js`, `Extrusion/edit_suggestion.js`.
    - Duy trì khả năng nhận diện khớp chính xác tức thì (`exactMatch`) khi quét mã vạch / mã QR mà không bị chậm trễ.
    - Ngăn ngừa triệt để hiện tượng sụt giảm khung hình (UI frame drops / DOM freeze) khi người dùng gõ phím nhanh hoặc thao tác trên màn hình cảm ứng xưởng sản xuất.
  - **4. Bảo toàn Nghiệp vụ & Kiểm tra Toàn diện:**
    - Giữ nguyên 100% logic nghiệp vụ, quy tắc tính toán, luồng chuyển trạng thái Bobin và giao diện chức năng.
    - Đảm bảo 100% hoạt động offline trong mạng cục bộ nội bộ (local intranet).
    - Toàn bộ 75 tệp mã nguồn PHP và toàn bộ tệp JavaScript đều vượt qua kiểm tra cú pháp linter đạt 100% thành công.

### TASK-044:
**Status:** DONE
- Tại trang "Danh sách Hiện tại" (`app/views/listBobinDetailView.php`), bổ sung và hoàn thiện tính năng lọc theo trạng thái "Đã hủy" (`Cancelled`):
  - **1. Bộ lọc Dropdown (`#statusFilter`):**
    - Đưa tùy chọn `'Cancelled' => '❌ Đã hủy'` vào danh mục `$statusOptions` trong `app/views/listBobinDetailView.php`.
    - Khi người dùng chọn trạng thái "❌ Đã hủy", hệ thống nạp URL `status=Cancelled`, lọc chính xác các Bobin có `bobin_current_status = 'Cancelled'`.
  - **2. Đồng bộ Thống kê CSDL & Thẻ KPI Dashboard:**
    - Cập nhật hàm `BobinRepository::getBobinStatusStats()` trả về chỉ số `$cancelled = (int)($rows['Cancelled'] ?? 0)`.
    - Cập nhật công thức tính `$realRolled` trong `listBobinDetailView.php` trừ đi số lượng Bobin đã hủy (`$statusCounts['Cancelled'] ?? 0`).
    - Bổ sung thẻ KPI bấm được cho "Đã hủy" (`.kpi-card.cancelled`) trong lưới KPI Dashboard hiển thị số lượng Bobin đã hủy và liên kết lọc nhanh `status=Cancelled`.
    - Bổ sung cột "Đã hủy" vào biểu đồ trạng thái (`$chartLabels`, `$chartSeries`) và cập nhật mảng màu ApexCharts tương ứng (`#ef4444`).
  - **3. Đa ngôn ngữ (`i18n.js`):**
    - Bổ sung `{ vi: "❌ Đã hủy", en: "❌ Scrapped", ja: "❌ 廃棄済" }`, `{ vi: "Đã hủy", en: "Scrapped", ja: "廃棄済" }`, `{ vi: "ĐÃ HỦY", en: "SCRAPPED", ja: "廃棄済" }` vào `VALUE_TRANSLATIONS` trong `public/assets/js/i18n.js`.
### TASK-045:
**Status:** DONE
- Tại trang danh sách Bobin (`app/views/listBobinDetailView.php`), tái định nghĩa lại hiển thị và lọc, đồng thời giả lập dữ liệu CSDL:
  - **1. Cập nhật Database giả lập trường dữ liệu 500 Bobin để lấy mẫu test:**
    - Bảo toàn nguyên vẹn các Bobin mẫu hiện có (`A0021..A0050`, `C0001`), bao gồm `A0028` (`Busy_Checked`, đủ thông tin đùn), `A0031` (`Rolled`, đủ thông tin đùn), `A0029` (`Rolled`, rỗng thông tin đùn) và `A0032` (`Rolled`, thiếu thông tin đùn).
    - Khởi tạo đồng bộ thêm 473 Bobin mới (`A0051..A0204`, `B0001..B0160`, `C0002..C0160`) trên cả 3 bảng `bobin_list_detail`, `bobin_list_general`, `bobin_history` với dữ liệu danh mục thực tế từ `product_list`, `material_lot_list`, `employee_list`, `rack_list`, `winding_machine_list`.
    - Tổng cộng có đúng **500 Bobin** có đầy đủ thông tin nhập từ nhóm đùn (`PL7-3`: 180, `PL4-7 (TU04.TU06)`: 160, `PL4-7 (TU08~)`: 160), phân bổ: `Rolled` (220), `Busy_Unchecked` (110), `Busy_Checked` (120), `Tồn line` (230), `Pending_Cancellation` (20), `Cancelled` (30) và `Bobin trống` (1,110 trên tổng dung lượng 1,360).
  - **2. Tại `kpi-grid`, đối với tất cả các trạng thái lọc (`kpi-card`), chỉ đếm những Bobin có đầy đủ thông tin nhập từ nhóm đùn:**
    - Bổ sung hàm `BobinRepository::getExtrusionCompleteCondition()` kiểm tra chặt chẽ các trường thông tin nhóm đùn (`products`, `extrusion_employee`, `material_lot`, `rack`, `extrusion_check`, `print_lot`, `length_m > 0`, `shift`, `extrusion_date`, `finish_time`).
    - Áp dụng điều kiện đầy đủ thông tin đùn vào `getBobinStatusStats()`, đồng thời cập nhật `listBobinDetailView.php` lấy trực tiếp số lượng `Rolled` thực tế có đủ thông tin đùn từ `$statusCounts['Rolled']` (thay vì công thức trừ ngược từ tổng dung lượng trước đây làm đếm nhầm cả Bobin trống), giữ nguyên công thức tính `Bobin Trống` (`$emptyBobins = max(0, $totalRealBobins - $statusCounts['Line'] - $statusCounts['Pending_Cancellation'])`).
    - Đảm bảo `A0028` (`Busy_Checked`, đủ thông tin đùn) được đếm vào "Đã QC" và "Tồn Line"; `A0031` (`Rolled`, đủ thông tin đùn) được đếm vào "Đã Cuộn"; `A0029` và `A0032` (`Rolled`, thiếu/rỗng thông tin đùn) không được liệt vào "Đã cuộn" mà chỉ thuộc "Bobin trống".
  - **3. Tại `filter-dashboard`, đối với tất cả các chức năng lọc, chỉ lọc những Bobin có thông tin đầy đủ từ nhóm đùn (riêng filter Bobin trống vẫn giữ nguyên chức năng):**
    - Cập nhật `getBobinListDetail()`, `countBobinListDetail()`, `getBobinListDetailForExport()` và các hàm truy vấn liên quan trong `BobinRepository.php` để mọi bộ lọc trạng thái (`all`, `Rolled`, `Busy_Unchecked`, `Busy_Checked`, `Line`, `Pending_Cancellation`, `Cancelled`), kích thước, loại Bobin, vị trí Rack và từ khóa chỉ trả về các Bobin có đầy đủ thông tin từ nhóm đùn, trong khi trạng thái `Empty` (Bobin trống) vẫn giữ nguyên logic.
### TASK-046:
**Status:** DONE
- **Completed:** 2026-10-18
- **Changes Made:**
  - **1. Điều chỉnh quyền hiển thị và thao tác Bobin tại trang "Điều chỉnh cuộn" (`windingEditBobinView.php`):**
    - Cập nhật `BobinRepository::getListDetailBobinsForWindingEdit()` và `BobinRepository::countDetailBobinsForWindingEdit()` để lọc chặt chẽ ở tầng Backend/SQL theo thông tin định danh của User đang đăng nhập (`JSON_UNQUOTE(JSON_EXTRACT(winding_employee, '$.employee_code')) = :winding_emp_code` hoặc `JSON_UNQUOTE(JSON_EXTRACT(winding_employee, '$.id')) = :winding_user_id`), đồng thời bỏ giới hạn `INTERVAL 3 DAY` để đảm bảo các Bobin đã xác nhận cuộn trước đó (như `A0022` của nhân viên Lê Thị Lụa `02224428`) luôn hiển thị đúng cho người đã nhập và ẩn hoàn toàn với mọi User khác.
    - Bổ sung phương thức kiểm tra quyền sở hữu `BobinRepository::isBobinWindingConfirmedByUser()` và `BobinServices::isBobinWindingConfirmedByUser()`, áp dụng chặn bảo mật cấp API (HTTP 403) trong `BobinController::updateWindingEditBobin()` và `BobinController::windingCancelBobin()` (khi `source === 'winding_edit'`).
  - **2. Bổ sung 3 bộ lọc nâng cao và thống kê động đồng bộ tại cả 4 trang quản lý Bobin (`Danh sách Bobin`, `Lịch sử Bobin`, `Danh sách chờ hủy`, `Danh sách đã hủy`):**
    - Mở rộng `BobinGetListDTO` với 3 thuộc tính `$employeeCode` (`employee_code`), `$productCode` (`product_code`), `$qcDefect` (`qc_defect`).
    - Xây dựng các hàm xử lý truy vấn SQL & thống kê tại `BobinRepository`: `normalizeEmployeeCodeFilter()`, `normalizeProductCodeFilter()`, `getQcInspectedCondition()`, `getSingleQcDefectNgExpr()` (NULL-safe với `COALESCE`), `getQcDefectFilterCondition()`, `appendAdvancedManagementFilters()`, `getDetailQcDefectStats()`, `getDetailProductCounts()`, `computeQcDefectStatsFromRows()`, `computeProductCountsFromRows()` và `getDetailBobinsForCancelled()`.
    - **2a. Filter theo mã số nhân viên (`employee_code`):** Ô nhập kèm Suggestion Dropdown từ danh mục nhân viên; truy vấn đồng thời trên cả **3 nhóm nhân viên** (`extrusion_employee -> $.employee_code` OR `winding_employee -> $.employee_code` OR `visual_inspection -> $.inspector_code`).
    - **2b. Filter theo mã sản phẩm (`product_code`) và thống kê số lượng Bobin:** Ô nhập kèm Suggestion Dropdown hiển thị số lượng Bobin của từng mã sản phẩm, banner thống kê nổi bật khi đang chọn 1 mã sản phẩm và dải chip thống kê phân bổ số lượng Bobin theo từng mã sản phẩm.
    - **2c. Filter theo tình trạng ngoại quan của nhóm QC (`qc_defect`) và thống kê số lượng:** Đồng bộ 8 trường ngoại quan QC (`gel`, `foreign_object`, `color_issue`, `print_quality`, `clump`, `scratch`, `streak`, `deformation`) cùng các bộ lọc tổng hợp (`all`, `ok_all` — Đạt 8/8 OK, `ng_any` — Có lỗi NG bất kỳ), hiển thị trực quan số lượng Bobin của từng tình trạng ngoại quan tương ứng với điều kiện lọc hiện tại.
    - Xây dựng component dùng chung `app/views/components/advancedBobinFilterAndStats.php` và stylesheet `public/assets/css/advancedBobinFilter.css`, tích hợp vào `listBobinDetailView.php`, `listBobinHistoryView.php`, `listPendingCancellationView.php` và trang mới `listCancelledBobinView.php` (`bobin/listCancelledBobinView`, đăng ký trên `Router.php`, `AuthHelper.php`, `sidebar.php`).
    - Cập nhật đầy đủ từ điển đa ngôn ngữ (`vi`, `en`, `ja`) trong `config/default_translations.json`.



### TASK-047:
**Status:** DONE
- **Completed:** 2026-10-18
- **Changes Made:**
  - **1. Bổ sung tính năng phân trang (50 mục/trang) cho 5 trang chưa phân trang, giữ nguyên hoàn toàn 4 trang đã phân trang (`qcEditBobinView`, `windingEditBobinView`, `windingView`, `listBobinDetailView`):**
    - **Danh sách điều chỉnh đùn (`extrusionEditBobinView.php`):** Cập nhật `BobinRepository::getListDetailBobinsForEditting()` hỗ trợ `LIMIT / OFFSET`, thêm `BobinRepository::countDetailBobinsForEditting()` & `BobinServices::countDetailBobinsForEditting()`, cập nhật `BobinController::extrusionEditBobinView()` và render thanh phân trang chuẩn kèm giữ nguyên toàn bộ bộ lọc tìm kiếm (`extrusionEditBobin.css`).
    - **Nhóm QC (`qcView.php`):** Cập nhật `BobinRepository::getDetailBobinsForQC()` và `BobinServices::getDetailBobinsForQC()` hỗ trợ phân trang `LIMIT / OFFSET`, kết nối với `countDetailBobinsForQC()` sẵn có trong `BobinController::listBobinView_QC()` và bổ sung thanh phân trang (`listBobin_QC.css`).
    - **Lịch sử Bobin (`listBobinHistoryView.php`):** Tính toán toàn bộ KPI, biểu đồ thống kê lý do hủy, hiệu suất máy cuộn, thống kê mã sản phẩm và ngoại quan QC trên toàn tập dữ liệu lọc (`$allBobins`), sau đó cắt trang 50 mục/trang (`$bobins`) để hiển thị bảng và thanh phân trang mà không ảnh hưởng đến số liệu thống kê hay xuất Excel.
    - **Danh sách chờ hủy (`listPendingCancellationView.php`) & Danh sách đã hủy (`listCancelledBobinView.php`):** Sửa lỗi truyền bí danh bảng (`'pnd'` / `'can'` -> `''`) trong `appendAdvancedManagementFilters()`, tính toán thống kê trên toàn tập dữ liệu lọc và áp dụng phân trang 50 mục/trang kèm thanh điều hướng giữ nguyên mọi tham số lọc (`advancedBobinFilter.css`).
  - **2. Điều chỉnh quyền hiển thị & thao tác Bobin theo người thực hiện tại "Điều chỉnh Đùn" và "Điều chỉnh QC" (đồng bộ với "Điều chỉnh Cuộn"):**
    - **Điều chỉnh Đùn:** Lọc mặc định theo `extrusion_employee.employee_code` / `extrusion_employee.id` của User đang đăng nhập và bỏ giới hạn `INTERVAL 3 DAY`; bổ sung `isBobinExtrusionCreatedByUser()` để chặn bảo mật cấp API (HTTP 403) trong `BobinController::extrusionUpdateBobin()` và `BobinController::extrusionDeleteBobin()`.
    - **Điều chỉnh QC:** Lọc mặc định theo `visual_inspection.inspector_code` / `visual_inspection.id` của User đang đăng nhập và bỏ giới hạn `INTERVAL 3 DAY`; bổ sung `isBobinQcInspectedByUser()` để chặn bảo mật cấp API (HTTP 403) trong `BobinController::updateQCEditBobin()` và `BobinController::qcCancelBobin()` (khi gửi từ `qc_edit`).
  - **3. Bổ sung 3 quyền "Xem & điều chỉnh toàn bộ Bobin" theo từng nhóm tại trang Phân quyền (`employeePermissionsView.php`, `AuthHelper.php`, `EmployeeController.php`):**
    - Thêm 3 quyền mới gắn ràng buộc `role_group`: `extrusion_edit_view_all` (chỉ áp dụng cho nhóm `extrusion` & `admin`), `qc_edit_view_all` (chỉ áp dụng cho nhóm `qc` & `admin`), `winding_edit_view_all` (chỉ áp dụng cho nhóm `winding` & `admin`).
    - Bổ sung `AuthHelper::canViewAllEditBobins($stage, $user)`: mặc định tài khoản `admin` có toàn quyền xem và điều chỉnh tất cả 3 trang Điều chỉnh (Đùn / QC / Cuộn); nhân viên được cấp quyền `*_edit_view_all` tương ứng với nhóm của mình sẽ xem và điều chỉnh được toàn bộ Bobin của trang điều chỉnh thuộc nhóm đó.
    - Cập nhật giao diện `employeePermissionsView.php` tự động khóa (disable) các quyền `*_edit_view_all` khác nhóm đối với nhân viên không phải `admin`, đồng thời kiểm tra chặt chẽ ở Backend `EmployeeController::updatePermissions()` và `AuthHelper::getUserPermissions()`.
    - Bổ sung từ khóa đa ngôn ngữ (`vi`, `en`, `ja`) vào `config/default_translations.json`.


### TASK-048:
**Status:** DONE
- **Completed:** 2026-10-19
- **Changes Made:**
  - **1. Thiết lập cấu hình và logic kiểm tra 4 mốc thời gian đăng xuất định kỳ cố định (`6:05 AM`, `2:05 PM`, `4:40 PM`, `10:05 PM`) cho toàn bộ User đã đăng nhập (`AuthHelper.php`, `AuthController.php`, `Router.php`):**
    - Bổ sung hằng số `AuthHelper::PERIODIC_LOGOUT_SCHEDULES` (`06:05`, `14:05`, `16:40`, `22:05`), `AuthHelper::PERIODIC_LOGOUT_COUNTDOWN_SECONDS = 60`, cùng 2 phương thức `AuthHelper::getNextPeriodicLogoutCheckpoint()` và `AuthHelper::checkPeriodicLogoutStatus()`.
    - Lưu dấu thời gian đăng nhập chuẩn `Asia/Ho_Chi_Minh` (`logged_at` và `logged_at_ts`) trong `AuthController::validateLogin()` và chuyển tiếp thông tin mốc đăng xuất (`logout_reason`, `cp`) trong `AuthController::logout()`.
    - Tích hợp kiểm tra bảo mật cấp Server trong `Router::run()`: tự động hủy phiên đăng nhập và chuyển hướng về trang Login nếu đã quá 1 phút kể từ mốc kiểm soát cố định, đồng thời chặn mọi request ghi dữ liệu (`POST` API) khi đang trong thời gian chờ xác nhận đăng xuất để đảm bảo User 2 không thể thao tác bằng tài khoản của User 1.
  - **2. Xây dựng Dialog Yêu cầu Đăng xuất Định kỳ kèm Counter đếm ngược 60 giây (`periodicLogoutModal.php`, `header.php`, `Controller.php`, `loginView.php`):**
    - Tạo component `app/views/components/periodicLogoutModal.php` tự động nhúng trên mọi trang khi User đã đăng nhập (qua `header.php` và `Controller::view()`).
    - Khi đến bất kỳ mốc thời gian nào trong 4 mốc (`6:05 AM`, `2:05 PM`, `4:40 PM`, `10:05 PM`), Dialog lập tức hiển thị nổi bật trên cùng màn hình (khóa đóng bằng phím `ESC` hoặc click ra ngoài), hiển thị thông tin tài khoản đang mở, mốc thời gian kiểm soát và **Counter đếm ngược 60 giây** (vòng tròn SVG tiến trình + số giây đếm ngược + thanh progress bar chuyển đỏ cảnh báo khi còn $\le 15$ giây).
    - Khi User nhấn **"Xác nhận đăng xuất ngay"** hoặc khi Counter đếm ngược về `0` (sau 1 phút không xác nhận), hệ thống lập tức thực hiện đăng xuất và hiển thị thông báo trạng thái tương ứng trên trang đăng nhập (`loginView.php`).
    - Cập nhật đầy đủ từ khóa đa ngôn ngữ (`vi`, `en`, `ja`) trong `config/default_translations.json`.


### TASK-049
**Status:** DONE
1. **Sửa lỗi hiển thị trùng lặp 2 bảng suggestion tại textfield "👤 Mã số nhân viên:":**
   - Đã loại bỏ thuộc tính `list="advEmployeeDatalist"` và phần tử `<datalist id="advEmployeeDatalist">` trong `app/views/components/advancedBobinFilterAndStats.php`, đồng thời loại bỏ `<datalist id="advProductDatalist">`.
   - Giờ chỉ còn menu gợi ý tuỳ chỉnh chuyên nghiệp (`#advEmployeeSuggestMenu` / `#advProductSuggestMenu`) điều khiển bằng JavaScript, khắc phục hoàn toàn hiện tượng cả browser native suggestion và custom dropdown cùng bật lên đồng thời.
2. **Sửa lỗi nút nhấn Export Excel:**
   - Khắc phục lỗi SQL cú pháp nghiêm trọng trong `BobinRepository::getBobinListDetailForExport()` (tiền tố bảng `'exp'` bị sai lệch so với alias `active_bobins`).
   - Bổ sung bộ lọc khoảng thời gian (`from_date`, `to_date`) và đồng bộ chuẩn hóa tham số lọc nâng cao.
   - Thêm cột và dữ liệu "Máy đùn" (`extrusion_machine`) vào file Excel/CSV xuất ra cho Lịch sử (`outputEnhancedCsvHistory`) và Chi tiết (`outputEnhancedCsvDetail`).
3. **Thêm nút Export Excel ở trang "Đã hủy" và "Chờ hủy":**
   - Đã triển khai action `exportPendingCancellationExcel()` và `exportCancelledExcel()` trong `app/controllers/BobinController.php`.
   - Đăng ký routing và cấp quyền bảo mật trong `app/core/Router.php` và `app/core/AuthHelper.php`.
   - Bổ sung nút "Xuất Excel" chuẩn giao diện hiện đại kèm icon SVG và đa ngôn ngữ (`data-i18n="btn_export_excel"`) trên cả 2 view `listPendingCancellationView.php` và `listCancelledBobinView.php`.
   - Tối ưu CSS `.btn-export` đồng bộ giao diện trong `public/assets/css/listPendingCancellation.css`.
4. **Tối ưu trang "Danh sách nhân viên" (`employeeListView.php`):**
   - Khắc phục lỗi `ReferenceError: filterByRole is not defined` khi nhấn vào các thẻ thống kê vai trò hoặc các mục chú giải của Donut Chart.
   - Bổ sung hàm `filterByRole(role)` và `applyEmployeeTableFilter()` lọc nhanh tức thì theo vai trò (`extrusion`, `qc`, `winding`, `admin`, `all`) kết hợp với ô tìm kiếm từ khóa và mã bộ phận.
   - Tự động đánh số lại STT (cột 1, 2, 3...) và cập nhật huy hiệu số lượng nhân viên hiển thị (`#empCounter`).
   - Đồng bộ trạng thái active của các stat-card và legend item với hiệu ứng nổi bật trong `public/assets/css/employeeList.css`.
   - Tự động cập nhật URL nút "📤 Xuất Excel" theo đúng tham số lọc hiện hành (`keyword`, `cost_center`, `role`) để xuất đúng tập dữ liệu đang lọc.
   - Chuẩn hóa toàn bộ URL với biến `$baseUrl` linh hoạt thay vì hardcode `/WEB_BOBIN/public`.
5. **Thêm trường "Máy đùn" (`extrusion_machine`) vào database và cập nhật toàn bộ dự án:**
   - **Database Migration:** Đã thêm cột `extrusion_machine VARCHAR(50) DEFAULT NULL AFTER extrusion_check` vào cả 2 bảng `bobin_list_detail` và `bobin_history`.
   - **DATABASE_SCHEMA.md:** Đã cập nhật định dạng schema mới nhất cho cả 2 bảng.
   - **DTOs & Entities:** Cập nhật `BobinCreateDTO`, `BobinExtUpdateDTO` (nhận diện `extrusion_machine` hoặc `machine`), và `BobinEntity` (`public string $extrusion_machine`).
   - **Services & Repositories:** Cập nhật `BobinServices` và `BobinRepository` (insert, update, copy history `SELECT ... FROM bobin_list_detail`, keyword search).
   - **Views & UI hiển thị:** Đã hiển thị trường "Máy đùn" trên toàn bộ các trang:
     - `extrusionEditBobinView.php` (nhập/chỉnh sửa máy đùn) + `public/assets/js/Extrusion/edit_submit.js`
     - `listBobinDetailView.php` (thẻ chi tiết Bobin)
     - `listBobinHistoryView.php` (thẻ chi tiết lịch sử Bobin)
     - `listPendingCancellationView.php` (danh sách chờ hủy)
     - `listCancelledBobinView.php` (danh sách đã hủy)
     - `qcView.php` & `qcEditBobinView.php` (giao diện QC và Điều chỉnh QC)
     - `windingView.php` & `windingEditBobinView.php` (giao diện Cuộn và Điều chỉnh Cuộn)
   - **Đa ngôn ngữ (i18n):** Bổ sung đầy đủ bản dịch 3 thứ tiếng (`vi`, `en`, `ja`) trong `config/default_translations.json`.

### TASK-050
**Status:** DONE
- Đọc lại `AI_preference/AI_CONTEXT.md` và kiểm tra toàn bộ các yêu cầu chức năng chưa thực thi. Đã báo cáo đầy đủ cho người dùng và nhận chỉ đạo triển khai các mục 1, 2, 3:
  1. **Bổ sung bộ lọc "Mã máy đùn" và "Mã sản phẩm" cho trang QC (`bobin/qcView`):**
     - Mở rộng `BobinGetListDTO` hỗ trợ thuộc tính `$machine`.
     - Cập nhật `BobinServices::getDetailBobinsForQC()` và `countDetailBobinsForQC()`.
     - Cập nhật `BobinRepository::getDetailBobinsForQC()` và `countDetailBobinsForQC()` lọc chính xác theo `extrusion_machine` và `products.product_code`, đồng thời hỗ trợ tìm kiếm máy đùn trong từ khóa (`keyword`).
     - Bổ sung giao diện select máy đùn (`#filterMachine`), input mã sản phẩm (`#filterProductCode` kèm datalist gợi ý), nút xóa lọc (`.btn-reset-filter`) và style CSS responsive trên `qcView.php` & `listBobin_QC.css`.
     - Bổ sung các từ khóa dịch thuật `opt_all_machines`, `ph_product_code` vào `config/default_translations.json`.
  2. **Mở rộng phạm vi lấy Bobin dang dở ở Nhóm Cuộn thành 7 ngày:**
     - Cập nhật trong `BobinRepository::getDetailBobinsForWinding()` và `countDetailBobinsForWinding()` chuyển từ `INTERVAL 24 HOUR` thành `INTERVAL 7 DAY` theo đúng quy định tại Mục 2.3 của `AI_CONTEXT.md`.
  3. **Bổ sung cập nhật `updated_time = NOW()` khi điều chỉnh thông tin Bobin:**
     - Cập nhật các câu lệnh `UPDATE` trong `extUpdateBobinDetail()`, `extUpdateBobinGeneral()`, và `extUpdateBobinHistory()` ở công đoạn Đùn.
     - Cập nhật các câu lệnh `UPDATE` trong `adminUpdateQCBobin()` ở công đoạn QC.
     - Cập nhật các câu lệnh `UPDATE` trong `adminUpdateWindingBobin()` ở công đoạn Cuộn.
     - Đảm bảo toàn bộ các thay đổi điều chỉnh đều lưu vết thời gian cập nhật mới nhất theo đúng yêu cầu bắt buộc của `AI_CONTEXT.md`.

### TASK-051
**Status:** DONE
1. Kiểm tra lại các trang danh sách, QC, Cuộn: "Loại Bobin" VÀ "Mã sản phẩm" chưa được làm nổi bật dựa trên giá trị -> **DONE** (Áp dụng palette 10 màu giá trị cố định và badge phân loại chuẩn trên tất cả các trang: Danh sách, Lịch sử, QC, Cuộn, Chờ hủy, Đã hủy).
2. Thêm chức năng filter tương tự ở mục 1 cho trang thông tin Cuộn -> **DONE** (Trang Cuộn `windingView` đã có bộ lọc: máy đùn, máy cuộn, mã sản phẩm, loại Bobin, tích hợp nút Quét QR, Reset bộ lọc và active filter chips).
3. Thêm chức năng lọc theo máy đùn, máy cuộn cho trang Danh sách và Lịch sử -> **DONE** (Bổ sung bộ lọc máy đùn, máy cuộn xuyên suốt DTO, Repository, Service và Controller cho các trang `listBobinDetailView`, `listBobinHistoryView`, `listPendingCancellationView`, `listCancelledBobinView`).
4. Update UI/UX Filter của trang QC -> **USER SELF-ADJUST** (Người dùng tự điều chỉnh câu chữ nhầm lẫn; hệ thống đã sẵn sàng khung giao diện & palette đồng bộ).
### TASK-052
**Status:** DONE
1. Đang có lỗi ở trang qcView.php -> **DONE** (Khắc phục Fatal Error gọi hàm `buildQcFilterUrl()` tại các chip active filter và xóa khối CSS dư thừa gây mất cân bằng ngoặc `{}` tại `public/assets/css/listBobin_QC.css`).
2. Giá trị Loại Bobin vẫn chưa được làm nổi bật ở các trang, giao diện không hiển thị màu -> **DONE** (Phân loại chi tiết và định nghĩa bộ màu nhận diện nổi bật, tương phản cao đạt chuẩn WCAG AA cho toàn bộ các loại Bobin: Sản xuất, Bù, Điều chỉnh CP và 8 loại Điều chỉnh ngoại quan. Bổ sung helper `getBobinTypeBadgeStyle()` sinh inline fallback style cùng đồng bộ CSS trên toàn bộ 6 trang: Danh sách, Lịch sử, QC, Cuộn, Chờ hủy, Đã hủy).
3. Đề xuất thêm biểu đồ ở trang danh sách Bobin và trang lịch sử Bobin, tiêu đề là: Thống kê ngoại quan Bobin -> **DONE** (Xây dựng panel biểu đồ "Thống kê ngoại quan Bobin" với BaseChart/ApexCharts thể hiện 8 tiêu chí lỗi ngoại quan từ `qcDefectStats`, nhãn dữ liệu tỷ lệ %, cùng bộ badge thống kê Đạt/Có lỗi trên header, hỗ trợ đầy đủ 3 ngôn ngữ `vi`, `en`, `ja`).
## 🟡 RULES

### TASK-053
**Status:** TODO
Thêm trường dữ liệu "Hình ảnh biểu đồ" cho Bobin, cần đồng bộ dữ liệu ở tất cả các trang, trường này có kiểu dữ liệu sẽ là hình ảnh. 
Quy trình như sau:
+ Tính năng thêm/xóa/đổi ảnh được dùng cho cả 3 nhóm Đùn/QC/Cuộn
+ Nhóm Đùn: Nhập thông tin Bobin, có thể thêm hình ảnh biểu đồ dữ liệu đo hoặc không (Biểu đồ riêng của nhóm đùn). Tại trang điều chỉnh của nhóm đùn, nhóm đùn cũng có thể bổ sung/xóa/thay đổi ảnh tương ứng. 
+ Nhóm QC: Có thể bổ sung hình ảnh ngoại quan nếu cần thiết. Tại trang điều chỉnh của nhóm QC, nhóm QC cũng có thể bổ sung/xóa/ thay đổi ảnh tương ứng
+ Nhóm Cuộn: có thể thêm hình ảnh biểu đồ dữ liệu đo hoặc không (Biểu đồ riêng của nhóm Cuộn). Tại trang điều chỉnh của nhóm cuộn, nhóm cuộn cũng có thể bổ sung/xóa/thay đổi ảnh tương ứng. 
+ 3 loại hình ảnh trên của 3 nhóm là khác nhau, cần phân biệt rõ rệt theo Nhóm mà user thuộc về để phân quyền
+ Tại toàn bộ trang danh sách, khi render danh sách, không cần render hình ảnh ngay lặp tức. Tương ứng với dữ liệu từng nhóm sẽ có 1 nút "Xem hình ảnh", khi nhấn vào nút này thì hình ảnh sẽ hiển thị ra như dạng Dialog giữa màn hình.
+ Hình ảnh có thể được thêm vào bằng cách chụp/lấy từ thư viện (Đối với điện thoại), hoặc chọn mục hình ảnh (Đối với máy tính desktop)
Toàn bộ dưới đây là DEVELOPMENT RULES phải tuân thủ:

## 0. Security

- Đảm bảo toàn bộ project của tôi có thể hoạt động trong môi trường local mà không phụ thuộc vào internet
- Cố gắng hạn chế tối đa phụ thuộc vào source thư viện trên internet
- Không được phép download thư viện trên internet, chỉ được phép nói ra các thư viện cần download đê tôi thực hiện download thủ công

## 1. Database

- Không tự ý thay đổi **database schema** nếu TASK hiện tại không thực sự yêu cầu.
- Không tự ý thêm, xóa, đổi tên hoặc thay đổi kiểu dữ liệu của table/column/index/constraint.
- Nếu nhận thấy cần thay đổi database để thực hiện TASK tốt hơn, phải:
  1. Giải thích lý do.
  2. Đánh giá ảnh hưởng.
  3. Đề xuất thay đổi.
  4. Chờ xác nhận trước khi thực hiện.
- Không làm mất hoặc tự ý chỉnh sửa dữ liệu hiện có.

## 2. API & Backward Compatibility

- Không xóa API/endpoint hiện tại nếu không được yêu cầu.
- Không thay đổi URL, request format, response structure hoặc behavior của API hiện tại nếu TASK không yêu cầu.
- Khi cần mở rộng chức năng, ưu tiên mở rộng API hiện tại hoặc thêm API mới mà không phá vỡ compatibility.
- Không thay đổi tên các field, parameter, key hoặc property hiện tại nếu không được yêu cầu.
- Nếu thay đổi có nguy cơ làm hỏng code/frontend đang sử dụng API, phải báo cáo trước khi thực hiện.

## 3. Phân tích trước khi sửa code

Trước khi chỉnh sửa code:

1. Đọc TASK hiện tại.
2. Đọc các file trực tiếp liên quan.
3. Kiểm tra các function/class/module/API/database dependency liên quan.
4. Kiểm tra nơi function hoặc API đó đang được sử dụng.
5. Xác định phạm vi ảnh hưởng của thay đổi.
6. Ưu tiên tái sử dụng architecture, component, helper, function và convention hiện có.

Không sửa code chỉ dựa trên một đoạn code riêng lẻ khi chức năng đó có dependency với phần khác của hệ thống.

## 4. Task Scope

- Chỉ sửa những gì cần thiết để hoàn thành TASK hiện tại.
- Không tự ý refactor, rewrite hoặc "clean up" các phần không liên quan.
- Nếu phát hiện bug, security issue, technical debt hoặc vấn đề khác ngoài phạm vi TASK:
  - Không tự ý sửa.
  - Ghi nhận và báo cáo cho người dùng.
  - Chỉ sửa khi vấn đề đó trực tiếp ngăn TASK hiện tại hoạt động hoặc được người dùng cho phép.
- Không tự ý tạo thêm chức năng mà TASK không yêu cầu.

## 5. Translation / i18n

Project hỗ trợ:

- Tiếng Việt (`vi`)
- English (`en`)
- 日本語 (`ja`)

Khi tạo hoặc sửa UI:

- Không hard-code text hiển thị cho người dùng nếu text đó thuộc hệ thống translation.
- Mọi text UI mới phải được thêm đầy đủ vào hệ thống translation cho cả 3 ngôn ngữ.
- Bao gồm nhưng không giới hạn:
  - Title
  - Label
  - Button
  - Placeholder
  - Tooltip
  - Modal/Dialog
  - Toast
  - Alert
  - Confirmation message
  - Validation message
  - Error message
  - Empty state
  - Table header
  - Status text
  - Menu/Navigation
- Ưu tiên tái sử dụng translation key hiện có nếu cùng ý nghĩa.
- Không tạo duplicate translation key nếu không cần thiết.
- Sau khi hoàn thành TASK có thay đổi UI, kiểm tra lại cả `vi`, `en` và `ja` để đảm bảo không còn text bị hard-code hoặc thiếu translation.

## 6. UI/UX

Khi TASK liên quan đến giao diện:

- Tuân theo design system, layout, spacing, typography, component và interaction pattern hiện tại của project.
- Ưu tiên tái sử dụng component/style hiện có.
- Không tạo duplicate UI/component/CSS nếu chức năng tương đương đã tồn tại.
- Không tự ý redesign toàn bộ giao diện ngoài phạm vi TASK.
- Có thể cải thiện UI/UX trong phạm vi component hoặc trang đang được TASK tác động nếu:
  - Không làm thay đổi behavior ngoài yêu cầu.
  - Không phá vỡ giao diện hiện tại.
  - Không tạo dependency không cần thiết.
- Đảm bảo giao diện dễ hiểu, nhất quán và hạn chế thao tác thừa cho người dùng.

## 7. Code Quality

- Tuân theo architecture và coding convention hiện tại của project.
- Ưu tiên code đơn giản, dễ đọc và dễ maintain.
- Tránh duplicate logic.
- Ưu tiên tái sử dụng function/helper/component hiện có.
- Không tạo abstraction mới nếu không mang lại lợi ích rõ ràng.
- Không thêm dependency/library mới nếu chức năng hiện tại có thể giải quyết hợp lý mà không cần dependency đó.
- Không để lại debug code như `console.log`, `var_dump`, `print_r` hoặc code test tạm thời sau khi hoàn thành, trừ khi project chủ động sử dụng chúng.

## 8. Testing & Verification

Sau khi sửa code:

1. Kiểm tra syntax/error cơ bản.
2. Test luồng chính của chức năng.
3. Test các edge case hợp lý liên quan trực tiếp đến TASK.
4. Test trường hợp dữ liệu không hợp lệ hoặc thiếu dữ liệu nếu có.
5. Kiểm tra chức năng cũ có liên quan để tránh regression.
6. Nếu có thay đổi UI, kiểm tra translation `vi`, `en`, `ja`.
7. Kiểm tra console/server error nếu có thể.
8. Không tuyên bố một test đã PASS nếu test đó chưa thực sự được thực hiện.

Nếu môi trường hiện tại không cho phép thực hiện một test nào đó:

- Không giả định rằng test đã thành công.
- Báo rõ test nào đã thực hiện và test nào chưa thể thực hiện.

## 9. Không che giấu lỗi

- Không xử lý lỗi bằng cách đơn giản loại bỏ error message hoặc validation.
- Không dùng workaround chỉ để làm cho TASK có vẻ hoạt động nếu nguyên nhân gốc chưa được xử lý.
- Nếu chỉ có thể sử dụng workaround, phải giải thích rõ lý do và ảnh hưởng.

## 10. Sau khi hoàn thành TASK

Phải cung cấp báo cáo gồm:

### Files Changed

Danh sách file đã thêm/sửa/xóa và mục đích của từng file.

### Functions / Components Changed

Danh sách function, method, class, component hoặc API đã thay đổi.

### Changes

Tóm tắt những gì đã thực hiện.

### Impact

Cho biết thay đổi có ảnh hưởng đến chức năng khác hay không.

### Database

Xác nhận database schema có thay đổi hay không.

### API

Xác nhận API/interface hiện tại có thay đổi hay không.

### Translation

Nếu có thay đổi UI, xác nhận `vi`, `en`, `ja` đã được cập nhật và kiểm tra.

### Testing

Liệt kê:

- Test đã thực hiện.
- Kết quả.
- Test chưa thể thực hiện (nếu có).

### Additional Issues

Liệt kê các vấn đề ngoài phạm vi TASK phát hiện trong quá trình làm việc nhưng chưa sửa.
