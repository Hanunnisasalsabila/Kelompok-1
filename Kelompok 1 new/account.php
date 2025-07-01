<?php
// Selalu mulai session di baris paling atas
session_start();
require_once 'db_connection.php'; // Ini akan mengimpor variabel $conn

// ========================================================================
// 1. BLOK LOGIKA & KEAMANAN PHP
// ========================================================================

// --- Pemeriksaan Sesi & Pengambilan Data Pengguna dari DATABASE ---
if (!isset($_SESSION['isLoggedIn']) || $_SESSION['isLoggedIn'] !== true || !isset($_SESSION['userId'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['userId'];
$message = ''; // Variabel untuk notifikasi

// --- Penanganan Aksi (Router untuk POST dan GET) ---
$action = $_REQUEST['action'] ?? '';

// --- Aksi Logout, Hapus Alamat, Hapus Pembayaran ---
if ($action === 'logout') { 
    session_destroy(); 
    header('Location: login.php'); 
    exit; 
}
if (isset($_GET['id']) && $action === 'delete_address') {
    $address_id = (int)$_GET['id'];
    $stmt_delete = $conn->prepare("DELETE FROM user_addresses WHERE id = ? AND user_id = ?");
    $stmt_delete->bind_param("ii", $address_id, $user_id);
    $stmt_delete->execute();
    header('Location: account.php?section=addresses&msg=Alamat berhasil dihapus.');
    exit;
}
if (isset($_GET['id']) && $action === 'delete_payment') {
    $payment_id = (int)$_GET['id'];
    $stmt_delete = $conn->prepare("DELETE FROM user_payment_methods WHERE id = ? AND user_id = ?");
    $stmt_delete->bind_param("ii", $payment_id, $user_id);
    $stmt_delete->execute();
    header('Location: account.php?section=payment&msg=Metode pembayaran berhasil dihapus.');
    exit;
}


// --- Aksi Simpan & Update (via POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    switch ($action) {
        case 'update_profile':
            if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
                $target_dir = "uploads/profiles/";
                if (!is_dir($target_dir)) {
                    mkdir($target_dir, 0755, true);
                }
                
                $imageFileType = strtolower(pathinfo($_FILES["profile_image"]["name"], PATHINFO_EXTENSION));
                $new_filename = "user_" . $user_id . "_" . time() . "." . $imageFileType;
                $target_file = $target_dir . $new_filename;

                $check = getimagesize($_FILES["profile_image"]["tmp_name"]);
                if ($check !== false && in_array($imageFileType, ['jpg', 'png', 'jpeg', 'gif']) && $_FILES['profile_image']['size'] <= 2000000) {
                    if (move_uploaded_file($_FILES["profile_image"]["tmp_name"], $target_file)) {
                        $stmt_img = $conn->prepare("UPDATE users SET profile_image = ? WHERE id = ?");
                        $stmt_img->bind_param("si", $target_file, $user_id);
                        if ($stmt_img->execute()) {
                            $message = "Foto profil berhasil diperbarui.";
                        } else {
                            $message = "Gagal menyimpan path gambar ke database.";
                        }
                        $stmt_img->close();
                    } else {
                        $message = "Maaf, terjadi kesalahan saat mengunggah file.";
                    }
                } else {
                    $message = "File bukan gambar, format tidak didukung (JPG, PNG, GIF), atau ukuran melebihi 2MB.";
                }
            } else {
                $message = "Tidak ada file yang dipilih atau terjadi error saat upload.";
            }
            header('Location: account.php?section=profile&msg=' . urlencode($message));
            exit;

        case 'change_password':
            $current_password_form = $_POST['current_password'];
            $new_password = $_POST['new_password'];
            $confirm_password = $_POST['confirm_password'];

            if (!password_verify($current_password_form, $current_user['password'])) {
                $message = "Password saat ini salah.";
            } elseif ($new_password !== $confirm_password) {
                $message = "Konfirmasi password baru tidak cocok.";
            } else {
                $new_hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt_pass = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt_pass->bind_param("si", $new_hashed_password, $user_id);
                if ($stmt_pass->execute()) {
                    session_destroy();
                    header('Location: login.php?msg=Password berhasil diubah. Silakan login kembali.');
                    exit;
                }
                $stmt_pass->close();
            }
            break;

        case 'save_address':
            $address_id = $_POST['address_id'];
            $label = $_POST['label'];
            $recipient_name = $_POST['recipient_name'];
            $phone_number = $_POST['phone_number'];
            $province = $_POST['province'];
            $city = $_POST['city'];
            $postal_code = $_POST['postal_code'];
            $street_address = $_POST['street_address'];
            
            if (empty($address_id)) { 
                $stmt = $conn->prepare("INSERT INTO user_addresses (user_id, label, recipient_name, phone_number, province, city, postal_code, street_address) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("isssssss", $user_id, $label, $recipient_name, $phone_number, $province, $city, $postal_code, $street_address);
            } else { 
                $stmt = $conn->prepare("UPDATE user_addresses SET label=?, recipient_name=?, phone_number=?, province=?, city=?, postal_code=?, street_address=? WHERE id=? AND user_id=?");
                $stmt->bind_param("sssssssii", $label, $recipient_name, $phone_number, $province, $city, $postal_code, $street_address, $address_id, $user_id);
            }
            
            $stmt->execute();
            header('Location: account.php?section=addresses&msg=Alamat berhasil disimpan.');
            $stmt->close();
            exit;
            
        case 'save_payment':
            $payment_id = $_POST['payment_id'];
            $card_type = $_POST['card_type'];
            $cardholder_name = $_POST['cardholder_name'];
            $card_number = preg_replace('/[^0-9]/', '', $_POST['card_number']);
            $expiry_date = $_POST['expiry_date'];
            
            if (empty($payment_id)) {
                if (strlen($card_number) < 13 || strlen($card_number) > 19) {
                    header('Location: account.php?section=payment&msg=Nomor kartu tidak valid.');
                    exit;
                }
                $stmt_count = $conn->prepare("SELECT COUNT(*) as count FROM user_payment_methods WHERE user_id = ?");
                $stmt_count->bind_param("i", $user_id);
                $stmt_count->execute();
                $is_first_card = $stmt_count->get_result()->fetch_assoc()['count'] == 0;
                $is_default = $is_first_card ? 1 : 0;
                
                $stmt = $conn->prepare("INSERT INTO user_payment_methods (user_id, card_type, cardholder_name, card_number, expiry_date, is_default) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("issssi", $user_id, $card_type, $cardholder_name, $card_number, $expiry_date, $is_default);
                $msg = "Metode pembayaran berhasil disimpan.";
            } else {
                $stmt = $conn->prepare("UPDATE user_payment_methods SET card_type=?, cardholder_name=?, expiry_date=? WHERE id=? AND user_id=?");
                $stmt->bind_param("sssii", $card_type, $cardholder_name, $expiry_date, $payment_id, $user_id);
                $msg = "Metode pembayaran berhasil di-update.";
            }
            
            if ($stmt->execute()) {
                header('Location: account.php?section=payment&msg=' . urlencode($msg));
            } else {
                header('Location: account.php?section=payment&msg=Gagal menyimpan data.');
            }
            $stmt->close();
            exit;

        case 'set_default_payment':
            $payment_id = (int)$_POST['payment_id'];
            $conn->begin_transaction();
            try {
                $conn->query("UPDATE user_payment_methods SET is_default = 0 WHERE user_id = $user_id");
                $conn->query("UPDATE user_payment_methods SET is_default = 1 WHERE id = $payment_id AND user_id = $user_id");
                $conn->commit();
                header('Location: account.php?section=payment&msg=Metode pembayaran utama berhasil diubah.');
            } catch (Exception $e) {
                $conn->rollback();
                header('Location: account.php?section=payment&msg=Gagal mengubah metode pembayaran utama.');
            }
            exit;
    }
}

// --- Pengambilan Data dari Database ---
$stmt_user = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt_user->bind_param("i", $user_id);
$stmt_user->execute();
$current_user = $stmt_user->get_result()->fetch_assoc();
$stmt_user->close();

$stmt_addresses = $conn->prepare("SELECT * FROM user_addresses WHERE user_id = ? ORDER BY id DESC");
$stmt_addresses->bind_param("i", $user_id);
$stmt_addresses->execute();
$user_addresses = $stmt_addresses->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_addresses->close();

$stmt_orders = $conn->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY order_date DESC");
$stmt_orders->bind_param("i", $user_id);
$stmt_orders->execute();
$user_orders = $stmt_orders->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_orders->close();

$stmt_payments = $conn->prepare("SELECT * FROM user_payment_methods WHERE user_id = ? ORDER BY is_default DESC, id DESC");
$stmt_payments->bind_param("i", $user_id);
$stmt_payments->execute();
$user_payments = $stmt_payments->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_payments->close();

if (isset($_GET['msg'])) $message = htmlspecialchars($_GET['msg']);
$cart_item_count = isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0;
$active_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Akun Saya | LUXE</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        :root { --primary-color: #333; --accent-color: #c5a47e; --light-color: #f8f9fa; --border-color: #dee2e6; }
        .account-page-container { display: flex; max-width: 1400px; margin: 20px auto; background-color: white; box-shadow: 0 0 15px rgba(0,0,0,0.07); border-radius: 8px; overflow: hidden; }
        .account-sidebar { width: 280px; padding: 20px; border-right: 1px solid var(--border-color); background-color: #fff; }
        .account-nav .list-group-item.active { background-color: var(--accent-color); color: #fff; border-color: var(--accent-color); border-radius: 8px; }
        .account-content { flex: 1; padding: 30px 40px; }
        .section { display: none; } 
        .section.active { display: block; animation: fadeIn 0.5s; }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        .payment-card, .address-card { border: 1px solid var(--border-color); border-radius: 8px; padding: 20px; position: relative; }
        .payment-card.default { border-color: var(--accent-color); box-shadow: 0 0 5px rgba(197, 164, 126, 0.5); }
        .default-badge { position: absolute; top: 15px; right: 15px; background-color: var(--accent-color); color: white; padding: 3px 8px; border-radius: 5px; font-size: 0.8em; }
        .notification-alert { transition: opacity 0.5s ease-in-out; }
        .profile-picture-wrapper { text-align: center; }
        .profile-picture { width: 150px; height: 150px; border-radius: 50%; object-fit: cover; border: 4px solid #eee; }
        .card-logo { height: 25px; margin-right: 15px; }
        .payment-card-body { display: flex; align-items: center; }
        .card-preview-wrapper { padding: 20px; display: flex; justify-content: center; align-items: center; }
        .card-preview { width: 280px; height: 170px; border-radius: 15px; padding: 20px; color: white; position: relative; background: linear-gradient(45deg, #4d4d4d, #111); box-shadow: 0 10px 20px rgba(0,0,0,0.2); transition: all 0.3s ease; }
        .card-preview .card-logo-preview { position: absolute; top: 20px; right: 20px; height: 30px; opacity: 0.8; }
        .card-preview .card-chip { position: absolute; top: 25px; left: 25px; width: 40px; }
        .card-preview .card-number-preview { font-family: 'Courier New', Courier, monospace; font-size: 1.2rem; letter-spacing: 2px; position: absolute; bottom: 60px; left: 20px; }
        .card-preview .card-holder-preview, .card-preview .card-expiry-preview { font-size: 0.75rem; position: absolute; bottom: 20px; text-transform: uppercase; }
        .card-preview .card-holder-preview { left: 20px; }
        .card-preview .card-expiry-preview { right: 20px; }
    </style>
</head>
<body>
<header class="header">
    <div class="container"><div class="header-inner"><div class="logo"><a href="index.php">LUXE</a></div><nav class="main-nav"><ul><li><a href="index.php">Home</a></li><li><a href="collections.php">Collections</a></li></ul></nav><div class="nav-actions"><a href="account.php" class="account-btn active"><i class="far fa-user"></i></a><a href="checkout.php" class="cart-btn" id="cart-btn"><i class="fas fa-shopping-bag"></i><span class="cart-count"><?php echo $cart_item_count; ?></span></a></div></div></div>
</header>

<?php if(!empty($message)): ?>
    <div class="alert alert-info position-fixed top-0 start-50 translate-middle-x mt-3 notification-alert" style="z-index: 1056;"><?php echo $message; ?></div>
<?php endif; ?>

<div class="container account-page-container mt-4">
    <div class="account-sidebar">
        <h2 class="mb-4">Akun Saya</h2>
        <div class="list-group account-nav">
            <a onclick="showSection('profile')" class="list-group-item list-group-item-action"><i class="fas fa-user-circle fa-fw me-2"></i> Profil Saya</a>
            <a onclick="showSection('addresses')" class="list-group-item list-group-item-action"><i class="fas fa-map-marker-alt fa-fw me-2"></i> Alamat Saya</a>
            <a onclick="showSection('payment')" class="list-group-item list-group-item-action"><i class="fas fa-credit-card fa-fw me-2"></i> Pembayaran</a>
            <a onclick="showSection('orders')" class="list-group-item list-group-item-action"><i class="fas fa-box-open fa-fw me-2"></i> Pesanan Saya</a>
            <a onclick="showSection('change-password')" class="list-group-item list-group-item-action"><i class="fas fa-lock fa-fw me-2"></i> Ganti Password</a>
            <a href="account.php?action=logout" class="list-group-item list-group-item-action text-danger mt-3"><i class="fas fa-sign-out-alt fa-fw me-2"></i> Logout</a>
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
                        <tr><td style="width: 150px;"><strong>Username</strong></td><td>: <?php echo htmlspecialchars($current_user['username']); ?></td></tr>
                        <tr><td><strong>Nama Lengkap</strong></td><td>: <?php echo htmlspecialchars($current_user['fullname']); ?></td></tr>
                        <tr><td><strong>Email</strong></td><td>: <?php echo htmlspecialchars($current_user['email']); ?></td></tr>
                        <tr><td><strong>Telepon</strong></td><td>: <?php echo htmlspecialchars($current_user['phone'] ?: 'Belum diatur'); ?></td></tr>
                        <tr><td><strong>Tanggal Lahir</strong></td><td>: <?php echo htmlspecialchars($current_user['date_of_birth'] ?: 'Belum diatur'); ?></td></tr>
                        <tr><td><strong>Jenis Kelamin</strong></td><td>: <?php echo htmlspecialchars($current_user['gender'] ?: 'Belum diatur'); ?></td></tr>
                    </table>
                </div>
                <div class="col-md-4">
                    <div class="profile-picture-wrapper">
                        <img src="<?php echo (isset($current_user['profile_image']) && file_exists($current_user['profile_image'])) ? htmlspecialchars($current_user['profile_image']) : 'https://placehold.co/150x150/E8D4B7/333?text=Profil'; ?>" alt="Foto Profil" class="profile-picture mb-3">
                        <form action="account.php" method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="update_profile">
                            <div class="input-group">
                                <input type="file" class="form-control form-control-sm" name="profile_image" id="profile_image" required>
                                <button class="btn btn-sm btn-outline-secondary" type="submit">Upload</button>
                            </div>
                            <small class="form-text text-muted">Pilih gambar (maks. 2MB)</small>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bagian Alamat -->
        <div id="addresses" class="section">
             <div class="d-flex justify-content-between align-items-center">
                <h3>Alamat Saya</h3>
                <button class="btn btn-dark" data-bs-toggle="modal" data-bs-target="#addressModal" onclick="prepareAddressModal()">Tambah Alamat Baru</button>
            </div>
            <hr>
            <div class="row g-3">
                <?php if(empty($user_addresses)): ?>
                    <p>Anda belum menyimpan alamat.</p>
                <?php else: ?>
                    <?php foreach($user_addresses as $addr): ?>
                    <div class="col-md-6">
                        <div class="address-card h-100">
                            <h5><?php echo htmlspecialchars($addr['label']); ?></h5>
                            <p class="mb-1"><strong><?php echo htmlspecialchars($addr['recipient_name']); ?></strong></p>
                            <p class="mb-1"><?php echo htmlspecialchars($addr['phone_number']); ?></p>
                            <p class="text-muted small"><?php echo htmlspecialchars($addr['street_address']); ?>, <?php echo htmlspecialchars($addr['city']); ?>, <?php echo htmlspecialchars($addr['province']); ?> <?php echo htmlspecialchars($addr['postal_code']); ?></p>
                            <hr>
                            <button class="btn btn-sm btn-outline-secondary edit-address-btn" data-address='<?php echo json_encode($addr, JSON_HEX_APOS); ?>'>Edit</button>
                            <a href="account.php?action=delete_address&id=<?php echo $addr['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Yakin ingin menghapus alamat ini?')">Hapus</a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Bagian Pembayaran -->
        <div id="payment" class="section">
            <div class="d-flex justify-content-between align-items-center">
                <h3>Metode Pembayaran</h3>
                <button class="btn btn-dark" data-bs-toggle="modal" data-bs-target="#paymentModal" onclick="preparePaymentModal()">Tambah Kartu Baru</button>
            </div>
            <p>Kelola kartu kredit/debit yang tersimpan untuk kemudahan transaksi.</p>
            <hr>
            <div class="row g-3">
                <?php if(empty($user_payments)): ?>
                    <p>Anda belum menyimpan metode pembayaran.</p>
                <?php else: ?>
                    <?php foreach($user_payments as $card): ?>
                    <div class="col-md-6">
                        <div class="payment-card <?php if($card['is_default']) echo 'default'; ?>">
                            <?php if($card['is_default']): ?><span class="default-badge">Utama</span><?php endif; ?>
                            <div class="payment-card-body">
                                <img src="" alt="<?php echo ucfirst($card['card_type']); ?>" class="card-logo" id="logo-<?php echo $card['id']; ?>">
                                <script>
                                    (function(){
                                        const cardType = '<?php echo $card['card_type']; ?>';
                                        const logo = document.getElementById('logo-<?php echo $card['id']; ?>');
                                        const logos = {
                                            visa: 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIyNCIgaGVpZ2h0PSIyNCIgdmlld0JveD0iMCAwIDI0IDI0Ij48cGF0aCBmaWxsPSIjMUExRjcxIiBkPSJNMjEgMy4zSDNjLS45IDAtMS43LjYtMS45IDEuNEwyIDE2LjZjLS4xLjktLjEgMS4xIDAgMmwzLjYgMi4yYy40LjMuOC41IDEuMy41aDEyLjJjLjUgMCAuOS0uMiAxLjMtLjVsMy42LTIuMmMuMy0uOC4zLTEuMS4xLTJsLTEuMy0xMS45Yy0uMy0uOC0xLTEuNC0yLTEuNHptLTkuNyA5LjZsLTIuMyA2LjRIMy4xTDEwLjYgMy4zaDMuMWwtNC4xIDkuNnpNMTcgMy4zaC0yLjhsLTIuNiA5LjcgMS45LjFMMTQuOCA2bDQuOCAxMy4yaDIuM2wtMy40LTE2eiIvPjwvc3ZnPg==',
                                            mastercard: 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIyNCIgaGVpZ2h0PSIyNCIgdmlld0JveD0iMCAwIDI0IDI0Ij48cGF0aCBkPSJNMTIgMmMwIDUuNSAyLjIgMTAgNS41IDEwUzIzIDE3LjUgMjMgMTJzLTIuMi0xMC01LjUtMTBTMTEgNi41IDExIDJ6IiBmaWxsPSIjZWIwMDFiIi8+PHBhdGggZD0iTTEyIDJjMCA1LjUtMi4yIDEwLTUuNSAxMFMtMSAxNy41LTEgMTJzMi4yLTEwIDUuNS0xMFMxMiA2LjUgMTIgMnoiIGZpbGw9IiNmNzk2MTkiLz48cGF0aCBkPSJNMTQuNzUgOGEuNS41IDAgMCAwLS41LjV2NmEuNS41IDAgMSAwIDEgMFY4LjVhLjUuNSAwIDAgMC0uNS0uNXptLTYuNSAwYS41LjUgMCAwIDAtLjUuNXY2YS41LjUgMCAxIDAgMSAwVjguNWEuNS41IDAgMCAwLS41LS41ek0xMiAxMmMzLjMgMCA2IDIuNyA2IDZzLTIuNyA2LTYgNi02LTIuNy02LTYgMi43LTYgNi02eiIgZmlsbD0iI2ZmNWYwMCIvPjwvc3ZnPg==',
                                            jcb: 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIyNCIgaGVpZ2h0PSIyNCIgdmlld0JveD0iMCAwIDI0IDI0Ij48cGF0aCBmaWxsPSIjMDM2QTExIiBkPSJNMyAzaDE4djE4SDN6Ii8+PHBhdGggZmlsbD0iI2MzMjMyNyIgZD0iTTcgN2g0djEwSDd6Ii8+PHBhdGggZmlsbD0iIzM3OTA1MSIgZD0iTTEyIDdoNHYxMEgxMnoiLz48cGF0aCBmaWxsPSIjZmZmIiBkPSJNOS41IDguNWgtMXY3aDF2LTdoLTdoMXY3aDF2LTdoLTdoMXY3aDF2LTdoLTdoMXY3aDF2LTdoLTd6bTQuNS0xaC0xdjdIMGwxLjUtNC41aDJMNi41IDE0LjVIMTJWMTMuNWwtMS41LTQuNWgyTDE0LjUgMTQuNUgxNlY3LjV6Ii8+PC9zdmc+'
                                        };
                                        if (logo && logos[cardType]) {
                                            logo.src = logos[cardType];
                                        } else if(logo) {
                                            logo.src = 'https://placehold.co/40x25/ccc/333?text=CARD';
                                        }
                                    })();
                                </script>
                                <div>
                                    <h5 class="mb-0"><?php echo htmlspecialchars($card['cardholder_name']); ?></h5>
                                    <p class="mb-0 font-monospace text-muted small"><?php echo htmlspecialchars(ucfirst($card['card_type'])); ?> **** **** **** <?php echo substr(htmlspecialchars($card['card_number']), -4); ?></p>
                                </div>
                            </div>
                            <hr>
                            <div class="d-flex gap-2">
                                <?php if(!$card['is_default']): ?>
                                <form method="POST" action="account.php" class="d-inline">
                                    <input type="hidden" name="action" value="set_default_payment">
                                    <input type="hidden" name="payment_id" value="<?php echo $card['id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-primary">Jadikan Utama</button>
                                </form>
                                <?php endif; ?>
                                <button class="btn btn-sm btn-outline-secondary edit-payment-btn" data-payment='<?php echo json_encode($card, JSON_HEX_APOS); ?>'>Edit</button>
                                <a href="account.php?action=delete_payment&id=<?php echo $card['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Yakin ingin menghapus kartu ini?')">Hapus</a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Bagian Pesanan -->
        <div id="orders" class="section">
            <h3>Pesanan Saya</h3>
            <hr>
            <?php if(empty($user_orders)): ?>
                <p>Anda belum memiliki riwayat pesanan.</p>
            <?php else: ?>
                <div class="accordion" id="ordersAccordion">
                <?php foreach($user_orders as $order): ?>
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="heading<?php echo $order['id']; ?>">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?php echo $order['id']; ?>">
                                Order #<?php echo $order['id']; ?> - <?php echo date('d M Y', strtotime($order['order_date'])); ?> - <strong class="ms-2">$<?php echo number_format($order['total_amount'], 2); ?></strong>
                            </button>
                        </h2>
                        <div id="collapse<?php echo $order['id']; ?>" class="accordion-collapse collapse" data-bs-parent="#ordersAccordion">
                            <div class="accordion-body">
                                <p><strong>Status:</strong> <span class="badge bg-primary"><?php echo ucfirst(htmlspecialchars($order['order_status'])); ?></span></p>
                                <p><strong>Tipe Pembayaran:</strong> 
                                    <span class="badge bg-secondary">
                                        <?php 
                                            if (isset($order['payment_type'])) {
                                                echo strtoupper(htmlspecialchars($order['payment_type']));
                                            } else {
                                                echo 'N/A';
                                            }
                                        ?>
                                    </span>
                                </p>
                                <p class="mt-2">Detail item akan ditampilkan di sini nanti.</p>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                </div>
            <?php endif; ?>
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
    </div>
</div>

<!-- Modal Alamat -->
<div class="modal fade" id="addressModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="addressForm" method="POST" action="account.php">
                <input type="hidden" name="action" value="save_address">
                <input type="hidden" name="address_id" id="address_id">
                <div class="modal-header">
                    <h5 class="modal-title" id="addressModalLabel">Tambah Alamat Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3"><label for="label" class="form-label">Label Alamat (cth: Rumah, Kantor)</label><input type="text" class="form-control" name="label" id="label" required></div>
                    <div class="mb-3"><label for="recipient_name" class="form-label">Nama Penerima</label><input type="text" class="form-control" name="recipient_name" id="recipient_name" required></div>
                    <div class="mb-3"><label for="phone_number" class="form-label">Nomor Telepon</label><input type="tel" class="form-control" name="phone_number" id="phone_number" required></div>
                    <div class="mb-3"><label for="street_address" class="form-label">Alamat Jalan</label><textarea class="form-control" name="street_address" id="street_address" required></textarea></div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="province" class="form-label">Provinsi</label>
                            <select class="form-select" name="province" id="province" required>
                                <option value="">Memuat provinsi...</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="city" class="form-label">Kota/Kabupaten</label>
                             <select class="form-select" name="city" id="city" required disabled>
                                <option value="">Pilih provinsi dahulu</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3"><label for="postal_code" class="form-label">Kode Pos</label><input type="text" class="form-control" name="postal_code" id="postal_code" required></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Alamat</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Pembayaran -->
<div class="modal fade" id="paymentModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="paymentForm" method="POST" action="account.php">
                <input type="hidden" name="action" value="save_payment">
                <input type="hidden" name="payment_id" id="payment_id">
                <div class="modal-header">
                    <h5 class="modal-title" id="paymentModalLabel">Tambah Kartu Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="card-preview-wrapper">
                        <div class="card-preview" id="cardPreview">
                            <img src="data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIyNCIgaGVpZ2h0PSIyNCIgdmlld0JveD0iMCAwIDI0IDI0Ij48cGF0aCBmaWxsPSIjZmZmIiBkPSJNMi41IDEzLjVINS4zYS41LjUgMCAwIDAgLjUtLjV2LTRhLjUuNSAwIDAgMC0uNS0uNUgyLjVhLjUuNSAwIDAgMC0uNS41djRhLjUuNSAwIDAgMCAuNS41em0yLjgtNC41djVoNC45di01SDUuM3ptLTUtNGg0Ljl2LTQuOWgtNC45djQuOXptMCAzLjZjMCAuOS43IDEuNiAxLjYgMS42aDExLjhjLjkgMCAxLjYtLjcgMS42LTEuNlY2LjdjMC0uOS0uNy0xLjYtMS42LTEuNmgtMTEuOGMtLjkgMC0xLjYuNy0xLjYgMS42djQuOXptMTggMy41aDIuN2MuMyAwIC41LjIgLjUuNXY0YS41LjUgMCAwIDEtLjUuNUgyMy4zYy0uMyAwLS41LS4yLS41LS41di00Yy4xLS4zLjItLjUuNS0uNXoiLz48L3N2Zz4=" class="card-chip" alt="Chip">
                            <img src="" class="card-logo-preview" id="cardLogoPreview" alt="Card Logo">
                            <div class="card-number-preview" id="cardNumberPreview">**** **** **** ****</div>
                            <div class="card-holder-preview" id="cardHolderPreview">NAMA PEMEGANG KARTU</div>
                            <div class="card-expiry-preview" id="cardExpiryPreview">MM/YY</div>
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label for="card_type" class="form-label">Jenis Kartu</label>
                            <select class="form-select" name="card_type" id="card_type" required>
                                <option value="visa">Visa</option>
                                <option value="mastercard">Mastercard</option>
                                <option value="jcb">JCB</option>
                            </select>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label for="card_number" class="form-label">Nomor Kartu Kredit</label>
                            <input type="text" class="form-control" id="card_number" name="card_number" required inputmode="numeric" pattern="[0-9\s]{13,22}" maxlength="19" placeholder="xxxx xxxx xxxx xxxx">
                        </div>
                        <div class="col-md-12 mb-3">
                             <label for="cardholder_name" class="form-label">Nama di Kartu</label>
                             <input type="text" class="form-control" name="cardholder_name" id="cardholder_name" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="expiry_date" class="form-label">Tanggal Kadaluarsa</label>
                            <input type="month" class="form-control" name="expiry_date" id="expiry_date" required>
                        </div>
                         <div class="col-md-6 mb-3">
                            <label for="cvv" class="form-label">CVV</label>
                            <input type="password" class="form-control" id="cvv" name="cvv" required inputmode="numeric" maxlength="4">
                             <small class="form-text text-muted">CVV tidak akan disimpan.</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Kartu</button>
                </div>
            </form>
        </div>
    </div>
</div>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const urlParams = new URLSearchParams(window.location.search);
    const validSections = ['profile', 'addresses', 'payment', 'orders', 'change-password'];
    let section = urlParams.get('section') || 'profile';
    if (!validSections.includes(section)) {
        section = 'profile';
    }
    showSection(section);

    // --- LOGIKA MODAL ALAMAT DENGAN DROPDOWN DINAMIS ---
    const addressModal = new bootstrap.Modal(document.getElementById('addressModal'));
    const provinceSelect = document.getElementById('province');
    const citySelect = document.getElementById('city');
    const API_BASE_URL = 'https://www.emsifa.com/api-wilayah-indonesia/api/';

    async function loadProvinces(selectedProvinceName = null) {
        try {
            const response = await fetch(`${API_BASE_URL}provinces.json`);
            const provinces = await response.json();
            provinceSelect.innerHTML = '<option value="">Pilih Provinsi</option>';
            let selectedProvinceId = null;

            provinces.forEach(prov => {
                const option = document.createElement('option');
                option.value = prov.name; // Simpan nama untuk submit form
                option.dataset.id = prov.id; // Simpan ID untuk fetch kota
                option.textContent = prov.name;
                if (prov.name.toLowerCase() === selectedProvinceName?.toLowerCase()) {
                    option.selected = true;
                    selectedProvinceId = prov.id;
                }
                provinceSelect.appendChild(option);
            });
            return selectedProvinceId;
        } catch (error) {
            console.error('Error loading provinces:', error);
            provinceSelect.innerHTML = '<option value="">Gagal memuat</option>';
        }
    }
    
    async function loadCities(provinceId, selectedCityName = null) {
        if (!provinceId) {
            citySelect.innerHTML = '<option value="">Pilih provinsi dahulu</option>';
            citySelect.disabled = true;
            return;
        }
        citySelect.innerHTML = '<option value="">Memuat kota...</option>';
        citySelect.disabled = true;

        try {
            const response = await fetch(`${API_BASE_URL}regencies/${provinceId}.json`);
            const cities = await response.json();
            citySelect.innerHTML = '<option value="">Pilih Kota/Kabupaten</option>';
            cities.forEach(city => {
                const option = document.createElement('option');
                option.value = city.name;
                option.textContent = city.name;
                if (city.name.toLowerCase() === selectedCityName?.toLowerCase()) {
                    option.selected = true;
                }
                citySelect.appendChild(option);
            });
            citySelect.disabled = false;
        } catch (error) {
            console.error('Error loading cities:', error);
            citySelect.innerHTML = '<option value="">Gagal memuat</option>';
        }
    }
    
    provinceSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        const provinceId = selectedOption ? selectedOption.dataset.id : null;
        loadCities(provinceId);
    });

    window.prepareAddressModal = function() {
        document.getElementById('addressForm').reset();
        document.getElementById('addressModalLabel').textContent = 'Tambah Alamat Baru';
        document.getElementById('address_id').value = '';
        loadProvinces();
        citySelect.innerHTML = '<option value="">Pilih provinsi dahulu</option>';
        citySelect.disabled = true;
    };

    document.querySelectorAll('.edit-address-btn').forEach(btn => {
        btn.addEventListener('click', async function() {
            const data = JSON.parse(this.getAttribute('data-address'));
            
            // Isi form lain seperti biasa
            document.getElementById('addressModalLabel').textContent = 'Edit Alamat';
            document.getElementById('address_id').value = data.id;
            document.getElementById('label').value = data.label;
            document.getElementById('recipient_name').value = data.recipient_name;
            document.getElementById('phone_number').value = data.phone_number;
            document.getElementById('street_address').value = data.street_address;
            document.getElementById('postal_code').value = data.postal_code;

            // Muat dan pilih provinsi & kota yang sesuai
            const provinceId = await loadProvinces(data.province);
            if (provinceId) {
                await loadCities(provinceId, data.city);
            }
            
            addressModal.show();
        });
    });

    // --- LOGIKA MODAL PEMBAYARAN INTERAKTIF ---
    const paymentModal = new bootstrap.Modal(document.getElementById('paymentModal'));
    const paymentForm = document.getElementById('paymentForm');
    const paymentModalLabel = document.getElementById('paymentModalLabel');
    const paymentIdInput = document.getElementById('payment_id');
    const cardTypeSelect = document.getElementById('card_type');
    const cardNumberInput = document.getElementById('card_number');
    const cardholderNameInput = document.getElementById('cardholder_name');
    const expiryDateInput = document.getElementById('expiry_date');
    const cvvInput = document.getElementById('cvv');
    const cardPreview = document.getElementById('cardPreview');
    const cardLogoPreview = document.getElementById('cardLogoPreview');
    const cardNumberPreview = document.getElementById('cardNumberPreview');
    const cardHolderPreview = document.getElementById('cardHolderPreview');
    const cardExpiryPreview = document.getElementById('cardExpiryPreview');
    
    const cardLogos = {
        visa: 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIyNCIgaGVpZ2h0PSIyNCIgdmlld0JveD0iMCAwIDI0IDI0Ij48cGF0aCBmaWxsPSIjZmZmIiBkPSJNMjEgMy4zSDNjLS45IDAtMS43LjYtMS45IDEuNEwyIDE2LjZjLS4xLjktLjEgMS4xIDAgMmwzLjYgMi4yYy40LjMuOC41IDEuMy41aDEyLjJjLjUgMCAuOS0uMiAxLjMtLjVsMy42LTIuMmMuMy0uOC4zLTEuMS4xLTJsLTEuMy0xMS45Yy0uMy0uOC0xLTEuNC0yLTEuNHptLTkuNyA5LjZsLTIuMyA2LjRIMy4xTDEwLjYgMy4zaDMuMWwtNC4xIDkuNnpNMTcgMy4zaC0yLjhsLTIuNiA5LjcgMS45LjFMMTQuOCA2bDQuOCAxMy4yaDIuM2wtMy40LTE2eiIvPjwvc3ZnPg==',
        mastercard: 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIyNCIgaGVpZ2h0PSIyNCIgdmlld0JveD0iMCAwIDI0IDI0Ij48cGF0aCBkPSJNMTIgMmMwIDUuNSAyLjIgMTAgNS41IDEwUzIzIDE3LjUgMjMgMTJzLTIuMi0xMC01LjUtMTBTMTEgNi41IDExIDJ6IiBmaWxsPSIjZWIwMDFiIi8+PHBhdGggZD0iTTEyIDJjMCA1LjUtMi4yIDEwLTUuNSAxMFMtMSAxNy41LTEgMTJzMi4yLTEwIDUuNS0xMFMxMiA2LjUgMTIgMnoiIGZpbGw9IiNmNzk2MTkiLz48cGF0aCBkPSJNMTQuNzUgOGEuNS41IDAgMCAwLS41LjV2NmEuNS41IDAgMSAwIDEgMFY4LjVhLjUuNSAwIDAgMC0uNS0uNXptLTYuNSAwYS41LjUgMCAwIDAtLjUuNXY2YS41LjUgMCAxIDAgMSAwVjguNWEuNS41IDAgMCAwLS41LS41ek0xMiAxMmMzLjMgMCA2IDIuNyA2IDZzLTIuNyA2LTYgNi02LTIuNy02LTYgMi43LTYgNi02eiIgZmlsbD0iI2ZmNWYwMCIvPjwvc3ZnPg==',
        jcb: 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIyNCIgaGVpZ2h0PSIyNCIgdmlld0JveD0iMCAwIDI0IDI0Ij48cGF0aCBmaWxsPSIjMDM2QTExIiBkPSJNMyAzaDE4djE4SDN6Ii8+PHBhdGggZmlsbD0iI2MzMjMyNyIgZD0iTTcgN2g0djEwSDd6Ii8+PHBhdGggZmlsbD0iIzM3OTA1MSIgZD0iTTEyIDdoNHYxMEgxMnoiLz48cGF0aCBmaWxsPSIjZmZmIiBkPSJNOS41IDguNWgtMXY3aDF2LTdoLTdoMXY3aDF2LTdoLTdoMXY3aDF2LTdoLTdoMXY3aDF2LTdoLTd6bTQuNS0xaC0xdjdIMGwxLjUtNC41aDJMNi41IDE0LjVIMTJWMTMuNWwtMS41LTQuNWgyTDE0LjUgMTQuNUgxNlY3LjV6Ii8+PC9zdmc+'
    };

    const updatePreview = () => {
        cardLogoPreview.src = cardLogos[cardTypeSelect.value] || '';
        let num = cardNumberInput.value.replace(/\D/g, '').substring(0, 16);
        let formattedNum = num.replace(/(\d{4})/g, '$1 ').trim();
        cardNumberPreview.textContent = formattedNum || '**** **** **** ****';
        cardHolderPreview.textContent = cardholderNameInput.value.toUpperCase() || 'NAMA PEMEGANG KARTU';
        if (expiryDateInput.value) {
            const [year, month] = expiryDateInput.value.split('-');
            cardExpiryPreview.textContent = `${month}/${year.slice(2)}`;
        } else {
            cardExpiryPreview.textContent = 'MM/YY';
        }
    };

    cardTypeSelect.addEventListener('change', updatePreview);
    
    cardNumberInput.addEventListener('input', (e) => {
        let value = e.target.value.replace(/\D/g, '');
        let formattedValue = value.replace(/(\d{4})/g, '$1 ').trim();
        e.target.value = formattedValue;
        updatePreview();
    });

    cardholderNameInput.addEventListener('input', updatePreview);
    expiryDateInput.addEventListener('change', updatePreview);

    window.preparePaymentModal = function() {
        paymentForm.reset();
        paymentModalLabel.textContent = 'Tambah Kartu Baru';
        paymentIdInput.value = '';
        cardNumberInput.removeAttribute('readonly');
        cardNumberInput.setAttribute('required', 'required');
        cvvInput.value = '';
        updatePreview();
    };

    document.querySelectorAll('.edit-payment-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const data = JSON.parse(this.getAttribute('data-payment'));
            paymentModalLabel.textContent = 'Edit Metode Pembayaran';
            paymentIdInput.value = data.id;
            cardTypeSelect.value = data.card_type;
            cardholderNameInput.value = data.cardholder_name;
            
            let rawNumber = data.card_number.replace(/\D/g, '');
            cardNumberInput.value = rawNumber.replace(/(\d{4})/g, '$1 ').trim();

            expiryDateInput.value = data.expiry_date;
            cardNumberInput.setAttribute('readonly', 'readonly');
            cardNumberInput.removeAttribute('required');
            updatePreview();
            paymentModal.show();
        });
    });

    const alertBox = document.querySelector('.notification-alert');
    if (alertBox) {
        setTimeout(() => {
            alertBox.style.opacity = '0';
            setTimeout(() => { alertBox.remove(); }, 500);
        }, 5000);
    }
});

function showSection(sectionId) {
    document.querySelectorAll('.section').forEach(s => s.classList.remove('active'));
    document.getElementById(sectionId)?.classList.add('active');
    document.querySelectorAll('.account-nav .list-group-item').forEach(item => {
        item.classList.remove('active');
        if (item.getAttribute('onclick') === `showSection('${sectionId}')`) {
            item.classList.add('active');
        }
    });
    const newurl = `${window.location.protocol}//${window.location.host}${window.location.pathname}?section=${sectionId}`;
    window.history.pushState({path:newurl}, '', newurl);
}
</script>
</body>
</html>
