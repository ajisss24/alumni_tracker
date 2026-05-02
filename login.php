<?php
require_once 'db.php';

if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: index.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ?");
    $stmt->execute([$username]);
    $admin = $stmt->fetch();
    if ($admin && password_verify($password, $admin['password_hash'])) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_username'] = $admin['username'];
        $_SESSION['admin_name'] = $admin['nama_lengkap'] ?? 'Admin';
        header('Location: index.php');
        exit;
    } else {
        $error = 'Username atau password salah!';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Alumni Tracker Pro</title>
    <meta name="description" content="Sistem Pelacakan Alumni - Login untuk mengakses dashboard pelacakan alumni.">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body style="display:block;">
    <div class="login-wrapper">
        <div class="login-card">
            <div class="login-brand">
                <div class="icon-wrap">
                    <i class="fas fa-graduation-cap"></i>
                </div>
                <h1>Alumni Tracker Pro</h1>
                <p>Sistem Pelacakan Alumni Terintegrasi</p>
            </div>

            <div class="disclaimer">
                <i class="fas fa-shield-halved"></i>
                <span>Semua data dalam sistem ini adalah untuk <strong>kepentingan pembelajaran</strong>. Dilarang keras menyebarkan data untuk kepentingan apapun di luar tujuan akademis.</span>
            </div>

            <?php if($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?= $error ?>
            </div>
            <?php endif; ?>

            <form action="" method="POST" id="loginForm">
                <div class="form-group">
                    <label for="username"><i class="fas fa-user"></i> Username</label>
                    <input type="text" name="username" id="username" class="form-control" placeholder="Masukkan username" required autofocus>
                </div>
                <div class="form-group" style="position:relative;">
                    <label for="password"><i class="fas fa-lock"></i> Password</label>
                    <input type="password" name="password" id="password" class="form-control" placeholder="Masukkan password" required>
                    <button type="button" onclick="togglePw()" style="position:absolute;right:0.75rem;top:2.25rem;background:none;border:none;color:var(--text-muted);cursor:pointer;font-size:0.9rem;">
                        <i class="fas fa-eye" id="pwIcon"></i>
                    </button>
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%;padding:0.875rem;margin-top:0.5rem;">
                    Masuk ke Dashboard <i class="fas fa-arrow-right"></i>
                </button>
            </form>

            <div style="text-align:center;margin-top:2rem;">
                <p class="text-xs text-muted">
                    &copy; 2026 Alumni Tracker Pro — Daily Project<br>
                    Rekayasa Kebutuhan Perangkat Lunak
                </p>
            </div>
        </div>
    </div>
    <script>
    function togglePw() {
        const p = document.getElementById('password');
        const i = document.getElementById('pwIcon');
        if (p.type === 'password') { p.type = 'text'; i.className = 'fas fa-eye-slash'; }
        else { p.type = 'password'; i.className = 'fas fa-eye'; }
    }
    </script>
</body>
</html>
