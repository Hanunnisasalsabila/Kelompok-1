<?php
session_start();
require_once 'db_connection.php'; // Mengimpor koneksi $conn

// ========================================================================
// 1. BLOK DATA & LOGIKA PHP
// ========================================================================

// --- AMBIL PRODUK UNGGULAN DARI DATABASE ---
$products_result = $conn->query("SELECT * FROM products ORDER BY created_at DESC LIMIT 4");
$featured_products = [];
if ($products_result) {
    while($row = $products_result->fetch_assoc()) {
        $featured_products[] = $row;
    }
}

// --- AMBIL KOLEKSI UNGGULAN DARI DATABASE (DINAMIS) ---
$collections_result = $conn->query("SELECT * FROM categories LIMIT 3");
$featured_collections = [];
if ($collections_result) {
    $default_images = [
        'assets/Spring Essentials.jpg', 
        'assets/summer vibes.jpg', 
        'assets/autumn layers.jpg'
    ];
    $i = 0;
    while($row = $collections_result->fetch_assoc()) {
        $featured_collections[] = [
            'id' => $row['id'],
            'title' => $row['name'],
            'description' => $row['description'],
            // PERBAIKAN: Menggunakan parameter 'filter' agar sesuai dengan collections.php
            'link' => 'collections.php?filter=' . $row['id'], 
            'image' => $default_images[$i % count($default_images)]
        ];
        $i++;
    }
}


// --- Logika Keranjang Belanja (Add to Cart) ---
$message = ''; // Untuk notifikasi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    $product_id = (int)$_POST['product_id'];
    $quantity = 1;

    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    
    if (isset($_SESSION['cart'][$product_id])) {
        $_SESSION['cart'][$product_id]['quantity'] += $quantity;
        $product_name = $_SESSION['cart'][$product_id]['name'];
        $message = htmlspecialchars($product_name) . " quantity updated in your bag.";
    } else {
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
                'image_url' => $product_to_add['image_url'],
                'quantity' => $quantity
            ];
            $message = htmlspecialchars($product_to_add['name']) . " added to your bag!";
        } else {
             $message = "Product not found!";
        }
    }
    
    header('Location: ' . $_SERVER['PHP_SELF'] . '?message=' . urlencode($message));
    exit;
}

if(isset($_GET['message'])) {
    $message = htmlspecialchars($_GET['message']);
}
$cart_item_count = isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LUXE | Elegant Fashion</title>
    <link rel="icon" href="assets/Luxe.png" type="image/jpeg">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        .toast-notification {
            position: fixed; bottom: 20px; left: 50%; transform: translateX(-50%);
            background-color: #333; color: white; padding: 12px 25px; border-radius: 25px;
            z-index: 1000; opacity: 0; visibility: hidden; transition: all 0.5s;
        }
        .toast-notification.show { opacity: 1; visibility: visible; transform: translate(-50%, -10px); }

        .header {
            padding: 15px 0;
            background-color: #fff;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        .header .logo img {
            height: 50px;
            width: auto;
        }
        .nav-actions {
            display: flex;
            align-items: center;
            gap: 25px; 
        }
        .nav-actions .account-btn, 
        .nav-actions .cart-btn {
            font-size: 1.5rem; 
            color: #333;
            text-decoration: none;
            position: relative;
        }
        .cart-btn .cart-count {
            position: absolute;
            top: -6px;
            right: -11px;
            background-color: #c5a47e;
            color: white;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 0.7rem;
            font-weight: bold;
        }

        .hero {
            position: relative;
            height: 90vh; 
            background-image: url('assets/BG.jpeg');
            background-size: cover;
            background-position: center;
            display: flex;
            align-items: center;
            justify-content: flex-start;
            color: #fff;
        }

        .hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(to right, rgba(0, 0, 0, 0.6), rgba(0, 0, 0, 0.1));
        }
        
        .hero-content {
            position: relative;
            z-index: 2;
            padding-left: 12%; 
            max-width: 550px;
        }
        
        .hero-title {
            font-family: 'Playfair Display', serif;
            font-size: 3.8rem;
            font-weight: 700;
            color: #FFFFFF;
            line-height: 1.2;
            text-shadow: 1px 1px 5px rgba(0, 0, 0, 0.5);
            margin-top: 0;
        }

        .hero-subtitle {
            font-family: 'Montserrat', sans-serif;
            font-size: 1.1rem; 
            margin-top: 15px;
            color: #E8D4B7;
            letter-spacing: 0.5px;
            max-width: 400px; 
        }

        .hero-btn {
            margin-top: 35px;
            background-color: #fff;
            color: #333;
            padding: 12px 35px;
            text-decoration: none;
            border-radius: 2px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: all 0.3s ease;
        }
        .hero-btn:hover { background-color: #E8D4B7; color: #000; }
        
    </style>
</head>
<body>
    <header class="header">
        <div class="container">
            <div class="header-inner">
                <div class="logo">
                    <a href="index.php">
                        <img src="assets/Luxe.png" alt="LUXE Logo">
                    </a>
                </div>
                <nav class="main-nav">
                    <ul>
                        <li><a href="index.php" class="active">Home</a></li>
                        <li><a href="collections.php">Collections</a></li>
                        <li><a href="about.php">About</a></li>
                    </ul>
                </nav>
                <div class="nav-actions">
                    <a href="account.php" class="account-btn"><i class="far fa-user"></i></a>
                    <a href="checkout.php" class="cart-btn" id="cart-btn">
                        <i class="fas fa-shopping-bag"></i>
                        <span class="cart-count"><?php echo $cart_item_count; ?></span>
                    </a>
                </div>
            </div>
        </div>
    </header>

    <section class="hero">
        <div class="hero-content">
            <h1 class="hero-title">Elegance Redefined</h1>
            <p class="hero-subtitle">Discover our curated collection of timeless pieces that define modern sophistication.</p>
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
                    </div>
                    <div class="product-info">
                        <h3 class="product-title"><?php echo htmlspecialchars($product['name']); ?></h3>
                        <p class="product-price">$<?php echo number_format($product['price'], 2); ?></p>
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
    
    <div id="toast-notification" class="toast-notification"></div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const message = "<?php echo addslashes($message); ?>";
        if (message) {
            const toast = document.getElementById('toast-notification');
            if(toast) {
                toast.textContent = message;
                toast.classList.add('show');
                setTimeout(() => toast.classList.remove('show'), 3000);
            }
        }
    });
    </script>
</body>
</html>
