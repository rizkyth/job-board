<?php
require_once '../config/database.php';
require_once '../config/session.php';

requireLogin();

$role = $_SESSION['role'];
$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';

if ($role === 'kandidat') {
    // Kandidat hanya lihat lamaran sendiri
    $query = "SELECT la.*, lo.judul_lowongan, lo.tipe_pekerjaan, lo.lokasi,
                     p.nama_perusahaan, p.logo_url
              FROM lamaran la
              JOIN lowongan lo ON la.id_lowongan = lo.id_lowongan
              JOIN perusahaan p ON lo.id_perusahaan = p.id_perusahaan
              JOIN kandidat k ON la.id_kandidat = k.id_kandidat
              WHERE k.id_user = ?
              ORDER BY la.tanggal_lamar DESC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $_SESSION['id_user']);
} else {
    // HRD lihat semua lamaran ke perusahaannya
    $query = "SELECT la.*, lo.judul_lowongan, lo.tipe_pekerjaan, lo.lokasi,
                     p.nama_perusahaan, k.nama_lengkap AS nama_kandidat,
                     u.email AS email_kandidat
              FROM lamaran la
              JOIN lowongan lo ON la.id_lowongan = lo.id_lowongan
              JOIN perusahaan p ON lo.id_perusahaan = p.id_perusahaan
              JOIN kandidat k ON la.id_kandidat = k.id_kandidat
              JOIN users u ON k.id_user = u.id_user
              JOIN hrd h ON h.id_perusahaan = p.id_perusahaan
              WHERE h.id_user = ?
              ORDER BY la.tanggal_lamar DESC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $_SESSION['id_user']);
}

$stmt->execute();
$lamaranList = $stmt->get_result();

$statusColors = [
    'menunggu' => 'bg-yellow-100 text-yellow-800',
    'proses'   => 'bg-blue-100 text-blue-800',
    'diterima' => 'bg-green-100 text-green-800',
    'ditolak'  => 'bg-red-100 text-red-800',
];
$statusLabels = [
    'menunggu' => 'Menunggu',
    'proses'   => 'Diproses',
    'diterima' => 'Diterima',
    'ditolak'  => 'Ditolak',
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $role === 'hrd' ? 'Daftar Lamaran' : 'Lamaran Saya'; ?> - Job Board</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen">
    <?php include '../includes/navbar.php'; ?>

    <div class="max-w-7xl mx-auto px-6 py-10">
        <h1 class="text-3xl font-bold text-gray-800 mb-2">
            <?php echo $role === 'hrd' ? 'Daftar Lamaran Masuk' : 'Lamaran Saya'; ?>
        </h1>
        <p class="text-gray-500 mb-8">
            <?php echo $role === 'hrd' ? 'Kelola lamaran kandidat ke perusahaan Anda' : 'Pantau status lamaran pekerjaan Anda'; ?>
        </p>

        <?php if ($success): ?>
            <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-6">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($lamaranList->num_rows === 0): ?>
            <div class="bg-white rounded-xl shadow p-16 text-center">
                <div class="text-gray-300 text-6xl mb-4">📄</div>
                <p class="text-gray-500 text-lg">
                    <?php echo $role === 'kandidat' ? 'Anda belum melamar pekerjaan apapun.' : 'Belum ada lamaran masuk.'; ?>
                </p>
                <?php if ($role === 'kandidat'): ?>
                    <a href="../lowongan/list.php" class="mt-4 inline-block bg-blue-600 hover:bg-blue-700 text-white font-semibold px-6 py-2 rounded-lg transition">
                        Cari Lowongan
                    </a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="space-y-4">
                <?php while ($lamaran = $lamaranList->fetch_assoc()): ?>
                    <div class="bg-white rounded-xl shadow hover:shadow-md transition p-6 flex flex-col md:flex-row md:items-center gap-4">
                        <div class="flex-1">
                            <div class="flex flex-wrap items-center gap-3 mb-1">
                                <h2 class="text-lg font-bold text-gray-800"><?php echo htmlspecialchars($lamaran['judul_lowongan']); ?></h2>
                                <span class="text-xs font-semibold px-2 py-1 rounded-full <?php echo $statusColors[$lamaran['status_lamaran']]; ?>">
                                    <?php echo $statusLabels[$lamaran['status_lamaran']]; ?>
                                </span>
                            </div>
                            <p class="text-gray-500 text-sm mb-1">
                                <?php if ($role === 'kandidat'): ?>
                                    🏢 <?php echo htmlspecialchars($lamaran['nama_perusahaan']); ?>
                                <?php else: ?>
                                    👤 <?php echo htmlspecialchars($lamaran['nama_kandidat']); ?> &mdash;
                                    <span class="text-gray-400"><?php echo htmlspecialchars($lamaran['email_kandidat']); ?></span>
                                <?php endif; ?>
                            </p>
                            <p class="text-gray-400 text-xs">
                                📍 <?php echo htmlspecialchars($lamaran['lokasi']); ?> &nbsp;|&nbsp;
                                📅 Dilamar: <?php echo date('d M Y', strtotime($lamaran['tanggal_lamar'])); ?>
                            </p>
                            <?php if ($lamaran['catatan_hr']): ?>
                                <p class="mt-2 text-sm text-blue-700 bg-blue-50 px-3 py-1 rounded">
                                    💬 Catatan HR: <?php echo htmlspecialchars($lamaran['catatan_hr']); ?>
                                </p>
                            <?php endif; ?>
                        </div>
                        <div class="flex gap-2 flex-shrink-0">
                            <a href="detail.php?id=<?php echo $lamaran['id_lamaran']; ?>"
                               class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-4 py-2 rounded-lg transition">
                                Detail
                            </a>
                            <?php if ($role === 'kandidat' && $lamaran['status_lamaran'] === 'menunggu'): ?>
                                <a href="edit.php?id=<?php echo $lamaran['id_lamaran']; ?>"
                                   class="bg-gray-200 hover:bg-gray-300 text-gray-700 text-sm font-semibold px-4 py-2 rounded-lg transition">
                                    Edit
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
