<?php
session_start();
require_once 'db_connection.php'; // Hubungkan ke database

$message_sent = false;
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Di sini Anda bisa menambahkan logika untuk mengirim email
    // Untuk saat ini, kita hanya akan menampilkan pesan sukses.
    $name = htmlspecialchars($_POST['name']);
    $email = htmlspecialchars($_POST['email']);
    $subject = htmlspecialchars($_POST['subject']);
    $message_body = htmlspecialchars($_POST['message']);
    
    // (Logika pengiriman email akan ditambahkan di sini di masa depan)
    
    $message_sent = true;
}

// Hitung jumlah item di keranjang untuk ditampilkan di header
$cart_item_count = isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us | LUXE</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        .contact-section {
            padding: 60px 0;
        }
        .contact-form {
            background: #fff;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .contact-info {
            padding-left: 30px;
        }
        .contact-info .info-item {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }
        .contact-info .info-item i {
            font-size: 24px;
            color: var(--primary-color, #333);
            width: 50px;
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
                        <li><a href="collections.php">Collections</a></li>
                        <li><a href="about.php">About</a></li>
                        <li><a href="contact.php" class="active">Contact</a></li>
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
            <h1 class="page-title">Contact Us</h1>
            <p class="page-subtitle">We'd love to hear from you. Get in touch with us.</p>
        </div>
    </section>

    <section class="contact-section">
        <div class="container">
            <div class="row">
                <div class="col-lg-7">
                    <div class="contact-form">
                        <h3>Send us a Message</h3>
                        <?php if ($message_sent): ?>
                            <div class="alert alert-success" role="alert">
                                Thank you for your message! We will get back to you shortly.
                            </div>
                        <?php endif; ?>
                        <form action="contact.php" method="POST">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="name" class="form-label">Name</label>
                                    <input type="text" class="form-control" id="name" name="name" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="email" name="email" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="subject" class="form-label">Subject</label>
                                <input type="text" class="form-control" id="subject" name="subject" required>
                            </div>
                            <div class="mb-3">
                                <label for="message" class="form-label">Message</label>
                                <textarea class="form-control" id="message" name="message" rows="5" required></textarea>
                            </div>
                            <button type="submit" class="btn btn-dark">Send Message</button>
                        </form>
                    </div>
                </div>
                <div class="col-lg-5">
                    <div class="contact-info">
                        <h3>Our Information</h3>
                        <p>Feel free to reach out to us through any of the following methods. We are available during business hours to assist you.</p>
                        <div class="info-item">
                            <i class="fas fa-map-marker-alt"></i>
                            <div><strong>Address:</strong><br>123 Fashion Ave, Suite 456, Jakarta, Indonesia</div>
                        </div>
                        <div class="info-item">
                            <i class="fas fa-envelope"></i>
                            <div><strong>Email:</strong><br>support@luxe.com</div>
                        </div>
                        <div class="info-item">
                            <i class="fas fa-phone"></i>
                            <div><strong>Phone:</strong><br>(021) 555-0123</div>
                        </div>
                    </div>
                </div>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
