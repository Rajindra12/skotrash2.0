<!-- riwayat.php -->
<?php
session_start();
include '../koneksi.php'; // Sesuaikan path jika lokasi file koneksi.php berbeda

// Pastikan user sudah login dan ID user tersedia di sesi
if (!isset($_SESSION['user']) || !isset($_SESSION['user']['id'])) {
    header("Location: login.php");
    exit;
}

$current_user_id = $_SESSION['user']['id']; // Ambil ID user dari sesi

// Query untuk mengambil semua riwayat penyetoran untuk user yang sedang login.
// Kita akan melakukan JOIN antara tabel penyetoran, detail_penyetoran, dan jenis_sampah
// untuk mendapatkan semua informasi yang dibutuhkan.
$query = "
    SELECT
        p.id AS penyetoran_id,
        p.tanggal,
        p.status,
        js.nama AS nama_jenis_sampah,
        dp.jumlah,
        dp.subtotal_poin
    FROM
        penyetoran p
    JOIN
        detail_penyetoran dp ON p.id = dp.penyetoran_id
    JOIN
        jenis_sampah js ON dp.jenis_id = js.id -- KOREKSI DI SINI: dari dp.jenis_sampah_id menjadi dp.jenis_id
    WHERE
        p.user_id = '$current_user_id'
    ORDER BY
        p.tanggal DESC, p.id DESC; -- Urutkan dari yang terbaru
";

$result = mysqli_query($conn, $query);

// Kita akan mengelompokkan data berdasarkan ID penyetoran (p.id)
// karena satu penyetoran bisa memiliki banyak jenis sampah (di detail_penyetoran).
$grouped_riwayat = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $penyetoran_id = $row['penyetoran_id'];

        // Jika ID penyetoran ini belum ada di array kelompok, inisialisasi
        if (!isset($grouped_riwayat[$penyetoran_id])) {
            $grouped_riwayat[$penyetoran_id] = [
                'tanggal' => $row['tanggal'],
                'status' => $row['status'],
                'total_poin_penyetoran' => 0, // Akan dihitung dari subtotal_poin
                'detail_sampah' => []
            ];
        }

        // Tambahkan detail sampah ke dalam array detail_sampah
        $grouped_riwayat[$penyetoran_id]['detail_sampah'][] = [
            'nama_jenis_sampah' => $row['nama_jenis_sampah'],
            'jumlah' => $row['jumlah'],
            'subtotal_poin' => $row['subtotal_poin']
        ];

        // Tambahkan subtotal_poin ke total_poin_penyetoran untuk penyetoran ini
        $grouped_riwayat[$penyetoran_id]['total_poin_penyetoran'] += $row['subtotal_poin'];
    }
} else {
    echo "Error: " . mysqli_error($conn);
}

// Bagian HTML dan JavaScript lainnya tetap sama
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Riwayat Penyetoran - Skotrash</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Optional: Add some custom CSS for better readability or spacing */
        .history-card {
            border-left: 4px solid #10b981; /* Tailwind green-500 */
        }
    </style>
</head>
<body class="bg-green-50 min-h-screen">

    <div class="container mx-auto p-6">
        <h2 class="text-3xl font-bold text-gray-800 mb-6">Riwayat Penyetoran Sampah</h2>

        <?php if (!empty($grouped_riwayat)): ?>
            <div class="space-y-6">
                <?php foreach ($grouped_riwayat as $penyetoran_id => $data_penyetoran): ?>
                    <div class="bg-white rounded-lg shadow p-6 history-card">
                        <div class="flex justify-between items-center mb-2">
                            <h3 class="text-lg font-semibold text-gray-800">
                                Tanggal: <?= htmlspecialchars(date('d F Y, H:i', strtotime($data_penyetoran['tanggal']))) ?>
                            </h3>
                            <span class="px-3 py-1 rounded-full text-xs font-medium
                                <?= $data_penyetoran['status'] == 'selesai' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' ?>">
                                <?= htmlspecialchars(ucfirst($data_penyetoran['status'])) ?>
                            </span>
                        </div>

                        <div class="text-sm text-gray-600 mb-4">
                            <p class="font-bold">Total Poin Penyetoran: <span class="text-green-600">Rp <?= number_format($data_penyetoran['total_poin_penyetoran'] * 100, 0, ',', '.') ?></span></p>
                        </div>

                        <div class="border-t border-gray-200 pt-4">
                            <p class="text-md font-medium text-gray-700 mb-2">Detail Sampah:</p>
                            <ul class="list-disc list-inside space-y-1 text-gray-700">
                                <?php foreach ($data_penyetoran['detail_sampah'] as $detail): ?>
                                    <li>
                                        <span class="font-semibold"><?= htmlspecialchars($detail['nama_jenis_sampah']) ?></span>
                                        <span class="text-gray-500"> (<?= number_format($detail['jumlah'], 2, ',', '.') ?> Kg/Satuan)</span>
                                        <span class="float-right text-green-600 font-medium">Rp <?= number_format($detail['subtotal_poin'] * 100, 0, ',', '.') ?></span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="bg-white rounded-lg shadow p-6 text-center">
                <p class="text-gray-600 text-lg">Anda belum memiliki riwayat penyetoran sampah.</p>
                <p class="text-gray-500 mt-2">Ayo, mulai setor sampahmu sekarang!</p>
            </div>
        <?php endif; ?>
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
      <a href="riwayat.php" class="flex flex-col items-center p-2 text-green-600 font-semibold">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 mb-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v10M16 7v10" />
        </svg>
        Riwayat
      </a>
      <a href="profile.php" class="flex flex-col items-center p-2 hover:text-green-600">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 mb-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A12.073 12.073 0 0112 15c2.762 0 5.304.938 7.121 2.804M15 11a3 3 0 10-6 0 3 3 0 006 0z" />
        </svg>
        Profil
      </a>
    </div>
  </nav>
</body>
</html>