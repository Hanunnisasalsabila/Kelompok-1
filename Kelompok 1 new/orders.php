<?php
session_start();
require_once 'db_connection.php'; // Hubungkan ke database

// ========================================================================
// 1. BLOK KEAMANAN & LOGIKA PHP
// ========================================================================

// --- Pemeriksaan Keamanan: Pastikan hanya admin yang bisa mengakses halaman ini ---
if (!isset($_SESSION['isLoggedIn']) || !isset($_SESSION['userRole']) || strtolower($_SESSION['userRole']) !== 'admin') {
    header('Location: login.php');
    exit;
}

// --- Pengambilan Data Pesanan dari Database ---
$orders_query = "
    SELECT 
        o.id as order_id, 
        o.order_date, 
        o.total_amount, 
        o.order_status, 
        u.fullname as customer_name,
        u.email as customer_email,
        ua.recipient_name,
        ua.phone_number,
        ua.street_address,
        ua.city,
        ua.province,
        ua.postal_code
    FROM orders o
    JOIN users u ON o.user_id = u.id
    JOIN user_addresses ua ON o.shipping_address_id = ua.id
    ORDER BY o.order_date DESC
";
$orders_result = $conn->query($orders_query);
$all_orders = $orders_result->fetch_all(MYSQLI_ASSOC);

// Ambil item untuk setiap pesanan
$order_items = [];
if (!empty($all_orders)) {
    $order_ids = array_column($all_orders, 'order_id');
    $items_query = "
        SELECT oi.*, p.name as product_name, p.image_url 
        FROM order_items oi
        JOIN products p ON oi.product_id = p.id
        WHERE oi.order_id IN (". implode(',', $order_ids) .")
    ";
    $items_result = $conn->query($items_query);
    while ($item = $items_result->fetch_assoc()) {
        $order_items[$item['order_id']][] = $item;
    }
}

