<?php
// Mulai session di baris paling atas
session_start();

// 1. HUBUNGKAN KE DATABASE
require_once 'db_connection.php'; // Mengimpor koneksi $conn

// Cek jika pengguna sudah login, alihkan ke halaman yang sesuai
if (isset($_SESSION['isLoggedIn']) && $_SESSION['isLoggedIn'] === true) {
    // Periksa role dari session untuk pengalihan yang benar (dibuat tidak case-sensitive)
    if (isset($_SESSION['userRole']) && strtolower($_SESSION['userRole']) === 'admin') {
        header('Location: dashboardadmin.php');
    } else {
        header('Location: account.php');
    }
    exit;
}

// Variabel untuk menyimpan pesan notifikasi
$message = '';

// ========================================================================
// LOGIKA PEMROSESAN FORMULIR (POST REQUEST)
// ========================================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // --- PENANGANAN REGISTRASI ---
    if (isset($_POST['register_action'])) {
        $fullname = trim($_POST['signup_fullname']);
        $username = trim($_POST['signup_username']);
        $email = trim($_POST['signup_email']);
        $password = $_POST['signup_password'];
        $confirmPassword = $_POST['signup_confirm_password'];

        if ($password !== $confirmPassword) {
            $message = 'Password dan Konfirmasi Password tidak cocok!';
        } elseif (empty($fullname) || empty($username) || empty($email) || empty($password)) {
            $message = 'Semua kolom wajib diisi!';
        } else {
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
            $stmt->bind_param("ss", $email, $username);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows > 0) {
                $message = 'Email atau Username sudah terdaftar!';
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt_insert = $conn->prepare("INSERT INTO users (fullname, username, email, password) VALUES (?, ?, ?, ?)");
                $stmt_insert->bind_param("ssss", $fullname, $username, $email, $hashed_password);

                if ($stmt_insert->execute()) {
                    $message = "Registrasi Berhasil! Silakan Login.";
                } else {
                    $message = "Terjadi kesalahan saat registrasi: " . $stmt_insert->error;
                }
                $stmt_insert->close();
            }
            $stmt->close();
        }
    }

    // --- PENANGANAN LOGIN ---
    if (isset($_POST['login_action'])) {
        $email = trim($_POST['login_email']);
        $password = $_POST['login_password'];

        $stmt = $conn->prepare("SELECT id, fullname, username, email, password, role FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

            if (password_verify($password, $user['password'])) {
                // Login Berhasil, simpan data ke session
                $_SESSION['isLoggedIn'] = true;
                $_SESSION['userId'] = $user['id'];
                $_SESSION['loggedInUserEmail'] = $user['email'];
                $_SESSION['loggedInUserName'] = $user['fullname'];
                $_SESSION['userRole'] = $user['role']; // Simpan role dari DB
                
                // PENTING: Alihkan berdasarkan role (dibuat tidak case-sensitive)
                if (strtolower($user['role']) === 'admin') {
                     header('Location: dashboardadmin.php');
                } else {
                     header('Location: account.php');
                }
                exit;

            } else {
                $message = 'Email atau Password salah!';
            }
        } else {
            $message = 'Email atau Password salah!';
        }
        $stmt->close();
    }
}

