# CHANGELOG.md - Nhật Ký Những Thay Đổi Quan Trọng

Toàn bộ các cập nhật lớn, sửa lỗi logic, tái cấu trúc mã nguồn và cải tiến UX/UI được ghi nhận tuần tự theo thời gian tại đây.

## [TASK-054] - 2026-10-28 - Cải Tiến Bổ Sung / Thay Đổi / Xóa Hình Ảnh Biểu Đồ Nhóm Đùn Cho Bobin Đã Qua QC & Cuộn (Cô Lập Tuyệt Đối Dữ Liệu Nghiệp Vụ)

### 1. Bối cảnh & Yêu cầu:
- Cho phép nhân sự Nhóm Đùn (`extrusion` hoặc `admin` có quyền `extrusion_edit`) thực hiện thêm / đổi / xóa ảnh biểu đồ dữ liệu đo (`extrusion_chart_image`) ngay cả khi Bobin đã được Nhóm QC (`Busy_Checked`) hoặc Nhóm Cuộn (`Rolled`) xác nhận.
- Cô lập tuyệt đối phạm vi thao tác: chỉ thay đổi duy nhất dữ liệu hình ảnh `extrusion_chart_image` (và ghi vết kiểm toán `update_history`), tuyệt đối không chỉnh sửa, ghi đè hay xóa bất kỳ trường nghiệp vụ nào khác, không thay đổi trạng thái Bobin (`bobin_current_status`, `previous_status`), không chạm thông tin QC, thông tin Cuộn hay `updated_time`.
- Nghiêm cấm bổ sung/thay đổi hình ảnh đối với các Bobin ở trạng thái chờ hủy (`Pending_Cancellation`) hoặc đã hủy (`Cancelled`).

### 2. Các thay đổi kỹ thuật:
1. **Repository & Truy vấn an toàn (`app/repositories/BobinRepository.php`):**
   - Bổ sung `buildExtrusionEditStatusCondition()` và mở rộng `getListDetailBobinsForEditting()` / `countDetailBobinsForEditting()` hỗ trợ lọc trạng thái trong tập hợp lệ `('Busy_Unchecked', 'Busy_Checked', 'Rolled')`, ưu tiên hiển thị `Busy_Unchecked` trước rồi đến `Busy_Checked` và `Rolled` mới nhất, loại trừ tuyệt đối `Pending_Cancellation` và `Cancelled`.
   - Mở rộng `getBobinCurrentImages()` trả về thêm `bobin_current_status` và nâng cấp `isBobinExtrusionCreatedByUser()` hỗ trợ kiểm tra quyền sở hữu theo tập trạng thái hợp lệ và `bobin_key_code` (giữ mặc định `['Busy_Unchecked']` cho luồng sửa/hủy đầy đủ).
   - Bổ sung hàm cô lập `updateExtrusionChartImageOnly()` sử dụng `SELECT ... FOR UPDATE`, chỉ cập nhật duy nhất cột `extrusion_chart_image` (kèm `update_history`) trên `bobin_list_detail` và đồng bộ `extrusion_chart_image` trên các bản ghi cùng `bobin_key_code` trong `bobin_history`, không chạm `bobin_list_general` và không thay đổi `updated_time`.
2. **Service & Controller API (`app/services/BobinServices.php`, `app/controllers/BobinController.php`):**
   - Bổ sung endpoint độc lập `POST/PUT bobin/extrusionUpdateChartImage`: kiểm tra quyền nhóm Đùn (`BobinImageHelper::canManageStageImage('extrusion', 'edit')`), xác thực mật khẩu, kiểm tra quyền sở hữu người tạo đùn, chặn tuyệt đối `Pending_Cancellation` / `Cancelled`, và chỉ đọc duy nhất `bobin_identification_code`, `bobin_key_code`, `confirm_password`, `extrusion_image_action` cùng file `extrusion_chart_image`.
   - Gia cố bảo vệ tại `extrusionUpdateBobin()` và `extrusionDeleteBobin()` bắt buộc `bobin_current_status === 'Busy_Unchecked'` ngay ở tầng API.
3. **Giao diện, CSS, JS & Đa ngôn ngữ (`app/views/extrusionEditBobinView.php`, `public/assets/css/extrusionEditBobin.css`, `public/assets/js/Extrusion/edit_submit.js`, `config/default_translations.json`):**
   - Bổ sung dropdown lọc trạng thái, thẻ KPI thứ 4 thống kê Bobin đã qua QC/Cuộn, phân biệt màu header và badge theo từng trạng thái (`Busy_Unchecked`, `Busy_Checked`, `Rolled`), hiển thị banner khóa bảo vệ `.ext-img-only-banner` và ẩn nút Hủy Bobin trên các thẻ đã qua QC/Cuộn.
   - Tách riêng chế độ mở khóa chỉ bộ chọn ảnh trong `startEdit()` / `cancelEdit()` và hàm lưu `handleImageOnlyConfirm()`.
   - Bổ sung đồng bộ các khóa dịch thuật mới cho cả 3 ngôn ngữ `vi`, `en`, `ja` (`731` khóa mỗi ngôn ngữ).

---

## [TASK-052] - 2026-10-25 - Khắc Phục Lỗi Trang QC, Nâng Cấp Hệ Thống Màu Sắc Nhận Diện "Loại Bobin" Chống Cache Trình Duyệt, Thêm Biểu Đồ "Thống Kê Ngoại Quan Bobin" Trang Danh Sách & Lịch Sử

### 1. Bối cảnh & Yêu cầu:
- **Mục 1 (Sửa lỗi trang QC `qcView.php` & `listBobin_QC.css`):** Khắc phục lỗi Fatal Error `Call to undefined function buildQcFilterUrl()` khi kích hoạt active filter chip và sửa lỗi cú pháp CSS mất cân bằng ngoặc `{}` trong `listBobin_QC.css`.
- **Mục 2 (Làm nổi bật màu sắc cho "Loại Bobin"):** Giải quyết triệt để vấn đề "Loại Bobin không hiển thị màu" trên giao diện người dùng. Xây dựng hệ thống bảng màu nhận diện trực quan, tương phản cao đạt chuẩn WCAG AA cho từng loại Bobin cụ thể và bổ sung cơ chế inline style fallback chống cache trình duyệt trên toàn bộ 6 view trong hệ thống.
- **Mục 3 (Biểu đồ "Thống kê ngoại quan Bobin"):** Tích hợp biểu đồ thống kê ngoại quan trực quan sử dụng dữ liệu `qcDefectStats` trên cả 2 trang Danh sách Bobin (`listBobinDetailView.php`) và Lịch sử Bobin (`listBobinHistoryView.php`) với tiêu đề "Thống kê ngoại quan Bobin", hỗ trợ đầy đủ 3 ngôn ngữ `vi`, `en`, `ja`.

### 2. Các thay đổi kỹ thuật:
1. **Sửa lỗi trang QC (`qcView.php`, `listBobin_QC.css`):**
   - Trong `qcView.php`: Định nghĩa `buildQcFilterUrl(array $newParams): string` lọc bỏ sạch các tham số rỗng/all/null khi xóa active filter chips và alias `buildFilterUrl` phục vụ điều hướng phân trang.
   - Trong `listBobin_QC.css`: Xóa đoạn thuộc tính orphan và dấu đóng ngoặc dư thừa `{ = 174, } = 175` tại dòng 319-322, khôi phục cân bằng ngoặc `{}` 100% giúp trình duyệt parse hoàn toàn các rules styling bên dưới.
2. **Nâng cấp nhận diện màu sắc Loại Bobin (`listBobinDetailView.php`, `listBobinHistoryView.php`, `qcView.php`, `windingView.php`, `listPendingCancellationView.php`, `listCancelledBobinView.php`):**
   - Nâng cấp `getBobinTypeBadgeClass(?string $type)` và bổ sung `getBobinTypeBadgeStyle(?string $type)` phân biệt rõ ràng:
     - `Sản xuất`: Xanh lá đậm (`color: #14532d; background: #dcfce7; border: 1.5px solid #22c55e;`)
     - `Bù`: Xanh dương (`color: #1e40af; background: #dbeafe; border: 1.5px solid #3b82f6;`)
     - `Điều chỉnh (Do CP)`: Cam hổ phách (`color: #c2410c; background: #ffedd5; border: 1.5px solid #f97316;`)
     - 8 Loại Điều chỉnh ngoại quan riêng biệt: Gel (Tím `#6b21a8`), Dị vật (Đỏ/Rose `#9f1239`), Trầy (Vàng hổ phách `#92400e`), Biến dạng (Teal `#115e59`), Xước (Hồng `#86198f`), Chữ in (Indigo `#3730a3`), Vón cục (Violet `#5b21b6`), Màu (Đỏ gạch `#991b1b`).
   - Cung cấp inline fallback style trực tiếp trên thẻ `<span>` badge chống 100% rủi ro cache trình duyệt hoặc conflict CSS specificity.
   - Đồng bộ rules CSS tương ứng vào tất cả 5 file CSS quản lý danh sách: `listBobinDetail.css`, `listBobinHistory.css`, `listBobin_QC.css`, `listBobin_Winding.css`, `listPendingCancellation.css`.
3. **Biểu đồ "Thống kê ngoại quan Bobin" (`listBobinDetailView.php`, `listBobinHistoryView.php`, `listBobinDetail.css`, `listBobinHistory.css`):**
   - Tái cấu trúc phân vùng dashboard thành hệ thống lưới `.analytics-charts-grid` 2 panel biểu đồ song song: Panel 1 (Thống kê trạng thái) và Panel 2 (Thống kê ngoại quan Bobin).
   - Panel Thống kê ngoại quan Bobin hiển thị header với gradient mềm mại, icon kính lúp/QC và các badge con tổng hợp: Đạt (`ok_all`), Có lỗi (`ng_any`).
   - Biểu đồ dạng thanh ngang (Horizontal Bar Chart) 8 tiêu chí kiểm tra QC: Gel, Dị vật, Lỗi màu, Chữ in, Vón cục, Trầy, Xước, Biến dạng kèm số lượng và tỷ lệ % trên từng thanh.
   - Bổ sung translation keys đa ngữ cho `vi`, `en`, `ja` trong `config/default_translations.json` và `public/assets/js/i18n.js`.

---

## [TASK-051] - 2026-10-25 - Đồng Bộ Làm Nổi Bật Dựa Trên Giá Trị Cho "Mã Sản Phẩm" & "Loại Bobin", Thêm Bộ Lọc Trang Cuộn, Bổ Sung Bộ Lọc Máy Đùn & Máy Cuộn Cho Danh Sách & Lịch Sử

### 1. Bối cảnh & Yêu cầu:
- **Mục 1 (Làm nổi bật theo giá trị - Value-based Highlighting):** Thống nhất hệ thống badge làm nổi bật dựa trên giá trị cho "Mã sản phẩm" (hệ màu 10 palette cố định chuẩn tương phản WCAG AA) và "Loại Bobin" (phân biệt Sản xuất / Bù / Điều chỉnh / Trống) xuyên suốt các trang: Danh sách chi tiết, Lịch sử, QC, Cuộn, Chờ hủy và Đã hủy.
- **Mục 2 (Bộ lọc trang thông tin Cuộn `bobin/windingView`):** Bổ sung bộ lọc cho trang thông tin Cuộn gồm: Máy đùn, Máy cuộn, Mã sản phẩm, Loại Bobin, Quét QR, Reset bộ lọc và thanh hiển thị các filter đang áp dụng (active filter chips).
- **Mục 3 (Bộ lọc Máy đùn & Máy cuộn cho Danh sách & Lịch sử):** Bổ sung 2 bộ lọc "Máy đùn" và "Máy cuộn" cho trang Danh sách chi tiết (`listBobinDetailView`), Lịch sử (`listBobinHistoryView`), Chờ hủy (`listPendingCancellationView`) và Đã hủy (`listCancelledBobinView`), hỗ trợ cả hiển thị, đếm, thống kê KPI và xuất Excel.
- **Mục 4:** Giao diện bộ lọc trang QC được tối ưu hóa giao diện và người dùng sẽ chủ động tinh chỉnh thêm câu chữ nhầm lẫn.

### 2. Các thay đổi kỹ thuật:
1. **Từ điển đa ngôn ngữ (`config/default_translations.json`):**
   - Bổ sung translation keys cho `vi`, `en`, `ja`: `opt_all_winding_machines`, `filter_extrusion_machine`, `filter_winding_machine`, `opt_all_bobin_types`, `active_filter_chips`.
2. **DTO & Backend Pipeline (`BobinGetListDTO.php`, `BobinRepository.php`, `BobinServices.php`, `BobinController.php`):**
   - Bổ sung thuộc tính `public ?string $windingMachine` vào `BobinGetListDTO` và ánh xạ từ request `winding_machine`.
   - Cập nhật hàm lọc nâng cao trung tâm `BobinRepository::appendAdvancedManagementFilters()` hỗ trợ lọc chính xác theo `extrusion_machine` (`machine`) và `winding_machine`, áp dụng đồng bộ cho cả `getDetailBobins`, `countDetailBobins`, `getBobinsHistory`, `getDetailBobinsForPendingCancellation`, `getDetailBobinsForCancelled` và xuất Excel.
   - Nâng cấp `BobinRepository::getDetailBobinsForWinding()` và `countDetailBobinsForWinding()` hỗ trợ lọc đồng thời theo `machine`, `winding_machine`, `product_code` và `bobin_type`.
   - Cập nhật `BobinController` truyền đầy đủ danh sách `machines` và `windingMachines` sang các view liên quan.
3. **Value-based Badge System & Styling (`listBobinDetail.css`, `listBobinHistory.css`, `listPendingCancellation.css`, `listBobin_QC.css`, `listBobin_Winding.css`):**
   - Xây dựng 10 palette màu cố định (`prod-theme-0` đến `prod-theme-9`) dựa trên giải thuật hàm băm `crc32` của chuỗi mã sản phẩm, đảm bảo cùng một mã sản phẩm luôn có cùng một màu sắc trực quan, hài hòa và dễ phân biệt.
   - Thêm trạng thái `.product-empty` và `.type-empty` với viền nét đứt (dashed border) cho các trường hợp rỗng/chưa cập nhật.
   - Thiết lập CSS responsive `repeat(auto-fit, minmax(145px, 1fr))` cho `.filter-grid-row`.
