<?php
require_once '../config/database.php';
require_once '../config/session.php';

requireLogin();

if ($_SESSION['role'] !== 'kandidat') {
    header("Location: ../dashboard.php");
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_lengkap = sanitize($_POST['nama_lengkap']);
    $no_telepon = sanitize($_POST['no_telepon']);
    $alamat = sanitize($_POST['alamat']);
    $tanggal_lahir = sanitize($_POST['tanggal_lahir']);
    $pendidikan_terakhir = sanitize($_POST['pendidikan_terakhir']);
    $pengalaman_kerja = sanitize($_POST['pengalaman_kerja']);
    $bio = sanitize($_POST['bio']);
    
    // Handle file uploads
    $cv_url = '';
    $foto_url = '';
    
    if (isset($_FILES['cv']) && $_FILES['cv']['error'] === 0) {
        $cv_ext = pathinfo($_FILES['cv']['name'], PATHINFO_EXTENSION);
        $cv_name = 'cv_' . $_SESSION['id_user'] . '.' . $cv_ext;
        $cv_path = '../uploads/cv/' . $cv_name;
        
        if (!is_dir('../uploads/cv')) {
            mkdir('../uploads/cv', 0755, true);
        }
        
        if (move_uploaded_file($_FILES['cv']['tmp_name'], $cv_path)) {
            $cv_url = 'uploads/cv/' . $cv_name;
        }
    }
    
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === 0) {
        $foto_ext = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
        $foto_name = 'foto_' . $_SESSION['id_user'] . '.' . $foto_ext;
        $foto_path = '../uploads/foto/' . $foto_name;
        
        if (!is_dir('../uploads/foto')) {
            mkdir('../uploads/foto', 0755, true);
        }
        
        if (move_uploaded_file($_FILES['foto']['tmp_name'], $foto_path)) {
            $foto_url = 'uploads/foto/' . $foto_name;
        }
    }
    
    if (empty($nama_lengkap) || empty($no_telepon) || empty($alamat) || empty($tanggal_lahir) || empty($pendidikan_terakhir) || empty($pengalaman_kerja) || empty($bio)) {
        $error = "Semua field harus diisi";
    } else {
        $id_kandidat = generateUUID();
        $query = "INSERT INTO kandidat (id_kandidat, id_user, nama_lengkap, no_telepon, alamat, tanggal_lahir, pendidikan_terakhir, pengalaman_kerja, cv_url, foto_url, bio) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("sssssssisss", $id_kandidat, $_SESSION['id_user'], $nama_lengkap, $no_telepon, $alamat, $tanggal_lahir, $pendidikan_terakhir, $pengalaman_kerja, $cv_url, $foto_url, $bio);
        
        if ($stmt->execute()) {
            $_SESSION['id_kandidat'] = $id_kandidat;
            header("Location: ../dashboard.php");
            exit();
        } else {
            $error = "Terjadi kesalahan saat menyimpan profil: " . $stmt->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lengkapi Profil - Job Board</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen py-12">
    <div class="max-w-2xl mx-auto">
        <div class="bg-white rounded-lg shadow-lg p-8">
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold text-gray-800">Lengkapi Profil Anda</h1>
                <p class="text-gray-500 mt-2">Informasi ini akan membantu perusahaan mengenal Anda lebih baik</p>
            </div>

            <?php if ($error): ?>
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" class="space-y-6">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">Nama Lengkap *</label>
                        <input type="text" name="nama_lengkap" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">No. Telepon *</label>
                        <input type="tel" name="no_telepon" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>

                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Alamat *</label>
                    <textarea name="alamat" required rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">Tanggal Lahir *</label>
                        <input type="date" name="tanggal_lahir" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">Pendidikan Terakhir *</label>
                        <select name="pendidikan_terakhir" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">-- Pilih --</option>
                            <option value="SMA">SMA/Sederajat</option>
                            <option value="D3">Diploma (D3)</option>
                            <option value="S1">Sarjana (S1)</option>
                            <option value="S2">Master (S2)</option>
                            <option value="S3">Doktor (S3)</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Pengalaman Kerja (tahun) *</label>
                    <input type="number" name="pengalaman_kerja" min="0" max="70" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Bio/Deskripsi Singkat *</label>
                    <textarea name="bio" required rows="4" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Ceritakan tentang Anda..."></textarea>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">Foto Profil</label>
                        <input type="file" name="foto" accept="image/*" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">CV (PDF/DOC) *</label>
                        <input type="file" name="cv" accept=".pdf,.doc,.docx" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>

                <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg transition duration-200">
                    Simpan Profil
                </button>
            </form>
        </div>
    </div>
</body>
</html>