document.addEventListener('DOMContentLoaded', function() {
    // DOM elements (semua yang mungkin ada di kedua halaman)
    const menuBtn = document.getElementById('menu-btn');
    const closeMenuBtn = document.getElementById('close-menu');
    const mobileMenu = document.getElementById('mobile-menu');
    const cartBtn = document.getElementById('cart-btn');
    const closeCartBtn = document.getElementById('close-cart');
    const cartSidebar = document.getElementById('cart-sidebar');
    const cartOverlay = document.getElementById('cart-overlay');
    const cartItemsContainer = document.getElementById('cart-items');
    const cartCountElement = document.querySelector('.cart-count');
    const totalPriceElement = document.querySelector('.total-price');
    const addToCartBtns = document.querySelectorAll('.add-to-cart');
    const checkoutBtn = document.querySelector('.checkout-btn'); // Mungkin null di collections.html

    // DOM elements (khusus checkout.html - akan menjadi null di collections.html)
    const orderItemsContainer = document.getElementById('order-items');
    const subtotalPrice = document.querySelector('.subtotal-price');
    const grandTotalPrice = document.querySelector('.grand-total-price');
    const shippingForm = document.getElementById('shipping-form');
    const paymentOptions = document.querySelectorAll('input[name="payment-method"]');
    const creditCardDetails = document.querySelector('.credit-card-details');
    const paypalDetails = document.querySelector('.paypal-details');
    const cardNumberField = document.getElementById('card-number');
    const expiryDateField = document.getElementById('expiry-date');
    const cvvField = document.getElementById('cvv');

    let cart = JSON.parse(localStorage.getItem('cart')) || [];
    updateCartDisplay();

    // ========== Mobile Menu Toggle ==========
    if (menuBtn && closeMenuBtn && mobileMenu) {
        menuBtn.addEventListener('click', function() {
            mobileMenu.classList.add('active');
            document.body.style.overflow = 'hidden';
        });

        closeMenuBtn.addEventListener('click', function() {
            mobileMenu.classList.remove('active');
            document.body.style.overflow = '';
        });
    }

    // ========== Shopping Cart Functionality (Open/Close) ==========
    if (cartBtn && closeCartBtn && cartSidebar && cartOverlay) {
        cartBtn.addEventListener('click', function() {
            cartSidebar.classList.add('active');
            cartOverlay.classList.add('active');
            document.body.style.overflow = 'hidden';
        });

        closeCartBtn.addEventListener('click', function() {
            cartSidebar.classList.remove('active');
            cartOverlay.classList.remove('active');
            document.body.style.overflow = '';
        });

        cartOverlay.addEventListener('click', function() {
            cartSidebar.classList.remove('active');
            cartOverlay.classList.remove('active');
            document.body.style.overflow = '';
        });
    }

    // ========== Add to Cart ==========
    if (addToCartBtns) {
        addToCartBtns.forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const id = this.dataset.id;
                const name = this.dataset.name;
                const price = parseFloat(this.dataset.price);
                const image = this.closest('.product-card').querySelector('img').src;

                const existingItem = cart.find(item => item.id === id);

                if (existingItem) {
                    existingItem.quantity += 1;
                } else {
                    cart.push({ id, name, price, image, quantity: 1 });
                }

                localStorage.setItem('cart', JSON.stringify(cart));
                updateCartDisplay();
                showNotification(`${name} added to cart`);
            });
        });
    }

    // ========== Update Cart Display (Handles both sidebar and order summary conditionally) ==========
    function updateCartDisplay() {
        if (cartItemsContainer) {
            cartItemsContainer.innerHTML = '';
            let itemCount = 0;
            let total = 0;

            if (cart.length === 0) {
                cartItemsContainer.innerHTML = `
                    <div class="empty-cart">
                        <i class="fas fa-shopping-bag"></i>
                        <p>Your bag is empty</p>
                    </div>
                `;
                if (orderItemsContainer) {
                    orderItemsContainer.innerHTML = `
                        <div class="empty-cart-message">
                            <i class="fas fa-shopping-bag"></i>
                            <p>Your cart is empty</p>
                            <a href="collections.html" class="btn">Continue Shopping</a>
                        </div>
                    `;
                }
                if (cartCountElement) cartCountElement.textContent = '0';
                if (totalPriceElement) totalPriceElement.textContent = '$0.00';
                if (subtotalPrice) subtotalPrice.textContent = '$0.00';
                if (grandTotalPrice) grandTotalPrice.textContent = '$0.00';
                if (checkoutBtn) checkoutBtn.disabled = true;
                localStorage.setItem('cart', JSON.stringify(cart));
                return;
            }

            cart.forEach(item => {
                const itemTotal = item.price * item.quantity;
                total += itemTotal;

                // Add items to sidebar cart
                const cartItemElement = document.createElement('div');
                cartItemElement.classList.add('cart-item');
                cartItemElement.innerHTML = `
                    <div class="cart-item-details">
                        <h4 class="cart-item-title">${item.name}</h4>
                        <pclass="cart-item-price">$${item.price.toFixed(2)}</p>
                        <div class="cart-item-actions">
                            <div class="quantity-selector">
                                <button class="quantity-btn minus" data-id="${item.id}">-</button>
                                <input type="number" class="quantity-input" value="${item.quantity}" min="1" data-id="${item.id}">
                                <button class="quantity-btn plus" data-id="${item.id}">+</button>
                            </div>
                            <button class="remove-item" data-id="${item.id}">
                                <i class="fas fa-trash-alt"></i> Remove
                            </button>
                        </div>
                    </div>
                `;
                if (cartItemsContainer) cartItemsContainer.appendChild(cartItemElement);

                // Add items to order summary (if on checkout page)
                if (orderItemsContainer) {
                    const orderItemElement = document.createElement('div');
                    orderItemElement.classList.add('order-item');
                    orderItemElement.innerHTML = `
                        <div class="order-item-image">
                            <img src="${item.image}" alt="${item.name}">
                        </div>
                        <div class="order-item-details">
                            <h4>${item.name}</h4>
                            <p>$${item.price.toFixed(2)} × ${item.quantity}</p>
                        </div>
                        <div class="order-item-price">
                            $${itemTotal.toFixed(2)}
                        </div>
                    `;
                    orderItemsContainer.appendChild(orderItemElement);
                }
            });

            if (cartCountElement) cartCountElement.textContent = cart.reduce((total, item) => total + item.quantity, 0);
            if (totalPriceElement) totalPriceElement.textContent = `$${total.toFixed(2)}`;
            if (subtotalPrice) subtotalPrice.textContent = `$${total.toFixed(2)}`;
            if (grandTotalPrice) grandTotalPrice.textContent = `$${total.toFixed(2)}`;
            if (checkoutBtn && cart.length > 0) checkoutBtn.disabled = false;
            localStorage.setItem('cart', JSON.stringify(cart));

            // Add event listeners to quantity buttons in sidebar (after items are rendered)
            const minusBtns = document.querySelectorAll('.quantity-btn.minus');
            const plusBtns = document.querySelectorAll('.quantity-btn.plus');
            const quantityInputs = document.querySelectorAll('.quantity-input');
            const removeBtns = document.querySelectorAll('.remove-item');

            minusBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    const id = btn.dataset.id;
                    updateQuantity(id, -1);
                });
            });

            plusBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    const id = btn.dataset.id;
                    updateQuantity(id, 1);
                });
            });

            quantityInputs.forEach(input => {
                input.addEventListener('change', function() {
                    const id = this.dataset.id;
                    const newQuantity = parseInt(this.value);
                    if (newQuantity > 0) {
                        const item = cart.find(item => item.id === id);
                        if (item) {
                            item.quantity = newQuantity;
                            updateCartDisplay();
                        }
                    } else {
                        this.value = 1;
                    }
                });
            });

            removeBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    const id = this.dataset.id;
                    removeFromCart(id);
                });
            });
        }
    }

    // ========== Update Item Quantity ==========
    function updateQuantity(id, change) {
        const item = cart.find(item => item.id === id);
        if (item) {
            item.quantity += change;
            if (item.quantity <= 0) {
                cart = cart.filter(item => item.id !== id);
            }
            updateCartDisplay();
        }
    }

    // ========== Remove Item from Cart ==========
    function removeFromCart(id) {
        cart = cart.filter(item => item.id !== id);
        updateCartDisplay();
        showNotification('Item removed from cart');
    }

    // ========== Checkout Functionality (Only if checkout button exists) ==========
    if (checkoutBtn) {
        checkoutBtn.addEventListener('click', function(e) {
            e.preventDefault();
            if (cart.length === 0) {
                showNotification('Your cart is empty');
                return;
            }
            window.location.href = 'checkout.html';
        });
    }

    // ========== Show Notification ==========
    function showNotification(message) {
        const notification = document.createElement('div');
        notification.classList.add('notification');
        notification.textContent = message;
        document.body.appendChild(notification);
        setTimeout(() => notification.classList.add('show'), 10);
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    }

    // Initialize Cart
    updateCartDisplay();

    // ========== Quick View Modal (Jika ada di kedua halaman) ==========
    const quickViewBtns = document.querySelectorAll('.quick-view');
    if (quickViewBtns) {
        quickViewBtns.forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                alert('Quick view feature would open a modal with product details');
            });
        });
    }

    // ========== Wishlist (Jika ada di kedua halaman) ==========
    const wishlistBtns = document.querySelectorAll('.wishlist');
    if (wishlistBtns) {
        wishlistBtns.forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const icon = btn.querySelector('i');
                icon.classList.toggle('far');
                icon.classList.toggle('fas');
                showNotification(icon.classList.contains('fas') ? 'Added to wishlist' : 'Removed from wishlist');
            });
        });
    }

    // ========== Add notification styles dynamically ==========
    const style = document.createElement('style');
    style.textContent = `
        .notification {
            position: fixed;
            bottom: 2rem;
            left: 50%;
            transform: translateX(-50%);
            background-color: #333;
            color: #fff;
            padding: 1rem 2rem;
            border-radius: 0.5rem;
            font-size: 1.4rem;
            opacity: 0;
            transition: opacity 0.3s ease;
            z-index: 2000;
        }

        .notification.show {
            opacity: 1;
        }

        .checkout-btn:disabled {
            background-color: #ccc;
            cursor: not-allowed;
        }

        .fa-spin {
            animation: fa-spin 2s infinite linear;
        }

        @keyframes fa-spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    `;
    document.head.appendChild(style);

    // ========== Form Submission (Khusus checkout.html) ==========
    if (shippingForm) {
        shippingForm.addEventListener('submit', function(e) {
            e.preventDefault();
            let isValid = true;
            const requiredFields = this.querySelectorAll('[required]');

            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    field.style.borderColor = 'var(--error-color)';
                    isValid = false;
                } else {
                    field.style.borderColor = '';
                }
            });

            if (!isValid) {
                alert('Please fill in all required fields.');
                return;
            }

            const selectedPayment = document.querySelector('input[name="payment-method"]:checked')?.value;

            if (selectedPayment === 'credit-card') {
                const cardNumber = document.getElementById('card-number')?.value.replace(/\s/g, '');
                const expiryDate = document.getElementById('expiry-date')?.value;
                const cvv = document.getElementById('cvv')?.value;

                if (!/^\d{16}$/.test(cardNumber)) {
                    alert('Please enter a valid 16-digit card number.');
                    return;
                }
                if (!/^\d{2}\/\d{2}$/.test(expiryDate)) {
                    alert('Please enter a valid expiry date in MM/YY format.');
                    return;
                }
                if (!/^\d{3,4}$/.test(cvv)) {
                    alert('Please enter a valid CVV (3 or 4 digits).');
                    return;
                }
            }

            if (selectedPayment === 'paypal') {
                alert('You will now be redirected to PayPal to complete your payment.');
            }

            cart = [];
            localStorage.setItem('cart', JSON.stringify(cart));
            updateCartDisplay();
            shippingForm.reset();
            alert('Order successfully placed! Thank you for your purchase.');
            // window.location.href = 'confirmation.html';
        });
    }

    // ========== Payment Method Toggle (Khusus checkout.html) ==========
    if (paymentOptions) {
        paymentOptions.forEach(option => {
            option.addEventListener('change', function() {
                if (creditCardDetails && paypalDetails && cardNumberField && expiryDateField && cvvField) {
                    if (this.value === 'credit-card') {
                        creditCardDetails.classList.remove('d-none');
                        paypalDetails.classList.add('d-none');
                        cardNumberField.required = true;
                        expiryDateField.required = true;
                        cvvField.required = true;
                    } else if (this.value === 'paypal') {
                        creditCardDetails.classList.add('d-none');
                        paypalDetails.classList.remove('d-none');
                        cardNumberField.required = false;
                        expiryDateField.required = false;
                        cvvField.required = false;
                    }
                }
            });
        });
    }
    
});
document.addEventListener('DOMContentLoaded', function() {
    // --- Navigasi Mobile ---
    const menuBtn = document.getElementById('menu-btn');
    const closeMenuBtn = document.getElementById('close-menu');
    const mobileMenu = document.getElementById('mobile-menu');

    if (menuBtn && mobileMenu) {
        menuBtn.addEventListener('click', function() {
            mobileMenu.classList.add('active');
        });
    }

    if (closeMenuBtn && mobileMenu) {
        closeMenuBtn.addEventListener('click', function() {
            mobileMenu.classList.remove('active');
        });
    }

    // --- Fungsionalitas Edit/Simpan Informasi Akun ---
    const editInfoBtn = document.getElementById('edit-info-btn');
    const nameInput = document.getElementById('name');
    const emailInput = document.getElementById('email');
    const addressInput = document.getElementById('address');

    // **PENTING:** Bidang input TIDAK akan dinonaktifkan secara default di sini.
    // Mereka akan langsung bisa diketik saat halaman dimuat.
    // Tombol "Edit Informasi" akan berfungsi sebagai tombol "Simpan".

    if (editInfoBtn && nameInput && emailInput && addressInput) { // Pastikan semua elemen ada
        // Set teks awal tombol menjadi "Simpan Perubahan" karena bidang bisa langsung diedit
        // atau biarkan "Edit Informasi" jika Anda ingin itu berfungsi sebagai toggle.
        // Jika Anda ingin tombol ini HANYA menjadi tombol 'Simpan' dan bidang selalu dapat diedit,
        // Anda bisa set teksnya secara default menjadi 'Simpan Perubahan'.
        // editInfoBtn.textContent = 'Simpan Perubahan';

        editInfoBtn.addEventListener('click', function() {
            // Dalam skenario ini, karena bidang selalu dapat diedit,
            // tombol ini selalu berfungsi sebagai tombol "Simpan".
            // Anda bisa menambahkan logika validasi atau AJAX di sini.

            // --- Simulasi Proses Simpan ---
            const newName = nameInput.value;
            const newEmail = emailInput.value;
            const newAddress = addressInput.value;

            console.log('Informasi Disimpan:');
            console.log('Nama: ' + newName);
            console.log('Email: ' + newEmail);
            console.log('Alamat: ' + newAddress);

            // Tambahkan kelas untuk efek visual 'menyimpan' atau loading jika diinginkan
            editInfoBtn.classList.add('saving'); // Anda bisa menambahkan CSS untuk ini
            editInfoBtn.textContent = 'Menyimpan...';

            setTimeout(() => {
                alert('Informasi Anda telah disimpan!');
                editInfoBtn.textContent = 'Edit Informasi'; // Setelah disimpan, kembali ke teks default
                editInfoBtn.classList.remove('saving'); // Hapus kelas 'saving'
                editInfoBtn.classList.remove('save-btn'); // Hapus juga save-btn jika ada
                // Di aplikasi nyata, setelah sukses simpan,
                // mungkin Anda ingin menonaktifkan kembali field jika diperlukan
                // atau refresh data dari server.
            }, 1500); // Simulasi waktu penyimpanan
            // --- Akhir Simulasi Proses Simpan ---
        });
    }
});
document.addEventListener('DOMContentLoaded', function() {
    // --- NAVIGASI MOBILE (Global) ---
    const menuBtn = document.getElementById('menu-btn');
    const closeMenuBtn = document.getElementById('close-menu');
    const mobileMenu = document.getElementById('mobile-menu');

    if (menuBtn && mobileMenu) {
        menuBtn.addEventListener('click', function() {
            mobileMenu.classList.add('active');
        });
    }

    if (closeMenuBtn && mobileMenu) {
        closeMenuBtn.addEventListener('click', function() {
            mobileMenu.classList.remove('active');
        });
    }

    // --- Fungsionalitas Edit/Simpan Informasi Akun (Hanya di account.html) ---
    const accountDetailsSection = document.querySelector('.account-details');
    if (accountDetailsSection) { // Cek apakah kita di halaman akun
        const editInfoBtn = document.getElementById('edit-info-btn');
        const nameInput = document.getElementById('name');
        const emailInput = document.getElementById('email');
        const addressInput = document.getElementById('address');

        if (editInfoBtn && nameInput && emailInput && addressInput) {
            editInfoBtn.addEventListener('click', function() {
                const newName = nameInput.value;
                const newEmail = emailInput.value;
                const newAddress = addressInput.value;

                console.log('Informasi Disimpan:');
                console.log('Nama: ' + newName);
                console.log('Email: ' + newEmail);
                console.log('Alamat: ' + newAddress);

                editInfoBtn.classList.add('saving');
                editInfoBtn.textContent = 'Menyimpan...';

                setTimeout(() => {
                    alert('Informasi Anda telah disimpan!');
                    editInfoBtn.textContent = 'Edit Informasi';
                    editInfoBtn.classList.remove('saving');
                    editInfoBtn.classList.remove('save-btn');
                }, 1500); // Simulasi waktu penyimpanan
            });
        }
    }

    // --- FUNGSI GLOBAL STATUS LOGIN/LOGOUT ---
    const navActions = document.querySelector('.nav-actions');
    const accountBtn = document.querySelector('.nav-actions .account-btn');

    // Buat tombol logout baru
    const logoutBtn = document.createElement('button');
    logoutBtn.classList.add('logout-btn');
    logoutBtn.innerHTML = '<i class="fas fa-sign-out-alt"></i>';
    logoutBtn.title = 'Logout';

    function checkLoginStatus() {
        const isLoggedIn = localStorage.getItem('isLoggedIn') === 'true';
        const userRole = localStorage.getItem('userRole'); // 'user' atau 'admin'
        const currentPage = window.location.pathname.split('/').pop(); // Mengambil nama file halaman

        if (isLoggedIn) {
            // Tampilkan tombol akun (tidak disembunyikan)
            if (accountBtn) {
                accountBtn.style.display = 'block'; // Pastikan terlihat
                // Opsional: atur link akun sesuai peran jika diperlukan,
                // untuk saat ini biarkan default ke account.html
                // if (userRole === 'admin') { accountBtn.href = 'admin-dashboard.html'; }
                // else { accountBtn.href = 'account.html'; }
            }

            // Tambahkan tombol logout jika belum ada
            if (!navActions.querySelector('.logout-btn')) {
                // Tambahkan setelah account-btn
                if (accountBtn) {
                    navActions.insertBefore(logoutBtn, accountBtn.nextSibling);
                } else {
                    // Jika accountBtn tidak ada (jarang), tambahkan di akhir nav-actions
                    navActions.appendChild(logoutBtn);
                }
            }

            // Redirect jika di halaman login tapi sudah login
            if (currentPage === 'login.php') {
                if (userRole === 'admin') {
                    window.location.href = 'admin-dashboard.php';
                } else { // default ke user
                    window.location.href = 'account.php';
                }
            }

        } else { // Belum login
            // Tombol akun tetap ada, tapi mungkin perlu arahkan ke login.html
            if (accountBtn) {
                accountBtn.style.display = 'block';
                accountBtn.href = 'login.php'; // Pastikan mengarah ke halaman login jika belum login
            }

            // Jika ada tombol logout, hapus
            if (navActions.querySelector('.logout-btn')) {
                navActions.removeChild(logoutBtn);
            }

            // Redirect jika di halaman yang seharusnya membutuhkan login (selain login.html)
            const protectedPages = ['account.html', 'admin-dashboard.html'];
            if (protectedPages.includes(currentPage) && currentPage !== 'login.php') {
                 alert('Anda harus login untuk mengakses halaman ini.');
                 window.location.href = 'login.php';
            }
        }
    }

    // Panggil saat halaman dimuat
    checkLoginStatus();

    // Event listener untuk tombol logout di header
    logoutBtn.addEventListener('click', function() {
        performLogout();
    });

    // --- FUNGSI LOGOUT UMUM ---
    function performLogout() {
        if (confirm('Anda yakin ingin keluar?')) {
            localStorage.removeItem('isLoggedIn'); // Hapus status login
            localStorage.removeItem('userRole');    // Hapus peran pengguna
            checkLoginStatus(); // Perbarui tampilan header
            alert('Anda telah keluar.');
            window.location.href = 'login.php'; // Arahkan ke halaman login
        }
    }

    // --- FUNGSI HALAMAN LOGIN (login.html) ---
    const loginContainerCustom = document.querySelector('.login-container-custom');
    if (loginContainerCustom) { // Cek apakah kita di halaman login
        const tabButtons = document.querySelectorAll('.tab-btn');
        const loginForms = document.querySelectorAll('.login-form-tab');

        // Mengelola pergantian tab Login User/Admin
        tabButtons.forEach(button => {
            button.addEventListener('click', function() {
                tabButtons.forEach(btn => btn.classList.remove('active'));
                loginForms.forEach(form => form.classList.remove('active'));

                this.classList.add('active');

                const targetTab = this.dataset.tab;
                document.getElementById(`${targetTab}-login-form`).classList.add('active');
            });
        });

        // Fungsionalitas Login User
        const userLoginForm = document.getElementById('user-login-form');
        if (userLoginForm) {
            userLoginForm.addEventListener('submit', function(event) {
                event.preventDefault();

                const email = document.getElementById('user-email').value;
                const password = document.getElementById('user-password').value;

                const correctUserEmail = 'user@example.com';
                const correctUserPassword = 'user123';

                if (email === correctUserEmail && password === correctUserPassword) {
                    // alert('Login User Berhasil!'); // Hilangkan alert ini untuk smooth redirect
                    localStorage.setItem('isLoggedIn', 'true');
                    localStorage.setItem('userRole', 'user');
                    checkLoginStatus(); // Memanggil ulang untuk redirect
                } else {
                    alert('Email/Kata Sandi User salah. Silakan coba lagi.');
                    document.getElementById('user-password').value = '';
                }
            });
        }

        // Fungsionalitas Login Admin
        const adminLoginForm = document.getElementById('admin-login-form');
        if (adminLoginForm) {
            adminLoginForm.addEventListener('submit', function(event) {
                event.preventDefault();

                const email = document.getElementById('admin-email').value;
                const password = document.getElementById('admin-password').value;

                const correctAdminEmail = 'admin@luxe.com';
                const correctAdminPassword = 'admin123';

                if (email === correctAdminEmail && password === correctAdminPassword) {
                    // alert('Login Admin Berhasil!'); // Hilangkan alert ini untuk smooth redirect
                    localStorage.setItem('isLoggedIn', 'true');
                    localStorage.setItem('userRole', 'admin');
                    checkLoginStatus(); // Memanggil ulang untuk redirect
                } else {
                    alert('Email/Kata Sandi Admin salah. Silakan coba lagi.');
                    document.getElementById('admin-password').value = '';
                }
            });
        }
    }

    // --- FUNGSI HALAMAN AKUN (account.html) ---
    // Pastikan kode ini hanya berjalan jika kita berada di halaman akun (menggunakan body class 'account-page')
    if (document.body.classList.contains('account-page')) {
        const accountSidebarLinks = document.querySelectorAll('.account-sidebar li a');
        const accountContentCards = document.querySelectorAll('.account-content-card');
        const sidebarLogoutBtn = document.getElementById('sidebar-logout-btn');

        if (accountSidebarLinks.length > 0 && accountContentCards.length > 0) {
            accountSidebarLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    // Cek apakah link adalah tombol logout di sidebar
                    if (this.id === 'sidebar-logout-btn') {
                        e.preventDefault(); // Cegah default link
                        performLogout(); // Panggil fungsi logout umum
                        return; // Hentikan eksekusi lebih lanjut
                    }

                    e.preventDefault(); // Mencegah default link untuk navigasi konten

                    // Hapus 'active' dari semua link sidebar dan semua kartu konten
                    accountSidebarLinks.forEach(item => item.classList.remove('active'));
                    accountContentCards.forEach(card => card.classList.remove('active'));

                    // Tambahkan 'active' ke link yang diklik
                    this.classList.add('active');

                    // Tampilkan kartu konten yang sesuai dengan data-content-id
                    const contentId = this.dataset.contentId;
                    if (contentId) {
                        const targetCard = document.getElementById(contentId);
                        if (targetCard) {
                            targetCard.classList.add('active');
                        }
                    }
                });
            });
        }
    }
});

