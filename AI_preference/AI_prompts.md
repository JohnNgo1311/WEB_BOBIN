# prompt main dành cho Antigravity Panel

Hãy đọc toàn bộ context của project trong thư mục AI_preference/:

- AI_preference/AI_CONTEXT.md
- AI_preference/DATABASE_SCHEMA.md
- AI_preference/AI_Task.md
- AI_preference/CHANGELOG.md

Sau đó xác định task TODO hiện tại và thực hiện task đó.

Trước khi sửa code:
1. Đọc các file code liên quan.
2. Xác định chức năng đó có hay chưa.
3. Phân tích nguyên nhân của lỗi hiện tại (nếu có).
4. Kiểm tra các dependency có thể bị ảnh hưởng.

Trong quá trình thực hiện:
- Không tự ý thay đổi database schema nếu không cần thiết.
- Không tự ý thay đổi API structure nếu không cần thiết.
- Không sửa các chức năng ngoài phạm vi task nếu không cần thiết.

Sau khi hoàn thành:
1. Test toàn diện các chức năng.
2. Cập nhật AI_preference/AI_Task.md.
3. Cập nhật AI_preference/CHANGELOG.md.
4. Báo cáo các file đã thay đổi và nội dung thay đổi.