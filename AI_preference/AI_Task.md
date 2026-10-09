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
## 🟡 RULES

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