// --- Fungsi Pembantu ---
function getStatusClass($status) {
    $statusMap = [
        'paid' => 'processing', 'shipped' => 'shipped', 'completed' => 'delivered', 'cancelled' => 'cancelled'
    ];
    return $statusMap[strtolower($status)] ?? 'pending';
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Management | LUXE ADMIN</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Montserrat:wght@400;500&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        :root { --primary-color: #333; --secondary-color: #666; --light-color: #f4f4f4; --border-color: #ddd; }
        body { background-color: #f8f8f8; font-family: 'Montserrat', sans-serif; }
        .admin-container { display: grid; grid-template-columns: 250px 1fr; min-height: 100vh; }
        .admin-sidebar { background-color: var(--primary-color); color: #fff; padding: 2rem 0; }
        .admin-nav a { display: block; padding: 1rem 1.5rem; color: rgba(255, 255, 255, 0.8); text-decoration: none; transition: all 0.3s ease; }
        .admin-nav a.active, .admin-nav a:hover { color: #fff; background-color: rgba(255, 255, 255, 0.1); }
        .admin-nav i { width: 2rem; margin-right: 1rem; text-align: center; }
        .admin-main { padding: 2rem; }
        .admin-table { width: 100%; background-color: #fff; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.07); border-collapse: collapse; overflow: hidden; }
        .admin-table th, .admin-table td { padding: 1rem 1.5rem; text-align: left; border-bottom: 1px solid var(--border-color); }
        .status { padding: 0.5rem 1rem; border-radius: 2rem; font-size: 0.8rem; font-weight: 500;}
        .status.processing { background-color: #cce5ff; color: #004085; }
        .status.shipped { background-color: #d1ecf1; color: #0c5460; }
        .status.delivered { background-color: #d4edda; color: #155724; }
        .status.cancelled { background-color: #f8d7da; color: #721c24; }
        /* CSS custom untuk modal sudah dihapus untuk menghindari konflik */
    </style>
</head>
<body>
    <div class="admin-container">
        <aside class="admin-sidebar">
            <div class="admin-logo" style="text-align:center; padding: 0 1rem 2rem 1rem;"><h3>LUXE ADMIN</h3></div>
            <nav class="admin-nav">
                <ul>
                    <li><a href="dashboardadmin.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                    <li><a href="products.php"><i class="fas fa-shopping-bag"></i> Products</a></li>
                    <li><a href="orders.php" class="active"><i class="fas fa-receipt"></i> Orders</a></li>
                    <li><a href="settings.php"><i class="fas fa-cog"></i> Settings</a></li>
                    <li><a href="account.php?action=logout"><i class="fas fa-sign-out-alt"></i> Log Out</a></li>
                </ul>
            </nav>
        </aside>
        
        <main class="admin-main">
            <header class="admin-header"><h1 class="admin-title">Order Management</h1></header>
            
            <table class="admin-table" id="ordersTable">
                <thead>
                    <tr><th>Order ID</th><th>Customer</th><th>Date</th><th>Amount</th><th>Status</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    <?php if (empty($all_orders)): ?>
                        <tr><td colspan="6" style="text-align: center;">No orders found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($all_orders as $order): ?>
                            <tr>
                                <td>#<?php echo htmlspecialchars($order['order_id']); ?></td>
                                <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                <td><?php echo date('d M Y, H:i', strtotime($order['order_date'])); ?></td>
                                <td>$<?php echo number_format($order['total_amount'], 2); ?></td>
                                <td><span class="status <?php echo getStatusClass($order['order_status']); ?>"><?php echo htmlspecialchars(ucfirst($order['order_status'])); ?></span></td>
                                <td><button class="btn btn-sm btn-outline-dark view-order-btn" data-bs-toggle="modal" data-bs-target="#orderModal" data-order='<?php echo json_encode($order, JSON_HEX_APOS); ?>' data-items='<?php echo json_encode($order_items[$order['order_id']] ?? [], JSON_HEX_APOS); ?>'>View</button></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </main>
    </div>
    
    <!-- Modal untuk Detail Pesanan -->
    <div class="modal fade" id="orderModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalOrderTitle">Order Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Customer Details</h6>
                            <p class="mb-1"><strong>Name:</strong> <span id="modalCustomerName"></span></p>
                            <p class="mb-1"><strong>Email:</strong> <span id="modalCustomerEmail"></span></p>
                        </div>
                        <div class="col-md-6">
                            <h6>Shipping Address</h6>
                            <address id="modalShippingAddress" class="mb-0" style="white-space: pre-line;"></address>
                        </div>
                    </div>
                    <hr>
                    <h6>Items Ordered</h6>
                    <table class="table table-sm">
                        <thead>
                            <tr><th>Product</th><th>Quantity</th><th class="text-end">Subtotal</th></tr>
                        </thead>
                        <tbody id="modalOrderItems">
                            <!-- Item rows will be injected by JavaScript -->
                        </tbody>
                    </table>
                    <div class="d-flex justify-content-end">
                        <h5 class="me-3">Grand Total:</h5>
                        <h5 id="modalGrandTotal"></h5>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const orderModalEl = document.getElementById('orderModal');
        
        orderModalEl.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget; // Tombol yang memicu modal
            
            // Ambil data dari atribut tombol
            const orderData = JSON.parse(button.getAttribute('data-order'));
            const itemsData = JSON.parse(button.getAttribute('data-items'));

            // Dapatkan elemen-elemen di dalam modal
            const modalTitle = orderModalEl.querySelector('.modal-title');
            const customerName = orderModalEl.querySelector('#modalCustomerName');
            const customerEmail = orderModalEl.querySelector('#modalCustomerEmail');
            const shippingAddress = orderModalEl.querySelector('#modalShippingAddress');
            const grandTotal = orderModalEl.querySelector('#modalGrandTotal');
            const itemsTbody = orderModalEl.querySelector('#modalOrderItems');

            // Isi modal dengan data
            modalTitle.textContent = `Order Details #${orderData.order_id}`;
            customerName.textContent = orderData.customer_name;
            customerEmail.textContent = orderData.customer_email;
            
            const address = `${orderData.recipient_name}\n${orderData.phone_number}\n${orderData.street_address}\n${orderData.city}, ${orderData.province} ${orderData.postal_code}`;
            shippingAddress.textContent = address;
            
            grandTotal.textContent = `$${parseFloat(orderData.total_amount).toFixed(2)}`;

            // Isi tabel item
            itemsTbody.innerHTML = ''; // Kosongkan tabel sebelum diisi
            if (itemsData.length > 0) {
                itemsData.forEach(item => {
                    const row = itemsTbody.insertRow();
                    const subtotal = (item.quantity * item.price).toFixed(2);
                    row.innerHTML = `
                        <td>${item.product_name}</td>
                        <td>${item.quantity}</td>
                        <td class="text-end">$${subtotal}</td>
                    `;
                });
            } else {
                itemsTbody.innerHTML = '<tr><td colspan="3">No items found for this order.</td></tr>';
            }
        });
    });
    </script>
</body>
</html>
