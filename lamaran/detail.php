<?php
require_once '../config/database.php';
require_once '../config/session.php';

requireLogin().

$id_lamaran = sanitize($_GET['id'] ?? '');
if (empty($id_lamaran)) {
    header("Location: list.php");
    exit();
}

// Ambil detail lamaran
$query = "SELECT la.*, 
                 lo.judul_lowongan, lo.tipe_pekerjaan, lo.lokasi, lo.deskripsi_pekerjaan,
                 p.nama_perusahaan, p.id_perusahaan,
                 k.nama_lengkap AS nama_kandidat, k.no_telepon, k.pendidikan_terakhir,
                 k.pengalaman_kerja, k.bio, k.foto_url, k.id_kandidat,
                 u.email AS email_kandidat
          FROM lamaran la
          JOIN lowongan lo ON la.id_lowongan = lo.id_lowongan
          JOIN perusahaan p ON lo.id_perusahaan = p.id_perusahaan
          JOIN kandidat k ON la.id_kandidat = k.id_kandidat
          JOIN users u ON k.id_user = u.id_user
          WHERE la.id_lamaran = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $id_lamaran);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: list.php");
    exit();
}
$lamaran = $result->fetch_assoc();

// Akses kontrol
if ($_SESSION['role'] === 'kandidat') {
    $stmtK = $conn->prepare("SELECT id_kandidat FROM kandidat WHERE id_user = ?");
    $stmtK->bind_param("s", $_SESSION['id_user']);
    $stmtK->execute();
    $myKandidat = $stmtK->get_result()->fetch_assoc();
    if (!$myKandidat || $myKandidat['id_kandidat'] !== $lamaran['id_kandidat']) {
        header("Location: list.php");
        exit();
    }
}

// HRD update status
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_SESSION['role'] === 'hrd') {
    $status_baru  = sanitize($_POST['status_baru']);
    $keterangan   = sanitize($_POST['keterangan']);
    $validStatus  = ['menunggu', 'proses', 'diterima', 'ditolak'];

    if (!in_array($status_baru, $validStatus)) {
        $error = "Status tidak valid.";
    } else {
        // Ambil id_hrd
        $stmtH = $conn->prepare("SELECT id_hrd FROM hrd WHERE id_user = ?");
        $stmtH->bind_param("s", $_SESSION['id_user']);
        $stmtH->execute();
        $hrdData = $stmtH->get_result()->fetch_assoc();

        if ($hrdData) {
            // Simpan status lamaran dan catatan HR ke tabel lamaran
            $stmtUpdate = $conn->prepare("UPDATE lamaran SET status_lamaran = ?, catatan_hr = ? WHERE id_lamaran = ?");
            $stmtUpdate->bind_param("sss", $status_baru, $keterangan, $id_lamaran);
            
            if ($stmtUpdate->execute()) {
                // Simpan catatan HR juga ke tabel lowongan
                $id_lowongan = $lamaran['id_lowongan'];
                $stmtLowongan = $conn->prepare("UPDATE lowongan SET catatan_hr = ? WHERE id_lowongan = ?");
                if ($stmtLowongan) {
                    $stmtLowongan->bind_param("ss", $keterangan, $id_lowongan);
                    $stmtLowongan->execute();
                }

                // Mencoba mencatat ke riwayat jika databasenya mendukung tabel riwayat_status
                $id_riwayat = generateUUID();
                $stmtHistory = $conn->prepare("INSERT INTO riwayat_status (id_riwayat, id_lamaran, id_hrd, status_baru, keterangan) VALUES (?, ?, ?, ?, ?)");
                if ($stmtHistory) {
                    $stmtHistory->bind_param("sssss", $id_riwayat, $id_lamaran, $hrdData['id_hrd'], $status_baru, $keterangan);
                    $stmtHistory->execute();
                }

                header("Location: detail.php?id=" . $id_lamaran . "&success=" . urlencode("Status lamaran berhasil diperbarui."));
                exit();
            } else {
                $error = "Gagal memperbarui status.";
            }
        }
    }
}

