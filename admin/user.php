<?php
// admin/user.php
session_start();
include '../koneksi.php'; // Sesuaikan path koneksi.php jika struktur folder berbeda

// Cek apakah admin sudah login
if (!isset($_SESSION['admin']) || !isset($_SESSION['admin']['id'])) {
    header("Location: ../login_admin.php"); // Ganti dengan halaman login admin Anda
    exit;
}

// Query untuk mendapatkan semua penyetoran (header transaksi) dari tabel 'penyetoran'
// Termasuk informasi user dan statusnya.
$queryPenyetoran = "
    SELECT
        p.id AS penyetoran_id,
        p.tanggal,
        p.status,
        u.id AS user_id,
        u.nama AS nama_user
    FROM
        penyetoran p
    JOIN
        users u ON p.user_id = u.id
    ORDER BY
        p.tanggal DESC, p.id DESC;
";

$resultPenyetoran = mysqli_query($conn, $queryPenyetoran);

// Struktur data untuk menyimpan semua transaksi penyetoran
$all_transactions = [];
if ($resultPenyetoran) {
    while ($row = mysqli_fetch_assoc($resultPenyetoran)) {
        $penyetoran_id = $row['penyetoran_id'];
        $all_transactions[$penyetoran_id] = [
            'id' => $row['penyetoran_id'],
            'tanggal' => $row['tanggal'],
            'status' => $row['status'],
            'user_id' => $row['user_id'],
            'nama_user' => $row['nama_user'],
            'detail_sampah' => [], // Untuk detail jenis sampah di dalam transaksi ini
            'total_poin_transaksi' => 0 // Akan diisi dari subtotal_poin detail
        ];
    }
} else {
    echo "Error fetching penyetoran: " . mysqli_error($conn);
}

// Sekarang, ambil detail sampah untuk setiap transaksi
$queryDetailSampah = "
    SELECT
        dp.penyetoran_id,
        js.nama AS nama_jenis_sampah,
        dp.jumlah,
        dp.subtotal_poin
    FROM
        detail_penyetoran dp
    JOIN
        jenis_sampah js ON dp.jenis_id = js.id
    ORDER BY
        dp.penyetoran_id ASC;
";

$resultDetailSampah = mysqli_query($conn, $queryDetailSampah);

if ($resultDetailSampah) {
    while ($row = mysqli_fetch_assoc($resultDetailSampah)) {
        $penyetoran_id = $row['penyetoran_id'];
        if (isset($all_transactions[$penyetoran_id])) {
            $all_transactions[$penyetoran_id]['detail_sampah'][] = [
                'nama_jenis_sampah' => $row['nama_jenis_sampah'],
                'jumlah' => $row['jumlah'],
                'subtotal_poin' => (float)$row['subtotal_poin']
            ];
            // Akumulasikan total poin untuk transaksi ini
            $all_transactions[$penyetoran_id]['total_poin_transaksi'] += (float)$row['subtotal_poin'];
        }
    }
} else {
    echo "Error fetching detail sampah: " . mysqli_error($conn);
}


