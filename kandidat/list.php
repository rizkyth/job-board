<?php
require_once '../config/database.php';
require_once '../config/session.php';

requireRole('hrd').

// Filter
$filter_status   = sanitize($_GET['status'] ?? '');
$filter_lowongan = sanitize($_GET['id_lowongan'] ?? '');

// Ambil daftar lowongan milik perusahaan HRD ini (untuk dropdown filter)
$stmtLow = $conn->prepare("SELECT lo.id_lowongan, lo.judul_lowongan 
                            FROM lowongan lo
                            JOIN hrd h ON h.id_perusahaan = lo.id_perusahaan
                            WHERE h.id_user = ?
                            ORDER BY lo.created_at DESC");
$stmtLow->bind_param("s", $_SESSION['id_user']);
$stmtLow->execute();
$lowonganList = $stmtLow->get_result();

// Query kandidat
$params = [$_SESSION['id_user']];
$types  = "s";
$where  = "";

if (!empty($filter_status)) {
    $where .= " AND la.status_lamaran = ?";
    $params[] = $filter_status;
    $types   .= "s";
}
if (!empty($filter_lowongan)) {
    $where .= " AND lo.id_lowongan = ?";
    $params[] = $filter_lowongan;
    $types   .= "s";
}

$query = "SELECT k.id_kandidat, k.nama_lengkap, k.pendidikan_terakhir, k.pengalaman_kerja,
                 k.foto_url, u.email,
                 la.id_lamaran, la.status_lamaran, la.tanggal_lamar,
                 lo.judul_lowongan
          FROM lamaran la
          JOIN kandidat k ON la.id_kandidat = k.id_kandidat
          JOIN users u ON k.id_user = u.id_user
          JOIN lowongan lo ON la.id_lowongan = lo.id_lowongan
          JOIN perusahaan p ON lo.id_perusahaan = p.id_perusahaan
          JOIN hrd h ON h.id_perusahaan = p.id_perusahaan
          WHERE h.id_user = ? $where
          ORDER BY la.tanggal_lamar DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$kandidatList = $stmt->get_result();

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
    <title>Daftar Kandidat - Job Board</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen">
    <?php include '../includes/navbar.php'; ?>

    <div class="max-w-7xl mx-auto px-6 py-10">
        <h1 class="text-3xl font-bold text-gray-800 mb-2">Daftar Kandidat</h1>
        <p class="text-gray-500 mb-8">Kandidat yang melamar ke perusahaan Anda</p>

        <!-- Filter -->
        <form method="GET" class="bg-white rounded-xl shadow p-5 mb-8 flex flex-wrap gap-4 items-end">
            <div class="flex-1 min-w-[180px]">
                <label class="block text-sm font-semibold text-gray-600 mb-1">Filter Lowongan</label>
                <select name="id_lowongan" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Semua Lowongan</option>
                    <?php 
                    $lowonganList->data_seek(0);
                    while ($low = $lowonganList->fetch_assoc()): ?>
                        <option value="<?php echo $low['id_lowongan']; ?>" <?php if($filter_lowongan === $low['id_lowongan']) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($low['judul_lowongan']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="flex-1 min-w-[160px]">
                <label class="block text-sm font-semibold text-gray-600 mb-1">Filter Status</label>
                <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Semua Status</option>
                    <option value="menunggu" <?php if($filter_status==='menunggu') echo 'selected'; ?>>Menunggu</option>
                    <option value="proses"   <?php if($filter_status==='proses') echo 'selected'; ?>>Diproses</option>
                    <option value="diterima" <?php if($filter_status==='diterima') echo 'selected'; ?>>Diterima</option>
                    <option value="ditolak"  <?php if($filter_status==='ditolak') echo 'selected'; ?>>Ditolak</option>
                </select>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-5 py-2 rounded-lg transition">
                    Filter
                </button>
                <a href="list.php" class="bg-gray-200 hover:bg-gray-300 text-gray-700 text-sm font-semibold px-4 py-2 rounded-lg transition">
                    Reset
                </a>
            </div>
        </form>

        <!-- Statistik -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
            <?php
            $statQuery = $conn->prepare("SELECT status_lamaran, COUNT(*) as total FROM lamaran la
                                         JOIN lowongan lo ON la.id_lowongan = lo.id_lowongan
                                         JOIN hrd h ON h.id_perusahaan = lo.id_perusahaan
                                         WHERE h.id_user = ? GROUP BY status_lamaran");
            $statQuery->bind_param("s", $_SESSION['id_user']);
            $statQuery->execute();
            $stats = [];
            $statResult = $statQuery->get_result();
            while ($s = $statResult->fetch_assoc()) $stats[$s['status_lamaran']] = $s['total'];
            $statDef = ['menunggu' => ['🕐','Menunggu','yellow'], 'proses' => ['🔄','Diproses','blue'], 'diterima' => ['✅','Diterima','green'], 'ditolak' => ['❌','Ditolak','red']];
            foreach ($statDef as $key => [$icon, $label, $color]):
            ?>
            <div class="bg-white rounded-xl shadow p-4 text-center">
                <div class="text-2xl mb-1"><?php echo $icon; ?></div>
                <div class="text-2xl font-bold text-gray-800"><?php echo $stats[$key] ?? 0; ?></div>
                <div class="text-gray-500 text-sm"><?php echo $label; ?></div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Tabel Kandidat -->
        <?php if ($kandidatList->num_rows === 0): ?>
            <div class="bg-white rounded-xl shadow p-16 text-center">
                <div class="text-6xl text-gray-200 mb-4">👥</div>
                <p class="text-gray-500 text-lg">Belum ada kandidat yang melamar.</p>
            </div>
        <?php else: ?>
        <div class="bg-white rounded-xl shadow overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b">
                    <tr>
                        <th class="text-left px-6 py-4 text-gray-600 font-semibold">Kandidat</th>
                        <th class="text-left px-4 py-4 text-gray-600 font-semibold">Posisi</th>
                        <th class="text-left px-4 py-4 text-gray-600 font-semibold">Kualifikasi</th>
                        <th class="text-left px-4 py-4 text-gray-600 font-semibold">Tanggal Lamar</th>
                        <th class="text-left px-4 py-4 text-gray-600 font-semibold">Status</th>
                        <th class="px-4 py-4"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php while ($k = $kandidatList->fetch_assoc()): ?>
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <?php if ($k['foto_url']): ?>
                                    <img src="<?php echo htmlspecialchars($k['foto_url']); ?>" class="w-10 h-10 rounded-full object-cover">
                                <?php else: ?>
                                    <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center font-bold text-blue-600">
                                        <?php echo strtoupper(substr($k['nama_lengkap'], 0, 1)); ?>
                                    </div>
                                <?php endif; ?>
                                <div>
                                    <p class="font-semibold text-gray-800"><?php echo htmlspecialchars($k['nama_lengkap']); ?></p>
                                    <p class="text-gray-400 text-xs"><?php echo htmlspecialchars($k['email']); ?></p>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-4 text-gray-700"><?php echo htmlspecialchars($k['judul_lowongan']); ?></td>
                        <td class="px-4 py-4 text-gray-600">
                            <p><?php echo htmlspecialchars($k['pendidikan_terakhir'] ?? '-'); ?></p>
                            <p class="text-gray-400 text-xs"><?php echo $k['pengalaman_kerja'] ?? 0; ?> thn pengalaman</p>
                        </td>
                        <td class="px-4 py-4 text-gray-500"><?php echo date('d M Y', strtotime($k['tanggal_lamar'])); ?></td>
                        <td class="px-4 py-4">
                            <span class="text-xs font-semibold px-2 py-1 rounded-full <?php echo $statusColors[$k['status_lamaran']]; ?>">
                                <?php echo ucfirst($k['status_lamaran']); ?>
                            </span>
                        </td>
                        <td class="px-4 py-4">
                            <a href="../lamaran/detail.php?id=<?php echo $k['id_lamaran']; ?>"
                               class="text-blue-600 hover:text-blue-800 font-semibold text-xs">
                                Detail →
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>
