<?php
require_once 'config/database.php';
require_once 'config/session.php';

requireLogin();

$role = $_SESSION['role'];

if ($role === 'kandidat') {
    // Statistik kandidat
    $stmtK = $conn->prepare("SELECT id_kandidat, nama_lengkap FROM kandidat WHERE id_user = ?");
    $stmtK->bind_param("s", $_SESSION['id_user']);
    $stmtK->execute();
    $kandidat = $stmtK->get_result()->fetch_assoc();

    if ($kandidat) {
        $stmtStat = $conn->prepare("SELECT status_lamaran, COUNT(*) as total FROM lamaran WHERE id_kandidat = ? GROUP BY status_lamaran");
        $stmtStat->bind_param("s", $kandidat['id_kandidat']);
        $stmtStat->execute();
        $statResult = $stmtStat->get_result();
        $stats = ['menunggu' => 0, 'proses' => 0, 'diterima' => 0, 'ditolak' => 0];
        while ($s = $statResult->fetch_assoc()) $stats[$s['status_lamaran']] = $s['total'];

        // Lamaran terbaru
        $stmtRecent = $conn->prepare("SELECT la.status_lamaran, la.tanggal_lamar, lo.judul_lowongan, p.nama_perusahaan, la.id_lamaran
                                      FROM lamaran la
                                      JOIN lowongan lo ON la.id_lowongan = lo.id_lowongan
                                      JOIN perusahaan p ON lo.id_perusahaan = p.id_perusahaan
                                      WHERE la.id_kandidat = ?
                                      ORDER BY la.tanggal_lamar DESC LIMIT 5");
        $stmtRecent->bind_param("s", $kandidat['id_kandidat']);
        $stmtRecent->execute();
        $recentLamaran = $stmtRecent->get_result();
    }

    // Lowongan terbaru
    $lowonganBaru = $conn->query("SELECT lo.id_lowongan, lo.judul_lowongan, lo.lokasi, lo.tipe_pekerjaan, p.nama_perusahaan
                                  FROM lowongan lo
                                  JOIN perusahaan p ON lo.id_perusahaan = p.id_perusahaan
                                  WHERE lo.status = 'buka' AND lo.tanggal_tutup >= CURDATE()
                                  ORDER BY lo.created_at DESC LIMIT 5");

} else {
    // HRD
    $stmtH = $conn->prepare("SELECT h.nama_lengkap, h.jabatan, p.nama_perusahaan, p.id_perusahaan
                              FROM hrd h JOIN perusahaan p ON h.id_perusahaan = p.id_perusahaan
                              WHERE h.id_user = ?");
    $stmtH->bind_param("s", $_SESSION['id_user']);
    $stmtH->execute();
    $hrd = $stmtH->get_result()->fetch_assoc();

    if ($hrd) {
        $id_perusahaan = $hrd['id_perusahaan'];

        // Statistik HRD
        $stmtStat = $conn->prepare("SELECT 
            COUNT(DISTINCT lo.id_lowongan) as total_lowongan,
            COUNT(DISTINCT CASE WHEN lo.status = 'buka' THEN lo.id_lowongan END) as lowongan_aktif,
            COUNT(DISTINCT la.id_lamaran) as total_lamaran,
            COUNT(DISTINCT CASE WHEN la.status_lamaran = 'menunggu' THEN la.id_lamaran END) as lamaran_baru
            FROM lowongan lo
            LEFT JOIN lamaran la ON la.id_lowongan = lo.id_lowongan
            WHERE lo.id_perusahaan = ?");
        $stmtStat->bind_param("s", $id_perusahaan);
        $stmtStat->execute();
        $stats = $stmtStat->get_result()->fetch_assoc();

        // Lamaran terbaru masuk
        $stmtRecent = $conn->prepare("SELECT la.id_lamaran, la.status_lamaran, la.tanggal_lamar,
                                             lo.judul_lowongan, k.nama_lengkap AS nama_kandidat
                                      FROM lamaran la
                                      JOIN lowongan lo ON la.id_lowongan = lo.id_lowongan
                                      JOIN kandidat k ON la.id_kandidat = k.id_kandidat
                                      WHERE lo.id_perusahaan = ?
                                      ORDER BY la.tanggal_lamar DESC LIMIT 5");
        $stmtRecent->bind_param("s", $id_perusahaan);
        $stmtRecent->execute();
        $recentLamaran = $stmtRecent->get_result();

        // Lowongan milik perusahaan
        $stmtLow = $conn->prepare("SELECT id_lowongan, judul_lowongan, status, jumlah_pelamar, kuota_pelamar, tanggal_tutup
                                   FROM lowongan WHERE id_perusahaan = ? ORDER BY created_at DESC LIMIT 5");
        $stmtLow->bind_param("s", $id_perusahaan);
        $stmtLow->execute();
        $myLowongan = $stmtLow->get_result();
    }
}

$statusColors = [
    'menunggu' => 'bg-yellow-100 text-yellow-800',
    'proses'   => 'bg-blue-100 text-blue-800',
    'diterima' => 'bg-green-100 text-green-800',
    'ditolak'  => 'bg-red-100 text-red-800',
];
$statusLowongan = [
    'buka'  => 'bg-green-100 text-green-700',
    'tutup' => 'bg-red-100 text-red-700',
    'draft' => 'bg-gray-100 text-gray-600',
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Job Board</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-gray-100 min-h-screen">
    <?php include(__DIR__ . '/includes/navbar.php'); ?>

    <div class="max-w-7xl mx-auto px-6 py-10">

        <!-- Greeting -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-800">
                Selamat datang, <?php echo htmlspecialchars($role === 'kandidat' ? ($kandidat['nama_lengkap'] ?? $_SESSION['email']) : ($hrd['nama_lengkap'] ?? $_SESSION['email'])); ?>! 👋
            </h1>
            <p class="text-gray-500 mt-1">
                <?php if ($role === 'kandidat'): ?>
                    Temukan pekerjaan impian Anda dan pantau status lamaran.
                <?php else: ?>
                    <?php echo htmlspecialchars($hrd['jabatan'] ?? 'HRD'); ?> di <strong><?php echo htmlspecialchars($hrd['nama_perusahaan'] ?? ''); ?></strong>
                <?php endif; ?>
            </p>
        </div>

        <!-- Banner profil belum lengkap -->
        <?php if ($role === 'kandidat' && !$kandidat): ?>
        <div class="bg-yellow-50 border border-yellow-300 rounded-xl p-5 mb-8 flex items-center justify-between">
            <div>
                <p class="font-semibold text-yellow-800">⚠️ Profil belum dilengkapi</p>
                <p class="text-yellow-700 text-sm">Lengkapi profil Anda agar bisa melamar pekerjaan.</p>
            </div>
            <a href="profil/edit.php" class="bg-yellow-500 hover:bg-yellow-600 text-white font-semibold px-5 py-2 rounded-lg transition text-sm">
                Lengkapi Sekarang
            </a>
        </div>
        <?php endif; ?>

        <?php if ($role === 'kandidat'): ?>
        <!-- ===================== KANDIDAT DASHBOARD ===================== -->

        <!-- Stats -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
            <?php
            $statDef = [
                ['icon' => '📨', 'label' => 'Total Lamaran', 'value' => array_sum($stats), 'color' => 'blue'],
                ['icon' => '🕐', 'label' => 'Menunggu',      'value' => $stats['menunggu'], 'color' => 'yellow'],
                ['icon' => '✅', 'label' => 'Diterima',      'value' => $stats['diterima'], 'color' => 'green'],
                ['icon' => '❌', 'label' => 'Ditolak',       'value' => $stats['ditolak'],  'color' => 'red'],
            ];
            foreach ($statDef as $s):
            ?>
            <div class="bg-white rounded-xl shadow p-5 text-center">
                <div class="text-3xl mb-1"><?php echo $s['icon']; ?></div>
                <div class="text-2xl font-bold text-gray-800"><?php echo $s['value']; ?></div>
                <div class="text-gray-500 text-sm"><?php echo $s['label']; ?></div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="grid md:grid-cols-2 gap-8">

            <!-- Lamaran Terbaru -->
            <div class="bg-white rounded-xl shadow p-6">
                <div class="flex justify-between items-center mb-5">
                    <h2 class="text-lg font-bold text-gray-800">Lamaran Terbaru</h2>
                    <a href="lamaran/list.php" class="text-blue-600 text-sm hover:underline">Lihat semua →</a>
                </div>
                <?php if (!$kandidat || $recentLamaran->num_rows === 0): ?>
                    <p class="text-gray-400 text-center py-8">Belum ada lamaran.</p>
                <?php else: ?>
                    <div class="space-y-3">
                        <?php while ($la = $recentLamaran->fetch_assoc()): ?>
                        <a href="lamaran/detail.php?id=<?php echo $la['id_lamaran']; ?>"
                           class="flex items-center justify-between p-3 rounded-lg hover:bg-gray-50 transition">
                            <div>
                                <p class="font-medium text-gray-800 text-sm"><?php echo htmlspecialchars($la['judul_lowongan']); ?></p>
                                <p class="text-gray-400 text-xs"><?php echo htmlspecialchars($la['nama_perusahaan']); ?></p>
                            </div>
                            <span class="text-xs font-semibold px-2 py-1 rounded-full <?php echo $statusColors[$la['status_lamaran']]; ?>">
                                <?php echo ucfirst($la['status_lamaran']); ?>
                            </span>
                        </a>
                        <?php endwhile; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Lowongan Terbaru -->
            <div class="bg-white rounded-xl shadow p-6">
                <div class="flex justify-between items-center mb-5">
                    <h2 class="text-lg font-bold text-gray-800">Lowongan Terbaru</h2>
                    <a href="lowongan/list.php" class="text-blue-600 text-sm hover:underline">Lihat semua →</a>
                </div>
                <?php if ($lowonganBaru->num_rows === 0): ?>
                    <p class="text-gray-400 text-center py-8">Belum ada lowongan aktif.</p>
                <?php else: ?>
                    <div class="space-y-3">
                        <?php while ($lo = $lowonganBaru->fetch_assoc()): ?>
                        <a href="lowongan/detail.php?id=<?php echo $lo['id_lowongan']; ?>"
                           class="flex items-start justify-between p-3 rounded-lg hover:bg-gray-50 transition">
                            <div>
                                <p class="font-medium text-gray-800 text-sm"><?php echo htmlspecialchars($lo['judul_lowongan']); ?></p>
                                <p class="text-gray-400 text-xs"><?php echo htmlspecialchars($lo['nama_perusahaan']); ?> · <?php echo htmlspecialchars($lo['lokasi']); ?></p>
                            </div>
                            <span class="text-xs bg-blue-50 text-blue-600 px-2 py-1 rounded flex-shrink-0 ml-2">
                                <?php echo str_replace('_', ' ', ucfirst($lo['tipe_pekerjaan'])); ?>
                            </span>
                        </a>
                        <?php endwhile; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="mt-8 grid grid-cols-2 md:grid-cols-3 gap-4">
            <a href="lowongan/list.php" class="bg-blue-600 hover:bg-blue-700 text-white rounded-xl p-5 text-center transition">
                <div class="text-3xl mb-2">🔍</div>
                <p class="font-semibold">Cari Lowongan</p>
            </a>
            <a href="perusahaan/list.php" class="bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl p-5 text-center transition">
                <div class="text-3xl mb-2">🏢</div>
                <p class="font-semibold">Lihat Perusahaan</p>
            </a>
            <a href="profil/edit.php" class="bg-gray-600 hover:bg-gray-700 text-white rounded-xl p-5 text-center transition">
                <div class="text-3xl mb-2">👤</div>
                <p class="font-semibold">Edit Profil</p>
            </a>
        </div>

        <!-- Charts untuk Kandidat (2 grafik) -->
        <?php if ($role === 'kandidat'): ?>
        <div class="mt-8 grid md:grid-cols-2 gap-6">
            <div class="bg-white rounded-xl shadow p-6">
                <h3 class="text-md font-semibold mb-4">Status Lamaran</h3>
                <canvas id="chartKandidatStatus"></canvas>
            </div>
            <div class="bg-white rounded-xl shadow p-6">
                <h3 class="text-md font-semibold mb-4">Top Kategori Dilamar</h3>
                <canvas id="chartKandidatKategori"></canvas>
            </div>
        </div>
        <?php endif; ?>

        <?php else: ?>
        <!-- ===================== HRD DASHBOARD ===================== -->

        <!-- Stats -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
            <div class="bg-white rounded-xl shadow p-5 text-center">
                <div class="text-3xl mb-1">📋</div>
                <div class="text-2xl font-bold text-gray-800"><?php echo $stats['total_lowongan'] ?? 0; ?></div>
                <div class="text-gray-500 text-sm">Total Lowongan</div>
            </div>
            <div class="bg-white rounded-xl shadow p-5 text-center">
                <div class="text-3xl mb-1">🟢</div>
                <div class="text-2xl font-bold text-gray-800"><?php echo $stats['lowongan_aktif'] ?? 0; ?></div>
                <div class="text-gray-500 text-sm">Lowongan Aktif</div>
            </div>
            <div class="bg-white rounded-xl shadow p-5 text-center">
                <div class="text-3xl mb-1">📨</div>
                <div class="text-2xl font-bold text-gray-800"><?php echo $stats['total_lamaran'] ?? 0; ?></div>
                <div class="text-gray-500 text-sm">Total Lamaran</div>
            </div>
            <div class="bg-white rounded-xl shadow p-5 text-center">
                <div class="text-3xl mb-1">🔔</div>
                <div class="text-2xl font-bold text-yellow-600"><?php echo $stats['lamaran_baru'] ?? 0; ?></div>
                <div class="text-gray-500 text-sm">Lamaran Baru</div>
            </div>
        </div>

        <div class="grid md:grid-cols-2 gap-8">

            <!-- Lamaran Terbaru Masuk -->
            <div class="bg-white rounded-xl shadow p-6">
                <div class="flex justify-between items-center mb-5">
                    <h2 class="text-lg font-bold text-gray-800">Lamaran Terbaru Masuk</h2>
                    <a href="lamaran/list.php" class="text-blue-600 text-sm hover:underline">Lihat semua →</a>
                </div>
                <?php if (!$hrd || $recentLamaran->num_rows === 0): ?>
                    <p class="text-gray-400 text-center py-8">Belum ada lamaran masuk.</p>
                <?php else: ?>
                    <div class="space-y-3">
                        <?php while ($la = $recentLamaran->fetch_assoc()): ?>
                        <a href="lamaran/detail.php?id=<?php echo $la['id_lamaran']; ?>"
                           class="flex items-center justify-between p-3 rounded-lg hover:bg-gray-50 transition">
                            <div>
                                <p class="font-medium text-gray-800 text-sm"><?php echo htmlspecialchars($la['nama_kandidat']); ?></p>
                                <p class="text-gray-400 text-xs"><?php echo htmlspecialchars($la['judul_lowongan']); ?></p>
                            </div>
                            <span class="text-xs font-semibold px-2 py-1 rounded-full <?php echo $statusColors[$la['status_lamaran']]; ?>">
                                <?php echo ucfirst($la['status_lamaran']); ?>
                            </span>
                        </a>
                        <?php endwhile; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Lowongan Saya -->
            <div class="bg-white rounded-xl shadow p-6">
                <div class="flex justify-between items-center mb-5">
                    <h2 class="text-lg font-bold text-gray-800">Lowongan Saya</h2>
                    <a href="lowongan/list.php" class="text-blue-600 text-sm hover:underline">Lihat semua →</a>
                </div>
                <?php if (!$hrd || $myLowongan->num_rows === 0): ?>
                    <p class="text-gray-400 text-center py-8">Belum ada lowongan.</p>
                <?php else: ?>
                    <div class="space-y-3">
                        <?php while ($lo = $myLowongan->fetch_assoc()): ?>
                        <a href="lowongan/detail.php?id=<?php echo $lo['id_lowongan']; ?>"
                           class="flex items-center justify-between p-3 rounded-lg hover:bg-gray-50 transition">
                            <div>
                                <p class="font-medium text-gray-800 text-sm"><?php echo htmlspecialchars($lo['judul_lowongan']); ?></p>
                                <p class="text-gray-400 text-xs"><?php echo $lo['jumlah_pelamar']; ?>/<?php echo $lo['kuota_pelamar']; ?> pelamar · Tutup <?php echo date('d M', strtotime($lo['tanggal_tutup'])); ?></p>
                            </div>
                            <span class="text-xs font-semibold px-2 py-1 rounded-full <?php echo $statusLowongan[$lo['status']]; ?>">
                                <?php echo ucfirst($lo['status']); ?>
                            </span>
                        </a>
                        <?php endwhile; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Quick Actions HRD -->
        <div class="mt-8 grid grid-cols-2 md:grid-cols-4 gap-4">
            <a href="lowongan/tambah.php" class="bg-blue-600 hover:bg-blue-700 text-white rounded-xl p-5 text-center transition">
                <div class="text-3xl mb-2">➕</div>
                <p class="font-semibold">Tambah Lowongan</p>
            </a>
            <a href="lowongan/list.php" class="bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl p-5 text-center transition">
                <div class="text-3xl mb-2">📋</div>
                <p class="font-semibold">Kelola Lowongan</p>
            </a>
            <a href="kandidat/list.php" class="bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl p-5 text-center transition">
                <div class="text-3xl mb-2">👥</div>
                <p class="font-semibold">Lihat Kandidat</p>
            </a>
            <a href="profil/edit.php" class="bg-gray-600 hover:bg-gray-700 text-white rounded-xl p-5 text-center transition">
                <div class="text-3xl mb-2">👤</div>
                <p class="font-semibold">Edit Profil</p>
            </a>
        </div>

        <!-- Charts untuk HRD (2 grafik) -->
        <?php if ($role !== 'kandidat'): ?>
        <div class="mt-8 grid md:grid-cols-2 gap-6">
            <div class="bg-white rounded-xl shadow p-6">
                <h3 class="text-md font-semibold mb-4">Distribusi Status Lamaran</h3>
                <canvas id="chartHrdStatus"></canvas>
            </div>
            <div class="bg-white rounded-xl shadow p-6">
                <h3 class="text-md font-semibold mb-4">Rata-rata Penerimaan per Lowongan (%)</h3>
                <canvas id="chartHrdAcceptance"></canvas>
            </div>
        </div>
        <?php endif; ?>

        <?php endif; ?>
    </div>

    <script>
    <?php if ($role === 'kandidat' && isset($kandidat)): ?>
        <?php
        // status counts already in $stats
        $k_labels = array_map('ucfirst', array_keys($stats));
        $k_values = array_values($stats);

        // Top kategori yang dilamar oleh kandidat
        $stmtCat = $conn->prepare("SELECT kp.nama_kategori, COUNT(*) AS cnt FROM lamaran l JOIN lowongan lo ON l.id_lowongan = lo.id_lowongan JOIN kategori_pekerjaan kp ON lo.id_kategori = kp.id_kategori WHERE l.id_kandidat = ? GROUP BY kp.id_kategori ORDER BY cnt DESC LIMIT 5");
        $stmtCat->bind_param("s", $kandidat['id_kandidat']);
        $stmtCat->execute();
        $catRes = $stmtCat->get_result();
        $k_cat_labels = [];
        $k_cat_values = [];
        while ($row = $catRes->fetch_assoc()) {
            $k_cat_labels[] = $row['nama_kategori'];
            $k_cat_values[] = (int) $row['cnt'];
        }
        ?>
        const kandidatStatusLabels = <?php echo json_encode($k_labels); ?>;
        const kandidatStatusData = <?php echo json_encode($k_values); ?>;
        const kandidatKategoriLabels = <?php echo json_encode($k_cat_labels); ?>;
        const kandidatKategoriData = <?php echo json_encode($k_cat_values); ?>;

        (function(){
            const ctx1 = document.getElementById('chartKandidatStatus');
            if (ctx1) new Chart(ctx1, { type: 'pie', data: { labels: kandidatStatusLabels, datasets: [{ data: kandidatStatusData, backgroundColor: ['#60A5FA','#FBBF24','#34D399','#F87171'] }] }, options: { responsive:true } });

            const ctx2 = document.getElementById('chartKandidatKategori');
            if (ctx2) new Chart(ctx2, { type: 'bar', data: { labels: kandidatKategoriLabels, datasets: [{ label: 'Jumlah Lamaran', data: kandidatKategoriData, backgroundColor: '#3B82F6' }] }, options: { responsive:true, scales:{ y:{ beginAtZero:true, precision:0 } } } });
        })();
    <?php endif; ?>

    <?php if ($role !== 'kandidat' && isset($id_perusahaan)): ?>
        <?php
        // status distribution for company
        $stmtHS = $conn->prepare("SELECT la.status_lamaran, COUNT(*) AS cnt FROM lamaran la JOIN lowongan lo ON la.id_lowongan = lo.id_lowongan WHERE lo.id_perusahaan = ? GROUP BY la.status_lamaran");
        $stmtHS->bind_param("s", $id_perusahaan);
        $stmtHS->execute();
        $resHS = $stmtHS->get_result();
        $h_labels = [];
        $h_values = [];
        while ($r = $resHS->fetch_assoc()) { $h_labels[] = ucfirst($r['status_lamaran']); $h_values[] = (int)$r['cnt']; }

        // rata-rata penerimaan per lowongan
        $stmtAccept = $conn->prepare("SELECT lo.judul_lowongan, SUM(CASE WHEN la.status_lamaran='diterima' THEN 1 ELSE 0 END) AS diterima, COUNT(la.id_lamaran) AS total FROM lowongan lo LEFT JOIN lamaran la ON la.id_lowongan = lo.id_lowongan WHERE lo.id_perusahaan = ? GROUP BY lo.id_lowongan ORDER BY total DESC LIMIT 6");
        $stmtAccept->bind_param("s", $id_perusahaan);
        $stmtAccept->execute();
        $accRes = $stmtAccept->get_result();
        $h_accept_labels = [];
        $h_accept_values = [];
        while ($row = $accRes->fetch_assoc()) {
            $h_accept_labels[] = mb_substr($row['judul_lowongan'], 0, 18);
            $h_accept_values[] = $row['total'] > 0 ? round((int)$row['diterima'] / (int)$row['total'] * 100, 2) : 0;
        }
        ?>
        const hrdStatusLabels = <?php echo json_encode($h_labels); ?>;
        const hrdStatusData = <?php echo json_encode($h_values); ?>;
        const hrdAcceptLabels = <?php echo json_encode($h_accept_labels); ?>;
        const hrdAcceptData = <?php echo json_encode($h_accept_values); ?>;

        (function(){
            const ctxA = document.getElementById('chartHrdStatus');
            if (ctxA) new Chart(ctxA, { type: 'doughnut', data: { labels: hrdStatusLabels, datasets: [{ data: hrdStatusData, backgroundColor: ['#60A5FA','#FBBF24','#34D399','#F87171'] }] }, options: { responsive:true } });

            const ctxB = document.getElementById('chartHrdAcceptance');
            if (ctxB) new Chart(ctxB, { type: 'bar', data: { labels: hrdAcceptLabels, datasets: [{ label: 'Persentase Diterima', data: hrdAcceptData, backgroundColor: '#3B82F6' }] }, options: { responsive:true, scales:{ y:{ beginAtZero:true, max:100, ticks:{ stepSize:10 } } } } });
        })();
    <?php endif; ?>
    </script>

</body>
</html>
