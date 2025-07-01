<?php
session_start();
require_once 'db_connection.php'; // Hubungkan ke database

// ========================================================================
// 1. BLOK KEAMANAN & LOGIKA PHP
// ========================================================================

// --- Pemeriksaan Keamanan: Pastikan pengguna sudah login ---
if (!isset($_SESSION['isLoggedIn']) || $_SESSION['isLoggedIn'] !== true || !isset($_SESSION['userId'])) {
    header('Location: login.php?redirect_url=checkout.php');
    exit;
}

$user_id = $_SESSION['userId'];
$message = '';

// --- Aksi: Hapus Item dari Keranjang (via GET) ---
if (isset($_GET['action']) && $_GET['action'] === 'remove' && isset($_GET['id'])) {
    $product_id_to_remove = $_GET['id'];
    if (isset($_SESSION['cart'][$product_id_to_remove])) {
        unset($_SESSION['cart'][$product_id_to_remove]);
    }
    // Alihkan kembali ke halaman checkout tanpa parameter untuk membersihkan URL
    header('Location: checkout.php');
    exit;
}

$cart = $_SESSION['cart'] ?? [];

// --- Aksi: Proses Pesanan (via POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['complete_order'])) {
    $selected_address_id = $_POST['selected_address_id'] ?? null;
    $default_payment_id = $_POST['default_payment_id'] ?? null; 
    $payment_type = $_POST['payment_type'] ?? 'cod'; 

    if (empty($cart)) {
        $message = 'Keranjang Anda kosong. Tidak bisa memproses pesanan.';
    } elseif (!$selected_address_id) {
        $message = 'Silakan pilih alamat pengiriman.';
    } else {
        $conn->begin_transaction();
        try {
            // Kalkulasi total
            $total_amount = 0;
            foreach ($cart as $item) {
                $total_amount += ($item['price'] ?? 0) * ($item['quantity'] ?? 0);
            }
            $shipping_cost = 10.00; // Biaya pengiriman tetap
            $grand_total = $total_amount + $shipping_cost;

            // Simpan pesanan utama
            $order_status = ($payment_type === 'card') ? 'paid' : 'pending';
            $payment_id_to_db = ($payment_type === 'card') ? $default_payment_id : NULL;

            $stmt_order = $conn->prepare("INSERT INTO orders (user_id, shipping_address_id, payment_method_id, total_amount, order_status, payment_type) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt_order->bind_param("iiidss", $user_id, $selected_address_id, $payment_id_to_db, $grand_total, $order_status, $payment_type);
            
            if (!$stmt_order->execute()) {
                throw new Exception($stmt_order->error);
            }

            $order_id = $conn->insert_id;
            $stmt_order->close();

            // Simpan detail item pesanan
            $stmt_items = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
            foreach ($cart as $product_id => $item) {
                $stmt_items->bind_param("iiid", $order_id, $product_id, $item['quantity'], $item['price']);
                $stmt_items->execute();
            }
            $stmt_items->close();

            $conn->commit();
            unset($_SESSION['cart']); // Kosongkan keranjang
            header('Location: account.php?section=orders&msg=Pesanan #' . $order_id . ' berhasil dibuat!');
            exit;

        } catch (Exception $e) {
            $conn->rollback();
            $message = "Terjadi kesalahan saat memproses pesanan. Silakan coba lagi. Error: " . $e->getMessage();
        }
    }
}

// --- Pengambilan Data untuk Tampilan Halaman ---

// Ambil alamat pengguna
$stmt_addr = $conn->prepare("SELECT * FROM user_addresses WHERE user_id = ? ORDER BY id DESC");
$stmt_addr->bind_param("i", $user_id);
$stmt_addr->execute();
$user_addresses = $stmt_addr->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_addr->close();

// Ambil metode pembayaran utama (default)
$stmt_pay = $conn->prepare("SELECT * FROM user_payment_methods WHERE user_id = ? AND is_default = 1");
$stmt_pay->bind_param("i", $user_id);
$stmt_pay->execute();
$default_payment = $stmt_pay->get_result()->fetch_assoc();
$stmt_pay->close();

// Kalkulasi total
$subtotal = 0;
foreach ($cart as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}
$shipping = 10.00;
$grand_total = $subtotal + $shipping;

