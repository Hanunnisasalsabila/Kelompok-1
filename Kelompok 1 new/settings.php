<?php
session_start();
require_once 'db_connection.php'; // Ini akan mengimpor variabel $conn

// ... sisa kode Anda bisa menggunakan variabel $conn untuk query ...
// Contoh: $result = $conn->query("SELECT * FROM users");
// ========================================================================
// 1. BLOK LOGIKA & KEAMANAN PHP
// ========================================================================

// --- Pemeriksaan Keamanan: Pastikan hanya admin yang bisa mengakses ---
if (!isset($_SESSION['isLoggedIn']) || $_SESSION['userRole'] !== 'admin') {
    header('Location: login.php');
    exit;
}

// --- Konfigurasi dan Fungsi Pembantu ---
$settings_file = 'settings.json';
$users_file = 'users.json';

function get_json_data($file, $default_data = []) {
    if (!file_exists($file)) return $default_data;
    $data = json_decode(file_get_contents($file), true);
    return is_array($data) ? $data : $default_data;
}

function save_json_data($file, $data) {
    file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));
}

// --- Memuat Pengaturan & Data Pengguna ---
$default_settings = [
    'store' => ['name' => 'LUXE', 'email' => '', 'phone' => '', 'currency' => 'USD', 'address' => ''],
    'payment' => ['credit_card' => true, 'paypal' => true, 'apple_pay' => false, 'google_pay' => false],
    'shipping' => ['rate' => '5.99', 'free_threshold' => '100.00', 'countries' => ['US']]
];
$settings = get_json_data($settings_file, $default_settings);
$all_users = get_json_data($users_file, []);
$admin_user_index = array_search($_SESSION['loggedInUserEmail'], array_column($all_users, 'email'));
$admin_user = ($admin_user_index !== false) ? $all_users[$admin_user_index] : null;


