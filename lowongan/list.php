<?php
require_once '../config/database.php';
require_once '../config/session.php';

requireLogin();

$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$kategori = isset($_GET['kategori']) ? sanitize($_GET['kategori']) : '';
$tipe = isset($_GET['tipe']) ? sanitize($_GET['tipe']) : '';

$where = [];
$params = [];
$types = "";

// role kandidat hanya lihat lowongan buka
if ($_SESSION['role'] === 'kandidat') {
    $where[] = "l.status = 'buka'";
    $where[] = "l.tanggal_tutup >= CURDATE()";
}

// role HRD hanya lihat lowongan perusahaannya
if ($_SESSION['role'] === 'hrd') {

    $where[] = "l.id_perusahaan = (
        SELECT id_perusahaan 
        FROM hrd 
        WHERE id_user = ?
    )";

    $params[] = $_SESSION['id_user'];
    $types .= "s";
}

if (!empty($search)) {
    $where[] = "(l.judul_lowongan LIKE ? OR p.nama_perusahaan LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types .= "ss";
}

if (!empty($kategori)) {
    $where[] = "k.id_kategori = ?";
    $params[] = $kategori;
    $types .= "s";
}

if (!empty($tipe)) {
    $where[] = "l.tipe_pekerjaan = ?";
    $params[] = $tipe;
    $types .= "s";
}

$whereSQL = "";

if (count($where) > 0) {
    $whereSQL = "WHERE " . implode(" AND ", $where);
}

$query = "
SELECT 
    l.*,
    p.nama_perusahaan,
    p.logo_url,
    k.nama_kategori
FROM lowongan l
JOIN perusahaan p ON l.id_perusahaan = p.id_perusahaan
JOIN kategori_pekerjaan k ON l.id_kategori = k.id_kategori
$whereSQL
ORDER BY l.created_at DESC
";

$stmt = $conn->prepare($query);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

// kategori
$queryKategori = "SELECT * FROM kategori_pekerjaan WHERE is_active = TRUE";
$resultKategori = $conn->query($queryKategori);

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lowongan Kerja</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100">

<?php include(__DIR__ . '/../includes/navbar.php'); ?>

