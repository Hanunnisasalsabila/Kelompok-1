<?php
session_start();
require_once 'db_connection.php'; // Mengimpor koneksi $conn

// ========================================================================
// 1. BLOK KEAMANAN & PENGAMBILAN DATA
// ========================================================================

// --- Pemeriksaan Keamanan: Pastikan hanya admin yang login yang bisa mengakses ---
if (!isset($_SESSION['isLoggedIn']) || !isset($_SESSION['userRole']) || $_SESSION['userRole'] !== 'admin') {
    // Jika tidak login atau bukan admin, alihkan ke halaman login
    header('Location: login.php');
    exit;
}

// Ambil data admin yang sedang login dari session
$admin_id = $_SESSION['userId'];
$stmt = $conn->prepare("SELECT fullname, profile_picture_url FROM users WHERE id = ?");
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$admin_user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// --- PENGAMBILAN DATA UNTUK KARTU STATISTIK DARI DATABASE ---

// Total Pendapatan (dari pesanan yang statusnya tidak 'cancelled')
$revenue_result = $conn->query("SELECT SUM(total_amount) as total FROM orders WHERE order_status != 'cancelled'");
$total_revenue = $revenue_result->fetch_assoc()['total'] ?? 0;

// Total Pesanan
$orders_count_result = $conn->query("SELECT COUNT(id) as total FROM orders");
$total_orders = $orders_count_result->fetch_assoc()['total'] ?? 0;

// Pelanggan Baru (diasumsikan semua pengguna dengan role 'user')
$customers_count_result = $conn->query("SELECT COUNT(id) as total FROM users WHERE role = 'user'");
$new_customers = $customers_count_result->fetch_assoc()['total'] ?? 0;

// Tingkat Konversi ( (Total Pesanan / Total Pelanggan) * 100 )
$conversion_rate = ($new_customers > 0) ? ($total_orders / $new_customers) * 100 : 0;


// --- PENGAMBILAN DATA UNTUK TABEL PESANAN TERBARU DARI DATABASE ---
$recent_orders_query = "
    SELECT o.id, o.order_date, o.total_amount, o.order_status, u.fullname as customer_name
    FROM orders o
    JOIN users u ON o.user_id = u.id
    ORDER BY o.order_date DESC
    LIMIT 5
";
$recent_orders_result = $conn->query($recent_orders_query);
$recent_orders = $recent_orders_result->fetch_all(MYSQLI_ASSOC);


// --- FUNGSI BANTUAN ---
function format_currency($amount, $currency_symbol = '$') {
    return $currency_symbol . number_format($amount, 2);
}

