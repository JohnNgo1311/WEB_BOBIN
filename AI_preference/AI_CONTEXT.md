# AI_CONTEXT.md - Tổng Quan & Hướng Dẫn Vận Hành Dự Án WEB_BOBIN

Tài liệu này cung cấp toàn bộ bức tranh kiến trúc, luồng nghiệp vụ nhà máy, quy tắc code và các quy chuẩn quan trọng của dự án **WEB_BOBIN** nhằm giúp AI nắm bắt tức thì bối cảnh làm việc mà không cần phân tích lại từ đầu.

---

## 1. Tổng Quan Dự Án & Công Nghệ

- **Mục đích:** Hệ thống Web App quản lý Bobin trong quá trình sản xuất trong nhà xưởng (Nhà xưởng đùn nhựa - SMC Factory 2).
- **Môi trường hoạt động:** Chạy Local Intranet / Server XAMPP (Apache + MariaDB/MySQL + PHP 8.2+).
- **Kiến trúc phần mềm:** Mô hình MVC (Model - View - Controller / Repository - Service pattern thuần, không dùng framework nặng như Laravel/Symfony).
  - **Routing:** Front Controller qua `public/index.php?url=controller/method`. Router phân giải request và mapping controller.
  - **Database Access:** Singleton PDO qua `Database::getInstance()->pdo()`.
  - **i18n (Đa ngôn ngữ):** Hỗ trợ 3 ngôn ngữ: Tiếng Việt (`vi`), Tiếng Anh (`en`), Tiếng Nhật (`ja`).
    - Backend: `Language::getInstance()`, helper `__('key')`.
    - Frontend: `public/assets/js/i18n.js` với attribute `data-i18n="key"` và hàm `window.t('key')`.
  - **Session & Quyền hạn (Roles):**
    - `extrusion`: Nhân viên nhóm Đùn.
    - `qc`: Nhân viên kiểm tra chất lượng (QC).
    - `winding`: Nhân viên nhóm Cuộn.
    - `admin`: Quản trị viên (quản lý nhân viên, cấu hình hệ thống).

---

## 2. Quy Trình Vận Hành Sản Xuất (Bobin Lifecycle)
Quy trình sản xuất Bobin trải qua 4 công đoạn cốt lõi theo thứ tự:
A. Nhóm Đùn sản xuất ra Bobin => Quét mã QR định danh của Bobin Tiến hành nhập thông tin Bobin theo kế hoạch sản xuất => Trạng thái Bobin được cập nhật thành [BUSY_UNCHECKED] Đưa Bbin lên Rack để nhóm QC tiến hành kiểm tra
B. Nhóm QC đi đến vị trí Rack tương ứng, lần lượt quét mã QR định danh của Bobin trên Rack => Kiểm tra ngoại quan, phán định tình trạng Bobin
  + Nếu kết quả phán định tổng thể là OK (Có thể có hạng mục NG nhưng vẫn cho phép nhóm cuộn lấy Bobin từ Rack xuống để sản xuất) => Trạng thái Bobin được cập nhật thành [BUSY_CHECKED]
  + Nếu kết quả phán định tổng thể là NG (Nhóm cuộn không được phép lấy Bobin từ Rack xuống để sản xuất) => Bobin được đưa vào danh sách chờ hủy => Trạng thái Bobin được cập nhật thành [PENDING_CANCELlATION]
C. Nhóm Cuộn lấy những Bobin ở trạng thái [BUSY_CHECKED] ra khỏi Rack để tiến hành sản xuất:
  + Trước khi sản xuất, Bobin được kiểm tra có rò khí hay không? Nếu có thì quét mã QR định danh của Bobin tương ứng => Đưa Bobin vào danh sách chờ hủy => Trạng thái Bobin được cập nhật thành [PENDING_CANCELLATION]. Ngược lại, nếu kiểm tra rò khí thành công thì quét mã QR định danh Bobin => Cập nhật trạng thái Bobin [ROLLED].
  + Trong quá trình sản xuất: Nếu các lỗi về đường kính, ngoại quan của ống trên Bobin phát sinh quá nhiều => Người vận hành quét mã QR định danh Bobin để tiến hành chỉnh sửa trạng thái BOBIN từ [ROLLED] thành [PENDING_CANCELLATION]
