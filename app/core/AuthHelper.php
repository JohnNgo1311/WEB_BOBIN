<?php
// File: app/core/AuthHelper.php
require_once ROOT_PATH . '/app/core/Database.php';

class AuthHelper
{
    private static bool $sessionSynced = false;

    /**
     * Danh mục định nghĩa toàn bộ quyền hạn trong hệ thống
     */
    public static function getAllPermissions(): array
    {
        return [
            'extrusion' => [
                'title_key' => 'perm_group_extrusion',
                'title_default' => 'Công đoạn Đùn (Extrusion)',
                'icon' => '🏭',
                'items' => [
                    'extrusion_create' => [
                        'name_key' => 'perm_extrusion_create',
                        'name_default' => 'Nhập thông số Đùn & Tạo Bobin',
                        'desc_key' => 'perm_extrusion_create_desc',
                        'desc_default' => 'Truy cập trang nhập liệu sản xuất đùn, khởi tạo mã Bobin mới',
                        'routes' => ['bobin/index', 'bobin/extrusion', 'bobin/createbobinview', 'bobin/createbobin']
                    ],
                    'extrusion_edit_cancel' => [
                        'name_key' => 'perm_extrusion_edit_cancel',
                        'name_default' => 'Điều chỉnh Đùn (Edit + Cancel): Chỉnh sửa & Đưa vào chờ hủy',
                        'desc_key' => 'perm_extrusion_edit_cancel_desc',
                        'desc_default' => 'Được chỉnh sửa Bobin, đưa Bobin vào danh sách chờ hủy và hủy Bobin theo quy trình hiện tại',
                        'routes' => ['bobin/extrusioneditbobinview', 'bobin/extrusionupdatebobin', 'bobin/extrusiondeletebobin']
                    ],
                    'extrusion_edit_only' => [
                        'name_key' => 'perm_extrusion_edit_only',
                        'name_default' => 'Điều chỉnh Đùn (Edit Only): Chỉ chỉnh sửa Bobin',
                        'desc_key' => 'perm_extrusion_edit_only_desc',
                        'desc_default' => 'Được chỉnh sửa Bobin nhưng không được đưa Bobin vào danh sách chờ hủy hoặc hủy Bobin',
                        'routes' => ['bobin/extrusioneditbobinview', 'bobin/extrusionupdatebobin']
                    ],
                ]
            ],
            'qc' => [
                'title_key' => 'perm_group_qc',
                'title_default' => 'Kiểm tra chất lượng (QC)',
                'icon' => '🛡️',
                'items' => [
                    'qc_check' => [
                        'name_key' => 'perm_qc_check',
                        'name_default' => 'Kiểm tra ngoại quan QC',
                        'desc_key' => 'perm_qc_check_desc',
                        'desc_default' => 'Xem danh sách chờ QC, xác nhận đạt/lỗi ngoại quan hoặc báo chờ hủy Bobin tại QC',
                        'routes' => ['bobin/listbobinview_qc', 'bobin/updateqcbobin', 'bobin/changebobintype', 'bobin/updateqcandchangetype', 'bobin/qccancelbobin']
                    ],
                    'qc_edit' => [
                        'name_key' => 'perm_qc_edit',
                        'name_default' => 'Điều chỉnh kết quả QC',
                        'desc_key' => 'perm_qc_edit_desc',
                        'desc_default' => 'Truy cập trang Điều chỉnh QC, sửa tiêu chuẩn kiểm tra hoặc báo hủy QC',
                        'routes' => ['bobin/qceditbobinview', 'bobin/updateqceditbobin', 'bobin/qccancelbobin']
                    ],
                ]
            ],
            'winding' => [
                'title_key' => 'perm_group_winding',
                'title_default' => 'Công đoạn Cuộn (Winding)',
                'icon' => '📍',
                'items' => [
                    'winding_confirm' => [
                        'name_key' => 'perm_winding_confirm',
                        'name_default' => 'Xác nhận thông tin Cuộn',
                        'desc_key' => 'perm_winding_confirm_desc',
                        'desc_default' => 'Xem danh sách chờ cuộn, nhập máy cuộn, test thông khí, hoàn tất hoặc báo chờ hủy',
                        'routes' => ['bobin/windingview', 'bobin/listbobinview_winding', 'bobin/updatewindingbobin', 'bobin/windingcancelbobin']
                    ],
                    'winding_edit' => [
                        'name_key' => 'perm_winding_edit',
                        'name_default' => 'Điều chỉnh thông tin Cuộn',
                        'desc_key' => 'perm_winding_edit_desc',
                        'desc_default' => 'Truy cập trang Điều chỉnh Cuộn, sửa thông số máy cuộn hoặc báo hủy',
                        'routes' => ['bobin/windingeditbobinview', 'bobin/updatewindingeditbobin', 'bobin/windingcancelbobin']
                    ],
                ]
            ],
            'monitoring' => [
                'title_key' => 'perm_group_monitoring',
                'title_default' => 'Theo dõi & Báo cáo',
                'icon' => '📊',
                'items' => [
                    'bobin_list' => [
                        'name_key' => 'perm_bobin_list',
                        'name_default' => 'Xem Danh sách Bobin chi tiết',
                        'desc_key' => 'perm_bobin_list_desc',
                        'desc_default' => 'Tra cứu danh sách Bobin tồn line, phân lọc theo vị trí và xuất Excel',
                        'routes' => ['bobin/listbobindetailview', 'bobin/exportdetailexcel', 'bobin/getspecificbobin']
                    ],
                    'bobin_history' => [
                        'name_key' => 'perm_bobin_history',
                        'name_default' => 'Xem Lịch sử luân chuyển Bobin',
                        'desc_key' => 'perm_bobin_history_desc',
                        'desc_default' => 'Tra cứu dòng thời gian và dấu vết lịch sử thay đổi của Bobin',
                        'routes' => ['bobin/listbobinhistoryview', 'bobin/exporthistoryexcel']
                    ],
                    'pending_cancel_edit' => [
                        'name_key' => 'perm_pending_cancel_edit',
                        'name_default' => 'Danh sách chờ Hủy: Được xem và được chỉnh sửa thông tin',
                        'desc_key' => 'perm_pending_cancel_edit_desc',
                        'desc_default' => 'Được xem danh sách Bobin chờ hủy và thực hiện khôi phục hoặc xác nhận hủy Bobin hoàn toàn',
                        'routes' => ['bobin/listpendingcancellationview', 'bobin/deletebobin', 'bobin/restorebobin']
                    ],
                    'pending_cancel_view' => [
                        'name_key' => 'perm_pending_cancel_view',
                        'name_default' => 'Danh sách chờ Hủy: Chỉ được xem nhưng không được chỉnh sửa',
                        'desc_key' => 'perm_pending_cancel_view_desc',
                        'desc_default' => 'Chỉ được xem danh sách Bobin chờ hủy, không có quyền khôi phục hoặc xác nhận hủy',
                        'routes' => ['bobin/listpendingcancellationview']
                    ],
                ]
            ],
            'system' => [
                'title_key' => 'perm_group_system',
                'title_default' => 'Quản trị hệ thống',
                'icon' => '⚙️',
                'items' => [
                    'employee_manage' => [
                        'name_key' => 'perm_employee_manage',
                        'name_default' => 'Quản lý Danh sách Nhân viên',
                        'desc_key' => 'perm_employee_manage_desc',
                        'desc_default' => 'Thêm mới, sửa, khóa tài khoản và import/export danh sách nhân viên',
                        'routes' => ['employee/index', 'employee/create', 'employee/update', 'employee/delete', 'employee/importcsv', 'employee/exportcsv', 'employee/importexcel', 'employee/exportexcel', 'employee/downloadtemplate', 'employee/resetpassword']
                    ],
                    'permission_manage' => [
                        'name_key' => 'perm_permission_manage',
                        'name_default' => 'Phân quyền Tài khoản Người dùng',
                        'desc_key' => 'perm_permission_manage_desc',
                        'desc_default' => 'Tra cứu nhân viên và thiết lập chi tiết các quyền thao tác cho từng user',
                        'routes' => ['employee/permissionsview', 'employee/getemployeepermissions', 'employee/updatepermissions', 'employee/translationsview', 'employee/updatetranslations', 'employee/resettranslations']
                    ],
                ]
            ]
        ];
    }

