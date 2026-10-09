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

    public static function getCustomFilePath(): string
    {
        $base = defined('ROOT_PATH') ? ROOT_PATH : dirname(__DIR__, 2);
        return $base . '/config/custom_translations.json';
    }

    public static function getCustomDictionary(): array
    {
        $filePath = self::getCustomFilePath();
        if (!file_exists($filePath)) {
            return [];
        }
        $content = file_get_contents($filePath);
        if ($content === false || trim($content) === '') {
            return [];
        }
        $decoded = json_decode($content, true);
        return is_array($decoded) ? $decoded : [];
    }

    public static function saveCustomDictionary(array $data): bool
    {
        $dir = dirname(self::getCustomFilePath());
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $cleanData = [
            'vi' => [],
            'en' => [],
            'ja' => []
        ];

        foreach (['vi', 'en', 'ja'] as $lang) {
            if (isset($data[$lang]) && is_array($data[$lang])) {
                foreach ($data[$lang] as $k => $v) {
                    $cleanKey = trim((string)$k);
                    $cleanVal = trim((string)$v);
                    if ($cleanKey !== '' && $cleanVal !== '') {
                        $cleanData[$lang][$cleanKey] = $cleanVal;
                    }
                }
            }
        }

        $json = json_encode($cleanData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $result = file_put_contents(self::getCustomFilePath(), $json, LOCK_EX);

        if ($result !== false) {
            self::$dictionary = null; // Reset cache so subsequent calls re-load with overrides
            return true;
        }

        return false;
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

        // Kiểm tra alias: btn_confirm <-> confirm, btn_cancel <-> cancel
        $aliasKey = match ($key) {
            'btn_confirm' => 'confirm',
            'confirm'     => 'btn_confirm',
            'btn_cancel'  => 'cancel',
            'cancel'      => 'btn_cancel',
            default       => null
        };
        if ($aliasKey !== null && isset(self::$dictionary[$lang][$aliasKey])) {
            return self::$dictionary[$lang][$aliasKey];
        }

        if (isset(self::$dictionary['vi'][$key])) {
            return self::$dictionary['vi'][$key];
        }

        if ($aliasKey !== null && isset(self::$dictionary['vi'][$aliasKey])) {
            return self::$dictionary['vi'][$aliasKey];
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
                'nav_qc_edit'          => 'Điều chỉnh QC',
                'nav_winding'          => 'Cuộn',
                'nav_winding_edit'     => 'Điều chỉnh Cuộn',
                'nav_bobin_list'       => 'Danh sách Bobin',
                'nav_bobin_history'    => 'Lịch sử Bobin',
                'nav_pending_cancel'   => 'Danh sách chờ hủy',
                'nav_employee_list'    => 'Danh sách nhân viên',
                'nav_change_pwd'       => 'Đổi mật khẩu',
                'nav_logout'           => 'Đăng xuất',
                'nav_login'            => 'Đăng nhập',
                'nav_permissions'      => 'Phân quyền tài khoản',
                'nav_translations'     => 'Cấu hình đa ngôn ngữ',
                'lang_page_title'      => 'Cấu hình Từ điển Đa ngôn ngữ Động',
                'lang_page_subtitle'   => 'Trực tiếp chỉnh sửa các ô nhập liệu (textfield) để quy định nội dung chuyển đổi giữa các ngôn ngữ mà không bị fix cứng',
                'lang_badge_custom'    => 'Công cụ I18n Động',
                'lang_stat_total'      => 'Tổng số mục từ điển',
                'lang_stat_custom'     => 'Đã tùy chỉnh riêng',
                'lang_stat_langs'      => 'Ngôn ngữ đồng bộ (VI/EN/JA)',
                'lang_search_ph'       => 'Tìm theo mã từ khóa (Key) hoặc nội dung bất kỳ...',
                'lang_filter_all'      => '-- Tất cả từ khóa --',
                'lang_filter_custom'   => 'Chỉ xem mục đã tùy chỉnh',
                'lang_filter_default'  => 'Chỉ xem mục mặc định',
                'lang_btn_add_key'     => 'Thêm từ khóa mới',
                'lang_btn_save_all'    => 'Lưu toàn bộ thay đổi',
                'lang_btn_reset_all'   => 'Khôi phục toàn bộ về gốc',
                'lang_table_title'     => 'Danh sách từ khóa & Bản dịch 3 ngôn ngữ',
                'lang_col_key'         => 'Mã từ khóa (Key)',
                'lang_col_vi'          => '🇻🇳 Tiếng Việt (VI)',
                'lang_col_en'          => '🇬🇧 English (EN)',
                'lang_col_ja'          => '🇯🇵 日本語 (JA)',
                'lang_col_action'      => 'Thao tác',
                'lang_tag_custom'      => 'Tùy chỉnh',
                'lang_modal_add_title' => 'Thêm từ khóa dịch thuật mới',
                'lang_modal_key_label' => 'Mã định danh từ khóa (Key):',
                'lang_modal_vi_label'  => '🇻🇳 Bản dịch Tiếng Việt:',
                'lang_modal_en_label'  => '🇬🇧 Bản dịch English:',
                'lang_modal_ja_label'  => '🇯🇵 Bản dịch 日本語:',
                'lang_btn_confirm_add' => 'Lưu từ khóa vào hệ thống',
                'perm_page_title'      => 'Quản lý Phân quyền Tài khoản',
                'perm_page_subtitle'   => 'Tra cứu nhân viên và thiết lập chi tiết các quyền thao tác cho từng tài khoản',
                'perm_search_placeholder' => 'Nhập mã nhân viên hoặc tên để tìm...',
                'perm_btn_search'      => 'Tra cứu',
                'perm_btn_save'        => 'Lưu cấu hình phân quyền',
                'perm_btn_select_all'  => 'Chọn tất cả',
                'perm_btn_deselect_all' => 'Bỏ chọn tất cả',
                'perm_btn_reset_default' => 'Khôi phục quyền theo Role',
                'perm_group_extrusion' => 'Công đoạn Đùn (Extrusion)',
                'perm_extrusion_create' => 'Nhập thông số Đùn & Tạo Bobin',
                'perm_extrusion_create_desc' => 'Truy cập trang nhập liệu sản xuất đùn, khởi tạo mã Bobin mới',
                'perm_extrusion_edit'  => 'Điều chỉnh thông tin Bobin Đùn',
                'perm_extrusion_edit_desc' => 'Truy cập trang Điều chỉnh Đùn, cập nhật và xóa Bobin đùn',
                'perm_group_qc'        => 'Kiểm tra chất lượng (QC)',
                'perm_qc_check'        => 'Kiểm tra ngoại quan QC',
                'perm_qc_check_desc'   => 'Xem danh sách chờ QC, xác nhận đạt/lỗi ngoại quan Bobin',
                'perm_qc_edit'         => 'Điều chỉnh kết quả QC',
                'perm_qc_edit_desc'    => 'Truy cập trang Điều chỉnh QC, sửa tiêu chuẩn kiểm tra hoặc báo hủy QC',
                'perm_group_winding'   => 'Công đoạn Cuộn (Winding)',
                'perm_winding_confirm' => 'Xác nhận thông tin Cuộn',
                'perm_winding_confirm_desc' => 'Xem danh sách chờ cuộn, nhập máy cuộn, test thông khí & hoàn tất',
                'perm_winding_edit'    => 'Điều chỉnh thông tin Cuộn',
                'perm_winding_edit_desc' => 'Truy cập trang Điều chỉnh Cuộn, sửa thông số máy cuộn hoặc báo hủy',
                'perm_group_monitoring' => 'Theo dõi & Báo cáo',
                'perm_bobin_list'      => 'Xem Danh sách Bobin chi tiết',
                'perm_bobin_list_desc' => 'Tra cứu danh sách Bobin tồn line, phân lọc theo vị trí và xuất Excel',
                'perm_bobin_history'   => 'Xem Lịch sử luân chuyển Bobin',
                'perm_bobin_history_desc' => 'Tra cứu dòng thời gian và dấu vết lịch sử thay đổi của Bobin',
                'perm_pending_cancel'  => 'Quản lý Bobin chờ hủy',
                'perm_pending_cancel_desc' => 'Xem danh sách Bobin chờ duyệt hủy và thực hiện hủy/khôi phục',
                'perm_group_system'    => 'Quản trị hệ thống',
                'perm_employee_manage' => 'Quản lý Danh sách Nhân viên',
                'perm_employee_manage_desc' => 'Thêm mới, sửa, khóa tài khoản và import/export danh sách nhân viên',
                'perm_permission_manage' => 'Phân quyền Tài khoản Người dùng',
                'perm_permission_manage_desc' => 'Tra cứu nhân viên và thiết lập chi tiết các quyền thao tác cho từng user',
                'perm_custom_badge'    => 'Quyền tùy chỉnh',
                'perm_default_badge'   => 'Mặc định theo Role',
                'perm_save_success'    => 'Cập nhật phân quyền thành công!',
                'perm_select_employee_prompt' => 'Vui lòng chọn hoặc tra cứu một nhân viên từ danh sách để thiết lập quyền thao tác.',
                'perm_quick_select'    => 'Chọn nhanh nhân viên:',
                'perm_active_status'   => 'Đang hoạt động',
                'perm_inactive_status' => 'Đã khóa',
                'perm_role_label'      => 'Vai trò chính',
                'sidebar_toggle'       => 'Thu gọn / Mở rộng',
                'sidebar_nav_title_extrusion_stage' => 'NHÓM ĐÙN',
                'sidebar_nav_title_qc_stage' => 'CÔNG ĐOẠN QC',
                'sidebar_nav_title_winding_stage' => 'CÔNG ĐOẠN CUỘN',
                'sidebar_nav_title_monitor' => 'GIÁM SÁT & BÁO CÁO',
                'sidebar_nav_title_system' => 'QUẢN TRỊ HỆ THỐNG',
                'breadcrumb_home'      => 'Trang chủ',
                'tree_items_count'     => 'mục',

                // Chung & Thao tác
                'stt'                  => 'STT',
                'ok'                   => 'OK',
                'ng'                   => 'NG',
                'pass'                 => 'Đạt',
                'fail'                 => 'Không đạt',
                'save'                 => 'Lưu',
                'cancel'               => 'Hủy',
                'btn_cancel'           => 'Hủy',
                'edit'                 => 'Chỉnh sửa',
                'delete'               => 'Xóa',
                'confirm'              => 'Xác nhận',
                'btn_confirm'          => 'Xác nhận',
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
                'page_qc_edit'         => 'Quản trị - Điều chỉnh thông tin kiểm tra QC',
                'page_winding'         => 'Nhóm Cuộn - Xác nhận hoàn thành Bobin',
                'page_winding_edit'    => 'Quản trị - Điều chỉnh thông tin công đoạn Cuộn',
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
                'status_extruded'      => 'Đã đùn',
                'status_unchecked'     => 'Chưa QC (Chờ kiểm tra)',
                'status_checked'       => 'Đã QC (Chờ cuộn)',
                'status_pending_cancel' => 'Chờ hủy',
                'status_cancelled'     => 'Đã hủy',
                'lang_name'            => 'Tiếng Việt',
                'flag'                 => '🇻🇳',
                'short'                => 'VI',
                'pipeline_no_ext_data' => '⏳ Chưa có dữ liệu sản xuất Đùn',
                'pipeline_no_qc_data'  => '⏳ Chưa có dữ liệu kiểm tra QC',
                'pipeline_no_winding_data' => '⏳ Chưa có dữ liệu thông tin cuộn',

                // ================= QUẢN LÝ NHÂN VIÊN =================
                'emp_manage_title'     => 'DANH SÁCH NHÂN VIÊN',
                'emp_manage_subtitle'  => 'Quản lý tài khoản, phân quyền vai trò và cấp lại mật khẩu cho nhân viên.',
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
                'emp_msg_imported'     => 'Nhập file Excel hoàn tất thành công!',
                'emp_chart_title'      => 'Phân bổ vai trò',
                'emp_chart_no_data'    => 'Chưa có dữ liệu',
                'emp_active_count'     => 'Đang làm việc',
                'emp_inactive_count'   => 'Đã nghỉ việc',
                'emp_showing'          => 'Hiển thị',
                'emp_of'               => '/',
                'emp_staff'            => 'nhân viên',
                'emp_search_ph'        => 'Tìm theo Mã NV hoặc Họ tên...',
                'emp_all_roles'        => '-- Tất cả vai trò --',
                'emp_all_status'       => '-- Tất cả tình trạng --',
                'emp_notice_title'     => 'Cơ chế tự động thông minh',
                'emp_notice_username'  => 'Tên đăng nhập sẽ tự động đặt là Mã nhân viên',
                'emp_notice_password'  => 'Mật khẩu khởi tạo mặc định là 123 (bắt buộc đổi khi đăng nhập)',
                'emp_code_ph'          => 'VD: 02619486 hoặc NV01',
                'emp_name_ph'          => 'VD: Nguyễn Văn A',
                'emp_code_hint'        => 'Mã định danh cá nhân duy nhất trong xưởng.',
                'emp_role_hint'        => 'Chọn phân xưởng hoặc vai trò trực thuộc của nhân viên.',
                'emp_self_badge'       => 'Bạn',
                'emp_updated_prefix'   => 'Cập nhật:',
                'emp_new_badge'        => 'Mới tạo',
                'emp_profile_label'    => 'Mã số nhân viên (Tài khoản):',
                'emp_reset_target'     => 'Nhân viên cần cấp lại:',
                'emp_reset_desc_title' => 'Quy trình bảo mật nội bộ:',
                'emp_reset_desc_body'  => 'Mật khẩu tài khoản sẽ được khôi phục về mặc định: 123. Hệ thống sẽ bắt buộc nhân viên đổi mật khẩu mới ngay sau khi đăng nhập thành công.',
                'emp_reset_custom_label' => '⚙️ Đặt mật khẩu tùy chỉnh khác (Nếu cần)',
                'emp_reset_custom_ph'  => 'Để trống = Mặc định 123',
                'emp_reset_btn'        => '⚡ Khôi phục về 123',
                'emp_import_title'     => 'Chọn file Excel / CSV nhân viên',
                'emp_import_sub'       => 'Hệ thống hỗ trợ file .CSV (UTF-8) xuất trực tiếp từ Microsoft Excel',
                'emp_import_update'    => 'Cập nhật thông tin nếu Mã nhân viên đã có trên hệ thống',
                'emp_import_col_title' => '📌 Cấu trúc các cột chuẩn trong file:',
                'emp_import_col1'      => 'Cột 1: Mã NV (Bắt buộc)',
                'emp_import_col2'      => 'Cột 2: Họ và tên (Bắt buộc)',
                'emp_import_col3'      => 'Cột 3: Vai trò (extrusion/qc/winding/admin)',
                'emp_import_col4'      => 'Cột 4: Tên đăng nhập (Có thể để trống)',
                'emp_import_col5'      => 'Cột 5: Trạng thái (1: Làm việc, 0: Khóa)',
                'emp_import_download'  => 'Tải file mẫu chuẩn (Mau_nhap_nhan_vien_SMC.csv)',
                'emp_import_start'     => '🚀 Bắt đầu nhập dữ liệu',
                'emp_delete_confirm_full' => 'Bạn có chắc chắn muốn xóa nhân viên khỏi hệ thống không? Thao tác này không thể hoàn tác!',
                'emp_msg_success'      => '✅',
                'emp_msg_error'        => '❌',
                // Chart & Analytics
                'chart_empty'          => 'Trống',
                'chart_bobin_count'    => 'Số lượng Bobin',
                'chart_status_stats'   => 'Thống kê trạng thái Bobin',
                'chart_history_status_stats' => 'Thống kê lịch sử cập nhật trạng thái Bobin',
                'chart_ratio'          => 'Tỷ lệ',
                'chart_status_distribution' => 'Phân bố trạng thái Bobin',
                // Section & Card Titles
                'sec_bobin_identification' => 'Định danh Bobin & Người phụ trách',
                'sec_bobin_identification_desc' => 'Quét mã QR hoặc nhập mã Bobin và thông tin nhân viên phụ trách',
                'sec_production_info'  => 'THÔNG TIN SẢN XUẤT',
                'sec_production_info_desc' => 'Mã sản phẩm, Chiều dài, Lot in',
                'sec_time_and_rack'    => 'Thời điểm đùn và vị trí Rack',
                'sec_time_and_rack_desc' => 'Thời điểm hoàn thành Bobin và vị trí đặt Rack',
                'sec_ext_check1'       => 'Nhóm đùn Check lần 1',
                'sec_ext_check1_desc'  => 'Xác nhận chất lượng ban đầu trước công đoạn QC',
                'sec_ext_visual_check' => 'Tự kiểm tra ngoại quan đùn',
                // Labels & Units
                'lbl_bobin_id_code'    => 'Mã định danh Bobin',
                'lbl_bobin_size'       => 'Kích thước Bobin',
                'lbl_ext_emp_code'     => 'Mã số nhân viên',
                'lbl_ext_emp_name'     => 'Họ tên nhân viên',
                'lbl_machine_no'       => 'Số máy đùn',
                'lbl_material_type'    => 'Vật liệu',
                'lbl_grind_time'       => 'Số lần nghiền',
                'lbl_rack_location'    => 'Vị trí Rack',
                'lbl_ext_date'         => 'Ngày đùn',
                'lbl_finish_time'      => 'Thời gian hoàn thành',
                'lbl_manual_time'      => 'Chọn thời gian thủ công',
                'unit_meter'           => 'mét',
                'chk_diameter'         => 'Đường kính',
                'chk_gel'              => 'Gel',
                'chk_foreign_object'   => 'Dị vật',
                'chk_color'            => 'Màu sắc',
                'chk_print'            => 'Chữ in',
                'btn_refresh'          => 'Làm mới',
                'btn_scan_qr'          => 'Quét QR',
                // Placeholders
                'ph_scan_bobin'        => 'Nhập hoặc quét mã Bobin...',
                'ph_auto_size'         => 'Tự động theo mã Bobin',
                'ph_search_product'    => 'Gõ để tìm mã sản phẩm...',
                'ph_select_machine'    => 'Chọn số máy đùn...',
                'ph_select_material'   => 'Chọn loại vật liệu...',
                'ph_grind_hint'        => 'Nhập 0, 1 hoặc 2',
                'ph_enter_material_lot' => 'Nhập Lot vật liệu...',
                'ph_auto_printlot'     => 'Lot in được tạo tự động',
                'ph_select_rack'       => 'Chọn Rack đặt...',
                // Pagination
                'page_prev'            => '‹ Trước',
                'page_next'            => 'Sau ›',
                'page_info'            => 'Trang :current / :total (Tổng :records Bobin)'
            ],

            // ==========================================
            // 🇬🇧 ENGLISH
            // ==========================================
            'en' => [
                // Navigation
                'nav_extrusion'        => 'Extrusion',
                'nav_extrusion_edit'   => 'Extrusion Edit',
                'nav_qc'               => 'QC Check',
                'nav_qc_edit'          => 'QC Edit',
                'nav_winding'          => 'Winding',
                'nav_winding_edit'     => 'Winding Edit',
                'nav_bobin_list'       => 'Bobin List',
                'nav_bobin_history'    => 'Bobin History',
                'nav_pending_cancel'   => 'Pending Scraps',
                'nav_employee_list'    => 'Employee List',
                'nav_change_pwd'       => 'Change Password',
                'nav_logout'           => 'Logout',
                'nav_login'            => 'Login',
                'nav_permissions'      => 'Account Permissions',
                'nav_translations'     => 'Language Settings',
                'lang_page_title'      => 'Dynamic Multi-Language Dictionary Settings',
                'lang_page_subtitle'   => 'Directly edit textfields to customize translation texts across languages without hardcoded restrictions',
                'lang_badge_custom'    => 'Dynamic I18n Engine',
                'lang_stat_total'      => 'Total Dictionary Entries',
                'lang_stat_custom'     => 'Custom Overrides',
                'lang_stat_langs'      => 'Synchronized Languages (VI/EN/JA)',
                'lang_search_ph'       => 'Search by keyword key or any translation content...',
                'lang_filter_all'      => '-- All Keywords --',
                'lang_filter_custom'   => 'Custom Overrides Only',
                'lang_filter_default'  => 'Default Built-in Only',
                'lang_btn_add_key'     => 'Add New Keyword',
                'lang_btn_save_all'    => 'Save All Changes',
                'lang_btn_reset_all'   => 'Restore All to Defaults',
                'lang_table_title'     => 'Keyword List & 3-Language Translations',
                'lang_col_key'         => 'Keyword (Key)',
                'lang_col_vi'          => '🇻🇳 Vietnamese (VI)',
                'lang_col_en'          => '🇬🇧 English (EN)',
                'lang_col_ja'          => '🇯🇵 Japanese (JA)',
                'lang_col_action'      => 'Actions',
                'lang_tag_custom'      => 'Custom',
                'lang_modal_add_title' => 'Add New Translation Keyword',
                'lang_modal_key_label' => 'Keyword Identifier (Key):',
                'lang_modal_vi_label'  => '🇻🇳 Vietnamese Translation:',
                'lang_modal_en_label'  => '🇬🇧 English Translation:',
                'lang_modal_ja_label'  => '🇯🇵 Japanese Translation:',
                'lang_btn_confirm_add' => 'Save Keyword to System',
                'perm_page_title'      => 'Account Permissions Management',
                'perm_page_subtitle'   => 'Search employees and configure granular operational permissions for each user',
                'perm_search_placeholder' => 'Enter employee code or name to search...',
                'perm_btn_search'      => 'Search',
                'perm_btn_save'        => 'Save Permissions',
                'perm_btn_select_all'  => 'Select All',
                'perm_btn_deselect_all' => 'Deselect All',
                'perm_btn_reset_default' => 'Reset to Role Defaults',
                'perm_group_extrusion' => 'Extrusion Stage',
                'perm_extrusion_create' => 'Input Extrusion & Create Bobin',
                'perm_extrusion_create_desc' => 'Access extrusion data entry and initialize new bobin codes',
                'perm_extrusion_edit'  => 'Adjust Extrusion Bobin',
                'perm_extrusion_edit_desc' => 'Access Extrusion Edit page, update parameters and delete bobins',
                'perm_group_qc'        => 'Quality Control (QC)',
                'perm_qc_check'        => 'QC Visual Inspection',
                'perm_qc_check_desc'   => 'View QC pending queue and confirm pass/defect inspection results',
                'perm_qc_edit'         => 'Adjust QC Results',
                'perm_qc_edit_desc'    => 'Access QC Edit page, modify inspection items or report cancellation',
                'perm_group_winding'   => 'Winding Stage',
                'perm_winding_confirm' => 'Confirm Winding Information',
                'perm_winding_confirm_desc' => 'View winding queue, select machine, perform air flow test & complete',
                'perm_winding_edit'    => 'Adjust Winding Information',
                'perm_winding_edit_desc' => 'Access Winding Edit page, modify machine data or cancel',
                'perm_group_monitoring' => 'Monitoring & Reporting',
                'perm_bobin_list'      => 'Detailed Bobin List',
                'perm_bobin_list_desc' => 'View active bobins on rack, filter by location and export to Excel',
                'perm_bobin_history'   => 'View Bobin History',
                'perm_bobin_history_desc' => 'Track lifecycle timeline and audit trail modifications of bobins',
                'perm_pending_cancel'  => 'Manage Pending Cancellation',
                'perm_pending_cancel_desc' => 'Review pending cancellation bobins, approve cancel or restore',
                'perm_group_system'    => 'System Administration',
                'perm_employee_manage' => 'Employee Management',
                'perm_employee_manage_desc' => 'Create, edit, deactivate accounts and import/export employee list',
                'perm_permission_manage' => 'User Account Permissions',
                'perm_permission_manage_desc' => 'Search employees and configure specific operational permissions per user',
                'perm_custom_badge'    => 'Custom Permissions',
                'perm_default_badge'   => 'Role Default',
                'perm_save_success'    => 'Permissions updated successfully!',
                'perm_select_employee_prompt' => 'Please select or search for an employee from the list to configure permissions.',
                'perm_quick_select'    => 'Quick Select Employee:',
                'perm_active_status'   => 'Active',
                'perm_inactive_status' => 'Deactivated',
                'perm_role_label'      => 'Primary Role',
                'sidebar_toggle'       => 'Collapse / Expand',
                'sidebar_nav_title_extrusion_stage' => 'EXTRUSION STAGE',
                'sidebar_nav_title_qc_stage' => 'QC STAGE',
                'sidebar_nav_title_winding_stage' => 'WINDING STAGE',
                'sidebar_nav_title_monitor' => 'MONITORING & REPORTS',
                'sidebar_nav_title_system' => 'SYSTEM ADMIN',
                'breadcrumb_home'      => 'Home',
                'tree_items_count'     => 'items',

                // General
                'stt'                  => 'No.',
                'ok'                   => 'OK',
                'ng'                   => 'NG',
                'pass'                 => 'Pass',
                'fail'                 => 'Fail',
                'save'                 => 'Save',
                'cancel'               => 'Cancel',
                'btn_cancel'           => 'Cancel',
                'edit'                 => 'Edit',
                'delete'               => 'Delete',
                'confirm'              => 'Confirm',
                'btn_confirm'          => 'Confirm',
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
                'page_qc_edit'         => 'Admin - Edit QC Inspection Data',
                'page_winding'         => 'Winding - Finalize Bobin',
                'page_winding_edit'    => 'Admin - Edit Winding Process Data',
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
                'status_extruded'      => 'Extruded',
                'status_unchecked'     => 'Unchecked (Pending QC)',
                'status_checked'       => 'Checked (Pending Winding)',
                'status_pending_cancel' => 'Pending Scrap',
                'status_cancelled'     => 'Scrapped',
                'lang_name'            => 'English',
                'flag'                 => '🇬🇧',
                'short'                => 'EN',
                'pipeline_no_ext_data' => '⏳ No extrusion production data',
                'pipeline_no_qc_data'  => '⏳ No QC inspection data',
                'pipeline_no_winding_data' => '⏳ No winding information',

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
                'emp_msg_imported'     => 'Excel import completed successfully!',
                'emp_chart_title'      => 'Role Distribution',
                'emp_chart_no_data'    => 'No data',
                'emp_active_count'     => 'Active',
                'emp_inactive_count'   => 'Inactive',
                'emp_showing'          => 'Showing',
                'emp_of'               => '/',
                'emp_staff'            => 'staff',
                'emp_search_ph'        => 'Search by ID or Name...',
                'emp_all_roles'        => '-- All Roles --',
                'emp_all_status'       => '-- All Status --',
                'emp_notice_title'     => 'Auto Setup Rules',
                'emp_notice_username'  => 'Username is auto-set to Employee ID',
                'emp_notice_password'  => 'Default password is 123 (must change on first login)',
                'emp_code_ph'          => 'e.g. 02619486 or EMP01',
                'emp_name_ph'          => 'e.g. John Smith',
                'emp_code_hint'        => 'Unique personal ID in the factory.',
                'emp_role_hint'        => "Select the employee's department or role.",
                'emp_self_badge'       => 'You',
                'emp_updated_prefix'   => 'Updated:',
                'emp_new_badge'        => 'Newly Added',
                'emp_profile_label'    => 'Employee ID (Username):',
                'emp_reset_target'     => 'Employee to reset:',
                'emp_reset_desc_title' => 'Internal Security Process:',
                'emp_reset_desc_body'  => 'The account password will be reset to default: 123. The system will require the employee to change their password immediately after login.',
                'emp_reset_custom_label' => '⚙️ Set custom password (Optional)',
                'emp_reset_custom_ph'  => 'Leave blank = Default 123',
                'emp_reset_btn'        => '⚡ Reset to 123',
                'emp_import_title'     => 'Select Employee Excel / CSV',
                'emp_import_sub'       => 'Supports .CSV (UTF-8) exported directly from Microsoft Excel',
                'emp_import_update'    => 'Update info if Employee ID already exists',
                'emp_import_col_title' => '📌 Required CSV Column Structure:',
                'emp_import_col1'      => 'Column 1: Employee ID (Required)',
                'emp_import_col2'      => 'Column 2: Full Name (Required)',
                'emp_import_col3'      => 'Column 3: Role (extrusion/qc/winding/admin)',
                'emp_import_col4'      => 'Column 4: Username (Optional)',
                'emp_import_col5'      => 'Column 5: Status (1: Active, 0: Locked)',
                'emp_import_download'  => 'Download sample template (Employee_Import_SMC.csv)',
                'emp_import_start'     => '🚀 Start Import',
                'emp_delete_confirm_full' => 'Are you sure you want to delete this employee from the system? This action cannot be undone!',
                'emp_msg_success'      => '✅',
                'emp_msg_error'        => '❌',
                // Chart & Analytics
                'chart_empty'          => 'Empty',
                'chart_bobin_count'    => 'Bobin Quantity',
                'chart_status_stats'   => 'Bobin Status Statistics',
                'chart_history_status_stats' => 'Bobin Status Update History',
                'chart_ratio'          => 'Percentage',
                'chart_status_distribution' => 'Bobin Status Distribution',
                // Section & Card Titles
                'sec_bobin_identification' => 'Bobin Identification & In-charge',
                'sec_bobin_identification_desc' => 'Scan QR or enter Bobin ID and operator info',
                'sec_production_info'  => 'PRODUCTION INFORMATION',
                'sec_production_info_desc' => 'Product Code, Length, Print Lot',
                'sec_time_and_rack'    => 'Extrusion Time & Rack Location',
                'sec_time_and_rack_desc' => 'Bobin completion time and Rack storage location',
                'sec_ext_check1'       => 'Extrusion 1st Inspection',
                'sec_ext_check1_desc'  => 'Initial quality verification before QC inspection',
                'sec_ext_visual_check' => 'Extrusion Visual Check',
                // Labels & Units
                'lbl_bobin_id_code'    => 'Bobin ID Code',
                'lbl_bobin_size'       => 'Bobin Size',
                'lbl_ext_emp_code'     => 'Employee ID',
                'lbl_ext_emp_name'     => 'Employee Name',
                'lbl_machine_no'       => 'Extruder Machine No.',
                'lbl_material_type'    => 'Material',
                'lbl_grind_time'       => 'Grinding Times',
                'lbl_rack_location'    => 'Rack Location',
                'lbl_ext_date'         => 'Extrusion Date',
                'lbl_finish_time'      => 'Completion Time',
                'lbl_manual_time'      => 'Manual Time Selection',
                'unit_meter'           => 'meter',
                'chk_diameter'         => 'Diameter',
                'chk_gel'              => 'Gel',
                'chk_foreign_object'   => 'Foreign Object',
                'chk_color'            => 'Color',
                'chk_print'            => 'Print Text',
                'btn_refresh'          => 'Refresh',
                'btn_scan_qr'          => 'Scan QR',
                // Placeholders
                'ph_scan_bobin'        => 'Enter or scan Bobin code...',
                'ph_auto_size'         => 'Auto generated by Bobin code',
                'ph_search_product'    => 'Type to search product code...',
                'ph_select_machine'    => 'Select extruder machine...',
                'ph_select_material'   => 'Select material...',
                'ph_grind_hint'        => 'Enter 0, 1 or 2',
                'ph_enter_material_lot' => 'Enter material lot...',
                'ph_auto_printlot'     => 'Auto merged Print Lot',
                'ph_select_rack'       => 'Select Rack location...',
                // Pagination
                'page_prev'            => '‹ Prev',
                'page_next'            => 'Next ›',
                'page_info'            => 'Page :current / :total (Total :records Bobins)'
            ],

            // ==========================================
            // 🇯🇵 日本語 (JAPANESE)
            // ==========================================
            'ja' => [
                // Navigation
                'nav_extrusion'        => '押出',
                'nav_extrusion_edit'   => '押出編集',
                'nav_qc'               => 'QC検査',
                'nav_qc_edit'          => 'QC編集',
                'nav_winding'          => '巻取',
                'nav_winding_edit'     => '巻取編集',
                'nav_bobin_list'       => 'ボビン一覧',
                'nav_bobin_history'    => 'ボビン履歴',
                'nav_pending_cancel'   => '廃棄待ち一覧',
                'nav_employee_list'    => '従業員一覧',
                'nav_change_pwd'       => 'PW変更',
                'nav_logout'           => 'ログアウト',
                'nav_login'            => 'ログイン',
                'nav_permissions'      => 'アカウント権限設定',
                'nav_translations'     => '多言語設定',
                'lang_page_title'      => '動的多言語辞書設定',
                'lang_page_subtitle'   => 'テキストボックスから直接入力して、各言語の翻訳文を動的にカスタマイズできます',
                'lang_badge_custom'    => '動的I18nエンジン',
                'lang_stat_total'      => '総辞書登録数',
                'lang_stat_custom'     => '個別カスタマイズ項目',
                'lang_stat_langs'      => '同期対応言語 (VI/EN/JA)',
                'lang_search_ph'       => 'キーワードコードまたは内容で検索...',
                'lang_filter_all'      => '-- すべてのキーワード --',
                'lang_filter_custom'   => 'カスタマイズ済みのみ',
                'lang_filter_default'  => 'デフォルト標準のみ',
                'lang_btn_add_key'     => '新規キーワード追加',
                'lang_btn_save_all'    => 'すべての変更を保存',
                'lang_btn_reset_all'   => 'すべて初期値に戻す',
                'lang_table_title'     => 'キーワード一覧＆3言語翻訳',
                'lang_col_key'         => 'キーワードコード (Key)',
                'lang_col_vi'          => '🇻🇳 ベトナム語 (VI)',
                'lang_col_en'          => '🇬🇧 英語 (EN)',
                'lang_col_ja'          => '🇯🇵 日本語 (JA)',
                'lang_col_action'      => '操作',
                'lang_tag_custom'      => 'カスタム',
                'lang_modal_add_title' => '新規翻訳キーワードの追加',
                'lang_modal_key_label' => 'キーワード識別子 (Key):',
                'lang_modal_vi_label'  => '🇻🇳 ベトナム語テキスト:',
                'lang_modal_en_label'  => '🇬🇧 英語テキスト:',
                'lang_modal_ja_label'  => '🇯🇵 日本語テキスト:',
                'lang_btn_confirm_add' => 'システムに登録',
                'perm_page_title'      => 'アカウント権限管理',
                'perm_page_subtitle'   => '社員コードで検索し、各ユーザーの詳細な操作権限を設定',
                'perm_search_placeholder' => '社員コードまたは名前を入力...',
                'perm_btn_search'      => '検索',
                'perm_btn_save'        => '権限設定を保存',
                'perm_btn_select_all'  => 'すべて選択',
                'perm_btn_deselect_all' => 'すべて解除',
                'perm_btn_reset_default' => '役職デフォルトに戻す',
                'perm_group_extrusion' => '押出工程 (Extrusion)',
                'perm_extrusion_create' => '押出データ入力・ボビン作成',
                'perm_extrusion_create_desc' => '押出製造データ入力ページへのアクセスと新規ボビン作成',
                'perm_extrusion_edit'  => '押出ボビン情報調整',
                'perm_extrusion_edit_desc' => '押出調整ページへのアクセス、仕様更新および削除',
                'perm_group_qc'        => '品質管理 (QC)',
                'perm_qc_check'        => 'QC外観検査',
                'perm_qc_check_desc'   => 'QC待機リスト確認および外観検査の合否判定',
                'perm_qc_edit'         => 'QC検査結果調整',
                'perm_qc_edit_desc'    => 'QC調整ページへのアクセス、検査項目修正または破棄申請',
                'perm_group_winding'   => '巻取工程 (Winding)',
                'perm_winding_confirm' => '巻取情報確認・完了',
                'perm_winding_confirm_desc' => '巻取機選択、通気テスト入力および巻取完了の確定',
                'perm_winding_edit'    => '巻取情報調整',
                'perm_winding_edit_desc' => '巻取調整ページへのアクセス、巻取機修正または破棄申請',
                'perm_group_monitoring' => '監視・レポート',
                'perm_bobin_list'      => 'ボビン詳細一覧表示',
                'perm_bobin_list_desc' => 'ラック上の保管ボビン検索、場所別絞り込みおよびExcel出力',
                'perm_bobin_history'   => 'ボビン履歴表示',
                'perm_bobin_history_desc' => 'ボビンのライフサイクルタイムラインと変更ログの確認',
                'perm_pending_cancel'  => '破棄待機ボビン管理',
                'perm_pending_cancel_desc' => '破棄待機リスト確認、承認または復元',
                'perm_group_system'    => 'システム管理',
                'perm_employee_manage' => '従業員管理',
                'perm_employee_manage_desc' => 'アカウントの追加・編集・無効化およびCSV入出力',
                'perm_permission_manage' => 'アカウント権限管理',
                'perm_permission_manage_desc' => '従業員検索およびユーザーごとの詳細操作権限設定',
                'perm_custom_badge'    => 'カスタム権限',
                'perm_default_badge'   => '役職デフォルト',
                'perm_save_success'    => '権限を正常に更新しました！',
                'perm_select_employee_prompt' => 'リストから従業員を選択または検索して権限を設定してください。',
                'perm_quick_select'    => '従業員クイック選択:',
                'perm_active_status'   => '有効',
                'perm_inactive_status' => '停止中',
                'perm_role_label'      => '基本役職',
                'sidebar_toggle'       => '折りたたみ / 展開',
                'sidebar_nav_title_extrusion_stage' => '押出工程',
                'sidebar_nav_title_qc_stage' => '品質管理 (QC)',
                'sidebar_nav_title_winding_stage' => '巻取完了',
                'sidebar_nav_title_monitor' => '監視・レポート',
                'sidebar_nav_title_system' => 'システム管理',
                'breadcrumb_home'      => 'ホーム',
                'tree_items_count'     => '項目',

                // General
                'stt'                  => 'No.',
                'ok'                   => 'OK',
                'ng'                   => 'NG',
                'pass'                 => '合格',
                'fail'                 => '不合格',
                'save'                 => '保存',
                'cancel'               => 'キャンセル',
                'btn_cancel'           => 'キャンセル',
                'edit'                 => '編集',
                'delete'               => '削除',
                'confirm'              => '確認',
                'btn_confirm'          => '確認',
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
                'page_qc_edit'         => '管理者 - QC検査情報編集',
                'page_winding'         => '巻取グループ - ボビン完了確認',
                'page_winding_edit'    => '管理者 - 巻取工程情報編集',
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
                'status_extruded'      => '押出済',
                'status_unchecked'     => 'QC前 (未検査)',
                'status_checked'       => 'QC済 (巻取待)',
                'status_pending_cancel' => '廃棄待ち',
                'status_cancelled'     => '廃棄済',
                'lang_name'            => '日本語',
                'flag'                 => '🇯🇵',
                'short'                => 'JA',
                'pipeline_no_ext_data' => '⏳ 押出製造データなし',
                'pipeline_no_qc_data'  => '⏳ QC検査データなし',
                'pipeline_no_winding_data' => '⏳ 巻取情報データなし',

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
                'emp_msg_imported'     => 'Excelの一括取込が正常に完了しました！',
                'emp_chart_title'      => '役職分布',
                'emp_chart_no_data'    => 'データなし',
                'emp_active_count'     => '在職中',
                'emp_inactive_count'   => '退職/ロック',
                'emp_showing'          => '表示中',
                'emp_of'               => 'のうち',
                'emp_staff'            => '名',
                'emp_search_ph'        => '社員番号または氏名で検索...',
                'emp_all_roles'        => '-- すべての役職 --',
                'emp_all_status'       => '-- すべての状態 --',
                'emp_notice_title'     => '自動設定ルール',
                'emp_notice_username'  => 'ログインIDは社員番号に自動設定',
                'emp_notice_password'  => '初期パスワードは123（初回ログイン時に変更必須）',
                'emp_code_ph'          => '例：02619486 または EMP01',
                'emp_name_ph'          => '例：田中太郎',
                'emp_code_hint'        => '工場内の固有の個人識別コード。',
                'emp_role_hint'        => '従業員の所属部門または役職を選択。',
                'emp_self_badge'       => 'あなた',
                'emp_updated_prefix'   => '更新:',
                'emp_new_badge'        => '新規追加',
                'emp_profile_label'    => '社員番号（ログインID）：',
                'emp_reset_target'     => '再発行対象：',
                'emp_reset_desc_title' => '社内セキュリティ手順：',
                'emp_reset_desc_body'  => 'アカウントのパスワードがデフォルト（123）にリセットされます。次回ログイン後、直ちに新しいパスワードへの変更が必須となります。',
                'emp_reset_custom_label' => '⚙️ カスタムパスワードの設定（任意）',
                'emp_reset_custom_ph'  => '空白 = デフォルト123',
                'emp_reset_btn'        => '⚡ 123にリセット',
                'emp_import_title'     => '従業員Excel/CSVファイルを選択',
                'emp_import_sub'       => 'Microsoft ExcelからエクスポートされたCSV（UTF-8）に対応',
                'emp_import_update'    => '社員番号が既に存在する場合は情報を更新',
                'emp_import_col_title' => '📌 ファイルの標準列構造：',
                'emp_import_col1'      => '列1：社員番号（必須）',
                'emp_import_col2'      => '列2：氏名（必須）',
                'emp_import_col3'      => '列3：役職（extrusion/qc/winding/admin）',
                'emp_import_col4'      => '列4：ログインID（省略可）',
                'emp_import_col5'      => '列5：状態（1：在職中、0：ロック）',
                'emp_import_download'  => 'ひな形ファイルをダウンロード（従業員インポートひな形.csv）',
                'emp_import_start'     => '🚀 インポート開始',
                'emp_delete_confirm_full' => 'この従業員をシステムから削除してもよろしいですか？この操作は元に戻せません！',
                'emp_msg_success'      => '✅',
                'emp_msg_error'        => '❌',
                // Chart & Analytics
                'chart_empty'          => '空き',
                'chart_bobin_count'    => 'ボビン数量',
                'chart_status_stats'   => 'ボビン状態別統計',
                'chart_history_status_stats' => 'ボビン更新履歴統計',
                'chart_ratio'          => '割合',
                'chart_status_distribution' => 'ボビン状態別分布',
                // Section & Card Titles
                'sec_bobin_identification' => 'ボビン識別・担当者',
                'sec_bobin_identification_desc' => 'QRスキャンまたはボビン番号と担当者情報を入力',
                'sec_production_info'  => '製造情報',
                'sec_production_info_desc' => '製品コード・長さ・印字ロット',
                'sec_time_and_rack'    => '押出日時・ラック位置',
                'sec_time_and_rack_desc' => 'ボビン完了日時とラック保管位置',
                'sec_ext_check1'       => '押出工程1次検査',
                'sec_ext_check1_desc'  => 'QC検査前の初期品質確認',
                'sec_ext_visual_check' => '押出工程外観自己検査',
                // Labels & Units
                'lbl_bobin_id_code'    => 'ボビン識別番号',
                'lbl_bobin_size'       => 'ボビンサイズ',
                'lbl_ext_emp_code'     => '社員番号',
                'lbl_ext_emp_name'     => '氏名',
                'lbl_machine_no'       => '押出機番号',
                'lbl_material_type'    => '原材料',
                'lbl_grind_time'       => '粉砕回数',
                'lbl_rack_location'    => 'ラック位置',
                'lbl_ext_date'         => '押出日',
                'lbl_finish_time'      => '完了日時',
                'lbl_manual_time'      => '手動時間選択',
                'unit_meter'           => 'm',
                'chk_diameter'         => '外径',
                'chk_gel'              => 'ゲル',
                'chk_foreign_object'   => '異物',
                'chk_color'            => '色調',
                'chk_print'            => '印字',
                'btn_refresh'          => 'リフレッシュ',
                'btn_scan_qr'          => 'QRスキャン',
                // Placeholders
                'ph_scan_bobin'        => 'ボビン番号を入力またはスキャン...',
                'ph_auto_size'         => 'ボビン番号から自動',
                'ph_search_product'    => '製品コードを検索...',
                'ph_select_machine'    => '押出機を選択...',
                'ph_select_material'   => '材料を選択...',
                'ph_grind_hint'        => '0, 1または2を入力',
                'ph_enter_material_lot' => '材料ロットを入力...',
                'ph_auto_printlot'     => '印字ロット自動生成',
                'ph_select_rack'       => 'ラック位置を選択...',
                // Pagination
                'page_prev'            => '‹ 前へ',
                'page_next'            => '次へ ›',
                'page_info'            => 'ページ :current / :total (全 :records ボビン)'
            ]
        ];

        // Gộp bản dịch tùy chỉnh nếu có cấu hình custom_translations.json
        $custom = self::getCustomDictionary();
        if (!empty($custom)) {
            foreach (['vi', 'en', 'ja'] as $lang) {
                if (!empty($custom[$lang]) && is_array($custom[$lang])) {
                    foreach ($custom[$lang] as $k => $v) {
                        self::$dictionary[$lang][$k] = (string)$v;
                        if ($k === 'btn_cancel' && !isset($custom[$lang]['cancel'])) {
                            self::$dictionary[$lang]['cancel'] = (string)$v;
                        }
                        if ($k === 'btn_confirm' && !isset($custom[$lang]['confirm'])) {
                            self::$dictionary[$lang]['confirm'] = (string)$v;
                        }
                        if ($k === 'cancel' && !isset($custom[$lang]['btn_cancel'])) {
                            self::$dictionary[$lang]['btn_cancel'] = (string)$v;
                        }
                        if ($k === 'confirm' && !isset($custom[$lang]['btn_confirm'])) {
                            self::$dictionary[$lang]['btn_confirm'] = (string)$v;
                        }
                    }
                }
            }
        }
    }
}

if (!function_exists('__')) {
    function __(string $key, ?string $default = null): string
    {
        return Language::get($key, $default);
    }
}
