<?php
require_once '../config/database.php';
require_once '../config/session.php';

requireRole('hrd');

$id_lowongan = sanitize($_GET['id'] ?? '');
if (empty($id_lowongan)) {
    header("Location: list.php");
    exit();
}

// Validasi kepemilikan & tidak ada lamaran aktif
$query = "SELECT lo.judul_lowongan, lo.jumlah_pelamar FROM lowongan lo
          JOIN hrd h ON h.id_perusahaan = lo.id_perusahaan
          WHERE lo.id_lowongan = ? AND h.id_user = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ss", $id_lowongan, $_SESSION['id_user']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: list.php?error=Lowongan tidak ditemukan");
    exit();
}
$lowongan = $result->fetch_assoc();

// Konfirmasi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm'])) {
    $del = $conn->prepare("DELETE FROM lowongan WHERE id_lowongan = ?");
    $del->bind_param("s", $id_lowongan);
    if ($del->execute()) {
        header("Location: list.php?success=Lowongan berhasil dihapus");
    } else {
        header("Location: list.php?error=Gagal menghapus lowongan");
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hapus Lowongan - Job Board</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen">
    <?php include '../includes/navbar.php'; ?>
    <div class="max-w-lg mx-auto px-6 py-20">
        <div class="bg-white rounded-xl shadow p-8 text-center">
            <div class="text-5xl mb-4">⚠️</div>
            <h1 class="text-2xl font-bold text-gray-800 mb-2">Hapus Lowongan?</h1>
            <p class="text-gray-600 mb-2">Anda akan menghapus lowongan:</p>
            <p class="font-semibold text-gray-800 text-lg mb-4">"<?php echo htmlspecialchars($lowongan['judul_lowongan']); ?>"</p>

            <?php if ($lowongan['jumlah_pelamar'] > 0): ?>
                <div class="bg-yellow-50 border border-yellow-200 text-yellow-700 px-4 py-3 rounded-lg mb-6 text-sm">
                    ⚠️ Terdapat <strong><?php echo $lowongan['jumlah_pelamar']; ?> lamaran</strong> yang akan ikut terhapus.
                </div>
            <?php endif; ?>

            <p class="text-red-600 text-sm mb-8">Tindakan ini tidak dapat dibatalkan.</p>

            <form method="POST" class="flex gap-4">
                <a href="list.php" class="flex-1 py-3 bg-gray-200 hover:bg-gray-300 text-gray-700 font-semibold rounded-lg transition">
                    Batal
                </a>
                <button type="submit" name="confirm" value="1"
                        class="flex-1 py-3 bg-red-600 hover:bg-red-700 text-white font-bold rounded-lg transition">
                    Ya, Hapus
                </button>
            </form>
        </div>
    </div>
</body>
</html>
