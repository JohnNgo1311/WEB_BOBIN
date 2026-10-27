<?php
// File: app/core/BobinImageHelper.php
require_once ROOT_PATH . '/app/core/AuthHelper.php';
require_once ROOT_PATH . '/app/core/Language.php';

class BobinImageHelper
{
    public const UPLOAD_SUBDIR = 'uploads/bobin_images';
    public const MAX_FILE_SIZE = 15728640; // 15 MB

    public const ALLOWED_MIMES = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        'image/gif'  => 'gif',
    ];

    public const STAGE_COLUMNS = [
        'extrusion' => 'extrusion_chart_image',
        'qc'        => 'qc_visual_image',
        'winding'   => 'winding_chart_image',
    ];

    public const STAGE_PREFIXES = [
        'extrusion' => 'ext',
        'qc'        => 'qc',
        'winding'   => 'wnd',
    ];

    /**
     * Cho phép cả PUT (JSON truyền thống - tương thích ngược) và POST (multipart/form-data khi gửi kèm file ảnh)
     */
    public static function isWriteMethodAllowed(): bool
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        return in_array($method, ['PUT', 'POST'], true);
    }

    /**
     * Đọc dữ liệu request thống nhất cho cả JSON body (PUT/POST) và FormData (POST multipart/form-data)
     */
    public static function parseRequestData(): array
    {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? ($_SERVER['HTTP_CONTENT_TYPE'] ?? '');
        $isMultipartOrForm = !empty($_POST)
            || !empty($_FILES)
            || stripos($contentType, 'multipart/form-data') !== false
            || stripos($contentType, 'application/x-www-form-urlencoded') !== false;

        if ($isMultipartOrForm) {
            $data = [];
            if (isset($_POST['payload_json']) && is_string($_POST['payload_json']) && trim($_POST['payload_json']) !== '') {
                $decodedPayload = json_decode($_POST['payload_json'], true);
                if (is_array($decodedPayload)) {
                    $data = $decodedPayload;
                }
            }

            foreach ($_POST as $key => $val) {
                if ($key === 'payload_json') {
                    continue;
                }
                if (is_string($val) && in_array($key, ['defects', 'cancel_errors'], true)) {
                    $trimmed = trim($val);
                    if ((str_starts_with($trimmed, '[') && str_ends_with($trimmed, ']'))
                        || (str_starts_with($trimmed, '{') && str_ends_with($trimmed, '}'))
                    ) {
                        $decodedField = json_decode($trimmed, true);
                        if (is_array($decodedField)) {
                            $data[$key] = $decodedField;
                            continue;
                        }
                    }
                }
                $data[$key] = $val;
            }
            return $data;
        }

        $raw = file_get_contents('php://input');
        if (is_string($raw) && trim($raw) !== '') {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }
        return [];
    }

    /**
     * Phân quyền nghiêm ngặt theo Nhóm mà user thuộc về (extrusion / qc / winding / admin)
     * kết hợp với quyền thao tác chi tiết trên AuthHelper.
     */
    public static function canManageStageImage(string $stage, string $mode = 'any', ?array $user = null): bool
    {
        $stage = strtolower(trim($stage));
        if (!isset(self::STAGE_COLUMNS[$stage])) {
            return false;
        }

        if ($user === null) {
            AuthHelper::ensureSessionPermissionsFresh();
            $user = $_SESSION['user'] ?? [];
        }

        if (empty($user)) {
            return false;
        }

        $role = strtolower(trim($user['role'] ?? ''));

        // Bắt buộc user phải thuộc đúng nhóm của loại hình ảnh đó (hoặc là admin)
        if ($role !== 'admin' && $role !== $stage) {
            return false;
        }

        return match ($stage) {
            'extrusion' => match ($mode) {
                'create' => AuthHelper::hasPermission('extrusion_create', $user),
                'edit'   => AuthHelper::hasPermission('extrusion_edit', $user),
                default  => AuthHelper::hasPermission('extrusion_create', $user) || AuthHelper::hasPermission('extrusion_edit', $user),
            },
            'qc' => match ($mode) {
                'check' => AuthHelper::hasPermission('qc_check', $user),
                'edit'  => AuthHelper::hasPermission('qc_edit', $user),
                default => AuthHelper::hasPermission('qc_check', $user) || AuthHelper::hasPermission('qc_edit', $user),
            },
            'winding' => match ($mode) {
                'confirm' => AuthHelper::hasPermission('winding_confirm', $user),
                'edit'    => AuthHelper::hasPermission('winding_edit', $user),
                default   => AuthHelper::hasPermission('winding_confirm', $user) || AuthHelper::hasPermission('winding_edit', $user),
            },
            default => false,
        };
    }

    /**
     * Ném ngoại lệ nếu user không thuộc nhóm hoặc không đủ quyền thao tác ảnh của công đoạn tương ứng
     */
    public static function assertStageImagePermission(string $stage, string $mode = 'any', ?array $user = null): void
    {
        if (!self::canManageStageImage($stage, $mode, $user)) {
            throw new Exception(__('err_img_permission_denied'));
        }
    }

    /**
     * Kiểm tra có file upload được gửi lên ở field tương ứng hay không
     */
    public static function hasIncomingUpload(string $fileField): bool
    {
        if (!isset($_FILES[$fileField]) || !is_array($_FILES[$fileField])) {
            return false;
        }
        $err = (int)($_FILES[$fileField]['error'] ?? UPLOAD_ERR_NO_FILE);
        return $err !== UPLOAD_ERR_NO_FILE;
    }

    /**
     * Xử lý lưu file hình ảnh được upload và trả về đường dẫn tương đối lưu DB (VD: uploads/bobin_images/BB_...)
     * Trả về null nếu người dùng không đính kèm ảnh.
     */
    public static function handleUploadedFile(string $fileField, string $stage, string $bobinCode = '', string $mode = 'any'): ?string
    {
        if (!self::hasIncomingUpload($fileField)) {
            return null;
        }

        // Bắt buộc kiểm tra phân quyền theo nhóm trước khi nhận file
        self::assertStageImagePermission($stage, $mode);

        $file = $_FILES[$fileField];
        $err = (int)($file['error'] ?? UPLOAD_ERR_NO_FILE);

        if ($err === UPLOAD_ERR_INI_SIZE || $err === UPLOAD_ERR_FORM_SIZE) {
            throw new Exception(__('err_img_too_large'));
        }
        if ($err !== UPLOAD_ERR_OK) {
            throw new Exception(__('err_img_upload_failed'));
        }

        $tmpName = (string)($file['tmp_name'] ?? '');
        $size = (int)($file['size'] ?? 0);

        if ($tmpName === '' || !is_file($tmpName) || $size <= 0) {
            throw new Exception(__('err_img_upload_failed'));
        }
        if ($size > self::MAX_FILE_SIZE) {
            throw new Exception(__('err_img_too_large'));
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = strtolower((string)$finfo->file($tmpName));
        if (!isset(self::ALLOWED_MIMES[$mime])) {
            throw new Exception(__('err_img_invalid_type'));
        }

        $imgInfo = @getimagesize($tmpName);
        if ($imgInfo === false || (int)($imgInfo[0] ?? 0) <= 0 || (int)($imgInfo[1] ?? 0) <= 0) {
            throw new Exception(__('err_img_invalid_type'));
        }

        $targetDir = ROOT_PATH . '/public/' . self::UPLOAD_SUBDIR;
        if (!is_dir($targetDir)) {
            if (!@mkdir($targetDir, 0775, true) && !is_dir($targetDir)) {
                throw new Exception(__('err_img_upload_failed'));
            }
        }

        $safeCode = preg_replace('/[^A-Za-z0-9_-]/', '', strtoupper(trim($bobinCode)));
        if ($safeCode === '') {
            $safeCode = 'BOBIN';
        }
        $prefix = self::STAGE_PREFIXES[$stage] ?? 'img';
        $ext = self::ALLOWED_MIMES[$mime];
        $rand = bin2hex(random_bytes(4));
        $fileName = "BB_{$safeCode}_{$prefix}_" . date('Ymd_His') . "_{$rand}.{$ext}";
        $destAbs = $targetDir . '/' . $fileName;

        $moved = is_uploaded_file($tmpName)
            ? @move_uploaded_file($tmpName, $destAbs)
            : (PHP_SAPI === 'cli' ? @copy($tmpName, $destAbs) : false);

        if (!$moved || !is_file($destAbs)) {
            throw new Exception(__('err_img_upload_failed'));
        }

        return self::UPLOAD_SUBDIR . '/' . $fileName;
    }

    /**
     * Giải quyết giá trị ảnh khi Điều chỉnh (keep | replace | delete)
     * @return array{changed: bool, final_path: ?string, old_path: ?string}
     */
    public static function resolveEditImageValue(
        string $stage,
        ?string $currentPath,
        string $imageAction,
        ?string $uploadedPath,
        string $mode = 'edit'
    ): array {
        $normCurrent = self::normalizeStoredPath($currentPath);
        $action = strtolower(trim($imageAction !== '' ? $imageAction : 'keep'));

        if ($uploadedPath !== null) {
            self::assertStageImagePermission($stage, $mode);
            return [
                'changed'    => ($normCurrent !== $uploadedPath),
                'final_path' => $uploadedPath,
                'old_path'   => ($normCurrent !== null && $normCurrent !== $uploadedPath) ? $normCurrent : null,
            ];
        }

        if ($action === 'delete' || $action === 'remove') {
            self::assertStageImagePermission($stage, $mode);
            return [
                'changed'    => ($normCurrent !== null),
                'final_path' => null,
                'old_path'   => $normCurrent,
            ];
        }

        return [
            'changed'    => false,
            'final_path' => $normCurrent,
            'old_path'   => null,
        ];
    }

    /**
     * Chuẩn hóa và kiểm tra an toàn đường dẫn tương đối lưu trong DB
     */
    public static function normalizeStoredPath(?string $relativePath): ?string
    {
        if ($relativePath === null) {
            return null;
        }
        $clean = ltrim(str_replace('\\', '/', trim($relativePath)), '/');
        if ($clean === '' || str_contains($clean, '..')) {
            return null;
        }
        if (!preg_match('#^uploads/bobin_images/[A-Za-z0-9._-]+$#', $clean)) {
            return null;
        }
        return $clean;
    }

    /**
     * Chuyển đường dẫn tương đối thành URL public (chỉ trả về khi file thực sự tồn tại trên ổ đĩa)
     */
    public static function toPublicUrl(?string $relativePath): string
    {
        $norm = self::normalizeStoredPath($relativePath);
        if ($norm === null) {
            return '';
        }
        $absPath = ROOT_PATH . '/public/' . $norm;
        if (!is_file($absPath)) {
            return '';
        }
        return '/WEB_BOBIN/public/' . $norm;
    }

    /**
     * Xóa file mới vừa upload nếu giao dịch DB thất bại/rollback
     */
    public static function cleanupUploadedFileOnError(?string $relativePath): void
    {
        $norm = self::normalizeStoredPath($relativePath);
        if ($norm === null) {
            return;
        }
        $absPath = ROOT_PATH . '/public/' . $norm;
        if (is_file($absPath)) {
            @unlink($absPath);
        }
    }

    /**
     * Xóa file vật lý cũ chỉ khi KHÔNG còn bất kỳ bản ghi nào trong bobin_list_detail và bobin_history tham chiếu tới nó
     */
    public static function deletePhysicalFileIfUnreferenced(PDO $pdo, ?string $relativePath): void
    {
        $norm = self::normalizeStoredPath($relativePath);
        if ($norm === null) {
            return;
        }

        try {
            $sql = "SELECT
                        (SELECT COUNT(*) FROM bobin_list_detail
                         WHERE extrusion_chart_image = :p1 OR qc_visual_image = :p2 OR winding_chart_image = :p3)
                        +
                        (SELECT COUNT(*) FROM bobin_history
                         WHERE extrusion_chart_image = :p4 OR qc_visual_image = :p5 OR winding_chart_image = :p6)
                    AS total_refs";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':p1' => $norm,
                ':p2' => $norm,
                ':p3' => $norm,
                ':p4' => $norm,
                ':p5' => $norm,
                ':p6' => $norm,
            ]);
            $refs = (int)$stmt->fetchColumn();
            if ($refs === 0) {
                $absPath = ROOT_PATH . '/public/' . $norm;
                if (is_file($absPath)) {
                    @unlink($absPath);
                }
            }
        } catch (Throwable $e) {
            error_log("BobinImageHelper::deletePhysicalFileIfUnreferenced error: " . $e->getMessage());
        }
    }

    /**
     * Render nút "Xem hình ảnh" (Deferred rendering - không tải sẵn ảnh trong danh sách)
     * Khi click mở Modal Dialog ở giữa màn hình.
     */
    public static function renderViewButton(
        string $stage,
        ?string $relativePath,
        string $bobinCode = '',
        string $extraClass = ''
    ): string {
        $stage = strtolower(trim($stage));
        $publicUrl = self::toPublicUrl($relativePath);
        $hasImage = ($publicUrl !== '');

        $stateClass = $hasImage ? 'has-image' : 'no-image';
        $classes = trim("btn-view-bobin-image stage-{$stage} {$stateClass} {$extraClass}");
        $safeUrl = htmlspecialchars($publicUrl, ENT_QUOTES, 'UTF-8');
        $safeStage = htmlspecialchars($stage, ENT_QUOTES, 'UTF-8');
        $safeCode = htmlspecialchars($bobinCode, ENT_QUOTES, 'UTF-8');
        $btnLabel = htmlspecialchars(__('btn_view_image'), ENT_QUOTES, 'UTF-8');

        $dotHtml = $hasImage ? '<span class="bip-has-img-dot" aria-hidden="true"></span>' : '';

        return '<button type="button" class="' . htmlspecialchars($classes, ENT_QUOTES, 'UTF-8') . '"'
            . ' data-bobin-view-image="1"'
            . ' data-image-url="' . $safeUrl . '"'
            . ' data-image-stage="' . $safeStage . '"'
            . ' data-bobin-code="' . $safeCode . '">'
            . '<svg class="bip-btn-icon" viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
            . '<rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>'
            . '<circle cx="8.5" cy="8.5" r="1.5"></circle>'
            . '<polyline points="21 15 16 10 5 21"></polyline>'
            . '</svg>'
            . '<span data-i18n="btn_view_image">' . $btnLabel . '</span>'
            . $dotHtml
            . '</button>';
    }

    /**
     * Render bộ điều khiển chọn/chụp/xem/xóa hình ảnh theo đúng nhóm phân quyền (extrusion | qc | winding)
     */
    public static function renderPickerWidget(
        string $stage,
        string $inputName,
        ?string $existingRelativePath = null,
        bool $locked = false,
        string $bobinCode = '',
        string $mode = 'any'
    ): string {
        $stage = strtolower(trim($stage));
        $canManage = self::canManageStageImage($stage, $mode);
        $publicUrl = self::toPublicUrl($existingRelativePath);
        $hasExisting = ($publicUrl !== '');

        [$labelKey, $descKey] = match ($stage) {
            'extrusion' => ['lbl_ext_chart_image', 'desc_ext_chart_image'],
            'qc'        => ['lbl_qc_visual_image', 'desc_qc_visual_image'],
            'winding'   => ['lbl_wnd_chart_image', 'desc_wnd_chart_image'],
            default     => ['lbl_ext_chart_image', 'desc_ext_chart_image'],
        };

        $safeStage = htmlspecialchars($stage, ENT_QUOTES, 'UTF-8');
        $safeInputName = htmlspecialchars($inputName, ENT_QUOTES, 'UTF-8');
        $safeActionName = htmlspecialchars("{$stage}_image_action", ENT_QUOTES, 'UTF-8');
        $safeUrl = htmlspecialchars($publicUrl, ENT_QUOTES, 'UTF-8');
        $safeCode = htmlspecialchars($bobinCode, ENT_QUOTES, 'UTF-8');

        $statusKey = $hasExisting ? 'img_status_attached' : 'img_status_none';
        $statusClass = $hasExisting ? 'is-attached' : 'is-empty';
        $lockedClass = $locked ? 'is-locked' : '';
        $disabledAttr = $locked ? 'disabled' : '';

        $html = '<div class="bobin-image-picker stage-' . $safeStage . ' ' . $lockedClass . '"'
            . ' data-bobin-image-picker="1"'
            . ' data-stage="' . $safeStage . '"'
            . ' data-input-name="' . $safeInputName . '"'
            . ' data-action-name="' . $safeActionName . '"'
            . ' data-initial-url="' . $safeUrl . '"'
            . ' data-current-url="' . $safeUrl . '"'
            . ' data-bobin-code="' . $safeCode . '"'
            . ' data-locked="' . ($locked ? '1' : '0') . '">';

        $html .= '<div class="bip-header">'
            . '<div class="bip-title-wrap">'
            . '<span class="bip-label" data-i18n="' . $labelKey . '">' . htmlspecialchars(__($labelKey), ENT_QUOTES, 'UTF-8') . '</span>'
            . '<span class="bip-optional-hint" data-i18n="' . $descKey . '">' . htmlspecialchars(__($descKey), ENT_QUOTES, 'UTF-8') . '</span>'
            . '</div>'
            . '<span class="bip-status-badge ' . $statusClass . '">'
            . '<span class="bip-status-text" data-i18n="' . $statusKey . '">' . htmlspecialchars(__($statusKey), ENT_QUOTES, 'UTF-8') . '</span>'
            . '</span>'
            . '</div>';

        $html .= '<div class="bip-file-name-row" style="' . ($hasExisting ? '' : 'display:none;') . '">'
            . '<span class="bip-file-name-text">' . ($hasExisting ? htmlspecialchars(basename($publicUrl), ENT_QUOTES, 'UTF-8') : '') . '</span>'
            . '</div>';

        $html .= '<div class="bip-actions">';

        if ($canManage) {
            $html .= '<input type="hidden" name="' . $safeActionName . '" class="bip-action-input" value="keep">'
                . '<input type="file" name="' . $safeInputName . '" class="bip-file-input" accept="image/jpeg,image/png,image/webp,image/gif" ' . $disabledAttr . ' hidden>'
                . '<input type="file" class="bip-camera-input" accept="image/*" capture="environment" ' . $disabledAttr . ' hidden>';

            // Nút chọn ảnh (Desktop: chọn mục hình ảnh / Điện thoại: thư viện hoặc tệp)
            $chooseKey = $hasExisting ? 'btn_change_image' : 'btn_choose_image';
            $html .= '<button type="button" class="bip-btn bip-btn-choose" ' . $disabledAttr . '>'
                . '<svg class="bip-btn-icon" viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
                . '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>'
                . '<polyline points="17 8 12 3 7 8"></polyline>'
                . '<line x1="12" y1="3" x2="12" y2="15"></line>'
                . '</svg>'
                . '<span class="bip-choose-text" data-i18n="' . $chooseKey . '">' . htmlspecialchars(__($chooseKey), ENT_QUOTES, 'UTF-8') . '</span>'
                . '</button>';

            // Nút chụp ảnh trực tiếp bằng camera (hữu ích cho điện thoại / tablet hiện trường)
            $html .= '<button type="button" class="bip-btn bip-btn-camera" ' . $disabledAttr . '>'
                . '<svg class="bip-btn-icon" viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
                . '<path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path>'
                . '<circle cx="12" cy="13" r="4"></circle>'
                . '</svg>'
                . '<span data-i18n="btn_take_photo">' . htmlspecialchars(__('btn_take_photo'), ENT_QUOTES, 'UTF-8') . '</span>'
                . '</button>';
        }

        // Nút Xem hình ảnh (luôn mở được để kiểm tra trong Modal Dialog giữa màn hình)
        $html .= '<button type="button" class="bip-btn bip-btn-preview ' . ($hasExisting ? 'has-image' : 'no-image') . '">'
            . '<svg class="bip-btn-icon" viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
            . '<rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>'
            . '<circle cx="8.5" cy="8.5" r="1.5"></circle>'
            . '<polyline points="21 15 16 10 5 21"></polyline>'
            . '</svg>'
            . '<span data-i18n="btn_view_image">' . htmlspecialchars(__('btn_view_image'), ENT_QUOTES, 'UTF-8') . '</span>'
            . '</button>';

        if ($canManage) {
            $html .= '<button type="button" class="bip-btn bip-btn-remove" style="' . ($hasExisting ? '' : 'display:none;') . '" ' . $disabledAttr . '>'
                . '<svg class="bip-btn-icon" viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
                . '<polyline points="3 6 5 6 21 6"></polyline>'
                . '<path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"></path>'
                . '<path d="M10 11v6"></path>'
                . '<path d="M14 11v6"></path>'
                . '</svg>'
                . '<span data-i18n="btn_remove_image">' . htmlspecialchars(__('btn_remove_image'), ENT_QUOTES, 'UTF-8') . '</span>'
                . '</button>';
        }

        $html .= '</div></div>';
        return $html;
    }
}

