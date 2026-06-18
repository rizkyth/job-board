<?php
require_once '../config/database.php';
require_once '../config/session.php';

requireLogin();

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$id_lowongan = sanitize($_GET['id']);

// Ambil detail lowongan
$query = "
SELECT 
    l.*,
    p.nama_perusahaan,
    p.logo_url,
    p.website,
    p.kota,
    p.deskripsi AS deskripsi_perusahaan,
    k.nama_kategori
FROM lowongan l
JOIN perusahaan p ON l.id_perusahaan = p.id_perusahaan
JOIN kategori_pekerjaan k ON l.id_kategori = k.id_kategori
WHERE l.id_lowongan = ?
";

$stmt = $conn->prepare($query);
$stmt->bind_param("s", $id_lowongan);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Lowongan tidak ditemukan");
}

$lowongan = $result->fetch_assoc();

$posterLink = '';
if (!empty($lowongan['poster_url'])) {
    $posterLink = $lowongan['poster_url'];
    if (!preg_match('#^(https?://|/|\.\./)#i', $posterLink)) {
        $posterLink = '../' . $posterLink;
    }
}

// cek apakah kandidat sudah melamar
$sudahMelamar = false;

if ($_SESSION['role'] === 'kandidat') {

    $queryCheck = "
    SELECT id_lamaran 
    FROM lamaran
    WHERE id_lowongan = ?
    AND id_kandidat = (
        SELECT id_kandidat 
        FROM kandidat
        WHERE id_user = ?
    )
    ";

    $stmtCheck = $conn->prepare($queryCheck);
    $stmtCheck->bind_param("ss", $id_lowongan, $_SESSION['id_user']);
    $stmtCheck->execute();

    $sudahMelamar = $stmtCheck->get_result()->num_rows > 0;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Lowongan</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100">

<?php include '../includes/navbar.php'; ?>

<div class="max-w-6xl mx-auto py-10 px-4">

    <!-- CARD LOWONGAN -->
    <div class="bg-white rounded-xl shadow-md p-8 mb-8">

        <div class="flex justify-between items-start flex-wrap gap-4">

            <div class="flex gap-4">

                <?php if (!empty($lowongan['logo_url'])): ?>
                    <img 
                        src="../<?php echo $lowongan['logo_url']; ?>" 
                        class="w-20 h-20 rounded-lg object-cover border"
                    >
                <?php else: ?>
                    <div class="w-20 h-20 bg-gray-200 rounded-lg"></div>
                <?php endif; ?>

                <div>

                    <h1 class="text-3xl font-bold text-gray-800">
                        <?php echo htmlspecialchars($lowongan['judul_lowongan']); ?>
                    </h1>

                    <p class="text-lg text-gray-600 mt-1">
                        <?php echo htmlspecialchars($lowongan['nama_perusahaan']); ?>
                    </p>

                    <div class="flex flex-wrap gap-2 mt-4">

                        <span class="bg-blue-100 text-blue-700 px-3 py-1 rounded-full text-sm">
                            <?php echo $lowongan['nama_kategori']; ?>
                        </span>

                        <span class="bg-green-100 text-green-700 px-3 py-1 rounded-full text-sm">
                            <?php echo $lowongan['tipe_pekerjaan']; ?>
                        </span>

                        <span class="bg-gray-100 text-gray-700 px-3 py-1 rounded-full text-sm">
                            <?php echo $lowongan['lokasi']; ?>
                        </span>

                    </div>

                </div>

            </div>

            <!-- APPLY BUTTON -->
            <div>

                <?php if ($_SESSION['role'] === 'kandidat'): ?>

                    <?php if ($sudahMelamar): ?>

                        <button 
                            class="bg-gray-400 text-white px-6 py-3 rounded-lg cursor-not-allowed"
                            disabled
                        >
                            Sudah Melamar
                        </button>

                    <?php elseif ($lowongan['status'] !== 'buka'): ?>

                        <button 
                            class="bg-red-500 text-white px-6 py-3 rounded-lg cursor-not-allowed"
                            disabled
                        >
                            Lowongan Ditutup
                        </button>

                    <?php else: ?>

                        <a 
                            href="../lamaran/tambah.php?id=<?php echo $lowongan['id_lowongan']; ?>"
                            class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-semibold"
                        >
                            Lamar Sekarang
                        </a>

                    <?php endif; ?>

                <?php endif; ?>

            </div>

        </div>

    </div>

    <?php if (!empty($posterLink)): ?>
        <div class="bg-white rounded-xl shadow-md p-6 mb-8">
            <img src="<?php echo htmlspecialchars($posterLink); ?>" alt="Poster Lowongan" class="w-full h-auto rounded-xl object-cover">
        </div>
    <?php endif; ?>

    <!-- CONTENT -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- DESKRIPSI -->
        <div class="lg:col-span-2 space-y-6">

            <div class="bg-white rounded-xl shadow-md p-6">

                <h2 class="text-2xl font-bold mb-4">
                    Deskripsi Pekerjaan
                </h2>

                <div class="text-gray-700 leading-relaxed whitespace-pre-line">
                    <?php echo htmlspecialchars($lowongan['deskripsi_pekerjaan']); ?>
                </div>

            </div>

            <div class="bg-white rounded-xl shadow-md p-6">

                <h2 class="text-2xl font-bold mb-4">
                    Kualifikasi
                </h2>

                <div class="text-gray-700 leading-relaxed whitespace-pre-line">
                    <?php echo htmlspecialchars($lowongan['kualifikasi']); ?>
                </div>

            </div>

        </div>

        <!-- SIDEBAR -->
        <div class="space-y-6">

            <!-- INFO -->
            <div class="bg-white rounded-xl shadow-md p-6">

                <h2 class="text-xl font-bold mb-4">
                    Informasi Lowongan
                </h2>

                <div class="space-y-4 text-sm">

                    <div>
                        <p class="text-gray-500">Gaji</p>
                        <p class="font-semibold">
                            Rp <?php echo number_format($lowongan['gaji_min']); ?>
                            -
                            Rp <?php echo number_format($lowongan['gaji_max']); ?>
                        </p>
                    </div>

                    <div>
                        <p class="text-gray-500">Kuota</p>
                        <p class="font-semibold">
                            <?php echo $lowongan['kuota_pelamar']; ?> Orang
                        </p>
                    </div>

                    <div>
                        <p class="text-gray-500">Pelamar</p>
                        <p class="font-semibold">
                            <?php echo $lowongan['jumlah_pelamar']; ?> Orang
                        </p>
                    </div>

                    <div>
                        <p class="text-gray-500">Tanggal Tutup</p>
                        <p class="font-semibold">
                            <?php echo $lowongan['tanggal_tutup']; ?>
                        </p>
                    </div>

                </div>

            </div>

            <!-- PERUSAHAAN -->
            <div class="bg-white rounded-xl shadow-md p-6">

                <h2 class="text-xl font-bold mb-4">
                    Tentang Perusahaan
                </h2>

                <div class="space-y-3">

                    <h3 class="font-bold text-lg">
                        <?php echo $lowongan['nama_perusahaan']; ?>
                    </h3>

                    <p class="text-gray-600 text-sm">
                        <?php echo $lowongan['kota']; ?>
                    </p>

                    <p class="text-gray-700 text-sm leading-relaxed">
                        <?php echo substr($lowongan['deskripsi_perusahaan'], 0, 200); ?>...
                    </p>

                    <?php if (!empty($lowongan['website'])): ?>

                        <a 
                            href="<?php echo $lowongan['website']; ?>"
                            target="_blank"
                            class="text-blue-600 hover:underline text-sm"
                        >
                            Kunjungi Website
                        </a>

                    <?php endif; ?>

                </div>

            </div>

        </div>

    </div>

</div>

</body>
</html>