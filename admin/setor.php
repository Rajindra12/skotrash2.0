<?php
// user/setor.php
session_start();
include '../koneksi.php';

// Cek apakah user sudah login
if (!isset($_SESSION['user']) || !isset($_SESSION['user']['id'])) {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user']['id'];

// --- Logika untuk memproses "Setor" ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'confirm_setor') {
    $penyetoran_id_to_confirm = $_POST['penyetoran_id'];

    // Update status penyetoran menjadi 'completed' atau 'submitted'
    // Status 'approved' berarti admin sudah setuju, 'completed' berarti user sudah 'menyetorkan' fisik
    $update_query = "UPDATE penyetoran SET status = 'completed' WHERE id = '$penyetoran_id_to_confirm' AND user_id = '$user_id' AND status = 'approved'";
    
    if (mysqli_query($conn, $update_query)) {
        $_SESSION['success_message'] = "Penyetoran berhasil dikonfirmasi dan poin Anda telah ditambahkan!";
    } else {
        $_SESSION['error_message'] = "Gagal mengkonfirmasi penyetoran: " . mysqli_error($conn);
    }

    // Redirect untuk menghindari POST ganda
    header("Location: setor.php");
    exit;
}

// --- Query untuk mendapatkan penyetoran user yang sudah 'approved' (menunggu dikonfirmasi user) ---
$queryApprovedSetoran = "
    SELECT
        p.id AS penyetoran_id,
        js.nama AS nama_jenis_sampah,
        dp.jumlah AS jumlah_sampah
    FROM
        penyetoran p
    JOIN
        detail_penyetoran dp ON p.id = dp.penyetoran_id
    JOIN
        jenis_sampah js ON dp.jenis_id = js.id
    WHERE
        p.user_id = '$user_id' AND p.status = 'approved'
    ORDER BY
        p.tanggal DESC, p.id DESC;
";

$resultApprovedSetoran = mysqli_query($conn, $queryApprovedSetoran);

// Mengelompokkan detail sampah per penyetoran_id
$approved_transactions_for_user = [];
if ($resultApprovedSetoran) {
    while ($row = mysqli_fetch_assoc($resultApprovedSetoran)) {
        $penyetoran_id = $row['penyetoran_id'];
        if (!isset($approved_transactions_for_user[$penyetoran_id])) {
            $approved_transactions_for_user[$penyetoran_id] = [
                'id' => $row['penyetoran_id'],
                'detail_sampah' => []
            ];
        }
        $approved_transactions_for_user[$penyetoran_id]['detail_sampah'][] = [
            'nama_jenis_sampah' => $row['nama_jenis_sampah'],
            'jumlah_sampah' => $row['jumlah_sampah']
        ];
    }
} else {
    $_SESSION['error_message'] = "Error mengambil data penyetoran: " . mysqli_error($conn);
}

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Siap Setor - Skotrash</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="bg-green-50 min-h-screen">

    <nav class="bg-white shadow px-6 py-4 flex justify-between items-center">
        <h1 class="text-xl font-bold text-green-700">Skotrash</h1>
        <ul class="flex space-x-6 text-sm font-medium text-gray-700">
            <li><a href="index.php" class="hover:text-green-600">Home</a></li>
            <li><a href="user.php" class="hover:text-green-600">User</a></li>
            <li><a href="setor.php" class="hover:text-green-600">Setor</a></li>
        </ul>
    </nav>

    <div class="container mx-auto p-6">
        <h2 class="text-3xl font-bold text-gray-800 mb-6">Penyetoran Siap Diserahkan</h2>

        <?php
        // Tampilkan pesan sukses/error
        if (isset($_SESSION['success_message'])) {
            echo '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">' . htmlspecialchars($_SESSION['success_message']) . '</div>';
            unset($_SESSION['success_message']);
        }
        if (isset($_SESSION['error_message'])) {
            echo '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">' . htmlspecialchars($_SESSION['error_message']) . '</div>';
            unset($_SESSION['error_message']);
        }
        ?>

        <?php if (!empty($approved_transactions_for_user)): ?>
            <div class="space-y-6">
                <p class="text-gray-700 mb-4">Berikut adalah daftar penyetoran Anda yang telah disetujui oleh Admin. Silakan serahkan sampah Anda sesuai dengan daftar di bawah ini.</p>
                <?php foreach ($approved_transactions_for_user as $transaction): ?>
                    <div class="bg-white rounded-lg shadow p-6 border-l-4 border-green-500">
                        <h3 class="text-lg font-semibold text-gray-800 mb-3">Detail Penyetoran #<?= htmlspecialchars($transaction['id']) ?></h3>
                        
                        <ul class="list-disc list-inside space-y-1 text-gray-700 mb-4">
                            <?php foreach ($transaction['detail_sampah'] as $detail): ?>
                                <li>
                                    <span class="font-semibold"><?= htmlspecialchars($detail['nama_jenis_sampah']) ?></span>: 
                                    <?= number_format($detail['jumlah_sampah'], 2, ',', '.') ?> Kg/Satuan
                                </li>
                            <?php endforeach; ?>
                        </ul>

                        <form method="POST" action="setor.php" class="text-right">
                            <input type="hidden" name="penyetoran_id" value="<?= $transaction['id'] ?>">
                            <input type="hidden" name="action" value="confirm_setor">
                            <button type="submit" class="bg-blue-600 text-white px-5 py-2 rounded-full hover:bg-blue-700 transition duration-300 ease-in-out">
                                <i class="fas fa-check-circle mr-2"></i> Konfirmasi Penyerahan
                            </button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="bg-white rounded-lg shadow p-6 text-center text-gray-600">
                Tidak ada penyetoran yang menunggu untuk diserahkan.
            </div>
        <?php endif; ?>
    </div>
</body>
</html>