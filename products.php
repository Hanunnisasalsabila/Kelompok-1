<?php
session_start();
require_once 'db_connection.php'; // Ini akan mengimpor variabel $conn

// ... sisa kode Anda bisa menggunakan variabel $conn untuk query ...
// Contoh: $result = $conn->query("SELECT * FROM users");

// ========================================================================
// 1. BLOK LOGIKA & KEAMANAN PHP
// ========================================================================

// --- Pemeriksaan Keamanan: Pastikan hanya admin yang bisa mengakses ---
if (!isset($_SESSION['isLoggedIn']) || $_SESSION['userRole'] !== 'admin') {
    header('Location: login.php');
    exit;
}

// --- Konfigurasi dan Fungsi Pembantu ---
$products_file = 'products.json';
$uploads_dir = 'uploads/';

function get_products($file) {
    if (!file_exists($file)) return [];
    $data = json_decode(file_get_contents($file), true);
    return is_array($data) ? $data : [];
}

function save_products($file, $data) {
    file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));
}

$all_products = get_products($products_file);

// --- Penanganan Aksi (CRUD) ---

// Aksi Hapus (via GET)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'delete') {
    $product_id = $_GET['id'];
    // Filter array, simpan semua produk kecuali yang ID-nya cocok
    $products_after_delete = array_filter($all_products, fn($p) => $p['id'] != $product_id);
    // Re-index array untuk menghindari masalah JSON
    save_products($products_file, array_values($products_after_delete));
    header('Location: products.php?msg=deleted');
    exit;
}

// Aksi Simpan/Update (via POST dari form modal)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_product') {
    $product_id = $_POST['productId'];
    
    $product_data = [
        'name' => $_POST['productName'],
        'category' => $_POST['productCategory'],
        'price' => (float)$_POST['productPrice'],
        'stock' => (int)$_POST['productStock'],
        'description' => $_POST['productDescription'],
        'status' => $_POST['productStatus']
    ];

    // Handle file upload
    if (isset($_FILES['productImage']) && $_FILES['productImage']['error'] == 0) {
        if (!is_dir($uploads_dir)) mkdir($uploads_dir, 0777, true);
        $file_name = time() . '_' . basename($_FILES['productImage']['name']);
        $target_file = $uploads_dir . $file_name;
        if (move_uploaded_file($_FILES['productImage']['tmp_name'], $target_file)) {
            $product_data['image'] = $target_file;
        }
    }

    if (empty($product_id)) { // Tambah Produk Baru
        $product_data['id'] = empty($all_products) ? 1 : max(array_column($all_products, 'id')) + 1;
        if (!isset($product_data['image'])) {
            $product_data['image'] = 'assets/default-product.jpg'; // Gambar default jika tidak diunggah
        }
        $all_products[] = $product_data;
    } else { // Update Produk yang Ada
        $index = array_search($product_id, array_column($all_products, 'id'));
        if ($index !== false) {
            // Gabungkan data lama dengan data baru, gambar hanya diperbarui jika ada unggahan baru
            $all_products[$index] = array_merge($all_products[$index], $product_data);
        }
    }

    save_products($products_file, $all_products);
    header('Location: products.php?msg=saved');
    exit;
}