// Riwayat status
$riwayat = $conn->prepare("SELECT rs.*, h.nama_lengkap AS nama_hrd FROM riwayat_status rs
                            JOIN hrd h ON rs.id_hrd = h.id_hrd
                            WHERE rs.id_lamaran = ? ORDER BY rs.changed_at DESC");
$riwayat->bind_param("s", $id_lamaran);
$riwayat->execute();
$riwayatList = $riwayat->get_result();

if (isset($_GET['success'])) $success = htmlspecialchars($_GET['success']);

$statusColors = [
    'menunggu' => 'bg-yellow-100 text-yellow-800',
    'proses'   => 'bg-blue-100 text-blue-800',
    'diterima' => 'bg-green-100 text-green-800',
    'ditolak'  => 'bg-red-100 text-red-800',
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Lamaran - Job Board</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen">
    <?php include '../includes/navbar.php'; ?>

    <div class="max-w-5xl mx-auto px-6 py-10 grid md:grid-cols-3 gap-8">

        <div class="md:col-span-2 space-y-6">

            <?php if ($success): ?>
                <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg"><?php echo $success; ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg"><?php echo $error; ?></div>
            <?php endif; ?>

            <div class="bg-white rounded-xl shadow p-6">
                <div class="flex items-center justify-between mb-1">
                    <h1 class="text-2xl font-bold text-gray-800"><?php echo htmlspecialchars($lamaran['judul_lowongan']); ?></h1>
                    <span class="text-sm font-semibold px-3 py-1 rounded-full <?php echo $statusColors[$lamaran['status_lamaran']]; ?>">
                        <?php echo ucfirst($lamaran['status_lamaran']); ?>
                    </span>
                </div>
                <p class="text-gray-500 mb-4">🏢 <?php echo htmlspecialchars($lamaran['nama_perusahaan']); ?> &nbsp;|&nbsp; 📍 <?php echo htmlspecialchars($lamaran['lokasi']); ?></p>
                <p class="text-gray-400 text-sm">Dilamar pada: <?php echo date('d M Y, H:i', strtotime($lamaran['tanggal_lamar'])); ?></p>
            </div>

            <div class="bg-white rounded-xl shadow p-6">
                <h2 class="text-lg font-bold text-gray-700 mb-3">📝 Surat Lamaran</h2>
                <p class="text-gray-700 whitespace-pre-line leading-relaxed"><?php echo htmlspecialchars($lamaran['surat_lamaran']); ?></p>
            </div>

            <?php if ($lamaran['cv_url']): ?>
            <div class="bg-white rounded-xl shadow p-6">
                <h2 class="text-lg font-bold text-gray-700 mb-3">📎 CV / Portfolio</h2>
                <a href="<?php echo htmlspecialchars($lamaran['cv_url']); ?>" target="_blank"
                   class="inline-flex items-center gap-2 text-blue-600 hover:underline font-medium">
                    Buka CV / Portfolio →
                </a>
            </div>
            <?php endif; ?>

            <?php if ($lamaran['catatan_hr']): ?>
            <div class="bg-blue-50 border border-blue-200 rounded-xl p-6">
                <h2 class="text-lg font-bold text-blue-800 mb-2">💬 Catatan dari HR</h2>
                <p class="text-blue-900"><?php echo htmlspecialchars($lamaran['catatan_hr']); ?></p>
            </div>
            <?php endif; ?>

            <?php if ($_SESSION['role'] === 'hrd'): ?>
            <div class="bg-white rounded-xl shadow p-6">
                <h2 class="text-lg font-bold text-gray-700 mb-4">🔄 Perbarui Status Lamaran</h2>
                <form method="POST" class="space-y-4">
                    <div>
                        <label class="block text-gray-600 font-semibold mb-1">Status Baru</label>
                        <select name="status_baru" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="menunggu" <?php if($lamaran['status_lamaran']==='menunggu') echo 'selected'; ?>>Menunggu</option>
                            <option value="proses"   <?php if($lamaran['status_lamaran']==='proses') echo 'selected'; ?>>Diproses</option>
                            <option value="diterima" <?php if($lamaran['status_lamaran']==='diterima') echo 'selected'; ?>>Diterima</option>
                            <option value="ditolak"  <?php if($lamaran['status_lamaran']==='ditolak') echo 'selected'; ?>>Ditolak</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-gray-600 font-semibold mb-1">Catatan untuk Kandidat <span class="font-normal text-gray-400">(opsional)</span></label>
                        <textarea name="keterangan" rows="3"
                                  placeholder="Tulis keterangan atau feedback untuk kandidat..."
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"><?php echo htmlspecialchars($lamaran['catatan_hr'] ?? ''); ?></textarea>
                    </div>
                    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 rounded-lg transition">
                        Simpan Perubahan
                    </button>
                </form>
            </div>
            <?php endif; ?>

            <?php if ($riwayatList->num_rows > 0): ?>
            <div class="bg-white rounded-xl shadow p-6">
                <h2 class="text-lg font-bold text-gray-700 mb-4">🕓 Riwayat Status</h2>
                <div class="space-y-3">
                    <?php while ($r = $riwayatList->fetch_assoc()): ?>
                    <div class="flex items-start gap-3 text-sm">
                        <div class="w-2 h-2 rounded-full bg-blue-400 mt-2 flex-shrink-0"></div>
                        <div>
                            <p class="text-gray-700">
                                <span class="font-semibold"><?php echo ucfirst($r['status_lama'] ?? ''); ?></span>
                                → <span class="font-semibold"><?php echo ucfirst($r['status_baru'] ?? ''); ?></span>
                                <span class="text-gray-400">oleh <?php echo htmlspecialchars($r['nama_hrd']); ?></span>
                            </p>
                            <?php if ($r['keterangan']): ?>
                                <p class="text-gray-500 italic">"<?php echo htmlspecialchars($r['keterangan']); ?>"</p>
                            <?php endif; ?>
                            <p class="text-gray-400 text-xs"><?php echo date('d M Y, H:i', strtotime($r['changed_at'])); ?></p>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
            </div>
            <?php endif; ?>

        </div>

        <div class="space-y-6">
            <div class="bg-white rounded-xl shadow p-6 text-center">
                <?php if ($lamaran['foto_url']): ?>
                    <img src="<?php echo htmlspecialchars($lamaran['foto_url']); ?>" alt="Foto"
                         class="w-20 h-20 rounded-full object-cover mx-auto mb-3 border-2 border-gray-200">
                <?php else: ?>
                    <div class="w-20 h-20 rounded-full bg-blue-100 flex items-center justify-center mx-auto mb-3 text-3xl">👤</div>
                <?php endif; ?>
                <h3 class="font-bold text-gray-800 text-lg"><?php echo htmlspecialchars($lamaran['nama_kandidat']); ?></h3>
                <p class="text-gray-500 text-sm"><?php echo htmlspecialchars($lamaran['email_kandidat']); ?></p>
                <?php if ($lamaran['no_telepon']): ?>
                    <p class="text-gray-500 text-sm">📞 <?php echo htmlspecialchars($lamaran['no_telepon']); ?></p>
                <?php endif; ?>
            </div>

            <div class="bg-white rounded-xl shadow p-6 space-y-3 text-sm">
                <h3 class="font-bold text-gray-700">Kualifikasi</h3>
                <div class="flex justify-between">
                    <span class="text-gray-500">Pendidikan</span>
                    <span class="font-medium text-gray-800"><?php echo htmlspecialchars($lamaran['pendidikan_terakhir'] ?? '-'); ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">Pengalaman</span>
                    <span class="font-medium text-gray-800"><?php echo ($lamaran['pengalaman_kerja'] ?? 0); ?> tahun</span>
                </div>
                <?php if ($lamaran['bio']): ?>
                <div class="pt-2 border-t">
                    <p class="text-gray-500 mb-1">Bio</p>
                    <p class="text-gray-700"><?php echo htmlspecialchars($lamaran['bio']); ?></p>
                </div>
                <?php endif; ?>
            </div>

            <a href="list.php" class="block text-center text-gray-500 hover:text-gray-700 text-sm">← Kembali ke daftar</a>
        </div>

    </div>
</body>
</html>
