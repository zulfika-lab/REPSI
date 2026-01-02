<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

$page = $_GET['page'] ?? 'dashboard';

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php'; // sidebar tetap di kiri fixed
?>

<!-- ================= MAIN CONTENT ================= -->
<div class="content"> <!-- pastikan class ini sama dengan style di sidebar.php -->
<?php
$file = __DIR__ . "/pages/{$page}.php";

if (file_exists($file)) {
    include $file;
} else {
    echo "<h3>Halaman tidak ditemukan.</h3>";
}
?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
