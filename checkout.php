
<?php
session_start();
require_once 'db_connection.php'; // Ini akan mengimpor variabel $conn

// ... sisa kode Anda bisa menggunakan variabel $conn untuk query ...
// Contoh: $result = $conn->query("SELECT * FROM users");
// Selalu mulai session di baris paling atas


// ========================================================================
// KONFIGURASI DAN FUNGSI PEMBANTU
// ========================================================================

$users_file = 'users.json';

// --- Fungsi untuk membaca & menyimpan data pengguna dari file JSON ---
function get_users($file) {
    if (!file_exists($file)) return [];
    return json_decode(file_get_contents($file), true);
}
function save_users($file, $users) {
    file_put_contents($file, json_encode($users, JSON_PRETTY_PRINT));
}

// --- Pemeriksaan Login ---
// Jika tidak login, alihkan ke halaman login
if (!isset($_SESSION['isLoggedIn']) || $_SESSION['isLoggedIn'] !== true) {
    header('Location: login.php');
    exit;
}

// --- Data Pengguna & Keranjang ---
$all_users = get_users($users_file);
$current_user_email = $_SESSION['loggedInUserEmail'];
$user_index = array_search($current_user_email, array_column($all_users, 'email'));
$current_user = ($user_index !== false) ? $all_users[$user_index] : null;

// Ekstrak data pengguna untuk digunakan di halaman
$user_addresses = $current_user['addresses'] ?? [];
$user_payment_methods = $current_user['payments'] ?? [];

// Ambil keranjang dari session. Tambahkan item dummy JIKA kosong untuk demonstrasi.
if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    $_SESSION['cart'] = [
        ['id' => 'prod1', 'name' => 'Elegant Silk Blouse', 'price' => 85.00, 'quantity' => 1],
        ['id' => 'prod2', 'name' => 'Classic Trench Coat', 'price' => 175.00, 'quantity' => 1],
    ];
}
$cart = $_SESSION['cart'];
$message = ''; // Untuk notifikasi


// ========================================================================
// LOGIKA PEMROSESAN FORMULIR (POST REQUEST)
// ========================================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $current_user) {

    // --- Aksi: Simpan Alamat ---
    if (isset($_POST['save_address'])) {
        $new_address = [
            'id' => 'addr_' . time(),
            'label' => $_POST['addressLabel'],
            'fullname' => $_POST['addressFullname'],
            'phone' => $_POST['addressPhone'],
            'province' => $_POST['addressProvince'],
            'city' => $_POST['addressCity'],
            'district' => $_POST['addressDistrict'],
            'zip' => $_POST['addressZip'],
            'street' => $_POST['addressStreet'],
            'details' => $_POST['addressDetails'],
            'isDefault' => isset($_POST['isDefaultAddress'])
        ];

        if ($new_address['isDefault']) {
            foreach ($all_users[$user_index]['addresses'] as &$addr) {
                $addr['isDefault'] = false;
            }
        }
        $all_users[$user_index]['addresses'][] = $new_address;
        save_users($users_file, $all_users);
        $message = 'Alamat baru berhasil disimpan!';
        // Refresh halaman untuk menampilkan data baru
        header('Location: checkout.php');
        exit;
    }

    // --- Aksi: Simpan Pembayaran ---
    if (isset($_POST['save_payment'])) {
        $new_payment = [
            'id' => 'pay_' . time(),
            'type' => $_POST['cardType'],
            'cardNumber' => str_replace(' ', '', $_POST['cardNumber']),
            'cardName' => $_POST['cardName'],
            'expiryMonth' => $_POST['expiryMonth'],
            'expiryYear' => $_POST['expiryYear'],
            // CVV tidak seharusnya disimpan! Ini hanya untuk validasi.
            'isDefault' => isset($_POST['isDefaultPayment'])
        ];
        
        if ($new_payment['isDefault']) {
            foreach ($all_users[$user_index]['payments'] as &$pay) {
                $pay['isDefault'] = false;
            }
        }
        $all_users[$user_index]['payments'][] = $new_payment;
        save_users($users_file, $all_users);
        $message = 'Metode pembayaran baru berhasil disimpan!';
        header('Location: checkout.php');
        exit;
    }

    // --- Aksi: Selesaikan Pesanan ---
    if (isset($_POST['complete_order'])) {
        $selected_address_id = $_POST['selected_address_id'] ?? null;
        $selected_payment_id = $_POST['selected_payment_id'] ?? null;

        if (empty($cart)) {
            $message = 'Keranjang Anda kosong.';
        } elseif (!$selected_address_id) {
            $message = 'Silakan pilih alamat pengiriman.';
        } elseif (!$selected_payment_id) {
            $message = 'Silakan pilih metode pembayaran.';
        } else {
            // Temukan alamat & pembayaran yang dipilih
            $final_address_index = array_search($selected_address_id, array_column($user_addresses, 'id'));
            $final_payment_index = array_search($selected_payment_id, array_column($user_payment_methods, 'id'));
            
            if ($final_address_index !== false && $final_payment_index !== false) {
                $subtotal = array_reduce($cart, fn($sum, $item) => $sum + ($item['price'] * $item['quantity']), 0);
                $shipping_cost = ($subtotal >= 100) ? 0 : 15.00;
                $total_amount = $subtotal + $shipping_cost;

                $new_order = [
                    'orderId' => 'ORDER-' . time(),
                    'date' => date('d M Y'),
                    'status' => 'Dibayar',
                    'totalAmount' => $total_amount,
                    'items' => $cart,
                    'shippingAddress' => $user_addresses[$final_address_index],
                    'paymentMethod' => ['type' => $user_payment_methods[$final_payment_index]['type'], 'cardNumber' => '**** ' . substr($user_payment_methods[$final_payment_index]['cardNumber'], -4)]
                ];

                if (!isset($all_users[$user_index]['orders'])) {
                    $all_users[$user_index]['orders'] = [];
                }
                $all_users[$user_index]['orders'][] = $new_order;
                save_users($users_file, $all_users);

                // Kosongkan keranjang dan set flag sukses
                unset($_SESSION['cart']);
                $_SESSION['paymentSuccess'] = true;
                $_SESSION['paidOrderId'] = $new_order['orderId'];

                header('Location: account.php');
                exit;
            } else {
                $message = "Alamat atau pembayaran yang dipilih tidak valid.";
            }
        }
    }
}

