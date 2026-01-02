<?php
$page = $_GET['page'] ?? 'dashboard';
?>

<style>
/* agar sidebar tetap di kiri */
.sidebar {
    width: 220px;
    height: 100vh;
    background: #2A27F5;
    background-image: url('img/komdigi.png'); /* path gambar */
    background-size: cover;       /* gambar menyesuaikan area */
    background-position: center;  /* fokus di tengah */
    background-repeat: no-repeat;
    
    /* Optional agar teks tetap terbaca*/
    background-blend-mode: overlay;
    background-color: rgba(34, 34, 255, 0.85); /* biru transparan */

    color: white;
    position: fixed;
    top: 0;
    left: 0;
    padding: 20px;
    backdrop-filter: blur(2px); /* efek mewah */
}


/* agar menu rapi */
.sidebar h5 {
    font-size: 22px;
    margin-bottom: 40px;
    margin-top: 1px;
    font-weight: bold;
}

.sidebar h4 {
    font-size: 22px;
    margin-bottom: 10px;
    font-weight: bold;
}

.sidebar a {
    color: #fff;
    text-decoration: none;
    padding: 10px 8px;
    display: block;
    border-radius: 6px;
    margin-bottom: 4px;
}

.sidebar a:hover,
.sidebar a.active {
    background: #5969FF;
}

/* konten pindah ke kanan */
.content {
    margin-left: 240px;   /* jarak dari sidebar */
    padding: 25px;
}
</style>

<div class="sidebar">
    <h4>REPSI</h4>
    <h5>REKAP ABSENSI</h5>
    
    <a href="index.php?page=dashboard"      class="<?= $page=='dashboard'?'active':'' ?>">Dashboard</a>
    <a href="index.php?page=terlambat"      class="<?= $page=='terlambat'?'active':'' ?>">Rekapan Terlambat</a>
    <a href="index.php?page=bulanan"        class="<?= $page=='bulanan'?'active':'' ?>">Rekapan Bulanan</a>
    <a href="index.php?page=izin_list"      class="<?= $page=='izin_list'?'active':'' ?>">Daftar Izin</a>
</div>