// Menutup koneksi jika tidak lagi digunakan
if ($conn) {
    $conn->close();
}
?>
<!DOCTYPE html>
<!-- [ SELURUH KODE HTML DAN JAVASCRIPT ANDA DI SINI, TIDAK PERLU DIUBAH ] -->
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | LUXE</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Montserrat:wght@300;400;500&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        :root {
            --primary-color: #333; --secondary-color: #777; --accent-color: #d4af37; --light-color: #f9f9f9; --border-color: #e1e1e1; --error-color: #e74c3c; --success-color: #2ecc71; --heading-font: 'Playfair Display', serif; --body-font: 'Montserrat', sans-serif;
        }
        .login-page { display: flex; min-height: 100vh; background-color: var(--light-color); font-family: var(--body-font); color: var(--primary-color); }
        .login-hero { flex: 1; background: url('assets/photo-1483985988355-763728e1935b.jpeg') no-repeat center center/cover; position: relative; display: flex; align-items: center; justify-content: center; color: white; text-shadow: 2px 2px 4px rgba(0,0,0,0.5); padding: 20px; box-sizing: border-box; }
        .login-hero-content { position: relative; z-index: 1; text-align: center; max-width: 500px; }
        .login-hero-overlay { position: absolute; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.4); z-index: 0; }
        .login-hero h1 { font-family: var(--heading-font); font-size: 4.5rem; margin-bottom: 15px; color: white; text-shadow: 2px 2px 6px rgba(0,0,0,0.8); }
        .login-hero p { font-size: 1.8rem; line-height: 1.6; color: rgba(255,255,255,0.9); text-shadow: 1px 1px 4px rgba(0,0,0,0.7); }
        .login-hero .btn { background-color: white; color: var(--primary-color); padding: 12px 25px; border-radius: 5px; text-decoration: none; font-weight: 600; transition: background-color 0.3s ease, color 0.3s ease, box-shadow 0.3s ease; display: inline-block; box-shadow: 0 4px 10px rgba(0,0,0,0.2); }
        .login-hero .btn:hover { background-color: var(--primary-color); color: white; box-shadow: 0 6px 15px rgba(0,0,0,0.3); }
        .login-container-custom { flex: 1; display: flex; justify-content: center; align-items: center; padding: 40px 20px; position: relative; z-index: 1; background-color: white; }
        .login-form-wrapper { background-color: white; padding: 40px; border-radius: 10px; box-shadow: 0 8px 25px rgba(0,0,0,0.1); width: 100%; max-width: 450px; position: relative; overflow: hidden; }
        .form-tabs { display: flex; margin-bottom: 30px; border-bottom: 1px solid var(--border-color); }
        .tab-button { flex: 1; padding: 15px 0; text-align: center; font-family: var(--heading-font); font-size: 1.8rem; font-weight: 600; color: var(--secondary-color); cursor: pointer; border: none; background: none; transition: color 0.3s ease, border-bottom-color 0.3s ease; border-bottom: 2px solid transparent; }
        .tab-button.active { color: var(--accent-color); border-bottom-color: var(--accent-color); }
        .login-form-tab { display: none; animation: fadeIn 0.5s ease-out; }
        .login-form-tab.active { display: block; }
        .form-tab-title { font-family: var(--heading-font); font-size: 2.2rem; margin-bottom: 25px; color: var(--primary-color); text-align: center; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-size: 1.5rem; color: var(--primary-color); font-weight: 500; }
        .form-group input[type="email"], .form-group input[type="password"], .form-group input[type="text"] { width: 100%; padding: 12px 15px; border: 1px solid var(--border-color); border-radius: 6px; font-size: 1.6rem; color: var(--primary-color); transition: border-color 0.3s, box-shadow 0.3s; box-sizing: border-box; }
        .form-group input:focus { outline: none; border-color: var(--accent-color); box-shadow: 0 0 0 3px rgba(218, 165, 32, 0.2); }
        .form-options { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
        .remember-me { display: flex; align-items: center; gap: 8px; color: var(--primary-color); font-size: 1.4rem; }
        .remember-me input[type="checkbox"] { margin-right: 0; accent-color: var(--accent-color); width: 1.8rem; height: 1.8rem; }
        .forgot-password { color: var(--secondary-color); text-decoration: none; transition: color 0.3s; font-size: 1.4rem; }
        .forgot-password:hover { color: var(--accent-color); }
        .login-btn { width: 100%; padding: 15px; background-color: var(--accent-color); color: white; border: none; border-radius: 8px; font-size: 1.8rem; font-weight: 600; cursor: pointer; transition: background-color 0.3s, box-shadow 0.3s; box-shadow: 0 4px 10px rgba(218, 165, 32, 0.3); }
        .login-btn:hover { background-color: #C09A1C; box-shadow: 0 6px 15px rgba(218, 165, 32, 0.4); }
        .message-box-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.6); display: flex; justify-content: center; align-items: center; opacity: 0; visibility: hidden; transition: opacity 0.3s, visibility 0.3s; z-index: 10000; }
        .message-box-overlay.active { opacity: 1; visibility: visible; }
        .message-box-content { background: white; padding: 25px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.2); text-align: center; font-family: var(--body-font); color: var(--primary-color); max-width: 450px; word-wrap: break-word; transform: translateY(-50px); transition: transform 0.3s; }
        .message-box-overlay.active .message-box-content { transform: translateY(0); }
        .message-box-content p { margin-bottom: 20px; font-size: 1.6rem; }
        .message-box-content button { padding: 10px 20px; background-color: var(--accent-color); color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 1.6rem; transition: background-color 0.3s; }
        .message-box-content button:hover { background-color: #C09A1C; }
    </style>
</head>
<body class="login-page">
    <div class="login-hero">
        <div class="login-hero-overlay"></div>
        <div class="login-hero-content">
            <h1>Welcome to LUXE</h1>
            <p>Your destination for elegant fashion and timeless style. Log in or create an account to discover our exclusive collections.</p>
            <a href="index.php" class="btn">Back to Home</a> 
        </div>
    </div>
    <div class="login-container-custom">
        <div class="login-form-wrapper">
            <div class="form-tabs">
                <button class="tab-button active" data-tab="login">Login</button>
                <button class="tab-button" data-tab="signup">Register</button>
            </div>
            <form id="login-form" class="login-form-tab active" method="POST" action="login.php">
                <h2 class="form-tab-title">Login to Your Account</h2>
                <input type="hidden" name="login_action" value="1">
                <div class="form-group">
                    <label for="login-email">Email</label>
                    <input type="email" id="login-email" name="login_email" placeholder="Enter your email" required>
                </div>
                <div class="form-group">
                    <label for="login-password">Password</label>
                    <input type="password" id="login-password" name="login_password" placeholder="Enter your password" required>
                </div>
                <div class="form-options">
                    <label class="remember-me">
                        <input type="checkbox" name="remember_me"> Remember Me
                    </label>
                    <a href="#" class="forgot-password">Forgot Password?</a>
                </div>
                <button type="submit" class="login-btn">Login</button>
            </form>
            <form id="signup-form" class="login-form-tab" method="POST" action="login.php">
                <h2 class="form-tab-title">Create an Account</h2>
                <input type="hidden" name="register_action" value="1">
                <div class="form-group">
                    <label for="signup-fullname">Full Name</label>
                    <input type="text" id="signup-fullname" name="signup_fullname" placeholder="Enter your full name" required>
                </div>
                <div class="form-group">
                    <label for="signup-username">Username</label>
                    <input type="text" id="signup-username" name="signup_username" placeholder="Choose a username" required>
                </div>
                <div class="form-group">
                    <label for="signup-email">Email</label>
                    <input type="email" id="signup-email" name="signup_email" placeholder="Enter your email" required>
                </div>
                <div class="form-group">
                    <label for="signup-password">Password</label>
                    <input type="password" id="signup-password" name="signup_password" placeholder="Create a password" required>
                </div>
                <div class="form-group">
                    <label for="signup-confirm-password">Confirm Password</label>
                    <input type="password" id="signup-confirm-password" name="signup_confirm_password" placeholder="Confirm your password" required>
                </div>
                <button type="submit" class="login-btn">Register</button>
            </form>
        </div>
    </div>
    <div id="message-box-overlay" class="message-box-overlay">
        <div class="message-box-content">
            <p id="message-box-text"></p>
            <button id="message-box-ok-btn">OK</button>
        </div>
    </div>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const loginTabBtn = document.querySelector('.tab-button[data-tab="login"]');
        const signupTabBtn = document.querySelector('.tab-button[data-tab="signup"]');
        const loginForm = document.getElementById('login-form');
        const signupForm = document.getElementById('signup-form');
        function switchTab(tab) {
            if (tab === 'login') {
                loginTabBtn.classList.add('active');
                signupTabBtn.classList.remove('active');
                loginForm.classList.add('active');
                signupForm.classList.remove('active');
            } else {
                signupTabBtn.classList.add('active');
                loginTabBtn.classList.remove('active');
                signupForm.classList.add('active');
                loginForm.classList.remove('active');
            }
        }
        loginTabBtn.addEventListener('click', () => switchTab('login'));
        signupTabBtn.addEventListener('click', () => switchTab('signup'));
        <?php if (!empty($message)): ?>
            function showMessageBox(message) {
                const msgBoxOverlay = document.getElementById('message-box-overlay');
                const msgBoxText = document.getElementById('message-box-text');
                const msgBoxOkBtn = document.getElementById('message-box-ok-btn');
                if (!msgBoxOverlay || !msgBoxText || !msgBoxOkBtn) { alert(message); return; }
                msgBoxText.textContent = message;
                msgBoxOverlay.classList.add('active');
                const closeBox = () => msgBoxOverlay.classList.remove('active');
                msgBoxOkBtn.onclick = closeBox;
                msgBoxOverlay.onclick = (e) => { if (e.target === msgBoxOverlay) closeBox(); };
            }
            showMessageBox("<?php echo addslashes($message); ?>");
            <?php if (isset($_POST['register_action'])): ?>
                <?php if (strpos($message, 'Registrasi Berhasil') !== false): ?>
                    switchTab('login');
                    document.getElementById('signup-form').reset();
                <?php else: ?>
                    switchTab('signup');
                <?php endif; ?>
            <?php endif; ?>
        <?php endif; ?>
    });
    </script>
</body>
</html>
