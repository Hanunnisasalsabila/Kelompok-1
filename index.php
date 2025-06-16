<?php
session_start();
require_once 'db_connection.php'; // Mengimpor koneksi $conn

// ========================================================================
// 1. BLOK DATA & LOGIKA PHP
// ========================================================================

// --- AMBIL PRODUK UNGGULAN DARI DATABASE ---
// Mengambil 4 produk terbaru sebagai produk unggulan
$products_result = $conn->query("SELECT * FROM products ORDER BY created_at DESC LIMIT 4");
$featured_products = [];
if ($products_result) {
    while($row = $products_result->fetch_assoc()) {
        $featured_products[] = $row;
    }
}

// --- Data untuk "Featured Collections" (Bisa dibiarkan hardcoded atau dibuat dinamis) ---
$featured_collections = [
    ['title' => 'Spring Essentials', 'description' => 'Lightweight pieces', 'image' => 'assets/Spring Essentials.jpg', 'link' => 'collections.php?filter=new-arrivals'],
    ['title' => 'Summer Vibes', 'description' => 'Bright colors for sunny days', 'image' => 'assets/summer vibes.jpg', 'link' => 'collections.php?filter=dress'],
    ['title' => 'Best Sellers', 'description' => 'Cozy and loved styles', 'image' => 'assets/autumn layers.jpg', 'link' => 'collections.php?filter=best-seller']
];


// --- Logika Keranjang Belanja (Add to Cart) ---
$message = ''; // Untuk notifikasi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    $product_id = (int)$_POST['product_id'];
    $quantity = 1;

    // Inisialisasi keranjang jika belum ada
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    
    // Cek apakah produk sudah ada di keranjang
    if (isset($_SESSION['cart'][$product_id])) {
        $_SESSION['cart'][$product_id]['quantity'] += $quantity;
        $product_name = $_SESSION['cart'][$product_id]['name'];
        $message = htmlspecialchars($product_name) . " quantity updated in your bag.";
    } else {
        // Jika tidak, AMBIL PRODUK DARI DATABASE untuk memastikan data valid
        $stmt = $conn->prepare("SELECT id, name, price, image_url FROM products WHERE id = ?");
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $product_to_add = $result->fetch_assoc();
        $stmt->close();
        
        if ($product_to_add) {
            $_SESSION['cart'][$product_id] = [
                'id' => $product_to_add['id'],
                'name' => $product_to_add['name'],
                'price' => $product_to_add['price'],
                'image' => $product_to_add['image_url'], // Gunakan 'image_url' dari DB
                'quantity' => $quantity
            ];
            $message = htmlspecialchars($product_to_add['name']) . " added to your bag!";
        } else {
             $message = "Product not found!";
        }
    }
    
    // Alihkan dengan pesan untuk mencegah pengiriman ulang form saat refresh
    header('Location: ' . $_SERVER['PHP_SELF'] . '?message=' . urlencode($message));
    exit;
}

// Ambil pesan notifikasi dari URL jika ada
if(isset($_GET['message'])) {
    $message = htmlspecialchars($_GET['message']);
}

// Hitung jumlah item di keranjang untuk ditampilkan di header
$cart_item_count = isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LUXE | Elegant Fashion</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
                <div class="logo">
                    <a href="index.php">LUXE</a>
                </div>
                <nav class="main-nav">
                    <ul>
                        <li><a href="index.php" class="active">Home</a></li>
                        <li><a href="collections.php">Collections</a></li>
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

    <section class="hero">
        <div class="hero-content">
            <h1 class="hero-title">Elegance Redefined</h1>
            <p class="hero-subtitle">Discover our curated collection of timeless pieces</p>
            <a href="collections.php" class="hero-btn">Shop Now</a>
        </div>
    </section>
    
    <section class="section collections">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Featured Collections</h2>
                <a href="collections.php" class="view-all">View All</a>
            </div>
            <div class="collection-grid">
                <?php foreach ($featured_collections as $collection): ?>
                <div class="collection-card">
                    <a href="<?php echo htmlspecialchars($collection['link']); ?>" class="collection-link">
                        <div class="collection-image">
                            <img src="<?php echo htmlspecialchars($collection['image']); ?>" alt="<?php echo htmlspecialchars($collection['title']); ?>">
                            <div class="collection-overlay"><span class="collection-btn">View Collection</span></div>
                        </div>
                        <h3 class="collection-title"><?php echo htmlspecialchars($collection['title']); ?></h3>
                        <p class="collection-desc"><?php echo htmlspecialchars($collection['description']); ?></p>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="section products">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Our Curated Selection</h2>
                <p class="section-subtitle">Handpicked pieces for the modern wardrobe</p>
            </div>
            <div class="product-grid">
                <?php foreach ($featured_products as $product): ?>
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
                        <form method="POST" action="index.php">
                            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                            <button type="submit" name="add_to_cart" class="add-to-cart">Add to Bag</button>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <footer class="footer">
        <div class="container">
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> LUXE. All rights reserved.</p>
                </div>
        </div>
    </footer>
    
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
