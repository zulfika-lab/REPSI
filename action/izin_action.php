<?php
require_once "../config.php";

$id = $_POST['id'];
$aksi = $_POST['aksi'];

if($aksi=="approve"){
    mysqli_query($conn,"UPDATE izin SET status='approved', alasan='' WHERE id='$id'");
}

if($aksi=="reject"){
    $alasan = mysqli_real_escape_string($conn,$_POST['alasan']);
    mysqli_query($conn,"UPDATE izin SET status='rejected', alasan='$alasan' WHERE id='$id'");
}

header("Location: ../index.php?page=izin_list");
exit;
?>
