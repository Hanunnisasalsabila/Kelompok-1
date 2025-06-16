<?php
// Selalu mulai session di baris paling atas
session_start();
require_once 'db_connection.php'; // Ini akan mengimpor variabel $conn

// ========================================================================
// 1. BLOK LOGIKA PHP (SERVER-SIDE)
// ========================================================================

// --- Pemeriksaan Sesi & Pengambilan Data Pengguna dari DATABASE ---
if (!isset($_SESSION['isLoggedIn']) || $_SESSION['isLoggedIn'] !== true || !isset($_SESSION['userId'])) {
    header('Location: login.php');
    exit;
}

// Ambil data pengguna saat ini dari DATABASE berdasarkan ID yang tersimpan di session
$user_id = $_SESSION['userId'];
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$current_user = $result->fetch_assoc();
$stmt->close();

// Jika karena alasan tertentu data pengguna tidak ada di DB, hancurkan sesi dan paksa login ulang
if (!$current_user) {
    session_destroy();
    header('Location: login.php');
    exit;
}

$message = ''; // Variabel untuk notifikasi

// --- Penanganan Aksi Logout ---
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_destroy();
    header('Location: login.php');
    exit;
}

// --- Penanganan Aksi (Router untuk POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    switch ($action) {
        case 'update_profile':
            // Ambil data dari form
            $fullname = $_POST['fullname'];
            $phone = $_POST['phone'];
            $dob = $_POST['dob'];
            $gender = $_POST['gender'];
            
            // Siapkan query UPDATE untuk profil
            $stmt_update = $conn->prepare("UPDATE users SET fullname = ?, phone = ?, date_of_birth = ?, gender = ? WHERE id = ?");
            $stmt_update->bind_param("ssssi", $fullname, $phone, $dob, $gender, $user_id);
            
            if ($stmt_update->execute()) {
                 header('Location: account.php?section=profile&msg=Profil berhasil diperbarui.');
            } else {
                 header('Location: account.php?section=profile&msg=Gagal memperbarui profil.');
            }
            $stmt_update->close();
            exit;

        case 'change_password':
            $current_password_form = $_POST['current_password'];
            $new_password = $_POST['new_password'];
            $confirm_password = $_POST['confirm_password'];

            // Verifikasi password saat ini dengan yang ada di database
            if (!password_verify($current_password_form, $current_user['password'])) {
                $message = "Password saat ini salah.";
            } elseif ($new_password !== $confirm_password) {
                $message = "Konfirmasi password baru tidak cocok.";
            } else {
                // Hash password baru
                $new_hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                
                // Update password di database
                $stmt_pass = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt_pass->bind_param("si", $new_hashed_password, $user_id);
                
                if ($stmt_pass->execute()) {
                    session_destroy(); // Paksa login ulang untuk keamanan
                    header('Location: login.php?msg=Password berhasil diubah. Silakan login kembali.');
                    exit;
                } else {
                    $message = "Gagal mengubah password.";
                }
                $stmt_pass->close();
            }
            break;
            
        // Catatan: Logika untuk alamat, pembayaran, foto profil, dan pesanan
        // perlu dibuatkan tabel baru di database dan kodenya disesuaikan.
        // Untuk saat ini, fungsi tersebut saya non-aktifkan agar halaman bisa berjalan.
    }
}