<div class="max-w-7xl mx-auto py-10 px-4">

    <!-- HEADER -->
    <div class="flex flex-col md:flex-row md:justify-between md:items-center gap-4 mb-8">

        <div>
            <h1 class="text-3xl font-bold text-gray-800">
                Lowongan Kerja
            </h1>

            <p class="text-gray-500 mt-2">
                Temukan pekerjaan terbaik untuk karir Anda
            </p>
        </div>

        <?php if ($_SESSION['role'] === 'hrd'): ?>

            <a
                href="tambah.php"
                class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-3 rounded-lg font-semibold"
            >
                + Tambah Lowongan
            </a>

        <?php endif; ?>

    </div>

    <!-- FILTER -->
    <div class="bg-white rounded-xl shadow-md p-6 mb-8">

        <form method="GET">

            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">

                <div>
                    <input
                        type="text"
                        name="search"
                        value="<?php echo htmlspecialchars($search); ?>"
                        placeholder="Cari lowongan..."
                        class="w-full border border-gray-300 rounded-lg px-4 py-3"
                    >
                </div>

                <div>

                    <select
                        name="kategori"
                        class="w-full border border-gray-300 rounded-lg px-4 py-3"
                    >

                        <option value="">
                            Semua Kategori
                        </option>

                        <?php while($kat = $resultKategori->fetch_assoc()): ?>

                            <option
                                value="<?php echo $kat['id_kategori']; ?>"
                                <?php echo ($kategori == $kat['id_kategori']) ? 'selected' : ''; ?>
                            >
                                <?php echo $kat['nama_kategori']; ?>
                            </option>

                        <?php endwhile; ?>

                    </select>

                </div>

                <div>

                    <select
                        name="tipe"
                        class="w-full border border-gray-300 rounded-lg px-4 py-3"
                    >

                        <option value="">Semua Tipe</option>
                        <option value="full_time">Full Time</option>
                        <option value="part_time">Part Time</option>
                        <option value="kontrak">Kontrak</option>
                        <option value="magang">Magang</option>

                    </select>

                </div>

                <div>

                    <button
                        type="submit"
                        class="w-full bg-gray-800 hover:bg-gray-900 text-white px-4 py-3 rounded-lg font-semibold"
                    >
                        Cari
                    </button>

                </div>

            </div>

        </form>

    </div>

    <!-- LIST LOWONGAN -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <?php if ($result->num_rows > 0): ?>

            <?php while($row = $result->fetch_assoc()): ?>

                <div class="bg-white rounded-xl shadow-md p-6 hover:shadow-lg transition">

                    <div class="flex gap-4">

                        <!-- LOGO -->
                        <div>

                            <?php if (!empty($row['logo_url'])): ?>

                                <img
                                    src="../<?php echo $row['logo_url']; ?>"
                                    class="w-20 h-20 rounded-lg object-cover border"
                                >

                            <?php else: ?>

                                <div class="w-20 h-20 bg-gray-200 rounded-lg"></div>

                            <?php endif; ?>

                        </div>

                        <!-- CONTENT -->
                        <div class="flex-1">

                            <div class="flex justify-between items-start gap-4">

                                <div>

                                    <h2 class="text-xl font-bold text-gray-800">
                                        <?php echo htmlspecialchars($row['judul_lowongan']); ?>
                                    </h2>

                                    <p class="text-gray-600 mt-1">
                                        <?php echo htmlspecialchars($row['nama_perusahaan']); ?>
                                    </p>

                                </div>

                                <!-- STATUS -->
                                <div>

                                    <?php
                                    $statusColor = 'bg-gray-200 text-gray-700';

                                    if ($row['status'] === 'buka') {
                                        $statusColor = 'bg-green-100 text-green-700';
                                    }

                                    if ($row['status'] === 'tutup') {
                                        $statusColor = 'bg-red-100 text-red-700';
                                    }

                                    if ($row['status'] === 'draft') {
                                        $statusColor = 'bg-yellow-100 text-yellow-700';
                                    }
                                    ?>

                                    <span class="<?php echo $statusColor; ?> px-3 py-1 rounded-full text-xs font-semibold">
                                        <?php echo strtoupper($row['status']); ?>
                                    </span>

                                </div>

                            </div>

                            <!-- BADGE -->
                            <div class="flex flex-wrap gap-2 mt-4">

                                <span class="bg-blue-100 text-blue-700 px-3 py-1 rounded-full text-xs">
                                    <?php echo $row['nama_kategori']; ?>
                                </span>

                                <span class="bg-purple-100 text-purple-700 px-3 py-1 rounded-full text-xs">
                                    <?php echo $row['tipe_pekerjaan']; ?>
                                </span>

                                <span class="bg-gray-100 text-gray-700 px-3 py-1 rounded-full text-xs">
                                    <?php echo $row['lokasi']; ?>
                                </span>

                            </div>

                            <!-- DESKRIPSI -->
                            <p class="text-gray-600 text-sm mt-4 line-clamp-3">
                                <?php echo substr(strip_tags($row['deskripsi_pekerjaan']), 0, 150); ?>...
                            </p>

                            <!-- FOOTER -->
                            <div class="flex justify-between items-center mt-6">

                                <div>

                                    <p class="font-bold text-blue-600">
                                        Rp <?php echo number_format($row['gaji_min']); ?>
                                        -
                                        Rp <?php echo number_format($row['gaji_max']); ?>
                                    </p>

                                    <p class="text-xs text-gray-500 mt-1">
                                        Pelamar:
                                        <?php echo $row['jumlah_pelamar']; ?> /
                                        <?php echo $row['kuota_pelamar']; ?>
                                    </p>

                                </div>

                                <div class="flex gap-2">

                                    <a
                                        href="detail.php?id=<?php echo $row['id_lowongan']; ?>"
                                        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm"
                                    >
                                        Detail
                                    </a>

                                    <?php if ($_SESSION['role'] === 'hrd'): ?>

                                        <a
                                            href="edit.php?id=<?php echo $row['id_lowongan']; ?>"
                                            class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded-lg text-sm"
                                        >
                                            Edit
                                        </a>

                                        <a
                                            href="hapus.php?id=<?php echo $row['id_lowongan']; ?>"
                                            onclick="return confirm('Yakin ingin menghapus lowongan ini?')"
                                            class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg text-sm"
                                        >
                                            Hapus
                                        </a>

                                    <?php endif; ?>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>

            <?php endwhile; ?>

        <?php else: ?>

            <div class="col-span-2">

                <div class="bg-white rounded-xl shadow-md p-10 text-center">

                    <h2 class="text-2xl font-bold text-gray-700 mb-3">
                        Lowongan Tidak Ditemukan
                    </h2>

                    <p class="text-gray-500">
                        Belum ada lowongan yang tersedia saat ini
                    </p>

                </div>

            </div>

        <?php endif; ?>

    </div>

</div>

</body>
</html>