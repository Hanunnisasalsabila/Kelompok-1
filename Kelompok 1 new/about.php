<?php
session_start();
require_once 'db_connection.php'; // Ini akan mengimpor variabel $conn

// Data untuk bagian dinamis (dalam aplikasi nyata, ini bisa datang dari database)
$page_title = 'About Us & Contact | LUXE';

$company_values = [
    [
        'icon' => 'fa-leaf',
        'title' => 'Sustainability',
        'description' => 'We\'re committed to ethical sourcing and sustainable production methods, using eco-friendly materials whenever possible.'
    ],
    [
        'icon' => 'fa-tshirt',
        'title' => 'Quality Craftsmanship',
        'description' => 'Each garment is meticulously crafted by skilled artisans who take pride in their work, ensuring exceptional quality.'
    ],
    [
        'icon' => 'fa-heart',
        'title' => 'Timeless Design',
        'description' => 'We create pieces that transcend seasonal trends, focusing on classic silhouettes with modern details.'
    ],
    [
        'icon' => 'fa-hands-helping',
        'title' => 'Community',
        'description' => 'We support fair wages and safe working conditions for all our partners throughout the supply chain.'
    ]
];

$team_members = [
    [
        'image' => 'assets/foto profil girls 1.jpg',
        'name' => 'Isabella Rossi',
        'role' => 'Founder & Creative Director'
    ],
    [
        'image' => 'assets/foto profil man 1.jpg',
        'name' => 'Marco Bianchi',
        'role' => 'Head of Design'
    ],
    [
        'image' => 'assets/foto profil girls 2.jpg',
        'name' => 'Sophie Laurent',
        'role' => 'Production Manager'
    ],
    [
        'image' => 'assets/foto profil man 2.jpg',
        'name' => 'David Kim',
        'role' => 'Customer Experience'
    ]
];

$sustainability_points = [
    '100% organic cotton and recycled fabrics',
    'Low-impact, non-toxic dyes',
    'Zero-waste pattern cutting techniques',
    'Carbon-neutral shipping',
    'Biodegradable packaging'
];

