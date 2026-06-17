<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/job-board/config/database.php';
?>
<nav class="bg-blue-600 text-white shadow-lg sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-6 py-4">
        <div class="flex justify-between items-center">
            <a href="/job-board/dashboard.php" class="text-2xl font-bold">💼 Job Board</a>
            
            <div class="flex gap-6 items-center">
                <?php if ($_SESSION['role'] === 'kandidat'): ?>
                    <a href="/job-board/lowongan/list.php" class="hover:text-blue-200 transition">Lowongan</a>
                    <a href="/job-board/perusahaan/list.php" class="hover:text-blue-200 transition">Perusahaan</a>
                    <a href="/job-board/lamaran/myapplications.php" class="hover:text-blue-200 transition">Lamaran Saya</a>
                <?php elseif ($_SESSION['role'] === 'hrd'): ?>
                    <a href="/job-board/lowongan/list.php" class="hover:text-blue-200 transition">Lowongan</a>
                    <a href="/job-board/kandidat/list.php" class="hover:text-blue-200 transition">Kandidat</a>
                    <a href="/job-board/lamaran/list.php" class="hover:text-blue-200 transition">Lamaran</a>
                <?php endif; ?>
                
                <div class="relative flex gap-2">
                    <button class="active:text-blue-200 transition flex items-center gap-2">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"></path></svg>
                        <?php echo substr($_SESSION['email'], 0, 20); ?> ▼
                    </button>
                    <div class="block  bg-white text-gray-800 rounded shadow-lg w-max-content">
                        <a href="/job-board/auth/logout.php" class="block px-4 py-2 hover:bg-gray-100 rounded-lg">Logout</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</nav>