$cart_item_count = count($cart);
$active_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout | LUXE</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        .checkout-grid { display: grid; grid-template-columns: 2fr 1.5fr; gap: 30px; }
        .checkout-box { background-color: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.07); }
        .address-option { border: 1px solid #ddd; padding: 15px; border-radius: 8px; cursor: pointer; transition: all 0.2s ease-in-out; }
        .address-option.selected { border-color: #333; box-shadow: 0 0 0 2px rgba(51,51,51,0.2); }
        .payment-method { border-top: 1px solid #eee; padding-top: 20px; margin-top: 20px; }
        .order-item-list .order-item { display: flex; align-items: center; gap: 15px; padding: 15px 0; border-bottom: 1px solid #eee; }
        .order-item:last-child { border-bottom: none; }
        .order-item img { width: 70px; height: 85px; object-fit: cover; border-radius: 4px; }
        .item-details { flex-grow: 1; }
        .item-details p, .item-details small { margin: 0; }
        .item-remove a { color: #dc3545; text-decoration: none; font-size: 1.1em; }
        .item-remove a:hover { color: #a71d2a; }
    </style>
</head>
<body>

<header class="header">
    <div class="container">
        <div class="header-inner">
            <div class="logo"><a href="index.php">LUXE</a></div>
            <nav class="main-nav">
                <ul>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="collections.php">Collections</a></li>
                </ul>
            </nav>
            <div class="nav-actions">
                <a href="account.php" class="account-btn"><i class="far fa-user"></i></a>
                <a href="checkout.php" class="cart-btn active" id="cart-btn">
                    <i class="fas fa-shopping-bag"></i>
                    <span class="cart-count"><?php echo $cart_item_count; ?></span>
                </a>
            </div>
        </div>
    </div>
</header>

<main class="container py-5">
    <?php if(!empty($message)): ?>
        <div class="alert alert-danger"><?php echo $message; ?></div>
    <?php endif; ?>

    <div class="checkout-grid">
        <!-- Kolom Kiri: Alamat & Pembayaran (Form) -->
        <form id="checkoutForm" method="POST" action="checkout.php">
            <div class="shipping-details checkout-box">
                <h2 class="mb-4">Shipping & Payment</h2>

                <h4 class="mb-3">Select Shipping Address</h4>
                <input type="hidden" name="selected_address_id" id="selected_address_id">
                
                <?php if(empty($user_addresses)): ?>
                    <div class="alert alert-warning">Anda belum punya alamat tersimpan. <a href="account.php?section=addresses">Tambah Alamat</a></div>
                <?php else: ?>
                    <div class="d-grid gap-3">
                        <?php foreach($user_addresses as $addr): ?>
                        <div class="address-option" data-id="<?php echo $addr['id']; ?>">
                            <strong><?php echo htmlspecialchars($addr['label']); ?></strong>
                            <p class="mb-0 text-muted"><?php echo htmlspecialchars($addr['recipient_name']); ?> | <?php echo htmlspecialchars($addr['phone_number']); ?></p>
                            <p class="mb-0 text-muted small"><?php echo htmlspecialchars($addr['street_address']); ?></p>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <div class="payment-method">
                    <h4 class="mb-3">Payment Method</h4>
                    <?php if($default_payment): ?>
                        <p>Anda akan membayar menggunakan metode pembayaran utama Anda:</p>
                        <div class="address-option selected">
                             <strong><?php echo htmlspecialchars($default_payment['cardholder_name']); ?></strong>
                             <p class="mb-0 text-muted font-monospace">**** **** **** <?php echo substr(htmlspecialchars($default_payment['card_number']), -4); ?></p>
                        </div>
                        <input type="hidden" name="payment_type" value="card">
                        <input type="hidden" name="default_payment_id" value="<?php echo $default_payment['id']; ?>">
                    <?php else: ?>
                         <p>Untuk saat ini, kami hanya mendukung "Cash on Delivery". Fitur pembayaran lain akan segera hadir.</p>
                         <input type="hidden" name="payment_type" value="cod">
                    <?php endif; ?>
                </div>

                 <button type="submit" name="complete_order" class="btn btn-dark w-100 mt-4" <?php if(empty($cart) || empty($user_addresses)) echo 'disabled'; ?>>
                    Complete Order
                </button>
            </div>
        </form>

        <!-- Kolom Kanan: Ringkasan Pesanan -->
        <div class="order-summary checkout-box">
            <h3 class="mb-4">Your Bag (<?php echo $cart_item_count; ?> items)</h3>
            
            <?php if (empty($cart)): ?>
                <p>Keranjang belanja Anda kosong. <a href="collections.php">Lanjutkan belanja</a>.</p>
            <?php else: ?>
                <!-- STRUKTUR TAMPILAN BARANG YANG BARU -->
                <div class="order-item-list mb-4">
                    <?php foreach ($cart as $product_id => $item): ?>
                    <div class="order-item">
                        <!-- PERBAIKAN: Menggunakan 'image_url' agar konsisten -->
                        <img src="<?php echo htmlspecialchars($item['image_url'] ?? 'https://placehold.co/70x85/E8D4B7/333?text=Image'); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                        <div class="item-details">
                            <p class="fw-bold mb-1"><?php echo htmlspecialchars($item['name']); ?></p>
                            <small class="text-muted">Qty: <?php echo htmlspecialchars($item['quantity']); ?></small>
                        </div>
                        <p class="item-price fw-bold mb-0">$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></p>
                        <div class="item-remove ms-2">
                            <a href="checkout.php?action=remove&id=<?php echo $product_id; ?>" title="Hapus item" onclick="return confirm('Anda yakin ingin menghapus item ini?');">
                                <i class="fas fa-trash-alt"></i>
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Tampilan Total dengan d-flex, bukan table -->
                <div class="order-totals">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Subtotal</span>
                        <span>$<?php echo number_format($subtotal, 2); ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-3 text-muted">
                        <span>Shipping</span>
                        <span>$<?php echo number_format($shipping, 2); ?></span>
                    </div>
                    <div class="d-flex justify-content-between fw-bold fs-5 border-top pt-3">
                        <span>Grand Total</span>
                        <span>$<?php echo number_format($grand_total, 2); ?></span>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const hiddenAddressInput = document.getElementById('selected_address_id');
    const addressOptions = document.querySelectorAll('.address-option');

    function selectAddress(optionElement) {
        hiddenAddressInput.value = optionElement.dataset.id;
        const shippingAddressOptions = document.querySelectorAll('.shipping-details .address-option');
        shippingAddressOptions.forEach(el => el.classList.remove('selected'));
        optionElement.classList.add('selected');
    }

    const shippingAddressOptions = document.querySelectorAll('.shipping-details .address-option');
    shippingAddressOptions.forEach(el => {
        el.addEventListener('click', function() {
            if(this.closest('.payment-method')) return;
            selectAddress(this);
        });
    });

    if (shippingAddressOptions.length > 0) {
        selectAddress(shippingAddressOptions[0]);
    }
});
</script>

</body>
</html>