// Carousel functionality
document.addEventListener('DOMContentLoaded', function() {
    // Carousel
    const slides = document.querySelectorAll('.carousel-slide');
    const prevBtn = document.querySelector('.carousel-prev');
    const nextBtn = document.querySelector('.carousel-next');
    let currentSlide = 0;
    
    function showSlide(index) {
        slides.forEach(slide => slide.classList.remove('active'));
        slides[index].classList.add('active');
    }
    
    function nextSlide() {
        currentSlide = (currentSlide + 1) % slides.length;
        showSlide(currentSlide);
    }
    
    function prevSlide() {
        currentSlide = (currentSlide - 1 + slides.length) % slides.length;
        showSlide(currentSlide);
    }
    
    nextBtn.addEventListener('click', nextSlide);
    prevBtn.addEventListener('click', prevSlide);
    
    // Auto-rotate carousel
    setInterval(nextSlide, 5000);
    
    // Mobile menu toggle
    const menuBtn = document.getElementById('menu-btn');
    const mobileMenu = document.getElementById('mobile-menu');
    const closeMenu = document.getElementById('close-menu');
    
    menuBtn.addEventListener('click', function() {
        mobileMenu.classList.add('active');
    });
    
    closeMenu.addEventListener('click', function() {
        mobileMenu.classList.remove('active');
    });
    
    // Cart functionality
    const cartBtn = document.getElementById('cart-btn');
    const cartSidebar = document.getElementById('cart-sidebar');
    const closeCart = document.getElementById('close-cart');
    const cartOverlay = document.getElementById('cart-overlay');
    
    cartBtn.addEventListener('click', function() {
        cartSidebar.classList.add('active');
        cartOverlay.classList.add('active');
    });
    
    closeCart.addEventListener('click', function() {
        cartSidebar.classList.remove('active');
        cartOverlay.classList.remove('active');
    });
    
    cartOverlay.addEventListener('click', function() {
        cartSidebar.classList.remove('active');
        cartOverlay.classList.remove('active');
    });
    
    // Add to cart functionality
    const addToCartBtns = document.querySelectorAll('.add-to-cart');
    
    addToCartBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const id = btn.getAttribute('data-id');
            const name = btn.getAttribute('data-name');
            const price = parseFloat(btn.getAttribute('data-price'));
            const image = btn.closest('.product-card').querySelector('img').src;
            
            addToCart(id, name, price, image);
        });
    });
    
    function addToCart(id, name, price, image) {
        let cart = JSON.parse(localStorage.getItem('cart')) || [];
        const existingItem = cart.find(item => item.id === id);
        
        if (existingItem) {
            existingItem.quantity += 1;
        } else {
            cart.push({ id, name, price, image, quantity: 1 });
        }
        
        localStorage.setItem('cart', JSON.stringify(cart));
        updateCartCount();
    }
    
    function updateCartCount() {
        const cart = JSON.parse(localStorage.getItem('cart')) || [];
        const count = cart.reduce((total, item) => total + item.quantity, 0);
        document.querySelector('.cart-count').textContent = count;
    }
    
    // Initialize cart count
    updateCartCount();
});

