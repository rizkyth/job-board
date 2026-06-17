<?php
require_once '../config/database.php';
require_once '../config/session.php';

requireLogin();

// hanya HRD
if ($_SESSION['role'] !== 'hrd') {
    header("Location: ../dashboard.php");
    exit();
}

// ambil data perusahaan HRD
$queryPerusahaan = "
SELECT 
    h.id_perusahaan,
    p.nama_perusahaan
FROM hrd h
JOIN perusahaan p ON h.id_perusahaan = p.id_perusahaan
WHERE h.id_user = ?
";

$stmtPerusahaan = $conn->prepare($queryPerusahaan);
$stmtPerusahaan->bind_param("s", $_SESSION['id_user']);
$stmtPerusahaan->execute();

$resultPerusahaan = $stmtPerusahaan->get_result();

if ($resultPerusahaan->num_rows === 0) {
    die("Data perusahaan tidak ditemukan");
}

$perusahaan = $resultPerusahaan->fetch_assoc();

// kategori
$queryKategori = "
SELECT * 
FROM kategori_pekerjaan
WHERE is_active = TRUE
ORDER BY nama_kategori ASC
";

$resultKategori = $conn->query($queryKategori);

$error = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $judul = sanitize($_POST['judul_lowongan']);
    $kategori = sanitize($_POST['id_kategori']);
    $lokasi = sanitize($_POST['lokasi']);
    $tipe = sanitize($_POST['tipe_pekerjaan']);
    $deskripsi = sanitize($_POST['deskripsi_pekerjaan']);
    $kualifikasi = sanitize($_POST['kualifikasi']);
    $gaji_min = sanitize($_POST['gaji_min']);
    $gaji_max = sanitize($_POST['gaji_max']);
    $kuota = sanitize($_POST['kuota_pelamar']);
    $tanggal_tutup = sanitize($_POST['tanggal_tutup']);

    // validasi
    if (
        empty($judul) ||
        empty($kategori) ||
        empty($lokasi) ||
        empty($tipe) ||
        empty($deskripsi) ||
        empty($kualifikasi) ||
        empty($tanggal_tutup)
    ) {
        $error = "Semua field wajib diisi";
    } else {

        // generate id lowongan
        $id_lowongan = uniqid('LOW');

        // upload poster optional
        $poster_url = null;

        if (
            isset($_FILES['poster']) &&
            $_FILES['poster']['error'] === 0
        ) {

            $targetDir = "../uploads/poster/";

            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0777, true);
            }

            $fileName = time() . "_" . basename($_FILES['poster']['name']);
            $targetFile = $targetDir . $fileName;

            $allowed = ['jpg', 'jpeg', 'png', 'webp'];

            $ext = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));

            if (in_array($ext, $allowed)) {

                move_uploaded_file(
                    $_FILES['poster']['tmp_name'],
                    $targetFile
                );

                $poster_url = "uploads/poster/" . $fileName;
            }
        }

        // insert
        $queryInsert = "
        INSERT INTO lowongan (
            id_lowongan,
            id_perusahaan,
            id_kategori,
            judul_lowongan,
            deskripsi_pekerjaan,
            kualifikasi,
            lokasi,
            tipe_pekerjaan,
            gaji_min,
            gaji_max,
            kuota_pelamar,
            tanggal_tutup,
            catatan_hr,
            status,
            created_at
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'buka', NOW()
        )
        ";

        $stmtInsert = $conn->prepare($queryInsert);

        if ($stmtInsert === false) {
            $error = "Gagal menyiapkan kueri: " . $conn->error;
        } else {
            $stmtInsert->bind_param(
                "ssssssssiiis",
                $id_lowongan,
                $perusahaan['id_perusahaan'],
                $kategori,
                $judul,
                $deskripsi,
                $kualifikasi,
                $lokasi,
                $tipe,
                $gaji_min,
                $gaji_max,
                $kuota,
                $tanggal_tutup
            );

            if ($stmtInsert->execute()) {
                header("Location: list.php?success=tambah");
                exit();
            } else {
                $error = "Gagal menambahkan lowongan";
            }
        }
    }
}

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Lowongan</title>

    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100">

<?php include '../includes/navbar.php'; ?>

