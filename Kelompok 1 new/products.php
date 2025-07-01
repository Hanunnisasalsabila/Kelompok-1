<?php
session_start();
require_once 'db_connection.php'; // Ini akan mengimpor variabel $conn

// ========================================================================
// 1. BLOK LOGIKA & KEAMANAN PHP
// ========================================================================

// --- Pemeriksaan Keamanan: Pastikan hanya admin yang bisa mengakses ---
if (!isset($_SESSION['isLoggedIn']) || !isset($_SESSION['userRole']) || strtolower($_SESSION['userRole']) !== 'admin') {
    header('Location: login.php');
    exit;
}

// --- Konfigurasi ---
$uploads_dir = 'assets/'; // Sesuaikan dengan folder tempat Anda menyimpan gambar produk

$message = ''; // Untuk notifikasi
if(isset($_GET['msg'])) {
    $status = $_GET['msg'];
    if($status == 'saved') $message = "Product has been saved successfully.";
    if($status == 'deleted') $message = "Product has been deleted.";
    if($status == 'error') $message = "An error occurred. Please try again.";
}

// --- Penanganan Aksi (CRUD) ---

// Aksi Hapus (via GET)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $product_id_to_delete = (int)$_GET['id'];
    $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
    $stmt->bind_param("i", $product_id_to_delete);
    if ($stmt->execute()) {
        header('Location: products.php?msg=deleted');
    } else {
        header('Location: products.php?msg=error');
    }
    $stmt->close();
    exit;
}

// Aksi Simpan/Update (via POST dari form modal)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_product') {
    $product_id = $_POST['productId'];
    $name = $_POST['productName'];
    $category_id = (int)$_POST['productCategory'];
    $price = (float)$_POST['productPrice'];
    $original_price = !empty($_POST['productOriginalPrice']) ? (float)$_POST['productOriginalPrice'] : null;
    $stock = (int)$_POST['productStock'];
    $description = $_POST['productDescription'];
    $image_url = $_POST['existingImage']; // Ambil path gambar yang sudah ada

    // Handle file upload jika ada file baru
    if (isset($_FILES['productImage']) && $_FILES['productImage']['error'] == 0) {
        if (!is_dir($uploads_dir)) mkdir($uploads_dir, 0777, true);
        
        $file_name = time() . '_' . basename($_FILES['productImage']['name']);
        $target_file = $uploads_dir . $file_name;
        
        if (move_uploaded_file($_FILES['productImage']['tmp_name'], $target_file)) {
            $image_url = $target_file; // Ganti URL gambar jika upload berhasil
        }
    }

    if (empty($product_id)) { // Tambah Produk Baru
        $sql = "INSERT INTO products (name, category_id, price, original_price, stock_quantity, description, image_url) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("siddiss", $name, $category_id, $price, $original_price, $stock, $description, $image_url);
    } else { // Update Produk yang Ada
        $sql = "UPDATE products SET name = ?, category_id = ?, price = ?, original_price = ?, stock_quantity = ?, description = ?, image_url = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("siddissi", $name, $category_id, $price, $original_price, $stock, $description, $image_url, $product_id);
    }

    if ($stmt->execute()) {
        header('Location: products.php?msg=saved');
    } else {
        header('Location: products.php?msg=error');
    }
    $stmt->close();
    exit;
}

// --- Pengambilan Data dari Database untuk Ditampilkan ---
// Ambil semua kategori untuk dropdown di form
$categories_result = $conn->query("SELECT * FROM categories ORDER BY name ASC");
$categories = $categories_result->fetch_all(MYSQLI_ASSOC);

