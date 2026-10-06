<?php
// File: app/core/AuthHelper.php
require_once ROOT_PATH . '/app/core/Database.php';

class AuthHelper
{
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
                    'extrusion_edit' => [
                        'name_key' => 'perm_extrusion_edit',
                        'name_default' => 'Điều chỉnh thông tin Bobin Đùn',
                        'desc_key' => 'perm_extrusion_edit_desc',
                        'desc_default' => 'Truy cập trang Điều chỉnh Đùn, cập nhật và xóa Bobin đùn',
                        'routes' => ['bobin/extrusioneditbobinview', 'bobin/extrusionupdatebobin', 'bobin/extrusiondeletebobin']
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
                        'desc_default' => 'Xem danh sách chờ QC, xác nhận đạt/lỗi ngoại quan Bobin',
                        'routes' => ['bobin/listbobinview_qc', 'bobin/updateqcbobin', 'bobin/changebobintype', 'bobin/updateqcandchangetype']
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
                        'desc_default' => 'Xem danh sách chờ cuộn, nhập máy cuộn, test thông khí & hoàn tất',
                        'routes' => ['bobin/windingview', 'bobin/listbobinview_winding', 'bobin/updatewindingbobin']
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
                    'pending_cancel' => [
                        'name_key' => 'perm_pending_cancel',
                        'name_default' => 'Quản lý Bobin chờ hủy',
                        'desc_key' => 'perm_pending_cancel_desc',
                        'desc_default' => 'Xem danh sách Bobin chờ duyệt hủy và thực hiện hủy/khôi phục',
                        'routes' => ['bobin/listpendingcancellationview', 'bobin/deletebobin']
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
                        'routes' => ['employee/index', 'employee/create', 'employee/update', 'employee/delete', 'employee/importcsv', 'employee/exportcsv', 'employee/resetpassword']
                    ],
                    'permission_manage' => [
                        'name_key' => 'perm_permission_manage',
                        'name_default' => 'Phân quyền Tài khoản Người dùng',
                        'desc_key' => 'perm_permission_manage_desc',
                        'desc_default' => 'Tra cứu nhân viên và thiết lập chi tiết các quyền thao tác cho từng user',
                        'routes' => ['employee/permissionsview', 'employee/getemployeepermissions', 'employee/updatepermissions']
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
            'admin' => self::getAllPermissionKeys(),
            'extrusion' => [
                'extrusion_create',
                'extrusion_edit',
                'bobin_list',
                'bobin_history',
                'pending_cancel'
            ],
            'qc' => [
                'qc_check',
                'qc_edit',
                'bobin_list',
                'bobin_history',
                'pending_cancel'
            ],
            'winding' => [
                'winding_confirm',
                'bobin_list',
                'bobin_history',
                'pending_cancel'
            ],
            default => [
                'bobin_list',
                'bobin_history',
                'pending_cancel'
            ]
        };
    }

    /**
     * Lấy danh sách quyền thực tế của một user
     */
    public static function getUserPermissions(?array $user = null): array
    {
        if ($user === null) {
            $user = $_SESSION['user'] ?? [];
        }

        if (empty($user)) {
            return [];
        }

        $role = strtolower($user['role'] ?? '');

        // Nếu là admin và không có giới hạn tùy biến nào, luôn cấp toàn quyền
        $rawPerms = $user['permissions'] ?? null;
        if (is_string($rawPerms)) {
            $decoded = json_decode($rawPerms, true);
            if (is_array($decoded)) {
                $rawPerms = $decoded;
            }
        }

        if (is_array($rawPerms) && !empty($rawPerms)) {
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
            $user = $_SESSION['user'] ?? [];
        }

        if (empty($user)) {
            return false;
        }

        $userPerms = self::getUserPermissions($user);
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
            $user = $_SESSION['user'] ?? [];
        }

        if (empty($user)) {
            return false;
        }

        // Tìm permission yêu cầu cho route này
        $requiredPerm = null;
        foreach (self::getAllPermissions() as $group) {
            foreach ($group['items'] as $permKey => $info) {
                if (in_array($route, $info['routes'], true)) {
                    $requiredPerm = $permKey;
                    break 2;
                }
            }
        }

        // Nếu route không nằm trong danh mục kiểm soát đặc biệt, cho phép nếu đã đăng nhập
        if ($requiredPerm === null) {
            return true;
        }

        return self::hasPermission($requiredPerm, $user);
    }

    /**
     * Cập nhật lại session quyền nếu user đang chỉnh sửa chính là user đang đăng nhập
     */
    public static function syncCurrentSessionIfMatch(string $employeeCode): void
    {
        if (isset($_SESSION['user']['employee_code']) && $_SESSION['user']['employee_code'] === $employeeCode) {
            $pdo = Database::getInstance()->pdo();
            $stmt = $pdo->prepare("SELECT permissions, role FROM employee_list WHERE employee_code = ? LIMIT 1");
            $stmt->execute([$employeeCode]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                $perms = !empty($row['permissions']) ? json_decode($row['permissions'], true) : null;
                $_SESSION['user']['permissions'] = $perms;
            }
        }
    }
}