<div class="max-w-5xl mx-auto py-10 px-4">

    <div class="bg-white rounded-2xl shadow-md p-8">

        <!-- HEADER -->
        <div class="mb-8">

            <h1 class="text-3xl font-bold text-gray-800">
                Tambah Lowongan
            </h1>

            <p class="text-gray-500 mt-2">
                Buat lowongan pekerjaan baru untuk perusahaan Anda
            </p>

        </div>

        <!-- ERROR -->
        <?php if (!empty($error)): ?>

            <div class="bg-red-100 text-red-700 p-4 rounded-lg mb-6">
                <?php echo $error; ?>
            </div>

        <?php endif; ?>

        <!-- FORM -->
        <form method="POST" enctype="multipart/form-data">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                <!-- JUDUL -->
                <div class="md:col-span-2">

                    <label class="block mb-2 font-semibold text-gray-700">
                        Judul Lowongan
                    </label>

                    <input
                        type="text"
                        name="judul_lowongan"
                        class="w-full border border-gray-300 rounded-lg px-4 py-3"
                        placeholder="Contoh: Frontend Developer"
                        required
                    >

                </div>

                <!-- KATEGORI -->
                <div>

                    <label class="block mb-2 font-semibold text-gray-700">
                        Kategori
                    </label>

                    <select
                        name="id_kategori"
                        class="w-full border border-gray-300 rounded-lg px-4 py-3"
                        required
                    >

                        <option value="">
                            Pilih Kategori
                        </option>

                        <?php while($kategori = $resultKategori->fetch_assoc()): ?>

                            <option value="<?php echo $kategori['id_kategori']; ?>">
                                <?php echo $kategori['nama_kategori']; ?>
                            </option>

                        <?php endwhile; ?>

                    </select>

                </div>

                <!-- TIPE -->
                <div>

                    <label class="block mb-2 font-semibold text-gray-700">
                        Tipe Pekerjaan
                    </label>

                    <select
                        name="tipe_pekerjaan"
                        class="w-full border border-gray-300 rounded-lg px-4 py-3"
                        required
                    >

                        <option value="">Pilih Tipe</option>
                        <option value="full_time">Full Time</option>
                        <option value="part_time">Part Time</option>
                        <option value="kontrak">Kontrak</option>
                        <option value="magang">Magang</option>

                    </select>

                </div>

                <!-- LOKASI -->
                <div>

                    <label class="block mb-2 font-semibold text-gray-700">
                        Lokasi
                    </label>

                    <input
                        type="text"
                        name="lokasi"
                        class="w-full border border-gray-300 rounded-lg px-4 py-3"
                        placeholder="Jakarta"
                        required
                    >

                </div>

                <!-- KUOTA -->
                <div>

                    <label class="block mb-2 font-semibold text-gray-700">
                        Kuota Pelamar
                    </label>

                    <input
                        type="number"
                        name="kuota_pelamar"
                        class="w-full border border-gray-300 rounded-lg px-4 py-3"
                        required
                    >

                </div>

                <!-- GAJI -->
                <div>

                    <label class="block mb-2 font-semibold text-gray-700">
                        Gaji Minimum
                    </label>

                    <input
                        type="number"
                        name="gaji_min"
                        class="w-full border border-gray-300 rounded-lg px-4 py-3"
                    >

                </div>

                <div>

                    <label class="block mb-2 font-semibold text-gray-700">
                        Gaji Maksimum
                    </label>

                    <input
                        type="number"
                        name="gaji_max"
                        class="w-full border border-gray-300 rounded-lg px-4 py-3"
                    >

                </div>

                <!-- TANGGAL -->
                <div class="md:col-span-2">

                    <label class="block mb-2 font-semibold text-gray-700">
                        Tanggal Tutup
                    </label>

                    <input
                        type="date"
                        name="tanggal_tutup"
                        class="w-full border border-gray-300 rounded-lg px-4 py-3"
                        required
                    >

                </div>

                <!-- DESKRIPSI -->
                <div class="md:col-span-2">

                    <label class="block mb-2 font-semibold text-gray-700">
                        Deskripsi Pekerjaan
                    </label>

                    <textarea
                        name="deskripsi_pekerjaan"
                        rows="6"
                        class="w-full border border-gray-300 rounded-lg px-4 py-3"
                        required
                    ></textarea>

                </div>

                <!-- KUALIFIKASI -->
                <div class="md:col-span-2">

                    <label class="block mb-2 font-semibold text-gray-700">
                        Kualifikasi
                    </label>

                    <textarea
                        name="kualifikasi"
                        rows="6"
                        class="w-full border border-gray-300 rounded-lg px-4 py-3"
                        required
                    ></textarea>

                </div>

                <!-- POSTER -->
                <div class="md:col-span-2">

                    <label class="block mb-2 font-semibold text-gray-700">
                        Poster Lowongan (Optional)
                    </label>

                    <input
                        type="file"
                        name="poster"
                        class="w-full border border-gray-300 rounded-lg px-4 py-3"
                    >

                </div>

            </div>

            <!-- BUTTON -->
            <div class="flex justify-end gap-4 mt-8">

                <a
                    href="list.php"
                    class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-6 py-3 rounded-lg"
                >
                    Kembali
                </a>

                <button
                    type="submit"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-semibold"
                >
                    Simpan Lowongan
                </button>

            </div>

        </form>

    </div>

</div>

</body>
</html>