D. Admin dựa trên danh sách Bobin đang ở trạng thái [PENDING_CANCELLATION] => Tiến hành trao đổi thông tin giữa các bên tại xưởng để đưa ra quyết định cuối cùng có đem Bobin đi hủy hay không?
   + Nếu có, trạng Bobin được cập nhật thành [CANCELLED]
   + Nếu không, trạng thái Bobin sẽ được đưa về trạng thái trước khi [PENDING_CANCELLATION]
### Chi tiết các công đoạn:
 
1. **Nhóm Đùn (Extrusion):**
   - **Tạo mới Bobin (`bobin/extrusionView`):**
     - Nhân viên nhập mã định danh Bobin (`bobin_identification_code`), mã sản phẩm, mã chỉ thị sản xuất, lot vật liệu, lot in, chiều dài, ca làm việc, ngày đùn, thời gian hoàn thành (`finish_time`) Bobin, vị trí Rack, và các tiêu chí Đùn tự kiểm tra(`extrusion_check`: đường kính, gel, dị vật, màu, chữ in, vón cục, trầy, xước, biến dạng).
     - **Quy tắc tạo `bobin_key_code`:** Được sinh tự động từ mã định danh và thời gian hoàn thành (`finish_time`) Bobin:
       $$\text{bobin\_key\_code} = \text{bobin\_identification\_code} + \text{_} + Y\_m\_d\_H\_i\_s$$
       _(Ví dụ: `A0001_2026_10_04_18_29_44`)_.
     - Dữ liệu được ghi vào `bobin_list_detail`, `bobin_list_general` và đồng thời snapshot một dòng mới vào `bobin_history`.
     - Trạng thái ban đầu: `Busy_Unchecked`.
   - **Điều chỉnh thông tin Bobin (`bobin/extrusionEditBobinView`):**
     - **QUY TẮC ĐẶC BIỆT (Req VI & VII):**
       - Nhân viên đùn chỉ xem được danh sách Bobin tương ứng do người đó nhập trước đó, dựa trên trường `extrusion_employee` (employee_code) để tránh người A điều chỉnh thông tin Bobin do người B nhập. (Trừ trường hợp được phân quyền xem full)
       - Khi nhân viên Đùn sửa đổi thông tin Bobin: Chỉ thực hiện `UPDATE` trên `bobin_list_detail`, `bobin_list_general` và `UPDATE` trực tiếp trên dòng tương ứng trong `bobin_history` theo `bobin_key_code`.
       - **KHÔNG ĐƯỢC INSERT/POST** thêm dòng mới vào `bobin_history`.
       - **PHẢI CẬP NHẬT** cột `updated_time` khi có bất kì thay đổi gì.
       -  **PHẢI CẬP NHẬT** Lịch sử điều chỉnh Bobin trong `bobin_history`

2. **Nhóm Kiểm Tra Chất Lượng (QC Check):**
   - Xem danh sách các Bobin đang ở trạng thái `Busy_Unchecked` (`bobin/qcView`).
   - Có chức năng filter danh sách này theo
   + Mã máy đùn (Ví dụ: PL08)
   + Mã sản phẩm (Ví dụ: TU0425B-1M2)
   - Kiểm tra ngoại quan các lỗi: Gel, Dị vật, Lỗi màu, Chữ in, Trầy, Vón cục, Xước, Biến dạng.
   - Nhập mã nhân viên QC (`inspector_code`), tên (Tự động) và thời điểm kiểm tra (Tự động).
   - Khi hoàn thành kiểm tra đạt $\rightarrow$ Chuyển sang `Busy_Checked`. Ghi một dòng lịch sử mới vào `bobin_history` với `updated_time = NOW()`.
   - Nếu phạn định muốn hủy $\rightarrow$ Chuyển trạng thái sang `Pending_Cancellation`.
  - **Điều chỉnh thông tin Bobin (`bobin/qcEditBobinView`):**
   - Nhân viên QC chỉ xem được danh sách Bobin tương ứng do người đó nhập trước đó, dựa trên trường `inspector_code`để tránh người A điều chỉnh thông tin Bobin do người B nhập. (Trừ trường hợp được phân quyền xem full)
   - Khi nhân viên QC sửa đổi thông tin Bobin: Chỉ thực hiện `UPDATE` trên `bobin_list_detail`, `bobin_list_general` và `UPDATE` trực tiếp trên dòng tương ứng trong `bobin_history` theo `bobin_key_code`.
       - **KHÔNG ĐƯỢC INSERT/POST** thêm dòng mới vào `bobin_history`.
       - **PHẢI CẬP NHẬT** cột `updated_time` khi có bất kì thay đổi gì.
       - **PHẢI CẬP NHẬT** Lịch sử điều chỉnh Bobin trong `bobin_history`
       
