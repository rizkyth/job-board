<?php
require_once '../config/database.php';
require_once '../config/session.php';

requireLogin();

if ($_SESSION['role'] !== 'kandidat') {
    header("Location: ../dashboard.php");
    exit();
}

// ambil id kandidat
$queryKandidat = "
SELECT id_kandidat
FROM kandidat
WHERE id_user = ?
";

$stmtKandidat = $conn->prepare($queryKandidat);
$stmtKandidat->bind_param("s", $_SESSION['id_user']);
$stmtKandidat->execute();

$resultKandidat = $stmtKandidat->get_result();

if ($resultKandidat->num_rows === 0) {
    die("Profil kandidat belum ditemukan");
}

$kandidat = $resultKandidat->fetch_assoc();
$id_kandidat = $kandidat['id_kandidat'];

// ambil semua lamaran
$query = "
SELECT 
    l.*,
    lo.judul_lowongan,
    lo.lokasi,
    lo.tipe_pekerjaan,
    lo.gaji_min,
    lo.gaji_max,
    lo.status AS status_lowongan,
    p.nama_perusahaan,
    p.logo_url
FROM lamaran l
JOIN lowongan lo ON l.id_lowongan = lo.id_lowongan
JOIN perusahaan p ON lo.id_perusahaan = p.id_perusahaan
WHERE l.id_kandidat = ?
ORDER BY l.tanggal_lamar DESC
";

$stmt = $conn->prepare($query);
$stmt->bind_param("s", $id_kandidat);
$stmt->execute();

$result = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lamaran Saya</title>

    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100">

<?php include '../includes/navbar.php'; ?>

