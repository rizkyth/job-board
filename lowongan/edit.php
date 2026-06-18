<?php
require_once '../config/database.php';
require_once '../config/session.php';

requireRole('hrd');

$id_lowongan = sanitize($_GET['id'] ?? '');
if (empty($id_lowongan)) {
    header("Location: list.php");
    exit();
}

// Validasi kepemilikan
$query = "SELECT lo.* FROM lowongan lo
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

$categories = $conn->query("SELECT id_kategori, nama_kategori FROM kategori_pekerjaan WHERE is_active = TRUE");
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $judul_lowongan      = sanitize($_POST['judul_lowongan']);
    $id_kategori         = sanitize($_POST['id_kategori']);
    $deskripsi_pekerjaan = sanitize($_POST['deskripsi_pekerjaan']);
    $kualifikasi         = sanitize($_POST['kualifikasi']);
    $lokasi              = sanitize($_POST['lokasi']);
    $tipe_pekerjaan      = sanitize($_POST['tipe_pekerjaan']);
    $gaji_min            = sanitize($_POST['gaji_min']);
    $gaji_max            = sanitize($_POST['gaji_max']);
    $kuota_pelamar       = sanitize($_POST['kuota_pelamar']);
    $status              = sanitize($_POST['status']);
    $tanggal_buka        = sanitize($_POST['tanggal_buka']);
    $tanggal_tutup       = sanitize($_POST['tanggal_tutup']);

    if (empty($judul_lowongan) || empty($id_kategori) || empty($deskripsi_pekerjaan) || empty($lokasi) || empty($tipe_pekerjaan) || empty($kuota_pelamar)) {
        $error = "Semua field wajib harus diisi.";
    } else {
        $upd = $conn->prepare("UPDATE lowongan SET judul_lowongan=?, id_kategori=?, deskripsi_pekerjaan=?, kualifikasi=?, lokasi=?, tipe_pekerjaan=?, gaji_min=?, gaji_max=?, kuota_pelamar=?, status=?, tanggal_buka=?, tanggal_tutup=? WHERE id_lowongan=?");
        $upd->bind_param("ssssssssissss", $judul_lowongan, $id_kategori, $deskripsi_pekerjaan, $kualifikasi, $lokasi, $tipe_pekerjaan, $gaji_min, $gaji_max, $kuota_pelamar, $status, $tanggal_buka, $tanggal_tutup, $id_lowongan);

        if ($upd->execute()) {
            header("Location: list.php?success=Lowongan berhasil diperbarui");
            exit();
        } else {
            $error = "Terjadi kesalahan saat menyimpan.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Lowongan - Job Board</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen">
    <?php include '../includes/navbar.php'; ?>

    <div class="max-w-4xl mx-auto px-6 py-10">
        <div class="bg-white rounded-xl shadow p-8">
            <h1 class="text-2xl font-bold text-gray-800 mb-1">Edit Lowongan</h1>
            <p class="text-gray-400 text-sm mb-8">Perbarui informasi lowongan pekerjaan</p>

            <?php if ($error): ?>
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6"><?php echo $error; ?></div>
            <?php endif; ?>

            <?php if (!empty($lowongan['poster_url'] ?? '')): ?>
                <div class="mb-6">
                    <h2 class="text-lg font-semibold text-gray-800 mb-2">Poster Lowongan Saat Ini</h2>
                    <img src="../<?php echo htmlspecialchars($lowongan['poster_url']); ?>" alt="Poster Lowongan" class="w-full h-64 rounded-xl object-cover border">
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-5">
                <div>
                    <label class="block text-gray-700 font-semibold mb-1">Judul Lowongan <span class="text-red-500">*</span></label>
                    <input type="text" name="judul_lowongan" required
                           value="<?php echo htmlspecialchars($lowongan['judul_lowongan']); ?>"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-gray-700 font-semibold mb-1">Kategori <span class="text-red-500">*</span></label>
                        <select name="id_kategori" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">-- Pilih Kategori --</option>
                            <?php while ($cat = $categories->fetch_assoc()): ?>
                                <option value="<?php echo $cat['id_kategori']; ?>" <?php if($lowongan['id_kategori']===$cat['id_kategori']) echo 'selected'; ?>>
                                    <?php echo htmlspecialchars($cat['nama_kategori']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-gray-700 font-semibold mb-1">Tipe Pekerjaan <span class="text-red-500">*</span></label>
                        <select name="tipe_pekerjaan" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <?php foreach(['full_time'=>'Full Time','part_time'=>'Part Time','kontrak'=>'Kontrak','magang'=>'Magang'] as $v=>$l): ?>
                                <option value="<?php echo $v; ?>" <?php if($lowongan['tipe_pekerjaan']===$v) echo 'selected'; ?>><?php echo $l; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-gray-700 font-semibold mb-1">Deskripsi Pekerjaan <span class="text-red-500">*</span></label>
                    <textarea name="deskripsi_pekerjaan" required rows="5"
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"><?php echo htmlspecialchars($lowongan['deskripsi_pekerjaan']); ?></textarea>
                </div>

                <div>
                    <label class="block text-gray-700 font-semibold mb-1">Kualifikasi</label>
                    <textarea name="kualifikasi" rows="4"
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"><?php echo htmlspecialchars($lowongan['kualifikasi'] ?? ''); ?></textarea>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-gray-700 font-semibold mb-1">Lokasi <span class="text-red-500">*</span></label>
                        <input type="text" name="lokasi" required
                               value="<?php echo htmlspecialchars($lowongan['lokasi']); ?>"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-gray-700 font-semibold mb-1">Kuota Pelamar <span class="text-red-500">*</span></label>
                        <input type="number" name="kuota_pelamar" min="1" required
                               value="<?php echo htmlspecialchars($lowongan['kuota_pelamar']); ?>"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-gray-700 font-semibold mb-1">Gaji Min</label>
                        <input type="number" name="gaji_min" min="0"
                               value="<?php echo htmlspecialchars($lowongan['gaji_min'] ?? ''); ?>"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-gray-700 font-semibold mb-1">Gaji Max</label>
                        <input type="number" name="gaji_max" min="0"
                               value="<?php echo htmlspecialchars($lowongan['gaji_max'] ?? ''); ?>"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <label class="block text-gray-700 font-semibold mb-1">Status</label>
                        <select name="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="draft"  <?php if($lowongan['status']==='draft') echo 'selected'; ?>>Draft</option>
                            <option value="buka"   <?php if($lowongan['status']==='buka') echo 'selected'; ?>>Buka</option>
                            <option value="tutup"  <?php if($lowongan['status']==='tutup') echo 'selected'; ?>>Tutup</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-gray-700 font-semibold mb-1">Tanggal Buka</label>
                        <input type="date" name="tanggal_buka"
                               value="<?php echo htmlspecialchars($lowongan['tanggal_buka'] ?? ''); ?>"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-gray-700 font-semibold mb-1">Tanggal Tutup</label>
                        <input type="date" name="tanggal_tutup"
                               value="<?php echo htmlspecialchars($lowongan['tanggal_tutup'] ?? ''); ?>"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>

                <div class="flex gap-4 pt-4">
                    <a href="list.php" class="flex-1 text-center bg-gray-200 hover:bg-gray-300 text-gray-700 font-semibold py-3 rounded-lg transition">Batal</a>
                    <button type="submit" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 rounded-lg transition">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
