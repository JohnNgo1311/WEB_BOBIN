<?php

class Language
{
    public const SUPPORTED = [
        'vi' => [
            'code'  => 'vi',
            'name'  => 'Tiếng Việt',
            'flag'  => '🇻🇳',
            'short' => 'VI'
        ],
        'en' => [
            'code'  => 'en',
            'name'  => 'English',
            'flag'  => '🇬🇧',
            'short' => 'EN'
        ],
        'ja' => [
            'code'  => 'ja',
            'name'  => '日本語',
            'flag'  => '🇯🇵',
            'short' => 'JA'
        ]
    ];

    private static ?string $currentLang = null;
    private static ?array $dictionary = null;

    public static function init(): string
    {
        if (self::$currentLang !== null) {
            return self::$currentLang;
        }

        if (!empty($_GET['lang']) && array_key_exists(strtolower($_GET['lang']), self::SUPPORTED)) {
            $lang = strtolower($_GET['lang']);
            self::set($lang);
            return $lang;
        }

        if (session_status() === PHP_SESSION_ACTIVE && !empty($_SESSION['lang']) && array_key_exists($_SESSION['lang'], self::SUPPORTED)) {
            self::$currentLang = $_SESSION['lang'];
            return self::$currentLang;
        }

        if (!empty($_COOKIE['app_lang']) && array_key_exists(strtolower($_COOKIE['app_lang']), self::SUPPORTED)) {
            $lang = strtolower($_COOKIE['app_lang']);
            if (session_status() === PHP_SESSION_ACTIVE) {
                $_SESSION['lang'] = $lang;
            }
            self::$currentLang = $lang;
            return $lang;
        }

        self::$currentLang = 'vi';
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION['lang'] = 'vi';
        }
        return self::$currentLang;
    }

    public static function set(string $lang): void
    {
        $lang = strtolower(trim($lang));
        if (!array_key_exists($lang, self::SUPPORTED)) {
            $lang = 'vi';
        }

        self::$currentLang = $lang;

        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION['lang'] = $lang;
        }

        if (!headers_sent()) {
            setcookie('app_lang', $lang, [
                'expires'  => time() + 31536000,
                'path'     => '/',
                'httponly' => false,
                'samesite' => 'Lax'
            ]);
        }
    }

    public static function getCurrent(): string
    {
        return self::$currentLang ?? self::init();
    }

    public static function getCurrentInfo(): array
    {
        $code = self::getCurrent();
        return self::SUPPORTED[$code] ?? self::SUPPORTED['vi'];
    }

    public static function get(string $key, ?string $default = null): string
    {
        if (self::$dictionary === null) {
            self::loadDictionary();
        }

        $lang = self::getCurrent();
        if (isset(self::$dictionary[$lang][$key])) {
            return self::$dictionary[$lang][$key];
        }

        if (isset(self::$dictionary['vi'][$key])) {
            return self::$dictionary['vi'][$key];
        }

        return $default ?? $key;
    }

    public static function getJsonDictionary(): string
    {
        if (self::$dictionary === null) {
            self::loadDictionary();
        }
        return json_encode(self::$dictionary, JSON_UNESCAPED_UNICODE);
    }

    private static function loadDictionary(): void
    {
        self::$dictionary = [
            // ==========================================
            // 🇻🇳 TIẾNG VIỆT (VIETNAMESE)
            // ==========================================
            'vi' => [
                // Thanh điều hướng
                'nav_extrusion'        => 'Nhóm đùn',
                'nav_extrusion_edit'   => 'Điều chỉnh đùn',
                'nav_qc'               => 'QC',
                'nav_winding'          => 'Cuộn',
                'nav_bobin_list'       => 'Danh sách Bobin',
                'nav_bobin_history'    => 'Lịch sử Bobin',
                'nav_pending_cancel'   => 'Danh sách chờ hủy',
                'nav_employee_list'    => '👥 Danh sách nhân viên',
                'nav_change_pwd'       => '🔑 Đổi MK',
                'nav_logout'           => 'Đăng xuất',
                'nav_login'            => 'Đăng nhập',

                // Chung & Thao tác
                'stt'                  => 'STT',
                'ok'                   => 'OK',
                'ng'                   => 'NG',
                'pass'                 => 'Đạt',
                'fail'                 => 'Không đạt',
                'save'                 => 'Lưu',
                'cancel'               => 'Hủy',
                'edit'                 => 'Chỉnh sửa',
                'delete'               => 'Xóa',
                'confirm'              => 'Xác nhận',
                'back'                 => 'Quay lại',
                'search'               => 'Tìm kiếm',
                'filter'               => 'Bộ lọc',
                'reset_filter'         => 'Xóa lọc',
                'export_excel'         => 'Xuất file Excel',
                'import_excel'         => 'Nhập file Excel',
                'download_template'    => 'Tải file mẫu',
                'loading'              => 'Đang xử lý...',
                'status'               => 'Trạng thái',
                'actions'              => 'Thao tác',
                'success'              => 'Thành công',
                'error'                => 'Thất bại',
                'scan_qr'              => 'Quét mã QR',
                'start_camera'         => 'Bật camera quét QR',
                'stop_camera'          => 'Dừng camera',
                'close'                => 'Đóng',
                'all'                  => 'Tất cả',
                'from_date'            => 'Từ ngày',
                'to_date'              => 'Đến ngày',
                'keyword_search'       => 'Nhập thông tin cần tra cứu...',
                'view_detail'          => 'Chi tiết',
                'records'              => 'bản ghi',
                'total'                => 'Tổng số',

                // Trang đăng nhập
                'login_title'          => 'HỆ THỐNG QUẢN LÝ BOBIN',
                'login_subtitle'       => 'Nền tảng kiểm soát thông tin Bobin trong thời gian thực giữa các công đoạn Nhóm Đùn, Nhóm QC, Nhóm Cuộn.',
                'login_brand_badge'    => 'NHÀ XƯỞNG ĐÙN NHỰA',
                'login_quick_track'    => 'Theo dõi nhanh hệ thống',
                'login_free_access'    => 'Truy cập tự do',
                'login_username'       => 'Tên đăng nhập / Mã nhân viên',
                'login_username_ph'    => 'Nhập mã số nhân viên...',
                'login_password'       => 'Mật khẩu bảo mật',
                'login_password_ph'    => 'Nhập mật khẩu...',
                'login_btn_submit'     => 'Đăng nhập vào hệ thống',
                'login_forgot_pwd'     => 'Quên mật khẩu?',
                'login_toggle_pwd'     => 'Ẩn/Hiện mật khẩu',
                'login_stat_qr'        => '⚡ Quét QR tốc độ cao',
                'login_stat_chart'     => '📊 Báo cáo biểu đồ thời gian thực',
                'login_stat_shift'     => '🏭 Đồng bộ liên tục theo ca',
                'login_copyright'      => '© SMC Factory. Đã kích hoạt bảo vệ phiên làm việc.',

                // Portal Cards on Login
                'portal_detail_title'  => 'Danh sách Bobin hiện tại',
                'portal_detail_sub'    => 'Tra cứu số lượng, tình trạng và công đoạn của toàn bộ Bobin',
                'portal_history_title' => 'Lịch sử hoạt động Bobin',
                'portal_history_sub'   => 'Xem nhật ký luân chuyển, lọc theo ngày và xuất báo cáo Excel',
                'portal_pending_title' => 'Danh sách Bobin chờ hủy',
                'portal_pending_sub'   => 'Theo dõi các Bobin báo lỗi, chờ phê duyệt hủy từ phân xưởng',

                // Modal Quên mật khẩu Local
                'fp_modal_title'       => 'QUY TRÌNH CẤP LẠI MẬT KHẨU NỘI BỘ',
                'fp_modal_desc'        => 'Hệ thống vận hành trong mạng nội bộ nhà máy (Local Intranet). Để bảo đảm an toàn dữ liệu sản xuất:',
                'fp_step_1_title'      => 'Bước 1: Báo cho Quản lý ca / IT',
                'fp_step_1_desc'       => 'Liên hệ trực tiếp với Trưởng ca sản xuất (Supervisor) hoặc Bộ phận IT Nhà máy.',
                'fp_step_2_title'      => 'Bước 2: Cấp mật khẩu tạm',
                'fp_step_2_desc'       => 'Trưởng ca sẽ xác minh mã nhân viên và kích hoạt cấp lại mật khẩu mặc định (123).',
                'fp_step_3_title'      => 'Bước 3: Bắt buộc đổi mật khẩu mới',
                'fp_step_3_desc'       => 'Sau khi đăng nhập bằng mật khẩu tạm, hệ thống sẽ tự động yêu cầu bạn tạo mật khẩu mới ngay lập tức.',
                'fp_contact_info'      => 'Hotline hỗ trợ nội bộ xưởng:',
                'fp_contact_room'      => 'Phòng Điều Hành SX: Máy nhánh 102 | IT Support: Máy nhánh 108',
                'fp_btn_understood'    => 'Đã hiểu, quay lại đăng nhập',

                // Trang đổi mật khẩu
                'cp_title_first'       => 'THIẾT LẬP MẬT KHẨU MỚI',
                'cp_title_normal'      => 'ĐỔI MẬT KHẨU TÀI KHOẢN',
                'cp_sub_first'         => 'Vui lòng nhập mật khẩu hiện tại và tạo mật khẩu mới an toàn',
                'cp_sub_normal'        => 'Cập nhật mật khẩu định kỳ để bảo vệ tài khoản của bạn',
                'cp_alert_first'       => 'Yêu cầu bảo mật: Đổi mật khẩu lần đầu! Để đảm bảo an toàn thông tin cá nhân và tránh trùng lặp mật khẩu ban đầu của hệ thống, vui lòng đổi mật khẩu mới trước khi tiếp tục.',
                'cp_current_pwd'       => 'Mật khẩu hiện tại',
                'cp_current_pwd_ph'    => 'Nhập mật khẩu hiện tại...',
                'cp_new_pwd'           => 'Mật khẩu mới',
                'cp_new_pwd_ph'        => 'Tối thiểu 6 ký tự...',
                'cp_confirm_pwd'       => 'Xác nhận mật khẩu mới',
                'cp_confirm_pwd_ph'    => 'Nhập lại mật khẩu mới...',
                'cp_strength_label'    => 'Độ mạnh mật khẩu:',
                'cp_strength_weak'     => 'Yếu',
                'cp_strength_medium'   => 'Trung bình',
                'cp_strength_good'     => 'Tốt',
                'cp_strength_strong'   => 'Rất mạnh',
                'cp_btn_submit'        => 'Cập nhật mật khẩu mới',
                'cp_btn_back'          => 'Quay lại trang chủ',

                // Tiêu đề các trang
                'page_extrusion'       => 'Nhóm đùn - Nhập thông tin Bobin',
                'page_extrusion_edit'  => 'Nhóm đùn - Điều chỉnh thông tin Bobin',
                'page_qc'              => 'Nhóm QC - Kiểm tra chất lượng Bobin',
                'page_winding'         => 'Nhóm Cuộn - Xác nhận hoàn thành Bobin',
                'page_bobin_detail'    => 'Danh sách Bobin hiện tại',
                'page_bobin_history'   => 'Lịch sử Bobin',
                'page_pending_cancel'  => 'Danh sách Bobin chờ hủy',
                'page_employee_list'   => 'Quản lý danh sách nhân viên',

                // Thông số kỹ thuật Bobin
                'bobin_code'           => 'Mã Bobin',
                'po_code'              => 'Mã chỉ thị SX (PO)',
                'product_code'         => 'Mã sản phẩm',
                'length_m'             => 'Chiều dài (m)',
                'weight_kg'            => 'Khối lượng (kg)',
                'extrusion_machine'    => 'Máy đùn',
                'winding_machine'      => 'Máy cuộn',
                'material'             => 'Nguyên liệu',
                'material_lot'         => 'Lô nguyên liệu',
                'worker'               => 'Người thực hiện',
                'worker_extrusion'     => 'Người đùn',
                'worker_qc'            => 'Người QC',
                'worker_winding'       => 'Người cuộn',
                'shift'                => 'Ca làm việc',
                'shift_day'            => 'Ca ngày',
                'shift_night'          => 'Ca đêm',
                'print_lot'            => 'Lô in',
                'notes'                => 'Ghi chú',
                'appearance'           => 'Ngoại quan',
                'defect_type'          => 'Dạng khuyết tật',
                'defect_cause'         => 'Nguyên nhân khuyết tật',
                'created_time'         => 'Thời gian tạo',
                'updated_time'         => 'Thời gian cập nhật',
                'qc_time'              => 'Thời gian QC',
                'winding_time'         => 'Thời gian cuộn',
                'total_bobin'          => 'Tổng số Bobin',
                'btn_save_bobin'       => '💾 Lưu Bobin',
                'btn_update_bobin'     => 'Cập nhật Bobin',
                'btn_confirm_qc'       => 'Xác nhận kiểm tra QC',
                'btn_confirm_winding'  => 'Xác nhận hoàn thành cuộn',
                'btn_cancel_bobin'     => 'Báo hủy Bobin',
                'btn_restore_bobin'    => 'Khôi phục Bobin',

                // Trạng thái Bobin
                'status_ready'         => 'Đã cuộn (Hoàn thành)',
                'status_unchecked'     => 'Chưa QC (Chờ kiểm tra)',
                'status_checked'       => 'Đã QC (Chờ cuộn)',
                'status_pending_cancel' => 'Chờ hủy',
                'status_cancelled'     => 'Đã hủy',

                // ================= QUẢN LÝ NHÂN VIÊN =================
                'emp_manage_title'     => 'DANH SÁCH NHÂN VIÊN',
                'emp_manage_subtitle'  => 'Quản lý tài khoản, phân quyền vai trò và cấp lại mật khẩu cho nhân viên phân xưởng.',
                'emp_total_stat'       => 'Tổng nhân viên',
                'emp_ext_stat'         => 'Nhóm Đùn',
                'emp_qc_stat'          => 'Nhóm QC',
                'emp_wind_stat'        => 'Nhóm Cuộn',
                'emp_admin_stat'       => 'Quản trị viên',
                'emp_code'             => 'Mã nhân viên',
                'emp_name'             => 'Họ và tên',
                'emp_username'         => 'Tên đăng nhập',
                'emp_role'             => 'Vai trò / Nhóm',
                'emp_status'           => 'Tình trạng làm việc',
                'emp_password'         => 'Mật khẩu',
                'emp_first_login'      => 'Đổi MK lần đầu',
                'emp_btn_add'          => '➕ Thêm nhân viên mới',
                'emp_btn_import'       => '📥 Nhập Excel',
                'emp_btn_export'       => '📤 Xuất Excel',
                'emp_btn_template'     => '📋 Tải file mẫu',
                'emp_btn_edit'         => 'Sửa',
                'emp_btn_delete'       => 'Xóa',
                'emp_btn_reset_pwd'    => '🔑 Cấp lại mật khẩu',
                'emp_role_extrusion'   => 'Nhóm Đùn',
                'emp_role_qc'          => 'Nhóm QC',
                'emp_role_winding'     => 'Nhóm Cuộn',
                'emp_role_admin'       => 'Quản trị viên (Admin)',
                'emp_status_active'    => 'Đang làm việc',
                'emp_status_inactive'  => 'Đã nghỉ việc / Khóa',
                'emp_first_login_need' => 'Chưa đổi (Bắt buộc đổi)',
                'emp_first_login_done' => 'Đã đổi MK riêng',
                'emp_pwd_default'      => 'Mặc định (123)',
                'emp_pwd_encrypted'    => 'Đã mã hóa',
                'emp_modal_add_title'  => 'Thêm nhân viên mới',
                'emp_modal_edit_title' => 'Chỉnh sửa thông tin nhân viên',
                'emp_modal_reset_title' => 'Cấp lại mật khẩu nhân viên',
                'emp_modal_import_title' => 'Nhập danh sách nhân viên từ Excel/CSV',
                'emp_import_hint'      => 'Chọn file định dạng .csv hoặc .xlsx theo cấu trúc file mẫu để nhập hàng loạt.',
                'emp_confirm_delete'   => 'Bạn có chắc chắn muốn xóa nhân viên này khỏi hệ thống không?',
                'emp_msg_added'        => 'Thêm nhân viên mới thành công!',
                'emp_msg_updated'      => 'Cập nhật thông tin nhân viên thành công!',
                'emp_msg_deleted'      => 'Đã xóa nhân viên thành công!',
                'emp_msg_reset_done'   => 'Đã cấp lại mật khẩu về mặc định (123) thành công!',
                'emp_msg_imported'     => 'Nhập file Excel hoàn tất thành công!'
            ],

            // ==========================================
            // 🇬🇧 ENGLISH
            // ==========================================
            'en' => [
                // Navigation
                'nav_extrusion'        => 'Extrusion',
                'nav_extrusion_edit'   => 'Extrusion Edit',
                'nav_qc'               => 'QC Check',
                'nav_winding'          => 'Winding',
                'nav_bobin_list'       => 'Bobin List',
                'nav_bobin_history'    => 'Bobin History',
                'nav_pending_cancel'   => 'Pending Scraps',
                'nav_employee_list'    => '👥 Employee List',
                'nav_change_pwd'       => '🔑 Password',
                'nav_logout'           => 'Logout',
                'nav_login'            => 'Login',

                // General
                'stt'                  => 'No.',
                'ok'                   => 'OK',
                'ng'                   => 'NG',
                'pass'                 => 'Pass',
                'fail'                 => 'Fail',
                'save'                 => 'Save',
                'cancel'               => 'Cancel',
                'edit'                 => 'Edit',
                'delete'               => 'Delete',
                'confirm'              => 'Confirm',
                'back'                 => 'Back',
                'search'               => 'Search',
                'filter'               => 'Filter',
                'reset_filter'         => 'Reset Filter',
                'export_excel'         => 'Export Excel',
                'import_excel'         => 'Import Excel',
                'download_template'    => 'Download Template',
                'loading'              => 'Processing...',
                'status'               => 'Status',
                'actions'              => 'Actions',
                'success'              => 'Success',
                'error'                => 'Failed',
                'scan_qr'              => 'Scan QR',
                'start_camera'         => 'Start QR Scanner',
                'stop_camera'          => 'Stop Scanner',
                'close'                => 'Close',
                'all'                  => 'All',
                'from_date'            => 'From Date',
                'to_date'              => 'To Date',
                'keyword_search'       => 'Enter keywords to search...',
                'view_detail'          => 'Details',
                'records'              => 'records',
                'total'                => 'Total',

                // Login Page
                'login_title'          => 'BOBIN MANAGEMENT SYSTEM',
                'login_subtitle'       => 'Real-time Bobin control platform across Extrusion, QC, and Winding operations.',
                'login_brand_badge'    => 'PLASTIC EXTRUSION BUILDING',
                'login_quick_track'    => 'Quick System Tracking',
                'login_free_access'    => 'Open Access',
                'login_username'       => 'Username / Employee ID',
                'login_username_ph'    => 'Enter employee ID...',
                'login_password'       => 'Security Password',
                'login_password_ph'    => 'Enter password...',
                'login_btn_submit'     => 'Sign In to System',
                'login_forgot_pwd'     => 'Forgot Password?',
                'login_toggle_pwd'     => 'Show/Hide Password',
                'login_stat_qr'        => '⚡ High-speed QR Scanning',
                'login_stat_chart'     => '📊 Real-time Chart Reports',
                'login_stat_shift'     => '🏭 Continuous Shift Synchronization',
                'login_copyright'      => '© SMC Factory. Session protection activated.',

                // Portal Cards on Login
                'portal_detail_title'  => 'Current Bobin Inventory',
                'portal_detail_sub'    => 'Check quantities, statuses, and stages of all active Bobins',
                'portal_history_title' => 'Bobin Activity History',
                'portal_history_sub'   => 'Review transition logs, filter by date, and export Excel reports',
                'portal_pending_title' => 'Pending Scrap Bobin List',
                'portal_pending_sub'   => 'Track rejected Bobins awaiting scrapping approval from workshop',

                // Forgot Password Modal
                'fp_modal_title'       => 'INTERNAL PASSWORD RESET PROCESS',
                'fp_modal_desc'        => 'This system operates strictly on the factory Local Intranet. To ensure production security:',
                'fp_step_1_title'      => 'Step 1: Contact Shift Supervisor / IT',
                'fp_step_1_desc'       => 'Contact your Shift Supervisor or Factory IT Department directly.',
                'fp_step_2_title'      => 'Step 2: Temporary Password Issued',
                'fp_step_2_desc'       => 'Supervisor verifies your ID and resets account to default temporary password (123).',
                'fp_step_3_title'      => 'Step 3: Compulsory Password Change',
                'fp_step_3_desc'       => 'Upon sign-in with temporary password, system immediately prompts you to create a secure personal password.',
                'fp_contact_info'      => 'Internal Factory Contact:',
                'fp_contact_room'      => 'Production Office: Ext 102 | IT Support: Ext 108',
                'fp_btn_understood'    => 'Understood, return to Login',

                // Change Password Page
                'cp_title_first'       => 'SET NEW PASSWORD',
                'cp_title_normal'      => 'CHANGE ACCOUNT PASSWORD',
                'cp_sub_first'         => 'Please enter current password and create a secure new password',
                'cp_sub_normal'        => 'Periodically update your password to protect your account',
                'cp_alert_first'       => 'Security Notice: First login detected! To protect your personal account and avoid shared default credentials, please create a new password before continuing.',
                'cp_current_pwd'       => 'Current Password',
                'cp_current_pwd_ph'    => 'Enter current password...',
                'cp_new_pwd'           => 'New Password',
                'cp_new_pwd_ph'        => 'At least 6 characters...',
                'cp_confirm_pwd'       => 'Confirm New Password',
                'cp_confirm_pwd_ph'    => 'Re-enter new password...',
                'cp_strength_label'    => 'Password Strength:',
                'cp_strength_weak'     => 'Weak',
                'cp_strength_medium'   => 'Medium',
                'cp_strength_good'     => 'Good',
                'cp_strength_strong'   => 'Very Strong',
                'cp_btn_submit'        => 'Update Password',
                'cp_btn_back'          => 'Return to Home',

                // Page Titles
                'page_extrusion'       => 'Extrusion - Input Bobin Data',
                'page_extrusion_edit'  => 'Extrusion - Edit Bobin Data',
                'page_qc'              => 'QC - Quality Inspection',
                'page_winding'         => 'Winding - Finalize Bobin',
                'page_bobin_detail'    => 'Current Bobin Inventory',
                'page_bobin_history'   => 'Bobin Activity History',
                'page_pending_cancel'  => 'Pending Scrap Bobin List',
                'page_employee_list'   => 'Employee Management List',

                // Bobin Specifications
                'bobin_code'           => 'Bobin Code',
                'po_code'              => 'Production Order (PO)',
                'product_code'         => 'Product Code',
                'length_m'             => 'Length (m)',
                'weight_kg'            => 'Weight (kg)',
                'extrusion_machine'    => 'Extruder Machine',
                'winding_machine'      => 'Winder Machine',
                'material'             => 'Material',
                'material_lot'         => 'Material Lot',
                'worker'               => 'Operator',
                'worker_extrusion'     => 'Extrusion Operator',
                'worker_qc'            => 'QC Inspector',
                'worker_winding'       => 'Winding Operator',
                'shift'                => 'Shift',
                'shift_day'            => 'Day Shift',
                'shift_night'          => 'Night Shift',
                'print_lot'            => 'Print Lot',
                'notes'                => 'Remarks',
                'appearance'           => 'Appearance',
                'defect_type'          => 'Defect Type',
                'defect_cause'         => 'Defect Cause',
                'created_time'         => 'Created Time',
                'updated_time'         => 'Updated Time',
                'qc_time'              => 'QC Time',
                'winding_time'         => 'Winding Time',
                'total_bobin'          => 'Total Bobins',
                'btn_save_bobin'       => '💾 Save Bobin',
                'btn_update_bobin'     => 'Update Bobin',
                'btn_confirm_qc'       => 'Confirm QC Inspection',
                'btn_confirm_winding'  => 'Confirm Winding Completion',
                'btn_cancel_bobin'     => 'Report Bobin Scrap',
                'btn_restore_bobin'    => 'Restore Bobin',

                // Status
                'status_ready'         => 'Ready (Finished)',
                'status_unchecked'     => 'Unchecked (Pending QC)',
                'status_checked'       => 'Checked (Pending Winding)',
                'status_pending_cancel' => 'Pending Scrap',
                'status_cancelled'     => 'Scrapped',

                // Employee Management
                'emp_manage_title'     => 'EMPLOYEE LIST',
                'emp_manage_subtitle'  => 'Manage accounts, roles, access permissions and reset passwords for factory staff.',
                'emp_total_stat'       => 'Total Staff',
                'emp_ext_stat'         => 'Extrusion',
                'emp_qc_stat'          => 'QC Team',
                'emp_wind_stat'        => 'Winding Team',
                'emp_admin_stat'       => 'Administrators',
                'emp_code'             => 'Employee ID',
                'emp_name'             => 'Full Name',
                'emp_username'         => 'Username',
                'emp_role'             => 'Role / Team',
                'emp_status'           => 'Work Status',
                'emp_password'         => 'Password',
                'emp_first_login'      => 'First Login PW Change',
                'emp_btn_add'          => '➕ Add New Employee',
                'emp_btn_import'       => '📥 Import Excel',
                'emp_btn_export'       => '📤 Export Excel',
                'emp_btn_template'     => '📋 Sample Template',
                'emp_btn_edit'         => 'Edit',
                'emp_btn_delete'       => 'Delete',
                'emp_btn_reset_pwd'    => '🔑 Reset Password',
                'emp_role_extrusion'   => 'Extrusion Group',
                'emp_role_qc'          => 'QC Inspection',
                'emp_role_winding'     => 'Winding Group',
                'emp_role_admin'       => 'Administrator',
                'emp_status_active'    => 'Active',
                'emp_status_inactive'  => 'Inactive / Locked',
                'emp_first_login_need' => 'Pending (Mandatory)',
                'emp_first_login_done' => 'Changed',
                'emp_pwd_default'      => 'Default (123)',
                'emp_pwd_encrypted'    => 'Encrypted Hash',
                'emp_modal_add_title'  => 'Add New Employee',
                'emp_modal_edit_title' => 'Edit Employee Details',
                'emp_modal_reset_title' => 'Reset Employee Password',
                'emp_modal_import_title' => 'Import Employees from Excel/CSV',
                'emp_import_hint'      => 'Select a .csv or .xlsx file structured like the sample template.',
                'emp_confirm_delete'   => 'Are you sure you want to delete this employee from the system?',
                'emp_msg_added'        => 'New employee added successfully!',
                'emp_msg_updated'      => 'Employee details updated successfully!',
                'emp_msg_deleted'      => 'Employee deleted successfully!',
                'emp_msg_reset_done'   => 'Password has been reset to default (123) successfully!',
                'emp_msg_imported'     => 'Excel import completed successfully!'
            ],

            // ==========================================
            // 🇯🇵 日本語 (JAPANESE)
            // ==========================================
            'ja' => [
                // Navigation
                'nav_extrusion'        => '押出',
                'nav_extrusion_edit'   => '押出編集',
                'nav_qc'               => 'QC検査',
                'nav_winding'          => '巻取',
                'nav_bobin_list'       => 'ボビン一覧',
                'nav_bobin_history'    => 'ボビン履歴',
                'nav_pending_cancel'   => '廃棄待ち一覧',
                'nav_employee_list'    => '👥 従業員一覧',
                'nav_change_pwd'       => '🔑 PW変更',
                'nav_logout'           => 'ログアウト',
                'nav_login'            => 'ログイン',

                // General
                'stt'                  => 'No.',
                'ok'                   => 'OK',
                'ng'                   => 'NG',
                'pass'                 => '合格',
                'fail'                 => '不合格',
                'save'                 => '保存',
                'cancel'               => 'キャンセル',
                'edit'                 => '編集',
                'delete'               => '削除',
                'confirm'              => '確認',
                'back'                 => '戻る',
                'search'               => '検索',
                'filter'               => '絞り込み',
                'reset_filter'         => 'リセット',
                'export_excel'         => 'Excel出力',
                'import_excel'         => 'Excel取込',
                'download_template'    => 'ひな形DL',
                'loading'              => '処理中...',
                'status'               => 'ステータス',
                'actions'              => '操作',
                'success'              => '成功',
                'error'                => '失敗',
                'scan_qr'              => 'QRスキャン',
                'start_camera'         => 'スキャナー起動',
                'stop_camera'          => 'スキャン停止',
                'close'                => '閉じる',
                'all'                  => 'すべて',
                'from_date'            => '開始日',
                'to_date'              => '終了日',
                'keyword_search'       => '検索キーワードを入力...',
                'view_detail'          => '詳細',
                'records'              => '件',
                'total'                => '合計',

                // Login Page
                'login_title'          => 'ボビン管理システム',
                'login_subtitle'       => '押出・QC・巻取の各工程間でボビン情報をリアルタイムに統合管理するプラットフォーム。',
                'login_brand_badge'    => 'プラスチック押出棟',
                'login_quick_track'    => 'システム即時追跡',
                'login_free_access'    => '自由アクセス',
                'login_username'       => 'ユーザー名 / 社員番号',
                'login_username_ph'    => '社員番号を入力してください...',
                'login_password'       => 'パスワード',
                'login_password_ph'    => 'パスワードを入力してください...',
                'login_btn_submit'     => 'システムにログイン',
                'login_forgot_pwd'     => 'パスワードをお忘れですか？',
                'login_toggle_pwd'     => 'パスワード表示切替',
                'login_stat_qr'        => '⚡ 高速QRコードスキャン',
                'login_stat_chart'     => '📊 リアルタイムグラフ分析',
                'login_stat_shift'     => '🏭 シフト別連続データ同期',
                'login_copyright'      => '© SMC Factory. セッション保護稼働中。',

                // Portal Cards on Login
                'portal_detail_title'  => '現在ボビン一覧',
                'portal_detail_sub'    => '全ボビンの数量、状態、工程進捗を確認',
                'portal_history_title' => 'ボビン履歴一覧',
                'portal_history_sub'   => '移動ログの閲覧、日付絞り込み、Excel出力',
                'portal_pending_title' => '廃棄待ちボビン一覧',
                'portal_pending_sub'   => '不良報告された廃棄待ちボビンの追跡・承認',

                // Modal Quên mật khẩu
                'fp_modal_title'       => '社内パスワード再発行手順',
                'fp_modal_desc'        => '本システムは工場内専用イントラネット（Local）で稼働しています。生産データの保全のため：',
                'fp_step_1_title'      => 'ステップ1：シフト管理者またはIT部門へ連絡',
                'fp_step_1_desc'       => 'シフトリーダー（職長）または工場IT担当者に直接連絡してください。',
                'fp_step_2_title'      => 'ステップ2：初期パスワードの発行',
                'fp_step_2_desc'       => '管理者が社員証を確認後、アカウントの初期パスワード（123）を発行します。',
                'fp_step_3_title'      => 'ステップ3：新しいパスワードへの変更必須',
                'fp_step_3_desc'       => '初期パスワードでログイン後、直ちに個人用パスワードへの変更画面が表示されます。',
                'fp_contact_info'      => '工場内連絡先：',
                'fp_contact_room'      => '生産管理室：内線 102 | ITサポート：内線 108',
                'fp_btn_understood'    => '理解しました（ログイン画面へ）',

                // Change Password
                'cp_title_first'       => '新しいパスワードの設定',
                'cp_title_normal'      => 'パスワードの変更',
                'cp_sub_first'         => '現在のパスワードを入力し、安全な新しいパスワードを設定してください',
                'cp_sub_normal'        => 'セキュリティ保護のため定期的にパスワードを更新してください',
                'cp_alert_first'       => 'セキュリティ通知：初回ログインを検知しました！ 個人アカウントの安全保護のため、続行する前に新しいパスワードを設定してください。',
                'cp_current_pwd'       => '現在のパスワード',
                'cp_current_pwd_ph'    => '現在のパスワードを入力...',
                'cp_new_pwd'           => '新しいパスワード',
                'cp_new_pwd_ph'        => '6文字以上...',
                'cp_confirm_pwd'       => '新しいパスワード（確認）',
                'cp_confirm_pwd_ph'    => '新しいパスワードを再入力...',
                'cp_strength_label'    => 'パスワード強度:',
                'cp_strength_weak'     => '弱い',
                'cp_strength_medium'   => '普通',
                'cp_strength_good'     => '良好',
                'cp_strength_strong'   => '非常に強い',
                'cp_btn_submit'        => 'パスワードを更新する',
                'cp_btn_back'          => 'ホームに戻る',

                // Page Titles
                'page_extrusion'       => '押出グループ - ボビン情報入力',
                'page_extrusion_edit'  => '押出グループ - ボビン情報編集',
                'page_qc'              => 'QCグループ - 品質検査',
                'page_winding'         => '巻取グループ - ボビン完了確認',
                'page_bobin_detail'    => '現在ボビン一覧',
                'page_bobin_history'   => 'ボビン履歴一覧',
                'page_pending_cancel'  => '廃棄待ちボビン一覧',
                'page_employee_list'   => '従業員情報管理一覧',

                // Bobin Specifications
                'bobin_code'           => 'ボビン番号',
                'po_code'              => '製造指示書 (PO)',
                'product_code'         => '製品コード',
                'length_m'             => '長さ (m)',
                'weight_kg'            => '重量 (kg)',
                'extrusion_machine'    => '押出機',
                'winding_machine'      => '巻取機',
                'material'             => '原材料',
                'material_lot'         => '材料ロット',
                'worker'               => '作業者',
                'worker_extrusion'     => '押出作業者',
                'worker_qc'            => 'QC検査者',
                'worker_winding'       => '巻取作業者',
                'shift'                => 'シフト',
                'shift_day'            => '昼勤',
                'shift_night'          => '夜勤',
                'print_lot'            => '印字ロット',
                'notes'                => '備考',
                'appearance'           => '外観',
                'defect_type'          => '不良種別',
                'defect_cause'         => '不良原因',
                'created_time'         => '登録日時',
                'updated_time'         => '更新日時',
                'qc_time'              => 'QC日時',
                'winding_time'         => '巻取日時',
                'total_bobin'          => 'ボビン総数',
                'btn_save_bobin'       => '💾 ボビン保存',
                'btn_update_bobin'     => 'ボビン更新',
                'btn_confirm_qc'       => 'QC検査結果を確定',
                'btn_confirm_winding'  => '巻取完了を確定',
                'btn_cancel_bobin'     => 'ボビン廃棄登録',
                'btn_restore_bobin'    => 'ボビン復帰',

                // Status
                'status_ready'         => '巻取済 (完了)',
                'status_unchecked'     => 'QC前 (未検査)',
                'status_checked'       => 'QC済 (巻取待)',
                'status_pending_cancel' => '廃棄待ち',
                'status_cancelled'     => '廃棄済',

                // Employee Management
                'emp_manage_title'     => '従業員一覧',
                'emp_manage_subtitle'  => '工場スタッフのアカウント管理、権限設定、パスワード再発行を行います。',
                'emp_total_stat'       => '全従業員数',
                'emp_ext_stat'         => '押出グループ',
                'emp_qc_stat'          => 'QCグループ',
                'emp_wind_stat'        => '巻取グループ',
                'emp_admin_stat'       => '管理者',
                'emp_code'             => '社員番号',
                'emp_name'             => '氏名',
                'emp_username'         => 'ログインID',
                'emp_role'             => '役職 / グループ',
                'emp_status'           => '勤務状態',
                'emp_password'         => 'パスワード',
                'emp_first_login'      => '初回PW変更',
                'emp_btn_add'          => '➕ 従業員の追加',
                'emp_btn_import'       => '📥 Excel取込',
                'emp_btn_export'       => '📤 Excel出力',
                'emp_btn_template'     => '📋 ひな形DL',
                'emp_btn_edit'         => '編集',
                'emp_btn_delete'       => '削除',
                'emp_btn_reset_pwd'    => '🔑 パスワード再発行',
                'emp_role_extrusion'   => '押出グループ',
                'emp_role_qc'          => 'QC検査グループ',
                'emp_role_winding'     => '巻取グループ',
                'emp_role_admin'       => 'システム管理者',
                'emp_status_active'    => '在職中 (有効)',
                'emp_status_inactive'  => '退職 / ロック中',
                'emp_first_login_need' => '未変更 (必須)',
                'emp_first_login_done' => '変更済',
                'emp_pwd_default'      => '初期値 (123)',
                'emp_pwd_encrypted'    => '暗号化済',
                'emp_modal_add_title'  => '新規従業員の登録',
                'emp_modal_edit_title' => '従業員情報の編集',
                'emp_modal_reset_title' => 'パスワード再発行',
                'emp_modal_import_title' => 'Excel/CSVから一括取込',
                'emp_import_hint'      => 'ひな形に沿った.csvまたは.xlsxファイルを選択してください。',
                'emp_confirm_delete'   => 'この従業員をシステムから削除してもよろしいですか？',
                'emp_msg_added'        => '従業員を追加しました！',
                'emp_msg_updated'      => '従業員情報を更新しました！',
                'emp_msg_deleted'      => '従業員を削除しました！',
                'emp_msg_reset_done'   => 'パスワードを初期値 (123) に再発行しました！',
                'emp_msg_imported'     => 'Excelの一括取込が正常に完了しました！'
            ]
        ];
    }
}

if (!function_exists('__')) {
    function __(string $key, ?string $default = null): string
    {
        return Language::get($key, $default);
    }
}
