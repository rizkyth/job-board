<?php
require_once '../config/database.php';
session_start();

if (isset($_SESSION['id_user'])) {
    header("Location: ../dashboard.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email']);
    $password = sanitize($_POST['password']);
    
    $query = "SELECT id_user, email, password_hash, role, is_active FROM users WHERE email = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        if (!$user['is_active']) {
            $error = "Akun Anda tidak aktif";
        } elseif (password_verify($password, $user['password_hash'])) {
            $_SESSION['id_user'] = $user['id_user'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
            
            // Check if profile complete
            if ($user['role'] === 'kandidat') {
                $checkProfile = "SELECT id_kandidat FROM kandidat WHERE id_user = ?";
                $stmtCheck = $conn->prepare($checkProfile);
                $stmtCheck->bind_param("s", $user['id_user']);
                $stmtCheck->execute();
                
                if ($stmtCheck->get_result()->num_rows === 0) {
                    header("Location: ../profil/profil-kandidat-setup.php");
                    exit();
                }
            } elseif ($user['role'] === 'hrd') {
                $checkProfile = "SELECT id_hrd FROM hrd WHERE id_user = ?";
                $stmtCheck = $conn->prepare($checkProfile);
                $stmtCheck->bind_param("s", $user['id_user']);
                $stmtCheck->execute();
                
                if ($stmtCheck->get_result()->num_rows === 0) {
                    header("Location: ../profil/profil-hrd-setup.php");
                    exit();
                }
            }
            
            header("Location: ../dashboard.php");
            exit();
        } else {
            $error = "Email atau password salah";
        }
    } else {
        $error = "Email atau password salah";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Job Board</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen flex items-center justify-center">
    <div class="w-full max-w-md">
        <div class="bg-white rounded-lg shadow-lg p-8">
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold text-gray-800">Job Board</h1>
                <p class="text-gray-500 mt-2">Platform Pencari Kerja Terpercaya</p>
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
                    <label class="block text-gray-700 font-semibold mb-2">Password</label>
                    <input type="password" name="password" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="••••••••">
                </div>

                <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg transition duration-200">
                    Masuk
                </button>
            </form>

            <div class="mt-6 text-center">
                <p class="text-gray-600">Belum punya akun? <a href="register.php" class="text-blue-600 font-semibold hover:underline">Daftar di sini</a></p>
            </div>
        </div>
    </div>
</body>
</html>