<?php
require_once '../config/database.php';
require_once '../config/session.php';

requireRole('kandidat');

$id_lowongan = sanitize($_GET['id'] ?? '');
if (empty($id_lowongan)) {
    header("Location: ../lowongan/list.php");
    exit();
}

// Ambil data lowongan
$query = "SELECT l.*, p.nama_perusahaan FROM lowongan l
          JOIN perusahaan p ON l.id_perusahaan = p.id_perusahaan
          WHERE l.id_lowongan = ? AND l.status = 'buka'";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $id_lowongan);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: ../lowongan/list.php?error=Lowongan tidak tersedia");
    exit();
}
$lowongan = $result->fetch_assoc();

// Ambil data kandidat
$stmtK = $conn->prepare("SELECT * FROM kandidat WHERE id_user = ?");
$stmtK->bind_param("s", $_SESSION['id_user']);
$stmtK->execute();
$kandidat = $stmtK->get_result()->fetch_assoc();

if (!$kandidat) {
    header("Location: ../profil/edit.php?error=Lengkapi profil Anda terlebih dahulu");
    exit();
}

// Cek sudah melamar?
$stmtCek = $conn->prepare("SELECT id_lamaran FROM lamaran WHERE id_kandidat = ? AND id_lowongan = ?");
$stmtCek->bind_param("ss", $kandidat['id_kandidat'], $id_lowongan);
$stmtCek->execute();
if ($stmtCek->get_result()->num_rows > 0) {
    header("Location: list.php?error=Anda sudah melamar ke lowongan ini");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $surat_lamaran = sanitize($_POST['surat_lamaran']);
    $cv_url        = sanitize($_POST['cv_url']);

    if (empty($surat_lamaran)) {
        $error = "Surat lamaran tidak boleh kosong.";
    } else {
        $id_lamaran = generateUUID();
            $catatan_hr = '';
            $ins = $conn->prepare("INSERT INTO lamaran (id_lamaran, id_kandidat, id_lowongan, cv_url, surat_lamaran, catatan_hr) VALUES (?,?,?,?,?,?)");
            $ins->bind_param("ssssss", $id_lamaran, $kandidat['id_kandidat'], $id_lowongan, $cv_url, $surat_lamaran, $catatan_hr);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lamar Pekerjaan - Job Board</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen">
    <?php include '../includes/navbar.php'; ?>

    <div class="max-w-3xl mx-auto px-6 py-10">
        <!-- Info Lowongan -->
        <div class="bg-blue-50 border border-blue-200 rounded-xl p-5 mb-8">
            <p class="text-xs text-blue-500 uppercase font-semibold mb-1">Melamar untuk</p>
            <h2 class="text-xl font-bold text-blue-900"><?php echo htmlspecialchars($lowongan['judul_lowongan']); ?></h2>
            <p class="text-blue-700"><?php echo htmlspecialchars($lowongan['nama_perusahaan']); ?> &mdash; <?php echo htmlspecialchars($lowongan['lokasi']); ?></p>
        </div>

        <div class="bg-white rounded-xl shadow p-8">
            <h1 class="text-2xl font-bold text-gray-800 mb-6">Form Lamaran</h1>

            <?php if ($error): ?>
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-6">
                <!-- CV dari profil -->
                <div class="bg-gray-50 rounded-lg p-4">
                    <p class="text-sm text-gray-500 mb-1">CV dari profil Anda</p>
                    <p class="text-gray-800 font-medium">
                        <?php if ($kandidat['cv_url']): ?>
                            <a href="<?php echo htmlspecialchars($kandidat['cv_url']); ?>" target="_blank" class="text-blue-600 hover:underline">
                                📎 Lihat CV
                            </a>
                        <?php else: ?>
                            <span class="text-gray-400">Belum ada CV di profil</span>
                        <?php endif; ?>
                    </p>
                </div>

                <div>
                    <label class="block text-gray-700 font-semibold mb-2">
                        Link CV / Portfolio <span class="font-normal text-gray-400">(opsional, untuk mengganti CV profil)</span>
                    </label>
                    <input type="url" name="cv_url"
                           value="<?php echo htmlspecialchars($kandidat['cv_url'] ?? ''); ?>"
                           placeholder="https://drive.google.com/your-cv"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Surat Lamaran <span class="text-red-500">*</span></label>
                    <textarea name="surat_lamaran" required rows="8"
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                              placeholder="Tuliskan surat lamaran Anda di sini. Ceritakan mengapa Anda tertarik dan cocok untuk posisi ini..."></textarea>
                    <p class="text-gray-400 text-xs mt-1">Minimal 100 karakter. Tulis dengan jelas dan profesional.</p>
                </div>

                <!-- Ringkasan profil -->
                <div class="bg-gray-50 rounded-lg p-4 text-sm text-gray-600 space-y-1">
                    <p class="font-semibold text-gray-700 mb-2">📋 Ringkasan Profil Anda</p>
                    <p>Nama: <span class="font-medium text-gray-800"><?php echo htmlspecialchars($kandidat['nama_lengkap']); ?></span></p>
                    <p>Pendidikan: <span class="font-medium text-gray-800"><?php echo htmlspecialchars($kandidat['pendidikan_terakhir'] ?? '-'); ?></span></p>
                    <p>Pengalaman: <span class="font-medium text-gray-800"><?php echo ($kandidat['pengalaman_kerja'] ?? 0); ?> tahun</span></p>
                </div>

                <div class="flex gap-4 pt-2">
                    <a href="../lowongan/detail.php?id=<?php echo $id_lowongan; ?>"
                       class="flex-1 text-center bg-gray-200 hover:bg-gray-300 text-gray-700 font-semibold py-3 rounded-lg transition">
                        Batal
                    </a>
                    <button type="submit"
                            class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 rounded-lg transition">
                        Kirim Lamaran
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
