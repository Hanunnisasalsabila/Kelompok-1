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
    
    $redirect_url = $_SERVER['PHP_SELF'];
    if (isset($_GET['filter'])) {
        $redirect_url .= '?filter=' . urlencode($_GET['filter']);
    }
    $separator = (strpos($redirect_url, '?') === false) ? '?' : '&';
    header('Location: ' . $redirect_url . $separator . 'message=' . urlencode($message));
    exit;
}

// --- Logika Filter & Ambil Produk dari DB ---
$categories_result = $conn->query("SELECT * FROM categories ORDER BY name ASC");
$categories = $categories_result->fetch_all(MYSQLI_ASSOC);
$filter_id = $_GET['filter'] ?? 'all';
$page_title = 'All Products';
$sql = "SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id";
if ($filter_id !== 'all' && is_numeric($filter_id)) {
    $sql .= " WHERE p.category_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $filter_id);
    foreach ($categories as $cat) {
        if ($cat['id'] == $filter_id) {
            $page_title = $cat['name'];
            break;
        }
    }
} else {
    $stmt = $conn->prepare($sql);
}
$stmt->execute();
$products_result = $stmt->get_result();
$display_products = $products_result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

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
    <title>Collections | LUXE</title>
    <!-- PERUBAHAN: Menambahkan Favicon -->
    <link rel="icon" href="assets/Luxe.png" type="image/jpeg">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        .toast-notification {
            position: fixed; bottom: 20px; left: 50%; transform: translateX(-50%);
            background-color: #333; color: white; padding: 12px 25px; border-radius: 25px;
            z-index: 1000; opacity: 0; visibility: hidden; transition: all 0.5s;
        }
        .toast-notification.show { opacity: 1; visibility: visible; transform: translate(-50%, -10px); }
        .collections-filter .filter-inner { display: flex; align-items: center; justify-content: flex-start; gap: 15px; margin-bottom: 30px; }
        .collections-filter select { min-width: 220px; }

        /* PERUBAHAN: Menambahkan CSS Header yang Konsisten */
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
    </style>
</head>
<body>
    <!-- PERUBAHAN: Header disamakan dengan index.php -->
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
                        <li><a href="index.php">Home</a></li>
                        <li><a href="collections.php" class="active">Collections</a></li>
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

    <section class="page-hero">
        <div class="container text-center">
            <h1 class="page-title">Our Collections</h1>
            <p class="page-subtitle">Discover timeless pieces for every occasion</p>
        </div>
    </section>

    <section class="collections-filter">
        <div class="container">
             <div class="filter-inner">
                <label for="collection-filter" class="form-label">Filter By:</label>
                <form action="collections.php" method="GET" id="filterForm">
                    <select name="filter" id="collection-filter" class="form-select" onchange="document.getElementById('filterForm').submit();">
                        <option value="all" <?php if($filter_id === 'all') echo 'selected'; ?>>All Products</option>
                        <?php foreach($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>" <?php if($filter_id == $category['id']) echo 'selected'; ?>>
                                <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
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
                        </div>
                        <div class="product-info">
                            <h3 class="product-title"><?php echo htmlspecialchars($product['name']); ?></h3>
                            <p class="product-price">
                                <?php if (!empty($product['original_price'])): ?>
                                    <span class="original-price">$<?php echo number_format($product['original_price'], 2); ?></span>
                                <?php endif; ?>
                                $<?php echo number_format($product['price'], 2); ?>
                            </p>
                            <form method="POST" action="collections.php?filter=<?php echo htmlspecialchars($filter_id); ?>">
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
