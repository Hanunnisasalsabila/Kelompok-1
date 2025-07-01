<?php
// File: db_connection.php

// Jika Anda memanggil session_start() di file lain (misal: index.php),
// sebaiknya hapus baris ini untuk menghindari notice 'session already active'.
// Jika tidak, biarkan saja.
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// --- PENGATURAN KONEKSI DATABASE ---

$db_host = 'localhost';   // Host database, biasanya 'localhost'
$db_user = 'root';        // Username database XAMPP default
$db_pass = '';            // Password database XAMPP default (kosong)
$db_name = 'luxe_db';     // Nama database Anda
$db_port = 3307;          // !! PENTING: Port MySQL dari XAMPP Control Panel Anda

// --- MEMBUAT KONEKSI ---

// Membuat koneksi baru ke database menggunakan MySQLi dengan menyertakan PORT
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name, $db_port);

// --- MEMERIKSA KONEKSI ---

// Memeriksa apakah terjadi error saat koneksi
if ($conn->connect_error) {
    // Jika koneksi gagal, hentikan eksekusi skrip dan tampilkan pesan error.
    die("Koneksi ke database gagal: " . $conn->connect_error);
}

// (Sangat disarankan) Mengatur set karakter ke utf8mb4 agar mendukung semua karakter
$conn->set_charset("utf8mb4");

// Variabel $conn sekarang siap digunakan di file lain yang memanggil file ini.

?>
