<?php
// login_user.php (pakai tabel users, semua NIS termasuk 6 NIS sekarang user biasa)
session_start();
include '../koneksi.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  $nis = mysqli_real_escape_string($conn, $_POST['nis']);
  $nama = mysqli_real_escape_string($conn, $_POST['nama']);

  $query = mysqli_query($conn, "SELECT * FROM users WHERE nis = '$nis' AND nama = '$nama'");
  $user = mysqli_fetch_assoc($query);

  if ($user) {
    $_SESSION['user'] = $user;
    header("Location: index.php");
    exit;
  } else {
    $error = "Data tidak ditemukan. Cek kembali NIS dan nama Anda.";
  }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Login User - Skotrash</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-green-50 flex items-center justify-center min-h-screen">
  <div class="bg-white shadow-xl rounded-xl p-10 max-w-md w-full">
    <h2 class="text-2xl font-bold text-center text-green-700 mb-6">Login User</h2>

    <?php if (isset($error)): ?>
      <div class="bg-red-100 text-red-700 p-3 mb-4 rounded"><?= $error ?></div>
    <?php endif; ?>

    <form method="POST">
      <label class="block text-sm font-medium mb-1">Nama</label>
      <input type="text" name="nama" required class="w-full border px-4 py-2 rounded mb-4">

      <label class="block text-sm font-medium mb-1">NIS</label>
      <input type="text" name="nis" required class="w-full border px-4 py-2 rounded mb-4">

      <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white font-semibold py-2 rounded">
        Masuk
      </button>
    </form>
  </div>
</body>
</html>
