<?php
require_once '../config/database.php';
require_once '../config/session.php';

requireLogin();

$role    = $_SESSION['role'];
$error   = $_GET['error'] ?? '';
$success = '';

// Ambil data profil saat ini
if ($role === 'kandidat') {
    $stmt = $conn->prepare("SELECT * FROM kandidat WHERE id_user = ?");
    if ($stmt === false) {
        die("Prepare failed: " . $conn->error);
    }
    $stmt->bind_param("s", $_SESSION['id_user']);
    $stmt->execute();
    $profil = $stmt->get_result()->fetch_assoc();
} else {
    $stmt = $conn->prepare("SELECT h.*, p.nama_perusahaan FROM hrd h
                            JOIN perusahaan p ON h.id_perusahaan = p.id_perusahaan
                            WHERE h.id_user = ?");
    if ($stmt === false) {
        die("Prepare failed: " . $conn->error);
    }
    $stmt->bind_param("s", $_SESSION['id_user']);
    $stmt->execute();
    $profil = $stmt->get_result()->fetch_assoc();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($role === 'kandidat') {
        $nama_lengkap        = sanitize($_POST['nama_lengkap']);
        $no_telepon          = sanitize($_POST['no_telepon']);
        $alamat              = sanitize($_POST['alamat']);
        $tanggal_lahir       = sanitize($_POST['tanggal_lahir']);
        $pendidikan_terakhir = sanitize($_POST['pendidikan_terakhir']);
        $pengalaman_kerja    = (int)$_POST['pengalaman_kerja'];
        $cv_url              = sanitize($_POST['cv_url']);
        $foto_url            = sanitize($_POST['foto_url']);
        $bio                 = sanitize($_POST['bio']);

        if (empty($nama_lengkap)) {
            $error = "Nama lengkap tidak boleh kosong.";
        } else {
            if ($profil) {
                // Update
                $upd = $conn->prepare("UPDATE kandidat SET nama_lengkap=?, no_telepon=?, alamat=?, tanggal_lahir=?, pendidikan_terakhir=?, pengalaman_kerja=?, cv_url=?, foto_url=?, bio=? WHERE id_user=?");
                if ($upd === false) {
                    $error = "Prepare failed: " . $conn->error;
                } else {
                    $upd->bind_param("sssssissss", $nama_lengkap, $no_telepon, $alamat, $tanggal_lahir, $pendidikan_terakhir, $pengalaman_kerja, $cv_url, $foto_url, $bio, $_SESSION['id_user']);
                    $upd->execute();
                }
            } else {
                // Insert
                $id_kandidat = generateUUID();
                $ins = $conn->prepare("INSERT INTO kandidat (id_kandidat, id_user, nama_lengkap, no_telepon, alamat, tanggal_lahir, pendidikan_terakhir, pengalaman_kerja, cv_url, foto_url, bio) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
                if ($ins === false) {
                    $error = "Error prepare statement: " . $conn->error;
                } else {
                    $ins->bind_param("sssssssisss", $id_kandidat, $_SESSION['id_user'], $nama_lengkap, $no_telepon, $alamat, $tanggal_lahir, $pendidikan_terakhir, $pengalaman_kerja, $cv_url, $foto_url, $bio);
                    $ins->execute();
                }
            }
            $success = "Profil berhasil diperbarui!";
            // Refresh profil
            if (!isset($error) || $error === '') {
                $stmt = $conn->prepare("SELECT * FROM kandidat WHERE id_user = ?");
                if ($stmt === false) {
                    $error = "Prepare failed: " . $conn->error;
                } else {
                    $stmt->bind_param("s", $_SESSION['id_user']);
                    $stmt->execute();
                    $profil = $stmt->get_result()->fetch_assoc();
                }
            }
        }
    } else {
        // HRD
        $nama_lengkap = sanitize($_POST['nama_lengkap']);
        $jabatan      = sanitize($_POST['jabatan']);
        $no_ext       = sanitize($_POST['no_ext']);

        if (empty($nama_lengkap)) {
            $error = "Nama lengkap tidak boleh kosong.";
        } else {
            $upd = $conn->prepare("UPDATE hrd SET nama_lengkap=?, jabatan=?, no_ext=? WHERE id_user=?");
            if ($upd === false) {
                $error = "Prepare failed: " . $conn->error;
            } else {
                $upd->bind_param("ssss", $nama_lengkap, $jabatan, $no_ext, $_SESSION['id_user']);
                $upd->execute();
                $success = "Profil berhasil diperbarui!";
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
    <title>Edit Profil - Job Board</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen">
    <?php include '../includes/navbar.php'; ?>

    <div class="max-w-2xl mx-auto px-6 py-10">
        <div class="bg-white rounded-xl shadow p-8">
            <h1 class="text-2xl font-bold text-gray-800 mb-1">Edit Profil</h1>
            <p class="text-gray-400 text-sm mb-8">Lengkapi informasi profil Anda</p>

            <?php if ($error): ?>
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-6"><?php echo $success; ?></div>
            <?php endif; ?>

            <form method="POST" class="space-y-5">

                <?php if ($role === 'kandidat'): ?>

                <div>
                    <label class="block text-gray-700 font-semibold mb-1">Nama Lengkap <span class="text-red-500">*</span></label>
                    <input type="text" name="nama_lengkap" required
                           value="<?php echo htmlspecialchars($profil['nama_lengkap'] ?? ''); ?>"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-gray-700 font-semibold mb-1">No. Telepon</label>
                        <input type="tel" name="no_telepon"
                               value="<?php echo htmlspecialchars($profil['no_telepon'] ?? ''); ?>"
                               placeholder="08xxxxxxxxxx"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-gray-700 font-semibold mb-1">Tanggal Lahir</label>
                        <input type="date" name="tanggal_lahir"
                               value="<?php echo htmlspecialchars($profil['tanggal_lahir'] ?? ''); ?>"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>

                <div>
                    <label class="block text-gray-700 font-semibold mb-1">Alamat</label>
                    <textarea name="alamat" rows="2"
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"><?php echo htmlspecialchars($profil['alamat'] ?? ''); ?></textarea>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-gray-700 font-semibold mb-1">Pendidikan Terakhir</label>
                        <select name="pendidikan_terakhir"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">-- Pilih --</option>
                            <?php foreach(['SMA/SMK','D1','D2','D3','D4','S1','S2','S3'] as $p): ?>
                                <option value="<?php echo $p; ?>" <?php if(($profil['pendidikan_terakhir']??'') === $p) echo 'selected'; ?>><?php echo $p; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-gray-700 font-semibold mb-1">Pengalaman Kerja (tahun)</label>
                        <input type="number" name="pengalaman_kerja" min="0" max="50"
                               value="<?php echo htmlspecialchars($profil['pengalaman_kerja'] ?? 0); ?>"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>

                <div>
                    <label class="block text-gray-700 font-semibold mb-1">Link CV / Portfolio</label>
                    <input type="url" name="cv_url"
                           value="<?php echo htmlspecialchars($profil['cv_url'] ?? ''); ?>"
                           placeholder="https://drive.google.com/your-cv"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-gray-700 font-semibold mb-1">Link Foto Profil</label>
                    <input type="url" name="foto_url"
                           value="<?php echo htmlspecialchars($profil['foto_url'] ?? ''); ?>"
                           placeholder="https://..."
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-gray-700 font-semibold mb-1">Bio / Tentang Saya</label>
                    <textarea name="bio" rows="4"
                              placeholder="Ceritakan tentang diri Anda, keahlian, dan pengalaman..."
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"><?php echo htmlspecialchars($profil['bio'] ?? ''); ?></textarea>
                </div>

                <?php else: /* HRD */ ?>

                <div class="bg-gray-50 rounded-lg p-4 mb-2">
                    <p class="text-sm text-gray-500">Perusahaan</p>
                    <p class="font-semibold text-gray-800"><?php echo htmlspecialchars($profil['nama_perusahaan'] ?? '-'); ?></p>
                </div>

                <div>
                    <label class="block text-gray-700 font-semibold mb-1">Nama Lengkap <span class="text-red-500">*</span></label>
                    <input type="text" name="nama_lengkap" required
                           value="<?php echo htmlspecialchars($profil['nama_lengkap'] ?? ''); ?>"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-gray-700 font-semibold mb-1">Jabatan</label>
                        <input type="text" name="jabatan"
                               value="<?php echo htmlspecialchars($profil['jabatan'] ?? ''); ?>"
                               placeholder="HR Manager, Recruiter..."
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-gray-700 font-semibold mb-1">No. Ext</label>
                        <input type="text" name="no_ext"
                               value="<?php echo htmlspecialchars($profil['no_ext'] ?? ''); ?>"
                               placeholder="123"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>

                <?php endif; ?>

                <div class="flex gap-4 pt-4">
                    <a href="../dashboard.php"
                       class="flex-1 text-center bg-gray-200 hover:bg-gray-300 text-gray-700 font-semibold py-3 rounded-lg transition">
                        Batal
                    </a>
                    <button type="submit"
                            class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 rounded-lg transition">
                        Simpan Profil
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>