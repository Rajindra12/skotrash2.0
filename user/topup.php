<!-- topup.php -->
<?php
session_start();
include '../koneksi.php';

if (!isset($_SESSION['user'])) {
  header("Location: ../login_user.php");
  exit;
}

$user = $_SESSION['user'];
$user_id = $user['id'];
$tanggal = date('Y-m-d');

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['jenis_id'])) {
  $jenis_id = (int)$_POST['jenis_id'];
  $quantity = (float)$_POST['quantity'];

  $jenis = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM jenis_sampah WHERE id = $jenis_id"));
  $poin_per_satuan = $jenis['poin_per_satuan'];
  $total_poin = $poin_per_satuan * $quantity;

  mysqli_query($conn, "INSERT INTO penyetoran (user_id, tanggal, status, total_poin) VALUES ($user_id, '$tanggal', 'pending', $total_poin)");
  $penyetoran_id = mysqli_insert_id($conn);

  mysqli_query($conn, "INSERT INTO detail_penyetoran (penyetoran_id, jenis_id, jumlah, subtotal_poin) VALUES ($penyetoran_id, $jenis_id, $quantity, $total_poin)");

  mysqli_query($conn, "UPDATE users SET total_poin = total_poin + $total_poin WHERE id = $user_id");

  $success = "Setor berhasil untuk jenis '{$jenis['nama']}' sebanyak $quantity (Total Poin: $total_poin)";
}

$data_jenis = mysqli_query($conn, "SELECT * FROM jenis_sampah");
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Setor Sampah - Skotrash</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    body {
      font-family: 'Segoe UI', sans-serif;
    }
  </style>
  <script>
    function changeQuantity(id, step) {
      const input = document.getElementById('qty-' + id);
      let val = parseFloat(input.value);
      if (isNaN(val)) val = 0;
      val += step;
      if (val < 0) val = 0;
      input.value = val;
    }
  </script>
</head>
<body class="bg-green-50 min-h-screen pb-20">
  <div class="container mx-auto px-4 py-6">
    <h1 class="text-xl font-bold text-green-700 mb-4">Setor Sampah</h1>

    <?php if (isset($success)): ?>
      <div class="bg-green-100 text-green-700 px-4 py-3 mb-4 rounded-lg">
        <?= $success ?>
      </div>
    <?php endif; ?>

    <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
      <?php while ($row = mysqli_fetch_assoc($data_jenis)): ?>
        <form method="POST" class="bg-white shadow rounded-xl p-5 border border-green-200">
          <h3 class="text-lg font-semibold text-gray-800 mb-1"><?= htmlspecialchars($row['nama']) ?></h3>
          <p class="text-sm text-gray-500 mb-4">Poin per <?= $row['satuan'] ?>: <strong><?= $row['poin_per_satuan'] ?></strong></p>

          <div class="flex items-center mb-4">
            <button type="button" onclick="changeQuantity(<?= $row['id'] ?>, -1)" class="px-3 py-1 bg-red-200 text-red-800 rounded-l">-</button>
            <input type="number" name="quantity" id="qty-<?= $row['id'] ?>" value="0" step="0.1" min="0" class="w-full text-center border-t border-b px-3 py-1">
            <button type="button" onclick="changeQuantity(<?= $row['id'] ?>, 1)" class="px-3 py-1 bg-green-200 text-green-800 rounded-r">+</button>
          </div>

          <input type="hidden" name="jenis_id" value="<?= $row['id'] ?>">
          <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white py-2 px-4 rounded-lg font-semibold">
            Setor
          </button>
        </form>
      <?php endwhile; ?>
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
      <a href="topup.php" class="flex flex-col items-center p-2 text-green-600 font-semibold">
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
      <a href="profil.php" class="flex flex-col items-center p-2 hover:text-green-600">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 mb-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A12.073 12.073 0 0112 15c2.762 0 5.304.938 7.121 2.804M15 11a3 3 0 10-6 0 3 3 0 006 0z" />
        </svg>
        Profil
      </a>
    </div>
  </nav>
</body>
</html>