// Menghitung total untuk ditampilkan
$subtotal = array_reduce($cart, fn($sum, $item) => $sum + ($item['price'] * $item['quantity']), 0);
$shipping_cost = ($subtotal >= 100) ? 0.00 : 15.00; // Contoh aturan: gratis ongkir di atas $100
$grand_total = $subtotal + $shipping_cost;

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout | LUXE</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Montserrat:wght@300;400;500&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        /* [Salin semua CSS dari file HTML asli ke sini] */
        /* CSS sengaja disingkat untuk keringkasan, salin semua style dari file .html asli Anda ke sini */
        :root { --primary-color: #333; --secondary-color: #777; --accent-color: #d4af37; --light-color: #f9f9f9; --border-color: #e1e1e1; --shadow-light: 0 2px 5px rgba(0, 0, 0, 0.05); }
        body { font-family: 'Montserrat', sans-serif; }
        .order-form-section { padding: 40px 0; background-color: var(--light-color); }
        .order-form-grid { display: grid; grid-template-columns: 1fr 2fr; gap: 40px; max-width: 1200px; margin: auto; background-color: white; border-radius: 10px; box-shadow: var(--shadow-light); padding: 30px; }
        .order-summary { border-right: 1px solid var(--border-color); padding-right: 30px; }
        .order-summary .section-title { margin-bottom: 25px; font-family: 'Playfair Display', serif; }
        .order-items { max-height: 400px; overflow-y: auto; margin-bottom: 20px; padding-bottom: 15px; }
        .order-item { display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px dashed var(--border-color); }
        .order-item:last-child { border-bottom: none; }
        .order-totals div { display: flex; justify-content: space-between; margin-bottom: 10px; }
        .order-totals .total-price { font-weight: 700; font-size: 1.2em; border-top: 1px solid var(--border-color); padding-top: 15px; margin-top: 15px; }
        .shipping-payment-forms { padding-left: 30px; }
        .address-selection, .payment-selection { margin-bottom: 30px; }
        .address-options, .payment-options { display: flex; flex-direction: column; gap: 15px; }
        .address-option, .payment-option { border: 1px solid var(--border-color); border-radius: 8px; padding: 15px; cursor: pointer; transition: all 0.3s; position: relative; }
        .address-option.selected, .payment-option.selected { border-color: var(--accent-color); box-shadow: 0 0 0 2px rgba(212, 175, 55, 0.25); }
        .address-option h5, .payment-option h5 { margin: 0 0 5px 0; display: flex; align-items: center; }
        .badge { margin-left: 10px; font-size: 0.7em; background-color: var(--accent-color); color: #fff; border-radius: 4px; padding: 4px 8px; }
        .address-option p, .payment-option p { margin: 0 0 5px 0; color: var(--secondary-color); font-size: 0.9em; }
        .use-new-address button, .use-new-payment button { background: none; border: none; color: var(--accent-color); cursor: pointer; font-weight: 500; display: flex; align-items: center; gap: 8px; font-size: 1em; padding: 0; }
        .new-address-form, .new-payment-form { display: none; margin-top: 20px; }
        .new-address-form.active, .new-payment-form.active { display: block; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 500; }
        .form-group input, .form-group select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; box-sizing: border-box; }
        .form-row { display: flex; gap: 20px; } .form-row .form-group { flex: 1; }
        .btn { padding: 12px 25px; border-radius: 5px; font-size: 1.05em; cursor: pointer; text-decoration: none; display: inline-block; text-align: center; }
        .btn.proceed-btn { background-color: var(--accent-color); color: white; border: none; }
        .btn.btn-outline { background-color: transparent; color: var(--primary-color); border: 1px solid var(--border-color); }
        .form-actions { display: flex; justify-content: space-between; align-items: center; margin-top: 30px; border-top: 1px solid var(--border-color); padding-top: 20px; }
        #messageBox { display: none; position: fixed; top: 20px; left: 50%; transform: translateX(-50%); background-color: #333; color: #fff; padding: 10px 20px; border-radius: 5px; z-index: 1050; }
    </style>
</head>
<body>
    <header class="header">
        <div class="container" style="max-width: 1200px; margin: auto; display: flex; justify-content: space-between; align-items: center;">
            <a href="index.php" style="font-family: 'Playfair Display', serif; font-size: 2rem; font-weight: 700; color: #333; text-decoration: none;">LUXE</a>
            <nav>
                <ul style="display: flex; list-style: none; margin: 0; padding: 0; gap: 20px;">
                    <li><a href="index.php">Home</a></li>
                    <li><a href="account.php">My Account</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <div id="messageBox"></div>

    <section class="section order-form-section">
        <div class="container">
            <div class="order-form-grid">
                <div class="order-summary">
                    <h2 class="section-title">Order Summary</h2>
                    <div class="order-items">
                        <?php if (empty($cart)): ?>
                            <p>Keranjang Anda kosong.</p>
                        <?php else: ?>
                            <?php foreach ($cart as $item): ?>
                                <div class="order-item">
                                    <span><?php echo htmlspecialchars($item['name']); ?> x <?php echo htmlspecialchars($item['quantity']); ?></span>
                                    <span>$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></span>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <div class="order-totals">
                        <div>
                            <span>Subtotal</span>
                            <span class="subtotal-price">$<?php echo number_format($subtotal, 2); ?></span>
                        </div>
                        <div>
                            <span>Shipping</span>
                            <span class="shipping-price">$<?php echo number_format($shipping_cost, 2); ?></span>
                        </div>
                        <div class="total-price">
                            <span>Total</span>
                            <span class="grand-total-price">$<?php echo number_format($grand_total, 2); ?></span>
                        </div>
                    </div>
                </div>

                <div class="shipping-payment-forms">
                    
                    <div id="address-selection-container">
                        <h2 class="section-title">Shipping Information</h2>
                        <form method="POST" action="checkout.php" id="new-address-form" class="new-address-form">
                            <div class="form-group"><label for="addressLabel">Nama Alamat</label><input type="text" name="addressLabel" required></div>
                            <div class="form-row">
                                <div class="form-group"><label for="addressFullname">Nama Lengkap</label><input type="text" name="addressFullname" required></div>
                                <div class="form-group"><label for="addressPhone">Nomor Telepon</label><input type="tel" name="addressPhone" required></div>
                            </div>
                             <div class="form-group"><label for="addressProvince">Provinsi</label><select name="addressProvince" id="addressProvince" required></select></div>
                             <div class="form-row">
                                <div class="form-group"><label for="addressCity">Kota/Kabupaten</label><select name="addressCity" id="addressCity" required></select></div>
                                <div class="form-group"><label for="addressDistrict">Kecamatan</label><select name="addressDistrict" id="addressDistrict" required></select></div>
                            </div>
                            <div class="form-row">
                               <div class="form-group"><label for="addressZip">Kode Pos</label><input type="text" name="addressZip" required></div>
                               <div class="form-group"><label for="addressStreet">Nama Jalan</label><input type="text" name="addressStreet" required></div>
                           </div>
                            <div class="form-group"><label for="addressDetails">Detail Lainnya</label><input type="text" name="addressDetails"></div>
                            <div><input type="checkbox" name="isDefaultAddress" id="isDefaultAddress"> <label for="isDefaultAddress">Jadikan alamat utama</label></div>
                            <div style="display: flex; gap: 15px; margin-top: 15px;">
                                <button type="submit" name="save_address" class="btn proceed-btn">Simpan Alamat</button>
                                <button type="button" class="btn btn-outline" id="cancel-address-btn">Batal</button>
                            </div>
                        </form>
                        <div class="address-options">
                            <?php foreach($user_addresses as $addr): ?>
                                <div class="address-option" data-id="<?php echo $addr['id']; ?>">
                                    <h5><?php echo htmlspecialchars($addr['label']); ?><?php if($addr['isDefault']) echo '<span class="badge">Utama</span>'; ?></h5>
                                    <p><b><?php echo htmlspecialchars($addr['fullname']); ?></b> (<?php echo htmlspecialchars($addr['phone']); ?>)</p>
                                    <p><?php echo htmlspecialchars($addr['street']); ?>, <?php echo htmlspecialchars($addr['district']); ?>, <?php echo htmlspecialchars($addr['city']); ?></p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="use-new-address" style="margin-top: 15px;">
                            <button id="show-new-address-btn"><i class="fas fa-plus-circle"></i> Gunakan Alamat Baru</button>
                        </div>
                    </div>
                    
                    <div id="payment-selection-container" style="margin-top: 30px;">
                        <h2 class="section-title">Payment Method</h2>
                        <form method="POST" action="checkout.php" id="new-payment-form" class="new-payment-form">
                            <div class="form-group"><label for="cardType">Jenis Kartu</label><select name="cardType" required><option value="Visa">Visa</option><option value="Mastercard">Mastercard</option></select></div>
                             <div class="form-group"><label for="cardNumber">Nomor Kartu</label><input type="text" name="cardNumber" required></div>
                             <div class="form-row">
                                <div class="form-group"><label for="expiryMonth">Bulan</label><select name="expiryMonth" id="expiryMonth" required></select></div>
                                <div class="form-group"><label for="expiryYear">Tahun</label><select name="expiryYear" id="expiryYear" required></select></div>
                             </div>
                             <div class="form-group"><label for="cardName">Nama di Kartu</label><input type="text" name="cardName" required></div>
                             <div><input type="checkbox" name="isDefaultPayment" id="isDefaultPayment"> <label for="isDefaultPayment">Jadikan pembayaran utama</label></div>
                             <div style="display: flex; gap: 15px; margin-top: 15px;">
                                <button type="submit" name="save_payment" class="btn proceed-btn">Simpan Kartu</button>
                                <button type="button" class="btn btn-outline" id="cancel-payment-btn">Batal</button>
                            </div>
                        </form>
                         <div class="payment-options">
                            <?php foreach($user_payment_methods as $pay): ?>
                                <div class="payment-option" data-id="<?php echo $pay['id']; ?>">
                                    <h5><?php echo htmlspecialchars($pay['type']); ?> - **** <?php echo substr($pay['cardNumber'], -4); ?><?php if($pay['isDefault']) echo '<span class="badge">Utama</span>'; ?></h5>
                                    <p>a/n: <?php echo htmlspecialchars($pay['cardName']); ?></p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="use-new-payment" style="margin-top: 15px;">
                            <button id="show-new-payment-btn"><i class="fas fa-plus-circle"></i> Gunakan Kartu Baru</button>
                        </div>
                    </div>
                    
                    <form method="POST" action="checkout.php">
                        <input type="hidden" name="selected_address_id" id="selected_address_id">
                        <input type="hidden" name="selected_payment_id" id="selected_payment_id">
                        <div class="form-actions">
                            <a href="collections.php" class="btn btn-outline" style="text-decoration:none;"><i class="fas fa-arrow-left"></i> Continue Shopping</a>
                            <button type="submit" name="complete_order" class="btn proceed-btn">Complete Order</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // --- Data Lokasi untuk Dropdown Dinamis ---
    const locationData = { "Banten": { "Tangerang": ["Ciledug", "Karawaci"], "Serang": ["Taktakan", "Curug"] }, "DKI Jakarta": { "Jakarta Pusat": ["Gambir", "Senen"], "Jakarta Barat": ["Cengkareng", "Kebon Jeruk"], "Jakarta Selatan": ["Tebet", "Pasar Minggu"] }, "Jawa Barat": { "Bandung": ["Coblong", "Sukajadi"], "Bogor": ["Bogor Tengah", "Bogor Barat"] } };

    // --- Tampilkan Notifikasi dari Server ---
    const serverMessage = "<?php echo addslashes($message); ?>";
    if (serverMessage) {
        showMessageBox(serverMessage);
    }

    // --- Fungsi Bantuan UI ---
    function showMessageBox(msg) {
        const box = document.getElementById('messageBox');
        box.textContent = msg;
        box.style.display = 'block';
        setTimeout(() => { box.style.display = 'none'; }, 2500);
    }

    // --- Penanganan Formulir Baru (Alamat & Pembayaran) ---
    const newAddressForm = document.getElementById('new-address-form');
    const addressOptions = document.querySelector('#address-selection-container .address-options');
    document.getElementById('show-new-address-btn').addEventListener('click', () => {
        newAddressForm.style.display = 'block';
        addressOptions.style.display = 'none';
    });
    document.getElementById('cancel-address-btn').addEventListener('click', () => {
        newAddressForm.style.display = 'none';
        addressOptions.style.display = 'block';
    });

    const newPaymentForm = document.getElementById('new-payment-form');
    const paymentOptions = document.querySelector('#payment-selection-container .payment-options');
    document.getElementById('show-new-payment-btn').addEventListener('click', () => {
        newPaymentForm.style.display = 'block';
        paymentOptions.style.display = 'none';
    });
    document.getElementById('cancel-payment-btn').addEventListener('click', () => {
        newPaymentForm.style.display = 'none';
        paymentOptions.style.display = 'block';
    });


    // --- Logika Pemilihan Opsi (Alamat & Pembayaran) ---
    const hiddenAddressInput = document.getElementById('selected_address_id');
    document.querySelectorAll('.address-option').forEach(el => {
        el.addEventListener('click', function() {
            document.querySelectorAll('.address-option').forEach(opt => opt.classList.remove('selected'));
            this.classList.add('selected');
            hiddenAddressInput.value = this.dataset.id;
        });
    });
    
    const hiddenPaymentInput = document.getElementById('selected_payment_id');
    document.querySelectorAll('.payment-option').forEach(el => {
        el.addEventListener('click', function() {
            document.querySelectorAll('.payment-option').forEach(opt => opt.classList.remove('selected'));
            this.classList.add('selected');
            hiddenPaymentInput.value = this.dataset.id;
        });
    });

    // --- Inisialisasi Pilihan Default ---
    const defaultAddress = document.querySelector('.address-option .badge')?.closest('.address-option') || document.querySelector('.address-option');
    if (defaultAddress) defaultAddress.click();
    
    const defaultPayment = document.querySelector('.payment-option .badge')?.closest('.payment-option') || document.querySelector('.payment-option');
    if (defaultPayment) defaultPayment.click();


    // --- Logika Dropdown Tanggal & Lokasi ---
    function populateDateDropdowns() {
        const monthSelect = document.getElementById('expiryMonth');
        const yearSelect = document.getElementById('expiryYear');
        for (let i = 1; i <= 12; i++) { monthSelect.innerHTML += `<option value="${String(i).padStart(2, '0')}">${String(i).padStart(2, '0')}</option>`; }
        const currentYear = new Date().getFullYear();
        for (let i = 0; i < 15; i++) { yearSelect.innerHTML += `<option value="${currentYear + i}">${currentYear + i}</option>`; }
    }

    function populateProvinces() {
        const provinceSelect = document.getElementById('addressProvince');
        provinceSelect.innerHTML = '<option value="">Pilih Provinsi</option>';
        Object.keys(locationData).forEach(p => { provinceSelect.innerHTML += `<option value="${p}">${p}</option>`; });
    }

    const provinceSelect = document.getElementById('addressProvince');
    const citySelect = document.getElementById('addressCity');
    const districtSelect = document.getElementById('addressDistrict');

    provinceSelect.addEventListener('change', function() {
        citySelect.innerHTML = '<option value="">Pilih Kota/Kabupaten</option>';
        districtSelect.innerHTML = '<option value="">Pilih Kecamatan</option>';
        if (this.value && locationData[this.value]) {
            Object.keys(locationData[this.value]).forEach(c => { citySelect.innerHTML += `<option value="${c}">${c}</option>`; });
        }
    });

    citySelect.addEventListener('change', function() {
        districtSelect.innerHTML = '<option value="">Pilih Kecamatan</option>';
        const prov = provinceSelect.value;
        if (prov && this.value && locationData[prov][this.value]) {
            locationData[prov][this.value].forEach(d => { districtSelect.innerHTML += `<option value="${d}">${d}</option>`; });
        }
    });
    
    populateDateDropdowns();
    populateProvinces();
});
</script>
</body>
</html>