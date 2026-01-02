<?php
require_once __DIR__ . '/../config.php';

$nama = $_GET['nama'] ?? '';
$tgl  = $_GET['tgl'] ?? '';

if(!$nama || !$tgl){
    header("Location: index.php?page=izin_list&msg=invalid");
    exit;
}

// simpan ke database (insert atau update jika sudah ada)
mysqli_query($conn,"
    INSERT INTO izin_status (nama, tanggal, status)
    VALUES ('$nama', '$tgl', 'approved')
    ON DUPLICATE KEY UPDATE status='approved'
");

// kembali ke halaman izin_list
header("Location: index.php?page=izin_list&msg=approved");
exit;
