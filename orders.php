
<?php
session_start();
require_once 'db_connection.php'; // Ini akan mengimpor variabel $conn

// ... sisa kode Anda bisa menggunakan variabel $conn untuk query ...
// Contoh: $result = $conn->query("SELECT * FROM users");
// Selalu mulai session di baris paling atas


// ========================================================================
// 1. BLOK LOGIKA & KEAMANAN PHP
// ========================================================================

// --- Pemeriksaan Keamanan: Pastikan hanya admin yang bisa mengakses halaman ini ---
if (!isset($_SESSION['isLoggedIn']) || !isset($_SESSION['userRole']) || $_SESSION['userRole'] !== 'admin') {
    // Jika tidak login atau bukan admin, alihkan ke halaman login
    header('Location: login.php');
    exit;
}

// --- Fungsi Pembantu untuk mendapatkan data pengguna ---
function get_users($file = 'users.json') {
    if (!file_exists($file)) return [];
    return json_decode(file_get_contents($file), true) ?: [];
}

// --- Pengambilan dan Pemrosesan Data Pesanan ---
$all_users = get_users();
$all_orders = [];

// Gabungkan semua pesanan dari semua pengguna menjadi satu array
foreach ($all_users as $user) {
    if (!empty($user['orders'])) {
        foreach ($user['orders'] as $order) {
            // Tambahkan informasi pelanggan ke setiap objek pesanan untuk kemudahan akses
            $order['customer_name'] = $user['fullname'] ?? $user['username'];
            $order['customer_email'] = $user['email'];
            $order['customer_phone'] = $user['phone'] ?? 'N/A';
            $all_orders[] = $order;
        }
    }
}

// Urutkan pesanan berdasarkan tanggal, dari yang terbaru ke yang terlama
// Menggunakan 'usort' untuk perbandingan kustom
usort($all_orders, function($a, $b) {
    // Mengubah tanggal string menjadi timestamp untuk perbandingan yang akurat
    return strtotime($b['date']) - strtotime($a['date']);
});