// Ambil pesan dari URL untuk ditampilkan
if (isset($_GET['msg'])) {
    $message = htmlspecialchars($_GET['msg']);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Akun Saya | LUXE</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Montserrat:wght@400;500&display=swap">
    <style>
        :root { --primary-color: #333; --secondary-color: #6c757d; --accent-color: #c5a47e; --light-color: #f8f9fa; --border-color: #dee2e6; }
        body { font-family: 'Montserrat', sans-serif; background-color: var(--light-color); }
        .account-page-container { display: flex; max-width: 1400px; margin: 20px auto; background-color: white; box-shadow: 0 0 15px rgba(0,0,0,0.07); border-radius: 8px; overflow: hidden; }
        .account-sidebar { width: 280px; padding: 20px; border-right: 1px solid var(--border-color); background-color: #fdfdfd; }
        .account-sidebar h2 { font-family: 'Playfair Display', serif; }
        .account-nav .list-group-item { border: none; padding: 12px 20px; font-weight: 500; transition: all 0.3s ease; border-radius: 6px; margin-bottom: 5px; display: flex; align-items: center; cursor: pointer; }
        .account-nav .list-group-item i { width: 20px; text-align: center; margin-right: 15px; color: var(--secondary-color); }
        .account-nav .list-group-item:hover, .account-nav .list-group-item.active { background-color: var(--accent-color); color: #fff; }
        .account-nav .list-group-item:hover i, .account-nav .list-group-item.active i { color: #fff; }
        .account-nav .list-group-item.text-danger:hover { background-color: rgba(220, 53, 69, 0.1); }
        .account-content { flex: 1; padding: 30px 40px; }
        .section { display: none; animation: fadeIn 0.5s; } .section.active { display: block; }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        .btn-primary { background-color: var(--accent-color); border-color: var(--accent-color); }
        .profile-image-container { width: 120px; height: 120px; border-radius: 50%; border: 2px solid var(--border-color); overflow: hidden; position: relative; }
        .profile-image-container img { width: 100%; height: 100%; object-fit: cover; }
        .custom-message-box-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); display: flex; justify-content: center; align-items: center; z-index: 1060; opacity: 0; visibility: hidden; transition: opacity 0.3s; }
        .custom-message-box-overlay.active { opacity: 1; visibility: visible; }
        .custom-message-box-content { background: white; padding: 25px; border-radius: 8px; text-align: center; max-width: 400px; transform: translateY(-50px); transition: transform 0.3s; }
        .custom-message-box-overlay.active .custom-message-box-content { transform: translateY(0); }
        .custom-message-box-content button { background-color: var(--accent-color); color: white; border:none; padding: 8px 15px; border-radius: 5px; }
    </style>
</head>
<body>
<header class="bg-white shadow-sm py-3">
    <div class="container d-flex justify-content-between align-items-center">
        <a href="index.php" class="navbar-brand h1 mb-0" style="font-family: 'Playfair Display', serif;">LUXE</a>
        <nav>
            <ul class="nav">
                <li class="nav-item"><a class="nav-link text-dark" href="index.php">Home</a></li>
                <li class="nav-item"><a class="nav-link text-dark" href="checkout.php">Order</a></li>
                <li class="nav-item"><a class="nav-link text-dark" href="login.php">Login</a></li>
            </ul>
        </nav>
    </div>
</header>

    <div id="customMessageBoxOverlay" class="custom-message-box-overlay"><div class="custom-message-box-content"><p id="customMessageBoxText"></p><button id="customMessageBoxOkBtn">OK</button></div></div>

    <div class="container account-page-container mt-4">
        <div class="account-sidebar">
            <h2>Akun Saya</h2>
            <div class="list-group account-nav">
                <a onclick="showSection('profile')" class="list-group-item"><i class="fas fa-user-circle"></i> Profil Saya</a>
                <a onclick="showSection('addresses')" class="list-group-item"><i class="fas fa-map-marker-alt"></i> Alamat Saya (segera)</a>
                <a onclick="showSection('payments')" class="list-group-item"><i class="fas fa-credit-card"></i> Pembayaran (segera)</a>
                <a onclick="showSection('orders')" class="list-group-item"><i class="fas fa-box-open"></i> Pesanan Saya (segera)</a>
                <a onclick="showSection('change-password')" class="list-group-item"><i class="fas fa-lock"></i> Ganti Password</a>
                <a href="account.php?action=logout" class="list-group-item text-danger mt-3"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>

        <div class="account-content">
            <!-- Bagian Profil -->
            <div id="profile" class="section">
                <h3>Profil Saya</h3>
                <p>Kelola informasi profil Anda untuk mengontrol, melindungi, dan mengamankan akun.</p>
                <hr>
                <div class="row">
                    <div class="col-md-8">
                        <table class="table table-borderless">
                            <tr>
                                <td style="width: 150px;"><strong>Username</strong></td>
                                <td>: <?php echo htmlspecialchars($current_user['username']); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Nama Lengkap</strong></td>
                                <td>: <?php echo htmlspecialchars($current_user['fullname']); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Email</strong></td>
                                <td>: <?php echo htmlspecialchars($current_user['email']); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Telepon</strong></td>
                                <td>: <?php echo htmlspecialchars($current_user['phone'] ?: 'Belum diatur'); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Tanggal Lahir</strong></td>
                                <td>: <?php echo htmlspecialchars($current_user['date_of_birth'] ?: 'Belum diatur'); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Jenis Kelamin</strong></td>
                                <td>: <?php echo htmlspecialchars($current_user['gender'] ?: 'Belum diatur'); ?></td>
                            </tr>
                        </table>
                        <button class="btn btn-primary mt-2" data-bs-toggle="modal" data-bs-target="#profileModal">Edit Profil</button>
                    </div>
                    <div class="col-md-4 text-center">
                        <div class="profile-image-container mx-auto mb-3">
                            <img src="<?php echo htmlspecialchars($current_user['profile_picture_url'] ?? 'https://placehold.co/120x120/E8D4B7/333?text=Foto'); ?>" alt="Foto Profil">
                        </div>
                        <small class="text-muted">Fitur ganti foto akan datang!</small>
                    </div>
                </div>
            </div>

            <!-- Bagian Ganti Password -->
            <div id="change-password" class="section">
                <h3>Ganti Password</h3>
                 <p>Untuk keamanan akun Anda, mohon untuk tidak menyebarkan password Anda ke orang lain.</p>
                <hr>
                <form method="POST" action="account.php" class="mt-3" style="max-width: 500px;">
                    <input type="hidden" name="action" value="change_password">
                    <div class="mb-3">
                        <label for="current_password" class="form-label">Password Saat Ini</label>
                        <input type="password" id="current_password" name="current_password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="new_password" class="form-label">Password Baru</label>
                        <input type="password" id="new_password" name="new_password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Konfirmasi Password Baru</label>
                        <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Simpan Password</button>
                </form>
            </div>
            
            <!-- Placeholder untuk fitur lainnya -->
            <div id="addresses" class="section"><h3 class="text-muted">Fitur Alamat akan segera hadir.</h3></div>
            <div id="payments" class="section"><h3 class="text-muted">Fitur Pembayaran akan segera hadir.</h3></div>
            <div id="orders" class="section"><h3 class="text-muted">Fitur Pesanan akan segera hadir.</h3></div>

        </div>
    </div>
    
    <!-- Modal Edit Profil -->
    <div class="modal fade" id="profileModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Profil</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" action="account.php">
                        <input type="hidden" name="action" value="update_profile">
                        <div class="mb-3">
                            <label class="form-label">Nama Lengkap</label>
                            <input type="text" name="fullname" class="form-control" value="<?php echo htmlspecialchars($current_user['fullname']); ?>">
                        </div>
                        <div class="mb-3">
                             <label class="form-label">Telepon</label>
                             <input type="tel" name="phone" class="form-control" value="<?php echo htmlspecialchars($current_user['phone']); ?>">
                        </div>
                        <div class="mb-3">
                             <label class="form-label">Tanggal Lahir</label>
                             <input type="date" name="dob" class="form-control" value="<?php echo htmlspecialchars($current_user['date_of_birth']); ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Jenis Kelamin</label>
                            <select name="gender" class="form-select">
                                <option value="" <?php if(empty($current_user['gender'])) echo 'selected'; ?>>- Pilih -</option>
                                <option value="Pria" <?php if($current_user['gender'] == 'Pria') echo 'selected'; ?>>Pria</option>
                                <option value="Wanita" <?php if($current_user['gender'] == 'Wanita') echo 'selected'; ?>>Wanita</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Simpan Perubahan</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        // --- Fungsi untuk menampilkan pesan notifikasi ---
        const serverMessage = "<?php echo addslashes($message); ?>";
        if (serverMessage) {
            showMessageBox(serverMessage);
        }

        // --- Fungsi untuk navigasi antar bagian (tab) ---
        const urlParams = new URLSearchParams(window.location.search);
        // Jika ada pesan ubah password, buka tab ganti password, jika tidak buka profil
        let defaultSection = 'profile';
        if (serverMessage.includes('Password')) {
             defaultSection = 'change-password';
        }
        const section = urlParams.get('section') || defaultSection;
        showSection(section);
    });

    // --- Fungsi UI Global ---
    function showSection(sectionId) {
        document.querySelectorAll('.section').forEach(s => s.classList.remove('active'));
        const activeSection = document.getElementById(sectionId);
        if (activeSection) {
            activeSection.classList.add('active');
        }

        document.querySelectorAll('.account-nav .list-group-item').forEach(item => {
            item.classList.remove('active');
            if(item.getAttribute('onclick') === `showSection('${sectionId}')`) {
                item.classList.add('active');
            }
        });
        
        // Update URL tanpa reload halaman
        const url = new URL(window.location);
        url.searchParams.set('section', sectionId);
        window.history.pushState({}, '', url);
    }

    function showMessageBox(message) {
        const overlay = document.getElementById('customMessageBoxOverlay');
        document.getElementById('customMessageBoxText').textContent = message;
        overlay.classList.add('active');
        document.getElementById('customMessageBoxOkBtn').onclick = () => overlay.classList.remove('active');
    }
    </script>
</body>
</html>
