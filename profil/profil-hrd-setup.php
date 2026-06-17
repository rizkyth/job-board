<?php
require_once '../config/database.php';
require_once '../config/session.php';

requireLogin();

if ($_SESSION['role'] !== 'hrd') {
    header("Location: ../dashboard.php");
    exit();
}

$error = '';
$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;

if ($step === 2 && empty($_SESSION['id_perusahaan'])) {
    header("Location: profil-hrd-setup.php?step=1");
    exit();
}

// Step 1: Company Information
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $step === 1) {
    $nama_perusahaan = sanitize($_POST['nama_perusahaan']);
    $industri = sanitize($_POST['industri']);
    $alamat = sanitize($_POST['alamat']);
    $kota = sanitize($_POST['kota']);
    $website = sanitize($_POST['website']);
    $deskripsi = sanitize($_POST['deskripsi']);
    
    $logo_url = '';
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === 0) {
        $logo_ext = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
        $logo_name = 'logo_' . generateUUID() . '.' . $logo_ext;
        $logo_path = '../uploads/logo/' . $logo_name;
        
        if (!is_dir('../uploads/logo')) {
            mkdir('../uploads/logo', 0755, true);
        }
        
        if (move_uploaded_file($_FILES['logo']['tmp_name'], $logo_path)) {
            $logo_url = 'uploads/logo/' . $logo_name;
        }
    }
    
    if (empty($nama_perusahaan) || empty($industri) || empty($alamat) || empty($kota) || empty($deskripsi)) {
        $error = "Semua field harus diisi";
    } else {
        $id_perusahaan = generateUUID();
        $query = "INSERT INTO perusahaan (id_perusahaan, nama_perusahaan, industri, alamat, kota, website, logo_url, deskripsi) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($query);
        if ($stmt === false) {
            $error = "Terjadi kesalahan saat menyiapkan kueri perusahaan: " . $conn->error;
        } else {
            $stmt->bind_param("ssssssss", $id_perusahaan, $nama_perusahaan, $industri, $alamat, $kota, $website, $logo_url, $deskripsi);
            if ($stmt->execute()) {
                $_SESSION['id_perusahaan'] = $id_perusahaan;
                header("Location: profil-hrd-setup.php?step=2");
                exit();
            } else {
                $error = "Terjadi kesalahan saat menyimpan data perusahaan";
            }
        }
    }
}

