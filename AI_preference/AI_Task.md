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
+ Danh sách Bobin được hiển thị ở trang Điều chỉnh QC phải là những Bobin có status là "Busy_Checked"
+ Danh sách Bobin được hiển thị ở trang điều chỉnh Cuộn phải là những Bobin có status là "Rolled", tuy nhiên chỉ hiển thị những Bobin có updated time trong 7 ngày gần nhất 
Cả 2 trang trên đều phải có thêm tính năng phân trang để tránh quá tải hiển thị và quá tải dữ liệu khi đặt vào cùng 1 trang.

### TASK-006
**Status:** DONE
+ Danh sách Bobin được hiển thị ở trang điều chỉnh Cuộn phải là những Bobin có status là "Rolled", tuy nhiên chỉ hiển thị những Bobin có updated time trong 3 ngày gần nhất 
+ Danh sách Bobin được hiển thị ở trang Xác nhận thông tin cuộn phải là những Bobin có status là "Busy_Checked" và "Rolled", tuy nhiên đối với Bobin status là "Rolled" thì chỉ hiển thị những Bobin có updated time trong 3 ngày gần nhất
+ Kiểm tra là chức năng translation, hiện tại tôi thấy khi đang chế độ tiếng Việt nhưng có rất nhiều từ đang để là tiếng Anh
+ Các hiển thị trên từng card Bobin đều giữ nguyên là: "CHƯA KIỂM TRA QC", "ĐÃ KIỂM TRA QC", "ĐANG CHỜ HỦY", "ĐÃ HỦY", "ĐÃ CUỘN"

### TASK-007
**Status:** DONE
+ Nâng cao UI/UX ở các trang điều chỉnh để nổi bật và chuyên nghiệp hơn
+ Ở trang lịch sử, bỏ đi các nút nhấn "Hôm nay", "Hôm qua", "7 ngày", "1 tháng"
+ Chỉ có admin mới có thể truy cập vào trang điều chỉnh Cuộn
+ Nhân viên QC có thể truy cập vào trang điều chỉnh QC
+ Nhân viên Đùn có thể truy cập vào trang điều chỉnh Đùn
+ Tôi thấy bạn có thêm trường dữ liệu update_history. Lưu ý rằng, mục đích trường này được tạo ra là: Mỗi lần giá trị các trường dữ liệu Bobin (độc nhất theo bobin_key_code) thay đổi xuất phát từ các trang: Điều chỉnh đùn, điều chỉnh QC, điều chỉnh cuộn thì sẽ hiển thị người thay đổi dữ liệu cũng như thời gian thay đổi.
### TASK-008
**Status:** DONE
- Nâng cao UI cho trang Điều chỉnh đùn, điều chỉnh cuộn, điều chỉnh QC cho đẹp lên, nổi bật lên, chuyên nghiệp lên
- trường dữ liệu update_history chỉ cần hiển thị ra ở trang lịch sử bobin, các trang khác không cần hiển thị
## 🟡 RULES 
Toàn bộ dưới đây là DEVELOPMENT RULES phải tuân thủ:

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