4. **Giao diện Views (`listBobinDetailView.php`, `listBobinHistoryView.php`, `windingView.php`, `qcView.php`, `listPendingCancellationView.php`, `listCancelledBobinView.php`):**
   - Tích hợp hàm trợ giúp `getProductBadgeClass()` và `getBobinTypeBadgeClass()` trên toàn bộ các view.
   - Thêm các dropdown lọc Máy đùn và Máy cuộn, các input ẩn trong form tìm kiếm, và cập nhật nút "Đặt lại bộ lọc" để xóa sạch các tham số lọc này.
   - Nâng cấp giao diện trang Cuộn `windingView.php` và trang QC `qcView.php` với control bar hiện đại, input group, nút lọc, quét QR và active filter chips.

---

## [TASK-050] - 2026-10-25 - Đối Soát AI_CONTEXT.md: Bổ Sung Bộ Lọc Máy Đùn & Mã Sản Phẩm Trang QC, Mở Rộng Phạm Vi Bobin Cuộn Dang Dở 7 Ngày, Cập Nhật updated_time Khi Điều Chỉnh

### 1. Bối cảnh & Yêu cầu:
- **Mục 1 (Bộ lọc trang QC Check `bobin/qcView`):** Bổ sung chức năng lọc theo "Mã máy đùn" (Ví dụ: PL08) và "Mã sản phẩm" (Ví dụ: TU0425B-1M2) theo đúng đặc tả Mục 2.2 của `AI_CONTEXT.md`.
- **Mục 2 (Phạm vi Bobin cuộn dang dở `bobin/windingView`):** Mở rộng giới hạn hiển thị Bobin đang chạy dang dở ở trạng thái `Rolled` từ 24 giờ lên 7 ngày (`INTERVAL 7 DAY`) theo Mục 2.3 của `AI_CONTEXT.md`.
- **Mục 3 (Cập nhật `updated_time` khi điều chỉnh Bobin):** Bổ sung `updated_time = NOW()` vào các hàm cập nhật điều chỉnh ở cả 3 công đoạn Đùn, QC và Cuộn trên cả 2 bảng `bobin_list_detail` và `bobin_history` theo quy định bắt buộc của `AI_CONTEXT.md`.

### 2. Các thay đổi kỹ thuật:
1. **Bộ lọc Máy đùn & Mã sản phẩm cho QC (`BobinGetListDTO.php`, `BobinServices.php`, `BobinRepository.php`, `BobinController.php`, `qcView.php`, `listBobin_QC.css`):**
   - Mở rộng thuộc tính `$machine` trong `BobinGetListDTO`.
   - Cập nhật `BobinServices::getDetailBobinsForQC()` và `countDetailBobinsForQC()` tiếp nhận `machine` và `product_code`.
   - Cập nhật `BobinRepository::getDetailBobinsForQC()` và `countDetailBobinsForQC()` lọc chính xác theo `extrusion_machine` và `products.product_code` (sử dụng các tham số placeholder độc lập `:pcode1`, `:pcode2`), đồng thời đưa `extrusion_machine` vào tìm kiếm `keyword`.
   - Thêm dropdown chọn máy đùn (`#filterMachine`), input mã sản phẩm (`#filterProductCode` kèm `<datalist>`) và nút xóa lọc (`.btn-reset-filter`) trên giao diện `qcView.php` kèm CSS responsive.
   - Bổ sung từ điển dịch thuật `opt_all_machines` và `ph_product_code` trong `config/default_translations.json`.
2. **Mở rộng thời gian Bobin cuộn dang dở lên 7 ngày (`BobinRepository.php`):**
   - Thay thế `INTERVAL 24 HOUR` thành `INTERVAL 7 DAY` trong `BobinRepository::getDetailBobinsForWinding()` và `BobinRepository::countDetailBobinsForWinding()`.
3. **Cập nhật `updated_time = NOW()` khi điều chỉnh (`BobinRepository.php`):**
   - Bổ sung `updated_time = NOW()` trong `extUpdateBobinDetail()`, `extUpdateBobinGeneral()`, và `extUpdateBobinHistory()`.
   - Bổ sung `updated_time = NOW()` trong `adminUpdateQCBobin()` (cập nhật `bobin_list_detail`, `bobin_list_general`, `bobin_history`).
   - Bổ sung `updated_time = NOW()` trong `adminUpdateWindingBobin()` (cập nhật `bobin_list_detail`, `bobin_history`).

---

## [TASK-0489] - 2026-10-25 - Khắc Phục Gợi Ý Trùng Lặp, Sửa Lỗi Xuất Excel, Bổ Sung Xuất Excel Trang Chờ Hủy & Đã Hủy, Tối Ưu Trang Nhân Viên, và Bổ Sung Trường "Máy Đùn" (`extrusion_machine`)

### 1. Bối cảnh & Yêu cầu:
- **Mục 1 (Sửa gợi ý trùng lặp):** Tại các trang danh sách, ô textfield "👤 Mã số nhân viên:" khi nhập hiển thị cả dropdown mặc định của trình duyệt và menu gợi ý tùy biến.
- **Mục 2 (Sửa lỗi các nút Export Excel):** Khắc phục lỗi SQL syntax và ngoại lệ phát sinh khi nhấn nút Xuất Excel khi đang lọc nâng cao; bổ sung trường "Máy đùn" vào file Excel.
- **Mục 3 (Thêm nút Export Excel trang "Đã hủy" và "Chờ hủy"):** Bổ sung nút Xuất Excel và API xuất file cho 2 trang `listPendingCancellationView.php` và `listCancelledBobinView.php`.
- **Mục 4 (Tối ưu trang Quản lý nhân viên):** Sửa lỗi `filterByRole` chưa được định nghĩa khi click vào stat-card hoặc legend Donut chart, bổ sung bộ lọc vai trò tức thì, cập nhật STT, đồng bộ URL nút Xuất Excel theo bộ lọc hiện hành và dọn dẹp các đường dẫn hardcoded.
- **Mục 5 (Bổ sung trường "Máy đùn" vào database và toàn bộ dự án):** Thêm trường `extrusion_machine` (string, ví dụ "PL13") vào database và hiển thị đồng bộ trên toàn bộ các view của hệ thống (Đùn, QC, Cuộn, Chi tiết, Lịch sử, Chờ hủy, Đã hủy).

### 2. Các thay đổi kỹ thuật:
1. **Khắc phục gợi ý trùng lặp (`advancedBobinFilterAndStats.php`):**
   - Loại bỏ thuộc tính `list="advEmployeeDatalist"` và `list="advProductDatalist"` cùng các thẻ `<datalist>`, chuyển sang sử dụng hoàn toàn custom dropdown `#advEmployeeSuggestMenu` / `#advProductSuggestMenu`.
2. **Sửa lỗi và nâng cấp Xuất Excel (`BobinRepository.php`, `BobinController.php`, `BobinServices.php`):**
   - Sửa lỗi alias `exp.` thành `''` trong `BobinRepository::getBobinListDetailForExport()` khi gọi `appendAdvancedManagementFilters()`.
   - Bổ sung bộ lọc khoảng ngày (`from_date`, `to_date`).
   - Thêm cột `Máy đùn` vào header và row data trong cả `outputEnhancedCsvHistory()` và `outputEnhancedCsvDetail()`.
3. **Thêm Export Excel cho Chờ Hủy và Đã Hủy (`BobinController.php`, `Router.php`, `AuthHelper.php`, `listPendingCancellationView.php`, `listCancelledBobinView.php`):**
   - Triển khai `exportPendingCancellationExcel()` và `exportCancelledExcel()`.
   - Đăng ký route và kiểm tra quyền trong `Router.php` và `AuthHelper.php`.
   - Thêm nút Xuất Excel chuẩn giao diện, hỗ trợ xuất toàn bộ kết quả lọc hoặc các bản ghi được chọn qua checkbox.
4. **Tối ưu hóa trang Quản lý nhân viên (`employeeListView.php`, `EmployeeController.php`, `employeeList.css`):**
   - Thêm bộ lọc `role` trong `EmployeeController::index()` và `EmployeeController::exportExcel()`.
   - Triển khai `filterByRole(role)`, `applyEmployeeTableFilter()`, và `updateStatCardsActive()` trong `employeeListView.php`.
   - Tự động đồng bộ URL nút Xuất Excel theo đúng các tham số `keyword`, `cost_center`, `role`.
   - Thêm hiệu ứng CSS `.stat-card.active` và `.legend-item.active`.
   - Thay thế các đường dẫn hardcode bằng biến `$baseUrl`.
5. **Thêm trường "Máy đùn" (`extrusion_machine`) vào hệ thống:**
   - **Database:** Thêm `extrusion_machine VARCHAR(50) DEFAULT NULL AFTER extrusion_check` trong `bobin_list_detail` và `bobin_history`.
   - **DTOs & Entity:** Cập nhật `BobinCreateDTO`, `BobinExtUpdateDTO` (nhận `extrusion_machine` hoặc `machine`), và `BobinEntity`.
   - **Repository:** Cập nhật `insertBobinDetailRecord`, `insertBobinHistoryRecord`, 8 câu lệnh `INSERT ... SELECT`, update queries và keyword search.
   - **Views:** Hiển thị trường "Máy đùn" trên `extrusionEditBobinView.php`, `listBobinDetailView.php`, `listBobinHistoryView.php`, `listPendingCancellationView.php`, `listCancelledBobinView.php`, `qcView.php`, `qcEditBobinView.php`, `windingView.php`, `windingEditBobinView.php`.
   - **Đa ngôn ngữ:** Cập nhật từ khóa `extrusion_machine`, `filter_adv_extrusion_machine`, `header_extrusion_machine`, `btn_export_excel` trong `default_translations.json`.

---

## [TASK-048] - 2026-10-19 - Tính Năng Yêu Cầu Đăng Xuất Định Kỳ Tại 4 Thời Điểm Cố Định (6:05 AM, 2:05 PM, 4:40 PM, 10:05 PM) Kèm Bộ Đếm Ngược Tự Động Đăng Xuất Sau 1 Phút

### 1. Bối cảnh & Yêu cầu:
- **Mục đích:** Đảm bảo nhân viên ca sau (User 2) không sử dụng tài khoản còn đang mở của nhân viên ca trước (User 1) để thực hiện các tác vụ sản xuất/kiểm tra.
- **Các thời điểm cố định trong ngày:**
  - `6:05 AM` (`06:05`)
  - `2:05 PM` (`14:05`)
  - `4:40 PM` (`16:40`)
  - `10:05 PM` (`22:05`)
- **Phạm vi & Hành vi:**
  - Áp dụng đối với **toàn bộ User** (khi đã đăng nhập).
  - Khi đến thời điểm cố định, hiển thị Dialog yêu cầu User phải đăng xuất định kỳ.
  - Hiển thị **Counter đếm ngược 1 phút (60 giây)**: nếu trong vòng 1 phút User không nhấn xác nhận đăng xuất trên Dialog, hệ thống tự động đăng xuất tài khoản.

### 2. Các thay đổi kỹ thuật:
1. **Cấu hình Mốc Thời Gian & Kiểm Soát Phiên Cấp Backend (`AuthHelper.php`, `AuthController.php`, `Router.php`):**
   - Định nghĩa `AuthHelper::PERIODIC_LOGOUT_SCHEDULES` (`06:05`, `14:05`, `16:40`, `22:05`) và `AuthHelper::PERIODIC_LOGOUT_COUNTDOWN_SECONDS = 60`.
   - Xây dựng `AuthHelper::getNextPeriodicLogoutCheckpoint()` và `AuthHelper::checkPeriodicLogoutStatus()` tính toán mốc đăng xuất định kỳ kế tiếp ngay sau thời điểm đăng nhập (`logged_at_ts` theo múi giờ `Asia/Ho_Chi_Minh`).
   - Bổ sung kiểm tra tại `Router::run()`: nếu phiên làm việc đã vượt quá mốc cố định + 60 giây mà chưa đăng xuất, Server tự động hủy Session và chuyển hướng về trang Login (hoặc trả về HTTP 401 cho AJAX); đồng thời khóa các thao tác ghi dữ liệu (`POST` API) khi đang trong cửa sổ 60 giây chờ xác nhận đăng xuất.
