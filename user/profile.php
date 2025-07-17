<!-- profile.php -->
<?php
session_start();
include '../koneksi.php';

if (!isset($_SESSION['user'])) {
  header("Location: ../login_user.php");
  exit;
}

$user = $_SESSION['user'];
$user_id = $user['id'];
$nama = $user['nama'];
$kelas = $user['kelas'];
$nis = $user['nis'];
$total_poin = $user['total_poin'];

// Ambil total poin dari detail penyetoran (subtotal_poin)
$result_poin = mysqli_query($conn, "
  SELECT SUM(dp.subtotal_poin) as skor 
  FROM detail_penyetoran dp
  JOIN penyetoran p ON dp.penyetoran_id = p.id
  WHERE p.user_id = $user_id
");
$skor = mysqli_fetch_assoc($result_poin)['skor'] ?? 0;

// Ambil jumlah total sampah disetor (jumlah)
$result_jumlah = mysqli_query($conn, "
  SELECT SUM(dp.jumlah) as total_jumlah
  FROM detail_penyetoran dp
  JOIN penyetoran p ON dp.penyetoran_id = p.id
  WHERE p.user_id = $user_id
");
$jumlah_setoran = mysqli_fetch_assoc($result_jumlah)['total_jumlah'] ?? 0;
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Profil Saya - Skotrash</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-green-50 min-h-screen">

  <div class="container mx-auto px-4 py-8">
    <!-- Header -->
    <div class="bg-white shadow rounded-lg p-6 mb-6 text-center">
      <div class="w-20 h-20 mx-auto bg-green-100 rounded-full flex items-center justify-center text-green-700 text-4xl mb-4">
        👤
      </div>
    </div>

    <!-- Skor dan Jumlah Setoran -->
    <div class="bg-white shadow rounded-lg p-6 mb-6">
      <div class="flex justify-between">
        <div class="text-center">
          <p class="text-2xl font-bold text-green-700">Skor/ranking: <?= $skor ?></p>
        </div>
        <div class="text-center">
          <p class="text-2xl font-bold text-green-700">Jumlah setoran: <?= $jumlah_setoran ?> kg</p>
        </div>
      </div>
    </div>

    <!-- Informasi User -->
<div class="bg-white shadow rounded-lg p-6">
  <ul class="space-y-2 text-gray-700">
    <li><strong>Username:</strong> <?= htmlspecialchars($nama) ?></li>
    <li><strong>Kelas:</strong> <?= htmlspecialchars($kelas) ?></li>
    <li><strong>NIS:</strong> <?= htmlspecialchars($nis) ?></li>
    <li><strong>Total Poin:</strong> <?= $total_poin ?></li>
  </ul>

  <!-- Tombol Logout -->
  <div class="mt-6 text-center">
    <a href="../logout.php" 
       class="inline-block bg-red-500 hover:bg-red-600 text-white font-semibold py-2 px-4 rounded-lg shadow">
      Logout
    </a>
  </div>
</div>


    <!-- Navigasi Bawah -->
  <nav class="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 shadow-inner z-50">
    <div class="flex justify-around text-sm text-gray-500">
      <a href="index.php" class="flex flex-col items-center p-2 hover:text-green-600">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 mb-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M13 5v6h6m0 0l2 2m-2-2l-7 7-7-7" />
        </svg>
        Beranda
      </a>
      <a href="topup.php" class="flex flex-col items-center p-2 hover:text-green-600">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 mb-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        Setor
      </a>
      <a href="riwayat.php" class="flex flex-col items-center p-2 hover:text-green-600">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 mb-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v10M16 7v10" />
        </svg>
        Riwayat
      </a>
      <a href="profil.php" class="flex flex-col items-center p-2 text-green-600 font-semibold">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 mb-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A12.073 12.073 0 0112 15c2.762 0 5.304.938 7.121 2.804M15 11a3 3 0 10-6 0 3 3 0 006 0z" />
        </svg>
        Profil
      </a>
    </div>
  </nav>
</body>
</html>