// Ambil semua produk untuk ditampilkan di tabel
$products_query = "SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id ORDER BY p.created_at DESC";
$products_result = $conn->query($products_query);
$all_products = $products_result->fetch_all(MYSQLI_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Management | LUXE ADMIN</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Montserrat:wght@400;500&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        :root { --primary-color: #333; --light-color: #f4f4f4; --border-color: #ddd; }
        .admin-container { display: grid; grid-template-columns: 250px 1fr; min-height: 100vh; }
        .admin-sidebar { background-color: var(--primary-color); color: #fff; padding: 2rem 0; }
        .admin-nav a { display: block; padding: 1rem 1.5rem; color: rgba(255, 255, 255, 0.8); text-decoration: none; transition: all 0.3s ease; }
        .admin-nav a.active, .admin-nav a:hover { color: #fff; background-color: rgba(255, 255, 255, 0.1); }
        .admin-nav i { width: 2rem; margin-right: 1rem; text-align: center; }
        .admin-main { padding: 2rem; background-color: #f8f8f8; }
        .admin-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
        .admin-table { width: 100%; background-color: #fff; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.07); border-collapse: collapse; }
        .admin-table th, .admin-table td { padding: 1rem 1.5rem; text-align: left; border-bottom: 1px solid var(--border-color); }
        .product-image { width: 60px; height: 60px; object-fit: cover; border-radius: 4px; }
        .modal { display: none; position: fixed; z-index: 1050; left: 0; top: 0; width: 100%; height: 100%; overflow: hidden; outline: 0; background-color: rgba(0,0,0,0.5); }
        .modal.show { display: flex; justify-content: center; align-items: center; }
        .modal-dialog { max-width: 700px; width: 90%; margin: 1.75rem auto; }
        .modal-content { position: relative; display: flex; flex-direction: column; width: 100%; pointer-events: auto; background-color: #fff; background-clip: padding-box; border: 1px solid rgba(0,0,0,.2); border-radius: 0.3rem; outline: 0; }
        .modal-header, .modal-body, .modal-footer { padding: 1.5rem; }
        .modal-header { border-bottom: 1px solid var(--border-color); }
        .modal-footer { border-top: 1px solid var(--border-color); display:flex; justify-content: flex-end; gap: 1rem;}
    </style>
</head>
<body>
    <div class="admin-container">
        <aside class="admin-sidebar">
             <div class="admin-logo" style="text-align:center; padding: 0 1rem 2rem 1rem;"><h3>LUXE ADMIN</h3></div>
            <nav class="admin-nav">
                <ul>
                    <li><a href="dashboardadmin.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                    <li><a href="products.php" class="active"><i class="fas fa-shopping-bag"></i> Products</a></li>
                    <li><a href="orders.php"><i class="fas fa-receipt"></i> Orders</a></li>
                    <li><a href="settings.php"><i class="fas fa-cog"></i> Settings</a></li>
                    <li><a href="account.php?action=logout"><i class="fas fa-sign-out-alt"></i> Log Out</a></li>
                </ul>
            </nav>
        </aside>
        
        <main class="admin-main">
            <header class="admin-header">
                <h1 class="admin-title">Product Management</h1>
                <button id="addProductBtn" class="btn btn-primary">Add Product</button>
            </header>

            <?php if(!empty($message)): ?>
                <div class="alert alert-success"><?php echo $message; ?></div>
            <?php endif; ?>
            
            <table class="admin-table">
                <thead>
                    <tr><th>Image</th><th>Product Name</th><th>Category</th><th>Price</th><th>Stock</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    <?php if (empty($all_products)): ?>
                        <tr><td colspan="6" style="text-align:center;">No products found. Click 'Add Product' to start.</td></tr>
                    <?php else: ?>
                        <?php foreach ($all_products as $product): ?>
                        <tr>
                            <td><img src="<?php echo htmlspecialchars($product['image_url']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="product-image"></td>
                            <td><?php echo htmlspecialchars($product['name']); ?></td>
                            <td><?php echo htmlspecialchars($product['category_name']); ?></td>
                            <td>$<?php echo number_format($product['price'], 2); ?></td>
                            <td><?php echo htmlspecialchars($product['stock_quantity']); ?></td>
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
        <div class="modal-dialog">
            <div class="modal-content">
                 <form id="productForm" method="POST" action="products.php" enctype="multipart/form-data">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalTitle">Add Product</h5>
                        <button type="button" class="btn-close close-modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="save_product">
                        <input type="hidden" name="productId" id="productId">
                        <input type="hidden" name="existingImage" id="existingImage">
                        
                        <div class="mb-3"><label for="productName" class="form-label">Product Name</label><input type="text" name="productName" id="productName" class="form-control" required></div>
                        <div class="mb-3"><label for="productCategory" class="form-label">Category</label>
                            <select name="productCategory" id="productCategory" class="form-select" required>
                                <option value="">Select category</option>
                                <?php foreach($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3"><label for="productPrice" class="form-label">Price ($)</label><input type="number" name="productPrice" id="productPrice" class="form-control" step="0.01" required></div>
                            <div class="col-md-6 mb-3"><label for="productOriginalPrice" class="form-label">Original Price (Optional)</label><input type="number" name="productOriginalPrice" id="productOriginalPrice" class="form-control" step="0.01"></div>
                        </div>
                        <div class="mb-3"><label for="productStock" class="form-label">Stock Quantity</label><input type="number" name="productStock" id="productStock" class="form-control" required></div>
                        <div class="mb-3"><label for="productDescription" class="form-label">Description</label><textarea name="productDescription" id="productDescription" class="form-control" rows="3"></textarea></div>
                        <div class="mb-3"><label for="productImage" class="form-label">Product Image</label><input type="file" name="productImage" id="productImage" class="form-control" accept="image/*"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary close-modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Product</button>
                    </div>
                 </form>
            </div>
        </div>
    </div>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const modalElement = document.getElementById('productModal');
        const addProductBtn = document.getElementById('addProductBtn');
        const closeModalBtns = document.querySelectorAll('.close-modal, .btn-close');
        const productForm = document.getElementById('productForm');
        const modalTitle = document.getElementById('modalTitle');
        
        function openModal(isEditing = false, productData = null) {
            productForm.reset();
            if (isEditing && productData) {
                modalTitle.textContent = "Edit Product";
                document.getElementById('productId').value = productData.id;
                document.getElementById('productName').value = productData.name;
                document.getElementById('productCategory').value = productData.category_id;
                document.getElementById('productPrice').value = productData.price;
                document.getElementById('productOriginalPrice').value = productData.original_price;
                document.getElementById('productStock').value = productData.stock_quantity;
                document.getElementById('productDescription').value = productData.description || '';
                document.getElementById('existingImage').value = productData.image_url;
            } else {
                modalTitle.textContent = "Add New Product";
                document.getElementById('productId').value = '';
                document.getElementById('existingImage').value = '';
            }
            modalElement.classList.add('show');
            modalElement.style.display = 'flex';
        }

        function closeModal() {
            modalElement.classList.remove('show');
            modalElement.style.display = 'none';
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
            if (e.target === modalElement) closeModal();
        });
    });
    </script>
</body>
</html>
