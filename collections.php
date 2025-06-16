<?php
session_start();
require_once 'db_connection.php'; // Mengimpor koneksi $conn

// ========================================================================
// 1. BLOK DATA & LOGIKA PHP
// ========================================================================

$message = ''; // Untuk notifikasi

// --- Logika Keranjang Belanja (Add to Cart) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    $product_id = (int)$_POST['product_id'];
    $quantity = 1;

    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    
    if (isset($_SESSION['cart'][$product_id])) {
        $_SESSION['cart'][$product_id]['quantity'] += $quantity;
        $product_name = $_SESSION['cart'][$product_id]['name'];
        $message = htmlspecialchars($product_name) . " quantity updated in bag.";
    } else {
        $stmt = $conn->prepare("SELECT id, name, price, image_url FROM products WHERE id = ?");
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $product_to_add = $result->fetch_assoc();
        $stmt->close();
        
        if ($product_to_add) {
            $_SESSION['cart'][$product_id] = [
                'id' => $product_to_add['id'], 'name' => $product_to_add['name'],
                'price' => $product_to_add['price'], 'image' => $product_to_add['image_url'],
                'quantity' => $quantity
            ];
            $message = htmlspecialchars($product_to_add['name']) . " added to your bag!";
        } else {
             $message = "Product not found!";
        }
    }
    
    // Alihkan ke URL yang sama dengan filter yang aktif dan sertakan pesan
    $redirect_url = $_SERVER['PHP_SELF'];
    if (isset($_GET['filter'])) {
        $redirect_url .= '?filter=' . urlencode($_GET['filter']);
    }
    $separator = (strpos($redirect_url, '?') === false) ? '?' : '&';
    header('Location: ' . $redirect_url . $separator . 'message=' . urlencode($message));
    exit;
}

// --- Logika Filter & Ambil Produk dari DB ---
$filter = $_GET['filter'] ?? 'all';
$page_title = 'All Products';

// Query dasar untuk mengambil produk dan nama kategorinya
$sql = "SELECT p.*, c.name as category_name 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id";
$where_clauses = [];
$params = [];
$types = '';

// Bangun klausa WHERE berdasarkan filter
switch ($filter) {
    case 'new-arrivals':
        $where_clauses[] = "p.is_new_arrival = 1";
        $page_title = 'New Arrivals';
        break;
    case 'best-seller':
        $where_clauses[] = "p.is_best_seller = 1";
        $page_title = 'Best Sellers';
        break;
    case 'dress':
    case 'top':
    case 'bottom':
    case 'accessory':
        // Asumsi nama kategori di DB adalah 'Dress', 'Top', dst.
        $where_clauses[] = "c.name = ?";
        $types .= 's';
        $params[] = ucfirst($filter);
        $page_title = ucfirst($filter) . 'es';
        break;
}

if (!empty($where_clauses)) {
    $sql .= " WHERE " . implode(" AND ", $where_clauses);
}
$sql .= " ORDER BY created_at DESC";

// Eksekusi query dengan prepared statement
$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$products_result = $stmt->get_result();
$display_products = $products_result->fetch_all(MYSQLI_ASSOC);
$stmt->close();


// Ambil pesan notifikasi dari URL jika ada
if(isset($_GET['message'])) {
    $message = htmlspecialchars($_GET['message']);
}

// Hitung jumlah item di keranjang
$cart_item_count = isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Collections | LUXE</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        .toast-notification {
            position: fixed;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            background-color: #333;
            color: white;
            padding: 12px 25px;
            border-radius: 25px;
            z-index: 1000;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.5s, visibility 0.5s, transform 0.5s;
        }
        .toast-notification.show {
            opacity: 1;
            visibility: visible;
            transform: translate(-50%, -10px);
        }
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
                        <li><a href="collections.php" class="active">Collections</a></li>
                        <li><a href="about.php">About</a></li>
                        <li><a href="contact.php">Contact</a></li>
                    </ul>
                </nav>
                <div class="nav-actions">
                    <a href="account.php" class="account-btn"><i class="far fa-user"></i></a>
                    <a href="checkout.php" class="cart-btn" id="cart-btn">
                        <i class="fas fa-shopping-bag"></i>
                        <span class="cart-count"><?php echo $cart_item_count; ?></span>
                    </a>
                    <button class="menu-btn" id="menu-btn"><i class="fas fa-bars"></i></button>
                </div>
            </div>
        </div>
    </header>

    <section class="page-hero">
        <div class="container">
            <h1 class="page-title">Our Collections</h1>
            <p class="page-subtitle">Discover timeless pieces for every occasion</p>
        </div>
    </section>

    <section class="collections-filter">
        <div class="container">
             <div class="filter-inner">
                <div class="filter-left">
                    <span>Filter By:</span>
                    <a href="collections.php?filter=all" class="btn btn-sm <?php echo $filter === 'all' ? 'btn-dark' : 'btn-outline-dark'; ?>">All</a>
                    <a href="collections.php?filter=new-arrivals" class="btn btn-sm <?php echo $filter === 'new-arrivals' ? 'btn-dark' : 'btn-outline-dark'; ?>">New Arrivals</a>
                    <a href="collections.php?filter=best-seller" class="btn btn-sm <?php echo $filter === 'best-seller' ? 'btn-dark' : 'btn-outline-dark'; ?>">Best Seller</a>
                    <a href="collections.php?filter=dress" class="btn btn-sm <?php echo $filter === 'dress' ? 'btn-dark' : 'btn-outline-dark'; ?>">Dresses</a>
                    <a href="collections.php?filter=top" class="btn btn-sm <?php echo $filter === 'top' ? 'btn-dark' : 'btn-outline-dark'; ?>">Tops</a>
                </div>
            </div>
        </div>
    </section>

    <section class="section collection-section" id="product-list">
        <div class="container">
            <h2 class="section-title"><?php echo htmlspecialchars($page_title); ?></h2>

            <div class="product-grid">
                <?php if (empty($display_products)): ?>
                    <p class="text-center w-100">No products found in this category.</p>
                <?php else: ?>
                    <?php foreach ($display_products as $product): ?>
                    <div class="product-card" data-id="<?php echo $product['id']; ?>">
                        <div class="product-image">
                            <img src="<?php echo htmlspecialchars($product['image_url']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                            <?php if ($product['is_new_arrival']): ?>
                                <div class="product-badge">New</div>
                            <?php endif; ?>
                        </div>
                        <div class="product-info">
                            <h3 class="product-title"><?php echo htmlspecialchars($product['name']); ?></h3>
                            <p class="product-price">
                                <?php if (!empty($product['original_price'])): ?>
                                    <span class="original-price">$<?php echo number_format($product['original_price'], 2); ?></span>
                                <?php endif; ?>
                                $<?php echo number_format($product['price'], 2); ?>
                            </p>
                            <form method="POST" action=""> <!-- Action dikosongkan agar parameter filter tetap ada di URL -->
                                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                <button type="submit" name="add_to_cart" class="add-to-cart">Add to Bag</button>
                            </form>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <footer class="footer"></footer>
    
    <!-- Elemen untuk notifikasi -->
    <div id="toast-notification" class="toast-notification"></div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Script untuk menampilkan notifikasi dari server
        const message = "<?php echo addslashes($message); ?>";
        if (message) {
            const toast = document.getElementById('toast-notification');
            if(toast) {
                toast.textContent = message;
                toast.classList.add('show');
                setTimeout(() => {
                    toast.classList.remove('show');
                }, 3000);
            }
        }
    });
    </script>
</body>
</html>
