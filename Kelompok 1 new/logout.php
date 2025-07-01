<?php
session_start();
require_once 'db_connection.php'; // Ini akan mengimpor variabel $conn

// ... sisa kode Anda bisa menggunakan variabel $conn untuk query ...
// Contoh: $result = $conn->query("SELECT * FROM users");

if (isset($_GET['action']) && $_GET['action'] === 'true') {
    
    $_SESSION = array();

    session_destroy();
    // 3. Alihkan (redirect) pengguna ke halaman login dengan pesan
    //    Menambahkan parameter status=logged_out bersifat opsional,
    //    tapi bisa digunakan di halaman login untuk menampilkan pesan "Anda telah berhasil logout".
    header("Location: login.php?status=logged_out");
    exit; // Pastikan skrip berhenti setelah redirect
}

// Jika tidak ada permintaan logout (?action=true),
// maka skrip akan melanjutkan dan menampilkan halaman konfirmasi di bawah ini.
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logout | LUXE ADMIN</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Montserrat:wght@300;400;500&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        /* Variabel CSS (asumsikan dari style.css, ditambahkan untuk kelengkapan) */
        :root {
            --primary-color: #333;
            --secondary-color: #666;
            --dark-color: #000;
            --light-color: #f4f4f4;
            --border-color: #ddd;
            --font-heading: 'Playfair Display', serif;
        }

        .logout-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background-color: #f8f8f8;
            text-align: center;
            padding: 2rem;
            font-family: 'Montserrat', sans-serif;
        }
        
        .logout-box {
            background-color: #fff;
            border-radius: 0.5rem;
            padding: 4rem;
            max-width: 50rem;
            width: 100%;
            box-shadow: 0 0.5rem 1.5rem rgba(0, 0, 0, 0.05);
        }
        
        .logout-icon {
            font-size: 5rem;
            color: var(--primary-color);
            margin-bottom: 2rem;
        }
        
        .logout-title {
            font-size: 2.4rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            font-family: var(--font-heading);
        }
        
        .logout-message {
            font-size: 1.6rem;
            color: var(--secondary-color);
            margin-bottom: 3rem;
            line-height: 1.6;
        }
        
        .btn-group {
            display: flex;
            gap: 1.5rem;
            justify-content: center;
        }
        
        .btn {
            padding: 1rem 2.5rem;
            border-radius: 0.3rem;
            font-size: 1.4rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex; /* Menggunakan inline-flex untuk alignment ikon */
            align-items: center;
            gap: 0.8rem; /* Jarak antara ikon dan teks */
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            color: #fff;
            border: none;
        }
        
        .btn-primary:hover {
            background-color: var(--dark-color);
        }
        
        .btn-outline {
            background-color: transparent;
            border: 1px solid var(--border-color);
            color: var(--primary-color);
        }
        
        .btn-outline:hover {
            background-color: var(--light-color);
        }
        
        .logout-footer {
            margin-top: 3rem;
            font-size: 1.2rem;
            color: var(--secondary-color);
        }
    </style>
</head>
<body>
    <div class="logout-container">
        <div class="logout-box">
            <div class="logout-icon">
                <i class="fas fa-sign-out-alt"></i>
            </div>
            <h1 class="logout-title">Logout from LUXE Admin</h1>
            <p class="logout-message">Are you sure you want to log out? You'll need to enter your credentials again to access the admin dashboard.</p>
            
            <div class="btn-group">
                <a href="dashboardadmin.php" class="btn btn-outline">
                    <i class="fas fa-arrow-left"></i> Cancel
                </a>
                <a href="logout.php?action=true" class="btn btn-primary">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
            
            <div class="logout-footer">
                <p>&copy; <?php echo date('Y'); ?> LUXE. All rights reserved.</p>
            </div>
        </div>
    </div>
</body>
</html>