// --- Logika POST untuk Accept/Reject ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $setoran_id_to_update = $_POST['penyetoran_id'];
    $action = $_POST['action']; // 'accept' atau 'reject'
    $new_status = ($action === 'accept') ? 'approved' : 'rejected';

    // Mulai transaksi untuk memastikan konsistensi data
    mysqli_autocommit($conn, FALSE);
    $success = true;

    // Ambil status lama dan user_id sebelum update
    $get_prev_data_query = "SELECT status, user_id FROM penyetoran WHERE id = '$setoran_id_to_update'";
    $prev_data_result = mysqli_query($conn, $get_prev_data_query);
    $prev_data_row = mysqli_fetch_assoc($prev_data_result);
    $previous_status = $prev_data_row['status'];
    $target_user_id = $prev_data_row['user_id'];

    // Ambil total poin dari transaksi ini (dari detail_penyetoran)
    $get_total_poin_setoran_query = "SELECT SUM(dp.subtotal_poin) AS total_poin FROM detail_penyetoran dp WHERE dp.penyetoran_id = '$setoran_id_to_update'";
    $total_poin_setoran_result = mysqli_query($conn, $get_total_poin_setoran_query);
    $total_poin_setoran_row = mysqli_fetch_assoc($total_poin_setoran_result);
    $poin_amount = $total_poin_setoran_row['total_poin'] ?? 0;


    // 1. Update status penyetoran
    $update_status_query = "UPDATE penyetoran SET status = '$new_status' WHERE id = '$setoran_id_to_update'";
    if (!mysqli_query($conn, $update_status_query)) {
        $success = false;
    }

    // 2. Sesuaikan total_poin user berdasarkan perubahan status
    if ($success) {
        if ($new_status === 'approved' && $previous_status !== 'approved') {
            // Jika status baru approved dan sebelumnya BUKAN approved, tambahkan poin
            $update_user_poin_query = "UPDATE users SET total_poin = total_poin + '$poin_amount' WHERE id = '$target_user_id'";
            if (!mysqli_query($conn, $update_user_poin_query)) {
                $success = false;
            }
        } elseif ($new_status === 'rejected' && $previous_status === 'approved') {
            // Jika status baru rejected dan sebelumnya APPROVED, kurangi poin
            $update_user_poin_query = "UPDATE users SET total_poin = total_poin - '$poin_amount' WHERE id = '$target_user_id'";
            if (!mysqli_query($conn, $update_user_poin_query)) {
                $success = false;
            }
        }
        // Jika status tetap sama (approved ke approved, rejected ke rejected, pending ke pending)
        // atau pending ke rejected (poin tidak berubah) maka tidak ada perubahan poin user.
    }


    if ($success) {
        mysqli_commit($conn);
        $_SESSION['success_message'] = "Status setoran berhasil diupdate menjadi " . $new_status . "!";
    } else {
        mysqli_rollback($conn);
        $_SESSION['error_message'] = "Gagal mengupdate status setoran: " . mysqli_error($conn); // Debugging
    }

    // Redirect untuk menghindari POST ganda dan refresh data
    header("Location: user.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Manajemen Penyetoran - Admin Skotrash</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .card-status-approved { border-left: 4px solid #10B981; /* green-500 */ }
        .card-status-rejected { border-left: 4px solid #EF4444; /* red-500 */ }
        .card-status-pending { border-left: 4px solid #F59E0B; /* yellow-500 */ }
    </style>
</head>
<body class="bg-gray-100 min-h-screen">

    <nav class="bg-white shadow px-6 py-4 flex justify-between items-center">
        <h1 class="text-xl font-bold text-green-700">Skotrash Admin</h1>
        <ul class="flex space-x-6 text-sm font-medium text-gray-700">
            <li><a href="index.php" class="hover:text-green-600">Home</a></li>
            <li><a href="user.php" class="hover:text-green-600 font-semibold text-green-600">User</a></li>
            <li><a href="setor.php" class="hover:text-green-600">Setor</a></li> </ul>
    </nav>

    <div class="container mx-auto p-6">
        <h2 class="text-3xl font-bold text-gray-800 mb-6">Halo Admin!</h2>

        <?php
        // Tampilkan pesan sukses/error dari sesi
        if (isset($_SESSION['success_message'])) {
            echo '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">' . htmlspecialchars($_SESSION['success_message']) . '</div>';
            unset($_SESSION['success_message']);
        }
        if (isset($_SESSION['error_message'])) {
            echo '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">' . htmlspecialchars($_SESSION['error_message']) . '</div>';
            unset($_SESSION['error_message']);
        }
        ?>

        <h3 class="text-xl font-bold text-gray-800 mb-4">Daftar Penyetoran User</h3>

        <?php if (!empty($all_transactions)): ?>
            <div class="space-y-6">
                <?php foreach ($all_transactions as $setoran): ?>
                    <?php
                        $card_class = '';
                        if ($setoran['status'] === 'approved') {
                            $card_class = 'card-status-approved';
                        } elseif ($setoran['status'] === 'rejected') {
                            $card_class = 'card-status-rejected';
                        } else {
                            $card_class = 'card-status-pending';
                        }
                    ?>
                    <div class="bg-white rounded-lg shadow p-6 <?= $card_class ?>">
                        <div class="flex justify-between items-center mb-2">
                            <h3 class="text-lg font-semibold text-gray-800">
                                Penyetoran oleh: <span class="text-blue-600"><?= htmlspecialchars($setoran['nama_user']) ?></span>
                            </h3>
                            <span class="text-sm text-gray-500">
                                Tanggal: <?= htmlspecialchars(date('d F Y, H:i', strtotime($setoran['tanggal']))) ?>
                            </span>
                        </div>
                        <div class="text-sm text-gray-600 mb-4">
                            <p class="font-bold">Total Poin Transaksi Ini: <span class="text-green-600">Rp <?= number_format($setoran['total_poin_transaksi'] * 100, 0, ',', '.') ?></span></p>
                            <p class="text-xs text-gray-500">Status: <span class="font-semibold <?= ($setoran['status'] === 'approved' ? 'text-green-700' : ($setoran['status'] === 'rejected' ? 'text-red-700' : 'text-yellow-700')) ?>"><?= htmlspecialchars(ucfirst($setoran['status'])) ?></span></p>
                        </div>

                        <div class="border-t border-gray-200 pt-4 mb-4">
                            <p class="text-md font-medium text-gray-700 mb-2">Detail Sampah:</p>
                            <?php if (!empty($setoran['detail_sampah'])): ?>
                                <ul class="list-disc list-inside space-y-1 text-gray-700">
                                    <?php foreach ($setoran['detail_sampah'] as $detail): ?>
                                        <li>
                                            <span class="font-semibold"><?= htmlspecialchars($detail['nama_jenis_sampah']) ?></span>
                                            <span class="text-gray-500"> (<?= number_format($detail['jumlah'], 2, ',', '.') ?> Kg/Satuan)</span>
                                            <span class="float-right text-green-600 font-medium">Rp <?= number_format($detail['subtotal_poin'] * 100, 0, ',', '.') ?></span>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <p class="text-gray-500 text-sm">Tidak ada detail sampah untuk transaksi ini.</p>
                            <?php endif; ?>
                        </div>

                        <div class="flex justify-end gap-2">
                            <?php if ($setoran['status'] === 'pending'): ?>
                                <form method="POST" action="user.php" class="inline-block">
                                    <input type="hidden" name="penyetoran_id" value="<?= $setoran['id'] ?>">
                                    <input type="hidden" name="action" value="accept">
                                    <button type="submit" class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600 flex items-center">
                                        <i class="fas fa-check mr-2"></i> Terima
                                    </button>
                                </form>
                                <form method="POST" action="user.php" class="inline-block">
                                    <input type="hidden" name="penyetoran_id" value="<?= $setoran['id'] ?>">
                                    <input type="hidden" name="action" value="reject">
                                    <button type="submit" class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600 flex items-center">
                                        <i class="fas fa-times mr-2"></i> Tolak
                                    </button>
                                </form>
                            <?php elseif ($setoran['status'] === 'approved'): ?>
                                <button type="button" class="bg-green-600 text-white px-4 py-2 rounded opacity-80 cursor-not-allowed flex items-center">
                                    <i class="fas fa-check-double mr-2"></i> Disetujui
                                </button>
                                <form method="POST" action="user.php" class="inline-block">
                                    <input type="hidden" name="penyetoran_id" value="<?= $setoran['id'] ?>">
                                    <input type="hidden" name="action" value="reject">
                                    <button type="submit" class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600 flex items-center">
                                        <i class="fas fa-times mr-2"></i> Batalkan & Tolak
                                    </button>
                                </form>
                            <?php elseif ($setoran['status'] === 'rejected'): ?>
                                <button type="button" class="bg-red-600 text-white px-4 py-2 rounded opacity-80 cursor-not-allowed flex items-center">
                                    <i class="fas fa-ban mr-2"></i> Ditolak
                                </button>
                                <form method="POST" action="user.php" class="inline-block">
                                    <input type="hidden" name="penyetoran_id" value="<?= $setoran['id'] ?>">
                                    <input type="hidden" name="action" value="accept">
                                    <button type="submit" class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600 flex items-center">
                                        <i class="fas fa-check mr-2"></i> Koreksi & Terima
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="bg-white rounded-lg shadow p-6 text-center text-gray-600">
                Belum ada transaksi penyetoran sampah untuk ditampilkan.
            </div>
        <?php endif; ?>
    </div>
</body>
</html>