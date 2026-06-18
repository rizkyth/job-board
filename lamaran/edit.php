<?php
require_once '../config/database.php';
require_once '../config/session.php';

requireRole('kandidat').

$id_lamaran = sanitize($_GET['id'] ?? '');
if (empty($id_lamaran)) {
    header("Location: list.php");
    exit();
}

// Validasi kepemilikan
$query = "SELECT la.*, lo.judul_lowongan, p.nama_perusahaan, lo.lokasi
          FROM lamaran la
          JOIN lowongan lo ON la.id_lowongan = lo.id_lowongan
          JOIN perusahaan p ON lo.id_perusahaan = p.id_perusahaan
          JOIN kandidat k ON la.id_kandidat = k.id_kandidat
          WHERE la.id_lamaran = ? AND k.id_user = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ss", $id_lamaran, $_SESSION['id_user']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: list.php?error=Lamaran tidak ditemukan");
    exit();
}
$lamaran = $result->fetch_assoc();

if ($lamaran['status_lamaran'] !== 'menunggu') {
    header("Location: detail.php?id=$id_lamaran&error=Lamaran tidak dapat diedit karena sudah diproses");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $surat_lamaran = sanitize($_POST['surat_lamaran']);
    $cv_url        = sanitize($_POST['cv_url']);

    if (empty($surat_lamaran)) {
        $error = "Surat lamaran tidak boleh kosong.";
    } else {
        $upd = $conn->prepare("UPDATE lamaran SET surat_lamaran = ?, cv_url = ? WHERE id_lamaran = ?");
        $upd->bind_param("sss", $surat_lamaran, $cv_url, $id_lamaran);

        if ($upd->execute()) {
            header("Location: detail.php?id=$id_lamaran&success=Lamaran berhasil diperbarui");
            exit();
        } else {
            $error = "Terjadi kesalahan, silakan coba lagi.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Lamaran - Job Board</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen">
    <?php include '../includes/navbar.php'; ?>

    <div class="max-w-3xl mx-auto px-6 py-10">
        <div class="bg-blue-50 border border-blue-200 rounded-xl p-5 mb-8">
            <p class="text-xs text-blue-500 uppercase font-semibold mb-1">Edit Lamaran untuk</p>
            <h2 class="text-xl font-bold text-blue-900"><?php echo htmlspecialchars($lamaran['judul_lowongan']); ?></h2>
            <p class="text-blue-700"><?php echo htmlspecialchars($lamaran['nama_perusahaan']); ?> &mdash; <?php echo htmlspecialchars($lamaran['lokasi']); ?></p>
        </div>

        <div class="bg-white rounded-xl shadow p-8">
            <h1 class="text-2xl font-bold text-gray-800 mb-2">Edit Lamaran</h1>
            <p class="text-gray-400 text-sm mb-6">Hanya bisa diedit selama status masih <strong>Menunggu</strong>.</p>

            <?php if ($error): ?>
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST" class="space-y-6">
                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Link CV / Portfolio</label>
                    <input type="url" name="cv_url"
                           value="<?php echo htmlspecialchars($lamaran['cv_url'] ?? ''); ?>"
                           placeholder="https://drive.google.com/your-cv"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Surat Lamaran <span class="text-red-500">*</span></label>
                    <textarea name="surat_lamaran" required rows="10"
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"><?php echo htmlspecialchars($lamaran['surat_lamaran']); ?></textarea>
                </div>

                <div class="flex gap-4 pt-2">
                    <a href="detail.php?id=<?php echo $id_lamaran; ?>"
                       class="flex-1 text-center bg-gray-200 hover:bg-gray-300 text-gray-700 font-semibold py-3 rounded-lg transition">
                        Batal
                    </a>
                    <button type="submit"
                            class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 rounded-lg transition">
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
