<?php
require_once '../config/database.php';
require_once '../config/session.php';

requireRole('kandidat');

$search = sanitize($_GET['q'] ?? '');
$filter_industri = sanitize($_GET['industri'] ?? '');

// Ambil daftar industri untuk filter
$industri_list = $conn->query("SELECT DISTINCT industri FROM perusahaan WHERE industri IS NOT NULL ORDER BY industri");

// Query perusahaan
$params = [];
$types  = "";
$where  = "WHERE 1=1";

if (!empty($search)) {
    $where .= " AND (p.nama_perusahaan LIKE ? OR p.kota LIKE ? OR p.deskripsi LIKE ?)";
    $like = "%$search%";
    $params = array_merge($params, [$like, $like, $like]);
    $types .= "sss";
}
if (!empty($filter_industri)) {
    $where .= " AND p.industri = ?";
    $params[] = $filter_industri;
    $types   .= "s";
}

$query = "SELECT p.*, 
                 COUNT(DISTINCT lo.id_lowongan) AS total_lowongan,
                 COUNT(DISTINCT CASE WHEN lo.status = 'buka' AND lo.tanggal_tutup >= CURDATE() THEN lo.id_lowongan END) AS lowongan_aktif
          FROM perusahaan p
          LEFT JOIN lowongan lo ON lo.id_perusahaan = p.id_perusahaan
          $where
          GROUP BY p.id_perusahaan
          ORDER BY lowongan_aktif DESC, p.nama_perusahaan ASC";

if (!empty($params)) {
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $perusahaanList = $stmt->get_result();
} else {
    $perusahaanList = $conn->query($query);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Perusahaan - Job Board</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen">
    <?php include '../includes/navbar.php'; ?>

    <div class="max-w-7xl mx-auto px-6 py-10">
        <h1 class="text-3xl font-bold text-gray-800 mb-2">Perusahaan</h1>
        <p class="text-gray-500 mb-8">Temukan perusahaan impian Anda</p>

        <!-- Search & Filter -->
        <form method="GET" class="bg-white rounded-xl shadow p-5 mb-8 flex flex-wrap gap-4 items-end">
            <div class="flex-1 min-w-[220px]">
                <label class="block text-sm font-semibold text-gray-600 mb-1">Cari Perusahaan</label>
                <input type="text" name="q" value="<?php echo htmlspecialchars($search); ?>"
                       placeholder="Nama perusahaan, kota..."
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="flex-1 min-w-[180px]">
                <label class="block text-sm font-semibold text-gray-600 mb-1">Industri</label>
                <select name="industri" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Semua Industri</option>
                    <?php while ($ind = $industri_list->fetch_assoc()): ?>
                        <option value="<?php echo htmlspecialchars($ind['industri']); ?>"
                                <?php if($filter_industri === $ind['industri']) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($ind['industri']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-5 py-2 rounded-lg transition">
                    Cari
                </button>
                <a href="list.php" class="bg-gray-200 hover:bg-gray-300 text-gray-700 text-sm font-semibold px-4 py-2 rounded-lg transition">
                    Reset
                </a>
            </div>
        </form>

        <!-- Daftar Perusahaan -->
        <?php if ($perusahaanList->num_rows === 0): ?>
            <div class="bg-white rounded-xl shadow p-16 text-center">
                <div class="text-6xl text-gray-200 mb-4">🏢</div>
                <p class="text-gray-500 text-lg">Tidak ada perusahaan yang ditemukan.</p>
                <a href="list.php" class="mt-4 inline-block text-blue-600 hover:underline text-sm">Lihat semua perusahaan</a>
            </div>
        <?php else: ?>
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php while ($p = $perusahaanList->fetch_assoc()): ?>
                <a href="detail.php?id=<?php echo $p['id_perusahaan']; ?>"
                   class="bg-white rounded-xl shadow hover:shadow-lg transition p-6 flex flex-col group">
                    <div class="flex items-center gap-4 mb-4">
                        <?php if ($p['logo_url']): ?>
                            <img src="<?php echo htmlspecialchars($p['logo_url']); ?>" alt="Logo"
                                 class="w-14 h-14 rounded-xl object-contain border border-gray-100 p-1">
                        <?php else: ?>
                            <div class="w-14 h-14 rounded-xl bg-blue-100 flex items-center justify-center text-2xl font-bold text-blue-600">
                                <?php echo strtoupper(substr($p['nama_perusahaan'], 0, 1)); ?>
                            </div>
                        <?php endif; ?>
                        <div>
                            <h2 class="font-bold text-gray-800 group-hover:text-blue-600 transition"><?php echo htmlspecialchars($p['nama_perusahaan']); ?></h2>
                            <p class="text-gray-400 text-sm"><?php echo htmlspecialchars($p['industri'] ?? 'Lainnya'); ?></p>
                        </div>
                    </div>

                    <?php if ($p['deskripsi']): ?>
                    <p class="text-gray-600 text-sm line-clamp-3 mb-4 flex-1">
                        <?php echo htmlspecialchars(substr($p['deskripsi'], 0, 120)) . (strlen($p['deskripsi']) > 120 ? '...' : ''); ?>
                    </p>
                    <?php endif; ?>

                    <div class="mt-auto pt-4 border-t border-gray-100 flex justify-between text-sm">
                        <span class="text-gray-400">
                            📍 <?php echo htmlspecialchars($p['kota'] ?? 'Indonesia'); ?>
                        </span>
                        <span class="font-semibold <?php echo $p['lowongan_aktif'] > 0 ? 'text-green-600' : 'text-gray-400'; ?>">
                            <?php echo $p['lowongan_aktif']; ?> lowongan aktif
                        </span>
                    </div>
                </a>
                <?php endwhile; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
