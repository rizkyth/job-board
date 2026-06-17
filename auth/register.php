<?php
require_once '../config/database.php';
session_start();

if (isset($_SESSION['id_user'])) {
    header("Location: ../dashboard.php");
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email']);
    $password = sanitize($_POST['password']);
    $confirm_password = sanitize($_POST['confirm_password']);
    $role = sanitize($_POST['role']);
    
    // Validation
    if (empty($email) || empty($password) || empty($role)) {
        $error = "Semua field harus diisi";
    } elseif ($password !== $confirm_password) {
        $error = "Password tidak cocok";
    } elseif (strlen($password) < 6) {
        $error = "Password minimal 6 karakter";
    } elseif (!in_array($role, ['kandidat', 'hrd'])) {
        $error = "Role tidak valid";
    } else {
        // Check if email already exists
        $checkEmail = "SELECT id_user FROM users WHERE email = ?";
        $stmt = $conn->prepare($checkEmail);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows > 0) {
            $error = "Email sudah terdaftar";
        } else {
            // Create user
            $id_user = generateUUID();
            $password_hash = password_hash($password, PASSWORD_BCRYPT);
            
            $insertUser = "INSERT INTO users (id_user, email, password_hash, role, is_active) VALUES (?, ?, ?, ?, TRUE)";
            $stmtInsert = $conn->prepare($insertUser);
            $stmtInsert->bind_param("ssss", $id_user, $email, $password_hash, $role);
            
            if ($stmtInsert->execute()) {
                $_SESSION['id_user'] = $id_user;
                $_SESSION['email'] = $email;
                $_SESSION['role'] = $role;
                
                // Redirect to profile setup
                if ($role === 'kandidat') {
                    header("Location: ../profil/profil-kandidat-setup.php");
                } else {
                    header("Location: ../profil/profil-hrd-setup.php");
                }
                exit();
            } else {
                $error = "Terjadi kesalahan saat mendaftar";
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
    <title>Daftar - Job Board</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen flex items-center justify-center py-12">
    <div class="w-full max-w-md">
        <div class="bg-white rounded-lg shadow-lg p-8">
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold text-gray-800">Daftar</h1>
                <p class="text-gray-500 mt-2">Buat akun baru Anda</p>
            </div>

            <?php if ($error): ?>
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-4">
                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Email</label>
                    <input type="email" name="email" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="email@example.com">
                </div>

                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Pilih Tipe Akun</label>
                    <div class="space-y-2">
                        <label class="flex items-center">
                            <input type="radio" name="role" value="kandidat" required class="mr-3">
                            <span class="text-gray-700">Pencari Kerja (Kandidat)</span>
                        </label>
                        <label class="flex items-center">
                            <input type="radio" name="role" value="hrd" class="mr-3">
                            <span class="text-gray-700">HRD / Perusahaan</span>
                        </label>
                    </div>
                </div>

                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Password</label>
                    <input type="password" name="password" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="••••••••">
                </div>

                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Konfirmasi Password</label>
                    <input type="password" name="confirm_password" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="••••••••">
                </div>

                <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg transition duration-200">
                    Daftar
                </button>
            </form>

            <div class="mt-6 text-center">
                <p class="text-gray-600">Sudah punya akun? <a href="login.php" class="text-blue-600 font-semibold hover:underline">Masuk di sini</a></p>
            </div>
        </div>
    </div>
</body>
</html>