<div class="max-w-7xl mx-auto py-10 px-4">

    <!-- HEADER -->
    <div class="mb-8">

        <h1 class="text-3xl font-bold text-gray-800">
            Lamaran Saya
        </h1>

        <p class="text-gray-500 mt-2">
            Daftar semua pekerjaan yang pernah Anda lamar
        </p>

    </div>

    <!-- LIST -->
    <div class="space-y-6">

        <?php if ($result->num_rows > 0): ?>

            <?php while($row = $result->fetch_assoc()): ?>

                <?php
                $statusColor = 'bg-gray-100 text-gray-700';

                if ($row['status_lamaran'] === 'menunggu') {
                    $statusColor = 'bg-yellow-100 text-yellow-700';
                }

                if ($row['status_lamaran'] === 'proses') {
                    $statusColor = 'bg-blue-100 text-blue-700';
                }

                if ($row['status_lamaran'] === 'diterima') {
                    $statusColor = 'bg-green-100 text-green-700';
                }

                if ($row['status_lamaran'] === 'ditolak') {
                    $statusColor = 'bg-red-100 text-red-700';
                }
                ?>

                <div class="bg-white rounded-xl shadow-md p-6">

                    <div class="flex flex-col lg:flex-row gap-6">

                        <!-- LOGO -->
                        <div>

                            <?php if (!empty($row['logo_url'])): ?>

                                <img
                                    src="../<?php echo $row['logo_url']; ?>"
                                    class="w-24 h-24 rounded-xl border object-cover"
                                >

                            <?php else: ?>

                                <div class="w-24 h-24 rounded-xl bg-gray-200"></div>

                            <?php endif; ?>

                        </div>

                        <!-- CONTENT -->
                        <div class="flex-1">

                            <div class="flex flex-col lg:flex-row lg:justify-between gap-4">

                                <div>

                                    <h2 class="text-2xl font-bold text-gray-800">
                                        <?php echo htmlspecialchars($row['judul_lowongan']); ?>
                                    </h2>

                                    <p class="text-gray-600 mt-1">
                                        <?php echo htmlspecialchars($row['nama_perusahaan']); ?>
                                    </p>

                                    <div class="flex flex-wrap gap-2 mt-4">

                                        <span class="bg-gray-100 text-gray-700 px-3 py-1 rounded-full text-sm">
                                            <?php echo $row['lokasi']; ?>
                                        </span>

                                        <span class="bg-blue-100 text-blue-700 px-3 py-1 rounded-full text-sm">
                                            <?php echo $row['tipe_pekerjaan']; ?>
                                        </span>

                                    </div>

                                </div>

                                <!-- STATUS -->
                                <div>

                                    <span class="<?php echo $statusColor; ?> px-4 py-2 rounded-full text-sm font-semibold">
                                        <?php echo strtoupper($row['status_lamaran']); ?>
                                    </span>

                                </div>

                            </div>

                            <!-- INFO -->
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-6">

                                <div>
                                    <p class="text-sm text-gray-500">
                                        Gaji
                                    </p>

                                    <p class="font-semibold text-blue-600">
                                        Rp <?php echo number_format($row['gaji_min']); ?>
                                        -
                                        Rp <?php echo number_format($row['gaji_max']); ?>
                                    </p>
                                </div>

                                <div>
                                    <p class="text-sm text-gray-500">
                                        Tanggal Lamar
                                    </p>

                                    <p class="font-semibold">
                                        <?php echo date('d M Y H:i', strtotime($row['tanggal_lamar'])); ?>
                                    </p>
                                </div>

                                <div>
                                    <p class="text-sm text-gray-500">
                                        Status Lowongan
                                    </p>

                                    <p class="font-semibold">
                                        <?php echo strtoupper($row['status_lowongan']); ?>
                                    </p>
                                </div>

                            </div>

                            <!-- SURAT LAMARAN -->
                            <div class="mt-6">

                                <h3 class="font-bold text-gray-800 mb-2">
                                    Surat Lamaran
                                </h3>

                                <div class="bg-gray-50 rounded-lg p-4 text-gray-700 text-sm leading-relaxed whitespace-pre-line">
                                    <?php echo htmlspecialchars($row['surat_lamaran']); ?>
                                </div>

                            </div>

                            <!-- CATATAN HR -->
                            <?php if (!empty($row['catatan_hr'])): ?>

                                <div class="mt-6">

                                    <h3 class="font-bold text-gray-800 mb-2">
                                        Catatan HRD
                                    </h3>

                                    <div class="bg-blue-50 border border-blue-100 rounded-lg p-4 text-blue-700 text-sm leading-relaxed whitespace-pre-line">
                                        <?php echo htmlspecialchars($row['catatan_hr']); ?>
                                    </div>

                                </div>

                            <?php endif; ?>

                            <!-- ACTION -->
                            <div class="flex flex-wrap gap-3 mt-6">

                                <a
                                    href="../lowongan/detail.php?id=<?php echo $row['id_lowongan']; ?>"
                                    class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm"
                                >
                                    Detail Lowongan
                                </a>

                                <?php if ($row['status_lamaran'] === 'menunggu'): ?>

                                    <a
                                        href="edit.php?id=<?php echo $row['id_lamaran']; ?>"
                                        class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded-lg text-sm"
                                    >
                                        Edit Lamaran
                                    </a>

                                    <a
                                        href="hapus.php?id=<?php echo $row['id_lamaran']; ?>"
                                        onclick="return confirm('Yakin ingin menghapus lamaran ini?')"
                                        class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg text-sm"
                                    >
                                        Hapus
                                    </a>

                                <?php endif; ?>

                                <?php if (!empty($row['cv_url'])): ?>

                                    <a
                                        href="../<?php echo $row['cv_url']; ?>"
                                        target="_blank"
                                        class="bg-gray-800 hover:bg-gray-900 text-white px-4 py-2 rounded-lg text-sm"
                                    >
                                        Lihat CV
                                    </a>

                                <?php endif; ?>

                            </div>

                        </div>

                    </div>

                </div>

            <?php endwhile; ?>

        <?php else: ?>

            <div class="bg-white rounded-xl shadow-md p-12 text-center">

                <h2 class="text-2xl font-bold text-gray-700 mb-3">
                    Belum Ada Lamaran
                </h2>

                <p class="text-gray-500 mb-6">
                    Anda belum pernah melamar pekerjaan
                </p>

                <a
                    href="../lowongan/list.php"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-semibold"
                >
                    Cari Lowongan
                </a>

            </div>

        <?php endif; ?>

    </div>

</div>

</body>
</html>