// Fungsi untuk mendapatkan kelas CSS berdasarkan status pesanan
function getStatusClass($status) {
    $statusMap = [
        'Keranjang' => 'pending', 'Dibayar' => 'processing', 'Diproses' => 'processing',
        'Dikirim' => 'shipped', 'Terkirim' => 'delivered', 'Dibatalkan' => 'cancelled'
    ];
    return $statusMap[$status] ?? 'pending';
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Management | LUXE ADMIN</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Montserrat:wght@300;400;500&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        /* [Salin semua CSS dari file HTML asli ke sini] */
        /* CSS sengaja disingkat untuk keringkasan, salin semua style dari file .html asli Anda ke sini */
        :root { --primary-color: #333; --secondary-color: #666; --light-color: #f4f4f4; --border-color: #ddd; --font-heading: 'Playfair Display', serif; }
        .admin-container { display: grid; grid-template-columns: 250px 1fr; min-height: 100vh; }
        .admin-sidebar { background-color: var(--primary-color); color: #fff; padding: 2rem 0; }
        .admin-logo a { font-family: var(--font-heading); font-size: 2rem; color: #fff; text-decoration: none; }
        .admin-nav ul { list-style: none; padding: 0; } .admin-nav a { display: block; padding: 1rem 1.5rem; color: rgba(255, 255, 255, 0.8); text-decoration: none; }
        .admin-nav a.active, .admin-nav a:hover { color: #fff; background-color: rgba(255, 255, 255, 0.1); }
        .admin-nav i { width: 2rem; margin-right: 1rem; text-align: center; }
        .admin-main { padding: 2rem; background-color: #f8f8f8; }
        .admin-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
        .admin-title { font-size: 2.4rem; font-weight: 600; }
        .admin-table { width: 100%; background-color: #fff; border-radius: 0.5rem; overflow: hidden; box-shadow: 0 0.5rem 1.5rem rgba(0,0,0,0.05); border-collapse: collapse; }
        .admin-table th, .admin-table td { padding: 1.5rem; text-align: left; border-bottom: 1px solid var(--border-color); }
        .admin-table th { background-color: var(--light-color); font-weight: 600; }
        .status { padding: 0.5rem 1rem; border-radius: 2rem; font-size: 1.2rem; font-weight: 500; }
        .status.pending { background-color: #fff3cd; color: #856404; }
        .status.processing { background-color: #cce5ff; color: #004085; }
        .status.shipped { background-color: #d1ecf1; color: #0c5460; }
        .status.delivered { background-color: #d4edda; color: #155724; }
        .status.cancelled { background-color: #f8d7da; color: #721c24; }
        .btn { padding: 0.5rem 1rem; font-size: 1.2rem; border-radius: 0.3rem; cursor: pointer; border: 1px solid var(--border-color); background-color: transparent; }
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.5); justify-content: center; align-items: center; }
        .modal-content { background-color: #fff; margin: auto; padding: 3rem; border-radius: 0.5rem; max-width: 900px; width: 90%; }
        .modal-header { display: flex; justify-content: space-between; align-items: center; padding-bottom: 1rem; border-bottom: 1px solid var(--border-color); margin-bottom: 2rem; }
        .close-modal { font-size: 2.5rem; border: none; background: none; cursor: pointer; }
        .order-details { display: grid; grid-template-columns: 1fr 1fr; gap: 3rem; }
        .order-section-title { font-weight: 600; border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem; margin-bottom: 1rem; }
        .order-info { display: grid; grid-template-columns: 120px 1fr; gap: 0.5rem 1rem; margin-bottom: 0.5rem; }
        .order-info-label { font-weight: 500; color: var(--secondary-color); }
        .order-items { width: 100%; border-collapse: collapse; margin-top: 1rem; }
        .order-items th, .order-items td { text-align: left; padding: 1rem; border-bottom: 1px solid var(--border-color); }
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
                    <li><a href="orders.php" class="active"><i class="fas fa-receipt"></i> Orders</a></li>
                    <li><a href="settings.php"><i class="fas fa-cog"></i> Settings</a></li>
                    <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Log Out</a></li>
                </ul>
            </nav>
        </aside>
        
        <main class="admin-main">
            <header class="admin-header"><h1 class="admin-title">Order Management</h1></header>
            
            <table class="admin-table" id="ordersTable">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Customer</th>
                        <th>Date</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($all_orders)): ?>
                        <tr><td colspan="6" style="text-align: center;">No orders found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($all_orders as $order): ?>
                            <tr>
                                <td>#<?php echo htmlspecialchars($order['orderId']); ?></td>
                                <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                <td><?php echo htmlspecialchars($order['date']); ?></td>
                                <td>$<?php echo number_format($order['totalAmount'] ?? 0, 2); ?></td>
                                <td><span class="status <?php echo getStatusClass($order['status']); ?>"><?php echo htmlspecialchars($order['status']); ?></span></td>
                                <td>
                                    <button class="btn view-order-btn" data-order='<?php echo json_encode($order, JSON_HEX_APOS); ?>'>
                                        <i class="fas fa-eye"></i> View
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </main>
    </div>
    
    <div class="modal" id="orderModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title" id="modalOrderId"></h2>
                <button class="close-modal">&times;</button>
            </div>
            <div class="order-details">
                <div>
                    <div class="order-section"><h3 class="order-section-title">Customer Information</h3>
                        <div class="order-info"><span class="order-info-label">Name:</span><span id="modalCustomerName"></span></div>
                        <div class="order-info"><span class="order-info-label">Email:</span><span id="modalCustomerEmail"></span></div>
                        <div class="order-info"><span class="order-info-label">Phone:</span><span id="modalCustomerPhone"></span></div>
                    </div>
                    <div class="order-section"><h3 class="order-section-title">Shipping Address</h3>
                        <div id="modalShippingAddress"></div>
                    </div>
                </div>
                <div>
                    <div class="order-section"><h3 class="order-section-title">Order Information</h3>
                         <div class="order-info"><span class="order-info-label">Date:</span><span id="modalOrderDate"></span></div>
                         <div class="order-info"><span class="order-info-label">Status:</span><span id="modalOrderStatus"></span></div>
                    </div>
                    <div class="order-section"><h3 class="order-section-title">Order Items</h3>
                        <table class="order-items"><thead><tr><th>Product</th><th>Qty</th><th>Total</th></tr></thead><tbody id="modalOrderItems"></tbody></table>
                        <div class="order-summary"><div class="summary-row summary-total"><span>Total:</span><span id="modalGrandTotal"></span></div></div>
                    </div>
                </div>
            </div>
            <div class="order-actions" style="justify-content: flex-end; display: flex; margin-top: 2rem;">
                <button class="btn close-modal">Close</button>
            </div>
        </div>
    </div>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const orderModal = document.getElementById('orderModal');
        const closeModalBtns = document.querySelectorAll('.close-modal');
        const viewOrderBtns = document.querySelectorAll('.view-order-btn');

        // Fungsi untuk menampilkan modal
        function showModal(orderData) {
            // Isi data modal dari objek 'orderData'
            document.getElementById('modalOrderId').textContent = `Order #${orderData.orderId}`;
            
            // Info Pelanggan
            document.getElementById('modalCustomerName').textContent = orderData.customer_name || 'N/A';
            document.getElementById('modalCustomerEmail').textContent = orderData.customer_email || 'N/A';
            document.getElementById('modalCustomerPhone').textContent = orderData.customer_phone || 'N/A';

            // Info Pesanan
            document.getElementById('modalOrderDate').textContent = orderData.date || 'N/A';
            const statusSpan = document.createElement('span');
            statusSpan.className = `status ${orderData.status ? orderData.status.toLowerCase() : 'pending'}`; // Mengambil kelas status dari data
            statusSpan.textContent = orderData.status || 'N/A';
            document.getElementById('modalOrderStatus').innerHTML = ''; // Hapus konten lama
            document.getElementById('modalOrderStatus').appendChild(statusSpan);

            // Alamat Pengiriman
            const addrContainer = document.getElementById('modalShippingAddress');
            addrContainer.innerHTML = '';
            if (orderData.shippingAddress) {
                const addr = orderData.shippingAddress;
                addrContainer.innerHTML = `
                    <div class="order-info"><span class="order-info-label">Recipient:</span><span>${addr.fullname || 'N/A'}</span></div>
                    <div class="order-info"><span class="order-info-label">Address:</span><span>${addr.street || ''}, ${addr.district || ''}, ${addr.city || ''}</span></div>
                    <div class="order-info"><span class="order-info-label">Phone:</span><span>${addr.phone || 'N/A'}</span></div>`;
            } else {
                addrContainer.innerHTML = '<p>Shipping address not available.</p>';
            }

            // Item Pesanan
            const itemsTbody = document.getElementById('modalOrderItems');
            itemsTbody.innerHTML = '';
            if (orderData.items && Array.isArray(orderData.items)) {
                orderData.items.forEach(item => {
                    const row = itemsTbody.insertRow();
                    row.innerHTML = `<td>${item.name}</td><td>${item.quantity}</td><td>$${(item.price * item.quantity).toFixed(2)}</td>`;
                });
            }

            // Total
            document.getElementById('modalGrandTotal').textContent = `$${(orderData.totalAmount || 0).toFixed(2)}`;

            orderModal.style.display = 'flex';
        }

        // Event listener untuk setiap tombol 'View'
        viewOrderBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const orderData = JSON.parse(this.getAttribute('data-order'));
                showModal(orderData);
            });
        });

        // Event listener untuk menutup modal
        closeModalBtns.forEach(btn => btn.addEventListener('click', () => orderModal.style.display = 'none'));
        window.addEventListener('click', (e) => {
            if (e.target === orderModal) orderModal.style.display = 'none';
        });
    });
    </script>
</body>
</html>