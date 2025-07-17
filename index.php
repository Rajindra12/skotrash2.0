<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Skotrash - Bank Sampah Digital</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-gradient-to-b from-green-50 to-white">

<?php include 'koneksi.php'; ?>

<!-- Header -->
<header class="sticky top-0 z-50 bg-white/95 backdrop-blur-sm border-b border-green-100">
  <div class="container mx-auto px-4 py-3">
    <div class="flex items-center justify-between">
      <h1 class="text-xl font-bold text-green-700">Skotrash</h1>
      <a href="home.php" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded">Login</a>
    </div>
  </div>
</header>

<!-- Hero Section -->
<section class="px-4 py-12 text-center">
  <div class="container mx-auto">
    <span class="inline-block bg-green-100 text-green-700 px-3 py-1 rounded mb-4">🌱 Program Lingkungan SMK Telkom</span>
    <h2 class="text-4xl md:text-5xl font-bold text-gray-900 mb-4">
      Bank Sampah Digital <span class="block text-green-600">SMK Telkom</span>
    </h2>
    <p class="text-gray-600 text-lg mb-8 max-w-md mx-auto">
      Kelola sampah dengan cerdas, raih poin, dan wujudkan sekolah yang lebih hijau bersama teknologi digital
    </p>
    <div class="flex flex-col sm:flex-row gap-4 justify-center mb-12">
      <a href="#" class="bg-green-600 hover:bg-green-700 text-white px-8 py-3 text-lg rounded inline-flex items-center">
        Mulai Setor Sampah
      </a>
      <a href="#" class="border border-red-600 text-red-600 hover:bg-red-600 hover:text-white px-8 py-3 text-lg rounded">
        Lihat Poin Saya
      </a>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-3 gap-6 max-w-md mx-auto">
      <div class="text-center">
        <div class="text-3xl font-bold text-green-600">
          <?php
          $sampah = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(jumlah) as total FROM detail_penyetoran"))['total'] ?? 0;
          echo round($sampah, 2);
          ?>
        </div>
        <div class="text-sm text-gray-500">Kg Sampah</div>
      </div>
      <div class="text-center">
        <div class="text-3xl font-bold text-red-600">
          <?php
          $siswa = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM users"))['total'] ?? 0;
          echo $siswa;
          ?>
        </div>
        <div class="text-sm text-gray-500">Siswa Aktif</div>
      </div>
      <div class="text-center">
        <div class="text-3xl font-bold text-green-600">
          <?php
          $hari = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(DISTINCT tanggal) as total FROM penyetoran"))['total'] ?? 0;
          echo $hari;
          ?>
        </div>
        <div class="text-sm text-gray-500">Hari Aktif</div>
      </div>
    </div>
  </div>
</section>

<!-- Footer -->
<footer class="bg-gray-900 text-white px-4 py-12">
  <div class="container mx-auto text-center">
    <h1 class="text-xl font-bold text-green-400">Skotrash</h1>
    <p class="text-gray-400 text-base mt-4 mb-6">Bank Sampah Digital untuk masa depan yang lebih hijau</p>
    <div class="text-gray-400 space-y-2">
      <div class="flex justify-center gap-2 items-center">
        <span>📍</span><span>SMK Telkom Jakarta</span>
      </div>
      <div class="flex justify-center gap-2 items-center">
        <span>📞</span><span>(021) 1234-5678</span>
      </div>
      <div class="flex justify-center gap-2 items-center">
        <span>✉️</span><span>ecobank@smktelkom.sch.id</span>
      </div>
    </div>
    <div class="border-t border-gray-800 mt-8 pt-6">
      <p class="text-sm text-gray-500">© 2024 EcoBank SMK Telkom. All rights reserved.</p>
    </div>
  </div>
</footer>

</body>
</html>