3. **Nhóm Cuộn (Winding):**
   - Nhận các Bobin đã qua QC (`Busy_Checked`) tại trang `bobin/windingView` và các Bobin đang chạy dang dở trước đó (Trong phạm vi 7 ngày, miễn là còn ở trạng thái `ROLLED`)
   - Chọn máy cuộn (`winding_machine`), mã nhân viên cuộn (`winding_employee`), kết quả test thông khí (`flow_test_result`: 'Thành công' / 'Thất bại'), ghi chú cuộn.
   - Xác nhận hoàn thành $\rightarrow$ Chuyển sang trạng thái `Rolled`. Ghi một dòng lịch sử mới vào `bobin_history` với `updated_time = NOW()`.
  - **Điều chỉnh thông tin Bobin (`bobin/WindingEditBobinView`):**
   - Nhân viên cuộn chỉ xem được danh sách Bobin tương ứng do người đó nhập trước đó, dựa trên trường `winding_employee`  để tránh người A điều chỉnh thông tin Bobin do người B nhập. (Trừ trường hợp được phân quyền xem full)
   - Khi nhân viên cuộn sửa đổi thông tin Bobin: Chỉ thực hiện `UPDATE` trên `bobin_list_detail`, `bobin_list_general` và `UPDATE` trực tiếp trên dòng tương ứng trong `bobin_history` theo `bobin_key_code`.
       - **KHÔNG ĐƯỢC INSERT/POST** thêm dòng mới vào `bobin_history`.
       - **PHẢI CẬP NHẬT** cột `updated_time` khi có bất kì thay đổi gì.
       - **PHẢI CẬP NHẬT** Lịch sử điều chỉnh Bobin trong `bobin_history`

4. **Quản Lý Chờ Hủy (Pending Cancellation & Cancellation):**
   - Bobin ở bất kỳ giai đoạn nào nếu bị lỗi hỏng có thể yêu cầu hủy $\rightarrow$ trạng thái `Pending_Cancellation`.
   - Số lượng chờ hủy hiển thị qua badge `badge-pending-count` trên thanh menu bar cho mọi trang.
   - Trang `bobin/listPendingCancellationView` cho phép duyệt chuyển sang `Cancelled` (Đã hủy) hoặc khôi phục lại trạng thái trước đó. (Tùy thuộc vào phân quyền của user mới được chuyển sang `Cancelled`)
---

## 3. Kiến Trúc Trang Tra Cứu & Báo Cáo

### A. Trang Danh Sách Bobin Hiện Tại (`bobin/listBobinDetailView`):

- Hiển thị danh sách Bobin đang lưu hành (tham chiếu từ bảng `bobin_list_detail`).
- Hỗ trợ lọc theo:
  - Trạng thái (`all`, `Rolled`, `Line` [Busy_Unchecked + Busy_Checked], `Busy_Unchecked`, `Busy_Checked`, `Pending_Cancellation`, `Cancelled`).
  - Kích thước Bobin (`bobin_size`: `PL7-3`, `PL4-7 (TU08~)`, `PL4-7 (TU04.TU06)`).
  - Loại Bobin (`bobin_type`).
  - Vị trí Rack: Hỗ trợ lọc theo khu vực Xưởng/Tầng:
    - `B021` $\rightarrow$ Xưởng 2, tầng 1 (`Rack_B021_%`)
    - `B022` $\rightarrow$ Xưởng 2, tầng 2 (`Rack_B022_%`)
    - `B031` $\rightarrow$ Xưởng 3, tầng 1 (`Rack_B031_%`)
    - `B032` $\rightarrow$ Xưởng 3, tầng 2 (`Rack_B032_%`)
    - Hoặc chọn chính xác từng mã Rack cụ thể.
  - Mã số nhân viên
  - Mã sản phẩm
  - Tình trạng ngoại quan của QC