// Logika untuk form kontak
$message_sent = false;
if ($_SERVER["REQUEST_METHOD"] == "POST") {
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
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Montserrat:wght@300;400;500&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        /* Gaya spesifik untuk bagian kontak */
        .contact-section {
            padding: 60px 0;
            background-color: #f9f9f9; /* Latar belakang sedikit berbeda untuk memisahkan bagian */
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
        .section-header {
            text-align: center;
            margin-bottom: 50px;
        }
        .section-header .section-title {
            font-size: 2.5rem;
            font-family: 'Playfair Display', serif;
            margin-bottom: 10px;
        }
        .section-header .section-subtitle {
            font-size: 1.1rem;
            color: #666;
        }
        .about-hero {
            background: url('assets/hero-about.jpg') no-repeat center center/cover; /* Ganti dengan gambar hero yang sesuai */
            color: #fff;
            text-align: center;
            padding: 100px 0;
            position: relative;
        }
        .about-hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.4); /* Overlay gelap */
        }
        .about-hero-content {
            position: relative;
            z-index: 1;
        }
        .about-hero-content h1 {
            font-family: 'Playfair Display', serif;
            font-size: 3.5rem;
            margin-bottom: 15px;
        }
        .about-intro-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 40px;
            align-items: center;
        }
        @media (min-width: 992px) {
            .about-intro-grid {
                grid-template-columns: 1fr 1fr;
            }
        }
        .about-intro-image img {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        .signature img {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            margin-top: 20px;
        }
        .values-grid, .team-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
            text-align: center;
        }
        .value-card, .team-card {
            background: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            transition: transform 0.3s ease;
        }
        .value-card:hover, .team-card:hover {
            transform: translateY(-5px);
        }
        .value-icon i {
            font-size: 3rem;
            color: #d4af37; /* Warna emas */
            margin-bottom: 20px;
        }
        .team-image {
            position: relative;
            margin-bottom: 20px;
            /* Penambahan CSS untuk memusatkan foto */
            display: block; /* Pastikan elemen adalah blok untuk margin auto */
            margin-left: auto;
            margin-right: auto;
            /* Akhir penambahan CSS */
        }
        .team-image img {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid #d4af37;
        }
        .team-social {
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(255,255,255,0.9);
            border-radius: 20px;
            padding: 5px 15px;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        .team-card:hover .team-social {
            opacity: 1;
        }
        .team-social a {
            color: #333;
            margin: 0 5px;
            font-size: 1.2rem;
        }
        .sustainability-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 40px;
            align-items: center;
        }
        @media (min-width: 992px) {
            .sustainability-grid {
                grid-template-columns: 1fr 1fr;
            }
        }
        .sustainability-image img {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        .sustainability-list {
            list-style: none;
            padding: 0;
        }
        .sustainability-list li {
            margin-bottom: 10px;
            font-size: 1.1rem;
            color: #555;
        }
        .sustainability-list li i {
            color: #28a745;
            margin-right: 10px;
        }
        .newsletter {
            background-color: #f2f2f2;
            padding: 60px 0;
            text-align: center;
        }
        .newsletter-content {
            max-width: 700px;
            margin: 0 auto;
        }
        .newsletter-title {
            font-family: 'Playfair Display', serif;
            font-size: 2.5rem;
            margin-bottom: 15px;
        }
        .newsletter-text {
            font-size: 1.1rem;
            color: #666;
            margin-bottom: 30px;
        }
        .newsletter-form {
            display: flex;
            justify-content: center;
            gap: 10px;
        }
        .newsletter-form input {
            padding: 12px 20px;
            border: 1px solid #ccc;
            border-radius: 5px;
            width: 60%;
            max-width: 400px;
            font-size: 1rem;
        }
        .newsletter-form button {
            padding: 12px 25px;
            background-color: #333;
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1rem;
            transition: background-color 0.3s ease;
        }
        .newsletter-form button:hover {
            background-color: #555;
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
                        <li><a href="index.php">Home</a></li>
                        <li><a href="collections.php">Collections</a></li>
                        <li><a href="about.php" class="active">About</a></li> <!-- Contact digabungkan ke About -->
                    </ul>
                </nav>

                <div class="nav-actions">
                    <a href="account.php" class="account-btn"><i class="far fa-user"></i></a>
                    <!-- Tombol keranjang diubah menjadi tautan ke checkout.php -->
                    <a href="checkout.php" class="cart-btn" id="cart-btn">
                        <i class="fas fa-shopping-bag"></i>
                        <span class="cart-count"><?php echo $cart_item_count; ?></span>
                    </a>
                    <button class="menu-btn" id="menu-btn"><i class="fas fa-bars"></i></button>
                </div>
            </div>
        </div>
    </header>

    <div class="mobile-menu" id="mobile-menu">
        <div class="mobile-menu-header">
            <button class="close-menu" id="close-menu"><i class="fas fa-times"></i></button>
        </div>
        <nav class="mobile-nav">
            <ul>
                <li><a href="index.php">Home</a></li>
                <li><a href="collections.php">Collections</a></li>
                <li><a href="about.php">About</a></li>
            </ul>
        </nav>
    </div>

    <!-- Bagian cart-sidebar dan cart-overlay yang mengandalkan JS akan dipertahankan,
         namun tombol di header utama kini mengarah ke checkout.php -->
    <div class="cart-sidebar" id="cart-sidebar">
        <div class="cart-header">
            <h3>Your Bag</h3>
            <button class="close-cart" id="close-cart"><i class="fas fa-times"></i></button>
        </div>
        <div class="cart-items" id="cart-items">
            <div class="empty-cart">
                <i class="fas fa-shopping-bag"></i>
                <p>Your bag is empty</p>
            </div>
        </div>
        <div class="cart-footer">
            <div class="cart-total">
                <span>Subtotal</span>
                <span class="total-price">$0.00</span>
            </div>
            <a href="checkout.php" class="checkout-btn">Checkout</a>
            <p class="cart-notice">Free shipping on all orders over $100</p>
        </div>
    </div>
    <div class="cart-overlay" id="cart-overlay"></div>

    <main>
        <section class="about-hero">
            <div class="container">
                <div class="about-hero-content">
                    <h1>Our Story & Connect With Us</h1>
                    <p>Discover the passion behind LUXE and how to get in touch</p>
                </div>
            </div>
        </section>

        <section class="section about-intro">
            <div class="container">
                <div class="about-intro-grid">
                    <div class="about-intro-image">
                        <img src="assets/b5964104396921793fdf00456ba6e446.jpg" alt="Our founder">
                    </div>
                    <div class="about-intro-content">
                        <h2 class="section-title">Memadukan Gaya dan Keanggunan yang Abadi</h2>
                        <p>Sejak didirikan tahun 2015, LUXE berawal dari sebuah studio kecil di Milan dengan satu tujuan sederhana: menciptakan pakaian yang nggak sekadar ikut tren, tapi bisa mencerminkan kepribadian tiap orang. Isabella Rossi, pendiri kami, percaya bahwa gaya yang sesungguhnya datang dari kualitas buatan tangan dan perhatian pada detail.</p>
                        <p>Sekarang, kami tetap menjaga nilai itu, sambil terus berinovasi dan menerapkan praktik yang ramah lingkungan. Setiap koleksi kami dirancang dengan penuh pertimbangan supaya bisa jadi andalan di lemari kamu untuk waktu yang lama.</p>
                        <div class="signature">
                            <img src="assets/foto profil girls 1.jpg" alt="Founder's signature">
                            <p>Isabella Rossi<br>Founder & Creative Director</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="section values-section">
            <div class="container">
                <div class="section-header">
                    <h2 class="section-title">Our Values</h2>
                    <p class="section-subtitle">The principles that guide everything we do</p>
                </div>
                
                <div class="values-grid">
                    <?php foreach ($company_values as $value): ?>
                    <div class="value-card">
                        <div class="value-icon">
                            <i class="fas <?php echo htmlspecialchars($value['icon']); ?>"></i>
                        </div>
                        <h3><?php echo htmlspecialchars($value['title']); ?></h3>
                        <p><?php echo htmlspecialchars($value['description']); ?></p>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <section class="section team-section">
            <div class="container">
                <div class="section-header">
                    <h2 class="section-title">Meet The Team</h2>
                    <p class="section-subtitle">The creative minds behind LUXE</p>
                </div>
                
                <div class="team-grid">
                    <?php foreach ($team_members as $member): ?>
                    <div class="team-card">
                        <div class="team-image">
                            <img src="<?php echo htmlspecialchars($member['image']); ?>" alt="<?php echo htmlspecialchars($member['name']); ?>">
                            <div class="team-social">
                                <a href="#"><i class="fab fa-instagram"></i></a>
                                <a href="#"><i class="fab fa-linkedin"></i></a>
                                <a href="#"><i class="fab fa-twitter"></i></a>
                            </div>
                        </div>
                        <h3><?php echo htmlspecialchars($member['name']); ?></h3>
                        <p><?php echo htmlspecialchars($member['role']); ?></p>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <section class="section sustainability-section">
            <div class="container">
                <div class="sustainability-grid">
                    <div class="sustainability-content">
                        <h2 class="section-title">Our Commitment to Sustainability</h2>
                        <p>At LUXE, we believe fashion should be beautiful inside and out. That's why we've implemented several initiatives to reduce our environmental impact:</p>
                        <ul class="sustainability-list">
                            <?php foreach ($sustainability_points as $point): ?>
                            <li><i class="fas fa-check"></i> <?php echo htmlspecialchars($point); ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <a href="#" class="btn">Learn More</a>
                    </div>
                    <div class="sustainability-image">
                        <img src="assets/Sustainable materials.jpg" alt="Sustainable materials">
                    </div>
                </div>
            </div>
        </section>

        <!-- Bagian Kontak yang digabungkan dari contact.php -->
        <section class="contact-section">
            <div class="container">
                <div class="section-header">
                    <h2 class="section-title">Contact Us</h2>
                    <p class="section-subtitle">We'd love to hear from you. Get in touch with us.</p>
                </div>
                <div class="row">
                    <div class="col-lg-7">
                        <div class="contact-form">
                            <h3>Send us a Message</h3>
                            <?php if ($message_sent): ?>
                                <div class="alert alert-success" role="alert">
                                    Thank you for your message! We will get back to you shortly.
                                </div>
                            <?php endif; ?>
                            <form action="about.php" method="POST"> <!-- Ubah action ke about.php -->
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
        <!-- Akhir Bagian Kontak -->

    </main>

    <section class="newsletter">
        <div class="container">
            <div class="newsletter-content">
                <h2 class="newsletter-title">Join Our Community</h2>
                <p class="newsletter-text">Subscribe to receive updates, access to exclusive offers, and more.</p>
                <form class="newsletter-form" method="POST" action="subscribe.php">
                    <input type="email" name="email" placeholder="Your email address" required>
                    <button type="submit">Subscribe</button>
                </form>
            </div>
        </div>
    </section>

    <footer class="footer">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-col">
                    <h3>LUXE</h3>
                    <p>Timeless elegance for the modern individual. Crafted with passion, designed to last.</p>
                </div>
                <div class="footer-col">
                    <h4>Quick Links</h4>
                    <ul>
                        <li><a href="about.php">About Us</a></li>
                        <li><a href="faq.html">FAQ</a></li>
                        <li><a href="shipping.html">Shipping & Returns</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h4>Follow Us</h4>
                    <div class="social-links">
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-facebook-f"></i></a>
                        <a href="#"><i class="fab fa-pinterest"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                    </div>
                </div>
                 <div class="footer-col">
                    <h4>Customer Service</h4>
                     <ul>
                        <li><a href="account.php">My Account</a></li>
                        <li><a href="orders.html">Track Order</a></li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> LUXE. All Rights Reserved.</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="script.js"></script>
</body>
</html>
