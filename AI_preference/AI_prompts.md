# prompt main dành cho Antigravity Panel

Hãy đọc toàn bộ context của project trong thư mục AI_preference/:

- AI_preference/AI_CONTEXT.md
- AI_preference/DATABASE_SCHEMA.md
- AI_preference/AI_Task.md
- AI_preference/CHANGELOG.md

Sau đó xác định task TODO hiện tại và thực hiện task đó.
Nếu không có task TODO hiện tại thì dựa trên đoạn chat mới nhất cùng ngày để xác định TASK.

Trước khi sửa code:

1. Đọc các file code liên quan (Nếu có thay đổi so với phiên bản mới nhất)
2. Xác định chức năng đó có hay chưa.
3. Phân tích nguyên nhân của lỗi hiện tại (nếu có).
4. Kiểm tra các dependency có thể bị ảnh hưởng.
5. Phải luôn tối ưu UI/UX, chỉ dùng những bảng màu cố định và icon cố định tránh làm rối mắt giao diện.
6. Không được ảnh hưởng các chức năng nằm ngoài yêu cầu

Trong quá trình thực hiện:

- Không tự ý thay đổi database schema nếu không cần thiết/khôn yêu cầu, nếu cần thiết để phát triển dự án thì có quyền thực thi.
- Không tự ý thay đổi API structure nếu không cần thiết/Không yêu cầu, nếu cần thiết để phát triển dự án thì có quyền thực thi..
- Không sửa các chức năng ngoài phạm vi task nếu không cần thiết/Không yêu cầu, nếu cần thiết để phát triển dự án thì có quyền thực thi..

Sau khi hoàn thành:

1. Test toàn diện các chức năng.
2. Cập nhật AI_preference/AI_Task.md. (Tóm tắt)
3. Cập nhật AI_preference/CHANGELOG.md. (Tóm tắt)
4. Báo cáo các file đã thay đổi và nội dung thay đổi.