// --- Penanganan Permintaan POST untuk Menyimpan Pengaturan ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    switch ($action) {
        case 'save_store':
            $settings['store']['name'] = $_POST['store_name'];
            $settings['store']['email'] = $_POST['store_email'];
            $settings['store']['phone'] = $_POST['store_phone'];
            $settings['store']['currency'] = $_POST['store_currency'];
            $settings['store']['address'] = $_POST['store_address'];
            save_json_data($settings_file, $settings);
            break;
            
        case 'save_payment':
            $settings['payment']['credit_card'] = isset($_POST['credit_card']);
            $settings['payment']['paypal'] = isset($_POST['paypal']);
            $settings['payment']['apple_pay'] = isset($_POST['apple_pay']);
            $settings['payment']['google_pay'] = isset($_POST['google_pay']);
            save_json_data($settings_file, $settings);
            break;

        case 'save_shipping':
            $settings['shipping']['rate'] = $_POST['shipping_rate'];
            $settings['shipping']['free_threshold'] = $_POST['free_shipping_threshold'];
            $settings['shipping']['countries'] = $_POST['shipping_countries'] ?? [];
            save_json_data($settings_file, $settings);
            break;
            
        case 'save_account':
            if ($admin_user_index !== false) {
                $all_users[$admin_user_index]['fullname'] = $_POST['admin_name'];
                $all_users[$admin_user_index]['email'] = $_POST['admin_email'];
                
                // Hanya update password jika diisi
                if (!empty($_POST['admin_password']) && $_POST['admin_password'] === $_POST['admin_password_confirm']) {
                    $all_users[$admin_user_index]['password'] = $_POST['admin_password'];
                }
                save_json_data($users_file, $all_users);
            }
            break;
    }
    
    // Alihkan untuk mencegah pengiriman ulang formulir, dengan pesan sukses
    header('Location: settings.php?status=saved');
    exit;
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan | LUXE ADMIN</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Montserrat:wght@300;400;500&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        /* [Salin semua CSS dari file HTML asli ke sini] */
        :root { --primary-color: #333; /* ...dan seterusnya... */ }
        .admin-container { display: grid; grid-template-columns: 250px 1fr; min-height: 100vh; }
        .admin-sidebar { background-color: var(--primary-color); color: #fff; padding: 2rem 0; }
        .admin-logo a { font-family: var(--font-heading); font-size: 2rem; color: #fff; text-decoration: none; }
        .admin-nav ul { list-style: none; padding: 0; }
        .admin-nav a { display: block; padding: 1rem 1.5rem; color: rgba(255, 255, 255, 0.8); text-decoration: none; }
        .admin-nav a.active, .admin-nav a:hover { color: #fff; background-color: rgba(255, 255, 255, 0.1); }
        .admin-nav i { width: 2rem; margin-right: 1rem; text-align: center; }
        .admin-main { padding: 2rem; background-color: #f8f8f8; }
        .settings-section { background-color: #fff; border-radius: 0.5rem; padding: 2rem; margin-bottom: 2rem; }
        .settings-section h2 { font-size: 1.8rem; margin-bottom: 2rem; border-bottom: 1px solid #ddd; padding-bottom: 1rem; }
        .form-group { margin-bottom: 1.5rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; font-weight: 500; }
        .form-control { width: 100%; padding: 1rem; border: 1px solid #ddd; border-radius: 0.3rem; }
        .btn { padding: 0.8rem 1.5rem; border-radius: 0.3rem; font-weight: 500; cursor: pointer; text-decoration: none; border: 1px solid #ddd; }
        .btn-primary { background-color: var(--primary-color, #333); color: #fff; border-color: var(--primary-color, #333); }
        .switch { position: relative; display: inline-block; width: 5rem; height: 2.4rem; }
        .switch input { opacity: 0; width: 0; height: 0; }
        .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #ccc; transition: .4s; border-radius: 2.4rem; }
        .slider:before { position: absolute; content: ""; height: 1.8rem; width: 1.8rem; left: 0.3rem; bottom: 0.3rem; background-color: white; transition: .4s; border-radius: 50%; }
        input:checked + .slider { background-color: var(--primary-color, #333); }
        input:checked + .slider:before { transform: translateX(2.6rem); }
        .toggle-group { display: flex; align-items: center; justify-content: space-between; padding: 1rem 0; }
        .toast { position: fixed; top: 2rem; right: 2rem; background-color: #4CAF50; color: white; padding: 1.5rem; border-radius: 0.5rem; z-index: 1000; display: none; animation: slideIn 0.5s, fadeOut 0.5s 2.5s forwards; }
        @keyframes slideIn { from {transform: translateX(100%);} to {transform: translateX(0);} }
        @keyframes fadeOut { from {opacity: 1;} to {opacity: 0;} }
    </style>
</head>
<body>
    <div class="admin-container">
        <aside class="admin-sidebar">
            <div class="admin-logo"><a href="index.php">LUXE ADMIN</a></div>
            <nav class="admin-nav">
                <ul>
                    <li><a href="dashboardadmin.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                    <li><a href="products.php"><i class="fas fa-shopping-bag"></i> Products</a></li>
                    <li><a href="orders.php"><i class="fas fa-receipt"></i> Orders</a></li>
                    <li><a href="settings.php" class="active"><i class="fas fa-cog"></i> Settings</a></li>
                    <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Log Out</a></li>
                </ul>
            </nav>
        </aside>
        
        <main class="admin-main">
            <header class="admin-header">
                <h1 class="admin-title">Settings</h1>
            </header>
            
            <div class="toast" id="toast"></div>

            <form method="POST" action="settings.php">
                <input type="hidden" name="action" value="save_store">
                <div class="settings-section">
                    <h2><i class="fas fa-store"></i> Pengaturan Toko</h2>
                    <div class="form-group"><label for="store-name">Nama Toko</label><input type="text" name="store_name" class="form-control" value="<?php echo htmlspecialchars($settings['store']['name']); ?>"></div>
                    <div class="form-group"><label for="store-email">Email Toko</label><input type="email" name="store_email" class="form-control" value="<?php echo htmlspecialchars($settings['store']['email']); ?>"></div>
                    <div class="form-group"><label for="store-phone">Telepon Toko</label><input type="tel" name="store_phone" class="form-control" value="<?php echo htmlspecialchars($settings['store']['phone']); ?>"></div>
                    <button type="submit" class="btn btn-primary">Simpan Pengaturan Toko</button>
                </div>
            </form>
            
            <form method="POST" action="settings.php">
                <input type="hidden" name="action" value="save_payment">
                <div class="settings-section">
                    <h2><i class="fas fa-credit-card"></i> Pengaturan Pembayaran</h2>
                    <div class="toggle-group"><span>Pembayaran Kartu Kredit</span><label class="switch"><input type="checkbox" name="credit_card" <?php if ($settings['payment']['credit_card']) echo 'checked'; ?>><span class="slider"></span></label></div>
                    <div class="toggle-group"><span>PayPal</span><label class="switch"><input type="checkbox" name="paypal" <?php if ($settings['payment']['paypal']) echo 'checked'; ?>><span class="slider"></span></label></div>
                    <button type="submit" class="btn btn-primary">Simpan Pengaturan Pembayaran</button>
                </div>
            </form>

            <?php if ($admin_user): // Hanya tampilkan jika admin user ditemukan ?>
            <form method="POST" action="settings.php">
                <input type="hidden" name="action" value="save_account">
                <div class="settings-section">
                    <h2><i class="fas fa-user-cog"></i> Pengaturan Akun</h2>
                    <div class="form-group"><label>Nama Admin</label><input type="text" name="admin_name" class="form-control" value="<?php echo htmlspecialchars($admin_user['fullname']); ?>"></div>
                    <div class="form-group"><label>Email Admin</label><input type="email" name="admin_email" class="form-control" value="<?php echo htmlspecialchars($admin_user['email']); ?>"></div>
                    <div class="form-group"><label>Ubah Password</label><input type="password" name="admin_password" class="form-control" placeholder="Kosongkan jika tidak ingin diubah"></div>
                    <div class="form-group"><label>Konfirmasi Password</label><input type="password" name="admin_password_confirm" class="form-control" placeholder="Konfirmasi password baru"></div>
                    <button type="submit" class="btn btn-primary">Simpan Pengaturan Akun</button>
                </div>
            </form>
            <?php endif; ?>

        </main>
    </div>

    <script>
        function showToast(message) {
            const toast = document.getElementById('toast');
            if (toast) {
                toast.textContent = message;
                toast.style.display = 'block';
                setTimeout(() => { toast.style.display = 'none'; }, 3000);
            }
        }

        // Tampilkan toast jika ada parameter status=saved di URL
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('status') === 'saved') {
                showToast('Pengaturan berhasil disimpan!');
                // Hapus parameter dari URL agar toast tidak muncul lagi saat refresh
                window.history.replaceState(null, null, window.location.pathname);
            }
        });
    </script>
</body>
</html>