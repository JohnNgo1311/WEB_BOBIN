<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Login</title>
    <link rel="stylesheet" href="/WEB_BOBIN/public/assets/css/login.css">
    <link rel="icon" href="data:,">

</head>

<body>

    <div class="login-container">
        <img src="/WEB_BOBIN/public/assets/images/smcLogo.png" alt="SMC Logo" class="login-logo">
        <h1>ĐĂNG NHẬP</h1>
        <form method="POST" action="/WEB_BOBIN/public/index.php?url=auth/validateLogin">
            <label>Tên đăng nhập</label>
            <input type="text" name="username" required>
            <label>Mật khẩu</label>
            <input type="password" name="password" required>
            <button type="submit">Đăng nhập</button>
        </form>

    </div>

</body>

</html>