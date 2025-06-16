<?php

session_start();
require_once 'db_connection.php'; // Ini akan mengimpor variabel $conn

// ... sisa kode Anda bisa menggunakan variabel $conn untuk query ...
// Contoh: $result = $conn->query("SELECT * FROM users");
// Data untuk bagian dinamis (dalam aplikasi nyata, ini bisa datang dari database)
$page_title = 'About Us | LUXE';

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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Montserrat:wght@300;400;500&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
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
                        <li><a href="about.php">About</a></li>
                        <li><a href="contact.php">Contact</a></li>
                    </ul>
                </nav>

                <div class="nav-actions">
                    <a href="account.php" class="account-btn"><i class="far fa-user"></i></a>
                    <button class="cart-btn" id="cart-btn">
                        <i class="fas fa-shopping-bag"></i>
                        <span class="cart-count">0</span>
                    </button>
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
                <li><a href="contact.php">Contact</a></li>
            </ul>
        </nav>
    </div>

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
                    <h1>Our Story</h1>
                    <p>Discover the passion behind LUXE</p>
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
                        <li><a href="contact.php">Contact</a></li>
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

    <script src="script.js"></script>
</body>
</html>