<?php
require_once __DIR__ . '/../config.php';

$nama = $_GET['nama'] ?? '';
$tgl  = $_GET['tgl'] ?? '';

// Jika form di-submit
if($_SERVER['REQUEST_METHOD']=="POST"){

    $nama   = mysqli_real_escape_string($conn,$_POST['nama']);
    $tgl    = mysqli_real_escape_string($conn,$_POST['tgl']);
    $alasan = mysqli_real_escape_string($conn,$_POST['alasan']);

    mysqli_query($conn,"
        INSERT INTO izin_status(nama,tanggal,status,alasan)
        VALUES('$nama','$tgl','rejected','$alasan')
        ON DUPLICATE KEY UPDATE status='rejected', alasan='$alasan'
    ");

    // Kembali ke halaman izin_list
    header("Location: index.php?page=izin_list&msg=rejected");
    exit;
}

?>

<h3 class="mb-3">Tolak Izin <b><?= htmlspecialchars($nama) ?></b></h3>

<form method="POST" style="max-width:450px; padding:15px; background:#fff; border-radius:8px;">
    
    <input type="hidden" name="nama" value="<?= htmlspecialchars($nama) ?>">
    <input type="hidden" name="tgl"  value="<?= htmlspecialchars($tgl) ?>">

    <label><b>Alasan Penolakan</b></label>
    <textarea name="alasan" class="form-control" required style="height:120px"></textarea>

    <div class="mt-3 d-flex gap-2">
        <button class="btn btn-danger">Tolak</button>
        <!-- kembali ke izin_list normal -->
        <a href="index.php?page=izin_list" class="btn btn-secondary">Batal</a>
    </div>
</form>