2. **Giao diện Dialog Yêu Cầu Đăng Xuất Định Kỳ & Counter Đếm Ngược 60 Giây (`periodicLogoutModal.php`, `header.php`, `Controller.php`, `loginView.php`):**
   - Tạo component [periodicLogoutModal.php](file:///c:/xampp/htdocs/WEB_BOBIN/app/views/components/periodicLogoutModal.php) tự động tích hợp vào mọi trang đã đăng nhập thông qua `header.php` và `Controller::view()`.
   - Hiển thị thông tin nhân viên đang đăng nhập, khung giờ kiểm soát cố định, vòng tròn tiến trình SVG kèm số giây đếm ngược (`60` -> `0` giây) và thanh progress bar đổi màu cảnh báo khẩn cấp khi còn $\le 15$ giây.
   - Chặn đóng Dialog bằng phím `Escape` hoặc click nền ngoài; tự động kích hoạt đăng xuất ngay khi nhấn **"Xác nhận đăng xuất ngay"** hoặc khi bộ đếm ngược chạm `0` giây, đồng thời hiển thị thông báo tương ứng tại [loginView.php](file:///c:/xampp/htdocs/WEB_BOBIN/app/views/loginView.php).
   - Bổ sung đầy đủ từ điển đa ngôn ngữ (`vi`, `en`, `ja`) trong [default_translations.json](file:///c:/xampp/htdocs/WEB_BOBIN/config/default_translations.json).

---

## [TASK-047] - 2026-10-18 - Bổ Sung Phân Trang Cho Toàn Bộ Các Trang Danh Sách/Lịch Sử/QC/Điều Chỉnh Đùn & Phân Quyền Sở Hữu / Xem Toàn Bộ Tại 3 Trang Điều Chỉnh (Đùn / QC / Cuộn)

### 1. Bối cảnh & Yêu cầu:
- **Mục 1 & 2 (Đồng bộ phân trang trên 9 trang nghiệp vụ):**
  - Giữ nguyên 4 trang đã có phân trang (`Danh sách điều chỉnh QC`, `Danh sách điều chỉnh cuộn`, `Nhóm Cuộn`, `Danh sách Bobin hiện tại`).
  - Bổ sung phân trang (50 mục/trang) cho 5 trang còn lại (`Danh sách điều chỉnh đùn`, `Nhóm QC`, `Lịch sử Bobin`, `Danh sách chờ hủy`, `Danh sách đã hủy`) mà không làm ảnh hưởng đến bất kỳ logic xử lý dữ liệu, biểu đồ, thẻ KPI, bộ lọc hay tính năng xuất Excel nào.
- **Mục 3 (Phân quyền sở hữu tại 3 trang Điều chỉnh & Mở rộng quyền xem toàn bộ theo nhóm):**
  - Áp dụng cơ chế quyền hiển thị và thao tác theo người thực hiện tại trang **Điều chỉnh Đùn** (`extrusion_employee`) và **Điều chỉnh QC** (`visual_inspection.inspector_code`) tương tự như trang **Điều chỉnh Cuộn** (`winding_employee`).
  - Bổ sung tại trang Phân quyền (`employee/permissionsView`) tùy chọn cho phép chỉ định nhân viên cụ thể được xem và điều chỉnh toàn bộ Bobin thuộc nhóm của mình (Đùn / QC / Cuộn); tài khoản `admin` mặc định xem và thao tác được toàn bộ cả 3 trang điều chỉnh.

### 2. Các thay đổi kỹ thuật:
1. **Phân trang 5 trang nghiệp vụ (`BobinRepository.php`, `BobinServices.php`, `BobinController.php`, các file View & CSS):**
   - **Điều chỉnh Đùn (`extrusionEditBobinView.php`):** Bổ sung `countDetailBobinsForEditting()`, cập nhật `getListDetailBobinsForEditting()` hỗ trợ `LIMIT / OFFSET` (50 mục/trang) và thêm thanh điều hướng phân trang (`extrusionEditBobin.css`).
   - **Nhóm QC (`qcView.php`):** Cập nhật `getDetailBobinsForQC()` nhận `$page`, `$limit` và kết nối với `countDetailBobinsForQC()` trong `BobinController::listBobinView_QC()`, hiển thị thanh phân trang (`listBobin_QC.css`).
   - **Lịch sử Bobin (`listBobinHistoryView.php`), Danh sách chờ hủy (`listPendingCancellationView.php`), Danh sách đã hủy (`listCancelledBobinView.php`):** Giữ nguyên việc tính toán KPI, biểu đồ, thống kê mã sản phẩm và thống kê ngoại quan QC trên toàn bộ tập kết quả lọc (`$allBobins`), sau đó phân trang 50 mục/trang (`array_slice`) cho bảng dữ liệu hiển thị và thanh điều hướng phân trang (`advancedBobinFilter.css`). Đồng thời khắc phục lỗi bí danh bảng SQL trong `getDetailBobinsForPendingCancellation()` và `getDetailBobinsForCancelled()`.
2. **Kiểm soát quyền sở hữu tại Điều chỉnh Đùn & Điều chỉnh QC (`BobinRepository.php`, `BobinServices.php`, `BobinController.php`):**
   - Lọc mặc định theo mã nhân viên / ID người dùng đang đăng nhập (`extrusion_employee` cho Đùn, `visual_inspection` cho QC) và gỡ bỏ giới hạn `INTERVAL 3 DAY`.
   - Bổ sung `isBobinExtrusionCreatedByUser()` và `isBobinQcInspectedByUser()` để kiểm tra quyền sở hữu ở tầng API (`extrusionUpdateBobin`, `extrusionDeleteBobin`, `updateQCEditBobin`, `qcCancelBobin`), trả về HTTP 403 nếu người dùng không có quyền sở hữu và không có quyền xem toàn bộ.
3. **Bổ sung 3 quyền "Xem & điều chỉnh toàn bộ Bobin" theo nhóm (`AuthHelper.php`, `EmployeeController.php`, `employeePermissionsView.php`):**
   - Định nghĩa 3 quyền mới `extrusion_edit_view_all`, `qc_edit_view_all`, `winding_edit_view_all` kèm thuộc tính `role_group` (`extrusion`, `qc`, `winding`).
   - Xây dựng `AuthHelper::canViewAllEditBobins(string $stage, ?array $user = null)` và ràng buộc chặt chẽ tại `AuthHelper::getUserPermissions()` + `EmployeeController::updatePermissions()`: `admin` mặc định có đủ cả 3 quyền; nhân viên thuộc nhóm nào chỉ có thể được cấp quyền `*_edit_view_all` của đúng nhóm đó.
   - Cập nhật giao diện `employeePermissionsView.php` tự động vô hiệu hóa (disable) các checkbox `*_edit_view_all` khác nhóm vai trò của nhân viên đang chọn, kèm bản dịch `vi`/`en`/`ja` trong `config/default_translations.json`.

---

## [TASK-046] - 2026-10-18 - Phân Quyền Hiển Thị Theo Người Xác Nhận Tại Trang "Điều Chỉnh Cuộn" & Bổ Sung Bộ Lọc / Thống Kê Đa Chiều Tại 4 Trang Quản Lý Bobin

### 1. Bối cảnh & Yêu cầu:
- **Mục 1 (Trang "Điều chỉnh cuộn"):** Chỉ hiển thị và cho phép thao tác trên những Bobin mà User đang đăng nhập là người đã nhập và xác nhận thông tin cuộn trước đó (xử lý trực tiếp ở tầng Backend/API; ví dụ Bobin `A0022` do nhân viên **Lê Thị Lụa** - mã NV `02224428` xác nhận chỉ hiển thị khi `02224428` đăng nhập).
- **Mục 2 & 3 (Bổ sung Filter & Thống kê tại 4 trang quản lý Bobin — Danh sách Bobin, Lịch sử Bobin, Danh sách chờ hủy, Danh sách đã hủy):**
  - **2a. Filter theo mã số nhân viên (`employee_code`):** Gợi ý dropdown danh sách nhân viên và tìm kiếm đồng thời trên cả **3 nhóm nhân viên** (Đùn `extrusion_employee`, Cuộn `winding_employee`, QC `visual_inspection`).
  - **2b. Filter theo mã sản phẩm (`product_code`) & thống kê số lượng Bobin:** Gợi ý dropdown mã sản phẩm kèm số lượng Bobin và hiển thị thống kê số lượng Bobin tương ứng với mã sản phẩm đang lọc.
  - **2c. Filter theo tình trạng ngoại quan QC (`qc_defect`) & thống kê số lượng:** Đồng bộ đủ 8 trường ngoại quan QC (`gel`, `foreign_object`, `color_issue`, `print_quality`, `clump`, `scratch`, `streak`, `deformation`) cùng tùy chọn tổng hợp (`ok_all`, `ng_any`), hiển thị số lượng Bobin của từng tình trạng ngoại quan tương ứng với tập dữ liệu lọc.
  - Kết hợp độc lập và đồng thời tất cả các bộ lọc mới với các bộ lọc sẵn có trên từng trang.

### 2. Các thay đổi kỹ thuật:
1. **Phân quyền hiển thị & bảo vệ API theo người xác nhận tại "Điều chỉnh cuộn" (`BobinRepository.php`, `BobinServices.php`, `BobinController.php`):**
   - Cập nhật `getListDetailBobinsForWindingEdit()` và `countDetailBobinsForWindingEdit()` lọc theo `winding_employee.employee_code` / `winding_employee.id` của User đang đăng nhập và gỡ bỏ giới hạn `INTERVAL 3 DAY`.
   - Bổ sung `isBobinWindingConfirmedByUser()` kiểm tra quyền sở hữu ở tầng API trong `updateWindingEditBobin()` và `windingCancelBobin()` (khi `source === 'winding_edit'`), trả về HTTP 403 nếu User khác cố tình gửi request chỉnh sửa/hủy.
2. **Mở rộng DTO, Repository & Service cho 3 Bộ lọc và Thống kê (`BobinGetListDTO.php`, `BobinRepository.php`, `BobinServices.php`):**
   - Bổ sung `$employeeCode`, `$productCode`, `$qcDefect` vào `BobinGetListDTO`.
   - Xây dựng `appendAdvancedManagementFilters()` thực thi truy vấn SQL `OR` trên cả 3 trường JSON nhân viên (`$.employee_code` của Đùn, Cuộn và `$.inspector_code` của QC), lọc `$.product_code` và lọc tình trạng ngoại quan QC NULL-safe (`getSingleQcDefectNgExpr()`, `getQcDefectFilterCondition()`).
   - Xây dựng các hàm thống kê `getDetailQcDefectStats()`, `getDetailProductCounts()`, `computeQcDefectStatsFromRows()`, `computeProductCountsFromRows()` và `getDetailBobinsForCancelled()`.
3. **Giao diện Component Bộ lọc & Thống kê dùng chung và Trang "Danh sách đã hủy" (`advancedBobinFilterAndStats.php`, `advancedBobinFilter.css`, `listCancelledBobinView.php`):**
   - Tạo component `app/views/components/advancedBobinFilterAndStats.php` và stylesheet `public/assets/css/advancedBobinFilter.css`, tích hợp đồng bộ vào `listBobinDetailView.php`, `listBobinHistoryView.php`, `listPendingCancellationView.php` và `listCancelledBobinView.php`.
   - Bổ sung route và menu "Danh sách đã hủy" (`bobin/listCancelledBobinView`) trong `Router.php`, `AuthHelper.php`, `sidebar.php` và cập nhật từ điển đa ngôn ngữ `vi`/`en`/`ja` trong `config/default_translations.json`.

---

## [TASK-045] - 2026-10-18 - Giả Lập 500 Mẫu Bobin CSDL & Chuẩn Hóa Logic Thống Kê / Lọc Theo Điều Kiện Đầy Đủ Thông Tin Nhóm Đùn

### 1. Bối cảnh & Yêu cầu:
- Giả lập dữ liệu CSDL khoảng 500 Bobin có đầy đủ thông tin sản xuất để phục vụ kiểm thử.
- Tại `kpi-grid` (trang Danh sách Bobin hiện tại), đối với tất cả các thẻ trạng thái lọc (`kpi-card`), chỉ đếm những Bobin có đầy đủ thông tin nhập từ nhóm đùn (`A0028` thuộc "Đã QC" & "Tồn line"; `A0031` thuộc "Đã cuộn"; các Bobin mang trạng thái `Rolled` nhưng chưa có hoặc thiếu thông tin đùn như `A0029`, `A0032` không được liệt vào "Đã cuộn" mà chỉ thuộc "Bobin trống").
- Tại `filter-dashboard`, tất cả các chức năng lọc chỉ lọc những Bobin có thông tin đầy đủ từ nhóm đùn; riêng logic Bobin trống vẫn giữ nguyên chức năng.

### 2. Các thay đổi kỹ thuật:
1. **Giả lập Dữ liệu 500 Bobin Trong CSDL (`bobin_list_detail`, `bobin_list_general`, `bobin_history`):**
   - Bảo toàn các bản ghi test hiện hữu (`A0021..A0050`, `C0001`) và bổ sung đồng bộ 473 Bobin mới (`A0051..A0204`, `B0001..B0160`, `C0002..C0160`) sử dụng dữ liệu danh mục thực tế (`product_list`, `material_lot_list`, `employee_list`, `rack_list`, `winding_machine_list`).
   - Tổng số Bobin có đầy đủ thông tin nhóm đùn đạt đúng **500 Bobin** (`PL7-3`: 180, `PL4-7 (TU04.TU06)`: 160, `PL4-7 (TU08~)`: 160), phân bổ trạng thái: `Đã cuộn` (220), `Chưa QC` (110), `Đã QC` (120), `Tồn line` (230), `Chờ hủy` (20), `Đã hủy` (30), `Bobin trống` (1,110 / 1,360).
2. **Định nghĩa Điều kiện Đầy đủ Thông tin Nhóm Đùn (`app/repositories/BobinRepository.php`):**
   - Xây dựng phương thức `getExtrusionCompleteCondition()` kiểm tra đầy đủ các trường bắt buộc từ công đoạn Đùn: `products` (`$.product_code`), `extrusion_employee` (`$.employee_code`), `material_lot` (`$.lot`), `rack` (`$.code`), `extrusion_check`, `print_lot`, `length_m > 0`, `shift`, `extrusion_date`, `finish_time`.
   - Áp dụng điều kiện này vào `getBobinStatusStats()`, `getBobinListDetail()`, `countBobinListDetail()`, `getBobinListDetailForExport()` và các truy vấn danh sách công đoạn liên quan, giữ nguyên điều kiện cho trường hợp `status = 'Empty'`.
3. **Chuẩn hóa Hiển thị KPI Grid (`app/views/listBobinDetailView.php`):**
   - Cập nhật `$statusCounts['Rolled']` lấy trực tiếp số lượng Bobin có `bobin_current_status = 'Rolled'` kèm đầy đủ thông tin nhóm đùn từ `getBobinStatusStats()`, loại bỏ công thức trừ ngược gây đếm nhầm các Bobin trống chưa nhập dữ liệu đùn vào mục "Đã cuộn".
   - Giữ nguyên công thức tính "Bobin Trống": `$emptyBobins = max(0, $totalRealBobins - $statusCounts['Line'] - $statusCounts['Pending_Cancellation'])`.

---

## [TASK-044] - 2026-10-14 - Bổ Sung Chức Năng Lọc Trạng Thái "Đã Hủy" (Cancelled) Tại Trang "Danh Sách Hiện Tại" (listBobinDetailView)

### 1. Bối cảnh & Yêu cầu:
- Tại trang "Danh sách Hiện tại" (`app/views/listBobinDetailView.php`), ở mục filter dựa trên "📌 Trạng thái:" (`#statusFilter`), bổ sung và hoàn thiện tính năng lọc trạng thái "Đã hủy" (`Cancelled`).

### 2. Các thay đổi kỹ thuật:
1. **Dropdown Bộ lọc Trạng thái (`listBobinDetailView.php`):**
   - Đưa tùy chọn `'Cancelled' => '❌ Đã hủy'` vào danh mục `$statusOptions` trong `app/views/listBobinDetailView.php`.
   - Khi chọn "❌ Đã hủy", hệ thống nạp URL `status=Cancelled`, tự động lọc danh sách chỉ hiển thị các Bobin có `bobin_current_status = 'Cancelled'`.
2. **Thống kê CSDL & Lưới KPI Dashboard (`BobinRepository.php`, `listBobinDetailView.php`):**
   - Cập nhật `BobinRepository::getBobinStatusStats()` trả về chỉ số `$cancelled = (int)($rows['Cancelled'] ?? 0)` trong mảng kết quả.
   - Cập nhật công thức tính `$realRolled` trong `listBobinDetailView.php` trừ đi `$statusCounts['Cancelled']`.
   - Bổ sung thẻ KPI bấm được cho "Đã hủy" (`.kpi-card.cancelled`) vào lưới KPI Dashboard với biểu tượng `❌`, hiển thị số lượng Bobin đã hủy và liên kết lọc nhanh đến `status=Cancelled`.
   - Bổ sung cột "Đã hủy" vào nhãn và chuỗi số liệu biểu đồ ApexCharts (`$chartLabels`, `$chartSeries`) kèm mã màu `#ef4444`.
3. **Đồng bộ Đa ngôn ngữ (`public/assets/js/i18n.js`):**
   - Bổ sung `{ vi: "❌ Đã hủy", en: "❌ Scrapped", ja: "❌ 廃棄済" }`, `{ vi: "Đã hủy", en: "Scrapped", ja: "廃棄済" }`, `{ vi: "ĐÃ HỦY", en: "SCRAPPED", ja: "廃棄済" }` vào `VALUE_TRANSLATIONS` trong `public/assets/js/i18n.js` để tự động dịch dropdown và thẻ badge khi chuyển đổi giữa Tiếng Việt, Tiếng Anh và Tiếng Nhật.

---

## [TASK-042] - 2026-04-20 - Rà Soát Toàn Diện Mã Nguồn, Tối Ưu Hóa Tốc Độ Xử Lý Backend, CSDL & UI/UX Frontend (Bảo Toàn Tuyệt Đối Nghiệp Vụ)

### 1. Bối cảnh & Yêu cầu:
- Rà soát toàn bộ source code của dự án, tối ưu hóa giao diện người dùng và trải nghiệm (UI/UX), tối ưu hóa tốc độ xử lý backend và truy vấn cơ sở dữ liệu.
- **Ràng buộc quan trọng:** Tuyệt đối không thay đổi các chức năng, quy tắc kiểm tra, tính toán hay luồng nghiệp vụ hiện có ("tất cả đều mang tính nghiệp vụ").
- Đảm bảo 100% khả năng hoạt động offline / local intranet độc lập, không sử dụng tài nguyên CDN mạng internet.

### 2. Các thay đổi kỹ thuật:
1. **Tối ưu hóa Truy vấn Danh mục ListData (`app/repositories/ListDataRepository.php`):**
   - Loại bỏ hoàn toàn `$pdo->beginTransaction()` và `$pdo->commit()` bao bọc 12 truy vấn `SELECT` danh mục đọc dữ liệu. Trong MySQL InnoDB, việc mở/đóng transaction cho các lệnh đọc thuần túy gây lãng phí transaction log overhead, read-view snapshot allocations và metadata lock contention không cần thiết.
   - Thay thế việc gọi `$pdo->prepare($sql)->execute()` lặp lại cho các câu lệnh SQL tĩnh bằng `$pdo->query($sql)` trực tiếp trong `fetchAllListData()`, giảm thời gian nạp danh mục xuống còn ~13.3ms.
2. **Bộ nhớ đệm Nội bộ Cấp Yêu cầu (`app/repositories/BobinRepository.php` & `app/services/BobinServices.php`):**
   - Thêm thuộc tính private cache `$cachedCapacities` và `$cachedRacks` trong `BobinRepository`.
   - Hàm sinh bảng ảo `getActiveBaseTable()` (vốn được gọi nhiều lần trong một request để tính tổng số dòng và lấy dữ liệu phân trang) sẽ tái sử dụng cấu hình dung lượng trong bộ nhớ thay vì truy vấn lặp lại vào bảng `bobin_capacity`.
   - Tự động xóa cache (`$this->cachedCapacities = null`) khi người dùng cập nhật dung lượng qua `updateBobinCapacity()`.
   - Hỗ trợ tham số `$forceRefresh = false` trong `BobinServices` để tương thích ngược 100%.
3. **Đánh Chỉ mục CSDL Hiệu năng Cao (Composite Indexing an toàn, không sửa schema):**
   - Đánh chỉ mục composite `idx_status_updated` trên `bobin_list_detail(bobin_current_status, updated_time)`: Triệt tiêu hoàn toàn bước `Using filesort` của MySQL khi thực hiện các truy vấn lọc trạng thái kết hợp sắp xếp theo `updated_time DESC` tại danh sách QC, Cuộn và Chờ hủy.
   - Đánh chỉ mục composite `idx_size_ident` trên `bobin_list_detail(bobin_size, bobin_identification_code)`: Tối ưu hóa phân vùng window function `ROW_NUMBER() OVER(PARTITION BY bobin_size ORDER BY bobin_identification_code ASC)`.
4. **Tối ưu Hóa Render & Debouncing Tìm kiếm/Gợi ý (`suggestion.js`, `edit_suggestion.js`):**
   - Áp dụng kỹ thuật Debounce 100ms trên các ô nhập gợi ý trong `public/assets/js/QC/suggestion.js`, `public/assets/js/Winding/suggestion.js`, `public/assets/js/Extrusion/suggestion.js`, `public/assets/js/Extrusion/edit_suggestion.js`.
   - Xử lý nhận diện khớp chính xác (`exactMatch`) ngay lập tức không có độ trễ nhằm phục vụ tức thời thao tác quét mã vạch / mã QR.
   - Tích hợp `DocumentFragment` và giới hạn tối đa 40 kết quả hiển thị (`MAX_SUGGESTIONS = 40`), ngăn chặn việc tạo hàng ngàn DOM nodes gây sụt giảm khung hình và đơ giật giao diện khi gõ phím trên màn hình cảm ứng công nghiệp.
5. **Kiểm tra Xác minh & Tính Ổn định:**
   - Đạt 100% cú pháp sạch trên toàn bộ 75 file PHP (`php -l`) và 36 file JS (`node -c`).
   - Kiểm tra kết quả gọi API và repository: `getListData()` chạy chỉ mất 13.35ms, các danh mục và số lượng bản ghi trả về đầy đủ, chính xác.

---

## [TASK-041] - 2026-04-20 - Nâng Cấp Toàn Diện Giao Diện Hộp Thoại Hủy Bobin Tại Trang Điều Chỉnh Cuộn (Quick Chips, Live Preview, CSS Chuẩn Hóa)

### 1. Bối cảnh & Yêu cầu:
- Tối ưu hóa giao diện và trải nghiệm người dùng (UI/UX) khi nhấn nút "Hủy Bobin" tại trang Điều chỉnh Cuộn (`windingEditBobinView.php`).
- Khắc phục tình trạng phụ thuộc vào inline styles hoặc CSS gián tiếp của ConfirmDialog, chuyển đổi sang kiến trúc CSS chuyên biệt trong `windingEditBobin.css`.
- Nâng cấp khả năng nhập liệu tại phân xưởng với gợi ý nhanh (Quick Chips) cho cả lý do hủy và số lượng thành phẩm, đồng bộ hóa xem trước ghi chú thời gian thực.

### 2. Các thay đổi kỹ thuật:
1. **Thiết kế & Tích hợp CSS Modal Chuyên Biệt (`public/assets/css/windingEditBobin.css`):**
   - Bổ sung trọn bộ hệ thống class CSS `.wnd-modal-overlay`, `.wnd-modal-box`, `.wnd-modal-header`, `.wnd-modal-close`, `.wnd-modal-body`, `.wnd-cancel-alert`, `.wnd-cancel-summary`, `.wnd-modal-field`, `.wnd-quick-chips`, `.wnd-chip-btn`, `.wnd-cancel-preview-box`, `.wnd-modal-footer`, `.wnd-btn-modal-cancel`, `.wnd-btn-modal-confirm`.
   - Tạo hiệu ứng mở mượt mà (`wndModalFadeIn`, `wndModalZoomIn`), đổ bóng đa lớp và bo góc hiện đại.
   - Hỗ trợ responsive toàn diện: co giãn tối ưu trên tablet công nghiệp và điện thoại di động (chuyển nút thành full-width, lưới tóm tắt 1 cột).
2. **Nâng cấp Logic & Trải nghiệm Hộp thoại (`public/assets/js/Winding/edit_submit.js`):**
   - Viết lại hàm `handleWindingEditCancel`:
     - Header với tone màu đỏ cảnh báo (Crimson Gradient), biểu tượng 🗑️ và nút đóng tròn `✕` tương tác.
     - Khung tóm tắt Bobin dạng 2 cột hiển thị đầy đủ Mã Bobin (`#...` monospace), Sản phẩm (xanh dương), Máy cuộn, Nhân viên cuộn.
     - Ô nhập "Lý do hủy Bobin": Tích hợp **Quick Suggestion Chips** chuẩn: **Thông khí, Ngoại quan, A1, A2, A3, A4, A5**. Nhấp chọn có thể điền ngay, nhấp lại để gỡ bỏ hoặc kết hợp nhiều lý do ngăn cách bởi dấu phẩy; đồng bộ trạng thái `.active` theo thời gian thực.
     - Ô nhập "Số lượng thành phẩm đã chạy trước khi hủy": Tích hợp huy hiệu đơn vị **"Cuộn"** (vi: Cuộn, en: Rolls, ja: 巻), loại bỏ hoàn toàn khối gợi ý nhanh số lượng theo yêu cầu.
     - Khung xem trước (Live Preview): Cập nhật tự động thời gian thực nội dung chuỗi `Số thành phẩm đã chạy: {qty} Cuộn | Lý do hủy: {reason}` và đồng bộ trạng thái active của các chip.
     - Phím tắt công thái học: Hỗ trợ phím `Escape` để hủy, phím `Enter` để xác nhận nhanh, click ngoài backdrop để đóng, tự động focus vào ô nhập lý do.
     - Trạng thái tải: Nút xác nhận chuyển sang `⏳ Đang xử lý...` và khóa click kép khi đang thực hiện request.
3. **Cập nhật Backend nhận diện thông khí đa ngôn ngữ (`app/services/BobinServices.php`):**
   - Bổ sung nhận diện các biến thể "Thông khí", "Airflow", "通気" trong `winding_note` để tự động chuyển kết quả kiểm tra thông khí sang "Thất bại" khi hủy Bobin.
4. **Đa ngôn ngữ Tập trung (`config/default_translations.json`):**
   - Đồng bộ 100% các từ khóa đa ngôn ngữ cho cả 3 ngôn ngữ (`vi`, `en`, `ja`): `chip_reason_airflow`, `chip_reason_appearance`, `chip_reason_a1`, `chip_reason_a2`, `chip_reason_a3`, `chip_reason_a4`, `chip_reason_a5`, `qty_unit_roll`, `ph_winding_cancel_finished_qty`.

---

## [TASK-040] - 2026-04-20 - Lọc Danh Sách Bobin Hoàn Thành Cuộn Trong Vòng 24 Giờ & Bổ Sung Tính Năng Hủy Bobin Kèm Số Thành Phẩm Tại Trang Điều Chỉnh Cuộn

### 1. Bối cảnh & Yêu cầu:
- **Lọc danh sách Bobin tại trang "Nhóm Cuộn - Xác nhận hoàn thành Bobin":**
  - Chỉ hiển thị các Bobin ở trạng thái `Busy_Checked` và `Rolled`.
  - Đối với trạng thái `Rolled`, giới hạn hiển thị các Bobin có `updated_time` trong vòng 24 tiếng so với thời điểm hiện tại (`updated_time >= DATE_SUB(NOW(), INTERVAL 24 HOUR)` thay vì `INTERVAL 3 DAY` trước đây).
- **Tính năng Hủy Bobin tại trang "Điều chỉnh cuộn" (`windingEditBobinView.php`):**
  1. Bổ sung nút "Hủy Bobin" tương ứng với từng thẻ Bobin để đưa Bobin vào danh sách chờ hủy (`Pending_Cancellation`).
  2. Khi nhấn nút "Hủy Bobin", hiển thị dialog bao gồm:
     - 1 textfield "Ghi chú" lý do hủy Bobin (bắt buộc nhập).
     - 1 textfield nhập "Số lượng thành phẩm đã chạy được từ Bobin đó trước khi hủy" (type number, mặc định 0).
     - Gom cả 2 dữ liệu thành chuỗi chuẩn: `Số thành phẩm đã chạy: {qty} | Lý do hủy: {reason}` và gán vào trường `winding_note`.

### 2. Các thay đổi kỹ thuật:
1. **Truy vấn cơ sở dữ liệu (`BobinRepository.php`):**
   - Trong `getDetailBobinsForWinding()` và `countDetailBobinsForWinding()`: Cập nhật điều kiện lọc `INTERVAL 3 DAY` thành `INTERVAL 24 HOUR` chuẩn xác theo mốc 24 tiếng.
2. **Backend Controller & DTO (`BobinWindingCancelDTO.php`, `BobinController.php`, `BobinServices.php`):**
   - `BobinWindingCancelDTO.php`: Mở rộng thêm `$source`, `$finished_product_qty`, `$cancel_reason`.
   - `BobinController::validFormWinding_Cancel()`: Hỗ trợ linh hoạt khi nguồn hủy là `winding_edit` (không áp đặt chỉ chọn riêng "Thông khí"/"Ngoại quan"), đồng thời tự động bổ sung fallback thông tin nhân viên từ session nếu bản ghi ban đầu chưa cập nhật.
   - `BobinServices::windingCancelBobin()`: Nâng cấp hàm tìm kiếm nhân viên hỗ trợ fallback session tài khoản thực hiện, lưu vết lịch sử `pending_cancel` trong `update_history` và bảo toàn định dạng `winding_note`.
3. **Giao diện & Logic Điều chỉnh Cuộn (`windingEditBobinView.php`, `windingEditBobin.css`, `Winding/edit_submit.js`):**
   - `windingEditBobinView.php`: Thêm nút `.wnd-btn-delete.btn-cancel-bobin` trên mỗi card và đối tượng `window.CURRENT_USER` cung cấp mã/tên người dùng đăng nhập.
   - `public/assets/css/windingEditBobin.css`: Thêm kiểu dáng nút `.wnd-btn-delete` với tone đỏ cảnh báo và hiệu ứng hover đồng bộ giao diện.
   - `public/assets/js/Winding/edit_submit.js`:
     - Tự động ẩn/hiện nút "Hủy Bobin" khi chuyển đổi qua lại giữa chế độ xem và chế độ sửa.
     - Xây dựng hộp thoại dialog tương tác `handleWindingEditCancel`: Hiển thị tóm tắt thông tin Bobin, 2 textfield nhập liệu, hộp xem trước (live preview) nội dung `winding_note` theo thời gian thực và xác thực dữ liệu trước khi gửi PUT tới `bobin/windingCancelBobin`.
4. **Hệ thống Đa ngôn ngữ (`config/default_translations.json`):**
   - Bổ sung 8 từ khóa dịch mới cho cả 3 ngôn ngữ (`vi`, `en`, `ja`) phục vụ tiêu đề dialog, nhãn trường, placeholder và thông báo lỗi.

---

## [TASK-039] - 2026-04-20 - Đồng Bộ 4 Tiêu Chí Ngoại Quan Mới Vào Nhóm Đùn Check (9 Tiêu Chí) & Khắc Phục Lỗi Nút Bấm Không Hoạt Động Tại Trang QC (`qcView.php`)

### 1. Bối cảnh & Yêu cầu:
- **Đồng bộ 4 trường dữ liệu mới vào Nhóm Đùn Tự Kiểm Tra (Extrusion Check):** Bên cạnh 5 tiêu chí ban đầu ("Đường kính", "Gel", "Dị vật", "Màu sắc", "Chữ in"), bổ sung đồng bộ 4 tiêu chí mới ("Vón cục", "Trầy", "Xước", "Biến dạng") vào công đoạn Đùn tự kiểm tra (nâng tổng số lên 9 tiêu chí ngoại quan).
- **Khắc phục lỗi nút bấm tại trang QC (`qcView.php`):** Trên trang QC hiện tại, người dùng phản ánh các nút nhấn không hoạt động được. Cần điều tra tìm ra nguyên nhân gốc rễ và sửa chữa triệt để.

### 2. Nguyên nhân & Cách khắc phục lỗi nút bấm trang QC:
- **Nguyên nhân cốt lõi:** Trong `app/views/qcView.php`, vòng lặp con duyệt qua danh sách công tắc ngoại quan (`foreach ($qcItems as $key => $item):`) đã dùng biến tên `$item`, trùng và ghi đè lên biến `$item` của vòng lặp cha (`foreach ($bobins as $item):`). Khi vòng lặp con kết thúc, biến `$item` mang giá trị mảng lỗi ngoại quan (`['key' => 'crit_deformation', 'lbl' => 'Biến dạng']`), khiến các thuộc tính `$item['bobin_identification_code']`, `$item['bobin_key_code']` và `$item['updated_time']` ở các nút hành động bên dưới bị `undefined` (chuỗi rỗng `""`). Khi người dùng nhấn nút "Xác nhận đã kiểm tra", "Đổi loại", hoặc "Hủy Bobin", các hàm JS `handleConfirm(this, '', '')`, `handleChangeType(this, '', '')`, `handleCancel(this, '', '')` đều bị điều kiện kiểm tra `if (!bobinCode || !bobinKeyCode) return;` chặn lại ngay lập tức.
- **Biện pháp xử lý:**
  1. Đổi tên biến vòng lặp con thành `$qcDefect` (`foreach ($qcItems as $key => $qcDefect):`), khôi phục toàn bộ giá trị `$item['bobin_identification_code']` và `$item['bobin_key_code']` cho tất cả các nút hành động.
  2. Chuyển class container `.qc-badges` thành `.qc-switches-grid` chuẩn hóa bố cục 4 cột (2 cột trên mobile/tablet) theo CSS `listBobin_QC.css`.
  3. Bổ sung sự kiện click trên toàn bộ khối `.vi-item-switch` và con trỏ pointer để người dùng có thể nhấp trực tiếp vào tên tiêu chí hoặc nút OK để bật/tắt tiện lợi.
  4. Hỗ trợ nhập mã NV QC nếu session chưa có mã nhân viên (không khóa cứng `readonly`), cho phép tự động gợi ý và điền tên nhân viên qua `suggestion.js`.
  5. Xóa bỏ modal thừa `#modalChangeType` chứa các hàm onclick chưa được định nghĩa.

### 3. Đồng bộ 4 tiêu chí mới vào Nhóm Đùn Tự Kiểm Tra (Extrusion Check):
1. **Entity & DTO Backend:**
   - `app/entities/ExtrusionCheckEntity.php`: Bổ sung 4 thuộc tính boolean (`$clump`, `$scratch`, `$streak`, `$deformation`), mặc định `true`, hàm `fromArray()` tương thích ngược alias.
   - `app/dtos/Bobin/BobinCreateDTO.php` & `app/dtos/Bobin/BobinExtUpdateDTO.php`: Bổ sung 4 trường `ext_check_clump`, `ext_check_scratch`, `ext_check_streak`, `ext_check_deformation` và ánh xạ request tương ứng.
   - `app/services/BobinServices.php`: Khởi tạo đối tượng `ExtrusionCheckEntity` truyền đầy đủ 9 tham số khi tạo mới hoặc điều chỉnh Bobin.
2. **Giao diện tạo & điều chỉnh Đùn (`extrusionView.php`, `extrusionEditBobinView.php`):**
   - `app/views/extrusionView.php`: Mở rộng mảng `$checks` thành 9 tiêu chí với nhãn `data-i18n`.
   - `public/assets/js/Extrusion/submit.js`: Cập nhật `extLabels` và popup xem trước hiển thị đủ 9 tiêu chí.
   - `app/views/extrusionEditBobinView.php`: Cập nhật logic tính KPI kiểm tra ngoại quan đùn và mảng `$checks` 9 tiêu chí.
   - `public/assets/js/Extrusion/edit_submit.js`: Thu thập đầy đủ 9 tiêu chí trong payload và hiển thị popup xem trước.
3. **Đồng bộ hiển thị dải Đùn tự kiểm tra tại 6 màn hình tham khảo:**
   - `app/views/qcView.php`, `app/views/qcEditBobinView.php`, `app/views/windingView.php`, `app/views/listBobinDetailView.php`, `app/views/listBobinHistoryView.php`, `app/views/listPendingCancellationView.php`: Cập nhật dải huy hiệu "🏭 Đùn tự kiểm tra" hiển thị đầy đủ 9 tiêu chí ngoại quan với nhãn đa ngôn ngữ.

---

## [TASK-038] - 2026-04-20 - Mở Rộng Tiêu Chí Kiểm Tra Ngoại Quan QC Với 4 Hạng Mục Mới: "Vón Cục", "Trầy", "Xước", "Biến Dạng"

### 1. Bối cảnh & Yêu cầu:
- Tại trang QC (`qcView.php`), bên cạnh 4 tiêu chí ngoại quan hiện có ("Gel", "Dị vật", "Màu sắc", "Chữ in"), bổ sung thêm 4 tiêu chí mới: "Vón cục", "Trầy", "Xước", "Biến dạng" (nâng tổng số tiêu chí kiểm tra ngoại quan lên 8 hạng mục).
- Toàn bộ luồng thao tác liên quan phải hỗ trợ đồng bộ:
  1. Trang QC chính (`qcView.php`): Giao diện 8 công tắc bật/tắt (toggle switch) trực quan.
  2. Luồng xác nhận hoàn tất QC, luồng chuyển loại Bobin và luồng QC hủy Bobin (`public/assets/js/QC/submit.js`): Gửi đầy đủ 8 tiêu chí lên backend, xem trước trực quan hộp thoại xác nhận dạng lưới 2 cột.
  3. Trang điều chỉnh thông tin QC (`app/views/qcEditBobinView.php`, `public/assets/js/QC/edit_submit.js`): Cho phép xem và chỉnh sửa toàn bộ 8 tiêu chí.
  4. Các trang hiển thị thông tin Bobin (`windingView.php`, `listBobinDetailView.php`, `listBobinHistoryView.php`, `listPendingCancellationView.php`, `listBobinView.php`): Hiển thị đồng bộ huy hiệu (badges) 8 tiêu chí.
  5. Xuất file Excel/CSV lịch sử và chi tiết (`BobinController::exportHistoryExcel`, `BobinController::exportDetailExcel`): Tách đủ 8 cột tiêu chí.
  6. Hỗ trợ đầy đủ hệ thống Đa ngôn ngữ (`vi`, `en`, `ja`) và tương thích ngược với các bản ghi dữ liệu cũ.

### 2. Các thay đổi đã thực hiện:
1. **Từ điển Đa ngôn ngữ (`config/default_translations.json`, `public/assets/js/i18n.js`):**
   - Bổ sung 8 từ khóa mới: `crit_clump`, `crit_scratch`, `crit_streak`, `crit_deformation` và `chk_clump`, `chk_scratch`, `chk_streak`, `chk_deformation` trong cả 3 ngôn ngữ (`vi`, `en`, `ja`) tại `config/default_translations.json` (tổng số từ khóa đạt 589 keys đồng bộ 100%).
   - Cập nhật `DOM_MAPPINGS.labels` và mảng dịch tự động `DEFECT_NAMES` trong `public/assets/js/i18n.js`.
2. **Backend Entities, Models & DTOs:**
   - Cập nhật `DefectEntity.php` và `DetectModel.php`: Bổ sung 4 thuộc tính boolean (`$clump`, `$scratch`, `$streak`, `$deformation`), phương thức `jsonSerialize()` và `fromArray()` tương thích ngược với các tên alias (`lump`, `scuff`, `deformed`).
   - Cập nhật `BobinQCUpdateDTO.php`, `BobinQCCancelDTO.php`, `BobinUpdateQCDTO.php` tiếp nhận và chuyển đổi 4 trường lỗi mới từ request.
3. **Backend Service, Controller & Xuất dữ liệu:**
   - Cập nhật `BobinServices::createVisualInspection()` và các phương thức `updateQCBobin()`, `qcCancelBobin()`, `updateQCAndChangeTypeBobin()` để xử lý chuẩn hóa 8 tiêu chí ngoại quan.
   - Cập nhật `BobinController.php`: Xử lý 8 tiêu chí trong `updateQCEditBobin()`, kiểm tra `$hasNG` trong `validFormQC_Cancel()`, và thêm 4 cột tiêu chí mới vào file xuất Excel/CSV lịch sử và chi tiết (`exportHistoryExcel`, `exportDetailExcel`).
4. **Giao diện người dùng & Frontend Scripts:**
   - `app/views/qcView.php`: Mở rộng danh sách `$qcItems` thành 8 công tắc toggle switch với `data-i18n`.
   - `public/assets/js/QC/submit.js`: Thu thập đủ 8 tiêu chí cho cả 3 luồng xác nhận QC, chuyển loại Bobin và hủy Bobin; tối ưu giao diện hộp thoại xác nhận dạng lưới 2 cột gọn gàng và hỗ trợ i18n.
   - `app/views/qcEditBobinView.php`: Cập nhật thống kê KPI và mảng `$qcChecks` sang 8 tiêu chí.
   - `public/assets/js/QC/edit_submit.js`: Thu thập đủ 8 tiêu chí khi điều chỉnh QC, hiển thị lưới 2 cột trong hộp thoại xác nhận và gửi payload cập nhật.
   - `app/views/windingView.php`, `app/views/listBobinDetailView.php`, `app/views/listBobinHistoryView.php`, `app/views/listPendingCancellationView.php`, `app/views/listBobinView.php`: Cập nhật hiển thị huy hiệu (badges) kết quả kiểm tra ngoại quan đầy đủ 8 tiêu chí.

---

## [TASK-037] - 2026-04-20 - Kiểm Tra & Đồng Bộ Toàn Diện Hệ Thống Đa Ngôn Ngữ (`vi`, `en`, `ja`) Trên Toàn Dự Án & Đề Xuất Các Hướng Cải Tiến Kỹ Thuật

### 1. Bối cảnh & Yêu cầu:
- Kiểm tra toàn diện việc sử dụng đa ngôn ngữ (`vi`, `en`, `ja`) trên toàn bộ dự án (Views, Components, Controllers, JavaScript, Từ điển Backend & Frontend) để phát hiện và khắc phục triệt để các vị trí còn thiếu sót chuyển đổi.
- Kiểm tra kiến trúc tổng thể và đề xuất các hướng cải tiến thiết thực cho dự án để người dùng xem xét.

### 2. Kết quả kiểm tra & Giải pháp đã triển khai:
1. **Đồng bộ 100% Từ điển Đa ngôn ngữ giữa Backend (`app/core/Language.php`) và Frontend (`public/assets/js/i18n.js`):**
   - Bổ sung **14 từ khóa** từng có trong `public/assets/js/i18n.js` nhưng bị thiếu trong `app/core/Language.php`: `confirm_pwd_label`, `confirm_pwd_ph`, `confirm_pwd_empty`, `confirm_edit_title`, `confirm_edit_sub`, `saving`, `saved`, `save_failed`, `audit_trail_title`, `audit_stage_extrusion`, `audit_stage_qc`, `audit_stage_winding`, `audit_updater`, `audit_time` (khắc phục lỗi hiển thị raw key khi gọi `__('audit_trail_title')`, `__('audit_updater')`, `__('confirm_pwd_label')` trên `listBobinHistoryView.php` và `listPendingCancellationView.php`).
   - Bổ sung **3 từ khóa** từng có trong `app/core/Language.php` nhưng thiếu trong `public/assets/js/i18n.js`: `perm_active_status`, `perm_inactive_status`, `btn_delete`.
   - Bổ sung **7 từ khóa** đang được sử dụng trong các View/JS nhưng chưa được định nghĩa ở cả 2 từ điển: `no_data_found`, `showing`, `col_size`, `field_defect_note`, `btn_back`, `warning`, `info`.
   - Bổ sung **16 từ khóa mới** phục vụ chuyển đổi đa ngôn ngữ cho trang Đổi mật khẩu (`changePasswordView.php`), hộp thoại Đăng xuất (`logout.js`) và chức năng Sao chép dữ liệu (`copyText.js`): `cp_guide_title`, `cp_guide_1`, `cp_guide_2`, `cp_guide_3`, `cp_err_empty`, `cp_err_min_len`, `cp_err_mismatch`, `cp_err_same_old`, `cp_err_weak_forbidden`, `cp_err_failed`, `logout_confirm_title`, `logout_confirm_msg`, `copy_empty_err`, `copy_done_btn`, `copy_failed_err`.
   - Sau khi cập nhật, cả `Language.php` và `i18n.js` đều đạt **581 từ khóa đồng nhất 100%** trên cả 3 ngôn ngữ (`vi`, `en`, `ja`).
2. **Khắc phục toàn bộ các vị trí văn bản tĩnh/động chưa chuyển đổi trên giao diện & JS:**
   - **Header (`app/views/components/header.php`):** Thay thế nhãn vai trò người dùng hardcode tiếng Việt (`Nhóm Đùn`, `Nhóm Cuộn`, `Nhóm QC`, `Admin`) trên `.header-user-role` bằng `data-i18n` kết hợp `__()` (`emp_role_extrusion`, `emp_role_winding`, `emp_role_qc`, `emp_role_admin`).
   - **Trang Đổi mật khẩu (`app/views/changePasswordView.php`):** Chuyển đổi toàn bộ văn bản HTML (tiêu đề, phụ đề, hộp cảnh báo đăng nhập lần đầu, nhãn Mã nhân viên/Tài khoản, khung hướng dẫn quy tắc bảo mật, bản quyền) và thông báo JavaScript (thang đo độ mạnh mật khẩu và các Toast kiểm tra hợp lệ) sang `data-i18n`, `__()`, và `window.t()`.
   - **Trang Danh sách nhân viên (`app/views/employeeListView.php`):** Tách thẻ `<span data-i18n="...">` riêng biệt trong `#empCounter` (`emp_showing`, `emp_staff`) và tiêu đề cột `emp_code / emp_username` để khi chuyển đổi ngôn ngữ không ghi đè mất số đếm nhân viên.
   - **Bộ máy dịch thuật Frontend (`public/assets/js/i18n.js`):**
     - Mở rộng `DOM_MAPPINGS.labels`, `DOM_MAPPINGS.buttons`, `DOM_MAPPINGS.statusBadges` bao phủ toàn bộ nhãn thẻ Bobin và nút thao tác trên các trang Điều chỉnh / Lịch sử / Chờ hủy.
     - Hỗ trợ thuộc tính `[data-i18n-title]` trong `applyDataI18n()` và hỗ trợ đồng thời cả 2 định dạng placeholder `{current}` / `:current` cho phân trang `page_info`.
     - Bổ sung hàm toàn cục `window.translateDynamicText(rawText)` để tự động dịch các thông báo Toast/Dialog phát sinh động sang `en` và `ja`.
     - Bổ sung **Mục J trong `deepTranslateDOM()`** tự động dịch hiển thị của `<select><option>` (giữ nguyên `.value` gửi về server), dịch các giá trị mặc định trên thẻ Bobin (`Chưa cập nhật`, `Chưa kiểm`, `Không có ghi chú`, Loại Bobin, Ca làm việc, Thông khí), thẻ KPI, nhãn lọc ngày, menu xuất dữ liệu (`.exp-title`, `.exp-desc`) và các huy hiệu lỗi ngoại quan (`.vi-badge`, `.ext-chip`, `.qc-chip`).
   - **Các tiện ích JS (`public/assets/js/toast.js`, `public/assets/js/logout.js`, `public/assets/js/copyText.js`):** Tích hợp `window.t()` và `window.translateDynamicText()` cho toàn bộ thông báo Toast, hộp thoại xác nhận đăng xuất và nút sao chép dữ liệu.
3. **Triển khai Đề xuất 1 — Hợp nhất Nguồn Từ Điển Đa Ngôn Ngữ Duy Nhất (Single Source of Truth cho i18n):**
   - **Tách nguồn từ điển mặc định duy nhất (`config/default_translations.json`):** Toàn bộ **581 từ khóa × 3 ngôn ngữ (`vi`, `en`, `ja`)** được lưu trữ tập trung tại `config/default_translations.json` (song song với `config/custom_translations.json` dành cho các bản dịch tùy chỉnh động).
   - **Tối giản `app/core/Language.php` (giảm từ 2.072 dòng xuống 367 dòng):** Đọc trực tiếp từ `config/default_translations.json` và gộp với `config/custom_translations.json`, bổ sung `getDefaultDictionary()`, `getJsonDefaultDictionary()`, `getMergedDictionary()`, `getDictionaryHash()`, `getCurrentLanguage()`, và `renderJsBootstrap()`.
   - **Tự động nhúng Bootstrap trước `i18n.js` tại `app/core/Controller.php`:** Phương thức `Controller::view()` tự động chèn khối `<script>` khởi tạo `window.__I18N_HASH__`, `window.__DEFAULT_I18N__` và `window.__CUSTOM_I18N__` **đúng 1 lần duy nhất** ngay trước thẻ `<script src=".../i18n.js">` trong `<head>` của mọi trang View (tránh lặp dữ liệu giữa `sidebar.php` và `header.php`).
   - **Tối giản `public/assets/js/i18n.js` (giảm từ 3.565 dòng xuống 1.701 dòng):** Loại bỏ hoàn toàn 1.955 dòng đối tượng `DICT` bị trùng lặp; tự động khởi tạo đồng bộ từ `window.__DEFAULT_I18N__` / `localStorage` (`webbobin_default_i18n`, `webbobin_custom_i18n`, `webbobin_i18n_hash`) và tách biệt `DEFAULT_DICT` với `CUSTOM_DICT` trong `rebuildMergedDict()` để khi khôi phục (reset) từ khóa tùy chỉnh sẽ quay về ngay giá trị mặc định mà không cần tải lại trang.
   - **Bổ sung endpoint công khai `auth/getTranslations` (`AuthController.php`, `Router.php`):** Cho phép đồng bộ dự phòng cả `default_dict`, `custom_dict` và `hash` ngay cả trên các trang không đăng nhập.

---

## [TASK-036] - 2026-04-20 - Cấp Quyền Đưa Bobin Vào Danh Sách Chờ Hủy Cho Toàn Bộ Nhân Viên Extrusion & Khắc Phục Lỗi Nút "Ngoại Quan" / "Thông Khí" Kèm Nâng Cấp UI/UX Hủy Bobin Tại Trang Nhóm Cuộn

### 1. Bối cảnh & Yêu cầu:
- **Phân quyền Extrusion:** Toàn bộ nhân viên nhóm Đùn (Extrusion) đều được phép đưa Bobin vào danh sách chờ hủy (`extrusion_edit_cancel`).
- **Trang Nhóm cuộn (`windingView.php`):** Khi nhấn nút Hủy Bobin, người dùng không nhấn chọn được nút **"Ngoại quan"** và **"Thông khí"** trên hộp thoại xác nhận hủy trước khi xác nhận hủy. Cần kiểm tra nguyên nhân gốc rễ, khắc phục triệt để và điều chỉnh UI/UX hợp lý, thuận tiện cho thao tác tại xưởng.

### 2. Nguyên nhân gốc rễ & Giải pháp đã triển khai:
1. **Cập nhật Phân quyền cho toàn bộ nhân viên nhóm Đùn (`app/core/AuthHelper.php`, `production_db.employee_list`, `public/assets/sql/production_db_Data.sql`):**
   - Trong `AuthHelper::getDefaultPermissionsForRole('extrusion')` và logic tương thích ngược trong `AuthHelper::getUserPermissions()`, chuyển quyền mặc định của vai trò `extrusion` từ `extrusion_edit_only` sang `extrusion_edit_cancel` (`['extrusion_create', 'extrusion_edit_cancel', 'bobin_list', 'bobin_history', 'pending_cancel_view']`).
   - Đồng bộ toàn bộ 46 nhân viên nhóm Đùn tiêu chuẩn trong bảng `production_db.employee_list` và file `public/assets/sql/production_db_Data.sql` sang `extrusion_edit_cancel`. Hiện tại **100% nhân viên nhóm Đùn (52/52 nhân viên)** đều có quyền đưa Bobin vào danh sách chờ hủy (`bobin/extrusionDeleteBobin`), trong khi quyền duyệt hủy hoàn toàn (`pending_cancel_edit`) vẫn được bảo vệ chặt chẽ cho Admin và các nhân viên được chỉ định.
2. **Xác định nguyên nhân gốc rễ & Khắc phục lỗi không nhấn được nút "Ngoại quan" / "Thông khí" (`public/assets/js/Winding/submit.js`, `public/assets/js/logout.js`):**
   - **Nguyên nhân gốc rễ:** Trong `app/views/windingView.php`, file `public/assets/js/logout.js` được nạp ở dòng 358 trước `public/assets/js/Winding/submit.js` (dòng 360). File `logout.js` đã khởi tạo trước đối tượng toàn cục `window.ConfirmDialog` (phiên bản cơ bản chỉ nhận 3 tham số `show(title, contentHTML, onConfirm)`, không nhận callback `onToggle`, không gắn sự kiện click cho `.error-toggle-btn`, và luôn đóng hộp thoại ngay cả khi `onConfirm()` trả về `false`). Khi `Winding/submit.js` kiểm tra `if (typeof ConfirmDialog === "undefined")`, điều kiện trả về `false` nên toàn bộ phần định nghĩa `ConfirmDialog` nâng cao của trang Cuộn bị bỏ qua.
   - **Giải pháp:**
     - Gỡ bỏ điều kiện chặn `if (typeof ConfirmDialog === "undefined")` trong `Winding/submit.js` để luôn đăng ký `window.ConfirmDialog` đầy đủ tính năng với style riêng (`#winding-confirm-dialog-style`), gắn trực tiếp sự kiện `click` (`e.preventDefault(); e.stopPropagation();`) cho từng nút `.error-toggle-btn`, tự động chuyển đổi class `.active`, đổi huy hiệu trạng thái (`☑ NG` / `☐ OK`), gọi `onToggle(box)` và giữ hộp thoại mở nếu `onConfirm()` trả về `false`.
     - Đồng thời nâng cấp `window.ConfirmDialog` trong `public/assets/js/logout.js` để hỗ trợ đầy đủ tham số thứ 4 `onToggle` và xử lý `.error-toggle-btn` đồng nhất trên toàn hệ thống.
3. **Cải tiến UI/UX quy trình Hủy Bobin tại trang Nhóm cuộn (`app/views/windingView.php`, `public/assets/css/listBobin_Winding.css`, `public/assets/js/Winding/submit.js`, `app/core/Language.php`, `public/assets/js/i18n.js`):**
   - Bổ sung thanh chọn nhanh hạng mục lỗi (**🔍 Ngoại quan** và **💨 Thông khí**) trực tiếp ngay trên thẻ Bobin (`.winding-cancel-quick-group` / `.card-error-toggle-btn`) cạnh ô Ghi chú cuộn, đồng bộ 2 chiều với hộp thoại xác nhận hủy.
   - Mở ngay hộp thoại xác nhận hủy khi đã có Mã máy cuộn & Nhân viên thao tác (không bắt buộc phải nhập tay ghi chú trước khi nhấn nút Hủy Bobin).
   - Thiết kế lại hộp thoại xác nhận hủy gồm 2 bước rõ ràng:
     - **Bước 1:** 2 thẻ nút bấm lớn (**🔍 Ngoại quan** & **💨 Thông khí**) kèm mô tả phụ và huy hiệu trạng thái `☐ OK` / `☑ NG` rõ nét, dễ bấm trên màn hình cảm ứng.
     - **Bước 2:** Ô nhập ghi chú chi tiết ngay trong hộp thoại (đồng bộ 2 chiều với ô ghi chú trên thẻ Bobin) và khung xem trước trực tiếp nội dung lý do hủy tổng hợp (`Cuộn <Máy> yêu cầu Hủy - Lý do: Ngoại quan, Thông khí NG`).
   - Bổ sung đầy đủ 11 từ khóa đa ngôn ngữ (`vi`, `en`, `ja`) trong `app/core/Language.php` và `public/assets/js/i18n.js`.

---

## [TASK-035] - 2026-04-20 - Phân Quyền Và Đồng Bộ Quyền Cho Các Nhóm Người Dùng (Admin, Đùn, QC, Winding) & Bổ Sung 2 Cấp Quyền Tại Trang Điều Chỉnh Đùn (`Edit Only` vs `Edit + Cancel`)

### 1. Bối cảnh & Yêu cầu:
- Rà soát và đồng bộ hệ thống phân quyền giữa Database (`production_db.employee_list`), Backend/API và Frontend/UI theo 4 nhóm vai trò:
  - **Admin:** Toàn bộ quyền trên hệ thống.
  - **Winding:** Toàn bộ quyền nghiệp vụ Winding (`winding_confirm`, `winding_edit`); được xem 3 danh sách (`bobin_list`, `bobin_history`, `pending_cancel_view`) nhưng không được sửa dữ liệu ngoài phạm vi nghiệp vụ Winding.
  - **QC:** Toàn bộ quyền nghiệp vụ QC (`qc_check`, `qc_edit`); được xem 3 danh sách nhưng không được sửa dữ liệu ngoài phạm vi nghiệp vụ QC.
  - **Đùn (Extrusion):** Toàn bộ quyền nghiệp vụ Đùn (`extrusion_create`); được xem 3 danh sách và chỉnh sửa Bobin theo quyền được cấp.
- Tại trang **Điều chỉnh Đùn** (`extrusionEditBobinView.php`), bổ sung 2 cấp quyền loại trừ lẫn nhau:
  - **Edit Only (`extrusion_edit_only`):** Được chỉnh sửa Bobin nhưng không được đưa Bobin vào danh sách chờ hủy hoặc hủy Bobin.
  - **Edit + Cancel (`extrusion_edit_cancel`):** Được chỉnh sửa, đưa Bobin vào danh sách chờ hủy và hủy Bobin theo quy trình hiện tại.
- **Quyền hủy Bobin hoàn toàn (`pending_cancel_edit`):** Chỉ Admin và những nhân viên được chỉ định cụ thể mới được cấp quyền hủy Bobin hoàn toàn (`bobin/deleteBobin`, `bobin/restoreBobin`), kiểm soát chặt chẽ tại Backend/API.

### 2. Giải pháp đã triển khai:
1. **Cấu trúc quyền trung tâm & Đồng bộ phiên làm việc (`app/core/AuthHelper.php`, `app/core/Router.php`):**
   - Tách quyền Điều chỉnh Đùn thành `extrusion_edit_only` (`['bobin/extrusioneditbobinview', 'bobin/extrusionupdatebobin']`) và `extrusion_edit_cancel` (`['bobin/extrusioneditbobinview', 'bobin/extrusionupdatebobin', 'bobin/extrusiondeletebobin']`).
   - Bổ sung route `bobin/qccancelbobin` vào `qc_check` và `bobin/windingcancelbobin` vào `winding_confirm`; khóa chặt `bobin/deletebobin` và `bobin/restorebobin` chỉ dành cho `pending_cancel_edit`.
   - Bổ sung `AuthHelper::ensureSessionPermissionsFresh()` tự động đồng bộ `role` và `permissions` mới nhất từ `employee_list` vào `$_SESSION['user']` trên mỗi request.
   - Tại `Router::dispatch()`, kiểm tra `AuthHelper::isActionAllowed()` cho toàn bộ request đã đăng nhập và trả về HTTP 403 JSON chuẩn đối với mọi lời gọi API vượt quyền.
2. **Bảo vệ tất cả API & View tại Controller (`app/controllers/BobinController.php`, `app/controllers/EmployeeController.php`):**
   - Bổ sung kiểm tra `AuthHelper::hasPermission(...)` trực tiếp ở đầu toàn bộ các phương thức View và API trong `BobinController` (`extrusionDeleteBobin` bắt buộc `extrusion_edit_cancel`, `deleteBobin` và `restoreBobin` bắt buộc `pending_cancel_edit`, v.v.).
   - Cập nhật `EmployeeController` (`create`, `update`, `importExcel`, `getEmployeePermissions`, `updatePermissions`) để tự động đồng bộ cột `permissions` theo role và đảm bảo loại trừ lẫn nhau giữa `extrusion_edit_only` ↔ `extrusion_edit_cancel` và `pending_cancel_view` ↔ `pending_cancel_edit`.
3. **Giao diện Điều chỉnh Đùn & Quản trị Phân quyền (`app/views/extrusionEditBobinView.php`, `public/assets/js/Extrusion/edit_submit.js`, `app/views/employeePermissionsView.php`, `app/core/Language.php`, `public/assets/js\i18n.js`):**
   - Hiển thị huy hiệu cấp quyền (`Edit Only` / `Edit + Cancel`) trên `extrusionEditBobinView.php`; ẩn nút `"Hủy Bobin"` (`.btn-delete-scrap`) và chặn hàm `handleDelete()` khi tài khoản chỉ có quyền `extrusion_edit_only`.
   - Cập nhật trang `employeePermissionsView.php` hỗ trợ chọn loại trừ lẫn nhau cho `extrusion_edit_only` và `extrusion_edit_cancel`, kèm đầy đủ bản dịch `vi`, `en`, `ja`.
4. **Đồng bộ Database (`production_db.employee_list` & `public/assets/sql/production_db_Data.sql`):**
   - Đồng bộ 100% (132/132 nhân viên) trong CSDL và file SQL dump: 12 Admin toàn quyền; 6 nhân viên Đùn chỉ định (`id=12, 20, 21, 22, 24, 33`) có `extrusion_edit_cancel` & `pending_cancel_edit`; 2 nhân viên QC chỉ định (`id=15, 16`) và 5 nhân viên Winding chỉ định (`id=72, 75, 81, 82, 84`) có `pending_cancel_edit`; 107 nhân viên tiêu chuẩn còn lại nhận đúng bộ quyền chuẩn theo nhóm.

---

## [2026-04-20] - Chuẩn Hóa Hệ Thống Thông Báo Toast: Loại Bỏ Triệt Để Hiện Tượng Trùng Lặp (Duplicate Toast) Sau Mỗi Tác Vụ

### 1. Bối cảnh & Yêu cầu:
- Nhiều trang trên hệ thống xuất hiện **2 đến 3 thông báo Toast cùng lúc hoặc nối tiếp nhau** sau một tác vụ duy nhất (hiện song song góc trên phải + góc dưới phải, hiện cả trước và sau khi reload trang, hoặc vừa hiện banner vừa hiện Toast).
- Yêu cầu: **Sau mỗi tác vụ chỉ hiển thị duy nhất 1 Toast**.

### 2. Nguyên nhân gốc rễ & Giải pháp đã triển khai:
1. **Chuẩn hóa Singleton & Single-Instance tại `public/assets/js/toast.js`:**
   - Thêm cờ bảo vệ `window.__BOBIN_TOAST_INITIALIZED__` để ngăn `toast.js` khởi tạo 2 lần khi một số view nạp script này ở cả `<head>` và `components/header.php`.
   - Trong `Toast.show()`, luôn xóa sạch các Toast cũ trong `#bobin-toast-container` (`container.innerHTML = ''`) và ẩn mọi phần tử toast cục bộ (`.toast-message`, `.perm-toast.show`, `.lang-toast.show`) trước khi hiển thị Toast mới — đảm bảo trên màn hình **chỉ có tối đa 1 Toast tại mọi thời điểm**.
   - Trong `checkAutoToasts()`, ưu tiên hiển thị `sessionStorage` (`bobin_toast_flash`) và tự động dọn URL query (`?msg=`, `?success=`, `?error=`) mà không kích hoạt Toast thứ 2; đồng thời bỏ qua auto-toast từ URL `?error=` nếu trang đã có hộp cảnh báo lỗi inline (`.login-alert-error`, `.cp-alert-error`).
2. **Loại bỏ gọi kép ở các trang Quản trị & Đổi mật khẩu:**
   - `app/views/employeePermissionsView.php`: Thêm `return;` ngay sau `window.Toast.show(message, type)` trong `showToast()` để không kích hoạt thêm `#permToast` ở góc dưới phải.
   - `app/views/languageManageView.php`: Thêm `return;` ngay sau `window.Toast.show(message, type)` trong `showToast()`, đồng thời tại 4 tác vụ (Lưu từ điển, Khôi phục 1 key, Khôi phục toàn bộ, Thêm key mới) chỉ gọi `window.Toast.flash(...)` trước khi `window.location.reload()` thay vì gọi cả `Toast.flash` lẫn `showToast`.
   - `app/views/changePasswordView.php`: Chỉ gọi `Toast.flash(succMsg, "success")` trước khi chuyển trang thay vì gọi đồng thời `Toast.flash` và `Toast.show`.
   - `app/views/employeeListView.php`: Gỡ bỏ khối `<div class="msg-banner" id="msgBanner">` bị trùng lặp với cơ chế bắt `?msg=` tự động của `toast.js`.
   - `app/views/listBobinHistoryView.php` & `public/assets/js/Winding/submit.js`: Chuyển thông báo lỗi lịch sử sang `window.Toast.error()` đồng nhất và đưa `window.Toast.flash()` vào đúng callback xác nhận của `CopyTextDialog` trước khi reload.

---

## [TASK-034] - 2026-04-20 - Tách Phân Quyền Danh Sách Chờ Hủy (Xem & Chỉnh Sửa vs Chỉ Xem) Và Ghi Nhận "Đưa Vào Danh Sách Chờ Hủy" / "Xác Nhận Hủy" Trong `update_history`

### 1. Bối cảnh & Yêu cầu:
- Tại trang **Phân quyền truy cập** (`employeePermissionsView.php`), đối với mục **Danh sách chờ Hủy**, tách phân quyền thành 2 dạng độc lập:
  1. **Được xem và được chỉnh sửa thông tin** (`pending_cancel_edit`): Xem danh sách chờ hủy và được phép thực hiện thao tác **Khôi phục Bobin** hoặc **Xác nhận hủy**.
  2. **Chỉ được xem nhưng không được chỉnh sửa** (`pending_cancel_view`): Chỉ xem thông tin trên trang Danh sách chờ Hủy, không được phép bấm Khôi phục hay Xác nhận hủy.
- Tại trường `update_history` (lịch sử cập nhật của Bobin), ngoài các bản ghi điều chỉnh thông tin Bobin và khôi phục Bobin, bổ sung lưu vết đầy đủ cho 2 sự kiện: **"Đưa vào danh sách chờ hủy"** và **"Xác nhận hủy"**.

### 2. Giải pháp đã triển khai:
1. **Tách phân quyền Danh sách chờ Hủy thành 2 mức loại trừ lẫn nhau (`AuthHelper.php`, `EmployeeController.php`, `employeePermissionsView.php`, `listPendingCancellationView.php`, `BobinController.php`):**
   - Trong `AuthHelper::getAllPermissions()`, thay thế khóa `pending_cancel` bằng 2 khóa trong nhóm `monitoring`:
     - `pending_cancel_edit`: Ánh xạ tới `['bobin/listpendingcancellationview', 'bobin/deletebobin', 'bobin/restorebobin']`. Mặc định cấp cho vai trò `admin`.
     - `pending_cancel_view`: Ánh xạ tới `['bobin/listpendingcancellationview']`. Mặc định cấp cho các vai trò `extrusion`, `qc`, `winding`.
   - Hỗ trợ tương thích ngược toàn diện trong `AuthHelper::getUserPermissions()`, `hasPermission()` và `isActionAllowed()`:
     - Tài khoản có `pending_cancel_edit` hoặc `pending_cancel_view` đều thỏa mãn quyền xem trang Danh sách chờ Hủy (`hasPermission('pending_cancel')` / `hasPermission('pending_cancel_view')`).
     - Chỉ tài khoản có `pending_cancel_edit` mới thỏa mãn `hasPermission('pending_cancel_edit')` để gọi API `bobin/deleteBobin` và `bobin/restoreBobin`.
   - Trên trang `employeePermissionsView.php` và `EmployeeController::updatePermissions()`, thiết lập cơ chế loại trừ lẫn nhau (mutually exclusive): khi chọn `pending_cancel_edit` sẽ tự động bỏ chọn `pending_cancel_view` và ngược lại; thao tác "Chọn tất cả" tự động chọn mức quyền đầy đủ (`pending_cancel_edit`).
   - Trên trang `listPendingCancellationView.php`, kiểm tra `AuthHelper::hasPermission('pending_cancel_edit')`: nếu chỉ có quyền xem (`pending_cancel_view`), ẩn 2 nút **Khôi phục Bobin** và **Xác nhận Hủy**, thay bằng huy hiệu thông báo **Chế độ chỉ xem (Không có quyền chỉnh sửa / thao tác)** (`pending_view_only_notice`).
2. **Ghi nhận `"Đưa vào danh sách chờ hủy"` và `"Xác nhận hủy"` vào `update_history` (`BobinServices.php`, `BobinRepository.php`, `listBobinHistoryView.php`, `listPendingCancellationView.php`, `Language.php`, `i18n.js`):**
   - Khi chuyển Bobin sang trạng thái chờ hủy từ Nhóm Đùn (`BobinServices::extDeleteBobin()` / `BobinRepository::extrusionDeleteBobin()`), Nhóm QC (`BobinServices::qcCancelBobin()` / `BobinRepository::qcCancelBobin()`), hoặc Nhóm Cuộn (`BobinServices::windingCancelBobin()` / `BobinRepository::windingCancelBobin()`), hệ thống tạo bản ghi `update_history` với `action: "pending_cancel"`, `note: "Đưa vào danh sách chờ hủy"`, kèm thông tin công đoạn, nhân viên thao tác, thời gian và thay đổi trạng thái (`status: old -> Pending_Cancellation`) lưu đồng bộ vào `bobin_list_detail` và `bobin_history`.
   - Khi xác nhận hủy Bobin (`BobinServices::deleteBobin()` / `BobinRepository::deleteBobin()`, cũng như khi tự động hủy do tạo đè tại `BobinRepository::createNewBobin()`), hệ thống nối tiếp bản ghi `update_history` với `action: "confirm_cancel"`, `note: "Xác nhận hủy"`, người xác nhận, thời gian và thay đổi trạng thái (`Pending_Cancellation -> Cancelled`).
   - Nâng cấp giao diện hiển thị `update_history` trên `listBobinHistoryView.php` và bổ sung khung hiển thị lịch sử cập nhật trên thẻ `listPendingCancellationView.php`, hỗ trợ dịch đa ngôn ngữ đầy đủ (`vi`, `en`, `ja`).

---

## [2026-10-11] - Khắc Phục Sai Lệch Dữ Liệu Trang Điều Chỉnh QC & Bắt Buộc Chọn Lý Do ("Thông Khí" / "Ngoại Quan") Khi Hủy Bobin Tại Nhóm Cuộn (TASK-033)

### 1. Bối cảnh & Yêu cầu:
- Kiểm tra và sửa lỗi hiển thị sai dữ liệu tại trang **Điều chỉnh QC** (`qcEditBobinView.php`), đồng thời đảm bảo dữ liệu sau khi điều chỉnh QC đồng bộ hoàn toàn với trang Danh sách chi tiết và Lịch sử Bobin.
- Tại trang **Nhóm Cuộn** (`windingView.php` / `Winding/submit.js`), khi người dùng bấm Hủy Bobin, trên hộp thoại xác nhận (dialog review) bắt buộc phải chọn ít nhất **"Thông khí"** hoặc **"Ngoại quan"** thì mới được phép xác nhận hủy.

### 2. Nguyên nhân gốc rễ (Root Cause Analysis):
1. **Lệch cấu trúc JSON `visual_inspection` tại trang Điều chỉnh QC:**
   - Khi kiểm tra QC tại `qcView.php`, backend (`VisualInspectionEntity::toArray()`) lưu cột `visual_inspection` theo cấu trúc lồng chuẩn:
     `{"defects": {"gel": bool, "foreign_object": bool, "color_issue": bool, "print_quality": bool, "note": "..."}, "inspector_code": "...", "inspector_name": "...", "inspection_time": "..."}`.
   - Ngược lại, `qcEditBobinView.php` trước đây lại đọc ở cấp ngoài cùng (`$vi['defect_gel']`, `$vi['defect_foreign']`, `$vi['defect_color']`, `$vi['defect_print']`, `$vi['defect_note']`) khiến mọi Bobin vừa qua QC (kể cả Bobin có lỗi NG hoặc có ghi chú) đều bị hiển thị thành **4 tiêu chí OK hết**, mất ghi chú QC, và bộ đếm KPI trên đầu trang báo `Có lỗi ngoại quan = 0`.
   - Hơn nữa, hàm `BobinController::updateQCEditBobin()` khi lưu lại ghi đè JSON phẳng (`defect_gel`, ..., `inspected_at`) vào DB, làm mất object `defects` và khiến trang Danh sách chi tiết (`listBobinDetailView.php`) / Lịch sử (`listBobinHistoryView.php`) hiển thị sai ngược trở lại.
   - Ngoài ra, `BobinRepository::adminUpdateQCBobin()` trước đây chạy `UPDATE bobin_history SET visual_inspection = ... WHERE bobin_key_code = ...` không giới hạn công đoạn, vô tình ghi đè `visual_inspection` vào cả dòng lịch sử công đoạn Đùn (`Busy_Unchecked`).
2. **Hộp thoại Hủy Bobin tại trang Nhóm Cuộn chưa bắt buộc chọn nút lý do:**
   - Trong `public/assets/js/Winding/submit.js`, `ConfirmDialog.show()` luôn gọi `overlay.remove()` ngay khi bấm nút "Xác nhận", và `handleWindingCancel()` cho phép gửi request hủy ngay cả khi `selectedErrors.length === 0` (chưa chọn `"Ngoại quan"` hay `"Thông khí"`).

### 3. Giải pháp đã triển khai:
1. **Chuẩn hóa hiển thị & lưu trữ Điều chỉnh QC (`qcEditBobinView.php`, `qcEditBobin.css`, `BobinController.php`, `BobinServices.php`, `BobinRepository.php`):**
   - Cập nhật `qcEditBobinView.php` đọc chính xác `$vi['defects']` (`gel`, `foreign_object`, `color_issue`, `print_quality`, `note`) và `$vi['inspection_time']` (kèm fallback tương thích ngược với các khóa phẳng `defect_*`, `inspected_at`) cho cả 4 thẻ KPI và danh sách thẻ Bobin.
   - Bổ sung hiển thị đầy đủ thông số trên thẻ Điều chỉnh QC: `Kích thước`, `Lot vật liệu`, `Ca sản xuất`, `NV Đùn`, dải 5 tiêu chí `Đùn tự kiểm tra` tham khảo, định dạng hiển thị `bobin_key_code`, và tích hợp gợi ý nhân viên QC (`QC/suggestion.js`).
   - Cập nhật `BobinController::updateQCEditBobin()` lưu `visual_inspection` đúng chuẩn `VisualInspectionEntity` (`defects: {...}`, `inspector_code`, `inspector_name`, `inspection_time`), tự động đồng bộ tiền tố `"Phán định chuyển Bobin sang: ..."` khi đổi loại Bobin, và xác thực nhân viên QC qua `EmployeeRepository` trong `BobinServices::adminUpdateQCBobin()`.
   - Sửa `BobinRepository::adminUpdateQCBobin()` và `adminUpdateWindingBobin()` chỉ cập nhật `update_history` trên đúng dòng lịch sử của công đoạn tương ứng (`WHERE id = :hist_id`), bảo toàn nguyên vẹn dòng lịch sử `Busy_Unchecked`.
   - Sửa lỗi đảo ngược nhãn OK/NG khi xuất Excel cho 4 tiêu chí ngoại quan trong `BobinController` (`prepareHistoryRowData`, `prepareRowData`, `prepareDetailRowData`).
2. **Bắt buộc chọn "Thông khí" hoặc "Ngoại quan" khi hủy tại Nhóm Cuộn (`Winding/submit.js`, `BobinWindingCancelDTO.php`, `BobinController.php`, `BobinServices.php`):**
   - Nâng cấp `ConfirmDialog.show()` trong `Winding/submit.js`: nếu `await onConfirm(box)` trả về `false`, giữ nguyên hộp thoại và bật lại nút `.confirm-btn`.
   - Trong `handleWindingCancel()`, nếu người dùng chưa chọn `"Ngoại quan"` hoặc `"Thông khí"` (`selectedErrors.length === 0`), hiển thị Toast lỗi đa ngôn ngữ (`toast_err_winding_cancel_reason`) cùng cảnh báo trực quan ngay trong hộp thoại (`#winding-cancel-reason-error`) và chặn gửi request.
   - Bổ sung kiểm tra bắt buộc phía Backend trong `BobinController::validFormWinding_Cancel()` và `BobinServices::windingCancelBobin()`.

---

## [2026-10-11] - Bổ Sung Trường `previous_status` & Chức Năng Khôi Phục Bobin (Restore) Tại Danh Sách Chờ Hủy (TASK-032)

### 1. Bối cảnh & Yêu cầu:
- Bổ sung trường dữ liệu `previous_status` cho mỗi Bobin để lưu lại trạng thái liền trước trạng thái hiện tại (`bobin_current_status`).
- Tại trang **Danh sách chờ hủy** (`app/views/listPendingCancellationView.php`), bổ sung nút **"Khôi phục Bobin" (Restore)** để hoàn lại trạng thái trước đó theo quy tắc:
  1. Nếu đưa vào chờ hủy bởi **Nhóm Đùn** $\rightarrow$ Restore về **Chưa kiểm tra QC** (`Busy_Unchecked`).
  2. Nếu đưa vào chờ hủy bởi **Nhóm QC** $\rightarrow$ Restore về **Chưa kiểm tra QC** (`Busy_Unchecked`).
  3. Nếu đưa vào chờ hủy bởi **Nhóm Cuộn**, mà trước đó là **Đã QC** $\rightarrow$ Restore về **Đã QC** (`Busy_Checked`).
  4. Nếu đưa vào chờ hủy bởi **Nhóm Cuộn**, mà trước đó là **Đã Cuộn** $\rightarrow$ Restore về **Đã Cuộn** (`Rolled`).
- Phân tích và đề xuất hướng giải quyết cho nghiệp vụ **"Bobin chạy dở dang"** tại nhóm Cuộn.

### 2. Giải pháp đã triển khai:
1. **Cơ sở dữ liệu & Schema (`production_db`, `production_db._Structure.sql`, `DATABASE_SCHEMA.md`):**
   - Thêm cột `previous_status enum('Rolled','Busy_Unchecked','Busy_Checked','Pending_Cancellation','Cancelled') DEFAULT NULL` ngay sau `bobin_current_status` trên cả 3 bảng: `bobin_list_detail`, `bobin_list_general`, và `bobin_history`.
   - Backfill dữ liệu `previous_status` cho toàn bộ bản ghi hiện có trên CSDL.
2. **Backend (`BobinEntity.php`, `BobinRepository.php`, `BobinServices.php`, `BobinController.php`, `AuthHelper.php`, `Router.php`):**
   - Cập nhật toàn bộ các hàm chuyển trạng thái (`createNewBobin`, `extrusionDeleteBobin`, `updateQCBobinInfor`, `updateBobinTypeInfor`, `qcCancelBobin`, `updateWindingBobinInfor`, `windingCancelBobin`, `deleteBobin`) để tự động lưu vết `previous_status` đồng bộ trên cả 3 bảng.
   - Xây dựng hàm `BobinRepository::restoreBobin()`, `BobinServices::restoreBobin()` và API `PUT ?url=bobin/restoreBobin` (`BobinController::restoreBobin()`): xác định chính xác trạng thái khôi phục đích, làm sạch hậu tố/tiền tố báo hủy trong ghi chú, ghi vết kiểm toán `update_history` (`action: 'restore'`), cập nhật `bobin_list_detail`, `bobin_list_general` và ghi bản ghi lịch sử mới vào `bobin_history`.
3. **Giao diện & Đa ngôn ngữ (`listPendingCancellationView.php`, `listPendingCancellation.css`, `delete.js`, `Language.php`, `i18n.js`):**
   - Hiển thị huy hiệu trạng thái khôi phục đích (`Khôi phục về: CHƯA KIỂM TRA QC / ĐÃ KIỂM TRA QC / ĐÃ CUỘN`) trên thanh tiêu đề của mỗi thẻ Bobin chờ hủy.
   - Bổ sung nút `🔄 Khôi phục Bobin` (`.btn-restore-bobin`) và hộp thoại xác nhận `handleRestore()` trong `delete.js`, hỗ trợ đầy đủ 3 ngôn ngữ (`vi`, `en`, `ja`).

---

## [2026-10-10] - Bổ Sung Quét Mã QR Cho Vị Trí Rack & Ngăn Bàn Phím Ảo Bật Lên Sau Khi Quét (TASK-031)

### 1. Bối cảnh & Yêu cầu:
- Tại trang Nhóm Đùn (`app/views/extrusionView.php`), bổ sung chức năng quét mã QR cho trường **Vị trí Rack** (`#rack_code`).
- Ví dụ: Khi quét mã QR có nội dung `"Rack_B21_01"`, ô nhập liệu tại "Vị trí Rack" sẽ hiển thị ngay giá trị vừa giải mã.
- Đồng thời, sau khi quét xong mã QR, bàn phím ảo trên thiết bị di động/tablet **không** tự động hiện lên nữa.

### 2. Nguyên nhân bàn phím ảo hiện lên sau khi quét:
- Trong các file `scanQR.js` trước đây, sau khi giải mã thành công (`onSuccess`), hàm xử lý gọi `inputElement.focus()`. Trên các trình duyệt di động (Android/iOS), việc gọi `.focus()` vào thẻ `<input type="text">` sẽ kích hoạt bàn phím ảo bật lên che khuất màn hình.

### 3. Giải pháp đã triển khai:
1. **Giao diện Quét QR Vị trí Rack (`app/views/extrusionView.php`, `scanQR.css`, `extrusion.css`):**
   - Bổ sung khung hiển thị camera `<div id="qr-reader-rack"></div>` ngay đầu Khối 3 ("THÔNG SỐ CUỘN & VỊ TRÍ RACK").
   - Bọc `#rack_code` và `#rack_suggestions` vào `.qr-input-group` cùng nút `<button type="button" id="btnScanRackQR" class="btn-modern btn-scan">`.
   - Đồng bộ toàn bộ quy tắc hiển thị, ẩn canvas phụ và giao diện responsive cho `#qr-reader-rack` và `#btnScanRackQR.is-scanning`.
2. **Nâng cấp `QRScannerHelper` (`public/assets/js/qrScannerHelper.js`) & `Extrusion/scanQR.js`:**
   - Cập nhật `QRScannerHelper.toggle()` nhận diện `this.activeContainer === targetContainer` để cho phép chuyển đổi mượt mà giữa camera quét Bobin (`#qr-reader`) và camera quét Rack (`#qr-reader-rack`).
   - Hỗ trợ truyền `options.title` và `options.searchingText` riêng cho khung quét Rack (`qr_scanner_rack_title`, `qr_scanner_rack_searching` trên cả 3 ngôn ngữ `vi`, `en`, `ja`).
   - Thay thế toàn bộ lệnh `.focus()` sau khi quét bằng `.blur()` và `document.activeElement.blur()` trong `qrScannerHelper.js`, `Extrusion/scanQR.js`, `Extrusion/edit_scanQR.js`, `QC/scanQR.js`, `Winding/scanQR.js`, `Manage/scanQR.js`, đồng thời đóng gọn hộp gợi ý `#rack_suggestions` / `#bobin_suggestions`.
3. **Tương thích mã Rack trong Backend (`app/services/BobinServices.php`):**
   - Cập nhật `BobinServices::resolveRack(string $rackCode)` hỗ trợ đối chiếu chuẩn hóa số `0` ở đầu (ví dụ mã QR `"Rack_B21_01"` khớp hợp lệ với `"Rack_B021_01"` trong bảng `rack_list`), đảm bảo lưu biểu mẫu thành công với đúng chuỗi mã QR đã quét.

---

## [2026-10-10] - Khắc Phục Lỗi Unknown Column 'update_history' Khi Điều Chỉnh Bobin (TASK-030)

### 1. Bối cảnh & Yêu cầu:
- Người dùng báo lỗi: `Lỗi Database: SQLSTATE[42S22]: Column not found: 1054 Unknown column 'update_history' in 'field list'` khi bấm xác nhận điều chỉnh thông tin Bobin từ trang Điều chỉnh Đùn (`extrusionEditBobinView.php`).

### 2. Nguyên nhân gốc rễ (Root Cause Analysis):
1. **Thiếu cột `update_history` trên CSDL và tệp SQL:**
   - Theo thiết kế tại `AI_preference/DATABASE_SCHEMA.md` (dòng 51) và yêu cầu từ TASK-007/TASK-008, mỗi lần thông tin Bobin được điều chỉnh từ 3 trang: **Điều chỉnh Đùn**, **Điều chỉnh QC**, **Điều chỉnh Cuộn**, hệ thống sẽ ghi nhận vết kiểm toán (người sửa, thời gian sửa, công đoạn, ghi chú) dưới dạng mảng JSON vào cột `update_history` của bảng `bobin_list_detail` và `bobin_history`, đồng thời hiển thị tại trang Lịch sử Bobin (`listBobinHistoryView.php`).
   - Tuy nhiên, khi xuất/nhập lại CSDL từ file SQL (`production_db._Structure.sql` và `production_db_Data.sql`), hai bảng `bobin_list_detail` và `bobin_history` chưa có định nghĩa cột `update_history`, dẫn đến lỗi `1054 Unknown column 'update_history'` khi thực thi câu lệnh `SELECT`/`UPDATE` tại `BobinRepository::extUpdateBobinDetail()`, `extUpdateBobinHistory()`, `adminUpdateQCBobin()`, và `adminUpdateWindingBobin()`.
2. **Bảo toàn dữ liệu `update_history`, `extrusion_check`, `rack` qua các bước chuyển trạng thái:**
   - Trong `app/repositories/BobinRepository.php`, các câu lệnh tạo bản ghi snapshot lịch sử mới (`INSERT INTO bobin_history (...) SELECT ... FROM bobin_list_detail`) khi kiểm tra QC, xác nhận Cuộn hoặc Hủy Bobin trước đó chưa đưa cột `update_history` (và `extrusion_check`, `rack`) vào danh sách `SELECT`, khiến bản ghi lịch sử mới nhất (`MAX(id)`) có nguy cơ bị mất vết điều chỉnh đã lưu ở công đoạn trước.
   - Đồng thời, tại `insertBobinDetailRecord()`, mệnh đề `ON DUPLICATE KEY UPDATE` khi khởi tạo chu kỳ mới cho Bobin cần đặt lại `update_history = NULL` để tách biệt vết điều chỉnh giữa các `bobin_key_code` khác nhau.

### 3. Giải pháp đã triển khai:
1. **Cập nhật trực tiếp trên CSDL `production_db` (Live MySQL):**
   - Thực thi lệnh `ALTER TABLE` bổ sung cột `update_history longtext DEFAULT NULL COMMENT 'Mảng JSON lưu vết lịch sử điều chỉnh (Đùn, QC, Cuộn)'` vào cả hai bảng `bobin_list_detail` và `bobin_history`.
2. **Đồng bộ hóa cả 2 tệp SQL chuẩn:**
   - Cập nhật `public/assets/sql/production_db._Structure.sql` và `public/assets/sql/production_db_Data.sql`: bổ sung cột `update_history` vào cấu trúc bảng `bobin_history` và `bobin_list_detail`.
3. **Hoàn thiện `app/repositories/BobinRepository.php`:**
   - Đặt `update_history = NULL` trong `ON DUPLICATE KEY UPDATE` của `insertBobinDetailRecord()` khi Bobin bước sang chu kỳ sản xuất mới.
   - Bổ sung đầy đủ `extrusion_check, rack, update_history` vào toàn bộ 7 câu truy vấn `INSERT INTO bobin_history (...) SELECT ... FROM bobin_list_detail` (`deleteBobin`, `extInsertBobinHistoryAfterDelete`, `updateQCBobinInfor`, `updateBobinTypeInfor`, `qcCancelBobin`, `updateWindingBobinInfor`, `windingCancelBobin`).

---

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
