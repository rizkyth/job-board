<?php
require_once '../config/database.php';
require_once '../config/session.php';

requireRole('kandidat');

$id_perusahaan = sanitize($_GET['id'] ?? '');
if (empty($id_perusahaan)) {
    header("Location: list.php");
    exit();
}

$stmt = $conn->prepare("SELECT * FROM perusahaan WHERE id_perusahaan = ?");
$stmt->bind_param("s", $id_perusahaan);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: list.php");
    exit();
}
$perusahaan = $result->fetch_assoc();

// Ambil lowongan aktif dari perusahaan ini
$stmtLow = $conn->prepare("SELECT lo.*, k.nama_kategori FROM lowongan lo
                            JOIN kategori_pekerjaan k ON lo.id_kategori = k.id_kategori
                            WHERE lo.id_perusahaan = ? AND lo.status = 'buka' AND lo.tanggal_tutup >= CURDATE()
                            ORDER BY lo.created_at DESC");
$stmtLow->bind_param("s", $id_perusahaan);
$stmtLow->execute();
$lowonganAktif = $stmtLow->get_result();

// HRD list
$stmtHrd = $conn->prepare("SELECT h.nama_lengkap, h.jabatan FROM hrd h WHERE h.id_perusahaan = ? AND h.is_active = 1");
$stmtHrd->bind_param("s", $id_perusahaan);
$stmtHrd->execute();
$hrdList = $stmtHrd->get_result();

$tipeLabels = ['full_time' => 'Full Time', 'part_time' => 'Part Time', 'kontrak' => 'Kontrak', 'magang' => 'Magang'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($perusahaan['nama_perusahaan']); ?> - Job Board</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen">
    <?php include '../includes/navbar.php'; ?>

    <div class="max-w-5xl mx-auto px-6 py-10">

        <!-- Header Perusahaan -->
        <div class="bg-white rounded-xl shadow p-8 mb-8">
            <div class="flex flex-col md:flex-row gap-6 items-start">
                <?php if ($perusahaan['logo_url']): ?>
                    <img src="<?php echo htmlspecialchars($perusahaan['logo_url']); ?>" alt="Logo"
                         class="w-24 h-24 rounded-xl object-contain border border-gray-100 p-2 flex-shrink-0">
                <?php else: ?>
                    <div class="w-24 h-24 rounded-xl bg-blue-100 flex items-center justify-center text-4xl font-bold text-blue-600 flex-shrink-0">
                        <?php echo strtoupper(substr($perusahaan['nama_perusahaan'], 0, 1)); ?>
                    </div>
                <?php endif; ?>

                <div class="flex-1">
                    <h1 class="text-3xl font-bold text-gray-800 mb-1"><?php echo htmlspecialchars($perusahaan['nama_perusahaan']); ?></h1>
                    <p class="text-gray-500 text-lg mb-4"><?php echo htmlspecialchars($perusahaan['industri'] ?? 'Umum'); ?></p>

                    <div class="flex flex-wrap gap-4 text-sm text-gray-600">
                        <?php if ($perusahaan['kota']): ?>
                        <span class="flex items-center gap-1">
                            📍 <?php echo htmlspecialchars($perusahaan['kota']); ?>
                        </span>
                        <?php endif; ?>
                        <?php if ($perusahaan['website']): ?>
                        <a href="<?php echo htmlspecialchars($perusahaan['website']); ?>" target="_blank"
                           class="flex items-center gap-1 text-blue-600 hover:underline">
                            🌐 <?php echo htmlspecialchars($perusahaan['website']); ?>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="text-center bg-blue-50 rounded-xl p-4 flex-shrink-0">
                    <p class="text-3xl font-bold text-blue-600"><?php echo $lowonganAktif->num_rows; ?></p>
                    <p class="text-blue-700 text-sm font-medium">Lowongan Aktif</p>
                </div>
            </div>

            <?php if ($perusahaan['deskripsi']): ?>
            <div class="mt-6 pt-6 border-t">
                <h2 class="text-lg font-bold text-gray-700 mb-2">Tentang Perusahaan</h2>
                <p class="text-gray-600 leading-relaxed whitespace-pre-line"><?php echo htmlspecialchars($perusahaan['deskripsi']); ?></p>
            </div>
            <?php endif; ?>

            <?php if ($perusahaan['alamat']): ?>
            <div class="mt-4 text-sm text-gray-500">
                📮 Alamat: <?php echo htmlspecialchars($perusahaan['alamat']); ?>
            </div>
            <?php endif; ?>
        </div>

        <div class="grid md:grid-cols-3 gap-8">

            <!-- Lowongan Aktif -->
            <div class="md:col-span-2">
                <h2 class="text-xl font-bold text-gray-800 mb-4">Lowongan Tersedia</h2>

                <?php 
                $lowonganAktif->data_seek(0);
                if ($lowonganAktif->num_rows === 0): ?>
                    <div class="bg-white rounded-xl shadow p-10 text-center">
                        <div class="text-5xl text-gray-200 mb-3">🔍</div>
                        <p class="text-gray-500">Belum ada lowongan aktif saat ini.</p>
                    </div>
                <?php else: ?>
                    <div class="space-y-4">
                        <?php while ($low = $lowonganAktif->fetch_assoc()): ?>
                        <a href="../lowongan/detail.php?id=<?php echo $low['id_lowongan']; ?>"
                           class="bg-white rounded-xl shadow hover:shadow-md transition p-5 flex justify-between items-start group block">
                            <div>
                                <h3 class="font-bold text-gray-800 group-hover:text-blue-600 transition mb-1">
                                    <?php echo htmlspecialchars($low['judul_lowongan']); ?>
                                </h3>
                                <div class="flex flex-wrap gap-2 text-xs text-gray-500">
                                    <span class="bg-gray-100 px-2 py-1 rounded"><?php echo htmlspecialchars($low['lokasi']); ?></span>
                                    <span class="bg-gray-100 px-2 py-1 rounded"><?php echo $tipeLabels[$low['tipe_pekerjaan']] ?? $low['tipe_pekerjaan']; ?></span>
                                    <span class="bg-gray-100 px-2 py-1 rounded"><?php echo htmlspecialchars($low['nama_kategori']); ?></span>
                                </div>
                                <?php if ($low['gaji_min'] && $low['gaji_max']): ?>
                                <p class="mt-2 text-sm font-medium text-green-700">
                                    Rp <?php echo number_format($low['gaji_min'],0,',','.'); ?> – <?php echo number_format($low['gaji_max'],0,',','.'); ?>
                                </p>
                                <?php endif; ?>
                            </div>
                            <div class="text-right flex-shrink-0 ml-4">
                                <p class="text-xs text-gray-400">Tutup</p>
                                <p class="text-sm font-semibold text-gray-600"><?php echo date('d M Y', strtotime($low['tanggal_tutup'])); ?></p>
                                <p class="text-xs text-gray-400 mt-1"><?php echo $low['jumlah_pelamar']; ?>/<?php echo $low['kuota_pelamar']; ?> pelamar</p>
                            </div>
                        </a>
                        <?php endwhile; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Sidebar: Tim HR -->
            <div>
                <h2 class="text-xl font-bold text-gray-800 mb-4">Tim HR</h2>
                <?php if ($hrdList->num_rows === 0): ?>
                    <div class="bg-white rounded-xl shadow p-6 text-center text-gray-400 text-sm">Tidak ada info tim HR.</div>
                <?php else: ?>
                    <div class="space-y-3">
                        <?php while ($hrd = $hrdList->fetch_assoc()): ?>
                        <div class="bg-white rounded-xl shadow p-4 flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center font-bold text-blue-600 flex-shrink-0">
                                <?php echo strtoupper(substr($hrd['nama_lengkap'], 0, 1)); ?>
                            </div>
                            <div>
                                <p class="font-semibold text-gray-800 text-sm"><?php echo htmlspecialchars($hrd['nama_lengkap']); ?></p>
                                <p class="text-gray-400 text-xs"><?php echo htmlspecialchars($hrd['jabatan'] ?? 'HR'); ?></p>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    </div>
                <?php endif; ?>

                <div class="mt-6">
                    <a href="list.php" class="text-sm text-gray-500 hover:text-gray-700">← Kembali ke daftar perusahaan</a>
                </div>
            </div>

        </div>
    </div>
</body>
</html>