- **Trình bày trực quan, tối ưu UI/UX, hạn chế màu sắc chói 3 cột kiểm soát (Pipeline Inspection Grid):**
  - **🏭 Đùn Check:** Hiển thị đủ tiêu chí. Nếu chưa có dữ liệu $\rightarrow$ Hiển thị `⏳ Chưa có dữ liệu kiểm tra Đùn`.
  - **🛡️ QC Check:** Hiển thị mã NV QC, thời gian, tiêu chí lỗi. Nếu chưa có $\rightarrow$ Hiển thị `⏳ Chưa có dữ liệu kiểm tra QC`.
  - **📍 Thông tin cuộn:** Hiển thị máy cuộn, NV cuộn, test thông khí. Nếu chưa có $\rightarrow$ Hiển thị `⏳ Chưa có dữ liệu thông tin cuộn`.

### B. Trang Lịch Sử Hoạt Động Bobin (`bobin/listBobinHistoryView`):

- Truy vấn toàn bộ dòng thời gian luân chuyển từ bảng `bobin_history`.
- Mặc định khi vào trang: lọc **7 ngày gần nhất** theo `updated_time`.
- Các bộ lọc nghiệp vụ chuyên sâu:
  - **ĐÃ ĐÙN:** Lọc các Bobin có `updated_time` và thời điểm trong `bobin_key_code` thuộc khoảng thời gian chọn. Nếu cùng `bobin_key_code` lấy bản ghi có `updated_time` mới nhất. Các Bobin trong danh sách độc nhất theo `bobin_key_code`.
  - **CHƯA KT QC:** Bobin ở trạng thái `Busy_Unchecked` tính đến mốc thời gian lọc. Nếu cùng `bobin_key_code` lấy bản ghi có `updated_time` mới nhất. Các Bobin trong danh sách độc nhất theo `bobin_key_code`.
  - **ĐÃ KT QC:** Tách biệt dựa trên thời điểm trong `bobin_key_code`: _Đùn trong khoảng thời gian chọn_ và _Đùn trước đó_. Nếu cùng `bobin_key_code` lấy bản ghi có `updated_time` mới nhất. 
  - **ĐÃ CUỘN:** Bobin ở trạng thái `Rolled`.
  - **CHỜ HỦY & ĐÃ HỦY:** Tách biệt KPI và danh sách trong kỳ / trước kỳ.
- Đảm bảo công thức bảo toàn sản lượng giữa các công đoạn:
  $$\text{ĐÃ ĐÙN} = \text{ĐÃ KT QC (trong kỳ)} + \text{CHƯA KT QC} + \text{ĐÃ CUỘN} + \text{CHỜ HỦY (trong kỳ)} - \text{ĐÃ HỦY (trong kỳ)}$$
- Hiển thị trực quan 3 cột kiểm soát với cơ chế fallback tương tự trang danh sách.

---

## 4. Quản Lý Nhân Viên (`employee/*`)
- Trang quản trị danh sách nhân viên tối ưu UX/UI:
  - Biểu đồ phân bổ vai trò thuần Inline SVG Donut Chart (không phụ thuộc thư viện ngoài).
  - Thẻ thống kê nhân viên (Tổng, Đùn, QC, Cuộn, Admin).
  - Tự động sinh tài khoản khi tạo nhân viên: `username` = Mã nhân viên, mật khẩu mặc định `123` (băm bằng `password_hash`), cờ `is_first_login = 1`.
  - Hỗ trợ Import/Export CSV UTF-8 tương thích Microsoft Excel. Format file excel đẹp, 1 dòng là tiêu đề của từng mục, các dòng phía dưới là dữ liệu
---

## 5. Quy Tắc Lập Trình Bắt Buộc Đối Với AI
1. **Bảo toàn dữ liệu:**
   - Tuyệt đối không xóa dữ liệu sản xuất thực tế.
2. **Đa ngôn ngữ (i18n):**
   - Mọi chuỗi ký tự hiển thị trên UI phải có `data-i18n="key"` trên HTML hoặc dùng helper PHP `__('key')`.
   - Khi thêm chuỗi mới, bắt buộc khai báo đầy đủ cả 3 ngôn ngữ (`vi`, `en`, `ja`) trong:
     - `public/assets/js/i18n.js` (Dictionary frontend + DOM translation mappings).
     - `app/core/Language.php` (Dictionary backend).
3. **Kiểm tra cú pháp PHP (Linting):**
   - Sau bất kỳ thao tác chỉnh sửa file PHP nào, bắt buộc chạy kiểm tra cú pháp:
     `& "c:\xampp\php\php.exe" -l <path-to-file>`
4. **Được quyền sử dụng thư viện bên ngoài**: Tuy nhiên sau khi download phải lặp tức chuyển hóa về dạng local để tuyệt đối không còn phụ thuộc vào internet.