// Step 2: HRD Profile
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $step === 2) {
    $nama_lengkap = sanitize($_POST['nama_lengkap']);
    $jabatan = sanitize($_POST['jabatan']);
    $no_ext = sanitize($_POST['no_ext']);
    
    if (empty($nama_lengkap) || empty($jabatan)) {
        $error = "Semua field harus diisi";
    } else {
        $id_hrd = generateUUID();
        $id_perusahaan = $_SESSION['id_perusahaan'];
        
        $query = "INSERT INTO hrd (id_hrd, id_user, id_perusahaan, nama_lengkap, jabatan, no_ext, is_active) 
                  VALUES (?, ?, ?, ?, ?, ?, TRUE)";
        
        $stmt = $conn->prepare($query);
        if ($stmt === false) {
            $error = "Terjadi kesalahan saat menyiapkan kueri HRD: " . $conn->error;
        } else {
            $stmt->bind_param("ssssss", $id_hrd, $_SESSION['id_user'], $id_perusahaan, $nama_lengkap, $jabatan, $no_ext);
            if ($stmt->execute()) {
                $_SESSION['id_hrd'] = $id_hrd;
                header("Location: ../dashboard.php");
                exit();
            } else {
                $error = "Terjadi kesalahan saat menyimpan profil HRD";
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
    <title>Lengkapi Profil HRD - Job Board</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen py-12">
    <div class="max-w-2xl mx-auto">
        <div class="bg-white rounded-lg shadow-lg p-8">
            <!-- Progress -->
            <div class="mb-8">
                <div class="flex justify-between mb-4">
                    <div class="<?php echo $step >= 1 ? 'text-blue-600' : 'text-gray-400'; ?>">
                        <div class="flex items-center">
                            <div class="rounded-full w-8 h-8 bg-blue-600 text-white flex items-center justify-center text-sm font-bold">1</div>
                            <span class="ml-2">Perusahaan</span>
                        </div>
                    </div>
                    <div class="<?php echo $step >= 2 ? 'text-blue-600' : 'text-gray-400'; ?>">
                        <div class="flex items-center">
                            <div class="rounded-full w-8 h-8 <?php echo $step >= 2 ? 'bg-blue-600' : 'bg-gray-300'; ?> text-white flex items-center justify-center text-sm font-bold">2</div>
                            <span class="ml-2">Profil HRD</span>
                        </div>
                    </div>
                </div>
            </div>

            <?php if ($error): ?>
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <?php if ($step === 1): ?>
                <div>
                    <h1 class="text-3xl font-bold text-gray-800 mb-2">Informasi Perusahaan</h1>
                    <p class="text-gray-500 mb-8">Lengkapi data perusahaan Anda</p>

                    <form method="POST" enctype="multipart/form-data" class="space-y-6">
                        <div>
                            <label class="block text-gray-700 font-semibold mb-2">Nama Perusahaan *</label>
                            <input type="text" name="nama_perusahaan" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-gray-700 font-semibold mb-2">Industri *</label>
                                <select name="industri" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="">-- Pilih --</option>
                                    <option value="Teknologi">Teknologi</option>
                                    <option value="Keuangan">Keuangan</option>
                                    <option value="E-commerce">E-commerce</option>
                                    <option value="Manufaktur">Manufaktur</option>
                                    <option value="Retail">Retail</option>
                                    <option value="Kesehatan">Kesehatan</option>
                                    <option value="Pendidikan">Pendidikan</option>
                                    <option value="Lainnya">Lainnya</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-gray-700 font-semibold mb-2">Kota *</label>
                                <input type="text" name="kota" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                        </div>

                        <div>
                            <label class="block text-gray-700 font-semibold mb-2">Alamat *</label>
                            <textarea name="alamat" required rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                        </div>

                        <div>
                            <label class="block text-gray-700 font-semibold mb-2">Website</label>
                            <input type="url" name="website" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>

                        <div>
                            <label class="block text-gray-700 font-semibold mb-2">Deskripsi Perusahaan *</label>
                            <textarea name="deskripsi" required rows="4" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Jelaskan tentang perusahaan Anda..."></textarea>
                        </div>

                        <div>
                            <label class="block text-gray-700 font-semibold mb-2">Logo Perusahaan</label>
                            <input type="file" name="logo" accept="image/*" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>

                        <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg transition duration-200">
                            Lanjutkan
                        </button>
                    </form>
                </div>
            <?php elseif ($step === 2): ?>
                <div>
                    <h1 class="text-3xl font-bold text-gray-800 mb-2">Profil HRD</h1>
                    <p class="text-gray-500 mb-8">Lengkapi data profil Anda sebagai HR</p>

                    <form method="POST" class="space-y-6">
                        <div>
                            <label class="block text-gray-700 font-semibold mb-2">Nama Lengkap *</label>
                            <input type="text" name="nama_lengkap" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>

                        <div>
                            <label class="block text-gray-700 font-semibold mb-2">Jabatan *</label>
                            <select name="jabatan" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">-- Pilih --</option>
                                <option value="HR Manager">HR Manager</option>
                                <option value="HR Specialist">HR Specialist</option>
                                <option value="Recruitment Officer">Recruitment Officer</option>
                                <option value="HR Coordinator">HR Coordinator</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-gray-700 font-semibold mb-2">No. Ext (Opsional)</label>
                            <input type="text" name="no_ext" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Contoh: 123">
                        </div>

                        <div class="flex gap-4">
                            <a href="profil-hrd-setup.php?step=1" class="flex-1 text-center bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded-lg transition duration-200">
                                Kembali
                            </a>
                            <button type="submit" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg transition duration-200">
                                Selesai
                            </button>
                        </div>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>