    /**
     * Trả về danh sách phẳng các mã quyền
     */
    public static function getAllPermissionKeys(): array
    {
        $keys = [];
        foreach (self::getAllPermissions() as $group) {
            foreach (array_keys($group['items']) as $permKey) {
                $keys[] = $permKey;
            }
        }
        return $keys;
    }

    /**
     * Quyền mặc định theo từng Role ban đầu
     */
    public static function getDefaultPermissionsForRole(string $role): array
    {
        $role = strtolower(trim($role));
        return match ($role) {
            'admin' => array_values(array_diff(self::getAllPermissionKeys(), ['pending_cancel_view', 'extrusion_edit_only'])),
            'extrusion' => [
                'extrusion_create',
                'extrusion_edit_cancel',
                'bobin_list',
                'bobin_history',
                'pending_cancel_view'
            ],
            'qc' => [
                'qc_check',
                'qc_edit',
                'bobin_list',
                'bobin_history',
                'pending_cancel_view'
            ],
            'winding' => [
                'winding_confirm',
                'winding_edit',
                'bobin_list',
                'bobin_history',
                'pending_cancel_view'
            ],
            default => [
                'bobin_list',
                'bobin_history',
                'pending_cancel_view'
            ]
        };
    }

    /**
     * Đồng bộ quyền mới nhất từ Database vào $_SESSION['user'] trong mỗi HTTP request
     */
    public static function ensureSessionPermissionsFresh(): void
    {
        if (self::$sessionSynced) {
            return;
        }
        self::$sessionSynced = true;

        if (empty($_SESSION['user']) || !empty($_SESSION['user']['_skip_db_sync'])) {
            return;
        }

        $empCode = $_SESSION['user']['employee_code'] ?? '';
        $empId   = $_SESSION['user']['id'] ?? null;
        if ($empCode === '' && empty($empId)) {
            return;
        }

        try {
            $pdo = Database::getInstance()->pdo();
            if (!empty($empId)) {
                $stmt = $pdo->prepare("SELECT role, permissions FROM employee_list WHERE id = ? LIMIT 1");
                $stmt->execute([(int)$empId]);
            } else {
                $stmt = $pdo->prepare("SELECT role, permissions FROM employee_list WHERE employee_code = ? LIMIT 1");
                $stmt->execute([$empCode]);
            }
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                $_SESSION['user']['role'] = strtolower(trim($row['role'] ?? $_SESSION['user']['role'] ?? ''));
                $perms = !empty($row['permissions']) ? json_decode($row['permissions'], true) : null;
                $_SESSION['user']['permissions'] = is_array($perms) ? $perms : null;
            }
        } catch (Throwable $e) {
            error_log("AuthHelper::ensureSessionPermissionsFresh error: " . $e->getMessage());
        }
    }

    /**
     * Lấy danh sách quyền thực tế của một user
     */
    public static function getUserPermissions(?array $user = null): array
    {
        if ($user === null) {
            self::ensureSessionPermissionsFresh();
            $user = $_SESSION['user'] ?? [];
        }

        if (empty($user)) {
            return [];
        }

        $role = strtolower(trim($user['role'] ?? ''));

        $rawPerms = $user['permissions'] ?? null;
        if (is_string($rawPerms)) {
            $decoded = json_decode($rawPerms, true);
            if (is_array($decoded)) {
                $rawPerms = $decoded;
            }
        }

        if (is_array($rawPerms) && !empty($rawPerms)) {
            // Tương thích ngược: chuyển đổi mã quyền cũ 'pending_cancel'
            if (in_array('pending_cancel', $rawPerms, true)) {
                $rawPerms = array_diff($rawPerms, ['pending_cancel']);
                $rawPerms[] = ($role === 'admin') ? 'pending_cancel_edit' : 'pending_cancel_view';
            }

            // Nếu có cả 2 quyền chờ hủy thì ưu tiên giữ quyền chỉnh sửa (pending_cancel_edit)
            if (in_array('pending_cancel_edit', $rawPerms, true) && in_array('pending_cancel_view', $rawPerms, true)) {
                $rawPerms = array_diff($rawPerms, ['pending_cancel_view']);
            }

            // Tương thích ngược: chuyển đổi mã quyền cũ 'extrusion_edit'
            if (in_array('extrusion_edit', $rawPerms, true)) {
                $rawPerms = array_diff($rawPerms, ['extrusion_edit']);
                $hasCancelAuth = ($role === 'admin' || $role === 'extrusion') || in_array('pending_cancel_edit', $rawPerms, true);
                $rawPerms[] = $hasCancelAuth ? 'extrusion_edit_cancel' : 'extrusion_edit_only';
            }

            // Nếu có cả 2 quyền Điều chỉnh Đùn thì ưu tiên giữ quyền cao hơn (extrusion_edit_cancel)
            if (in_array('extrusion_edit_cancel', $rawPerms, true) && in_array('extrusion_edit_only', $rawPerms, true)) {
                $rawPerms = array_diff($rawPerms, ['extrusion_edit_only']);
            }

            // Admin luôn giữ quyền quản lý phân quyền để tránh bị lock out
            if ($role === 'admin' && !in_array('permission_manage', $rawPerms, true)) {
                $rawPerms[] = 'permission_manage';
            }
            return array_values(array_unique($rawPerms));
        }

        return self::getDefaultPermissionsForRole($role);
    }

    /**
     * Kiểm tra user hiện tại có quyền cụ thể không
     */
    public static function hasPermission(string $permKey, ?array $user = null): bool
    {
        if ($user === null) {
            self::ensureSessionPermissionsFresh();
            $user = $_SESSION['user'] ?? [];
        }

        if (empty($user)) {
            return false;
        }

        $userPerms = self::getUserPermissions($user);

        // Quyền truy cập & chỉnh sửa chung tại trang Điều chỉnh Đùn (Edit Only hoặc Edit + Cancel)
        if ($permKey === 'extrusion_edit') {
            return in_array('extrusion_edit_only', $userPerms, true)
                || in_array('extrusion_edit_cancel', $userPerms, true)
                || in_array('extrusion_edit', $userPerms, true);
        }

        // Quyền chỉ chỉnh sửa tại trang Điều chỉnh Đùn (Edit Only - loại trừ với Edit + Cancel)
        if ($permKey === 'extrusion_edit_only') {
            return in_array('extrusion_edit_only', $userPerms, true);
        }

        // Quyền chỉnh sửa & đưa Bobin vào chờ hủy / hủy tại trang Điều chỉnh Đùn (Edit + Cancel)
        if ($permKey === 'extrusion_edit_cancel') {
            return in_array('extrusion_edit_cancel', $userPerms, true);
        }

        // Quyền xem danh sách chờ hủy: hợp lệ nếu có pending_cancel_view hoặc pending_cancel_edit
        if ($permKey === 'pending_cancel' || $permKey === 'pending_cancel_view') {
            return in_array('pending_cancel_view', $userPerms, true)
                || in_array('pending_cancel_edit', $userPerms, true)
                || in_array('pending_cancel', $userPerms, true);
        }

        // Quyền chỉnh sửa danh sách chờ hủy (khôi phục / xác nhận hủy hoàn toàn)
        if ($permKey === 'pending_cancel_edit') {
            return in_array('pending_cancel_edit', $userPerms, true);
        }

        return in_array($permKey, $userPerms, true);
    }

    /**
     * Kiểm tra controller / method có được phép truy cập bởi user không
     */
    public static function isActionAllowed(string $controller, string $method, ?array $user = null): bool
    {
        $controller = strtolower(trim($controller));
        $method = strtolower(trim($method));
        $route = "{$controller}/{$method}";

        if ($user === null) {
            self::ensureSessionPermissionsFresh();
            $user = $_SESSION['user'] ?? [];
        }

        if (empty($user)) {
            return false;
        }

        // Tìm tất cả các permission cho phép truy cập route này
        $matchingPerms = [];
        foreach (self::getAllPermissions() as $group) {
            foreach ($group['items'] as $permKey => $info) {
                if (in_array($route, $info['routes'], true)) {
                    $matchingPerms[] = $permKey;
                }
            }
        }

        // Nếu route không nằm trong danh mục kiểm soát đặc biệt, cho phép nếu đã đăng nhập
        if (empty($matchingPerms)) {
            return true;
        }

        foreach ($matchingPerms as $permKey) {
            if (self::hasPermission($permKey, $user)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Cập nhật lại session quyền nếu user đang chỉnh sửa chính là user đang đăng nhập
     */
    public static function syncCurrentSessionIfMatch(string $employeeCode): void
    {
        self::$sessionSynced = false;
        if (isset($_SESSION['user']['employee_code']) && $_SESSION['user']['employee_code'] === $employeeCode) {
            $pdo = Database::getInstance()->pdo();
            $stmt = $pdo->prepare("SELECT permissions, role FROM employee_list WHERE employee_code = ? LIMIT 1");
            $stmt->execute([$employeeCode]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                $_SESSION['user']['role'] = strtolower(trim($row['role'] ?? $_SESSION['user']['role'] ?? ''));
                $perms = !empty($row['permissions']) ? json_decode($row['permissions'], true) : null;
                $_SESSION['user']['permissions'] = $perms;
            }
        }
    }
}

