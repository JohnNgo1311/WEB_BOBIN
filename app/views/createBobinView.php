<!doctype html>
<html lang="vi">

<head>
    <meta charset="utf-8">
    <title>Đăng ký Bobin</title>
    <link rel="stylesheet" href="/WEB_BOBIN/public/assets/css/create_bobin.css">
    <link rel="icon" href="data:,">

</head>

<body>
    <div class="create-container">
        <a href="/WEB_BOBIN/public/index.php?url=bobin/listBobinDetailView" class="back-link">
            ⬅️ Quay lại danh sách Bobin
        </a>
        <h1>Đăng ký Bobin</h1>

        <form id="registBobinForm">
            <div class="form-group">
                <label>Mã định danh Bobin</label>
                <input type="text" name="bobin_identification_code" placeholder="VD: BB29099" required>
            </div>
            <button type="submit">Xác nhận</button>
        </form>

    </div>

    <script>
        const API_BASE_URL = "<?= '/WEB_BOBIN/public/index.php?url=' ?>";
    </script>
    <script src="/WEB_BOBIN/public/assets/js/utils.js?v=<?= time() ?>"></script>
    <script src="/WEB_BOBIN/public/assets/js/Regist/submit.js?v=<?= time() ?>"></script>
</body>

</html>