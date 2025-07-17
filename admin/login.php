<?php
// login_admin.php (baru: pakai tabel admin, bukan NIS)
session_start();
include '../koneksi.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  $username = mysqli_real_escape_string($conn, $_POST['username']);
  $password = mysqli_real_escape_string($conn, $_POST['password']);

  $query = mysqli_query($conn, "SELECT * FROM admin WHERE username = '$username'");
  $admin = mysqli_fetch_assoc($query);

  if ($admin && $password === $admin['password']) { // NOTE: tambahkan hash check jika sudah di-hash
    $_SESSION['admin'] = $admin;
    header("Location: index.php");
    exit;
  } else {
    $error = "Username atau password salah.";
  }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Login Admin - Skotrash</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-green-50">
  <nav class="bg-white shadow px-4 py-3">
    <div class="container mx-auto">
      <h1 class="text-xl font-bold text-green-700">Skotrash</h1>
    </div>
  </nav>

  <div class="flex flex-col items-center justify-center min-h-screen px-4">
    <div class="bg-white shadow-xl rounded-xl p-10 max-w-md w-full">
      <h2 class="text-2xl font-bold text-center text-green-700 mb-2">Selamat Datang</h2>
      <p class="text-center text-gray-600 mb-6">Silakan login untuk mengakses halaman admin Skotrash</p>

      <?php if (isset($error)): ?>
        <div class="bg-red-100 text-red-700 p-3 mb-4 rounded"><?= $error ?></div>
      <?php endif; ?>

      <form method="POST">
        <label class="block text-sm font-medium mb-1">Username</label>
        <input type="text" name="username" required class="w-full border px-4 py-2 rounded mb-4">

        <label class="block text-sm font-medium mb-1">Password</label>
        <input type="password" name="password" required class="w-full border px-4 py-2 rounded mb-4">

        <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white font-semibold py-2 rounded">
          Masuk
        </button>
      </form>
    </div>
  </div>
</body>
</html>