// Wishlist functionality
function toggleWishlist(productId, productName, productPrice, productImage) {
    let wishlist = JSON.parse(localStorage.getItem('wishlist')) || [];
    const index = wishlist.findIndex(item => item.id === productId);
    
    if (index === -1) {
        // Add to wishlist
        wishlist.push({
            id: productId,
            name: productName,
            price: productPrice,
            image: productImage
        });
    } else {
        // Remove from wishlist
        wishlist.splice(index, 1);
    }
    
    localStorage.setItem('wishlist', JSON.stringify(wishlist));
    updateWishlistButtons();
    updateWishlistCount();
}

function updateWishlistButtons() {
    const wishlist = JSON.parse(localStorage.getItem('wishlist')) || [];
    document.querySelectorAll('.wishlist-btn').forEach(btn => {
        const productId = btn.closest('.product-card').getAttribute('data-id') || 
                         btn.closest('.product-card').querySelector('.add-to-cart').getAttribute('data-id');
        
        if (wishlist.some(item => item.id === productId)) {
            btn.classList.add('active');
            btn.innerHTML = '<i class="fas fa-heart"></i>';
        } else {
            btn.classList.remove('active');
            btn.innerHTML = '<i class="far fa-heart"></i>';
        }
    });
}

function updateWishlistCount() {
    const wishlist = JSON.parse(localStorage.getItem('wishlist')) || [];
    document.querySelectorAll('.wishlist-count').forEach(el => {
        el.textContent = wishlist.length;
    });
}

// Initialize wishlist on page load
document.addEventListener('DOMContentLoaded', function() {
    updateWishlistButtons();
    updateWishlistCount();
    
    // Wishlist button click event
    document.querySelectorAll('.wishlist-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const productCard = this.closest('.product-card');
            const productId = productCard.querySelector('.add-to-cart').getAttribute('data-id');
            const productName = productCard.querySelector('.product-title').textContent;
            const productPrice = productCard.querySelector('.product-price').textContent.replace('$', '').trim();
            const productImage = productCard.querySelector('.product-image img').src;
            
            toggleWishlist(productId, productName, productPrice, productImage);
        });
    });
});