// Muat ulang data produk setelah ada kemungkinan perubahan
$all_products = get_products($products_file);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Management | LUXE ADMIN</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Montserrat:wght@300;400;500&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        /* [Salin semua CSS dari file HTML asli ke sini] */
        :root { --primary-color: #333; --light-color: #f4f4f4; --border-color: #ddd; --font-heading: 'Playfair Display', serif; }
        .admin-container { display: grid; grid-template-columns: 250px 1fr; min-height: 100vh; }
        .admin-sidebar { background-color: var(--primary-color); color: #fff; padding: 2rem 0; }
        .admin-logo a { font-family: var(--font-heading); font-size: 2rem; color: #fff; text-decoration: none; }
        .admin-nav ul { list-style: none; padding: 0; }
        .admin-nav a { display: block; padding: 1rem 1.5rem; color: rgba(255, 255, 255, 0.8); text-decoration: none; }
        .admin-nav a.active, .admin-nav a:hover { color: #fff; background-color: rgba(255, 255, 255, 0.1); }
        .admin-nav i { width: 2rem; margin-right: 1rem; text-align: center; }
        .admin-main { padding: 2rem; background-color: #f8f8f8; }
        .admin-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
        .admin-title { font-size: 2.4rem; font-weight: 600; }
        .btn { padding: 0.8rem 1.5rem; border-radius: 0.3rem; font-size: 1.4rem; cursor: pointer; text-decoration: none; border: 1px solid var(--border-color); background: transparent; }
        .btn-primary { background-color: var(--primary-color); color: #fff; border-color: var(--primary-color); }
        .btn-outline { background-color: transparent; } .btn-sm { padding: 0.5rem 1rem; font-size: 1.2rem; }
        .admin-table { width: 100%; background-color: #fff; border-radius: 0.5rem; box-shadow: 0 0.5rem 1.5rem rgba(0,0,0,0.05); border-collapse: collapse; }
        .admin-table th, .admin-table td { padding: 1.5rem; text-align: left; border-bottom: 1px solid var(--border-color); }
        .admin-table th { background-color: var(--light-color); }
        .product-image { width: 60px; height: 60px; object-fit: cover; border-radius: 4px; }
        .status { padding: 0.5rem 1rem; border-radius: 2rem; font-size: 1.2rem; }
        .status.active { background-color: #d4edda; color: #155724; }
        .status.inactive { background-color: #f8d7da; color: #721c24; }
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); justify-content: center; align-items: center; }
        .modal-content { background-color: #fff; padding: 3rem; border-radius: 0.5rem; max-width: 700px; width: 90%; max-height: 90vh; overflow-y: auto; }
        .modal-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-color); padding-bottom: 1rem; margin-bottom: 2rem; }
        .close-modal { font-size: 2rem; background: none; border: none; cursor: pointer; }
        .form-group { margin-bottom: 1.5rem; } .form-group label { display: block; margin-bottom: 0.5rem; }
        .form-control { width: 100%; padding: 1rem; border: 1px solid var(--border-color); border-radius: 0.3rem; }
        .form-row { display: flex; gap: 2rem; } .form-row .form-group { flex: 1; }
        .form-actions { display: flex; justify-content: flex-end; gap: 1rem; margin-top: 2rem; }
    </style>
</head>
<body>
    <div class="admin-container">
        <aside class="admin-sidebar">
            <div class="admin-logo"><a href="index.php">LUXE ADMIN</a></div>
            <nav class="admin-nav">
                <ul>
                    <li><a href="dashboardadmin.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                    <li><a href="products.php" class="active"><i class="fas fa-shopping-bag"></i> Products</a></li>
                    <li><a href="orders.php"><i class="fas fa-receipt"></i> Orders</a></li>
                    <li><a href="settings.php"><i class="fas fa-cog"></i> Settings</a></li>
                    <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Log Out</a></li>
                </ul>
            </nav>
        </aside>
        
        <main class="admin-main">
            <header class="admin-header">
                <h1 class="admin-title">Product Management</h1>
                <button id="addProductBtn" class="btn btn-primary">Add Product</button>
            </header>
            
            <table class="admin-table">
                <thead>
                    <tr><th>Image</th><th>Product Name</th><th>Category</th><th>Price</th><th>Stock</th><th>Status</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    <?php if (empty($all_products)): ?>
                        <tr><td colspan="7" style="text-align:center;">No products found. Click 'Add Product' to start.</td></tr>
                    <?php else: ?>
                        <?php foreach ($all_products as $product): ?>
                        <tr>
                            <td><img src="<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="product-image"></td>
                            <td><?php echo htmlspecialchars($product['name']); ?></td>
                            <td><?php echo htmlspecialchars($product['category']); ?></td>
                            <td>$<?php echo number_format($product['price'], 2); ?></td>
                            <td><?php echo htmlspecialchars($product['stock']); ?></td>
                            <td><span class="status <?php echo $product['status']; ?>"><?php echo ucfirst($product['status']); ?></span></td>
                            <td>
                                <button class="btn btn-outline btn-sm edit-btn" data-product='<?php echo json_encode($product, JSON_HEX_APOS); ?>'><i class="fas fa-edit"></i></button>
                                <a href="products.php?action=delete&id=<?php echo $product['id']; ?>" class="btn btn-outline btn-sm" onclick="return confirm('Are you sure you want to delete this product?');"><i class="fas fa-trash"></i></a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </main>
    </div>
    
    <div class="modal" id="productModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title" id="modalTitle">Add New Product</h2>
                <button type="button" class="close-modal">&times;</button>
            </div>
            <form id="productForm" method="POST" action="products.php" enctype="multipart/form-data">
                <input type="hidden" name="action" value="save_product">
                <input type="hidden" name="productId" id="productId">
                <div class="form-row">
                    <div class="form-group"><label for="productName">Product Name</label><input type="text" name="productName" id="productName" class="form-control" required></div>
                    <div class="form-group"><label for="productCategory">Category</label><select name="productCategory" id="productCategory" class="form-control" required><option value="">Select category</option><option>Tops</option><option>Bottoms</option><option>Dresses</option><option>Accessories</option></select></div>
                </div>
                <div class="form-row">
                    <div class="form-group"><label for="productPrice">Price ($)</label><input type="number" name="productPrice" id="productPrice" class="form-control" step="0.01" required></div>
                    <div class="form-group"><label for="productStock">Stock Quantity</label><input type="number" name="productStock" id="productStock" class="form-control" required></div>
                </div>
                <div class="form-group"><label for="productDescription">Description</label><textarea name="productDescription" id="productDescription" class="form-control"></textarea></div>
                <div class="form-group"><label for="productImage">Product Image</label><input type="file" name="productImage" id="productImage" class="form-control" accept="image/*"></div>
                <div class="form-group"><label>Status</label>
                    <div>
                        <label><input type="radio" name="productStatus" value="active" checked> Active</label>
                        <label style="margin-left:1rem;"><input type="radio" name="productStatus" value="inactive"> Inactive</label>
                    </div>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn btn-outline close-modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Product</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const modal = document.getElementById('productModal');
        const addProductBtn = document.getElementById('addProductBtn');
        const closeModalBtns = document.querySelectorAll('.close-modal');
        const productForm = document.getElementById('productForm');
        const modalTitle = document.getElementById('modalTitle');
        const productIdInput = document.getElementById('productId');

        function openModal(isEditing = false, productData = null) {
            productForm.reset();
            if (isEditing && productData) {
                modalTitle.textContent = "Edit Product";
                productIdInput.value = productData.id;
                document.getElementById('productName').value = productData.name;
                document.getElementById('productCategory').value = productData.category;
                document.getElementById('productPrice').value = productData.price;
                document.getElementById('productStock').value = productData.stock;
                document.getElementById('productDescription').value = productData.description || '';
                document.querySelector(`input[name="productStatus"][value="${productData.status}"]`).checked = true;
            } else {
                modalTitle.textContent = "Add New Product";
                productIdInput.value = '';
            }
            modal.style.display = 'flex';
        }

        function closeModal() {
            modal.style.display = 'none';
        }

        addProductBtn.addEventListener('click', () => openModal(false));

        document.querySelectorAll('.edit-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const productData = JSON.parse(this.getAttribute('data-product'));
                openModal(true, productData);
            });
        });

        closeModalBtns.forEach(btn => btn.addEventListener('click', closeModal));
        window.addEventListener('click', (e) => {
            if (e.target === modal) closeModal();
        });
    });
    </script>
</body>
</html>