function get_status_display($status) {
    $status_class = 'pending'; // default
    switch (strtolower($status)) {
        case 'completed': $status_class = 'completed'; break;
        case 'paid':
        case 'shipped': $status_class = 'processing'; break; // Anda bisa buat kelas 'shipped' atau 'paid' sendiri
        case 'cancelled': $status_class = 'cancelled'; break;
    }
    return ['class' => $status_class, 'text' => ucfirst($status)];
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | LUXE</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Montserrat:wght@400;500&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        :root { --primary-color: #333; --secondary-color: #666; --light-color: #f4f4f4; --border-color: #ddd; --font-heading: 'Playfair Display', serif; }
        .admin-container { display: grid; grid-template-columns: 250px 1fr; min-height: 100vh; }
        .admin-sidebar { background-color: var(--primary-color); color: #fff; padding: 2rem 0; }
        .admin-logo a { font-family: var(--font-heading); font-size: 2rem; font-weight: 700; color: #fff; text-decoration: none; }
        .admin-nav ul { list-style: none; padding: 0; } .admin-nav a { display: block; padding: 1rem 1.5rem; color: rgba(255, 255, 255, 0.8); text-decoration: none; transition: all 0.3s ease; }
        .admin-nav a.active, .admin-nav a:hover { color: #fff; background-color: rgba(255, 255, 255, 0.1); }
        .admin-nav i { width: 2rem; margin-right: 1rem; text-align: center; }
        .admin-main { padding: 2rem; background-color: #f8f8f8; }
        .admin-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 3rem; padding-bottom: 1.5rem; border-bottom: 1px solid var(--border-color); }
        .admin-title { font-size: 2.4rem; font-weight: 600; }
        .admin-user img { width: 4rem; height: 4rem; border-radius: 50%; object-fit: cover; }
        .admin-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 2rem; margin-bottom: 3rem; }
        .admin-card { background-color: #fff; padding: 2rem; border-radius: 0.5rem; box-shadow: 0 0.5rem 1.5rem rgba(0, 0, 0, 0.05); }
        .admin-card-title { font-size: 1.4rem; color: var(--secondary-color); margin-bottom: 1rem; }
        .admin-card-value { font-size: 2.4rem; font-weight: 600; margin-bottom: 1rem; }
        .admin-table { width: 100%; border-collapse: collapse; background-color: #fff; border-radius: 0.5rem; overflow: hidden; box-shadow: 0 0.5rem 1.5rem rgba(0,0,0,0.05); }
        .admin-table th, .admin-table td { padding: 1.5rem; text-align: left; border-bottom: 1px solid var(--border-color); }
        .status { display: inline-block; padding: 0.5rem 1rem; border-radius: 2rem; font-size: 1.2rem; font-weight: 500; }
        .status.pending { background-color: #fff3cd; color: #856404; }
        .status.processing { background-color: #cce5ff; color: #004085; }
        .status.completed { background-color: #d4edda; color: #155724; }
        .status.cancelled { background-color: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
    <div class="admin-container">
        <aside class="admin-sidebar">
            <div class="admin-logo" style="text-align: center; margin-bottom: 3rem; padding: 0 1.5rem;">
                <a href="dashboardadmin.php">LUXE ADMIN</a>
            </div>
            
            <nav class="admin-nav">
                <ul>
                    <li><a href="dashboardadmin.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                    <li><a href="products.php"><i class="fas fa-shopping-bag"></i> Products</a></li>
                    <li><a href="orders.php"><i class="fas fa-receipt"></i> Orders</a></li>
                    <li><a href="settings.php"><i class="fas fa-receipt"></i> Settings</a></li>
                    <li><a href="account.php?action=logout"><i class="fas fa-sign-out-alt"></i> Log Out</a></li>
                </ul>
            </nav>
        </aside>
        
        <main class="admin-main">
            <header class="admin-header">
                <h1 class="admin-title">Dashboard</h1>
                <div class="admin-user" style="display: flex; align-items: center;">
                    <img src="<?php echo htmlspecialchars($admin_user['profile_picture_url'] ?? 'https://placehold.co/40x40?text=A'); ?>" alt="Admin">
                    <span style="margin-left: 1rem;"><?php echo htmlspecialchars($admin_user['fullname']); ?></span>
                </div>
            </header>
            
            <div class="admin-cards">
                <div class="admin-card">
                    <h3 class="admin-card-title">Total Revenue</h3>
                    <p class="admin-card-value"><?php echo format_currency($total_revenue); ?></p>
                </div>
                <div class="admin-card">
                    <h3 class="admin-card-title">Total Orders</h3>
                    <p class="admin-card-value"><?php echo number_format($total_orders); ?></p>
                </div>
                <div class="admin-card">
                    <h3 class="admin-card-title">New Customers</h3>
                    <p class="admin-card-value"><?php echo number_format($new_customers); ?></p>
                </div>
                <div class="admin-card">
                    <h3 class="admin-card-title">Conversion Rate</h3>
                    <p class="admin-card-value"><?php echo number_format($conversion_rate, 2); ?>%</p>
                </div>
            </div>
            
            <h2 style="margin-bottom: 2rem; font-size: 2rem;">Recent Orders</h2>
            <div class="admin-table-container">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Date</th>
                            <th>Amount</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recent_orders)): ?>
                            <tr><td colspan="5" style="text-align: center;">No recent orders found.</td></tr>
                        <?php else: ?>
                            <?php foreach ($recent_orders as $order): ?>
                                <?php $status_info = get_status_display($order['order_status']); ?>
                                <tr>
                                    <td>#<?php echo htmlspecialchars($order['id']); ?></td>
                                    <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($order['order_date'])); ?></td>
                                    <td><?php echo format_currency($order['total_amount']); ?></td>
                                    <td><span class="status <?php echo $status_info['class']; ?>"><?php echo htmlspecialchars($status_info['text']); ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</body>
</html>
